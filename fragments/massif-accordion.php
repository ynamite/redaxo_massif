<?php
$accordion = $this->params['content'] ? $this->params['content'] : [];
$id = $this->params['id'];
$initial = isset($this->params['initial']) ? $this->params['initial'] : null;
if (count($accordion) > 0) echo '<div class="accordion">';
foreach ($accordion as $key => $content) {
  $itemID = 'item-' . $id . '-' . $key;
?>
  <div class="accordion-item">
    <h2 class="h3 accordion-header" data-expand="<?= $itemID ?>" <?php if ($initial === $key) echo 'data-expand-initial'; ?>>
      <span><?= $content['label'] ?></span><i class="icon far fa-chevron-down" <?php if (rex::isBackend()) echo ' hidden'; ?>></i>
    </h2>
    <div class="accordion-body" data-expand-id="<?= $itemID ?>" hidden="true">
      <div class="accordion-body__inner">
        <?= hyphenator::hyphenate(str_replace(['/{{'], ['{{'], $content['body'])) ?>
      </div>
    </div>
  </div>
<?php }
if (count($accordion) > 0) echo '</div>'; ?>