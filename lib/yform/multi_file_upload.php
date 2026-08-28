<?php

/**
 * Generischer Multi-File-Upload (Dropzone): wie mupload, aber mit
 * konfigurierbarem Zip-Namen und Backend-geschütztem Download über die
 * massif_download-API in der Datensatzliste.
 *
 * multi_file_upload|name|label – weitere Optionen über Elemente:
 * file_types, file_types_human_readable, max_file_size, min_file_size,
 * tmp_folder, upload_folder, zip_name
 */
class rex_yform_value_multi_file_upload extends rex_yform_value_mupload
{
  public function getDescription(): string
  {
    return 'multi_file_upload|name|label';
  }

  public function getDefinitions(): array
  {
    $definitions = parent::getDefinitions();
    $definitions['name'] = 'multi_file_upload';
    $definitions['description'] = 'Multi-File-Upload (Dropzone), Ablage als Zip';
    return $definitions;
  }

  public static function getListValue($params)
  {
    $value = (string) $params['subject'];
    if ('' === $value) {
      return '';
    }
    $url = rex_url::backendController([
      'rex-api-call' => 'massif_download',
      'table_name' => $params['params']['field']['table_name'],
      'field' => $params['params']['field']['name'],
      'data_id' => $params['list']->getValue('id'),
    ]);
    return '<a href="' . $url . '" download>' . rex_escape(basename($value)) . '</a>';
  }
}
