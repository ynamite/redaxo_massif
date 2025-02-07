<?php

/** @var rex_yform_value_checkbox $this */

$notices = [];
if ($this->getElement('notice') != '') {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}

$notice = '';
if (count($notices) > 0) {
    $notice = '<p class="help-block">' . implode('<br />', $notices) . '</p>';
}

$class_group = trim('form-group checkbox ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$attributes = [
    'type' => 'checkbox',
    'id' => $this->getFieldId(),
    'name' => $this->getFieldName(),
    'value' => 1,
];
if ($value == 1) {
    $attributes['checked'] = 'checked';
}

$attributes = $this->getAttributeElements($attributes, ['required', 'disabled', 'autofocus']);

?>
<div class="<?= $class_group ?>" id="<?php echo $this->getHTMLId() ?>">
    <label>
        <input <?= implode(' ', $attributes) ?> />
        <i class="form-helper"></i>
        <span><?php echo $this->getLabel() ?></span>
    </label>
    <?php echo $notice; ?>
</div>