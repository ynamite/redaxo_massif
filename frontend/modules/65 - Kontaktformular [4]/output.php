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
<section class="fluid-grid stack-16 form-section" <?= $isFirst ? ' data-first-row' : '' ?>>

    <div class="span-md">
        <div class="prose prose-lg">

            <?php if ('REX_VALUE[1]') { ?><h1>REX_VALUE[1]</h1><?php } ?>
            REX_VALUE[2 output=html]

        </div>

    </div>
    <div class="span-md stack-8">
        <h3>Anfrageformular</h3>
        <?php
        echo Utils\Rex::parse('massif-form-general', ['response' => 'REX_VALUE[3 output=html]']);
        ?>

    </div>

</section>