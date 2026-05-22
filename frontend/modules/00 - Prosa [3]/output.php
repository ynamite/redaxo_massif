<?php

use Ynamite\Massif\Utils;

$slice = null;
try {
    $slice = $this->getCurrentSlice();
} catch (Exception) {
} finally {
    $slice = $slice ?? rex_article_slice::getArticleSliceById('REX_SLICE_ID');
}
$isFirst = $slice->getPriority() === 1;

?>
<section class="fluid-grid stack-16 default-content" <?= $isFirst ? ' data-first-row' : '' ?>>

    <div class="span-full flush">
        REX_PIC[src="REX_MEDIA[1]" preload="true" loading="eager" ratio="16:9" sizes="100vw"]
    </div>
    <div class="span-md">
        <article class="prose prose-lg">

            REX_VALUE[1 output=html]

        </article>

        <?= Utils\Rex::parse('massif-buttons', [
            'items' => 'REX_VALUE[id=19]'
        ]) ?>

    </div>

</section>