import Alpine from "alpinejs";

class Logger {
	constructor(options) {
		var opts = options || {};
		this.options = {};
		this.options.debug =
			typeof opts["debug"] !== "undefined" ? opts["debug"] : true;
		// if (logger !== true) {
		//     this.options.debug = false;
		// }
	}
	setOption = function (option, value) {
		if (option && value !== "") {
			this.options[option] = value;
		}
		return true;
	};

	log = function (...args) {
		this.output("log", ...args);
	};

	warn = function (...args) {
		this.output("warn", ...args);
	};

	error = function (...args) {
		this.output("error", ...args);
	};

	time = function (...args) {
		this.output("time", ...args);
	};

	timeEnd = function (...args) {
		this.output("timeEnd", ...args);
	};

	clear = function () {
		this.output("clear", ...args);
	};

	output = function (type = "log", ...args) {
		const debugMode = Alpine.store("debugMode") ?? false;

		if (
			debugMode &&
			this.options.debug &&
			typeof console[type] === "function"
		) {
			console[type](...args);
		}
	};
}
export const logger = new Logger({ debug: true });
