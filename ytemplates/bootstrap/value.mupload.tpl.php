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
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" width="12" height="12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M1 1l10 10M11 1L1 11" /></svg>
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

    $downloadUrl = rex_url::backendController([
        'rex-api-call' => 'massif_download',
        'table_name' => rex_request('table_name', 'string'),
        'field' => $this->getName(),
        'data_id' => rex_request('data_id', 'int'),
    ]);

    echo '<div class="form-group yform-element" id="' . $this->getHTMLId() . '">
    <label class="control-label" for="' . $this->getFieldId() . '">' . $this->getLabel() . '</label><br />';
    if ($value) {
        echo '<a href="' . $downloadUrl . '" download>' . rex_escape(basename($value)) . '</a>';
    } else {
        echo '–';
    }
    echo '<input type="hidden" name="' . $this->getFieldName() . '" id="' . $this->getFieldId() . '" value="' . rex_escape($value, 'html_attr') . '" />
    </div>';
} ?>