<?php
$rootCategories = rex_category::getRootCategories();
$currentArticle = rex_article::getCurrent();
$currentId = $currentArticle?->getId();
$currentAncestorIds = $currentArticle ? $currentArticle->getPathAsArray() : [];

// "active branch" = current article IS this category, OR this category is an ancestor of current article
$isActiveBranch = static fn(rex_category $cat): bool =>
$cat->getId() === $currentId || in_array($cat->getId(), $currentAncestorIds, true);

// "exact page" = the current article IS this category's start article (used for child highlighting)
$isExactPage = static fn(rex_category $cat): bool => $cat->getId() === $currentId;
?>
<!-- Desktop -->
<nav
  aria-label="Desktop Hauptmenü" class="hidden md:flex items-center gap-3">
  <!-- DESKTOP nav (md+) -->
  <ul class="flex items-center gap-x-7 font-medium text-sm">
    <?php foreach ($rootCategories as $top):
      $children = $top->getChildren();
      $active = $isActiveBranch($top);
    ?>
      <li class="relative py-2"
        <?= $children ? 'x-data="{ openSub: false }" @mouseenter="openSub = true" @mouseleave="openSub = false" @focusin="openSub = true" @focusout.away="openSub = false"' : '' ?>>
        <a href="<?= $top->getUrl() ?>"
          class="inline-flex items-center uppercase font-bold tracking-wider gap-1 py-2 transition-color duration-300 <?= $active ? 'text-accent' : 'text-muted hover:text-accent' ?> focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent rounded"
          <?= $active ? 'aria-current="page"' : '' ?>
          <?= $children ? ':aria-expanded="openSub"' : '' ?>>
          <span><?= $top->getName() ?></span>
          <?php if ($children): ?>
            <span class="size-4 motion-safe:transition-transform iconify lucide--chevron-down" :class="openSub && 'rotate-180'" aria-hidden="true"></span>
          <?php endif ?>
        </a>
        <?php if ($children): ?>
          <ul x-ref="subnav" x-show="openSub" x-cloak
            x-transition:enter="motion-safe:transition motion-safe:ease-out motion-safe:duration-150"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="motion-safe:transition motion-safe:ease-in motion-safe:duration-100"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-1"
            class="top-full left-0 absolute bg-body/85 shadow-xl backdrop-blur p-2 border border-white/10 rounded-lg min-w-44">
            <?php foreach ($children as $child):
              $childActive = $isExactPage($child);
            ?>
              <li>
                <a href="<?= $child->getUrl() ?>"
                  class="block rounded px-3 py-2 text-sm transition-colors <?= $childActive ? 'text-accent' : 'text-muted hover:bg-white/5 hover:text-fg' ?>"
                  <?= $childActive ? 'aria-current="page"' : '' ?>>
                  <?= $child->getName() ?>
                </a>
              </li>
            <?php endforeach ?>
          </ul>
        <?php endif ?>
      </li>
    <?php endforeach ?>
  </ul>

</nav>

<!-- Mobile -->
<nav x-cloak x-data="{ mobileOpen: false }"
  @resize.window="window.innerWidth >= 768 && (mobileOpen = false)"
  x-trap.inert.noscroll.noautofocus="mobileOpen"
  @keyup.escape.stop.prevent="mobileOpen = false"
  aria-label="Mobile Hauptmenü" class="md:hidden flex items-center gap-3">

  <!-- Mobile trigger (visible <md) -->
  <button type="button"
    @click.prevent.stop="mobileOpen = !mobileOpen"
    :aria-expanded="mobileOpen"
    :aria-label="mobileOpen ? 'Menü schließen' : 'Menü öffnen'"
    class="right-safe bottom-safe z-30 fixed flex justify-center items-center bg-body/85 backdrop-blur border border-white/10 rounded-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent size-10 text-fg">
    <span
      class="sr-only"
      x-text="mobileOpen ? 'Menü schließen' : 'Menü öffnen'"></span>
    <span :class="mobileOpen ? 'lucide--x' : 'lucide--menu'" class="size-6 iconify" aria-hidden="true"></span>
  </button>

  <!-- MOBILE drawer (<md) -->
  <div
    x-show="mobileOpen"
    role="dialog" aria-modal="true" aria-label="Mobile Hauptmenü"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-out duration-300"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="translate-x-full"
    class="z-20 fixed after:absolute inset-0 after:inset-0 flex flex-col justify-end bg-body/80 after:backdrop-blur px-8 pt-24 pb-12">
    <ul
      @click.outside.stop="mobileOpen = false"
      class="z-10 relative flex flex-col divide-y divide-fg/10 overflow-y-auto">
      <?php foreach ($rootCategories as $top):
        $children = $top->getChildren();
        $active = $isActiveBranch($top);
      ?>
        <li class="py-3"
          <?= $children ? 'x-data="{ subOpen: ' . ($isActiveBranch($top) ? 'true' : 'false') . ' }"' : '' ?>>
          <div class="flex justify-between items-center">
            <a href="<?= $top->getUrl() ?>"
              class="text-2xl font-bold transition-colors <?= $active ? 'text-fg' : 'text-muted hover:text-fg' ?>"
              <?= $active ? 'aria-current="page"' : '' ?>>
              <?= $top->getName() ?>
            </a>
            <?php if ($children): ?>
              <button type="button" @click="subOpen = !subOpen" :aria-expanded="subOpen"
                :aria-label="subOpen ? 'Untermenü schließen' : 'Untermenü öffnen'"
                class="inline-flex justify-center items-center ml-auto rounded focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent size-8 text-accent">
                <span class="size-5 motion-safe:transition-transform iconify lucide--chevron-down" :class="subOpen && 'rotate-180'" aria-hidden="true"></span>
              </button>
            <?php endif ?>
          </div>
          <?php if ($children): ?>
            <ul x-show="subOpen" x-collapse class="flex flex-col mt-2 ml-2">
              <?php foreach ($children as $child):
                $childActive = $isExactPage($child);
              ?>
                <li>
                  <a href="<?= $child->getUrl() ?>"
                    class="block py-2 text-base transition-colors <?= $childActive ? 'text-accent' : 'text-muted hover:text-fg' ?>"
                    <?= $childActive ? 'aria-current="page"' : '' ?>>
                    <?= $child->getName() ?>
                  </a>
                </li>
              <?php endforeach ?>
            </ul>
          <?php endif ?>
        </li>
      <?php endforeach ?>
    </ul>
  </div>

</nav>