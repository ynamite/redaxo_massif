<?php



class massif_seo
{

	public static function getTags()
	{


		$seo = new \Url\Seo();
		$manager = \Url\Url::resolveCurrent();
		//$rewriter = new \Url\Rewriter\Yrewrite();

		$image = 'fb-share.png';
		$imgSize = @getimagesize(rex_path::frontend() . $image);
		$logo = rex_yrewrite::getFullPath() . $image;
		$rewriter_seo = new rex_massif_yrewrite_seo();


		$tags = [];

		if (!$manager) {
			$article_img = rex_article::getCurrent()->getValue('image');
			if ($article_img) {
				$media = rex_media::get($article_img);
				if ($media) {
					$image = 'media/share/' . $article_img;
					$imgSize = @getimagesize(rex_path::frontend() . 'media/' . $article_img);
				}
			}
			$image = rex_yrewrite::getFullPath() . $image;
			$hasImage = file_exists($image);

			$tags['main'] = $rewriter_seo->getTags();
			// $tags['title'] = $rewriter_seo->getTitleTag();
			// $tags['og:title'] = '<meta property="og:title" content="' . self::normalize($rewriter_seo->getTitle()) . '" />';
			// $tags['twitter:title'] = '<meta name="twitter:title" content="' . self::normalize($rewriter_seo->getTitle()) . '" />';
			// $tags['description'] = $rewriter_seo->getDescriptionTag();
			$description = self::normalize($rewriter_seo->getDescription());
			$tags['og:description'] = '<meta property="og:description" content="' . $description . '" />';
			$tags['twitter:description'] = '<meta name="twitter:description" content="' . $description . '" />';
			// $tags['robots'] = $rewriter_seo->getRobotsTag();
			// $tags['canonical'] = $rewriter_seo->getCanonicalUrlTag();
			$tags['og:url'] = '<meta property="og:url" content="' . self::normalize($rewriter_seo->getCanonicalUrl()) . '" />';
			$tags['twitter:url'] = '<meta name="twitter:url" content="' . self::normalize($rewriter_seo->getCanonicalUrl()) . '" />';
			$tags['twitter:card'] = '<meta name="twitter:card" content="summary" />';
			$tags['twitter:card'] = '<meta name="twitter:card" content="summary_large_image" />';
			if ($hasImage) {
				$tags['image'] = '<meta name="image" content="' . $image . '" />';
				$tags['og:image'] = '<meta property="og:image" content="' . $image . '" />';
				$tags['twitter:image'] = '<meta name="twitter:image" content="' . $image . '" />';
				$tags['og:image:width'] = '<meta property="og:image:width" content="' . $imgSize[0] . '" />';
				$tags['og:image:height'] = '<meta property="og:image:height" content="' . $imgSize[1] . '" />';
			}
			// if (count(rex_clang::getAll()) > 1) {
			// 	$tags['hreflang'] = $rewriter_seo->getHreflangTags();
			// }
			$tagsHtml = implode("\n", $tags);
		} else {

			\rex_extension::register('URL_SEO_TAGS', function (\rex_extension_point $ep) use ($manager) {
				$tags = $ep->getSubject();

				$titleValues = [];
				$article = rex_article::get($manager->getArticleId());
				$title = strip_tags($tags['title']);

				if ($manager->getSeoTitle()) {
					$titleValues[] = $manager->getSeoTitle();
				}
				if ($article) {
					$domain = rex_yrewrite::getDomainByArticleId($article->getId());
					$title = $domain->getTitle();
					$titleValues[] = $article->getName();
				}
				if (count($titleValues)) {
					$title = rex_escape(str_replace('%T', implode(' / ', $titleValues), $title));
				}
				if ('' !== rex::getServerName()) {
					$title = rex_escape(str_replace('%SN', rex::getServerName(), $title));
				}

				$tags['title'] = sprintf('<title>%s</title>', $title);
				$ep->setSubject($tags);
			});

			$tagsHtml = $seo->getTags();

			$description = self::normalize($manager->getSeoDescription());
		}

		$full_url = rex_yrewrite::getFullPath();

		if (!rex::getProperty('req-with')) {

			$tagsHtml .= <<<EOT
			
				<script type="application/ld+json">
				{
					"@context": "http://schema.org",
					"@type": "LocalBusiness",
					"@id": "$full_url",
					"address": {
						"@type": "PostalAddress",
						"postalCode": "{{address_plz}}",
						"addressLocality": "{{address_ort}}",
						"addressRegion": "{{address_kanton_code}}",
						"addressCountry": "{{address_land_code}}"
					},
					"geo": {
						"@type": "GeoCoordinates",
						"latitude": "{{address_geo lat.}}",
						"longitude": "{{address_geo long.}}"
					},
					"description": "$description",
					"name": "{{address_firma}}",
					"url": "$full_url",
					"image": "$logo"
				}
				</script>

				<script type="application/ld+json">
				{
					"@context": "http://schema.org",
					"@type": "Organization",
					"url": "$full_url",
					"logo": "$logo"
				}
				</script>
				EOT;
		}


		return $tagsHtml;
	}

	protected static function normalize($string)
	{
		$string = rex_escape(strip_tags($string));
		return str_replace(["\n", "\r"], [' ', ''], $string);
	}
}
