<?php
$align = $this->getVar('align');
$buttonSet = $this->getVar('buttonSet');
?>

<div class="flex gap-8 <?= $align ?>">
  <?php foreach ($buttonSet as $button) {
    if ($button['style'] == 'simple') {
  ?>
      <a href="<?= $button['url'] ?>" <?= $button['target'] ?> class="button rounded-full border text-sm border-primary uppercase tracking-widest px-6 py-3">
        <?= $button['label'] ?>
      </a>
    <?php
    } else {
    ?>
      <a href="<?= $button['url'] ?>" <?= $button['target'] ?> class="button text-sm bg-accent text-white font-medium uppercase py-2 px-4 rounded-[0.1875rem] transition hover:bg-accent-light <?= implode(' ', $button['class']) ?>"><?= $button['label'] ?></a>
  <?php
    }
  }
  ?>
</div>