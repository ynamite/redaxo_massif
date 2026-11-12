  <?php

  use Ynamite\Massif\Form;

  $response = $this->getVar('response', 'Vielen Dank für Ihre Nachricht. Wir werden uns so bald wie möglich bei Ihnen melden.');

  $from = 'studio@massif.ch';
  $fromName = rex::getServerName() . ' ' . rex_category::getCurrent()->getName();
  $recipient = $from;
  $subject = $fromName . ' – Anfrage Webseite';
  $email_template = 'email/email.tpl.default-form';
  $send_user_mail = false;

  $form_name = 'form-general';
  $url = rex_getUrl(rex_article::getCurrentId()) . '#' . $form_name . '_wrap';

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


  $yform->setValueField('checkbox', array("confirm", 'Ich bin mit den <a href="' . rex_getUrl(22) . '" target="_blank" rel="noopener" data-no-swup>Datenschutzbestimmungen</a> einverstanden.*', 0, 'no_db'));
  $yform->setValidateField('empty', ["confirm", "Bitte bestätigen!"]);

  // $yform->setValueField('html', ['required', '<p class="text-smaller form-required typo-margin">* {{Pflichtfelder}}</p>']);
  $yform->setValueField('html', ['submit', '<div class="form-group" id="yform-' . $form_name . '-submit"><p class="btn-set"><button class="btn-ghost" type="submit" value="Senden"><span>Senden</span></button></p></div>']);

  $yform->setValueField('spam_protection', array("honeypot", "Bitte nicht ausfüllen.", "Ihre Anfrage wurde als Spam erkannt und gelöscht. Bitte versuchen Sie es in einigen Minuten erneut oder wenden Sie sich persönlich an uns.", 0));

  //$yform->setActionField('html', ['<div class="alert" data-redirect="'.rex_getUrl(19).'" data-modal></div>' ] );
  $yform->setActionField('callback', [function (rex_yform_action_abstract $action) use ($from, $fromName, $recipient, $subject, $email_template, $send_user_mail) {
    $values = $action->params['value_pool']['email'];
    $form_elements = $action->params['form_elements'];
    $params = ['template' => $email_template, 'send_user_email' => $send_user_mail];
    if (!Form::send_yform_email_template($from, $fromName, $recipient, $subject, $values, $form_elements, $params)) {
      Form::fail($action, 'Ihre Nachricht konnte nicht gesendet werden. Bitte versuchen Sie es später noch einmal.');
    }
  }]);
  $yform->setActionField('html', ['<div class="alert success">' . $response . '</div>']);


  $yformOut = $yform->getForm();
  // Shield visitor values from Sprog without altering YForm's inline scripts.
  $yformOut = preg_replace_callback('~<input\b[^>]*>|<textarea\b[^>]*>.*?</textarea>~si', static fn (array $match): string => strtr($match[0], ['{' => '&#123;', '}' => '&#125;']), $yformOut);

  ?>
  <div class="rex-yform-wrap">

    <?= $yformOut ?>
  </div>