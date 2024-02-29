<?php
$params = $this->params['params'];
?>
<div class="swiper-container">
    <div class="swiper-wrapper" <?php if (rex::isBackend()) echo ' style="list-style:none; margin: 0; padding: 0; display: grid; grid-gap: 8px;grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));"'; ?>>
        <?php if ($params['content']) echo $params['content'];
        else foreach ($params['images'] as $key => $img) {
            echo massif_utils::parse('swiper-slide', null, ['idx' => $key, 'content' => massif_img::get($img, ['width' => 371, 'loading' => $idx == 0 ? 'eager' : 'lazy'])]);
        } ?>
    </div>
    <?php if ($params['controls'] && rex::isFrontend()) { ?>
        <div class="swiper-controls">
            <?php if ($params['dir-nav']) { ?>
                <div class="swiper-button-prev">
                    <i class="icon icon-[ion--chevron-left]"></i>
                </div>
                <div class="swiper-button-next ">
                    <i class="icon icon-[ion--chevron-right]"></i>
                </div>
            <?php } ?>
            <?php if ($params['pager']) { ?>
                <div class="swiper-pagination"></div>
            <?php } ?>
        </div>
    <?php } ?>
</div>