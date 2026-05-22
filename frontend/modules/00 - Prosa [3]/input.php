<?php

use FriendsOfRedaxo\MForm;


echo MForm::factory()
  ->addMediaField(1, array('label' => 'Bild'))
  ->addTextareaField(1, ['label' => 'Text', 'class' => 'tiny-editor', 'data-profile' => 'massif'])
  ->addCustomLinkMultipleField(19, array('label' => 'Button', 'btn_add' => 'Button hinzufügen'))
  ->show();
