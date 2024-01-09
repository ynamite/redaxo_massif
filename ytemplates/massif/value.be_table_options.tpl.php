<?php
$class_group = trim('form-group ' . $this->getHTMLClass() . ' ' . $this->getWarningClass());

$data_index = 0;
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

$linkedId = $this->params['form_name'] . '-' . $this->getElement('link');
?>
<div class="<?= $class_group ?>" id="<?= $this->getHTMLId() ?>">
    <label class="control-label" for="<?php echo $this->getFieldId() ?>"><?php echo $this->getLabel() ?></label>
    <table class="table table-bordered">
        <thead>
        <tr>
            <?php foreach ($columns as $column): ?>
                <th><?php echo htmlspecialchars($column['label']) ?></th>
            <?php endforeach ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($data as $data_index => $row): ?>
            <tr>
                <?php foreach ($columns as $i => $column): ?>
                    
                        <?php
                            $field = $column['field'];
                            echo '<td class="be-value-input"';
                            if($columns[$i]['class']=='special'){
                                echo ' id="'.$field->getName().'-'.$row[$i].'"';
                            }
                            echo '>';
                            $field->params['this']->setObjectparams('form_name', $this->getId() .'.'. $i);
                            $field->params['form_name'] = $field->getName();
                            $field->params['form_label_type'] = 'html';
                            $field->params['send'] = false;
                            $field->setValue($row[$i] ?: '');
                            $field->setId($data_index);
                            $field->enterObject();
                            echo $field->params['form_output'][$field->getId()]
                        ?>
                    </td>
                <?php endforeach ?>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    <div class="d-none" id="<?= $this->getHTMLId() ?>-add-row"></div>
    <script type="text/javascript">
        (function () {
            var wrapper = jQuery('#<?php echo $this->getHTMLId() ?>'),
            	be_table_cnt = <?= (int) $data_index ?>;
            jQuery('#<?= $this->getHTMLId() ?>-add-row').on('click', function () {
                var tr = $('<tr/>'),
                    regexp = [
                        // REX_MEDIA
                        new RegExp("(REX_MEDIA_)", 'g'),
                        new RegExp("(openREXMedia\\()", 'g'),
                        new RegExp("(addREXMedia\\()", 'g'),
                        new RegExp("(deleteREXMedia\\()", 'g'),
                        new RegExp("(viewREXMedia\\()", 'g'),
                        // REX_MEDIALIST
                        new RegExp("(REX_MEDIALIST_SELECT_)", 'g'),
                        new RegExp("(moveREXMedialist\\()", 'g'),
                        new RegExp("(openREXMedialist\\()", 'g'),
                        new RegExp("(addREXMedialist\\()", 'g'),
                        new RegExp("(deleteREXMedialist\\()", 'g'),
                        new RegExp("(viewREXMedialist\\()", 'g'),
                    ],
                    row_html = '\
                    <?php foreach ($columns as $i => $column): ?>\
                        <?php
                        $field = $columns[$i]['field'];
                        $field->setId('--FIELD_ID--');

                        echo '<td class="be-value-input"';
                        if($columns[$i]['class']=='special'){
                            echo ' id="'.$field->getName().'-'.$field->getId().'"';
                        }
                        echo '>';

                        $field->params['this']->setObjectparams('form_name', $this->getId() . '.' . $i);
                        $field->params['form_name'] = $field->getName();
                        $field->params['form_label_type'] = 'html';
                        $field->params['send'] = false;
                        $field->setValue(null);                            
                        $field->enterObject();
                        echo strtr($field->params['form_output'][$field->getId()], ["\n" => '', "\r" => '', "'" => "\'"]);
                        ?></td>\
                    <?php endforeach ?>\
                ';

                be_table_cnt++;
                // set new row field ids
                row_html = row_html.replace(new RegExp('--FIELD_ID--', 'g'), be_table_cnt);

                for (var i in regexp) {
                    row_html = row_html.replace(regexp[i], '$1'+ be_table_cnt +'<?= $i ?>');
                }
                tr.html(row_html);
                // replace be medialist
                tr.find('select[id^="REX_MEDIALIST_"]').each(function() {
                    var $select = $(this),
                        $input  = $select.parent().children('input:first'),
                        id = $select.prop('id').replace('REX_MEDIALIST_SELECT_', '');

                    $input.prop('id', 'REX_MEDIALIST_'+ id);
                });


                $('#<?= $this->getHTMLId() ?>').find('table tbody').append(tr);
                $(document).trigger('be_table:row-added', [tr]);
                return false;
            });
            //jQuery('#<?= $this->getHTMLId() ?>-add-row').add(wrapper.find('.btn-delete')).css('display','none');
            

           /*wrapper.on('click', '.btn-delete', function () {
                var tr = jQuery(this).closest('tr');
                tr.fadeOut('normal', function () {
                    $(document).trigger('be_table:before-row-remove', [tr]);
                    tr.remove();
                    $(document).trigger('be_table:row-removed');
                })
                return false;
            });*/
            var checkupCheck = true;
            function checkup() {
	            var $options = $('#yform-<?=$linkedId?>').find('option');
	            var $tableRows = $('#<?= $this->getHTMLId() ?> tbody').find('tr');
	            $options.each(function(idx){
		            var $el = $('#<?= $field->getName()?>-'+ $(this).val());
		            if($el.length == 0) {
			            $(document).trigger('rex:YForm_selectData', ['<?=$linkedId?>', $(this).val(), $(this).text(), true]);
			            $el = $('#<?= $field->getName()?>-'+ $(this).val());
	                } else {
	             		$el.find('input').after('<span class="exists">'+$(this).text()+'</span>');
	                }
	            });
	            $tableRows.each(function(idx){
		            if($(this).find('span.exists').length == 0) {
		            	$(this).remove();
		            }
	            });
				checkupCheck = false;
            }
            
            function sortItAll() {

			    var $table = $('#<?= $this->getHTMLId() ?> tbody');
			    var $rows = $table.find('tr');

                var usedVals = {};
                $rows.find("input:text").each(function (idx) {
                    if(usedVals[this.value]) {
                        $rows.eq(idx).remove();
                    } else {
                        usedVals[this.value] = this.value;
                    }
                });	    

			    var $rows = $table.find('tr');
				
				// sort table
                var replaceChars={ "ü":"u", "ö":"o", "ä":"a", "è":"e", "é":"e", "à":"a" };
                var regex = new RegExp( Object.keys(replaceChars).join("|"), "g");                 
			    var store = [];
			    for(var i=0, len=$rows.length; i<len; i++){
			        var $row = $rows.eq(i);
			        store.push([$row.find('.exists').get(0), $row]);
			    }
			    store.sort(function(a,b) {
                    var _a = a[0].innerText.replace(regex,function(match) {return replaceChars[match];});
        			var _b = b[0].innerText.replace(regex,function(match) {return replaceChars[match];});

				    if (_a > _b) return 1;
				    if (_a < _b) return -1;
				    return 0
				});
			    for(var i=0, len=store.length; i<len; i++){
			        $table.append(store[i][1]);
			    }
			    store = null;				
				            
            }
            
            $(document).on('rex:YForm_selectData', function(e, id, data_id, data_name, multiple){
                var openerId = $('#YFORM_DATASETLIST_SELECT_'+id).parents('.form-group').attr('id');
                if(openerId != 'yform-<?=$linkedId?>')
                    return;

                jQuery('#<?= $this->getHTMLId() ?>-add-row').trigger('click');

                var $tableRow = $('#<?= $this->getHTMLId() ?> tbody').find('tr:last');
                $tableRow.find('td:first input').after('<span class="exists">'+data_name+'</span>');
                $tableRow.find('td:first input').val(data_id);
                if(!checkupCheck)
	                sortItAll();
            });

            $(document).on('rex:YForm_deleteData', function(e, id){
                var openerId = $('#YFORM_DATASETLIST_SELECT_'+id).parents('.form-group').attr('id');
               if(openerId != 'yform-<?=$linkedId?>')
                    return;

                var $el = $('#YFORM_DATASETLIST_SELECT_'+id);
                var medialist = 'YFORM_DATASETLIST_SELECT_'+id;
                var needle = new getObj(medialist);
                var source = needle.obj;
                var sourcelength = source.options.length;
               
                for (ii = 0; ii < sourcelength; ii++) {
                    if (source.options[ii].selected) {
                        option = source.options[ii];
                        break;
                    }
                }

                $('#<?= $field->getName()?>-'+ option.value).parent().remove();
                sortItAll();
            });
            checkup();
            sortItAll();

            
        })();
    </script>
    <?php echo $notice ?>
</div>
