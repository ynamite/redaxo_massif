<?php

$urlNs = 'portfolio_tag';
$urlManagerData = rex::getProperty('url-manager-data', []);

$tagsUrlNs = 'portfolio_tag';
$query = rex_yform_manager_table::get('rex_yf_portfolio_tag')->query()->alias('c')->joinRelation('portfolio', 'p');
$collection = $query->find();

$items = $collection->toArray();

$itemsMapped = [0 => [
  'id' => 0,
  'name' => 'Alles',
  'filter' => 'all',
  'url' => rex_getUrl(rex_article::getCurrentId()),
  'active' => !isset($urlManagerData[$urlNs]),
]];
array_walk($items, function ($item) use (&$itemsMapped, $urlNs, $urlManagerData) {
  $itemsMapped[$item->getId()] = [
    'id' => $item->getId(),
    'name' => $item->getValue('name'),
    'filter' => rex_string::normalize($item->getValue('name')),
    'url' => rex_getUrl(null, null, [$urlNs => $item->getId()]),
    'active' => isset($urlManagerData[$urlNs]) && $urlManagerData[$urlNs]['id'] === $item->getId(),
  ];
});

?>
<section class="page-margin section-padding portfolio-filter">
  <div class="portfolio-filter__tags h1 font-black" id="portfolio-filter">
    <?php
    foreach ($itemsMapped as $item) {
    ?>
      <a href="<?= $item['url'] ?>" class="filter-item portfolio-filter__tag<?= $item['active'] ? ' active' : '' ?>" data-filter="<?= $item['filter'] ?>" data-no-swup><?= $item['name'] ?></a>
    <?php
    }
    ?>
  </div>
</section>