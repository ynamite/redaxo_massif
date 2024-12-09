  <?php

  $response = $this->params['response'];

  $from = 'studio@massif.ch';
  $fromName = rex::getServerName() . ' ' . rex_category::getCurrent()->getName();
  $recipient = $from;
  $subject = $fromName . ' – Anfrage Webseite';
  $email_template = 'email/email.tpl.default-form';
  $send_user_mail = false;

  $url = rex_getUrl(rex_article::getCurrentId());

  $form_name = 'form-general';

  $yform = new rex_yform();
  $yform->setDebug(false);
  $yform->setObjectparams('real_field_names', 1);
  $yform->setObjectparams('submit_btn_show', false);
  $yform->setObjectparams('form_wrap_id', $form_name . '_wrap');
  $yform->setObjectparams('form_name', $form_name);
  $yform->setObjectparams('form_class', 'rex-yform rex-yform-' . $form_name);
  $yform->setObjectparams('form_action', $url);

  $yform->setValueField('textarea', ["message", "Nachricht", '', '0', ["rows" => 5]]);
  $yform->setValueField('text', ['fname', 'Vorname*']);
  $yform->setValidateField('empty', ["fname", "Bitte ausfüllen!"]);
  $yform->setValueField('text', ['lname', 'Nachname*']);
  $yform->setValidateField('empty', ["lname", "Bitte ausfüllen!"]);
  $yform->setValueField('text', ["email", "E-Mail*"]);
  $yform->setValidateField('type', ["email", "email", "Bitte gültige E-Mail-Adresse angeben!", 0]);
  $yform->setValueField('text', ["mobile", "Mobile*"]);
  $yform->setValidateField('empty', ["mobile", "Bitte ausfüllen!"]);


  $yform->setValueField('checkbox', array("confirm", 'Ich bin mit den <a href="' . rex_getUrl(22) . '" onclick="window.open(\'' . rex_getUrl(22, null, ['popup' => '1']) . '\',\'popup\',\'width=760,height=600\'); return false;" data-no-swup>Datenschutzbestimmungen</a> einverstanden.*', 0, 'no_db'));
  $yform->setValidateField('empty', ["confirm", "Bitte bestätigen!"]);

  // $yform->setValueField('html', ['required', '<p class="text-smaller form-required typo-margin">* {{Pflichtfelder}}</p>']);
  $yform->setValueField('html', ['required', '<p class="btn-set"><button class="btn" type="submit" value="Senden"><span>Senden</span></button></p>']);

  $yform->setValueField('spam_protection', array("honeypot", "Bitte nicht ausfüllen.", "Ihre Anfrage wurde als Spam erkannt und gelöscht. Bitte versuchen Sie es in einigen Minuten erneut oder wenden Sie sich persönlich an uns.", 0));

  //$yform->setActionField('html', ['<div class="alert" data-redirect="'.rex_getUrl(19).'" data-modal></div>' ] );
  $yform->setActionField('html', ['<div class="alert success">' . $response . '</div>']);


  $yformOut = $yform->getForm();

  $formSent = ($yform->getObjectparams('send') && !$yform->getObjectparams('warning_messages'));


  if ($formSent) {

    $values = $yform->objparams['value_pool']['email'];
    $form_elements = $yform->objparams['form_elements'];
    $params = ['template' => $email_template, 'send_user_email' => $send_user_mail];
    massif_form::send_yform_email_template($from, $fromName, $recipient, $subject, $values, $form_elements, $params);
  } else {
    //echo '<h3 class="h2 centered form-title">Wir freuen uns auf<br />Ihre Kontaktaufnahme!</h3>';
  }

  ?>
  <div class="rex-yform-wrap typo-margin">

    <?= $yformOut ?>
  </div>