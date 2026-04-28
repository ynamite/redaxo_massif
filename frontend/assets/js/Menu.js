const DesktopMenu = (await import("@/js/lib/menu/DesktopMenu.js")).default;
new DesktopMenu();

const has = document.querySelector("#nav-mobile");
if (has) {
	const options = {
		dropdowns: true,
	};
	const MobileMenu = (await import("@/js/lib/menu/MobileMenu.js")).default;
	new MobileMenu(options);
}
