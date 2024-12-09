<?php
$values = $this->getVar('values');
$form_elements = $this->getVar('form_elements');

$html = mjmlClient::getCacheFile(52);
$parameters = array(
	'{url}' => rex_yrewrite::getFullUrlByArticleId(24) . "?rex_ycom_activation_key=" . $values['activation_key'] . "&rex_ycom_id=" . $values['email'],
);
foreach ($parameters as $find => $replace) {
	$html = str_replace($find, $replace, $html);
}
echo $html;
