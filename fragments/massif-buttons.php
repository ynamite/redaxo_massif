<?php

use Ynamite\Massif\Utils;

/** Accepts both button shapes — see Utils\Url::parseButtons(). */
$buttons = Utils\Url::parseButtons((string) $this->getVar('items', ''));

if (count($buttons) > 0) {
?>
  <div class="flex flex-wrap gap-4 mt-8 mb-16 clamp-[text,base,lg]">
    <?php
    foreach ($buttons as $index => $button) {
      $class = $index === 0 ? 'btn-primary' : 'btn-ghost';
      echo '<a href="' . rex_escape($button['url'], 'html_attr') . '" class="' . $class . '" ' . $button['target'] . ' rel="noopener">' . rex_escape($button['label']) . '</a>';
    }
    ?>
  </div>
<?php
}

?>
