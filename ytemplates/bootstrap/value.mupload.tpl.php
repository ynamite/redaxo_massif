<?php if (!rex::isBackend()) {

    $hasFiles = count($this->files) > 0;

    $warning_class = '';
    if (!empty($this->getWarningClass())) {
        $warning_class = $this->getWarningClass();
    }

    $attributes = [
        'class' => 'form-control',
        'name' => $this->getFieldName(),
        'type' => 'file',
        'id' => $this->getFieldId(),
        'value' => '',
    ];

    $attributes = $this->getAttributeElements($attributes, ['placeholder', 'autocomplete', 'pattern', 'required', 'disabled', 'readonly']);

?>
    <div class="form-group form-group-mupload <?php if ($warning_class) echo ' ' . $warning_class; ?>" id="<?= $this->getHTMLId() ?>">
        <label for="fileupload has-icon"><?= $this->getLabel() ?></label>
        <div data-dropzone-template>
            <div class="file-row">
                <div>
                    <a href="<?= rex_yform_value_mupload::getPreviewUrl() ?>" target="_blank" class="file-name" data-dz-name></a>
                    <strong class="text-danger error" data-dz-errormessage></strong>
                    <div class="fileupload-process">
                        <div class="progress active" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                            <div class="progress-bar" style="width: 0%" role="progressbar" data-dz-uploadprogress></div>
                        </div>
                    </div>
                </div>
                <div class="file-size" data-dz-size>
                </div>
                <div data-dz-remove class="file-delete" title="Datei entfernen">
                    <?= Ynamite\Massif\Utils\Rex::parse('icons/icon', ['icon' => 'plus', 'class' => 'rotate-45', 'width' => 12, 'height' => 12]); ?>
                </div>
            </div>
        </div>
        <div class="dropzone-previews files <?= $hasFiles ? 'has-files' : '' ?>">
            <div>
                <div data-dropzone-previews></div>
                <div class="clickable clickable-area"></div>
            </div>
        </div>
        <div hidden>
            <input type="file" name="files[]" <?= implode(' ', $attributes) ?> multiple hidden inert data-max-file-size="<?= $this->MAX_FILE_SIZE ?>" data-accepted-files="<?= $this->getDropzoneFileTypes() ?>" />
        </div>
        <p class="file-info"><?= $this->getFormattedFileTypes() ?>, max <?= ceil(intval($this->MAX_FILE_SIZE) / 1000 / 1000) ?> MB pro Datei</p>
        <?php

        $out = '<script data-dropzone-files-data type="application/json">[';
        if ($hasFiles) {
            $outArray = [];
            foreach ($this->files as $file) {
                $data = [];
                $data[] = '"name": "' . basename($file) . '"';
                $data[] = '"size": "' . filesize($file) . '"';
                $outArray[] = '{' . implode(',', $data) . '}';
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

    $notice = $this->getElement('notice');

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
    ' . $field_before . '<br /><a ' . implode(' ', $attributes) . '>' . basename($value) . '</a>' .
        '<input ' . implode(' ', $attributesInput) . '/>' .
        $notice . ' – ';
    echo rex_yform_value_mupload::getDeleteLink(rex_request('data_id', 'int'), $value, false) . ' ';
    echo $field_after .
        '</div>';
} ?>