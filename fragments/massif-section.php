<?php
$category = $this->getVar('category', null);
$ctx = $this->getVar('ctx', null);

if (!$category) {
  $pathRoute = massif_utils::getPathRoute();
  if (count($pathRoute) < 2) {
    $category = rex_category::getCurrent();
    if (!$category) {
      $category = rex_article::getCurrent();
    }
  } else if ($ctx) {
    echo $ctx->getArticle();
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