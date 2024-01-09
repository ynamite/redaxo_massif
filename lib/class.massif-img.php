<?php

class massif_img
{


	public static $loaderHtml = '<div class="img-spinner"><div></div><div></div><div></div></div>';

	protected static $addPrintMarkup = false;
	protected static $imgMgrPath = 'mediatypes/';

	protected static $types = ['auto' => '', 'auto-crop' => '-c', 'square' => '-sq'];
	protected static $srcsets = ['default-xs' => 272, 'default-s' => 371, 'default' => 480, 'default-m' => 569, 'default-l' => 767, 'default-xl' => 1163, 'default-xxl' => 1559];

	public static function getPlaceholder($width = 0, $height = 0)
	{
		return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 " . $width . " " . $height . "'%3E%3C/svg%3E";
	}

	/*
	*	get image manager string
	*/

	public static function getPath($img, $type = 'lightbox', $_params = [])
	{
		$params = [
			'absolute' => false,
			'action' => ''
		];
		if (is_array($_params)) {
			$params = array_merge($params, $_params);
			unset($_params);
		}
		if (in_array($params['action'], ['cover', 'content'])) {
			$out = 'media-' . $params['action'] . '/' . $type . '/' . $img;
		} else {
			$out = self::$imgMgrPath . $type . '/' . $img;
		}
		return (!$params['absolute']) ? rex_url::frontend() . $out : rex_yrewrite::getCurrentDomain()->getUrl() . $out;
	}

	public static function getByGroup($group_name)
	{
		return rex_yform_manager_table::get('rex_media_manager_type_group')->query()->where('name', $group_name)->findOne();
	}

	private static function getTypes($group_name)
	{
		$group = self::getByGroup($group_name);
		return rex_yform_manager_table::get('rex_media_manager_type_meta')->query()->where('group_id', $group->getId())->orderBy('prio', 'desc')->find();
	}

	public static function getSrcset($img, $groupname, $height)
	{
		$srcset = [];
		$types = self::getTypes($groupname);

		foreach ($types as $key => $type) {

			// Erstellen der Mediendatei
			if ($type->getValue('min_width') != "") {
				$srcset[$key] = self::getPath($img, $type->getValue('type')) . ' ' . substr($type->getValue('min_width'), 0, -2) . 'w';
				if ($key == 0)
					$srcset[$key] .= ' ' . $height . 'h';
			}
		}
		/*
		if (!is_array($type)) {
			$suffix = self::$types[$type];
			$count = 0;
			foreach (self::$srcsets as $size => $w) {
				$srcset[$count] = self::getPath($img, $size . $suffix) . ' ' . $w . 'w ';
				if ($count == 0)
					$srcset[$count] .= $height . 'h';
				$count++;
			}
		} else {
			foreach ($type as $_type => $size) {
				$srcset[] = self::getPath($img, $_type) . ' ' . $size . 'w';
			}
		}
		*/
		return implode(', ', $srcset);
	}

	/*
	*	build image with lazyload in mind
	*/

	public static function get($img, $_params = [])
	{
		if ($img == '') return '';

		$params = [
			'alt' => '',
			'type' => 'default',
			'tag' => 'img',
			'calc' => false,
			'calc_as' => '',
			'width' => -1,
			'height' => -1,
			'ratio' => 1.388,
			'classes' => [],
			'style' => '',
			'inline-svg' => '',
			'loading' => 'lazy',
			'caption' => ''
		];

		$extensionsExcludeManager = ['svg', 'gif'];

		if (is_array($_params)) {
			$params = array_merge($params, $_params);
			unset($_params);
		}

		$rex_media = rex_media::get($img);

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

		if (is_array($params['type'])) {
			$mmType = array_key_first($params['type']);
		} else {
			$defMmType = 'default-xs';
			if ($params['calc_as'] == 'crop') {
				$defMmType .= '-c';
			}
			$mmType = ($params['type'] == 'auto' || $params['type'] == 'auto-crop') ? $defMmType : $params['type'];
		}

		if ($params['calc'] && $rex_media) {
			$media = rex_media_manager::create($mmType, $img)->getMedia();
			$width = $media->getWidth();
			$height = $media->getHeight();
			$ratio = /*($params['ratio']) ? $params['ratio'] : */ $width / $height;
		} else if ($params['width'] !== -1 && $params['height'] !== -1) {
			$width = $params['width'];
			$height = $params['height'];
			$ratio = ($params['ratio']) ? $params['ratio'] : $width / $height;
		} else if ($params['ratio']) {
			$width = 1000;
			//$height = ($params['ratio'] > 1) ? round($width / $params['ratio']) : round($width * $params['ratio']) ;
			$height = round($width / $params['ratio']);
			$ratio = $params['ratio'];
		} else if ($rex_media) {
			$width = $rex_media->getWidth();
			$height = $rex_media->getHeight();
			$ratio = ($params['ratio']) ? $params['ratio'] : $width / $height;
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
				$lip = 'default-m';
				if (!rex::isBackend() && $params['loading'] == 'lazy') {
					$lip = 'lip-' . $params['type'];
					$image .= 'decoding="async" ';
					//decoding = "async"
				}
				$image .= 'src="' . self::getPath($img, $lip) . '" ';
				$image .= 'data-srcset="' . self::getSrcset($img, $params['type'], $height) . '" ';
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
					$image .= '<source srcset="' . self::getPath($img, $_type) . '"';
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

		return massif_utils::parse('swiper', null, ['params' => $params]);
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
