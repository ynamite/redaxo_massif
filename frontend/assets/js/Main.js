import "@/js/lib/viewTransitions/index.js";
import collapse from "@alpinejs/collapse";
import focus from "@alpinejs/focus";
import morph from "@alpinejs/morph";
import persist from "@alpinejs/persist";
import Alpine from "alpinejs";
import { logger } from "@/js/lib/logger/index.js";
import { detectTouchDevice, getScrollBarWidth } from "@/js/utils/Device.js";
import { decryptEmailaddresses } from "@/js/utils/Dom.js";

Alpine.plugin([collapse, focus, morph, persist]);

Alpine.store("debugMode", process.env.NODE_ENV === "development");

const htmlElement = document.documentElement;

const isTouchDevice = detectTouchDevice();
const isFirefox = navigator.userAgent.toLowerCase().indexOf("firefox") > -1;
const isSsafari = navigator.userAgent.toLowerCase().indexOf("safari") > -1;
const scrollBarWidth = getScrollBarWidth();

Alpine.store("browser", {
	isTouchDevice,
	isFirefox,
	isSsafari,
	scrollBarWidth,
});

htmlElement.classList.add(isTouchDevice ? "touch-device" : "not-touch-device");
htmlElement.classList.add(isFirefox ? "firefox" : "not-firefox");
htmlElement.classList.add(isSsafari ? "safari" : "not-safari");

document.documentElement.style.setProperty("--scrollbarwidth", `${scrollBarWidth}px`);

export const init = () => {
	logger.log("commonInits");

	decryptEmailaddresses();

	window.Alpine = Alpine;
	Alpine.start();

	// Lazy-load the form library only on pages that have a yform form.
	if (document.querySelector(".rex-yform")) {
		import("@/js/lib/form/index.js").then(({ default: initForms }) => {
			initForms(".rex-yform", { animateLabels: true });
		});
	}
};

init();
