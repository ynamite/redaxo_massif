/*!
 * massif form — yform XHR submit pipeline
 */
import { logger } from "@/js/lib/logger/index.js";
import { clearAlerts, renderFieldErrors } from "./alerts.js";
import { focusFirstError, scrollToElement, setFormBusy } from "./dom.js";
import { parseYformResponse } from "./response.js";

/**
 * Intercept a yform's submit and run it through {@link submitForm}.
 * The listener is bound under the form's `__formAbort` controller so the
 * library can release it when the form is removed on success.
 *
 * @param {HTMLFormElement} form
 * @param {object} options - resolved settings from `initForms`
 */
export const bindSubmit = (form, options) => {
	form.addEventListener(
		"submit",
		(event) => {
			event.preventDefault();
			submitForm(form, options);
		},
		{ signal: form.__formAbort.signal },
	);
};

/**
 * POST the form over fetch and apply yform's response.
 *
 * On a validation error the form is NOT replaced — the visitor's values, the
 * (reusable) CSRF token and yform's honeypot all stay live; only the inline
 * error messages are rendered onto the existing form. Swapping in yform's
 * re-rendered HTML would break the honeypot, whose `_js_enabled` <script> is
 * inert once parsed by DOMParser.
 */
const submitForm = async (form, options) => {
	logger.log("Form", "submit", form.id);
	setFormBusy(form, true);
	clearAlerts(form);

	try {
		const response = await fetch(form.action, {
			method: "POST",
			credentials: "same-origin",
			body: new FormData(form),
		});
		const result = parseYformResponse(await response.text(), form.id);

		if (result.kind === "success") {
			showSuccess(form, result.successEl);
			return;
		}

		if (result.kind === "error") {
			const firstGroup = renderFieldErrors(form, result.errors);
			focusFirstError(firstGroup);
			scrollToElement(firstGroup);
		}
		// "rerender" → nothing to show; the form is simply unlocked below.
	} catch (error) {
		logger.log("Form", "submit failed", error);
		console.error(error);
	} finally {
		if (form.isConnected) setFormBusy(form, false);
	}
};

/**
 * Replace the form with yform's success message.
 */
const showSuccess = (form, successEl) => {
	if (!successEl) return;
	successEl.classList.add("success");
	form.__formAbort?.abort();
	form.__customSelect?.destroy();
	form.replaceWith(successEl);
	scrollToElement(successEl);
};
