<?php

use Ynamite\Massif\Media;
use Ynamite\Massif\Utils;

rex::setProperty('has-visual', true);

$maxWidth = 6016;
$slides = explode(',', 'REX_MEDIALIST[1]');
shuffle($slides);
$slides = array_slice($slides, 0, 7);
$swiperHtml = [];
foreach ($slides as $idx => $slide) {
    $content = [];
    $content[] = Media\Image::get(src: $slide, maxWidth: 1440, ratio: 400 / 1440,  loading: $idx == 0 ? Media\LoadingBehavior::EAGER : Media\LoadingBehavior::LAZY);
    $swiperHtml[] = Utils\Rex::parse('massif-swiper-slide', ['idx' => $idx, 'content' => implode("\n", $content)]);
}
echo Utils\Rex::parse('massif-swiper', ['content' => implode("\n", $swiperHtml)]);
