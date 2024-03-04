<?php
$category = $this->getVar('category', null);

if (!$category) {
  $pathRoute = massif_utils::getPathRoute();
  if (count($pathRoute) < 2) {
    $category = rex_category::get($this->getValue('category_id'));
    if (!$category) {
      $category = rex_article::get($this->getValue('article_id'));
    }
  } else {
    echo $this->getArticle();
  }
}
if ($category) {

  $article_helper = rex_article::get($category->getId(), 1);
  $content = (new rex_article_content($category->getId()))->getArticle(1);

  $name = rex_string::normalize($article_helper->getName());
  $isHome = ($category->getId() == rex_article::getSiteStartArticleId()) ? true : false;
?>
  <div data-section id="sec-<?= $name ?>" class="section section-<?= $name ?>">
    <?= $content ?>
  </div>
<?php } ?>