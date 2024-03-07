<?php

class massif_utils
{

	public static $pager;
	private static $yrs;

	private static $sliceCache = [];

	private static $lastColStart = 0;
	private static $totalCols = 0;
	public static $maxCols = 12;

	public static function getH1($title, $class = [])
	{
		if (count($class) === 0)
			$class[] = 'h1';
		$hasH1 = rex::getProperty('has-h1');
		rex::setProperty('has-h1', true);
		$tag = $hasH1 ? 'h2' : 'h1';
		return '<' . $tag . ' class="' . implode(' ', $class) . '">' . $title . '</' . $tag . '>';
	}

	public static function rexVartoArray($value)
	{
		return array_filter(rex_var::toArray($value, function ($item) {
			$val = '';
			foreach ($item as $k => $v) {
				if ($v) {
					$val = $v;
				}
			}
			return $val;
		}));
	}

	public static function isFirstLastSlice($slice_id, $ctype = 1, $firstSliceClass = 'first-row', $lastSliceClass = 'last-row')
	{
		$id = (int) $slice_id;
		if (!$id || !is_int($id)) {
			return -1;
		}
		if (count(self::$sliceCache) === 0) {
			$slice = rex_article_slice::getArticleSliceById($id);
			$sql = rex_sql::factory();
			$query = '
						SELECT *
            FROM ' . rex::getTable('article_slice') . '
            WHERE article_id=? AND clang_id=? AND revision=? 
            ORDER BY priority ';
			$queryFirst = $query . 'LIMIT 1';
			$queryLast = $query . 'DESC LIMIT 1';
			$first = rex_article_slice::fromSql($sql->setQuery($queryFirst, [$slice->getArticleId(), $slice->getClangId(), $slice->getRevision()]));
			$last = rex_article_slice::fromSql($sql->setQuery($queryLast, [$slice->getArticleId(), $slice->getClangId(), $slice->getRevision()]));
			self::$sliceCache = ['first' => $first->getId(), 'last' => $last->getId()];
		}
		$out = '';
		if (self::$sliceCache['first'] == $id) $out .= ' ' . $firstSliceClass;
		if (self::$sliceCache['last'] == $id) $out .= ' ' . $lastSliceClass;
		return $out;
	}

	/*
	*	normalize an article name after getting it by ID
	*/

	public static function normalizeArticleNameById($id)
	{

		if ((int) $id == 0)
			return;

		if (!self::$yrs) {
			self::$yrs = new \rex_yrewrite_scheme();
		}

		$article = rex_article::get($id);
		if ($article) {
			return self::$yrs->normalize($article->getName());
		}
		return false;
	}

	/*
	*	sort Array vertically (for CSS columns for example)
	*/

	public static function sortArrayVertically($data, $numCols = 3)
	{

		$temp = $new = [];
		$newData = [];
		$numEntries = count($data);
		$numRows = round($numEntries / $numCols);
		$q = 0;
		for ($i = 0; $i < $numRows; $i++) {
			for ($r = 0; $r < $numCols; $r++) {
				if ($data[$q])
					$temp[$r][$i] = $data[$q];
				$q++;
			}
		}
		foreach ($temp as $row) {
			foreach ($row as $col) {
				$new[] = $col;
			}
		}
		unset($temp);
		return $new;
	}

	public static function getAnchorNav()
	{
		$sql = rex_sql::factory();
		$sql->setQuery('SELECT value1 as name FROM rex_article_slice WHERE module_id = 80 AND article_id = ' . rex_article::getCurrentId() . ' ORDER BY priority');
		if ($sql->getRows()) {
			$result = $sql->getArray();
			return massif_utils::parse('anchor-nav', null, ['data' => $result]);
		}
	}

	/*
	*	rex 4 style
	*/

	static function toArray($value)
	{
		$return = @unserialize($value);

		if (!$return) {
			$return = unserialize(htmlspecialchars_decode(str_replace('<br />', '', $value), ENT_QUOTES));
		}

		return is_array($return) ? $return : rex_var::toArray($value);
	}

	/*
	*	parse video url
	*/

	public static function parseVideoUrl($subject)
	{
		if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $subject, $match)) {
			$video_id = $match[1];
			return array("video" => "youtube", "videoID" => $video_id);
		}
		$regexstr = '~
		# Match Vimeo link and embed code
		(?:<iframe [^>]*src=")? 	# If iframe match up to first quote of src
		(?:				# Group vimeo url
			https?:\/\/		# Either http or https
			(?:[\w]+\.)*		# Optional subdomains
			vimeo\.com		# Match vimeo.com
			(?:[\/\w]*\/videos?)?	# Optional video sub directory this handles groups links also
			\/			# Slash before Id
			([0-9]+)		# $1: VIDEO_ID is numeric
			[^\s]*			# Not a space
		)				# End group
		"?				# Match end quote if part of src
		(?:[^>]*></iframe>)?		# Match the end of the iframe
		(?:<p>.*</p>)?		        # Match any title information stuff
		~ix';
		if (preg_match($regexstr, $subject, $match)) {
			$video_id = $match[1];
			return array("video" => "vimeo", "videoID" => $video_id);
		}
		return false;
	}

	public static function buildVideoUrl($videoId, $type)
	{
		if (!$videoId || !$type) return;
		if ($type == 'youtube') {
			return 'https://www.youtube.com/embed/' . $videoId;
		} else if ($type == 'vimeo') {
			return 'https://player.vimeo.com/video/' . $videoId;
		} else return false;
	}

	/*
	*	create youtube iframe with custom options
	*/

	public static function videoEmbed($subject, $params = [], $allow = [], $allowFullScreen = true)
	{

		$_params = [
			'youtube' => ["rel" => 0, "enablejsapi" => 1, "iv_load_policy" => 3, "showinfo" => 0],
			'vimeo' => ["color" => 'eca400', "byline" => true, "portrait" => true, "title" => true, "quality" => '1080p']
		];
		$_allow = [
			'youtube' => ["accelerometer", "encrypted-media", "gyroscope", "picture-in-picture"],
			'vimeo' => ["autoplay", "fullscreen"],
		];

		if (is_int(intval($subject))) {
			$subject = 'https://vimeo.com/' . $subject;
		}

		$match = self::parseVideoUrl($subject);
		$type = $match['video'];
		$params = http_build_query(array_merge($_params[$type], $params));

		$src = self::buildVideoUrl($match['videoID'], $type);

		$allow = implode('; ', array_merge($_allow[$type], $allow));
		//print_r($match);
		$iframe = '<div class="youtube-scalar"><iframe src="' . $src . '" frameborder="0" allow="' . $allow . '"' . ($allowFullScreen ? ' allowfullscreen' : '') . '></iframe></div>';
		//rex_var_dumper::dump($iframe);
		return $iframe;
	}

	/*
	*	handle parsed urls
	*/

	public static function parseUrl($url = '')
	{
		if (!$url)
			return;

		$parsedUrl = parse_url($url);
		if (!isset($parsedUrl['scheme'])) {
			$url = 'http://' . $url;
		}
		$parsedUrl = parse_url($url);

		return ['url' => $url, 'host' => isset($parsedUrl['host']) ? $parsedUrl['host'] : '', 'path' => isset($parsedUrl['path']) ? $parsedUrl['path'] : ''];
	}

	public static function getCustomLinks($buttons, $align = '')
	{
		$buttonSet = [];
		foreach ($buttons as $button) {
			$buttonSet[] = self::getCustomLink($button['url'], ['label' => $button['label'], 'style' => isset($button['style']) ? $button['style'] : '']);
		}

		$buttonSet = array_filter($buttonSet);

		if (count($buttonSet) > 0) {
			$fragment = new rex_fragment();
			$fragment->setVar('align', $align, false);
			$fragment->setVar('buttonSet', $buttonSet, false);
			return $fragment->parse('massif-buttons.php');
		}
	}

	public static function getCustomLink($url, $_params = [])
	{

		$return = [
			'url' => '',
			'target' => '',
			'type' => '',
			'label' => '',
			'style' => ''
		];
		// set url
		if (!isset($url) or empty($url)) return '';

		$params = array_merge(['class' => ['cl-link']], $_params);
		$return['target'] = isset($params['target']) ? $params['target'] : '';

		$ytable = explode('://', $url);

		$entry = null;
		if (is_array($ytable) && count($ytable) === 2) {
			$table = str_replace('-', '_', $ytable[0]);
			$id = intval($ytable[1]);
			if ($id && $table) {
				try {
					$entry = rex_yform_manager_dataset::get($id, $table);
				} catch (Exception $e) {
				}
				if ($entry) {
					$profile = array_shift(Url\Profile::getByTableName($table));
					if ($profile) {
						$return['url'] = rex_getUrl(null, null, [$profile->getNamespace() => $id]);
						$return['type'] = 'ytable';
						// $return['label'] = $params['record_label'];
						$return['data_id'] = $id;
					}
				}
			}
		}
		if (!$entry) {
			if (file_exists(rex_path::media($url)) === true) {
				// media file?
				$return['url'] = rex_url::media($url);
				$return['type'] = 'media';
				$return['target'] .= ' target="_blank" data-no-swup';
			} else {
				// no media, may be an external or internal URL
				$is_url = filter_var($url, FILTER_VALIDATE_URL);
				// probably an interalURL
				if ($is_url === FALSE && is_numeric($url) && $article = rex_article::get($url)) {
					$templateId = $article->getTemplateId();
					$return['url'] = rex_getUrl($url);
					$return['type'] = 'internal';
					if ($templateId == 2) {
						$return['url'] = 'javascript:void(0);';
						$return['target'] .= ' data-a11y-dialog-show="overlay-' . $url . '" data-no-swup';
					} else if (substr($url, 0, 1) === '#') {
						$return['target'] .= ' data-no-swup';
					}
				} else {
					// external URL
					$return['url'] = $url;
					$return['type'] = 'external';
					$return['target'] .= ' target="_blank" data-no-swup';
				}
			}
		}

		if (isset($params['label']))
			$return['label'] = $params['label'];

		if (isset($params['style']))
			$return['style'] = $params['style'];

		$return['class'] = $params['class'];
		return $return;
	}

	public static function formatRange($from = '', $to = '', $opts = [])
	{
		$defs = ['glue' => ' bis ', 'addGlueOnEmptyFrom' => true];
		$opts = array_merge_recursive($defs, $opts);
		$glue = $opts['glue'];
		$val = '';
		if ($to) {
			if ($from) {
				$val = $from . $glue . $to;
			} else {
				if ($opts['addGlueOnEmptyFrom']) {
					$val = $glue . $to;
				} else {
					$val = $to;
				}
			}
		} else {
			$val = $from;
		}
		return $val;
	}

	public static function getArrayFromString($string)
	{
		if (is_array($string)) {
			return $string;
		}

		$delimeter = ',';
		$rawOptions = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $string);

		$options = [];
		foreach ($rawOptions as $option) {
			$delimeter = '=';
			$finalOption = preg_split('~(?<!\\\)' . preg_quote($delimeter, '~') . '~', $option);
			$v = $finalOption[0];
			if (isset($finalOption[1])) {
				$k = $finalOption[1];
			} else {
				$k = $finalOption[0];
			}
			$s = ['\=', '\,'];
			$r = ['=', ','];
			$k = str_replace($s, $r, $k);
			$v = str_replace($s, $r, $v);
			$options[$k] = trim($v);
		}

		return $options;
	}

	/*
	*	add a wrap
	*/

	public static function addWrapper($data, $class = '', $wrap = true)
	{
		if ($class) $class = ' ' . $class;
		$out .= '<div class="row-o row-' . $class . '">';
		if ($wrap) $out .= '<div class="wrap">';
		$out .= $data;
		if ($wrap) $out .= '</div>';
		$out .= '</div>';
		return $out;
	}


	/*
	*	is current article
	*/

	public static function isStartPage($id = '')
	{
		if (!$id)
			$id = rex_article::getCurrentId();
		return rex_article::getSiteStartArticleId() == $id;
	}

	/*
	*	check Google recaptcha
	*/

	public static function checkReCaptcha($data)
	{

		$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
		$recaptcha_secret = ''; // Insert your secret key here
		$recaptcha_response = $_REQUEST['recaptcha_response'];

		// Make the POST request
		$recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
		$recaptcha = json_decode($recaptcha);
		// Take action based on the score returned
		if ($recaptcha->success == true && $recaptcha->score >= 0.5 && $recaptcha->action == 'contact') {
			// This is a human. Insert the message into database OR send a mail
			return true;
		} else {
			// Score less than 0.5 indicates suspicious activity. Return an error
			return false;
		}
	}

	/*
	*	format a price using phps NumberFormatter 
	*/

	public static function formatPrice($value, $locale = 'de_CH')
	{

		if ($value) {
			$fmt = numfmt_create($locale, NumberFormatter::CURRENCY);
			return str_replace('.00', '.-', numfmt_format_currency($fmt, $value, "CHF"));
		}
	}

	/*
	*	format a date using phps IntlDateFormatter 
	* 	http://userguide.icu-project.org/formatparse/datetime
	*/

	public static function formatDate($date, $pattern = 'd. MMMM y', $locale = '')
	{

		if ($date) {
			$time = strtotime($date);
			//$time = $date;
			if (!$locale) $locale = rex_clang::getCurrent()->getValue('locale');
			if (!$locale) $locale = 'de_CH';
			$fmt = datefmt_create($locale, IntlDateFormatter::LONG, IntlDateFormatter::LONG, date_default_timezone_get());
			$fmt->setPattern($pattern);
			return datefmt_format($fmt, $time);
		}
	}

	public static function getDateTag($dateTime = '', $pattern = 'd.MM.yy', $class = 'datetime', $locale = 'de_CH')
	{
		if (!$dateTime)
			return;
		return '<time datetime="' . $dateTime . '" class="' . $class . '">' . self::formatDate($dateTime, $pattern, $locale) . '</time>';
	}

	/*
	*	format a phone number to use as a link href 
	*/

	public static function formatPhone($phoneNumber)
	{

		if ($phoneNumber) {
			$phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

			if (strlen($phoneNumber) > 10) {
				$countryCode = substr($phoneNumber, 0, strlen($phoneNumber) - 10);
				$areaCode = substr($phoneNumber, -10, 3);
				$nextThree = substr($phoneNumber, -7, 3);
				$lastFour = substr($phoneNumber, -4, 4);

				$phoneNumber = '+' . $countryCode . $areaCode . $nextThree . $lastFour;
			} else if (strlen($phoneNumber) == 10) {
				$areaCode = substr($phoneNumber, 0, 3);
				$nextThree = substr($phoneNumber, 3, 3);
				$lastFour = substr($phoneNumber, 6, 4);

				$phoneNumber = $areaCode . $nextThree . $lastFour;
			} else if (strlen($phoneNumber) == 7) {
				$nextThree = substr($phoneNumber, 0, 3);
				$lastFour = substr($phoneNumber, 3, 4);

				$phoneNumber = $nextThree . $lastFour;
			}

			return $phoneNumber;
		}
	}

	/*
	*	trim text to a certain length, add ellipses and remove html
	*/

	public static function trimText($input, $length = 160, $ellipses = true, $strip_html = false)
	{
		//strip tags, if desired
		if ($strip_html) {
			$input = strip_tags(stripslashes($input));
		}

		$totalCharacterLength = mb_strlen(str_replace(["\r", "\n", "\t", "&ndash;", "&rsquo;", "&#39;", "&quot;", "&nbsp;"], '', html_entity_decode(strip_tags($input))));

		//no need to trim, already shorter than trim length
		if ($totalCharacterLength <= $length) {
			return $input;
		}

		//find last space within length
		$last_space = strrpos(substr($input, 0, $length), ' ');
		$trimmed_text = substr($input, 0, $last_space);

		if (class_exists('hyphenator') && 1 == 2)
			$trimmed_text = hyphenator::hyphenate($trimmed_text);

		//add ellipses (...)
		if ($ellipses) {
			$trimmed_text .= ' &hellip;';
		}

		return $trimmed_text;
	}

	/*
	*	add string to $str, optionally with a separator $sep
	*/

	public static function addToString(&$str, $add, $sep = '')
	{
		if ($str != '')
			$str .= $sep;
		$str .= $add;
	}

	/*
	*	Get parsed file
	*/

	public static function parse($file, $vars = [])
	{
		$fragment = new rex_fragment();
		foreach ($vars as $key => $value) {
			$fragment->setVar($key, $value, false);
		}
		return $fragment->parse($file . ".php");
	}

	/*
	*	Get current path route (as in, category depth)
	*/

	public static function getPathRoute()
	{
		$path = rex_article::getCurrent()->getPathAsArray();
		if (!in_array(rex_article::getCurrentId(), $path)) {
			$path[] = rex_article::getCurrentId();
		}
		return $path;
	}

	/*
	*	Create an unordered list from files
	*/
	public static function getDownload($file = '')
	{
		$media = rex_media::get($file);
		$out = '';
		if ($media) {
			$icon = self::parseIcon($media->getExtension(), 'fa');
			//$icon = '<i class="icon icon-download"></i>';
			$label = $media->getTitle() ? $media->getTitle() : $media->getFilename();
			$out .= '<li class="download-item">';
			$out .= '<a href="' . $media->getUrl() . '" title="' . $label . '" target="_blank" class="download-anchor h-icon">' . $icon . '<span class="download-label">' . $label . ' </span></a>';
			// <span class="info">'.rex_formatter::bytes($media->getSize(), [1, '.', "'"]).'</span>
			$out .= '</li>';
		}
		return $out;
	}

	public static function getDownloads($files = '')
	{
		$filesArray = explode(',', $files);
		$out = [];
		if (count($filesArray)) {
			$out[] = '<ul class="download-list">';
			foreach ($filesArray as $file) {
				$out[] = self::getDownload($file);
			}
			$out[] = '</ul>';
		}
		return implode('', $out);
	}
	/* helper */

	public static function parseIcon($ext, $_iconSet = 'fa')
	{
		$iconLib = [
			'fa' => [
				'txt,json,ini' => 'file-text',
				'pdf' => 'file-pdf',
				'csv' => 'file-csv',
				'doc,docx' => 'file-word',
				'xlsx,xls' => 'file-excel',
				'pptx,ppt,ppsx' => 'file-powerpoint',
				'jpg,gif,png' => 'file-image',
				'zip,rar,7zip,sit' => 'file-archive',
				'mp3,m4a,wav,aac' => 'file-sound',
				'mp4,mpg,avi,mkv,webp' => 'file-video',
				'html,php,js,scss,sass,css' => 'file-code',
				'default-icon' => 'file',
				'template' => '<i class="icon far fa-{icon}"></i>'
			]
		];
		$iconSet = $iconLib[$_iconSet];
		$search = array_values(array_intersect_key($iconSet, array_flip(preg_grep("/\b$ext\b/", array_keys($iconSet), 0))));
		$icon = $search[0];
		if (!$icon) {
			$icon = $iconSet['default-icon'];
		}
		return str_replace('{icon}', $icon, $iconSet['template']);

		/*
		function preg_grep_keys($pattern, $input, $flags = 0) {
			return array_intersect_key($input, array_flip(preg_grep($pattern, array_keys($input), $flags)));
		}
		*/
	}

	public static function createZip(string $filename = "", array $files = [], string $path = '')
	{
		if (!$filename || !is_array($files) || count($files) == 0) return false;
		if (!$path) $path = rex_path::media();
		$zipPath = $path . 'zips/';
		$zipFilePath = $zipPath . $filename . '.zip';
		if (file_exists($path . $filename)) rex_file::delete($zipFilePath);

		$zip = new ZipArchive();
		if (!is_dir($zipPath)) {
			rex_dir::create($zipPath);
		}
		$zip->open($zipFilePath, ZipArchive::CREATE);
		foreach ($files as $file) {
			$zip->addFile($path . $file, basename($file));
		}
		$zip->close();
		return $zipFilePath;
	}
	/*
	*	Capitalize names nicely
	*/

	public static function nameCase($string)
	{
		$word_splitters = array(' ', '-', "O'", "L'", "D'", 'St.', 'Mc');
		$lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d'");
		$uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');

		$string = strtolower($string);
		foreach ($word_splitters as $delimiter) {
			$words = explode($delimiter, $string);
			$newwords = array();
			foreach ($words as $word) {
				if (in_array(strtoupper($word), $uppercase_exceptions))
					$word = strtoupper($word);
				else if (!in_array($word, $lowercase_exceptions))
					$word = ucfirst($word);

				$newwords[] = $word;
			}

			if (in_array(strtolower($delimiter), $lowercase_exceptions))
				$delimiter = strtolower($delimiter);

			$string = join($delimiter, $newwords);
		}
		return $string;
	}

	/*
	*	Create flex Table from Be-Table
	*/

	public static function createTable($array)
	{
		if (!is_array($array))
			return;
		if (count($array) == 0)
			return;

		$out = '<div class="flex be-table">';
		foreach ($array as $row) {
			$out .= '<div class="flex-box flex-fourth be-table-label">' . $row[0] . '</div>';
			$out .= '<div class="flex-box flex-three-fourths be-table-value">' . $row[1] . '</div>';
		}
		$out .= '</div>';
		return $out;
	}

	/*
	*	Create flex Table from Be-Table
	*/

	public static function createDataTable($arr, $options = [])
	{
		if (!is_array($arr))
			return;
		if (count($arr) == 0)
			return;

		$classes = ['data-table', 'typo-margin'];
		if (isset($options['class']))
			$classes[] = $options['class'];

		$out = '<dl class="' . implode(' ', $classes) . '">';
		foreach ($arr as $row) {
			$out .= '<dt>' . $row['label'] . '</dt>';
			$out .= '<dd>' . $row['value'] . '</dd>';
		}
		$out .= '</dl>';
		return $out;
	}

	/*
	*	get yform table
	*/

	public static function getYformTable($name, $disableTableFuncs = [], $tableValues = [], $addLinkVars = [])
	{

		$tableFunctions = ['add', 'delete', 'search', 'export', 'truncate_table'];
		foreach ($disableTableFuncs as $func) {
			if (($key = array_search($func, $tableFunctions)) !== false) {
				unset($tableFunctions[$key]);
			}
		}

		$table = rex_yform_manager_table::get($name);
		foreach ($tableValues as $offset => $value) {
			$table->offsetSet($offset, $value);
		}

		try {
			$page = new rex_yform_manager();
			$page->setTable($table);
			$page->setLinkVars(['page' => rex_request('page', 'string'), 'table_name' => $table->getTableName(), $addLinkVars]);
			$page->setDataPageFunctions($tableFunctions);
			return $page->getDataPage();
		} catch (Exception $e) {
			$message = nl2br($e->getMessage() . "\n" . $e->getTraceAsString());
			return rex_view::warning($message);
		}
	}


	public static function bePageSetSubPaths(\rex_be_page $page, \rex_package $package, $prefix = '')
	{
		foreach ($page->getSubpages() as $subpage) {
			if (!$subpage->hasSubPath()) {
				$subpage->setSubPath($package->getPath('pages/' . $prefix . $subpage->getKey() . '.php'));
			}
			self::bePageSetSubPaths($subpage, $package, $prefix . $subpage->getKey() . '.');
		}
	}

	public static function beBackendNav($head, $pages)
	{

		$nav = \rex_be_navigation::factory();
		$nav->setHeadline('default', \rex_i18n::msg('subnavigation', $head));
		$pages = rex_be_controller::getPageObject(rex_be_controller::getCurrentPagePart(1))->getSubpages();
		foreach ($pages as $pageObj) {
			$nav->addPage($pageObj);
		}
		$blocks = $nav->getNavigation();
		$navigation = [];
		if (1 == count($blocks)) {
			$navigation = current($blocks);
			$navigation = $navigation['navigation'];
		}

		if (!empty($navigation)) {
			$fragment = new \rex_fragment();
			$fragment->setVar('left', $navigation, false);
			$subtitle = $fragment->parse('core/navigations/content.php');
		} else {
			$subtitle = '';
		}


		$fragment = new \rex_fragment();
		$fragment->setVar('heading', $head, false);
		$fragment->setVar('subtitle', $subtitle, false);
		$return = $fragment->parse('core/page/header.php');

		return $return;
	}
}
