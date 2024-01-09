<?php

$notice = [];
if ($this->getElement('notice') != '') {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$class = $this->getElement('required') ? 'form-is-required ' : '';

$class_group = trim('form-group form-group-upload ' . $class . $this->getWarningClass());
$class_control = trim('form-control');

$default = $this->getElement('default');

?>
<div class="<?php echo $class_group ?> file-upload" id="<?php echo $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <div class="file-upload__wrap">
        <label class="file-upload__drop-area" for="<?php echo $this->getFieldId() ?>-inp">
            <span class="file-upload__icon">
                <i class="icon icon-new_l"></i>
            </span>

            <input
                name="<?php echo str_replace('--', '[]', $this->getFieldName()); ?>"
                id="<?php echo $this->getFieldId() ?>-inp"
                type="file"
                multiple="false" 
                data-max-size="5242880" 
                class="<?php echo $class_control ?> file-upload__inp"
                accept="<?php echo $this->getElement("types") ?>"
                aria-label="<?php echo $this->getLabel() ?>"
            />
            <?php echo $notice ?>
            <?php /*<input type="hidden" name="<?php echo $this->getFieldName('unique'); ?>" value="<?php echo rex_escape($unique, 'html'); ?>" />*/?>
            <div class="file-upload__preview"></div>
            <?php if($default['id']) {?>
            <div class="file-upload__inline-preview<?php if($default['format']) echo ' format-'.$default['format'];?>">
                <?=massif_img::get($default['id'], ['type' => $default['type'] ])?>
            </div>
            <?php } ?>
            
        </label>
    </div>
    <div class="btn-set">
        <button type="button" class="button file-upload-delete" data-file-upload-delete hidden>Datei löschen</button>
        <?php if($default) {?>
            <button type="button" class="button file-upload-change" data-file-upload-change>Anderes Bild wählen ...</button>
        <?php } ?>
    </div>
</div>

<?php
/*
$value = $this->getValue();
if ($filename != '') {
    $label = htmlspecialchars($filename);

    if (rex::isBackend() && $download_link != "") {
        $label = '<a href="' . $download_link . '">' . $label . '</a>';
    }

    echo '
        <div class="checkbox" id="' . $this->getHTMLId('checkbox') . '">
            <label>
                <input type="checkbox" id="' .  $this->getFieldId('delete') . '" name="' . $this->getFieldName('delete') . '" value="1" />
                ' . $error_messages['delete_file'] . ' "' . $label . '"
            </label>
        </div>';
}
*/
?>
