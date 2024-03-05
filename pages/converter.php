<?php

$content = '';
$convert = rex_request('massif_convert', 'int', 0);
if ($convert) {
  $massif_converter = massif_converter::factory();
  if ($massif_converter->convert()) {
    $content = rex_view::success('Konvertierung erfolgreich');
  } else {
    $content = rex_view::error('Konvertierung fehlgeschlagen');
  }
  $content .= $massif_converter->getLogOutput();
}

echo rex_view::title($this->getProperty('page')['title'], 'Untertitel');

$fragment = new rex_fragment();
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');


// $sql = rex_sql::factory();
// $entries = $sql->setQuery('SELECT p.id AS portfolio_id, ra.* FROM rex_article_slice ra JOIN rex_yf_portfolio p ON ra.article_id = p._article_id')->getArray();
// foreach ($entries as $entry) {
//   $portfolio = rex_yform_manager_dataset::get($entry['portfolio_id'], 'rex_yf_portfolio');
//   // images
//   if ($entry['module_id'] == 9) {
//     $portfolio->setValue('images', $entry['medialist1']);
//   }
//   // text
//   if ($entry['module_id'] == 1) {
//     $data = massif_utils::rexVartoArray($entry['value2']);
//     $portfolio->setValue('short_description', strip_tags($data['text']));
//     $portfolio->setValue('long_description', $data['text']);
//   }
//   // main img
//   if ($entry['module_id'] == 22) {
//     $portfolio->setValue('image', $entry['media1']);
//   }
//   // $portfolio->save();
//   // $insert = 'INSERT INTO rex_yf_portfolio SET prio = ' . $entry['priority'] . ', status = ' . $entry['status'] . ', _article_id = ' . $entry['id'] . ', `name` = "' . $entry['name'] . '"';

//   // $sql->setQuery($insert);
// }
// dump($inserts);

// $entries = rex_yform_manager_dataset::query('rex_yf_bb_portfolio')->find();
// foreach ($entries as $entry) {
//   $prio = 1;
//   $id = $entry->getId();
//   $reviews = $entry->getValue('reviews');
//   if ($reviews == '') continue;
//   $dom = new DOMDocument;
//   $dom->loadHTML($reviews);

//   $xPath = new DOMXPath($dom);
//   $nodes = $xPath->query('//a');

//   foreach ($nodes as $node) {
//     $href = $node->getAttribute('href');
//     if (str_starts_with($href, '/files/')) {
//       $href = str_replace('/files/', '', $href);
//       if (!rex_media::get($href)) continue;
//     }
//     $text = $node->nodeValue;
//     $review = rex_yform_manager_dataset::create('rex_yf_bb_portfolio_review');
//     $review->setValue('prio', $prio);
//     $review->setValue('id_portfolio', $id);
//     $review->setValue('name', $text);
//     $review->setValue('url', $href);
//     $review->setValue('type', 2);
//     $review->save();
//     $prio++;
//   }
// }


// $sql = rex_sql::factory();
// $sql->setQuery('SELECT * FROM rex_yf_bb_portfolio');
// $entries = $sql->getArray();
// foreach ($entries as $entry) {
//   $tags = implode(',', array_filter(explode('|', $entry['tags'])));
//   $date = date('Y-m-d', strtotime($entry['datetime_start']));
//   $sql->setQuery("UPDATE rex_yf_bb_portfolio SET tags = '$tags', date_start = '$date' WHERE id = {$entry['id']}");
// }
