<?php

class massif_img
{


	public static $loaderHtml = '<div class="img-spinner"><div></div><div></div><div></div></div>';

	protected static $addPrintMarkup = false;
	protected static $imgMgrPath = 'image/';

	public static $sizes = [];

	public static function getPlaceholder($width = 0, $height = 0)
	{
		return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 " . $width . " " . $height . "'%3E%3C/svg%3E";
	}

	/*
	*	get image manager string
	*/

	public static function getPath($img, $size, $type = 'auto', $_params = [])
	{
		if (!in_array($size, self::$sizes))
			$size = self::$sizes[0];

		$params = [
			'absolute' => false,
			'action' => ''
		];
		if (is_array($_params)) {
			$params = array_merge($params, $_params);
			unset($_params);
		}
		$out = self::$imgMgrPath . $type . '/' . $size . '/' . $img;
		return (!$params['absolute']) ? rex_url::frontend() . $out : rex_yrewrite::getCurrentDomain()->getUrl() . $out;
	}

	public static function getSrcset($img, $height, $type = 'auto')
	{
		$srcset = [];

		$sizes = self::$sizes;
		array_shift($sizes);

		foreach ($sizes as $key => $size) {

			// Erstellen der Mediendatei
			$srcset[] = self::getPath($img, $size, $type) . ' ' . $size . 'w';
			if ($key == 0)
				$srcset[$key] .= ' ' . $height . 'h';
		}

		return implode(', ', $srcset);
	}

	public static function getSizes()
	{
		$output = [];
		$sizes = self::$sizes;
		array_shift($sizes);
		$maxSize = array_pop($sizes);
		$maxWidths = $sizes;
		array_shift($maxWidths);
		$maxWidths[] = $maxSize;
		for ($i = 0; $i < count($sizes); $i++) {
			$output[] = '(max-width: ' . $maxWidths[$i] . 'px) ' . $sizes[$i] . 'px';
		}
		$output[] = $maxSize . 'px';
		return implode(', ', $output);
	}

	/*
	*	build image with lazyload in mind
	*/

	public static function get($img, $_params = [])
	{
		if ($img == '') return '';

		$params = [
			'alt' => '',
			'type' => 'auto',
			'tag' => 'img',
			'calc' => false,
			'calc_as' => '',
			'width' => -1,
			'height' => -1,
			'ratio' => 0,
			'classes' => [],
			'style' => '',
			'inline-svg' => '',
			'loading' => 'lazy',
			'caption' => ''
		];

		$width = $height = $ratio = 0;

		$extensionsExcludeManager = ['svg', 'gif'];

		$params = array_merge($params, $_params);
		unset($_params);

		$rex_media = rex_media::get($img);
		if (!$rex_media) return;
		$media_width = $rex_media->getWidth();
		$media_height = $rex_media->getHeight();
		if ($params['width'] == -1) $params['width'] = $media_width;
		if ($params['height'] == -1) $params['height'] = $media_height;

		$ext = '';
		if ($rex_media) {

			$ext = $rex_media->getExtension();

			if ($ext == 'json') {
				return '<div class="lottie" data-json="' . $rex_media->getUrl() . '"></div>';
			}

			if (!$params['alt']) {
				$params['alt'] = self::getMeta($img);
			}
		}

		if ($ext !== 'svg') {
			if ($params['calc'] && $rex_media) {
				$mmType = is_array($params['type']) ? array_key_first($params['type']) : $params['type'];
				$media = rex_media_manager::create($mmType, $img)->getMedia();
				$width = $media->getWidth();
				$height = $media->getHeight();
				$ratio = $width / $height;
			} else if ($params['width'] || $params['ratio']) {
				$width = $params['width'] ? $params['width'] : 1000;
				$ratio = $params['ratio'] ? $params['ratio'] : $media_width / $media_height;
				$height = round($width / $ratio);
			} else if ($rex_media) {
				$width = $media_width;
				$height = $media_height;
				$ratio = $params['ratio'] ? $params['ratio'] : $width / $height;
			}
		} else {
			$width = $media_width;
			$height = $media_height;
		}

		$focuspoint_css = '';
		if ($rex_media) {
			$imagesize = getimagesize(rex_path::media($rex_media->getFileName()));
			if ($imagesize && $imagesize[1] > $imagesize[0]) {
				$params['classes'][] = 'dir-portrait';
			}

			$focuspoint_css = str_replace(',', '% ', $rex_media->getValue('med_focuspoint')) . '%';
		}

		if ($ext == 'gif') {
			$img = $img . '?' . time();
		}
		if ($ext == 'svg')
			$params['loading'] = 'eager';

		if ($params['tag'] == 'img' && $img) {
			$image = '<img width="' . $width . '" height="' . $height . '" loading="' . $params['loading'] . '" data-sizes="auto" data-aspectratio="' . $ratio . '" ';
			// if ($params['loading'] == 'lazy')
			$image .= 'class="lazyload" ';
			if ($params['type'] && !in_array($ext, $extensionsExcludeManager)) {
				// $lipSize = $width && in_array($width, self::$sizes) ? $width : self::$sizes[1];
				$lipSize = self::$sizes[1];
				$srcset = self::getSrcset($img, $height, $params['type']);
				$image .= 'sizes="' . self::getSizes() . '"';
				$image .= 'data-sizes="auto"';
				if (!rex::isBackend() && $params['loading'] == 'lazy') {
					$lipSize = self::$sizes[0];
					$image .= 'decoding="async" ';
					$image .= 'data-srcset="' . $srcset . '" ';
				} else {
					$image .= 'srcset="' . $srcset . '" ';
					$image .= 'fetchpriority="high" ';
				}
				$image .= 'src="' . self::getPath($img, $lipSize, $params['type']) . '" ';
			} else {
				$image .= 'src="' . rex_url::media($img) . '" ';
			}
			if ($focuspoint_css != '%') {
				$image .= 'style="object-position:' . $focuspoint_css . ';' . $params['style'] . '" data-bg-pos="' . $focuspoint_css . '" ';
			}
			$image .= 'alt="' . htmlentities($params['alt']) . '"  />';
		} else if ($params['tag'] == 'picture') {
			$image = '<picture>';
			if (is_array($params['type'])) {
				foreach ($params['type'] as $_type => $size) {
					$image .= '<source srcset="' . self::getPath($img, $size, $_type) . '"';
					if ($size) {
						$image .= ' media="(max-width: ' . $size . 'px)"';
					}
					$image .= ' />';
				}
			}
		}

		if ($params['inline-svg']) {
			$image = file_get_contents(rex_path::media($img));
		}

		$out = '<figure class="' . $params['tag'] . '-cell';
		if ($params['classes'] && is_array($params['classes'])) {
			$out .= ' ' . implode(' ', $params['classes']);
		}
		if ($ext == 'svg') {
			$out .= ' svg';
		}
		$out .= '">';

		if (!rex::isBackend() && isset($image)) {
			$out .= str_replace('</picture>', self::$loaderHtml . '</picture>', $image);
			if ($params['tag'] == 'img' && ($ext != 'svg' && $params['inline-svg'])) {
				$out .= self::$loaderHtml;
			}
		} else if (isset($image)) {
			$out .= '<div style=" max-width: 200px;">' . $image . '</div>';
		}
		if ($params['caption']) {
			$out .= '<figcaption class="img-cell__caption text-small">' . $params['caption'] . '</figcaption>';
		}

		$out .= '</figure>';

		return $out;
	}

	/*
	*	get image meta info
	*/

	public static function getMeta($file, $field = 'title')
	{

		if ($file = rex_media::get($file)) {
			$title = $file->getValue($field);
			if (rex_clang::getCurrentId() == 2)
				$title = $file->getValue('med_title_2');

			return $title;
		}
	}

	/*
	*	get swiper html
	*/

	public static function getSwiper($_params = [])
	{

		$params = [
			'images' => [],
			'content' => '',
			'pager' => false,
			'dir-nav' => true,
			'controls' => true,
			'type' => 'visual',
			'ratio' => 0,
			'prev-icon' => '<i class="icon fal fa-chevron-left"></i>',
			'next-icon' => '<i class="icon fal fa-chevron-right"></i>'
		];

		if (is_array($_params)) {
			$params = array_merge($params, $_params);
			unset($_params);
		}

		if (isset($_params))
			$params['images'] = (is_array($_params['images'])) ? $_params['images'] : array_filter(explode(',', $_params['images']));

		return massif_utils::parse('massif-swiper', null, ['params' => $params]);
	}

	/*
	*	create slider list html 
	*/

	public static function imgList($images, $video, $options_user = array())
	{
		$options['max_slides'] = -1;
		$options['ul_class'] = 'rslides';
		$options['img_type'] = 'visual';
		$options['anchor'] = false;
		$options['anchor_class'] = '';
		$options['anchor_img_type'] = 'lightbox';
		$options['anchor_title'] = 'Vergrössern';
		$options = array_merge($options, $options_user);
		$out = '<ul class="' . $options['ul_class'] . '"';
		if (rex::isBackend()) {
			$out .= ' style="list-style:none;overflow:hidden;margin:0;padding:0;"';
		}
		$out .= '>';
		$i = 0;
		if ($video) {
			$out .= '<li';
			if (rex::isBackend()) {
				$out .= ' style="float: left; margin: 0 10px 10px 0;"';
			}
			$out .= ' class="video-slide"><div class="valign-content"><div class="content-scalar">' . $video . '</div></div></li>';
		}
		foreach ($images as $img) {
			if ($i == $options['max_slides']) break;

			$out .= '<li';
			if (rex::isBackend()) {
				$out .= ' style="float: left; margin: 0 10px 10px 0;"';
			}
			$out .= '>';


			if ($options['anchor']) {
				$out .= '<a href="' . self::getPath($img['REX_MEDIA_1'], $options['anchor_img_type']) . '" title="' . htmlentities($options['anchor_title']) . '" class="' . $options['anchor_class'] . '">';
			}

			$out .= self::get($img, ['type' => $options['img_type']]);

			if ($options['anchor']) {
				$out .= '</a>';
			}

			$out .= '</li>';

			$i++;
		}
		$out .= '</ul>';


		return $out;
	}
}
massif_img::$sizes = \rex_effect_auto::getSizes();
