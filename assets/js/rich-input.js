/**
 * MASSIF rich input — dependency-free inline formatting for MForm text fields.
 *
 * Any `<input data-rich>` or `<textarea data-rich>` is hidden and replaced by a
 * contenteditable plus a small toolbar. `data-rich` lists the allowed tags ("em",
 * "strong,em,mark", empty = all three). Inputs stay single-line (Enter blocked);
 * textareas get a multiline editor where Enter inserts a line break, stored as
 * `<br>` in the value. The contenteditable is only a view: on every change its
 * HTML is sanitized down to the allowlist and written back to the original
 * element, so storage (REX_VALUE / MForm) stays exactly as it was.
 */
(() => {
	"use strict";

	const TAGS = {
		strong: { title: "Fett", label: "Fett" },
		em: { title: "Kursiv", label: "Kursiv" },
		mark: { title: "Hervorheben", label: "Highlight" },
	};
	const ALL = Object.keys(TAGS);

	/** @returns {string[]} allowed tags for this input */
	const allowlist = (input) => {
		const wanted = (input.getAttribute("data-rich") || "")
			.split(",")
			.map((t) => t.trim().toLowerCase())
			.filter((t) => ALL.includes(t));
		return wanted.length ? wanted : ALL;
	};

	/**
	 * Copy `source`'s children into a fresh node, keeping only allowed tags
	 * (no attributes, no nesting of the same tag, no empty tags). Single-line
	 * mode flattens every kind of break to a space; multiline keeps them as
	 * `<br>` and reads block boundaries (`div`/`p` from contenteditable) as
	 * breaks too. Trailing breaks never survive.
	 * @returns {string} clean HTML
	 */
	const sanitize = (source, allow, multiline) => {
		const out = document.createElement("div");
		const walk = (from, to, open) => {
			for (const node of Array.from(from.childNodes)) {
				if (node.nodeType === Node.TEXT_NODE) {
					if (!multiline) {
						const text = node.nodeValue.replace(/[\r\n\t]+/g, " ");
						if (text !== "") to.appendChild(document.createTextNode(text));
						continue;
					}
					node.nodeValue.replace(/\r\n?/g, "\n").replace(/\t+/g, " ").split("\n").forEach((line, i) => {
						if (i > 0) to.appendChild(document.createElement("br"));
						if (line !== "") to.appendChild(document.createTextNode(line));
					});
					continue;
				}
				if (node.nodeType !== Node.ELEMENT_NODE) continue;
				const tag = node.tagName.toLowerCase();
				if (tag === "br") {
					to.appendChild(multiline ? document.createElement("br") : document.createTextNode(" "));
					continue;
				}
				if (allow.includes(tag) && !open.includes(tag)) {
					const el = document.createElement(tag);
					walk(node, el, open.concat(tag));
					if (el.textContent !== "") to.appendChild(el);
					continue;
				}
				// a block element starts a new line in what the browser rendered
				if (multiline && (tag === "div" || tag === "p") && to.hasChildNodes()) {
					to.appendChild(document.createElement("br"));
				}
				// unknown tag, or the same tag nested again: unwrap, keep the text
				walk(node, to, open);
			}
		};
		walk(source, out, []);
		while (out.lastChild && out.lastChild.nodeName === "BR") out.lastChild.remove();
		return out.innerHTML;
	};

	/** Stored value → clean HTML. DOMParser is inert: no script runs, no image loads. */
	const parseValue = (value, allow, multiline) =>
		sanitize(new DOMParser().parseFromString(value || "", "text/html").body, allow, multiline);

	/** Elements of `tag` the current selection sits in or covers. */
	const hitsFor = (editor, tag) => {
		const sel = window.getSelection();
		if (!sel || !sel.rangeCount) return [];
		const range = sel.getRangeAt(0);
		if (!editor.contains(range.commonAncestorContainer)) return [];
		const hits = Array.from(editor.querySelectorAll(tag)).filter((el) =>
			range.intersectsNode(el),
		);
		for (let n = range.commonAncestorContainer; n && n !== editor; n = n.parentNode) {
			if (n.nodeType === Node.ELEMENT_NODE && n.tagName.toLowerCase() === tag && !hits.includes(n)) {
				hits.push(n);
			}
		}
		return hits;
	};

	/**
	 * Toggle `tag` over the selection: covered → unwrap, otherwise wrap.
	 * ponytail: unwrapping drops the whole hit element, it does not split it at the
	 * selection edges — "select part of an italic phrase, un-italicise only that part"
	 * is not a thing editors ask for here. Range splitting if it ever is.
	 */
	const toggle = (editor, tag) => {
		const sel = window.getSelection();
		if (!sel || !sel.rangeCount) return;
		const range = sel.getRangeAt(0);
		if (!editor.contains(range.commonAncestorContainer)) return;

		const hits = hitsFor(editor, tag);
		if (hits.length) {
			for (const el of hits) el.replaceWith(...el.childNodes);
		} else if (!range.collapsed) {
			const el = document.createElement(tag);
			el.appendChild(range.extractContents());
			range.insertNode(el);
			const after = document.createRange();
			after.selectNodeContents(el);
			sel.removeAllRanges();
			sel.addRange(after);
		}
		editor.normalize();
	};

	const upgrade = (input) => {
		// A JS property, not an attribute: MBlock clones blocks, and a clone carries the
		// markup but none of the listeners — so a clone must (and does) re-upgrade.
		if (input.rexRichInput) return;
		input.rexRichInput = true;

		const allow = allowlist(input);
		const multiline = input.tagName === "TEXTAREA";
		const stale = input.nextElementSibling;
		if (stale && stale.classList.contains("rich-input")) stale.remove();

		const wrap = document.createElement("div");
		wrap.className = "rich-input";
		const bar = document.createElement("div");
		bar.className = "rich-input-bar";
		const editor = document.createElement("div");
		editor.className = "rich-input-editor form-control" + (multiline ? " rich-input-editor--multiline" : "");
		editor.contentEditable = "true";
		editor.setAttribute("role", "textbox");
		if (multiline) editor.setAttribute("aria-multiline", "true");
		editor.innerHTML = parseValue(input.value, allow, multiline);

		const label = input.closest(".form-group, .rex-form-group")?.querySelector("label");
		if (label) editor.setAttribute("aria-label", label.textContent.trim());

		const sync = () => {
			const html = sanitize(editor, allow, multiline);
			input.value = html;
			// MBlock clones DOM nodes when blocks are added/reordered and a clone only
			// inherits the markup (an input's value attribute, a textarea's child text),
			// never the live property — keep both in step.
			if (multiline) input.textContent = html;
			else input.setAttribute("value", html);
		};
		const refresh = () => {
			for (const btn of bar.children) {
				btn.setAttribute("aria-pressed", hitsFor(editor, btn.dataset.tag).length ? "true" : "false");
			}
		};
		editor.rexRichRefresh = refresh;

		for (const tag of allow) {
			const btn = document.createElement("button");
			btn.type = "button";
			btn.className = "btn btn-default rich-input-btn";
			btn.dataset.tag = tag;
			btn.title = TAGS[tag].title;
			btn.setAttribute("aria-pressed", "false");
			btn.innerHTML = `<${tag}>${TAGS[tag].label}</${tag}>`;
			btn.addEventListener("mousedown", (e) => e.preventDefault()); // keep the selection
			btn.addEventListener("click", () => {
				toggle(editor, tag);
				sync();
				refresh();
				editor.focus();
			});
			bar.appendChild(btn);
		}

		editor.addEventListener("input", sync);
		editor.addEventListener("blur", () => {
			editor.innerHTML = sanitize(editor, allow, multiline);
			sync();
		});
		editor.addEventListener("keydown", (e) => {
			if (e.key === "Enter") {
				e.preventDefault(); // never let the browser insert div/p soup
				if (!multiline) return; // single line
				const sel = window.getSelection();
				if (!sel || !sel.rangeCount) return;
				const range = sel.getRangeAt(0);
				if (!editor.contains(range.commonAncestorContainer)) return;
				range.deleteContents();
				const br = document.createElement("br");
				range.insertNode(br);
				// a break at the very end needs a filler break to show the new line;
				// sync() trims trailing breaks, so the filler never reaches storage
				if (!br.nextSibling) br.after(document.createElement("br"));
				range.setStartAfter(br);
				range.collapse(true);
				sel.removeAllRanges();
				sel.addRange(range);
				sync();
				return;
			}
			if (!e.metaKey && !e.ctrlKey) return;
			const tag = { b: "strong", i: "em" }[e.key.toLowerCase()];
			if (!tag || !allow.includes(tag)) return;
			e.preventDefault();
			toggle(editor, tag);
			sync();
			refresh();
		});
		editor.addEventListener("paste", (e) => {
			e.preventDefault();
			const raw = e.clipboardData?.getData("text/plain") || "";
			const text = multiline
				? raw.replace(/\r\n?/g, "\n").replace(/[ \t]+/g, " ")
				: raw.replace(/\s+/g, " ");
			const sel = window.getSelection();
			if (!sel || !sel.rangeCount) return;
			const range = sel.getRangeAt(0);
			range.deleteContents();
			const frag = document.createDocumentFragment();
			let last = null;
			text.split("\n").forEach((line, i) => {
				if (i > 0) frag.appendChild((last = document.createElement("br")));
				if (line !== "") frag.appendChild((last = document.createTextNode(line)));
			});
			if (!last) return;
			range.insertNode(frag);
			range.setStartAfter(last);
			range.collapse(true);
			sel.removeAllRanges();
			sel.addRange(range);
			sync();
		});
		editor.addEventListener("drop", (e) => e.preventDefault()); // a dropped image is not text

		// inline, not the `hidden` attribute: be_style's `.form-control { display: block }`
		// is an author rule and beats the UA stylesheet's `[hidden]`.
		input.style.display = "none";
		input.after(wrap);
		wrap.append(bar, editor);
		sync(); // an existing value may itself carry disallowed markup
	};

	const scan = () => {
		for (const input of document.querySelectorAll("input[data-rich],textarea[data-rich]")) upgrade(input);
	};

	document.addEventListener("selectionchange", () => {
		document.activeElement?.rexRichRefresh?.();
	});

	if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", scan);
	else scan();

	// ponytail: one observer instead of pjax/mblock hooks — the backend swaps content via
	// jQuery-triggered `pjax:end` (invisible to native listeners) and MBlock clones blocks
	// without any event at all. Both are just DOM insertions, and upgrade() is idempotent.
	let queued = false;
	new MutationObserver(() => {
		if (queued) return;
		queued = true;
		requestAnimationFrame(() => {
			queued = false;
			scan();
		});
	}).observe(document.documentElement, { childList: true, subtree: true });
})();
