<?php
$title = $this->getVar('title', '');
$src = $this->getVar('src');
$params = $this->getVar('params', '');
$controls = $this->getVar('controls', []);
$extension = pathinfo($src, PATHINFO_EXTENSION);
if (rex::isBackend()) {
  echo '<video playsinline autoplay muted loop class="thumbnail"><source src="' . $src . '" type="video/' . $extension . '"></video>';
} else {
?>
  <media-player title="<?= $title ?>" <?= $params ?> src="<?= $src ?>" class="ring-media-focus data-focus:ring-4 overflow-hidden text-white media-player">
    <media-provider></media-provider>
    <?php if (count($controls)) : ?>
      <media-controls class="z-10 absolute inset-0 flex flex-col bg-linear-to-t from-black/10 to-transparent opacity-0 media-controls:opacity-100 w-full h-full transition-opacity">
        <div class="flex-1 pointer-events-none"></div>
        <?php if (in_array('time-slider', $controls)) : ?>
          <media-controls-group class="flex items-center px-2 w-full">
            <!-- Time Slider -->
            <media-time-slider class="group inline-flex relative items-center mx-[7.5px] outline-none w-full h-10 touch-none cursor-pointer select-none">
              <media-slider-chapters class="relative flex items-center rounded-[1px] w-full h-full">
                <template>
                  <!-- Slider Chapter -->
                  <div class="relative flex items-center mr-0.5 last-child:mr-0 rounded-[1px] w-full h-full" style="contain: layout style">
                    <!-- Slider Chapter Track -->
                    <div class="z-0 relative bg-white/30 rounded-sm ring-media-focus group-data-focus:ring-[3px] w-full h-[5px]">
                      <div class="bg-media-brand absolute h-full w-(--chapter-fill) rounded-sm will-change-[width]"></div>
                      <div class="absolute z-10 h-full w-(--chapter-progress) rounded-sm bg-white/50 will-change-[width]"></div>
                    </div>
                  </div>
                </template>
              </media-slider-chapters>
              <!-- Slider Thumb -->
              <div class="absolute left-(--slider-fill) top-1/2 z-20 h-[15px] w-[15px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-[#cacaca] bg-white opacity-0 ring-white/40 transition-opacity will-change-[left] group-data-active:opacity-100 group-data-dragging:ring-4"></div>
              <!-- Slider Preview -->
              <media-slider-preview class="flex flex-col items-center opacity-0 data-visible:opacity-100 transition-opacity duration-200 pointer-events-none">
                <media-slider-thumbnail class="block h-(--thumbnail-height) max-h-[160px] min-h-[80px] w-(--thumbnail-width) min-w-[120px] max-w-[180px] overflow-hidden border border-white bg-black" src="https://image.mux.com/VZtzUzGRv02OhRnZCxcNg49OilvolTqdnFLEqBsTwaxU/storyboard.vtt"></media-slider-thumbnail>
                <div class="mt-1 text-sm" data-part="chapter-title"></div>
                <media-slider-value class="text-[13px]"></media-slider-value>
              </media-slider-preview>
            </media-time-slider>
          </media-controls-group>
        <?php endif; ?>

        <media-controls-group class="flex items-center -mt-0.5 px-2 pb-2 w-full">
          <?php if (in_array('play-button', $controls)) : ?>
            <!-- Play Button -->
            <media-tooltip>
              <media-tooltip-trigger>
                <media-play-button class="inline-flex relative justify-center items-center hover:bg-white/20 rounded-md outline-none ring-media-focus data-focus:ring-4 ring-inset w-10 h-10 cursor-pointer">
                  <media-icon class="hidden media-paused:block w-8 h-8" type="play"></media-icon>
                  <media-icon class="media-paused:hidden w-8 h-8" type="pause"></media-icon>
                </media-play-button>
              </media-tooltip-trigger>
              <media-tooltip-content class="slide-out-to-bottom-2 data-[visible]:slide-in-from-bottom-4 z-10 bg-black/90 px-2 py-0.5 rounded-sm font-medium text-white text-sm animate-out data-[visible]:animate-in fade-out data-[visible]:fade-in" placement="top start" offset="30">
                <span class="hidden media-paused:block">Play</span>
                <span class="media-paused:hidden">Pause</span>
              </media-tooltip-content>
            </media-tooltip>
          <?php endif; ?>

          <?php if (in_array('mute-button', $controls)) : ?>
            <!-- Mute Button -->
            <media-tooltip>
              <media-tooltip-trigger>
                <media-mute-button class="group inline-flex relative justify-center items-center hover:bg-white/20 -mr-1.5 rounded-md outline-none ring-media-focus data-focus:ring-4 ring-inset w-10 h-10 cursor-pointer">
                  <media-icon class="hidden group-data-[state='muted']:block w-8 h-8" type="mute"></media-icon>
                  <media-icon class="hidden group-data-[state='low']:block w-8 h-8" type="volume-low"></media-icon>
                  <media-icon class="hidden group-data-[state='high']:block w-8 h-8" type="volume-high"></media-icon>
                </media-mute-button>
              </media-tooltip-trigger>
              <media-tooltip-content class="slide-out-to-bottom-2 data-[visible]:slide-in-from-bottom-4 z-10 bg-black/90 px-2 py-0.5 rounded-sm font-medium text-white text-sm animate-out data-[visible]:animate-in fade-out data-[visible]:fade-in" placement="top" offset="30">
                <span class="media-muted:hidden">Mute</span>
                <span class="hidden media-muted:block">Unmute</span>
              </media-tooltip-content>
            </media-tooltip>
          <?php endif; ?>

          <?php if (in_array('volume-slider', $controls)) : ?>
            <!-- Volume Slider -->
            <media-volume-slider class="group inline-flex relative items-center mx-[7.5px] outline-none w-full max-w-[80px] h-10 touch-none cursor-pointer select-none">
              <!-- Slider Track -->
              <div class="z-0 relative bg-white/30 rounded-sm ring-media-focus group-data-focus:ring-[3px] w-full h-[5px]">
                <div class="bg-media-brand absolute h-full w-(--slider-fill) rounded-sm will-change-[width]"></div>
              </div>
              <!-- Slider Thumb -->
              <div class="absolute left-(--slider-fill) top-1/2 z-20 h-[15px] w-[15px] -translate-x-1/2 -translate-y-1/2 rounded-full border border-[#cacaca] bg-white opacity-0 ring-white/40 transition-opacity will-change-[left] group-data-active:opacity-100 group-data-dragging:ring-4"></div>
              <media-slider-preview class="opacity-0 data-visible:opacity-100 transition-opacity duration-200 pointer-events-none" no-clamp offset="30">
                <media-slider-value class="bg-black px-2 py-px rounded-sm font-medium text-[13px]"></media-slider-value>
              </media-slider-preview>
            </media-volume-slider>
          <?php endif; ?>

          <?php if (in_array('time-group', $controls)) : ?>
            <!-- Time Group -->
            <div class="flex items-center ml-1.5 font-medium text-sm">
              <media-time type="current"></media-time>
              <div class="mx-1 text-white/80">/</div>
              <media-time type="duration"></media-time>
            </div>

            <span class="inline-block flex-1 px-2 overflow-hidden font-medium text-white/70 text-sm text-ellipsis whitespace-nowrap">
              <span class="mr-1">|</span>
              <media-chapter-title></media-chapter-title>
            </span>
          <?php endif; ?>

          <?php if (in_array('caption-button', $controls)) : ?>
            <!-- Caption Button -->
            <media-tooltip>
              <media-tooltip-trigger>
                <media-caption-button class="group inline-flex relative justify-center items-center hover:bg-white/20 mr-0.5 rounded-md outline-none ring-media-focus data-focus:ring-4 ring-inset w-10 h-10 cursor-pointer">
                  <media-icon class="hidden media-captions:block w-8 h-8" type="closed-captions-on"></media-icon>
                  <media-icon class="media-captions:hidden w-8 h-8" type="closed-captions"></media-icon>
                </media-caption-button>
              </media-tooltip-trigger>
              <media-tooltip-content class="slide-out-to-bottom-2 data-[visible]:slide-in-from-bottom-4 z-10 bg-black/90 px-2 py-0.5 rounded-sm font-medium text-white text-sm animate-out data-[visible]:animate-in fade-out data-[visible]:fade-in" placement="top" offset="30">
                <span class="hidden media-captions:block">Closed-Captions Off</span>
                <span class="media-captions:hidden">Closed-Captions On</span>
              </media-tooltip-content>
            </media-tooltip>
          <?php endif; ?>

          <?php if (in_array('settings-menu', $controls)) : ?>
            <!-- Settings Menu -->
            <media-menu class="group">
              <!-- Settings Menu Button -->
              <media-tooltip>
                <media-tooltip-trigger>
                  <media-menu-button class="aria-hidden:hidden inline-flex relative justify-center items-center hover:bg-white/20 mr-0.5 rounded-md outline-none ring-media-focus data-focus:ring-4 ring-inset w-10 h-10 cursor-pointer">
                    <media-icon class="w-8 h-8 group-data-open:rotate-90 transition-transform duration-200 ease-out transform" type="settings"></media-icon>
                  </media-menu-button>
                </media-tooltip-trigger>
                <media-tooltip-content class="group-data-open:hidden slide-out-to-bottom-2 data-[visible]:slide-in-from-bottom-4 z-10 bg-black/90 px-2 py-0.5 rounded-sm font-medium text-white text-sm animate-out data-[visible]:animate-in fade-out data-[visible]:fade-in" placement="top" offset="30">
                  Settings
                </media-tooltip-content>
              </media-tooltip>
              <!-- Settings Menu Items -->
              <media-menu-items class="animate-out fade-out slide-out-to-bottom-2 data-[open]:animate-in data-[open]:fade-in data-[open]:slide-in-from-bottom-4 flex h-(--menu-height) max-h-[400px] min-w-[260px] flex-col overflow-y-auto overscroll-y-contain rounded-md border border-white/10 bg-black/95 p-2.5 font-sans text-[15px] font-medium outline-none backdrop-blur-sm transition-[height] duration-300 will-change-[height] data-resizing:overflow-hidden" offset="30" placement="top end">
                <!-- Caption Submenu -->
                <media-menu>
                  <!-- Caption Submenu Button -->
                  <media-menu-button class="aria-disabled:hidden aria-hidden:hidden data-open:-top-2.5 left-0 z-10 data-open:sticky flex justify-start items-center bg-black/60 data-hocus:bg-white/10 p-2.5 rounded-sm outline-none ring-media-focus data-focus:ring-[3px] ring-inset w-full cursor-pointer select-none parent">
                    <!-- Close Icon -->
                    <media-icon class="hidden parent-data-[open]:block mr-1.5 -ml-0.5 w-[18px] h-[18px]" type="chevron-left"></media-icon>
                    <!-- Icon -->
                    <media-icon class="parent-data-[open]:hidden mr-1.5 w-5 h-5" type="closed-captions"></media-icon>
                    <!-- Label -->
                    <span>Captions</span>
                    <!-- Hint -->
                    <span class="ml-auto text-white/50 text-sm" data-part="hint"></span>
                    <!-- Open Icon -->
                    <media-icon class="parent-data-[open]:hidden ml-0.5 w-[18px] h-[18px] text-white/50 text-sm" type="chevron-right"></media-icon>
                  </media-menu-button>
                  <!-- Caption Submenu Items -->
                  <media-menu-items class="hidden data-open:inline-block flex-col justify-center items-start data-keyboard:mt-[3px] outline-none w-full">
                    <media-captions-radio-group class="flex flex-col w-full">
                      <template>
                        <media-radio class="group relative flex justify-start items-center data-hocus:bg-white/10 p-2.5 rounded-sm outline-none ring-media-focus data-focus:ring-[3px] w-full cursor-pointer select-none">
                          <media-icon class="group-data-checked:hidden w-4 h-4" type="radio-button"></media-icon>
                          <media-icon class="hidden group-data-checked:block w-4 h-4 text-media-brand" type="radio-button-selected"></media-icon>
                          <span class="ml-2" data-part="label"></span>
                        </media-radio>
                      </template>
                    </media-captions-radio-group>
                  </media-menu-items>
                </media-menu>
              </media-menu-items>
            </media-menu>
          <?php endif; ?>

          <?php if (in_array('pip-button', $controls)) : ?>
            <!-- PIP Button -->
            <media-tooltip>
              <media-tooltip-trigger>
                <media-pip-button class="group inline-flex relative justify-center items-center hover:bg-white/20 mr-0.5 rounded-md outline-none ring-media-focus data-focus:ring-4 ring-inset w-10 h-10 cursor-pointer">
                  <media-icon class="media-pip:hidden w-8 h-8" type="picture-in-picture"></media-icon>
                  <media-icon class="hidden media-pip:block w-8 h-8" type="picture-in-picture-exit"></media-icon>
                </media-pip-button>
              </media-tooltip-trigger>
              <media-tooltip-content class="slide-out-to-bottom-2 data-[visible]:slide-in-from-bottom-4 z-10 bg-black/90 px-2 py-0.5 rounded-sm font-medium text-white text-sm animate-out data-[visible]:animate-in fade-out data-[visible]:fade-in" placement="top" offset="30">
                <span class="media-pip:hidden">Enter PIP</span>
                <span class="hidden media-pip:block">Exit PIP</span>
              </media-tooltip-content>
            </media-tooltip>
          <?php endif; ?>

          <?php if (in_array('fullscreen-button', $controls)) : ?>
            <!-- Fullscreen Button -->
            <media-tooltip>
              <media-tooltip-trigger>
                <media-fullscreen-button class="group inline-flex relative justify-center items-center hover:bg-white/20 rounded-md outline-none ring-media-focus data-focus:ring-4 ring-inset w-10 h-10 cursor-pointer">
                  <media-icon class="media-fullscreen:hidden w-8 h-8" type="fullscreen"></media-icon>
                  <media-icon class="hidden media-fullscreen:block w-8 h-8" type="fullscreen-exit"></media-icon>
                </media-fullscreen-button>
              </media-tooltip-trigger>
              <media-tooltip-content class="slide-out-to-bottom-2 data-[visible]:slide-in-from-bottom-4 z-10 bg-black/90 px-2 py-0.5 rounded-sm font-medium text-white text-sm animate-out data-[visible]:animate-in fade-out data-[visible]:fade-in" placement="top end" offset="30">
                <span class="media-fullscreen:hidden">Enter Fullscreen</span>
                <span class="hidden media-fullscreen:block">Exit Fullscreen</span>
              </media-tooltip-content>
            </media-tooltip>
          <?php endif; ?>
        </media-controls-group>
      </media-controls>
    <?php endif; ?>
  </media-player>
<?php } ?>