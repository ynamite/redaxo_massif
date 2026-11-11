<?php

namespace Ynamite\Massif;

use rex;
use rex_exception;
use rex_logger;
use rex_sql;
use rex_yform_action_abstract;
use rex_yform_email_template;
use Ynamite\Massif\Utils as massif_utils;
use Ynamite\MassifSettings\Utils as settings_utils;

class Form
{

  /*
	*	send email with template
	*/

  public static function send_yform_email_template($from, $fromName, $recipient, $subject, $values, $_form_elements, $params = []): bool
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
    $settings['skip_field_types'] = ['html', 'validate', 'action', 'csrf', 'mupload', 'multi_file_upload'];
    $settings['skip_fields'] = ['termsofuse_accepted', 'type'];
    $settings['replace_labels'] = ['subscribe_newsletter' => 'Newsletter', 'contact_city' => 'Stadt', 'confirm' => 'DSE akzeptiert?', 'context' => 'Interesse an', 'attachment' => 'Bewerbungsunterlagen', 'vacancy' => 'Stelle'];
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



    $renderMail = static function (string $template, array $values) use ($form_elements): string {
      // Keep visitor braces out of the template processors without changing header/log values.
      $prefix = '0' . bin2hex(random_bytes(16));
      foreach ($form_elements as $key => $label) {
        $visitorValues = array_intersect_key($values, [$key => true, $key . '_LABELS' => true]);
        array_walk_recursive($visitorValues, static function (&$value) use ($prefix): void {
          if (is_string($value)) {
            $value = strtr($value, ['{' => $prefix . 'open', '}' => $prefix . 'close']);
          }
        });
        $values = array_replace($values, $visitorValues);
      }
      $body = sprogdown(settings_utils::replaceStrings(massif_utils\Rex::parse($template, ['values' => $values, 'form_elements' => $form_elements])));
      return strtr($body, [$prefix . 'open' => '&#123;', $prefix . 'close' => '&#125;']);
    };
    $mailBody = $renderMail($settings['template'], $values);

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
      "body" => html_entity_decode(strip_tags($mailBody), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
      "body_html" => $mailBody,
      'attachments' => isset($values['attachments']) ? $values['attachments'] : [],
    ];


    if ($settings['send_user_email'] && $values['email']) {
      $values['is_user'] = true;
      $mailBody = $renderMail($settings['template_user'], $values);
      $template['user'] = $template['admin'];
      $template['user']['mail_reply_to'] = $settings['user_reply_to'];
      $template['user']['mail_reply_to_name'] = $fromName;
      $template['user']['mail_to'] = $values['email'];
      $template['user']['mail_to_name'] = trim($values['fname'] . ' ' . $values['lname']) ? $values['fname'] . ' ' . $values['lname'] : $values['email'];
      $template['user']['body'] = html_entity_decode(strip_tags($mailBody), ENT_QUOTES | ENT_HTML5, 'UTF-8');
      $template['user']['body_html'] = $mailBody;
    }

    $sent = true;
    foreach ($template as $tpl) {

      if (!rex_yform_email_template::sendMail($tpl, $tpl['name'])) {
        $sent = false;
      }
    }
    if (!$sent) {
      rex_logger::factory()->log('warning', 'Form mail not sent: ' . $subject);
    }
    return $sent;
  }

  public static function fail(rex_yform_action_abstract $action, string $message, string $field = 'submit'): never
  {
    foreach ($action->params['values'] as $index => $value) {
      if ($value->getName() === $field) {
        $action->params['warning'][$index] = $action->params['error_class'];
        $action->params['warning_messages'][$index] = $message;
        break;
      }
    }
    throw new rex_exception($message);
  }
}
