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

	public static function getColumnWidthAndStart($_cols, $_col_start)
	{
		$col_start = !$_col_start ? 0 : $_col_start;
		self::$totalCols += self::$lastColStart + $_cols;
		if (self::$totalCols >= self::$maxCols) {
			self::$totalCols = 0;
		}
		$rule = $col_start == 0 ? 'span ' . $_cols : $col_start . ' / span ' . $_cols;
		self::$lastColStart = $col_start;
		return (rex::isFrontend() ? '--' : '') . 'grid-column: ' . $rule;
	}

	public static function isFirstSlice($slice_id)
	{
		$id = (int) $slice_id;
		if (!$id || !is_int($id)) {
			return -1;
		}
		if (!self::$sliceCache[$id]) {
			self::$sliceCache[$id] = rex_article_slice::getArticleSliceById($id);
			if (!self::$sliceCache[$id])
				return -1;
		}
		return self::$sliceCache[$id]->getPriority() == 1;
	}

	public static function readmore($input, $options = ['placeholder' => '[weiterlesen]'])
	{
		$content = trim($input);
		if (!$content || strpos($content, $options['placeholder']) === false) {
			return $content;
		}
		$uid = 'exp-' . uniqid();
		$button = '<p><a href="javascript:;" data-expand="' . $uid . '"><i class="icon icon-link-arrow"></i> Readmore</a></p>';
		return str_replace($options['placeholder'], $button . '<div data-expand-id="' . $uid . '" hidden>', $content) . '</div>';
	}

	/*
	*	get Modal URL for use in an id attribute
	*	http://www.w3schools.com/tags/att_standard_id.asp
	*/

	public static function getModalUrl($id)
	{

		if ((int) $id == 0)
			return;

		$url = rex_getUrl($id);

		/*if(rex::getProperty('addon-page-url')) {
			$url = rex::getProperty('addon-page-url') . self::normalizeArticleNameById($id);
		}*/

		return $url;
	}

	public static function getModalRef($id)
	{

		if ((int) $id == 0)
			return;

		$url = rex_getUrl($id);

		return str_replace('/', '__', trim($url, '/'));
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
	*	send email with template
	*/

	public static function send_yform_email_template($from, $fromName, $recipient, $subject, $values, $_form_elements, $params = [])
	{

		if (rex::isDebugMode()) {
			$recipient = 'studio@massif.ch';
		}

		$values['recipient'] = $recipient;

		$settings['log'] = false;
		$settings['user_reply_to'] = $recipient;
		$settings['log_table'] = 'rex_yf_mail_log';
		$settings['template'] = 'email.contact-form';
		$settings['template_user'] = '';
		$settings['send_user_email'] = false;
		$settings['skip_field_types'] = ['html', 'validate', 'action', 'csrf', 'mupload'];
		$settings['skip_fields'] = ['termsofuse_accepted', 'type'];
		$settings['replace_labels'] = ['subscribe_newsletter' => 'Newsletter', 'contact_city' => 'Stadt', 'confirm' => 'DSE akzeptiert?', 'context' => 'Interesse an', 'attachment' => 'Bewerbungsunterlagen'];
		$settings['date_fields'] = ['dob'];
		$settings = array_merge($settings, $params);
		if (!$settings['template_user'])
			$settings['template_user'] = $settings['template'];

		$form_elements = [];

		foreach ($_form_elements as $el) {
			if (in_array($el[0], $settings['skip_field_types']))
				continue;
			if (in_array($el[1], $settings['skip_fields']))
				continue;
			if (isset($settings['replace_labels'][$el[1]])) {
				$el[2] = $settings['replace_labels'][$el[1]];
			}
			$form_elements[$el[1]] = str_replace('*', '', $el[2]);
		}
		unset($_form_elements);

		foreach ($form_elements as $key => $label) {
			$value = isset($values[$key . '_LABELS']) ? $values[$key . '_LABELS'] : (isset($values[$key]) ? $values[$key] : '');

			if ($value === '0') {
				$value = 'nein';
			}
			if ($value === '1') {
				$value = 'ja';
			}

			if (in_array($key, $settings['date_fields'])) {
				$value = date('d.m.Y', strtotime($value));
			}
			$values[$key] = $value;
		}



		$mailBody = sprogdown(\massif_settings::replaceStrings(\massif_utils::parse($settings['template'], null, ['values' => $values, 'form_elements' => $form_elements])));

		if ($settings['log'] && $settings['log_table']) {

			$valueParams = json_decode(rex_request('params', 'string', ''), true);

			$sql = rex_sql::factory();
			$sql->setTable($settings['log_table']);
			$sql->setValue('type', $values['type']);
			$sql->setValue('email', $from);
			$sql->setValue('context', $values['context']);
			$sql->setValue('fname', $values['fname']);
			$sql->setValue('lname', $values['lname']);
			$sql->setValue('recipient', $recipient);
			$sql->setValue('send_date', date('Y-m-d H:i:s'));
			$sql->setValue('body', $mailBody);
			$sql->setValue('attachment', rex_yform_value_mupload::getUserFolder() . $values['attachment']);
			$sql->setValue('data_id', $valueParams['id']);
			$sql->insert();
		}

		$template = [];

		// Admin mail
		$template['admin'] = [
			"name" => "general",
			"mail_from" => $from,
			"mail_from_name" => $fromName,
			"mail_reply_to" => $values['email'] ? $values['email'] : $from,
			"mail_reply_to_name" => trim($values['fname'] . ' ' . $values['lname']) ? $values['fname'] . ' ' . $values['lname'] : $from,
			"mail_to" => $recipient,
			"mail_to_name" => $fromName,
			"subject" => $subject,
			"body" => strip_tags($mailBody),
			"body_html" => $mailBody,
			'attachments' => isset($values['attachments']) ? $values['attachments'] : [],
		];


		if ($settings['send_user_email'] && $values['email']) {
			$values['is_user'] = true;
			$mailBody = sprogdown(\massif_settings::replaceStrings(\massif_utils::parse($settings['template_user'], null, ['values' => $values, 'form_elements' => $form_elements])));
			$template['user'] = $template['admin'];
			$template['user']['mail_reply_to'] = $settings['user_reply_to'];
			$template['user']['mail_reply_to_name'] = $fromName;
			$template['user']['mail_to'] = $values['email'];
			$template['user']['mail_to_name'] = trim($values['fname'] . ' ' . $values['lname']) ? $values['fname'] . ' ' . $values['lname'] : $values['email'];
			$template['user']['body'] = strip_tags($mailBody);
			$template['user']['body_html'] = $mailBody;
		}

		foreach ($template as $tpl) {

			if (!\rex_yform_email_template::sendMail($tpl, $tpl['name'])) {
				\rex_var_dumper::dump('E-Mail konnte nicht gesendet werden.');
				\rex_var_dumper::dump($tpl);
			}
		}
		return true;
	}

	/*
	*	get data from database
	*/

	public static function getData($_options = array())
	{

		$defaults = array(
			'table' => 'rex_news',
			'rowsPerPage' => 100,
			'fields' => '*', //'rex_news.*, GROUP_CONCAT(rex_tags.title SEPARATOR ",") AS tags, GROUP_CONCAT(rex_tags.id SEPARATOR ",") AS tags_id', 
			'join' => '', //'JOIN rex_tags_to_news ON rex_news.id = rex_tags_to_news.id_news JOIN rex_tags ON rex_tags.id = rex_tags_to_news.id_tag',
			'where' => '',
			'whereUnfiltered' => '',
			'groupBy' => '', //'rex_news.id',
			'orderBy' => '',
			'orderType' => '',
			'limit' => 0,
			'whereParams' => array(),
			'pager' => false,
		);

		$options = array_merge($defaults, $_options);

		$sql = rex_sql::factory();
		$query = ' FROM ' . $options['table'] . ' ';
		if ($options['join']) {
			$qry = $options['join'] . ' ';
			$query .= $qry;
		}
		$queryWithoutWhere = $query;
		if ($options['where']) {
			$qry = 'WHERE ' . $options['where'] . ' ';
			$query .= $qry;
		}
		if ($options['whereUnfiltered']) {
			$queryWithoutWhere .= 'WHERE ' . $options['whereUnfiltered'] . ' ';
		}
		if ($options['groupBy']) {
			$qry = 'GROUP BY ' . $options['groupBy'] . ' ';
			$query .= $qry;
			$queryWithoutWhere .= $qry;
		}
		if ($options['orderBy']) {
			$qry = 'ORDER BY ' . $options['orderBy'] . ' ';
			if ($options['orderType']) {
				$qry .= $options['orderType'] . ' ';
			}
			$query .= $qry;
			$queryWithoutWhere .= $qry;
		}


		if ($options['pager']) {
			$options['limit'] = $options['rowsPerPage'];
		}

		if ($options['limit']) {
			$qry = 'LIMIT ' . $options['limit'];
			$query .= $qry;
		}
		if ($options['pager']) {
			$pager = new massif_pager($options['rowsPerPage'], 'page');
			$cursor = (rex_request($pager->getCursorName(), 'int', 0) - 1) * $options['rowsPerPage'];
			if ($cursor < 0) {
				$cursor = 0;
			}
			$query .= ' OFFSET ' . $cursor;
		}
		//dump($query);

		$data = $sql->getArray('SELECT SQL_CALC_FOUND_ROWS ' . $options['fields'] . $query);
		//rex_var_dumper::dump('SELECT ' . $options['fields'] . $query);
		if (count($data) == 0)
			return;

		if ($options['pager']) {
			$sql->setQuery(' SELECT FOUND_ROWS() as num_rows ');
			$count = (int) $sql->getValue('num_rows');
			$pager->setRowCount($count);
			$fragment = new rex_fragment();
			$fragment->setVar('pager', $pager, false);
			$fragment->setVar('urlprovider', rex_article::getCurrent());
			rex::setProperty('massif-pager', $fragment->parse('massif-pager.php'));
		}


		return $data;
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

	public static function getAnchor($val)
	{
		$anchor = '<a id="' . self::normalize($val) . '" class="nav-anchor"></a>';
		return $anchor;
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

	public static function getUrlWithGetParams($id = null, $clang = null)
	{

		return rex_getUrl($id, $clang, $_GET);
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
	*	Get an array of items from sql
	*/

	public static function getSqlArray($sql)
	{

		self::$sql = rex_sql::factory();
		self::$sql->setQuery($sql);

		echo mysql_error();

		return self::$sql->getArray();
	}

	/*
	*	Get parsed file
	*/

	public static function parse($file, $context = null, $params = [])
	{
		$fragment = new rex_fragment();
		$fragment->setVar('context', $context, false);
		$fragment->setVar('params', $params, false);
		return $fragment->parse($file . ".php");
	}

	/*
	*	get REX slice by ID
	*/

	public static function getSlice($id)
	{

		$slice = rex_article_slice::getArticleSliceById($id);
		return $slice->getSlice();
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
	*	Get all slices from an article by its id
	*/

	public static function getAllSlicesFromArticle($article_id)
	{
		$slice = OOArticleSlice::getFirstSliceForArticle($article_id);
		while ($slice != null) {
			$slices[] = $slice;
			$slice = $slice->getNextSlice();
		}
		return $slices;
	}

	/*
	*	Get all articles from a category by its module id
	*/

	public static function getArticlesFromCategoryByModule($article_id, $module_id, $order_by)
	{
		global $REX;

		$orderby = ($order_by) ? "ORDER BY " . $order_by : "";

		$sql = rex_sql::getInstance();
		$query = "SELECT * FROM " . $REX['TABLE_PREFIX'] . "article WHERE id IN (SELECT article_id FROM " . $REX['TABLE_PREFIX'] . "article_slice WHERE modultyp_id=" . $module_id . ") AND (path LIKE '%|" . $article_id . "|%') AND status=1 $orderby";
		$sql->setQuery($query);
		$numRows = $sql->getRows();

		if ($numRows != 0) {
			for ($i = 0; $i < $numRows; $i++) {
				$articles[$i] = OOArticle::getArticleById($sql->getValue('id'));
				$sql->next();
			}
		}
		if ($articles && is_array($articles))
			return $articles;
		else
			return false;
	}

	/*
	*	Get all images from a media category
	*/

	public static function getFilesFromCat($catId, $random = false)
	{
		$catId = intval($catId);

		$cat = OOMediaCategory::getCategoryById($catId);
		$files = $cat->getFiles();
		$count = count($files);
		if ($count > 0) {
			if ($random) shuffle($files);
			foreach ($files as $file)
				$out[] = $file->getFileName();
			return $out;
		} else
			return false;
	}

	/*
	*	Get one random image from a media category
	*/

	public static function getRandomFileFromCat($catId)
	{
		$catId = intval($catId);

		$cat = OOMediaCategory::getCategoryById($catId);
		$files = $cat->getFiles();
		$count = count($files);
		if ($count > 0) {
			$random = mt_rand(0, $count - 1);
			$randomFile = $files[$random];
			return $randomFile->getFileName();
		} else
			return false;
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


	public static function pageSetSubPaths(\rex_be_page $page, \rex_package $package, $prefix = '')
	{
		foreach ($page->getSubpages() as $subpage) {
			if (!$subpage->hasSubPath()) {
				$subpage->setSubPath($package->getPath('pages/' . $prefix . $subpage->getKey() . '.php'));
			}
			self::pageSetSubPaths($subpage, $package, $prefix . $subpage->getKey() . '.');
		}
	}

	public static function backendNav($head, $pages)
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

	/*
	*	redaxo backend module JS/CSS for prettier module input ;)
	*	TODO: move to src folder and use rex::view
	*/

	public static function getModuleInJsCss()
	{
		return '
		<style type="text/css">
		.btn-set {
			display: flex;
			gap: 20px;
		}
		</style>
		';
	}
	public static function OLD_getModuleInJsCss()
	{
		return '<script type="text/javascript">
				/*
				jQuery(document).ready(function($) {
					
					var $dateFields = $(\'input[data-type="date"]\');
					
					$dateFields.each(function(i){
						
						var $this = $(this);
													
						$this.datepicker({
							dateFormat: "dd.mm.yy",
							altField: $this.data(\'alt-field\'),
							altFormat: "yy-mm-dd",
						    beforeShow: function(input, inst){
			 					$(inst.dpDiv).css({
									marginTop: $this.outerHeight(true)
								});
			 				    return {};
						    },
						    //minDate: new Date(2001, 1 - 1, 1), maxDate: new Date(2010, 12 - 1, 31),
						    showOn: \'both\'
				    	});
				    	
				    	//$year.after($placeholder.next());
						
					});
					
				});
				*/
			</script>
			<style type="text/css">
				
				.mform-tabs > .nav-tabs {
					margin: 0;
				}
				.mform-tabs > .tab-content .tab-content {
					padding-top: 20px;
				}
				.mform-tabs > .tab-content .mform-tabs .tab-content {
					padding: 15px;
					background: white;
				}
				.tox .tox-edit-area__iframe {
					background: #e9ecf2;
				}
				.mblock_wrapper > div {
					border: none;
				}
				.panel-edit,
				.panel-add {
					position: relative;
				}
				hr {
					border: none;
					border-bottom: 1px dotted rgba(0,0,0,0.1);
					margin: -5px 0 10px;
				}
				
				.field-group * {
					box-sizing: border-box;
				}
				
				.field-title,
				.section-title {
					font-size: 16px;
					padding-bottom: 5px;
					margin-bottom: 11px;
					border-bottom: 1px dotted rgba(0,0,0,0.1);
				}
			
				.section-title {
					font-size: 13px;
				}
				
				.content-group {
					margin-bottom: 33px;
				}
			
				.attributes-group {
					background: rgba(0,0,0,0.045);
					padding: 10px; 
					margin: 0 -10px;
				}
				
				.anchor {
					position: absolute;
					right: 15px; top: 55px;
					color: #188d12;
					font-size: 11px;
				}	
				.panel-edit .anchor {
					/*top: 7px;*/
				}
				.anchor .label {
					font-weight: bold;
					color: #c9302c;
					font-size: 11px;
				}
				.anchor .val {
					display: inline-block;
					font-style: italic;
				}
			
						
			
				/* 
				 *	grid 
				*/
				
				.field-group {
					/*overflow-x: hidden;*/
				}
				
				.field-group .row {
					/*overflow: hidden;*/
					/*margin: 0 -15px;*/
				}
				
				.field-group .grid {
					min-height: 250px;
					float: left;
					padding: 0 15px;
					position: relative;
				}
				.field-group .grid:after {
					content: "";
					position: absolute;
					left: 0; top: 0;
					height: 100%;
					width: 1px;
					background: rgba(0,0,0,0.1);
				}
				.field-group .grid:first-child:after {
					display: none;
				}
				.field-group .grid-fourth {
					width: 25%;
				}
				.field-group .grid-three-fourths {
					width: 75%;
				}
				.field-group .grid-third {
					width: 33%;
				}
				.field-group .grid-two-thirds {
					width: 66%;
				}
				.field-group .grid-half {
					width: 50%;
				}
				.field-group .grid-full {
					width: 100%;
				}
			
				/* 
				 *	fields 
				*/
				
				.field-holder {
					margin-bottom: 15.5px;
					/*overflow: hidden;*/
				}
				.field-holder.field-media {
					margin-bottom: 5.5px;
				}
				
				.field-holder label {
					padding-top: 4px !important;
					font-weight: bold !important;
					/*float: left !important;
					width: 60% !important;*/
				}
				
				.field-holder .field {
					/*float: left;
					width: 40%;*/
					width: 100%;
				}
				
				.field-holder.field-checkbox .field {
					width: 20px;
				}
				.field-holder.field-checkbox label {
					width: auto !important;
				}
				/*
				.field-holder.field-text label,
				.field-holder.field-textarea label,
				.field-holder.field-link label {
					padding-top: 6px !important;
					width: 30% !important;
				}
				.field-holder.field-text .field,
				.field-holder.field-textarea .field {
					width: 70% !important;
				}
			
				.grid-two-thirds .field-holder.field-text label,
				.grid-two-thirds .field-holder.field-textarea label {
					width: 20% !important;
				}
				.grid-two-thirds .field-holder.field-text .field,
				.grid-two-thirds .field-holder.field-textarea .field {
					width: 80% !important;
				}
				*/
				.field-holder.field-text .field input,
				.field-holder.field-textarea .field textarea {
					width: 100% !important;
					padding: 4px !important;
				}
				.field-holder.field-textarea .field textarea {
					min-height: 44px !important;
				}
				.field-holder.field-date .field input {
					width: 100px !important;
				}
			
			
				.field-holder.field-tiny label,
				.field-holder.field-tiny .field,
				.field-holder.field-media label,
				.field-holder.field-media .field {
					width: 100% !important;
					float: none !important;
				}
				.field-holder.field-textarea label,
				.field-holder.field-media label {
					margin-bottom: 5px;
				}
				.field-holder.field-media .field .rex-widget {
					margin-bottom: 0 !important;
				}
				.field-holder.field-media .field select {
					width: 227px !important;
				}
				
				fieldset.form-horizontal {
					padding: 15px;
					background: #f7f7f7;
				}
				fieldset.form-horizontal + fieldset.form-horizontal{
					margin-top: 30px;
				}
				fieldset.form-horizontal legend {
					border: none;
					position: relative;
					top: -15px;
					left: -15px;
					margin-bottom: 0;
				}

				/*
				fieldset.form-horizontal legend {
					position: relative;
					top: -11px;
					margin-bottom: 0;
				}
				fieldset.form-horizontal + fieldset.form-horizontal legend {
					top: -11px;
				}
				*/

				fieldset .info {
					clear: both;
					padding-top: 0;
					margin-top: -8px;
					margin-bottom: 20px;
					font-size: 11px;
					font-style: italic;
					width: auto !important;
					float: none !important;
				}
				fieldset .info ol,
				fieldset .info ul {
					padding-left: 20px;
				}

				.flexed {
					display: grid; grid-gap: 15px;
				}
				.flexed.col-2 {
					grid-template-columns: 1.3fr 0.7fr;					
				}
				.flexed.col-3 {
					grid-template-columns: 1fr 1fr 1fr;					
				}
				.flexed.col-4 {
					grid-template-columns: 1fr 1fr 1fr 1fr;					
				}
				.flexed.data-table {
					grid-template-columns: 1fr 1fr;					
				}

				
				/* 
				 *	margins 
				*/
				
				.grid-margins .field-holder {
					padding: 10px;
				}
				.margins {
					margin: 0 auto;
					width: 150px;
					height: 150px;
					position: relative;
				}
				
				.margins .inner,
				.margins .outer,
				.field-margin {
					position: absolute;
				}
				
				.margins .inner,
				.margins .outer {
					top: 50%;
					left: 50%;
					transform: translate(-50%, -50%);
				}
				.margins .inner {
					width: 40px;
					height: 40px;
					border-radius: 3px;
					background: #f9fde9;
					z-index: 2;
					box-shadow: rgba(0,0,0,0.3) 0 1px 1px 0;
					border-top: 1px solid white;
				}
				.margins .outer {
					width: 80px;
					height: 80px;
					border-radius: 5px;
					background: rgba(0,0,0,0.1);
					border: 1px solid rgba(0,0,0,0.1);
					box-shadow: 0 2px 8px 0 rgba(0, 0, 0, 0.05) inset;
				}
				.field-margin {
					z-index: 2;
					text-align: center;
				}
				.field-margin input {
					display: block !important;
					margin: 0 auto;
				}
				.field-holder .field-margin label {
					display: inline-block !important;
					float: none !important;
					width: auto !important;
					padding-top: 0 !important;
					width: auto !important;
				}
				.field-margin-top {
					top: 0;
					left: 50%;
					transform: translateX(-50%);
				}
				.field-margin-right {
					top: 50%;
					right: -12px;
					transform: translateY(-50%);
				}
				.field-margin-bottom {
					bottom: -5px;
					left: 50%;
					transform: translateX(-50%);
				}
				.field-margin-left {
					top: 50%;
					left: -5px;
					transform: translateY(-50%);
				}
				
				

			</style>';
	}
}
