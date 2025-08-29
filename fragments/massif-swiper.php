<?php

use Ynamite\Massif\Utils;
use Ynamite\Massif\Media;

$images = $this->getVar('images', []);
$content = $this->getVar('content', '');
$pager = $this->getVar('pager', false);
$dirNav = $this->getVar('dirNav', false);
$controls = $this->getVar('controls', true);
$prevIcon = $this->getVar('prevIcon', '<i class="icon-[bi--chevron-left] icon"></i>');
$nextIcon = $this->getVar('nextIcon', '<i class="icon-[bi--chevron-right] icon"></i>');
$wrap = $this->getVar('wrap', true);
?>
<?php if ($wrap) { ?>
  <div class="swiper">
  <?php } ?>
  <div class="swiper-container">
    <div class="swiper-wrapper" <?php if (rex::isBackend()) echo ' style="list-style:none; margin: 0; padding: 0; display: grid; grid-gap: 8px;grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));"'; ?>>
      <?php if ($content) echo $content;
      else foreach ($images as $key => $img) {
        $image = Media\Image::get(src: $img, loading: $key == 0 ? 'eager' : 'lazy');
        echo Utils\Rex::parse('swiper-slide', ['idx' => $key, 'content' => $image]);
      } ?>
    </div>
    <?php if ($controls && rex::isFrontend()) { ?>
      <div class="swiper-controls">
        <?php if ($dirNav) { ?>
          <div class="swiper-button-prev">
            <?php echo $prevIcon; ?>
          </div>
          <div class="swiper-button-next">
            <?php echo $nextIcon; ?>
          </div>
        <?php } ?>
        <?php if ($pager) { ?>
          <div class="swiper-pagination"></div>
        <?php } ?>
      </div>
    <?php } ?>
  </div>
  <?php if ($wrap) { ?>
  </div>
<?php } ?>