<?php
$params = $this->params['params'];
?>
<div class="swiper-container">
    <div class="swiper-wrapper" <?php if (rex::isBackend()) echo ' style="list-style:none; margin: 0; padding: 0; display: grid; grid-gap: 8px;grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));"'; ?>>
        <?php if ($params['content']) echo $params['content'];
        else foreach ($params['images'] as $key => $img) {
            echo massif_utils::parse('swiper-slide', null, ['idx' => $key, 'content' => rex_media_plus::get($img)->setAttributes(['loading="lazy"'])->getPicture('default')]);
        } ?>
    </div>
    <?php if ($params['controls'] && rex::isFrontend()) { ?>
        <div class="swiper-controls">
            <?php if ($params['dir-nav']) { ?>
                <div class="swiper-button-prev"><?php require(theme_path::assets('img/ico-arrow-lg-right.svg')) ?></div>
                <div class="swiper-button-next"><?php require(theme_path::assets('img/ico-arrow-lg-right.svg')) ?></div>
            <?php } ?>
            <?php if ($params['pager']) { ?>
                <div class="swiper-pagination"></div>
            <?php } ?>
        </div>
    <?php } ?>
</div>