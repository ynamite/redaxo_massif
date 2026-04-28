<?php

use Ynamite\Massif\ArticleNav;
use Ynamite\MassifSettings\Utils;

?>
<footer id="footer" class="lg:grid lg:grid-cols-2 xl:grid-cols-4 links-underlined-i footer section-p lg:clamp-[gap-x,10,20]">
	<div>
		<?= Utils::getWrapped('address_firma') ?>
		<?= Utils::getWrapped('address_zusatz') ?>
	</div>
	<div>
		<?= Utils::getWrapped('address_phone_formatted') ?>
		<?= Utils::getWrapped('address_e_mail_formatted') ?>
	</div>
	<div>
		<?= ArticleNav::get(options_user: ['list' => true]) ?>
	</div>
	<div>
		<?= Utils::getWrapped('social_instagram_formatted') ?>
		<?= Utils::getWrapped('social_linkedin_formatted') ?>
	</div>
</footer>