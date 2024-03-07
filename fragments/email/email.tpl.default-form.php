<?php

$values = $this->params['values'];
$form_elements = $this->params['form_elements'];

$styleLabel = 'vertical-align:top; width: 260px; padding-right: 8px;';
$styleTable = 'font-size: 1rem; vertical-align:top;';

?>
<?= massif_utils::parse('email/email.tpl.head') ?>

<div class="content" style="font-size: 1rem;">
	<br />
	<?php if (isset($values['is_user']) && $values['is_user']) {
		echo str_replace('_fname_,', ucwords($values['fname']), $values['user_text']);
	} else { ?>
		<h3>Anfrage von <?= \rex_yrewrite::getCurrentDomain() ?></h3>
		<br />
		<b>Kontakt</b>
		<table style="<?= $styleTable ?>" width="100%" cellpadding="0" cellspacing="0">

			<?php foreach ($form_elements as $key => $label) {

				$value = isset($values[$key . '_LABELS']) ? $values[$key . '_LABELS'] : $values[$key];

				if ($value) {
			?>
					<tr>
						<td style="<?= $styleLabel ?>"><?= $label ?>:</td>
						<td><?= $value ?></td>
					</tr>
			<?php }
			} ?>

		</table>
	<?php } ?>


	<?= massif_utils::parse('email/email.tpl.footer', ['recipient' => $values['recipient']]) ?>

</div>