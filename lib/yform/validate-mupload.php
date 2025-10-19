<?php

/**
 * yform
 * @author studio[at]massif[dot]ch Yves Torres
 * @author <a href="http://massif.ch">massif.ch</a>
 */

class rex_yform_validate_mupload extends rex_yform_validate_abstract
{

    function enterObject()
    {
        if ($this->params['send'] == '1') {

            $tmpDir = rex_config::get('yform', 'mupload_tmp_folder', '');

            $Object = $this->getValueObject();

            $error = false;
            $files = [];
            $path = rex_path::data($tmpDir . session_id() . '/');
            if (is_dir($path)) {
                $_files = scandir($path);
                foreach ($_files as $file) {
                    if ($file != '.' && $file != '..') {
                        $files[] = $path . $file;
                    }
                }
            }

            if (count($files) == 0) {
                $error = true;
            }

            if ($error) {
                $this->params['warning'][$Object->getId()] = $this->params['error_class'];
                $this->params['warning_messages'][$Object->getId()] = $this->getElement('message');
            }
        }
    }

    function getDescription(): string
    {
        return 'validate|mupload|name|warning_message';
    }

    function getDefinitions(): array
    {
        return array(
            'type' => 'validate',
            'name' => 'mupload',
            'values' => array(
                'name' => array('type' => 'select_name', 'label' => rex_i18n::msg("yform_validate_mupload_name")),
                'message' => ['type' => 'text', 'label' => rex_i18n::msg('yform_validate_mupload_message')]

            ),
            'description' => rex_i18n::msg("yform_validate_mupload_description"),
            'multi_edit' => false,
        );
    }
}
