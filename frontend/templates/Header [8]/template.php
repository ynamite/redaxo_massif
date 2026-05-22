<?php

use Ynamite\ViteRex\Assets;

?>
<a id="top"></a>
<a href="<?= rex_getUrl(rex_article::getCurrentId()) ?>#content" data-scroll class="sr-only" title="Zum Inhalt">
	<span hidden>Zum Inhalt</span>
</a>
<header class="top-0 z-40 after:-z-10 fixed after:absolute after:inset-0 flex justify-center items-center gap-6 after:bg-body/85 after:backdrop-blur border-white/10 border-b w-full">
	<div class="flex justify-between items-center mx-auto px-6 sm:px-8 py-4 w-full max-w-7xl">
		<span hidden><?= rex::getServerName() ?></span>
		<a href="<?= rex_getUrl(rex_article::getSiteStartArticleId()); ?>" title="<?= rex::getServerName() ?>" rel="home" class="group flex items-center gap-3">
			<span class="block [&_svg]:block w-30 md:w-40 [&_svg]:size-full shrink-0" aria-hidden="true">
				<?= Assets::inline('img/massif-logo.svg') ?>
			</span>
			<?php /*<span hidden class="flex flex-col leading-tight">
					<span class="w-fit font-extrabold text-gradient-brand text-3xl md:text-4xl tracking-tight"><?= rex::getServerName() ?></span>
					<span class="hidden sm:block font-medium text-[11px] text-muted uppercase tracking-widest">
						Supercharged <span class="text-brand-magenta">REDAXO</span> frontend with <span class="text-brand-cyan">Vite 8</span>
					</span>
				</span>*/ ?>
		</a>
		<div>
			REX_TEMPLATE[key="menu"]
		</div>
	</div>
</header>