<?php
$idx = $this->getVar('idx');
$content = $this->getVar('content');
?>
<div class="swiper-slide" <?php /*if ($idx == 0) echo ' data-swiper-autoplay="6000"'; */ ?>>
  <div class="swiper-slide-inner">
    <?= $content ?>
  </div>
</div>