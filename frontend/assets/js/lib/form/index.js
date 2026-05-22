/*!
 * massif form
 * Submits REDAXO yform forms over fetch and renders validation errors inline.
 * @author Yves Torres, studio@massif.ch
 */
import { logger } from "@/js/lib/logger/index.js";
import { setupForm } from "./setup.js";

const defaults = {
	usePlaceholders: false,
	animateLabels: true,
	customSelect: false,
	callbacks: {
		initForm: [],
	},
};

/**
 * Initialise every yform form matching `selector`. Idempotent — forms already
 * marked `data-state="ready"` are skipped.
 *
 * @param {string} [selector=".rex-yform"]
 * @param {object} [options]
 * @param {boolean} [options.usePlaceholders] - stamp the label text as the field placeholder
 * @param {boolean} [options.animateLabels] - enable the CSS floating-label treatment
 * @param {boolean} [options.customSelect] - enhance `[data-custom-select]` fields
 * @param {{ initForm?: Function[] }} [options.callbacks] - callbacks run per form after setup
 */
const initForms = (selector = ".rex-yform", options = {}) => {
	logger.log("Form", "initForms", selector);

	const settings = {
		...defaults,
		...options,
		callbacks: { ...defaults.callbacks, ...options.callbacks },
	};

	for (const form of document.querySelectorAll(selector)) {
		if (form.dataset.state === "ready") continue;
		setupForm(form, settings);
		form.dataset.state = "ready";
	}
};

export default initForms;
