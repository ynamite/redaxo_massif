<?php

use Ynamite\Massif\Nav;

$nav = Nav::factory();
// $nav->addFilter('id', 1, '!=', 1);
// $nav->addCallback(callback: function (array $params) {
//   ['category' => $category, 'a' => &$a, 'name' => &$name, 'depth' => $depth] = $params;
//   return true;
// });

$isPopup = rex_request('popup', 'bool', false);
$params = [];
if ($_GET) {
  if (isset($_GET['popup'])) {
    unset($_GET['popup']);
  }
  foreach ($_GET as $key => $val) {
    $params[$key] = $val;
  }
}

if (!$isPopup) { ?>

  <!-- Desktop menu -->
  <div class="hidden lg:block">
    <nav id="nav-desktop" class="main-nav main-nav-desktop" aria-label="Hauptmenü">
      <?= $nav->get(name: 'desktop') ?>
    </nav>
  </div>

  <!-- Mobile menu -->
  <div class="lg:hidden">
    <button
      type="button"
      id="mobile-menu-toggle"
      aria-label="Menü öffnen"
      data-label-open="Menü öffnen"
      data-label-close="Menü schliessen"
      aria-controls="mobile-menu"
      aria-expanded="false"
      style="--width: 36px; --bar-height: 2px; --bar-gap: 8px;">
      <span class="sr-only">Menü öffnen</span>
      <div class="icon hamburger">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </button>

    <div id="mobile-menu-overlay" aria-hidden="true"></div>

    <div
      id="mobile-menu"
      role="dialog"
      aria-modal="true"
      aria-labelledby="mobile-menu-title"
      inert>
      <span id="mobile-menu-title" class="sr-only">Menü</span>

      <nav id="nav-mobile" class="main-nav main-nav-mobile">
        <?= $nav->get(name: 'mobile') ?>
      </nav>
    </div>
  </div>

<?php } ?>