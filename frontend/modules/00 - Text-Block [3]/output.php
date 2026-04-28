<?php

use Ynamite\Massif\Media;
use Ynamite\Massif\Redactor\Output;
use Ynamite\Massif\Utils;
// use Ynamite\Massif\Markitup;
$slice = null;
try {
    $slice = $this->getCurrentSlice();
} catch (Exception) {
} finally {
    $slice = $slice ?? rex_article_slice::getArticleSliceById('REX_SLICE_ID');
}
$isFirst = $slice->getPriority() === 1;
$output = new Output((int)'REX_SLICE_ID');
$content = $output->parse('REX_VALUE[2 output=html]');

$buttons = [['url' => 'REX_VALUE[19]', 'label' => '<span>' . 'REX_VALUE[20]' . '</span>', 'style' => 'grid']];

?>
<section class="page-margin section-x-padding section-y-padding default-content" <?= $isFirst ? ' data-first-row' : '' ?>>

    <div class="tw-prose tw-prose-xl">
        <?= Utils\Html::getH1('REX_VALUE[1]') ?>
        <?php /* Markitup::parseOutput('markdown', 'REX_VALUE[2 output=html]'); */ ?>

        <?= $content ?>

        <?= Media\Image::get(src: 'REX_MEDIA[1]') ?>
    </div>

    <?php if ('REX_VALUE[19]REX_VALUE[20]') { ?>
        <div class="prose<?php if ('REX_VALUE[1]REX_VALUE[2]REX_MEDIA[1]') { ?> mt-8 md:mt-16<?php } ?>">
            <?= Utils\Url::getCustomLinks($buttons) ?>
        </div>
    <?php } ?>

</section>