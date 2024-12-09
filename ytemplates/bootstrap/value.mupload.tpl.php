<?php if (!rex::isBackend()) {

    $path = $this->getTempFilePath();

    $files = [];
    if (is_dir($path)) {
        $files = scandir($path);
    }

    $warning_class = '';
    if (!empty($this->getWarningClass())) {
        $warning_class = $this->getWarningClass();
    }
    $script_url = rex_yform_value_mupload::getScriptUrl($this->getElement('script_url_params'));

    $attributes = [
        'class' => 'form-control',
        'name' => $this->getFieldName(),
        'type' => 'file',
        'id' => $this->getFieldId(),
        'value' => '',
    ];

    $attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

?>
    <div class="form-group formupload formlabel-upload<?php if ($warning_class) echo ' ' . $warning_class; ?>" id="<?= $this->getHTMLId() ?>">
        <label for="fileupload" class="has-icon"><i class=" icon icon-arrow-right"></i><span><?= $this->getLabel() ?></span></label>
        <?php /*<div id="files-header" class="files-header" style="display: none">
            <span>Datei</span>
            <span class="filesize">Grösse</span>
            <span class="delete"></span>
        </div>
        
        <!-- The global file processing state -->
        <div class="fileupload-process">
            <div id="total-progress" class="progress active" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                <div class="progress-bar" role="progressbar" style="width: 0%" data-dz-uploadprogress></div>
            </div>
        </div>
        */ ?>
        <div id="template" data-url="<?= $script_url ?>&mupload" data-max-file-size="<?= $this->MAX_FILE_SIZE ?>" data-accepted-files="<?= $this->FILE_TYPES_DZ ?>">
            <div class="file-row">
                <div>
                    <a href="<?= rex_yform_value_mupload::getPreviewUrl('', $this->getElement('script_url_params')) ?>" target="_blank" class="name" data-dz-name></a>
                    <strong class="error text-danger" data-dz-errormessage></strong>
                    <div class="fileupload-process">
                        <div class=" progress active" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                            <div class="progress-bar" style="width: 0%" role="progressbar" data-dz-uploadprogress></div>
                        </div>
                    </div>
                </div>
                <div>
                    <p class="size" data-dz-size></p>
                </div>
                <div>
                    <div data-dz-remove class="btn-delete has-icon">
                        <i class="icon">
                            <?php require(theme_path::assets('img/ico-close.svg')) ?>
                        </i>
                    </div>
                </div>
            </div>
        </div>
        <div id="previews" class="files"></div>
        <?php
        /*<div class="button btn-secondary fileinput-button">
            <span>Dateien hinzufügen &hellip;</span>
        </div>
        */ ?>
        <div hidden>
            <input type="file" name="files[]" <?= implode(' ', $attributes) ?> multiple hidden inert />
        </div>

        <p><i class="info">pdf, doc, docx, jpg, jpeg, png / max. <?= ceil(intval($this->MAX_FILE_SIZE / 1000 / 1000)) ?> pro Datei<br />Drag & Drop - Dateien einfach auf diese Seite ziehen</i></p>
        <?php

        $out = '<script id="files-data" type="application/json">[';
        if (count($files) > 0) {
            $outArray = [];
            foreach ($files as $file) {
                if ($file != '.' && $file != '..') {
                    $data = [];
                    $data[] = '"name": "' . $file . '"';
                    $data[] = '"size": "' . filesize($path . $file) . '"';
                    $outArray[] = '{' . implode(',', $data) . '}';
                }
            }
            $out .= implode(',', $outArray);
        }
        $out .= ']</script>';
        echo $out;
        ?>

    </div>

<?php } else {
    if (!isset($value)) {
        $value = $this->getValue();
    }
    $class_group = 'form-group yform-element';

    $class_label[] = 'control-label';
    $field_before = '';
    $field_after = '';

    if (trim($this->getElement('grid')) != '') {
        $grid = explode(',', trim($this->getElement('grid')));

        if (isset($grid[0]) && $grid[0] != '') {
            $class_label[] = trim($grid[0]);
        }

        if (isset($grid[1]) && $grid[1] != '') {
            $field_before = '<div class="' . trim($grid[1]) . '">';
            $field_after = '</div>';
        }
    }

    $attributes = [
        'href' => rex_yform_value_mupload::getDownloadUrl($value),
        'download' => 'true',
        'id' => $this->getFieldId()
    ];


    $attributesInput = [
        'name' => $this->getFieldName(),
        'type' => 'hidden',
        'id' => $this->getFieldId(),
        'value' => $value
    ];

    $attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);
    $attributesInput = $this->getAttributeElements($attributesInput);
    //'onclick', 'return confirm(\' [###type_id###, ###type_name###, ###name###] ' . rex_i18n::msg('yform_delete') . ' ?\')');

    echo '<div class="' . $class_group . '" id="' . $this->getHTMLId() . '">
    <label class="' . implode(' ', $class_label) . '" for="' . $this->getFieldId() . '">' . $this->getLabel() . '</label>
    ' . $field_before . '<br />test<a ' . implode(' ', $attributes) . '>' . $value . '</a>' .
        '<input ' . implode(' ', $attributesInput) . '/>' .
        $notice . ' – ';
    echo rex_yform_value_mupload::getDeleteLink(rex_request('data_id', 'int'), $value, false) . ' ';
    echo $field_after .
        '</div>';
} ?>