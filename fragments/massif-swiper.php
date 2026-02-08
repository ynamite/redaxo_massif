<?php

use Ynamite\Massif\Utils;
use Ynamite\Massif\Media;

$swiperType = $this->getVar('swiperType', 'default');
$images = $this->getVar('images', []);
$imageOptions = $this->getVar('imageOptions', []);
$imageClassName = $this->getVar('imageClassName', '');
$priorityFirstImage = $this->getVar('priorityFirstImage', true);
$slideCallback = $this->getVar('slideCallback', null);
$content = $this->getVar('content', '');
$pager = $this->getVar('pager', false);
$dirNav = $this->getVar('dirNav', false);
$controls = $this->getVar('controls', true);
$prevIcon = $this->getVar('prevIcon', '<i class="icon">◀</i>');
$nextIcon = $this->getVar('nextIcon', '<i class="icon">▶</i>');
$wrap = $this->getVar('wrap', true);
$classNames = $this->getVar('classNames', '');


$reflection = new ReflectionMethod(Media\Media::class, 'factory');
$params = $reflection->getParameters();
$paramNames = array_filter(array_map(fn($p) => $p->getName() !== 'wrapperClassName' ? $p->getName() : null, $params));
$args = array_intersect_key($imageOptions, array_flip($paramNames));
$rest = array_diff_key($imageOptions, array_flip($paramNames));

?>
<?php if ($wrap) { ?>
  <div class="swiper<?php if ($classNames) echo ' ' . $classNames; ?>" data-swiper-type="<?php echo $swiperType; ?>">
  <?php } ?>
  <div class="swiper-container">
    <div class="swiper-wrapper">
      <?php if ($content) echo $content;
      else foreach ($images as $key => $img) {

        $resolvedArgs = array_merge($args, ['src' => $img, 'loading' => $args['loading'] ?? ($priorityFirstImage && $key == 0 ? 'eager' : 'lazy'), 'sizes' => $args['sizes'] ?? '100vw']);
        $media = Media\Media::factory(...$resolvedArgs);
        if (!$media) {
          continue;
        }
        foreach ($rest as $key => $value) {
          $media->setConfig($key, $value);
        }
        if ($slideCallback && is_callable($slideCallback)) {
          $slideCallback($media, $key);
        }

        echo Utils\Rex::parse('massif-swiper-slide', ['idx' => $key, 'content' => $media->render()]);
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