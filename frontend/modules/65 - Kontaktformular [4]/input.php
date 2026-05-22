<?php


use FriendsOfRedaxo\MForm;

$mformWrap = MForm::factory();


$mformWrap->addTextareaField(1, ['label' => 'Titel'])->setAttributes(["rows" => 3, "style" => "font-size: 1.5em; font-weight: bold; field-sizing: content"]);
$mformWrap->addTextareaField(2, ['label' => 'Text'])->setAttribute("rows", 8)->setAttribute("class", "tiny-editor")->setAttribute("data-profile", "massif");
$mformWrap->addTextareaField(3, ['label' => 'Text nach Versand'])->setAttribute("rows", 8)->setAttribute("class", "tiny-editor")->setAttribute("data-profile", "massif");

echo $mformWrap->show();
