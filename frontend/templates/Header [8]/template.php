<?php

use Ynamite\ViteRex\Assets;

?>
<a id="top"></a>

<header class="z-10 flex py-4 header section-px" id="header">

	<div class="site-logo clamp-[w,40,52]">
		<a href="<?= rex_getUrl(rex_article::getSiteStartArticleId()); ?>" title="<?= rex::getServerName() ?>">
			<span hidden><?= rex::getServerName() ?></span>
			<?= Assets::inline('img/massif-logo.svg') ?>
		</a>
	</div>

	<a href="<?= rex_getUrl(rex_article::getCurrentId()) ?>#content" data-scroll class="to-content" title="Zum Inhalt">
		<span hidden>Zum Inhalt</span>
	</a>

	REX_TEMPLATE[key="menu"]

</header>