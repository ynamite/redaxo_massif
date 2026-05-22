<?php

use Ynamite\Massif\Utils;

$items = $this->getVar('items', '');
$buttons = array_filter(json_decode(html_entity_decode($items, ENT_QUOTES | ENT_HTML5, 'UTF-8'), true)) ?? [];

if (count($buttons) > 0) {
?>
  <div class="flex flex-wrap gap-4 mt-8 mb-16 clamp-[text,base,lg]">
    <?php
    foreach ($buttons as $index => $url) {
      $url = Utils\Url::parseCustomLink($url);
      $class = $index === 0 ? 'btn-primary' : 'btn-ghost';
      echo '<a href="' . $url['customlink_url'] . '" class="' . $class . '" ' . $url['customlink_target'] . ' rel="noopener">' . $url['customlink_text'] . '</a>';
    }
    ?>
  </div>
<?php
}

?>