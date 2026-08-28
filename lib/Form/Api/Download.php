<?php

namespace Ynamite\Massif\Form\Api;

use rex;
use rex_api_exception;
use rex_api_function;
use rex_path;
use rex_request;
use rex_response;
use rex_yform_manager_dataset;
use rex_yform_manager_table;

/**
 * Backend-geschützter Download für mupload/multi_file_upload-Felder.
 * index.php?rex-api-call=massif_download&table_name=...&field=...&data_id=...
 *
 * Die Dateien liegen unter var/data (ausserhalb des Webroots); der Pfad wird
 * ausschliesslich aus der Feld-Konfiguration und dem gespeicherten Wert
 * aufgelöst, nie aus Request-Parametern.
 */
class Download extends rex_api_function
{
  protected const FIELD_TYPES = ['multi_file_upload', 'mupload'];
  protected const DEFAULT_UPLOAD_DIR = 'addons/massif/uploads_applications/';

  protected $published = false;

  public function execute()
  {
    if (!rex::getUser()) {
      throw new rex_api_exception('Nur mit Backend-Login abrufbar.');
    }

    $tableName = rex_request('table_name', 'string');
    $fieldName = rex_request('field', 'string');
    $dataId = rex_request('data_id', 'int');

    $table = rex_yform_manager_table::get($tableName);
    $field = $table ? $table->getValueField($fieldName) : null;
    if (!$field || !in_array($field->getTypeName(), self::FIELD_TYPES, true)) {
      throw new rex_api_exception('Kein Upload-Feld.');
    }

    $dataset = rex_yform_manager_dataset::get($dataId, $tableName);
    $file = $dataset ? (string) $dataset->getValue($fieldName) : '';
    if ('' === $file) {
      throw new rex_api_exception('Datei nicht gefunden.');
    }

    $uploadDir = $field->getElement('upload_folder') ?: self::DEFAULT_UPLOAD_DIR;
    $basePath = realpath(rex_path::data($uploadDir));
    $path = realpath(rex_path::data($uploadDir . $file));

    if (false === $basePath || false === $path || !str_starts_with($path, $basePath . DIRECTORY_SEPARATOR) || !is_file($path)) {
      throw new rex_api_exception('Datei nicht gefunden.');
    }

    rex_response::cleanOutputBuffers();
    rex_response::sendFile($path, 'application/zip', 'attachment');
    exit;
  }
}
