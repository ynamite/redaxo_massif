/**
 * MASSIF rich input — dependency-free inline formatting for MForm text fields.
 *
 * Any `<input data-rich>` is hidden and replaced by a single-line contenteditable
 * plus a small toolbar. `data-rich` lists the allowed tags ("em", "strong,em,mark",
 * empty = all three). The contenteditable is only a view: on every change its HTML
 * is sanitized down to the allowlist and written back to the original input, so
 * storage (REX_VALUE / MForm) stays exactly as it was.
 */
(() => {
	"use strict";

	const TAGS = {
		strong: { title: "Fett", label: "F" },
		em: { title: "Kursiv", label: "K" },
		mark: { title: "Hervorheben", label: "H" },
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
	 * (no attributes, no nesting of the same tag, no newlines, no empty tags).
	 * @returns {string} clean HTML
	 */
	const sanitize = (source, allow) => {
		const out = document.createElement("div");
		const walk = (from, to, open) => {
			for (const node of Array.from(from.childNodes)) {
				if (node.nodeType === Node.TEXT_NODE) {
					const text = node.nodeValue.replace(/[\r\n\t]+/g, " ");
					if (text) to.appendChild(document.createTextNode(text));
					continue;
				}
				if (node.nodeType !== Node.ELEMENT_NODE) continue;
				const tag = node.tagName.toLowerCase();
				if (tag === "br") {
					to.appendChild(document.createTextNode(" "));
					continue;
				}
				if (allow.includes(tag) && !open.includes(tag)) {
					const el = document.createElement(tag);
					walk(node, el, open.concat(tag));
					if (el.textContent !== "") to.appendChild(el);
					continue;
				}
				// unknown tag, or the same tag nested again: unwrap, keep the text
				walk(node, to, open);
			}
		};
		walk(source, out, []);
		return out.innerHTML;
	};

	/** Stored value → clean HTML. DOMParser is inert: no script runs, no image loads. */
	const parseValue = (value, allow) =>
		sanitize(new DOMParser().parseFromString(value || "", "text/html").body, allow);

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
		const stale = input.nextElementSibling;
		if (stale && stale.classList.contains("rich-input")) stale.remove();

		const wrap = document.createElement("div");
		wrap.className = "rich-input";
		const bar = document.createElement("div");
		bar.className = "rich-input-bar";
		const editor = document.createElement("div");
		editor.className = "rich-input-editor form-control";
		editor.contentEditable = "true";
		editor.setAttribute("role", "textbox");
		editor.innerHTML = parseValue(input.value, allow);

		const label = input.closest(".form-group, .rex-form-group")?.querySelector("label");
		if (label) editor.setAttribute("aria-label", label.textContent.trim());

		const sync = () => {
			const html = sanitize(editor, allow);
			input.value = html;
			// MBlock clones DOM nodes when blocks are added/reordered and a clone only
			// inherits the *attribute*, never the property — keep both in step.
			input.setAttribute("value", html);
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
			editor.innerHTML = sanitize(editor, allow);
			sync();
		});
		editor.addEventListener("keydown", (e) => {
			if (e.key === "Enter") {
				e.preventDefault(); // single line
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
			const text = (e.clipboardData?.getData("text/plain") || "").replace(/\s+/g, " ");
			const sel = window.getSelection();
			if (!sel || !sel.rangeCount) return;
			const range = sel.getRangeAt(0);
			range.deleteContents();
			const node = document.createTextNode(text);
			range.insertNode(node);
			range.setStartAfter(node);
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
		for (const input of document.querySelectorAll("input[data-rich]")) upgrade(input);
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
