<?php

use Ynamite\Massif\Utils;
use Ynamite\Media\Image;

$swiperType = $this->getVar('swiperType', 'default');
$images = $this->getVar('images', []);
$imageOptions = $this->getVar('imageOptions', []);
$priorityFirstImage = ($imageOptions['fetchpriority'] ?? null) === 'high';
$slideCallback = $this->getVar('slideCallback', null);
$content = $this->getVar('content', '');
$pager = $this->getVar('pager', false);
$dirNav = $this->getVar('dirNav', false);
$controls = $this->getVar('controls', true);
$prevIcon = $this->getVar('prevIcon', '<i class="icon">◀</i>');
$nextIcon = $this->getVar('nextIcon', '<i class="icon">▶</i>');
$wrap = $this->getVar('wrap', true);
$className = $this->getVar('className', '');

?>
<?php if ($wrap) { ?>
  <div class="swiper<?php if ($className) echo ' ' . $className; ?>" data-swiper-type="<?php echo $swiperType; ?>">
  <?php } ?>
  <div class="swiper-container">
    <div class="swiper-wrapper">
      <?php if ($content) echo $content;
      else foreach ($images as $key => $img) {

        $image = Image::picture(
          src: $img,
          alt: $imageOptions['alt'] ?? '',
          sizes: $imageOptions['sizes'] ?? '',
          ratio: $imageOptions['ratio'] ?? null,
          loading: $imageOptions['loading'] ?? ($priorityFirstImage && $key === 0 ? 'eager' : 'lazy'),
          fetchPriority: $imageOptions['fetchpriority'] ?? 'auto',
          class: $imageOptions['class'] ?? null,
          fit: $imageOptions['fit'] ?? null,
          width: $imageOptions['width'] ?? null,
          height: $imageOptions['height'] ?? null,
        );

        echo Utils\Rex::parse('massif-swiper-slide', ['idx' => $key, 'content' => $image]);
      } ?>
    </div>
    <?php if ($controls && rex::isFrontend()) { ?>
      <div class="swiper-controls" x-data="swipers" x-cloak x-show="inited">
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