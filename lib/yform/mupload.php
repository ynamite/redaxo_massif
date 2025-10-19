<?php

/**
 * yform.
 *
 * @author studio[at]massif[dot]ch Yves Torres
 * @author <a href="https://massif.ch">massif.ch</a>
 */

class rex_yform_value_mupload extends rex_yform_value_abstract
{
  protected $TMP_DIR = "addons/massif/tmp_uploads/";
  protected $UPLOAD_DIR = "addons/massif/uploads_applications/";
  protected $FILE_TYPES = 'pdf|doc|docx|jpg|jpeg|png';
  protected float $MAX_FILE_SIZE = 15e+6;
  protected float $MIN_FILE_SIZE = 1;
  protected array $files = [];

  public function getDropzoneFileTypes()
  {
    return '.' . str_replace('|', ',.', $this->FILE_TYPES);
  }

  public function getFormattedFileTypes()
  {
    $human_readable = $this->getElement('file_types_human_readable');
    return $human_readable ? $human_readable : str_replace('|', ', ', strtoupper($this->FILE_TYPES));
  }

  public static function getUserFolder()
  {
    return sha1(session_id() . rex::getProperty('instname')) . '/';
  }

  protected function getUserTempFilePath($file = '')
  {
    return rex_path::data($this->TMP_DIR . session_id() . '/' . $file);
  }

  protected function getUploadFilePath($file = '')
  {
    return rex_path::data($this->UPLOAD_DIR . $file);
  }


  public static function getApiUrl()
  {
    return rex_url::base('index.php') . '?rex-api-call=upload_files';
  }

  protected function updateConfig()
  {
    $configKeys = [
      'tmp_folder' => $this->TMP_DIR,
      'upload_folder' => $this->UPLOAD_DIR,
      'file_types' => '/\.(' . $this->FILE_TYPES . ')$/i',
      'max_file_size' => $this->MAX_FILE_SIZE,
      'min_file_size' => $this->MIN_FILE_SIZE
    ];
    foreach ($configKeys as $key => $value) {
      $savedValue = rex_config::get('yform', 'mupload_' . $key, null);
      if ($savedValue != $value) {
        rex_config::set('yform', 'mupload_' . $key, $value);
      }
    }
  }

  public function init()
  {

    $this->TMP_DIR = $this->getElement('tmp_folder') ?: $this->TMP_DIR;
    $this->UPLOAD_DIR = $this->getElement('upload_folder') ?: $this->UPLOAD_DIR;
    $this->FILE_TYPES = $this->getElement('file_types') ?: $this->FILE_TYPES;
    $this->MAX_FILE_SIZE = floatval($this->getElement('max_file_size')) ?: $this->MAX_FILE_SIZE;
    $this->MIN_FILE_SIZE = floatval($this->getElement('min_file_size')) ?: $this->MIN_FILE_SIZE;

    $this->cleanFiles(rex_path::data($this->TMP_DIR));
    $this->cleanFiles(rex_path::data($this->UPLOAD_DIR));

    $this->files = $this->getTempFiles();

    $this->updateConfig();
  }

  public function getTempFiles(): array
  {
    $path = $this->getUserTempFilePath();
    $files = [];
    if (is_dir($path)) {
      $_files = scandir($path);
      foreach ($_files as $file) {
        if ($file != '.' && $file != '..') {
          $files[] = $path . $file;
        }
      }
    }
    return $files;
  }

  public static function getPreviewUrl($file = '')
  {
    return self::getApiUrl() . '&preview_upload=1&file=' . $file;
  }

  public static function getDownloadUrl($file = '')
  {
    return self::getApiUrl() . '&download_application=1&file=' . $file;
  }

  public function getOption(string $key, $default = null)
  {
    if ($this->{$key} !== null) {
      return $this->{$key};
    }
    return $default;
  }

  public function enterObject()
  {

    $hasWarnings = count($this->params['warning']) != 0;
    $hasWarningMessages = count($this->params['warning_messages']) != 0;

    if (!rex::isBackend() && $this->params['send'] == "1" && !$hasWarnings && !$hasWarningMessages) {
      $this->setValue(self::handleUploads());

      $this->params['value_pool']['email']['attachments'][$this->getName()]['name'] = basename($this->getValue() ?? '');
      $this->params['value_pool']['email']['attachments'][$this->getName()]['path'] = $this->getUploadFilePath($this->getValue());
    }

    //$this->setValue($this->getValue());

    $deleteId = rex_request('mupload_delete', 'int');
    if ((int) $deleteId) {
      $this->setValue(self::deleteFile($deleteId));
    }

    if ($this->needsOutput()) {
      $this->params['form_output'][$this->getId()] = $this->parse('value.mupload.tpl.php');
    }

    $this->params['value_pool']['email'][$this->getName()] = basename($this->getValue() ?? '');
    if ($this->getElement('no_db') != 'no_db') {
      $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
    }

    return $this;
  }


  private function cleanFiles($path, $force = false)
  {

    $files = glob($path . "*");

    $now   = time();
    $days  = 1;
    $timeToKeep = 60 * 60 * 24 * $days;

    foreach ($files as $file) {
      if ($now - filemtime($file) >= $timeToKeep || $force) {
        if (!is_dir($file)) {
          rex_file::delete($file);
        } else {
          // delete dir if empty
          $innerFiles = glob($file . "/*");
          if (count($innerFiles) == 0) {
            rex_dir::delete($file);
          }
        }
      }
      if (is_dir($file)) {
        // delete dir if empty
        $innerFiles = glob($file . "/*");
        if (count($innerFiles) == 0) {
          rex_dir::delete($file);
        }
      }
    }
  }

  public static function deleteFile($id)
  {
    $table = rex_request('table_name', 'string', '');
    if ($id && $table) {
      $sql = rex_sql::factory();
      $sql->setTable($table);
      $sql->setWhere(['id' => $id]);
      $sql->select();
      if ($sql->getValue('attachment')) {
        $file = $sql->getValue('attachment');

        rex_file::delete(rex_path::data("uploads_applications/" . $file));

        $sql->setTable($table);
        $sql->setWhere(['id' => $id]);
        $sql->setValue('attachment', '');
        $sql->update();

        return '';
      }
    }
  }

  public static function getDeleteLink($id, $value, $list = true)
  {
    if ($value) {
      $_params = [
        'page' => rex_request('page', 'string'),
        'table_name' => rex_request('table_name', 'string'),
        'rex_yform_manager_popup' => rex_request('rex_yform_manager_popup', 'int'),
        'data_id' => $id,
        'func' => (!$list) ? rex_request('func', 'string') : '',
        'start' => rex_request('start', 'string'),
        'sort' => rex_request('sort', 'string'),
        'sorttype' => rex_request('sorttype', 'string'),
        'mupload_delete' => $id,
        'list' => rex_request('list', 'string')
        //index.php?page=yform/manager/data_edit&table_name=rex_quick_bewerbungen&rex_yform_manager_popup=0&data_id=48&func=edit&start=&sort=&sorttype=&list=6a6cabf3667c632353823aee3a7288ca
      ];
      return '<a href="' . rex_url::backendController($_params) . '" onclick="return confirm(\' Datei löschen?\')"><i class="rex-icon fa-remove"></i> Löschen</a>';
    }
  }

  protected function handleUploads()
  {
    $files = $this->getTempFiles();
    $zip = '';
    if (count($files) > 0) {
      $zip = self::zipUploads($files);
    }
    return $zip;
  }

  protected function zipUploads($files)
  {
    $zip = new ZipArchive();
    $path = rex_path::data($this->UPLOAD_DIR . self::getUserFolder());
    if (!is_dir($path)) {
      rex_dir::create($path);
    }
    $filename = 'bewerbungsunterlagen-' . date('Y-m-d_His') . '.zip';
    $zip->open($path . $filename, ZipArchive::CREATE);
    foreach ($files as $file) {
      $zip->addFile($file, basename($file));
    }
    $zip->close();
    $this->cleanFiles($this->getUserTempFilePath(), true);
    $this->cleanFiles($this->TMP_DIR);
    return self::getUserFolder() . $filename;
  }

  public function getDescription(): string
  {
    return 'mupload|name';
  }

  public function getDefinitions(): array
  {
    return [
      'type' => 'value',
      'name' => 'mupload',
      'values' => [
        'name' => ['type' => 'text',      'label' => 'Feldname'],
        'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')],
        'file_types' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_types')],
        'file_types_human_readable' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_types')],
        'max_file_size' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_sizes')],
        'min_file_size' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_sizes')],
        'tmp_folder' => ['type' => 'text',      'label' => 'Temporäres Verzeichnis'],
        'upload_folder' => ['type' => 'text',      'label' => 'Upload Verzeichnis'],
        /*,
                'required' => ['type' => 'boolean', 'label' => rex_i18n::msg('yform_values_upload_required')],
                'messages' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_messages')],
                'notice' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_notice')],*/
      ],
      'description' => rex_i18n::msg('yform_values_upload_description'),
      'dbtype' => 'text',
      'multi_edit' => true,
    ];
  }

  public static function getListValue($params)
  {

    $table_name = $params['params']['field']['table_name'];
    $value = $params['subject'];


    $deleteId = rex_request('mupload_delete', 'int');
    if ((int) $deleteId) {
      $value = self::deleteFile($deleteId);
    }

    $title = $value;
    $length = strlen($title);
    if ($length > 40) {
      $title = mb_substr($title, 0, 20) . ' ... ' . mb_substr($title, -20);
    }
    return '<a href="' . rex_yform_value_mupload::getDownloadUrl($value, ['table_name' => $table_name]) . '" download title="' . rex_escape($value) . '">' . rex_escape($title) . '</a><br />' . self::getDeleteLink($params['list']->getValue('id'), $value);
  }

  public static function getSearchField($params)
  {
    $params['searchForm']->setValueField('text', ['name' => $params['field']->getName(), 'label' => $params['field']->getLabel()]);
  }

  public static function getSearchFilter($params)
  {
    $sql = rex_sql::factory();
    $value = $params['value'];
    $field = $params['field']->getName();

    if ($value == '(empty)') {
      return ' (' . $sql->escapeIdentifier($field) . ' = "" or ' . $sql->escapeIdentifier($field) . ' IS NULL) ';
    }
    if ($value == '!(empty)') {
      return ' (' . $sql->escapeIdentifier($field) . ' <> "" and ' . $sql->escapeIdentifier($field) . ' IS NOT NULL) ';
    }

    $pos = strpos($value, '*');
    if ($pos !== false) {
      $value = str_replace('%', '\%', $value);
      $value = str_replace('*', '%', $value);
      return $sql->escapeIdentifier($field) . ' LIKE ' . $sql->escape($value);
    }
    return $sql->escapeIdentifier($field) . ' = ' . $sql->escape($value);
  }
}
