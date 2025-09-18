<?php

use Ynamite\Massif\Utils;
use Ynamite\Massif\Media;

$images = $this->getVar('images', []);
$content = $this->getVar('content', '');
$pager = $this->getVar('pager', false);
$dirNav = $this->getVar('dirNav', false);
$controls = $this->getVar('controls', true);
$prevIcon = $this->getVar('prevIcon', '<i class="icon">◀</i>');
$nextIcon = $this->getVar('nextIcon', '<i class="icon">▶</i>');
$wrap = $this->getVar('wrap', true);
$classNames = $this->getVar('classNames', '');
?>
<?php if ($wrap) { ?>
  <div class="swiper<?php if ($classNames) echo ' ' . $classNames; ?>">
  <?php } ?>
  <div class="swiper-container">
    <div class="swiper-wrapper">
      <?php if ($content) echo $content;
      else foreach ($images as $key => $img) {
        $image = Media\Image::get(src: $img, loading: $key == 0 ? 'eager' : 'lazy');
        echo Utils\Rex::parse('massif-swiper-slide', ['idx' => $key, 'content' => $image]);
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