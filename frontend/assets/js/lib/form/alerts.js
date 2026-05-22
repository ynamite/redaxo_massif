/*!
 * massif form — inline validation alerts
 */

/**
 * Create an alert element, optionally appended to a target.
 *
 * @param {{ msg: string, className?: string, appendTo?: Element|null }} params
 * @returns {HTMLDivElement}
 */
export const addAlert = ({ msg, className = "alert-danger", appendTo = null }) => {
	const alert = document.createElement("div");
	alert.className = className;
	alert.innerHTML = msg;
	appendTo?.appendChild(alert);
	return alert;
};

/**
 * Remove all alerts and error markers within a scope.
 *
 * @param {Element|null} scope
 */
export const clearAlerts = (scope) => {
	if (!scope) return;
	scope.classList.remove("has-error");
	for (const el of scope.querySelectorAll(".has-error")) {
		el.classList.remove("has-error");
	}
	for (const el of scope.querySelectorAll(".alert-danger")) {
		el.remove();
	}
};

/**
 * Render yform validation errors inline — one `.alert-danger` per field,
 * `.has-error` on its `.form-group`.
 *
 * @param {HTMLFormElement} form
 * @param {{ fieldName: string, msg: string }[]} errors
 * @returns {Element|null} the first errored `.form-group`, for focus/scroll
 */
export const renderFieldErrors = (form, errors) => {
	let firstGroup = null;
	for (const { fieldName, msg } of errors) {
		const group = form.querySelector(`#yform-${form.id}-${fieldName}`);
		if (!group) continue;
		group.classList.add("has-error");
		addAlert({ msg, appendTo: group });
		firstGroup ??= group;
	}
	return firstGroup;
};
