<?php

/** @var rex_yform_choice_list $choiceList */
/** @var rex_yform_choice_list_view $choiceListView */

$notices = [];
if ($this->getElement('notice')) {
    $notices[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notices[] = '<span class="text-warning">'.rex_i18n::translate($this->params['warning_messages'][$this->getId()], false).'</span>';
}

if (!isset($groupAttributes)) {
    $groupAttributes = [];
}

$groupClass = 'form-group form-check-group';
if (isset($groupAttributes['class']) && is_array($groupAttributes['class'])) {
    $groupAttributes['class'][] = $groupClass;
} elseif (isset($groupAttributes['class'])) {
    $groupAttributes['class'] .= ' '.$groupClass;
} else {
    $groupAttributes['class'] = $groupClass;
}

if (!isset($elementAttributes)) {
    $elementAttributes = [];
}
$elementClass = trim(($choiceList->isMultiple() ? 'checkbox' : 'radio').' '.$this->getWarningClass());
if (isset($elementAttributes['class']) && is_array($elementAttributes['class'])) {
    $elementAttributes['class'][] = $elementClass;
} elseif (isset($elementAttributes['class'])) {
    $elementAttributes['class'] .= ' '.$elementClass;
} else {
    $elementAttributes['class'] = $elementClass;
}

$customInputType = false;

if(isset($groupAttributes['data-custom-radios'])) {
    $customInputType = 'custom-radios';
    $groupAttributes['class'].= ' form-group-custom-radios';    
}

if(isset($groupAttributes['data-custom-color-radios'])) {
    $customInputType = 'custom-color';
    $groupAttributes['class'].= ' form-group-colors';
}
?>

<?php $choiceOutput = function (rex_yform_choice_view $view) use ($elementAttributes, $customInputType) {
    if($customInputType == 'custom-color') $elementAttributes['class'].= ' user-bg-color-'.rex_escape($view->getValue());
    ?>
    <div<?= rex_string::buildAttributes($elementAttributes) ?> >
        <label<?=($customInputType == 'custom-radios') ? ' class="button"' : ''?>>
            <input
                value="<?= rex_escape($view->getValue()) ?>"
                <?= (in_array($view->getValue(), $this->getValue(), true) ? ' checked="checked"' : '') ?>
                <?= $view->getAttributesAsString() ?>
                <?=($customInputType == 'custom-radios') ? ' hidden' : ''?>
            />
            <i class="form-helper<?=($customInputType == 'custom-radios') ? ' button active' : ''?>"><i></i></i>
            <?php if($customInputType != 'custom-color') echo $view->getLabel() ?>
        </label>
    </div>
<?php
} ?>

<?php $choiceGroupOutput = function (rex_yform_choice_group_view $view) use ($choiceOutput) {
        ?>
    <div class="form-check-group">
        <label><?= rex_escape($view->getLabel()) ?></label>
        <?php foreach ($view->getChoices() as $choiceView): ?>
            <?php $choiceOutput($choiceView) ?>
        <?php endforeach ?>
    </div>
<?php
    } ?>

<?php 
    if (!isset($groupAttributes['id'])) {
        $groupAttributes['id'] = $this->getHTMLId();
    }
 ?>

<div<?= rex_string::buildAttributes($groupAttributes) ?>>
    <?php if ($this->getLabel()): ?>
        <label class="control-label" for="<?= $this->getFieldId() ?>">
            <?= rex_escape($this->getLabelStyle($this->getLabel())) ?>
        </label>
    <?php endif ?>

    <div class="choices">
        <?php foreach ($choiceListView->getPreferredChoices() as $view): ?>
            <?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
        <?php endforeach ?>

        <?php foreach ($choiceListView->getChoices() as $view): ?>
            <?php $view instanceof rex_yform_choice_group_view ? $choiceGroupOutput($view) : $choiceOutput($view) ?>
        <?php endforeach ?>
    </div>

    <?php if ($notices): ?>
        <p class="help-block"><?= implode('<br />', $notices) ?></p>
    <?php endif ?>
</div>
