import { gsap } from "gsap";
import { swup } from "@/js/swup/index.js";

let SwiperImpl = null;
const swipers = [];

const getSlideVideo = (slide) => slide?.querySelector("video") ?? null;

const handleSlideTransition = (swiper) => {
	const activeSlide = swiper.slides[swiper.activeIndex];
	const prevSlide = swiper.slides[swiper.previousIndex];

	// Pause & reset previous video
	const prevVideo = getSlideVideo(prevSlide);
	if (prevVideo) {
		prevVideo.pause();
		prevVideo.currentTime = 0;
		if (prevVideo._onEnded) {
			prevVideo.removeEventListener("ended", prevVideo._onEnded);
			prevVideo._onEnded = null;
		}
	}

	const video = getSlideVideo(activeSlide);

	if (video) {
		video.loop = false; // Ensure ended event can fire
		if (swiper.autoplay?.running) {
			swiper.autoplay.pause();
		}

		video.currentTime = 0;

		const onEnded = () => {
			video.removeEventListener("ended", onEnded);
			video._onEnded = null;

			if (swiper.slides.length > 1) {
				swiper.slideNext();
				// If next slide has no video, resume autoplay
				const nextVideo = getSlideVideo(swiper.slides[swiper.activeIndex]);
				if (!nextVideo && swiper.params.autoplay) {
					swiper.autoplay.resume();
				}
			} else {
				video.currentTime = 0;
				video.play().catch(console.error);
			}
		};

		if (video._onEnded) {
			video.removeEventListener("ended", video._onEnded);
		}
		video._onEnded = onEnded;
		video.addEventListener("ended", onEnded);

		video.play().catch(console.error);
	} else {
		// No video — resume autoplay if it was paused
		if (swiper.params.autoplay && swiper.autoplay?.paused) {
			swiper.autoplay.resume();
		}
	}
};

const CONFIG = {
	slidesPerView: "auto",
	spaceBetween: 10,
	speed: 500,
	grabCursor: true,
	loop: true,
	loopPreventsSliding: false,
	effect: "slide",
	autoplay: false,
	navigation: {
		nextSelector: ".btn-next",
		nextEl: null,
		enabled: true,
	},
	pagination: {
		selector: ".pagination",
		el: null,
		clickable: true,
		type: "fraction",
		renderFraction: (currentClass, totalClass) =>
			`<span class="${currentClass}"></span> / <span class="${totalClass}"></span>`,
	},
	keyboard: {
		enabled: true,
		onlyInViewport: true,
	},
	breakpoints: {
		768: { spaceBetween: 20 },
		1280: { spaceBetween: 40 },
	},
	on: {
		init: (swiper) => handleSlideTransition(swiper),
	},
};

const initSwipers = (containers) => {
	containers.forEach(($swiper) => {
		const container = $swiper.querySelector(".swiper-container");
		if (!container) return;

		const config = { ...CONFIG };
		const type = $swiper.dataset.swiperType;

		switch (type) {
			case "gallery":
				config.effect = "slide";
				config.autoplay = false;
				config.speed = 500;
				break;
			default:
				config.speed = 1000;
				config.effect = "fade";
				config.fadeEffect = { crossFade: true };
				config.autoplay = {
					delay: gsap.utils.random(6000, 9000, 500),
					disableOnInteraction: false,
				};
				break;
		}

		const slides = container.querySelectorAll(".swiper-slide");
		if (slides.length <= 1) {
			// Still handle a single slide with video
			const video = getSlideVideo(slides[0]);
			if (video) {
				video.play().catch(console.error);
			}
			swipers.push(null);
			return;
		}

		config.navigation.nextEl = $swiper.querySelector(
			CONFIG.navigation.nextSelector,
		);
		config.navigation.prevEl = $swiper.querySelector(
			CONFIG.navigation.prevSelector,
		);
		config.pagination.el = $swiper.querySelector(CONFIG.pagination.selector);

		const swiper = new SwiperImpl(container, config);
		swiper.on("slideChangeTransitionEnd", handleSlideTransition);
		swipers.push(swiper);
	});
};

const destroySwipers = () => {
	swipers.forEach((swiper) => {
		if (!swiper) return;
		// Clean up video listeners
		swiper.slides?.forEach((slide) => {
			const video = getSlideVideo(slide);
			if (video?._onEnded) {
				video.removeEventListener("ended", video._onEnded);
				video._onEnded = null;
			}
		});
		swiper.destroy(true, true);
	});
	swipers.length = 0;
};

const init = async () => {
	const containers = document.querySelectorAll(".swiper");
	if (!containers.length) return;
	let shouldInit = false;
	containers.forEach((container) => {
		if (container.querySelectorAll(".swiper-slide").length > 1) {
			shouldInit = true;
			return;
		}
	});
	if (!shouldInit) return;

	await import("@/js/Swiper.css");
	SwiperImpl = (await import("swiper")).default;
	const { Navigation, Pagination, Keyboard, Autoplay, EffectFade } =
		await import("swiper/modules");
	SwiperImpl.use([Navigation, Pagination, Keyboard, Autoplay, EffectFade]);
	initSwipers(containers);
};

init();

swup.hooks.on("content:replace", destroySwipers);
swup.hooks.on("page:view", init);
