<?php

use Ynamite\Massif\ArticleNav;
use Ynamite\MassifSettings\Utils;

?>
<footer class="bg-surface mt-clamp-16-32 border-white/10 border-t [&_a]:hover:text-accent [&_a]:transition-colors [&_a]:duration-300">
	<div class="flex md:flex-row flex-col md:justify-between md:items-center gap-6 mx-auto px-6 sm:px-8 py-8 max-w-7xl text-muted text-sm">
		<p>
			© <?= date('Y') ?> <?= Utils::get('address_firma') ?> /
			<?= Utils::get('address_phone_formatted') ?> /
			<?= Utils::get('address_e_mail_formatted') ?>

		</p>
		<?= ArticleNav::get(options_user: ['list' => false, 'class' => 'flex items-center gap-6 text-sm [&_a.active]:text-accent']) ?>
	</div>
	<div class="flex md:flex-row flex-col md:items-center gap-6 mx-auto px-6 sm:px-8 pb-8 max-w-7xl">
		<a href="<?= Utils::get('social_instagram') ?>" title="Instagram" target="_blank" class="iconify lucide--instagram">
			<span class="sr-only">Instagram</span>
		</a>
		<a href="<?= Utils::get('social_linkedin') ?>" title="LinkedIn" target="_blank" class="iconify lucide--linkedin">
			<span class="sr-only">LinkedIn</span>
		</a>
	</div>
</footer>