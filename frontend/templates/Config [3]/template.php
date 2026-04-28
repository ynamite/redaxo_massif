<?php

use Url\Url;
use Ynamite\Massif\Utils;

header('Content-Type: text/html; charset=utf-8');

/*
	*	config
	*/

// get current language and locale
$lang = rex_clang::getCurrent();
$langLocale = $lang->getValue('locale');

$isMobileOrTablet = 'browser-is-' . rex_string::normalize(useragent::getBrowser());

if (useragent::isTablet()) {
	$isMobileOrTablet .= ' is-tablet';
} else if (useragent::isMobile()) {
	$isMobileOrTablet .= ' is-mobile';
}
if (useragent::isBrowserEdge()) {
	$isMobileOrTablet .= ' is-edge';
}
if (useragent::isBrowserInternetExplorer()) {
	$isMobileOrTablet .= ' is-ie';
}

// get current path route (category-id depth)
$pathRoute = Utils\Article::getPathRoute();
$pageClass = 'page-id-' . rex_article::getCurrentId();
$pageClass .= ' page-pid-' . $pathRoute[0];

$currentArticle = rex_article::getCurrent();
$tmplId = $currentArticle->getTemplateId();
$pageClass .= ' tmpl-id-' . $tmplId;

$manager = Url::resolveCurrent();
if ($manager) {
	$urlManagerData = [];
	if ($profile = $manager->getProfile()) {
		$ns = $profile->getNamespace();
		$urlManagerData[$ns] = [];
		$urlManagerData[$ns]['url'] = $manager->getUrl()->getPath();
		$urlManagerData[$ns]['id'] = $manager->getDatasetId();
		$urlManagerData[$ns]['ns-id'] = $profile->getId();
		$urlManagerData[$ns]['ns'] = $profile->getNamespace();
		$urlManagerData[$ns]['table-name'] = $profile->getTableName();
		$pageClass .= ' url-manager-page url-profile-' . $profile->getNamespace();
		if ($manager->isUserPath()) {
			$segments = $manager->getUrl()->getSegments();
			foreach ($profile->getUserPaths() as $value => $label) {
				if (in_array($value, $segments)) {
					$urlManagerData[$ns]['user-path'] = $label;
				}
			}
		}
	}
	rex::setProperty('url-manager-data', $urlManagerData);
}
$pageClass .= (Utils\Article::isStartArticle()) ? ' home-page' : ' content-page';
