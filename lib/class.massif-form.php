<?php

class massif_form
{

  /*
	*	send email with template
	*/

  public static function send_yform_email_template($from, $fromName, $recipient, $subject, $values, $_form_elements, $params = [])
  {

    if (rex::isDebugMode()) {
      $recipient = 'studio@massif.ch';
    }

    $values['recipient'] = $recipient;

    $settings['log'] = false;
    $settings['user_reply_to'] = $recipient;
    $settings['log_table'] = 'rex_yf_mail_log';
    $settings['template'] = 'email.contact-form';
    $settings['template_user'] = '';
    $settings['send_user_email'] = false;
    $settings['skip_field_types'] = ['html', 'validate', 'action', 'csrf', 'mupload'];
    $settings['skip_fields'] = ['termsofuse_accepted', 'type'];
    $settings['replace_labels'] = ['subscribe_newsletter' => 'Newsletter', 'contact_city' => 'Stadt', 'confirm' => 'DSE akzeptiert?', 'context' => 'Interesse an', 'attachment' => 'Bewerbungsunterlagen'];
    $settings['date_fields'] = ['dob'];
    $settings = array_merge($settings, $params);
    if (!$settings['template_user'])
      $settings['template_user'] = $settings['template'];

    $form_elements = [];

    foreach ($_form_elements as $el) {
      if (in_array($el[0], $settings['skip_field_types']))
        continue;
      if (in_array($el[1], $settings['skip_fields']))
        continue;
      if (isset($settings['replace_labels'][$el[1]])) {
        $el[2] = $settings['replace_labels'][$el[1]];
      }
      $form_elements[$el[1]] = str_replace('*', '', $el[2]);
    }
    unset($_form_elements);

    foreach ($form_elements as $key => $label) {
      $value = isset($values[$key . '_LABELS']) ? $values[$key . '_LABELS'] : (isset($values[$key]) ? $values[$key] : '');

      if ($value === '0') {
        $value = 'nein';
      }
      if ($value === '1') {
        $value = 'ja';
      }

      if (in_array($key, $settings['date_fields'])) {
        $value = date('d.m.Y', strtotime($value));
      }
      $values[$key] = $value;
    }



    $mailBody = sprogdown(\Ynamite\MassifSettings\Utils::replaceStrings(\massif_utils::parse($settings['template'], null, ['values' => $values, 'form_elements' => $form_elements])));

    if ($settings['log'] && $settings['log_table']) {

      $valueParams = json_decode(rex_request('params', 'string', ''), true);

      $sql = rex_sql::factory();
      $sql->setTable($settings['log_table']);
      $sql->setValue('type', $values['type']);
      $sql->setValue('email', $from);
      $sql->setValue('context', $values['context']);
      $sql->setValue('fname', $values['fname']);
      $sql->setValue('lname', $values['lname']);
      $sql->setValue('recipient', $recipient);
      $sql->setValue('send_date', date('Y-m-d H:i:s'));
      $sql->setValue('body', $mailBody);
      $sql->setValue('attachment', \rex_yform_value_mupload::getUserFolder() . $values['attachment']);
      $sql->setValue('data_id', $valueParams['id']);
      $sql->insert();
    }

    $template = [];

    // Admin mail
    $template['admin'] = [
      "name" => "general",
      "mail_from" => $from,
      "mail_from_name" => $fromName,
      "mail_reply_to" => $values['email'] ? $values['email'] : $from,
      "mail_reply_to_name" => trim($values['fname'] . ' ' . $values['lname']) ? $values['fname'] . ' ' . $values['lname'] : $from,
      "mail_to" => $recipient,
      "mail_to_name" => $fromName,
      "subject" => $subject,
      "body" => strip_tags($mailBody),
      "body_html" => $mailBody,
      'attachments' => isset($values['attachments']) ? $values['attachments'] : [],
    ];


    if ($settings['send_user_email'] && $values['email']) {
      $values['is_user'] = true;
      $mailBody = sprogdown(\Ynamite\MassifSettings\Utils::replaceStrings(\massif_utils::parse($settings['template_user'], null, ['values' => $values, 'form_elements' => $form_elements])));
      $template['user'] = $template['admin'];
      $template['user']['mail_reply_to'] = $settings['user_reply_to'];
      $template['user']['mail_reply_to_name'] = $fromName;
      $template['user']['mail_to'] = $values['email'];
      $template['user']['mail_to_name'] = trim($values['fname'] . ' ' . $values['lname']) ? $values['fname'] . ' ' . $values['lname'] : $values['email'];
      $template['user']['body'] = strip_tags($mailBody);
      $template['user']['body_html'] = $mailBody;
    }

    foreach ($template as $tpl) {

      if (!\rex_yform_email_template::sendMail($tpl, $tpl['name'])) {
        \rex_var_dumper::dump('E-Mail konnte nicht gesendet werden.');
        \rex_var_dumper::dump($tpl);
      }
    }
    return true;
  }
}
