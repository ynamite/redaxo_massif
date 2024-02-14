<?php
	$mjml = false;

	$values = $this->params['values'];
	$form_elements = $this->params['form_elements'];
	
	if($mjml) {

		$html = mjmlClient::getCacheFile(51);
		$parameters= array(
			'{url}' => rex_yrewrite::getFullUrlByArticleId(24)."?rex_ycom_activation_key=".$values['activation_key']."&rex_ycom_id=".$values['email'],
		);
		foreach($parameters as $find => $replace) {
			$html = str_replace($find, $replace, $html);
		}
		echo $html;
	
	} else {
		echo massif_utils::parse('email/email.tpl.head');
		?>
		<div class="content" style="font-size: 1rem;">
			<br />
			<?php if($values['is_user']) {?>
				Guten Tag <?=$values['vorname']?> <?=$values['nachname']?><br /><br />
				Besten Dank für Ihr Interesse an einer Mitgliedschaft im VZF.<br />
				Wir werden Ihre Anmeldung prüfen und Sie sobald wie möglich kontaktieren.<br />
			<?php } else { ?>
				<h3>Anfrage Mitgliedschaft www.<?=\rex_yrewrite::getCurrentDomain()?></h3>
			<?php } ?>
			<br />
			<table style="<?=$styleTable?>" width="100%" cellpadding="0" cellspacing="0">

				<?php foreach($form_elements as $key => $label){
					if($key == 'activation_key')
						continue;

					$value = $values[$key.'_LABELS'] ? $values[$key.'_LABELS'] : $values[$key];
					if($label && $value){
					?>
					<tr>
						<td style="<?=$styleLabel?>"><?=$label?>:</td>
						<td><?=$value?></td>
					</tr>
					<?php }
				} ?>

			</table>  

			<?=massif_utils::parse('email/email.tpl.footer')?>

		</div>		
		<?php
	}

	
?>
