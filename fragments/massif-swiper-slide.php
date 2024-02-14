<?php
$idx = $this->params['idx'];
$content = $this->params['content'];
?>
<div class="swiper-slide" <?php if ($idx == 0) echo ' data-swiper-autoplay="6000"'; ?>>
  <div class="swiper-slide-inner">
    <?= $content ?>
  </div>
</div>