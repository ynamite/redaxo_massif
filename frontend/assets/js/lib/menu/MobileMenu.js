/*!
 * massif menu
 * @author: Yves Torres, studio@massif.ch
 */
import { gsap } from "gsap";
import { addEvent } from "@/js/lib/events/index.js";
import { logger } from "@/js/lib/logger/index.js";

class MobileMenu {
	hooks = {};
	constructor({
		dropdowns = true,
		menuItemsSelector = ".rex-navi1 > li",
		focusFirstLinkOnOpen = false,
	} = {}) {
		logger.log("Initializing mobile menu...");
		this.menu = document.getElementById("mobile-menu");
		if (!this.menu) return;
		this.focusFirstLinkOnOpen = focusFirstLinkOnOpen;
		this.menuButton = document.getElementById("mobile-menu-toggle");
		this.menuItems = this.menu.querySelectorAll(menuItemsSelector);
		this.overlay = document.getElementById("mobile-menu-overlay");
		this.hamburgerLines = this.menuButton.querySelectorAll(".icon span");
		this.isOpen = false;
		this.dropdowns = dropdowns && this.menu.querySelectorAll(".dropdown");
		this.activeDropdown = null;
		this.lockY = 0;
		this.menuButtonWidth = parseInt(
			this.menuButton.style.getPropertyValue("--width").replace("px", ""),
			10,
		);
		this.menuButtonBarHeight = parseInt(
			this.menuButton.style.getPropertyValue("--bar-height").replace("px", ""),
			10,
		);
		this.menuButtonBarGap = parseInt(
			this.menuButton.style.getPropertyValue("--bar-gap").replace("px", ""),
			10,
		);
		this.matchMedia = gsap.matchMedia();

		this.init();
	}

	init() {
		// Menu toggle button
		addEvent(this.menuButton, "click.menuButtonToggle", () => {
			this.toggle();
		});
		// Overlay click to close
		addEvent(this.overlay, "click.menuOverlay", () => {
			this.close();
		});

		// Escape key to close
		addEvent(document, "keydown.menuClose", async (e) => {
			if (e.key === "Escape" && this.isOpen) {
				this.close();
				this.menuButton.blur(); // remove focus from menu button
			}
		});

		// Focus management
		addEvent(this.menu, "keydown.menuFocus", (e) => {
			if (e.key === "Tab") {
				this.trapFocus(e);
			}
		});

		// Remove 'closing' class after animation ends
		addEvent(this.hamburgerLines[0], "animationend.menuAnimationEnd", () => {
			if (!this.isOpen) {
				this.menuButton.classList.remove("closing");
			}
		});

		if (this.dropdowns.length) this.setupDropdowns();

		this.setMenuFocusability(false);

		this.handleMenuLinks();
	}

	handleMenuLinks() {
		const swupActive = typeof swup !== "undefined";

		const followLinkWithTransition = (href) => {
			if (swupActive) {
				swup.navigate(href);
			} else if (document.startViewTransition) {
				document.startViewTransition(() => {
					window.location.href = href;
				});
			} else {
				window.location.href = href;
			}
		};

		addEvent(this.menu, "click.menuLink", async (e) => {
			const target = e.target.closest(".menu-link");
			if (target) {
				const href = target.getAttribute("href");
				e.preventDefault();
				await this.close();
				followLinkWithTransition(href);
			}
		});

		const setup = () => {
			logger.log("Setting up menu links...");
			// Auto-close on same-domain link clicks
			const menuLinks = this.menu.querySelectorAll("a[href]");

			menuLinks.forEach((link) => {
				const href = link.getAttribute("href");
				if (
					href &&
					(href.startsWith("/") ||
						href.startsWith("#") ||
						href.startsWith(window.location.origin))
				) {
					swupActive && link.setAttribute("data-no-swup", "true");
					link.classList.add("menu-link");
				}
			});
		};

		setup();
	}

	setupDropdowns() {
		this.dropdowns.forEach((dropdown, idx) => {
			const parentListItem = dropdown.closest("li");
			const toggle = dropdown.previousElementSibling;

			gsap.set(dropdown, {
				clearProps: true,
			});

			if (parentListItem.classList.contains("rex-active")) {
				this.openDropdown(dropdown);
			} else {
				gsap.set(dropdown, {
					height: 0,
					autoAlpha: 0,
				});
			}

			addEvent(toggle, `click.toggleDropdown-${idx}`, () => {
				const isExpanded = toggle.getAttribute("aria-expanded") === "true";
				if (!isExpanded) {
					// Open dropdown
					this.openDropdown(dropdown);
				} else {
					// Close dropdown
					this.closeDropdown(dropdown);
				}
			});
		});
	}

	toggle() {
		this.isOpen ? this.close() : this.open();
	}

	async open() {
		if (this.isOpen) return;

		this.isOpen = true;

		// Update ARIA attributes
		this.menuButton.setAttribute("aria-expanded", "true");
		this.menuButton.setAttribute(
			"aria-label",
			this.menuButton.dataset.labelClose,
		);
		this.menu.removeAttribute("inert");

		// Make menu elements focusable
		this.setMenuFocusability(true);

		// add class to html element
		document.documentElement.classList.add("menu-open");

		this.lockScroll();

		// Animate hamburger
		gsap.to(this.hamburgerLines[0], {
			rotation: 45,
			y: this.menuButtonBarHeight + this.menuButtonBarGap,
			duration: 0.3,
			ease: "back.out(1.7)",
		});

		gsap.to(this.hamburgerLines[1], {
			opacity: 0,
			duration: 0.15,
			ease: "power2.out",
		});

		gsap.to(this.hamburgerLines[2], {
			rotation: -45,
			y: (this.menuButtonBarHeight + this.menuButtonBarGap) * -1,
			duration: 0.3,
			ease: "back.out(1.7)",
		});

		this.menuButton.classList.add("open");

		const timeline = gsap.timeline({
			paused: true,
			onStart: () => {
				this.overlay.style.pointerEvents = "auto";
			},
		});

		// Show overlay
		timeline.to(this.overlay, {
			opacity: 1,
			duration: 0.3,
			ease: "power2.out",
			overwrite: "auto",
		});

		// Slide in menu with spring animation
		timeline.to(this.menu, {
			//"--tw-translate-y": 0,
			height: "auto",
			opacity: 1,
			pointerEvents: "auto",
			duration: 0.3,
			ease: "back.out(0.4)",
		});

		// slide in menu items with spring animation
		const nestedTimeline = gsap.timeline();
		this.menuItems.forEach((item, index) => {
			nestedTimeline.from(
				item,
				{
					y: 30,
					opacity: 0,
					duration: 0.8,
					ease: "back.out(1.2)",
				},
				index * 0.05,
			);
		});
		timeline.add(nestedTimeline, "-=0.4"); // start slightly before previous animation ends

		await timeline.play();
		timeline.kill();

		if (this.hooks?.onMenuOpened) {
			await this.callback(this.hooks.onMenuOpened);
		}

		// Focus management
		if (this.focusFirstLinkOnOpen) {
			setTimeout(() => {
				// Focus the first menu link instead of a close button
				const firstLink = this.menu.querySelector(
					".mobile-menu-link, .dropdown-toggle",
				);
				if (firstLink) firstLink.focus();
			}, 100);
		}
	}

	async close() {
		if (!this.isOpen) return;

		this.isOpen = false;

		if (this.activeDropdown) {
			await this.closeDropdown(this.activeDropdown);
		}

		// Update ARIA attributes
		this.menuButton.setAttribute("aria-expanded", "false");
		this.menuButton.setAttribute(
			"aria-label",
			this.menuButton.dataset.labelOpen,
		);
		this.menu.setAttribute("inert", "true");

		// Remove focusable elements from tab order
		this.setMenuFocusability(false);

		this.unlockScroll();

		// Animate X back to hamburger
		this.menuButton.classList.remove("open");
		gsap.to(this.hamburgerLines[0], {
			rotation: 0,
			y: 0,
			duration: 0.3,
			ease: "back.out(1.7)",
		});

		gsap.to(this.hamburgerLines[1], {
			opacity: 1,
			duration: 0.15,
			delay: 0.1,
			ease: "power2.out",
		});

		gsap.to(this.hamburgerLines[2], {
			rotation: 0,
			y: 0,
			duration: 0.3,
			ease: "back.out(1.7)",
		});

		// Slide out menu items and menu
		const menuItemsReversed = Array.from(this.menuItems).reverse();
		const timeline = gsap.timeline({ paused: true });
		const nestedTimeline = gsap.timeline();
		menuItemsReversed.forEach(async (item, index) => {
			nestedTimeline.to(
				item,
				{
					y: 15,
					opacity: 0,
					duration: 0.4,
					ease: "back.out(1.2)",
					overwrite: true,
				},
				index * 0.05,
			);
		});
		timeline.add(nestedTimeline); // start slightly before previous animation ends
		timeline.to(
			this.menu,
			{
				//"--tw-translate-x": "100%",
				height: 0,
				opacity: 0,
				pointerEvents: "none",
				duration: 0.3,
				ease: "back.in(0.4)",
				overwrite: "auto",
			},
			"-=0.45",
		); // start slightly before previous animations end

		// Hide overlay
		timeline.to(
			this.overlay,
			{
				opacity: 0,
				duration: 0.2,
				ease: "power2.out",
				overwrite: "auto",
			},
			"-=0.2",
		);
		await timeline.play();
		timeline.kill();
		this.overlay.style.pointerEvents = "none";

		gsap.set(this.menuItems, {
			clearProps: true,
		});

		// remove class from html element
		document.documentElement.classList.remove("menu-open");

		if (this.hooks?.onMenuClosed) {
			await this.callback(this.hooks.onMenuClosed);
		}
	}

	async openDropdown(dropdown) {
		if (!dropdown) return;
		const toggle = dropdown.previousElementSibling;
		const arrow = toggle.firstElementChild;
		const isExpanded = toggle.getAttribute("aria-expanded") === "true";
		if (isExpanded) return;
		if (this.activeDropdown && this.activeDropdown !== dropdown) {
			await this.closeDropdown(this.activeDropdown);
		}
		toggle.setAttribute("aria-expanded", true);
		dropdown.classList.add("expanded");
		arrow.classList.add("-scale-y-100");
		this.activeDropdown = dropdown;
		await gsap.to(dropdown, {
			height: "auto",
			autoAlpha: 1,
			duration: 0.3,
			ease: "back.out(1.2)",
		});
		if (this.hooks?.onDropdownOpened) {
			await this.callback(this.hooks.onDropdownOpened);
		}
	}

	async closeDropdown(dropdown) {
		if (!dropdown) return;
		const toggle = dropdown.previousElementSibling;
		const arrow = toggle.firstElementChild;
		const isExpanded = toggle.getAttribute("aria-expanded") === "true";
		if (!isExpanded) return;

		toggle.setAttribute("aria-expanded", false);
		dropdown.classList.remove("expanded");
		arrow.classList.remove("-scale-y-100");
		this.activeDropdown = null;

		await gsap.to(dropdown, {
			height: 0,
			autoAlpha: 0,
			duration: 0.3,
			ease: "back.out(1.2)",
		});

		if (this.hooks?.onDropdownClosed) {
			await this.callback(this.hooks.onDropdownClosed);
		}
	}

	setMenuFocusability(focusable) {
		const focusableElements = this.menu.querySelectorAll(
			'a, button, input, select, textarea, [tabindex]:not([tabindex="-1"])',
		);

		focusableElements.forEach((element) => {
			if (focusable) {
				// Restore original tabindex or remove tabindex attribute
				const originalTabIndex = element.getAttribute("data-original-tabindex");
				if (originalTabIndex !== null) {
					if (originalTabIndex === "remove") {
						element.removeAttribute("tabindex");
					} else {
						element.setAttribute("tabindex", originalTabIndex);
					}
					element.removeAttribute("data-original-tabindex");
				}
			} else {
				// Store original tabindex and set to -1
				const currentTabIndex = element.getAttribute("tabindex");
				if (currentTabIndex !== null) {
					element.setAttribute("data-original-tabindex", currentTabIndex);
				} else {
					element.setAttribute("data-original-tabindex", "remove");
				}
				element.setAttribute("tabindex", "-1");
			}
		});
	}

	trapFocus(e) {
		const focusableElements = this.menu.querySelectorAll(
			'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
		);
		const firstElement = focusableElements[0];
		const lastElement = focusableElements[focusableElements.length - 1];

		if (e.shiftKey && document.activeElement === firstElement) {
			e.preventDefault();
			lastElement.focus();
		} else if (!e.shiftKey && document.activeElement === lastElement) {
			e.preventDefault();
			firstElement.focus();
		}
	}

	lockScroll() {
		this.lockY = window.scrollY || document.documentElement.scrollTop;
		// Freeze the page at its current position (iOS-safe)
		// document.body.style.position = 'fixed'
		// document.body.style.top = `-${this.lockY}px`
		// document.body.style.left = '0'
		// document.body.style.right = '0'
		// document.body.style.width = '100%'
		document.body.style.overflowY = "clip";
		this.matchMedia.add("(min-width: 64rem)", () => {
			document.body.style.overflowY = "";
		});
	}

	unlockScroll() {
		this.matchMedia.revert();
		document.documentElement.classList.add("disable-smooth-scroll");
		// document.body.style.position = ''
		// document.body.style.top = ''
		// document.body.style.left = ''
		// document.body.style.right = ''
		// document.body.style.width = ''
		document.body.style.overflowY = "";
		// window.scrollTo(0, this.lockY) // restore scroll position precisely
		setTimeout(() => {
			document.documentElement.classList.remove("disable-smooth-scroll");
		});
	}

	async callback(fn) {
		if (typeof fn === "function") {
			return await fn();
		}
		return null;
	}
}

export default MobileMenu;
