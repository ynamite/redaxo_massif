<?php

/**
 * @var rex_yform_value_be_table $this
 * @psalm-scope-this rex_yform_value_be_table
 */

$columns = $columns ?? [];
$data = $data ?? [];

$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$data_index = 0;
$notice = [];
if ('' != $this->getElement('notice')) {
    $notice[] = rex_i18n::translate($this->getElement('notice'), false);
}
if (isset($this->params['warning_messages'][$this->getId()]) && !$this->params['hide_field_warning_messages']) {
    $notice[] = '<span class="text-warning">' . rex_i18n::translate($this->params['warning_messages'][$this->getId()], false) . '</span>'; //    var_dump();
}
if (count($notice) > 0) {
    $notice = '<p class="help-block small">' . implode('<br />', $notice) . '</p>';
} else {
    $notice = '';
}

$ytemplates = $this->params['this']->getObjectparams('form_ytemplate');
$main_id = $this->params['this']->getObjectparams('main_id');

?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <table class="table table-hover table-bordered">
        <thead>
            <tr>
                <?php foreach ($columns as $column) : ?>
                    <th class="type-<?= $column['field']->getElement(0) ?>"><?php echo htmlspecialchars($column['label']) ?></th>
                <?php endforeach ?>
                <th class="rex-table-action"><a class="btn btn-xs btn-add btn-primary" id="<?= $this->getHTMLId() ?>-add-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i> <?php echo rex_i18n::msg('yform_add_row') ?></a></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $data_index => $row) : ?>
                <tr>
                    <?php foreach ($columns as $i => $column) : ?>
                        <?php
                        $rowData = array_values($row);
                        $field = $column['field'];
                        $field->params['form_output'] = [];
                        $field->params['this']->setObjectparams('form_name', $this->getName() . '.' . $i);
                        $field->params['this']->setObjectparams('form_ytemplate', $ytemplates);
                        $field->params['this']->setObjectparams('main_id', $main_id);
                        $field->params['form_name'] = $field->getName();
                        $field->params['form_label_type'] = 'html';
                        $field->params['send'] = false;

                        if ('be_manager_relation' == $field->getElement(0)) {
                            $field->params['main_table'] = $field->getElement('table');
                            $field->setName($field->getElement('field'));
                        }
                        $field->setValue($rowData[$i] ?? '');
                        $field->setId($data_index);
                        $field->enterObject();
                        /** @var array $field->params['form_output'] */
                        $field_output = trim($field->params['form_output'][$field->getId()]);
                        ?>
                        <td class="be-value-input type-<?= $column['field']->getElement(0) ?>" data-title="<?= rex_escape($column['label'], 'html_attr') ?>"><?= $field_output ?></td>
                    <?php endforeach ?>
                    <td class="delete-row">
                        <div class="btn-group btn-group-md mb-1">
                            <a class="btn btn-xs btn-dark btn-add" id="<?= $this->getHTMLId() ?>-add-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i></a>
                            <a class="btn btn-popup btn-up btn-xs" href="javascript:void(0)"><i class="rex-icon rex-icon-up"></i></a>
                            <a class="btn btn-popup btn-down btn-xs" href="javascript:void(0);"><i class="rex-icon rex-icon-down"></i></a>
                            <a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i></a>
                        </div>
                    </td>
                </tr>
            <?php endforeach ?>
        </tbody>
    </table>
    <a class="btn btn-primary btn-xs btn-add add-mobile-btn" id="<?= $this->getHTMLId() ?>-add-mobile-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i> <?php echo rex_i18n::msg('yform_add_row') ?></a>
    <?php
    $output = [];
    foreach ($columns as $i => $column) {
        $field = $columns[$i]['field'];
        $field->params['form_output'] = [];
        $field->params['this']->setObjectparams('form_name', $this->getName() . '.' . $i);
        $field->params['this']->setObjectparams('form_ytemplate', $ytemplates);
        $field->params['this']->setObjectparams('main_id', $main_id);
        $field->params['form_name'] = $field->getName();
        $field->params['form_label_type'] = 'html';
        $field->params['send'] = false;

        if ('be_manager_relation' == $field->getElement(0)) {
            $field->params['main_table'] = $field->getElement('table');
            $field->setName($field->getElement('field'));
        }
        $field->setValue(null);
        $field->setId('{{FIELD_ID}}');
        $field->enterObject();
        /** @var array $field->params['form_output'] */
        $field_output = trim(strtr($field->params['form_output'][$field->getId()], ["\n" => '', "\r" => '', "'" => "\'"]));


        $output[] = '<td class="be-value-input type-' . $column['field']->getElement(0) . '" data-title="' . $column['label'] . '">' . $field_output . '</td>';
    }
    $output[] = '<td class="delete-row">';
    $output[] = '<div class="btn-group btn-group-md mb-1">';
    $output[] = '<a class="btn btn-xs btn-secondary btn-add" id="' . $this->getHTMLId() . '-add-row" href="javascript:void(0);"><i class="rex-icon rex-icon-add"></i></a>';
    $output[] = '<a class="btn btn-xs btn-popup btn-up" href="javascript:void(0)"><i class="rex-icon rex-icon-up"></i></a>';
    $output[] = '<a class="btn btn-xs btn-popup btn-down" href="javascript:void(0);"><i class="rex-icon rex-icon-down"></i></a>';
    $output[] = '<a class="btn btn-xs btn-delete" href="javascript:void(0)"><i class="rex-icon rex-icon-delete"></i></a>';
    $output[] = '</div>';
    $output[] = '</td>';
    ?>
    <script type="text/javascript">
        (function() {
            var wrapper = jQuery('#<?php echo $this->getHTMLId() ?>'),
                be_table_cnt = <?= (int) $data_index ?>;

            wrapper.on('click', '.btn-add', function() {
                var $this = $(this),
                    $table = $this.parents('.formbe_table_extended').children('table'),
                    tr = $('<tr/>'),
                    regexp = [

                        new RegExp("(REX_MEDIA_)", 'g'),
                        new RegExp("(openREXMedia\\()", 'g'),
                        new RegExp("(addREXMedia\\()", 'g'),
                        new RegExp("(deleteREXMedia\\()", 'g'),
                        new RegExp("(viewREXMedia\\()", 'g'),

                        new RegExp("(REX_MEDIALIST_SELECT_)", 'g'),
                        new RegExp("(moveREXMedialist\\()", 'g'),
                        new RegExp("(openREXMedialist\\()", 'g'),
                        new RegExp("(addREXMedialist\\()", 'g'),
                        new RegExp("(deleteREXMedialist\\()", 'g'),
                        new RegExp("(viewREXMedialist\\()", 'g'),

                        new RegExp("(REX_LINK_)", 'g'),
                        new RegExp("(deleteREXLink\\()", 'g'),

                        new RegExp("(REX_LINKLIST_SELECT_)", 'g'),
                        new RegExp("(moveREXLinklist\\()", 'g'),
                        new RegExp("(openREXLinklist\\()", 'g'),
                        new RegExp("(deleteREXLinklist\\()", 'g'),

                    ],
                    row_html = '<?= implode('', $output) ?>';
                be_table_cnt++;
                // set new row field ids
                row_html = row_html.replace(new RegExp('{{FIELD_ID}}', 'g'), be_table_cnt);
                row_html = row_html.replace(new RegExp('--FIELD_ID--', 'g'), be_table_cnt);

                for (var i in regexp) {
                    row_html = row_html.replace(regexp[i], '$1' + be_table_cnt + '<?= $i ?>');
                }
                tr.html(row_html);

                // replace be medialist
                tr.find('select[id^="REX_MEDIALIST_"]').each(function() {
                    var $select = $(this),
                        $input = $select.parent().children('input:first'),
                        id = $select.prop('id').replace('REX_MEDIALIST_SELECT_', '');

                    $input.prop('id', 'REX_MEDIALIST_' + id);
                });

                $table.find('tbody').append(tr);
                $(document).trigger('be_table:row-added', [tr]);
                return false;
            });

            wrapper.on('click', '.btn-delete', function() {
                var tr = jQuery(this).closest('tr');
                tr.fadeOut('normal', function() {
                    $(document).trigger('be_table:before-row-remove', [tr]);
                    tr.remove();
                    $(document).trigger('be_table:row-removed');
                })
                return false;
            });

            wrapper.on('click', '.btn-up,.btn-down', function() {
                var $this = $(this);
                var $tr = $(this).closest('tr');
                var $clone;
                if ($this.hasClass('btn-up')) {
                    var $prev = $tr.prev();
                    $tr.insertBefore($prev);
                    $clone = $prev;
                } else {
                    var $next = $tr.next();
                    $tr.insertAfter($tr.next());
                    $clone = $next;
                }
                setTimeout(function() {
                    $tr.find('.mce-tinymce:first').remove();
                    $tr.find('.tinyMCEEditor-massif-red').removeClass('mce-initialized').removeAttr('style');
                    $clone.trigger('rex:change', [$clone]);
                }, 150);

                return false;
            });
        })();
    </script>
    <?php echo $notice ?>
</div>