/*!
 * massif form — yform response parsing (pure)
 */

/**
 * Parse a yform XHR response document.
 *
 * yform re-renders at the same URL on submit:
 *  - validation errors → the response still contains the `<form>`, with a
 *    `.alert-danger` summary block listing `<li data-id="fieldName">`
 *  - success → the response contains `.alert.success` and no `<form>`
 *  - rerender → the `<form>` is present without a `.alert-danger` block
 *    (e.g. spam protection swallowed the submit)
 *
 * @param {string} htmlText - raw HTML returned by the POST
 * @param {string} formId - id of the submitted form
 * @returns {{ kind: "success"|"error"|"rerender", successEl?: Element|null, errors?: {fieldName: string, msg: string}[] }}
 */
export const parseYformResponse = (htmlText, formId) => {
	const doc = new DOMParser().parseFromString(htmlText, "text/html");
	const formEl = doc.getElementById(formId);

	if (!formEl) {
		const successEl =
			doc.querySelector(".rex-yform-wrap .alert.success") ||
			doc.querySelector(".alert.success") ||
			doc.querySelector(".rex-yform-wrap .alert");
		return { kind: "success", successEl };
	}

	const summary = formEl.querySelector(".alert-danger");
	if (!summary) {
		return { kind: "rerender" };
	}

	const errors = [...summary.querySelectorAll("li[data-id]")].map((li) => ({
		fieldName: li.dataset.id,
		msg: li.innerHTML,
	}));
	return { kind: "error", errors };
};
