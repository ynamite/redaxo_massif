/*!
 * massif form — per-form wiring
 */
import { logger } from "@/js/lib/logger/index.js";
import { clearAlerts } from "./alerts.js";
import { stampPlaceholders } from "./dom.js";
import { bindSubmit } from "./submit.js";

/**
 * Wire a single form: floating-label placeholders, optional custom-select /
 * file-upload enhancements, the submit handler (yform or Mailchimp), and the
 * confirm + error-clearing helpers. Re-runnable — any previous wiring on the
 * same element is torn down first, so it is safe to call again after an
 * in-place form swap.
 *
 * @param {HTMLFormElement} form
 * @param {object} options - resolved settings from `initForms`
 */
export const setupForm = (form, options) => {
	logger.log("Form", "setupForm", form.id);

	form.__formAbort?.abort();
	form.__formAbort = new AbortController();
	const { signal } = form.__formAbort;

	if (options.animateLabels) {
		form.classList.add("rex-yform--floating-labels");
		stampPlaceholders(form, options.usePlaceholders);
	}

	if (options.customSelect) initCustomSelect(form);
	if (form.querySelector(".form-group-mupload")) initFileUpload(form);

	if (form.classList.contains("mailchimp")) {
		initMailchimp(form);
	} else {
		bindSubmit(form, options);
	}

	bindConfirm(form, signal);
	bindErrorClearing(form, signal);

	for (const callback of options.callbacks.initForm) {
		if (typeof callback === "function") callback(form);
	}

	document.dispatchEvent(new CustomEvent("forms_ready", { detail: form }));
};

/** Guard `[data-confirm]` controls with a native confirm() dialog. */
const bindConfirm = (form, signal) => {
	form.addEventListener(
		"click",
		(event) => {
			const trigger = event.target.closest("[data-confirm]");
			if (trigger && !window.confirm(trigger.dataset.confirm)) {
				event.preventDefault();
				event.stopPropagation();
			}
		},
		{ capture: true, signal },
	);
};

/** Clear a field's error markers as soon as the visitor gives it a value. */
const bindErrorClearing = (form, signal) => {
	const clear = (event) => {
		const field = event.target;
		if (field.matches("input, select, textarea") && field.value) {
			clearAlerts(field.closest(".form-group"));
		}
	};
	for (const type of ["blur", "keyup", "change"]) {
		form.addEventListener(type, clear, { capture: true, signal });
	}
};

const initCustomSelect = async (form) => {
	const { default: CustomSelect } = await import("./custom-select.js");
	form.__customSelect = new CustomSelect(form);
	form.__customSelect.init();
};

const initFileUpload = async (form) => {
	const { fileUpload } = await import("./file-upload.js");
	await fileUpload(form);
};

const initMailchimp = async (form) => {
	const { default: AjaxChimp } = await import("./mailchimp.js");
	const responseDiv = form.querySelector(".mailchimp-response");
	AjaxChimp()(form, {
		language: "de",
		responseDiv,
		callback: () => {
			form.classList.remove("submitting");
			window.setTimeout(() => {
				if (!responseDiv) return;
				responseDiv.innerHTML = "";
				responseDiv.setAttribute("hidden", "hidden");
				responseDiv.removeAttribute("style");
			}, 4000);
		},
	});
};
