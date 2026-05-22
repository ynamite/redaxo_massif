/*!
 * massif form — DOM helpers
 */

const PLACEHOLDER_FIELDS =
	'input[type="text"], input[type="email"], input[type="tel"], input[type="password"], input[type="date"], input[type="number"], input[type="url"], textarea';

/**
 * Toggle a form's busy state: `.submitting` (CSS keys the spinner off it),
 * `aria-busy`, and disabled submit buttons.
 *
 * @param {HTMLFormElement|null} form
 * @param {boolean} busy
 */
export const setFormBusy = (form, busy) => {
	if (!form) return;
	form.classList.toggle("submitting", busy);
	form.setAttribute("aria-busy", busy ? "true" : "false");
	for (const button of form.querySelectorAll('button[type="submit"]')) {
		button.disabled = busy;
	}
};

/**
 * Smooth-scroll an element to the viewport centre.
 *
 * @param {Element|null} element
 */
export const scrollToElement = (element) => {
	element?.scrollIntoView({ behavior: "smooth", block: "center" });
};

/**
 * Focus the first control inside a `.form-group`.
 *
 * @param {Element|null} group
 */
export const focusFirstError = (group) => {
	group?.querySelector("input, select, textarea")?.focus();
};

/**
 * CSS-only floating labels rely on `:placeholder-shown`, which only matches
 * when a field carries a `placeholder` attribute — yform emits none. Stamp a
 * blank placeholder (or the label text) so the CSS can detect an empty field.
 *
 * @param {HTMLFormElement} form
 * @param {boolean} [useLabelText=false] - use the field's label as placeholder text
 */
export const stampPlaceholders = (form, useLabelText = false) => {
	for (const field of form.querySelectorAll(PLACEHOLDER_FIELDS)) {
		if (field.hasAttribute("placeholder")) continue;
		const labelText = useLabelText ? form.querySelector(`label[for="${field.id}"]`)?.textContent.trim() : "";
		field.setAttribute("placeholder", labelText || " ");
	}
};
