<?php

/**
 * yform.
 *
 * @author studio[at]massif[dot]ch Yves Torres
 * @author <a href="https://massif.ch">massif.ch</a>
 */

class rex_yform_value_mupload extends rex_yform_value_abstract
{
    public function enterObject()
    {
        $hasWarnings = count($this->params['warning']) != 0;
        $hasWarningMessages = count($this->params['warning_messages']) != 0;
        if (!rex::isBackend() && $this->params['send'] == "1" && !$hasWarnings && !$hasWarningMessages) {
            $this->setValue(self::handleUploads());
        }

        //$this->setValue($this->getValue());

        $deleteId = rex_request('mupload_delete', 'int');
        if ((int) $deleteId) {
            $this->setValue(self::deleteFile($deleteId));
        }

        if ($this->needsOutput()) {
            $this->params['form_output'][$this->getId()] = $this->parse('value.mupload.tpl.php');
        }

        $this->params['value_pool']['email'][$this->getName()] = $this->getValue();
        if ($this->getElement('no_db') != 'no_db') {
            $this->params['value_pool']['sql'][$this->getName()] = $this->getValue();
        }


        return $this;
    }

    public static function deleteFile($id)
    {
        $table = rex_request('table_name', 'string', '');
        if ($id && $table) {
            $sql = rex_sql::factory();
            $sql->setTable($table);
            $sql->setWhere(['id' => $id]);
            $sql->select();
            if ($sql->getValue('mupload')) {
                $parsedUrl = parse_url($sql->getValue('mupload'));
                $path = dirname(rex_path::frontend(ltrim($parsedUrl['path'], "/")));
                $file = rex_path::basename($sql->getValue('mupload'));
                if ($path != '' && $path != '/' && $path != dirname(rex_path::frontend())) {

                    rex_dir::delete($path);

                    $sql->setTable($table);
                    $sql->setWhere(['id' => $id]);
                    $sql->setValue('mupload', '');
                    $sql->update();

                    return '';
                }
            }
        }
    }

    protected static function getDeleteLink($id, $value, $list = true)
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
        $path = rex_path::media('tmp_uploads/' . session_id() . '/');
        $files = [];
        $val = '';
        if (is_dir($path)) {
            $_files = scandir($path);
            foreach ($_files as $file) {
                if ($file != '.' && $file != '..') {
                    $files[] = $path . $file;
                }
            }
            if (count($files) > 0) {
                $val = self::zipUploads($files, $path);
            }
        }
        return $val;
    }

    public static function getUserFolder()
    {
        return sha1(session_id() . rex::getProperty('instname')) . '/';
    }

    protected function zipUploads($files, $tmpPath)
    {
        $zip = new ZipArchive();
        $newPath = '_uploads_/' . sha1(time() . session_id() . rex::getProperty('instname')) . '/';
        $url = rtrim(rex::getServer(), '/') . rex_url::media($newPath);
        $path = rex_path::media($newPath);
        if (!is_dir($path)) {
            rex_dir::create($path);
        }
        $filename = 'unterlagen-' . date('Y-m-d_His') . '.zip';
        $zip->open($path . $filename, ZipArchive::CREATE);
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();
        rex_dir::deleteFiles($tmpPath);
        rex_dir::delete($tmpPath);
        /*
        foreach ($files as $file) {
            rex_file::delete($file);
        }
        */
        return $url . $filename;
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
                'name' => ['type' => 'name',      'label' => rex_i18n::msg('yform_values_defaults_name')],
                'label' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_defaults_label')]/*,
                'sizes' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_sizes')],
                'types' => ['type' => 'text',    'label' => rex_i18n::msg('yform_values_upload_types')],
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
        $value = basename($params['subject']);

        $deleteId = rex_request('mupload_delete', 'int');
        if ((int) $deleteId) {
            $value = self::deleteFile($deleteId);
        }

        $length = strlen($value);
        $title = $value;
        if ($length > 40) {
            $value = mb_substr($value, 0, 20) . ' ... ' . mb_substr($value, -20);
        }
        return '<a href="' . $params['subject'] . '" target="_blank" title="' . rex_escape($title) . '">' . rex_escape($value) . '</a><br />' . self::getDeleteLink($params['list']->getValue('id'), $value);
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


rex_extension::register('YFORM_DATA_DELETE', function (rex_extension_point $ep) {

    $params = $ep->getParams();
    rex_yform_value_mupload::deleteFile($params['data_id']);
});
