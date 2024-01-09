<?php

/*
function massif_grid_finisher($params) {
	global $REX;
	$output = $params['subject'];
	$output.= $REX['massif']['grid']->finish();
	return $output;
}
*/

class massif_grid {
	
	public $colsDesktop = 12;
	public $colsTablet = 12;
	public $usedColsDesktop = 0;
	public $usedColsTablet = 0;	
	public $rows = 0;
	public $rowOpen = false;
	public $forceClose = false;
	public $numImages = 0;
	public $isPortfolio = false;
	public $isBlog = false;
	public $isOverview = false;
	public $isBlogOverview = false;
	public $blogControlsAdded = false;
	public static $shoulderSpacer = '<div class="spacer shoulder"></div>';
	
	protected $rowOptions = [];
	protected $videoBg = 'video-bg.jpg';
	protected $isFullScreenRow = false;
	protected $isWhiteBgRow = false;
	protected $output = '';
	protected $o;
	protected $spacer = '<div class="spacer lg"></div>';
	protected $spacerHalf = '<div class="spacer md"></div>';
	protected $articleId;
	protected $sliceId;
	protected $link = '';
	protected $linkExt = false;
	protected $isBackend = false;
	protected $sizes = array(
		'third' => '33%',
		'half' => '50%',
		'two-thirds' => '66%',
		'full' => '100%'
	);
	protected $legacy = false;
	
	public function __construct() {
		
		$this->isBackend = rex::isBackend();
		return $this;		
	}
	
	public function addRow($_options = []) {
		
		$this->rowOptions = $_options;
				
		$out = '';

		if( $this->o['attributes']['grid-fullscreen'] || $_options['row-fullscreen']) {
			$this->isFullScreenRow = true;
		}

		if( rex::getProperty('view') == 'blog-overview' ) {
			$this->isBlogOverview = true;
		}


		
		if(!$this->rowOpen && (rex::isFrontend() || $this->legacy == 0)) {

			//var_dump('OPEN row');
			
			$this->rowOpen = true;

			$classes = [];
			
			$classes[] = 'row-o';
			$classes[] = 'row-mg';
			if( $this->isOverview ) {
				$classes[] = 'projects-overview';
			} elseif( $this->isPortfolio ) {
				$classes[] = 'project-detail';
			} elseif( $this->isBlogOverview ) {
				$classes[] = 'blog-overview';
			} elseif( $this->isBlog ) {
				$classes[] = 'blog-detail'; 
			}
			if( $this->isFullScreenRow ) {
				$classes[] = 'no-pad';
			}			
			if($_options['row-bg-color']) {
				$bgClasses = [1 => 'bg-color-light', 2 => 'bg-color-main', 3 => 'row-tert', 4 => 'row-alt'];
				$classes[] = $bgClasses[$_options['row-bg-color']];
			}
			/*
			if($_options['row-width']) {
				$classes[] = 'row-width-'.$_options['row-width'];
			}
			*/
			if($_options['row-win-height']) {
				$classes[] = 'row-win-height valign-content';
			}

			$classes[] = 'row-st-'.$_options['row-space-top'];
			$classes[] = 'row-sb-'.$_options['row-space-bot'];


			$out.= '<div class="'.implode(' ', $classes).'"';
			if($_options['row-bg-color'] && rex::isBackend()) {
				$out.= ' style="background-color:#E8E8E8; padding: 15px;"';
			}
			$out.= '>';

			if(!rex::getProperty('has-visual')) {
				if(!$_options['row-win-height']){
					//$out.= self::$shoulderSpacer;
					rex::setProperty('has-visual', true);
				}
			}

			$classes = [];
			if($_options['row-width']) {
				$classes[] = 'wrap-'.$_options['row-width'];
			}
			
			if( !$this->isFullScreenRow ) { 
				$out.= '<div class="wrap '.implode(' ', $classes).'">';
			}

			if( $this->rowOptions['row-heading'] ) {
				$tag = rex::getProperty('has-text-block') ? 'h2' : 'h1';
				$style = [];
				if($this->rowOptions['row-heading-style'])
					$style[] = $this->rowOptions['row-heading-style'];
				else {
					$style = ['h1'];
				}
				if($this->rowOptions['row-heading-align']) {
					$style[] = $this->rowOptions['row-heading-align'];
				}
				$out.= '<header class="sec-heading">';
				$out.= '<'.$tag.' class="'.implode(' ', $style).' weight-light">'.$this->rowOptions['row-heading'].'</'.$tag.'>';
				$out.= $this->addSpacer(true);
				$out.= '</header>';
				rex::setProperty('has-text-block', true);
			} 

			$out.= '<div class="grid grid-tablet';
			/*
			$out.= ' justify-content-'.$this->rowOptions['row-justify-content'];
			if($this->rowOptions['row-cols-equal-height']) {
				$out.= ' cols-equal-height';
			}
			*/
			$out.= '"';
			if(rex::isBackend()) {
				$out.= ' style="display: grid; grid-gap: 15px; grid-template-columns: '.str_repeat('1fr ', $this->rowOptions['row-num-cols']).'"';
			}
			$out.= '>';
				
	
			$this->rows++;
			
		}
			
		return $out;
		
	}
	
	public function closeRow() {
		
		$out = '';

		if($this->rowOpen && (rex::isFrontend() || $this->legacy == 0)) {

			
			$this->rowOpen = false;
			$this->forceClose = false;
			
			// end grid
			$out.= '</div>';
			
			if((($this->rowOptions['row-bg-color'] == 1 || $this->rowOptions['row-bg-color'] == 3) || $this->rowOptions['row-margin-bot']) && !$this->rowOptions['row-win-height']) {
				//$out.= $this->addSpacer();
			}


			if( $this->rowOptions['row-btn-url'] ) {
				$link = massif_utils::getCustomLink($this->rowOptions['row-btn-url']);
				if( $link && $this->rowOptions['row-btn-label'] ) {
					$out.= '<div class="btn-set">';
						$out.= '<a href="' . $link['url'] . '"'.$link['target'];
						$out.= 'class="button">' . nl2br($this->rowOptions['row-btn-label']) . '</a>'; 
					$out.= '</div>'; 
				}
			} 


			if( !$this->isFullScreenRow ) {
				// end wrap
				$out.= '</div>';
			}

			if($this->rowOptions['row-win-height'] && rex_article::getCurrent()->getValue('art_blog_display')!=2) {
				$out.= '<a href="'.rex_getUrl(rex_article::getCurrentId()).'#jump" class="indicator" title="Zum Inhalt springen"><span class="hide">Jump to content</span><i class="icon icon-angle-down"></i></a>';
			}

			// end row-o
			$out.= '</div>';

			/*if($this->rowOptions['row-win-height']) {
				$out.= '<a id="jump" class="jump-skip';
				if($this->rowOptions['row-bg-color'] == 1 || $this->rowOptions['row-bg-color'] == 3) {
					$out.= ' no-margin';
				}
				$out.= '"></a>';
			}*/

		}
		
		$this->rowOptions = [];
		$this->isFullScreenRow = false;

		//$out.= 			var_dump('CLOSE row');
		return $out;
	}
	
	public function addGrid( $sliceId, $options = array() ) {
		//$content = '', $gridSize = array(), $attributes = array() 
		//print_r($options);
		if($this->legacy == 0) {
			$this->forceClose = true;
		}
		$this->legacy = 1;

				
		$this->articleId = rex_article::getCurrentId();
		$this->sliceId = $sliceId;
		$this->link = '';
		$this->linkExt = false;

		$widthReplacements = [
			'def' => 4,
			'third' => 4,
			'half' => 6,
			'two-thirds' => 8,
			'full' => 12
		];


		foreach ($options['attributes'] as $key => $val) {
			if(($key == 'grid-width' || $key == 'grid-width-tablet') && !(int)$options['attributes'][$key]) {
				$options['attributes'][$key] = $widthReplacements[$options['attributes'][$key]];
			} else {
				continue;
			}
		}

		$this->o = $options;
		
		return $this->addRow() . $this->_addGrid();
		
	}
	
	public function addGrid_v2( $sliceId, $options = array() ) {
		//$content = '', $gridSize = array(), $attributes = array() 
		//print_r($options);
		if($this->legacy == 1) {
			$this->forceClose = true;
		}
		$this->legacy = 0;
				
		$this->articleId = rex_article::getCurrentId();
		$this->sliceId = $sliceId;
		$this->link = '';
		$this->linkExt = false;
		
		$replacements = [
			'buttonlabel' => 'button-label',
			'imagelegend' => 'image-legend',
			'textoverlay' => 'text-overlay',
			'imagelightbox' => 'image-lightbox',
			'gridwidth' => 'grid-width',
			'gridwidthtablet' => 'grid-width-tablet',
			'gridmargintop' => 'grid-margin-top',
			//'gridmarginright' => 'grid-margin-right',
			'gridmarginbottom' => 'grid-margin-bottom',
			//'gridmarginleft' => 'grid-margin-left', 
			'gridoptionbgwhite' => 'grid-option-bg-white',
			'gridoptionbg' => 'grid-option-bg',
			'gridoptionforcehidedesktop' => 'grid-option-force-hide-desktop',
			'gridoptionforcehidemobile' => 'grid-option-force-hide-mobile',
			'gridoptioncleardesktop' => 'grid-option-clear-desktop',
			'gridoptioncleartablet' => 'grid-option-clear-tablet',
			'imgmaxwidth' => 'img-max-width',
			'imgalign' => 'img-align',
			'imgrestrictheight' => 'img-restrict-height',
			'imginlinesvg' => 'img-inline-svg',
			'linkstyle' => 'link-style',
			'linkfullcol' => 'link-full-col',

		];

		foreach ($replacements as $key => $val) {
			$options['attributes'][$val] = $options['attributes'][$key];
			$options['strings'][$val] = $options['attributes'][$key];
			$options['media'][$val] = $options['attributes'][$key];
			unset($options['attributes'][$key]);
			unset($options['attributes'][$key]);
			unset($options['attributes'][$key]);
		}
	
		$widthReplacements = [
			'def' => 4,
			'third' => 4,
			'half' => 6,
			'two-thirds' => 8,
			'full' => 12
		];
		foreach ($options['attributes'] as $key => $val) {
			if(($key == 'grid-width' || $key == 'grid-width-tablet') && !(int)$options['attributes'][$key]) {
				$options['attributes'][$key] = $widthReplacements[$options['attributes'][$key]];
			} else {
				continue;
			}
		}
		
				
		$this->o = $options;

		if(!$this->o['attributes']['link-style']) {
			$this->o['attributes']['link-style'] = 'norm';
		}
		if(!$this->o['attributes']['img-align']) {
			$this->o['attributes']['img-align'] = 'left';
		}

		if($this->o['custom-btn-url']) {
			$link = massif_utils::getCustomLink($this->o['custom-btn-url']);

			if($link['type']=='internal') {
				$this->o['button-link'] = $link['url'];
			}
			if($link['type']=='external') {
				$this->o['strings']['button-link-ext'] = $link['url'];
			}
			if($link['type']=='media') {
				$this->o['button-media'] = $link['url'];
			}			
		} 
		
		if($this->o['custom-img-url']) {
			$link = massif_utils::getCustomLink($this->o['custom-img-url']);
			if($link['type']=='internal') {
				$this->o['image-link'] = $link['url'];
			}
			if($link['type']=='external') {
				$this->o['strings']['image-link-ext'] = $link['url'];
			}
			if($link['type']=='media') {
				$this->o['image-file-link'] = $link['url'];
			}			
		} 
		
		return $this->_addGrid();
		
	}
	
	public function addSpacer($size = '') {
		
		if(!$size) {
			return $this->spacer;
		} else if($size === true) {
			return $this->spacerHalf;
		} else {
			return '<div class="spacer '.$size.'"></div>';
		}
		
	}
	
	protected function _addGrid() {
		
		$out = '';
		
		$classes = [];
		
		//$classes[] = 'grid';
		$classes[] = 'col-' . $this->o['attributes']['grid-width'];
		$classes[] = 'col-' . $this->o['attributes']['grid-width-tablet'].'-t';
		$this->usedColsDesktop = $this->usedColsDesktop + $this->o['attributes']['grid-width'];
		$this->usedColsTablet = $this->o['attributes']['grid-width-tablet'] ? $this->usedColsTablet + $this->o['attributes']['grid-width-tablet'] : $this->usedColsTablet + $this->o['attributes']['grid-width'];
		if($this->usedColsDesktop>=$this->colsDesktop) {
			$this->usedColsDesktop = $this->o['attributes']['grid-width'];
		}
		if($this->usedColsTablet>=$this->colsTablet) {
			$this->usedColsTablet = $this->o['attributes']['grid-width-tablet'] ? $this->o['attributes']['grid-width-tablet'] : $this->o['attributes']['grid-width'];
		}
		$clearDesktop = $this->colsDesktop - $this->usedColsDesktop;
		$clearTablet = $this->colsTablet - $this->usedColsTablet;
			
		if($this->o['attributes']['grid-margin-right'])
			$classes[] = 'm-right';			
		if($this->o['attributes']['grid-margin-left'])
			$classes[] = 'm-left';
			 
		if($this->o['attributes']['grid-option-force-hide-desktop'])
			$classes[] = 'hide-on-desktop';
		if($this->o['attributes']['grid-option-force-hide-mobile'])
			$classes[] = 'hide-on-mobile';
		if($this->o['attributes']['grid-option-clear-desktop'])
			$classes[] = 'clear-on-desktop clear-on-desktop-'.$clearDesktop;
		if($this->o['attributes']['grid-option-clear-tablet'])
			$classes[] = 'clear-on-tablet clear-on-tablet-'.$clearTablet;
			
		if($this->o['strings']['text'] || $this->o['strings']['button-label'])
			$classes[] = 'has-text';
		$hasVideo = false;
		$this->o['video'] = array_filter($this->o['video']);
		if(count($this->o['video'])>0) {
			$hasVideo = true;
		}
		//print_r($this->o['video']);
		
		$this->numImages = count($this->o['images']);
		// check if we have images only, no text
		if($this->numImages > 0 && !$this->o['strings']['text'] && !$this->o['strings']['button-label'])
			$classes[] = 'img-only';

		if($this->o['button-link']) {
			$this->link = $this->o['button-link'];
		} 
		else if($this->o['strings']['button-link-ext']) {
			$this->link = $this->o['strings']['button-link-ext'];
			$this->linkExt = true;
		}
		else if($this->o['button-media']) {
			$this->link = '/media/' . $this->o['button-media'];
			$this->linkExt = true;
		}

		if($this->o['image-link']) {
			$this->link = rex_getUrl($this->o['image-link']);
		}
		elseif($this->o['strings']['image-link-ext']) {
			$this->link = $this->o['strings']['image-link-ext'];
			$this->linkExt = true;
		}
		elseif($this->o['image-file-link']) {
			$this->link = '/media/' . $this->o['image-file-link'];
			$this->linkExt = true;
		}
		
		if($this->o['media']['image-lightbox']) {
			$this->link = massif_img::getPath($this->o['images'][0], 'lightbox');
		}

		$classes[] = 'img-justify-'.$this->o['attributes']['img-align'];
		
		$tag = ($this->link) ? 'article' : 'div';

					
		$out.= '<' . $tag . ' data-slice="s-'.$this->sliceId.'"';
		if($this->isBackend){
			$out.= ' style="position:relative;border:1px dashed lightgray;padding:8px 16px 4px;" ';
		}
		/*if($this->sliceId) 
			$out.= 'id="cont-' . $this->sliceId . '" ';*/
			
		$out.= ' class="' . implode(" ", $classes) . '">';

			if($this->o['attributes']['grid-margin-top'])
				$out.= $this->addSpacer('grid');

			/*
			if($this->sliceId) 
				$out.= '<div id="cont-' . $this->sliceId . '" style="transform:translateY(-90px)"></div>';
			*/
			/*if($this->link && ($this->numImages == 0)) {
				$out.= '<a href="' . $this->link . '" title="'.$this->o['media']['image-legend'].'" class="img-anchor has-zoom">';
			}
			*/
			if($this->isBackend) {
				$out.= $this->_addAnchorInfo();
			}
			if($hasVideo) {
				$out.= $this->_addVideo();
			} else if($this->numImages > 0) {
				$out.= $this->_addImages();
			}
			
			$out.= $this->_addText();	
				
			//$out.= '<div class="grid-inner">' . $this->o['attributes']['grid-width'] . '</div>';
			//$out.= print_r($this->o['attributes']) . print_r($this->o['media']);
			/*if($this->link && ($this->numImages == 0)) {
				$out.= '</a>';
			}*/

			$out.= $this->_addSizeInfo();

			if($this->o['attributes']['grid-margin-bottom'])
				$out.= $this->addSpacer('grid');

		$out.= '</' . $tag . '>';



		return $out;
		
	}
	
	protected function _addText() {

		$classes = array();
		$classes[] = 'tile-text';
		if($this->o['attributes']['grid-option-bg-white'])
			$classes[] = 'bg-alt';
		if($this->o['attributes']['grid-option-bg'])
			$classes[] = 'bg-'.$this->o['attributes']['grid-option-bg'];
		
		if($this->o['strings']['text'] || $this->o['strings']['button-label']) {
			if($this->o['strings']['text']) {
				if($this->link /* && $this->o['attributes']['grid-option-bg-white']*/ && !$this->isBlogOverview) {
					$out = '<a href="' . $this->link . '" title="'.$this->o['media']['image-legend'].'"';
					if($this->linkExt) {
						$out.= ' target="_blank"';
					}
				} else {
					$out = '<div';
				}
				$out.= ' class="' . implode(" ", $classes) . '">';
				/*
				if($this->isBlogOverview && !$this->blogControlsAdded && $this->articleId==9) {
					$out.= $this->_addBlogControls();	
					$this->blogControlsAdded = true;	
					$this->isBlogOverview = false;		
				}*/
				if($this->o['strings']['title']) {
					$tag = rex::getProperty('has-text-block') ? 'h2' : 'h1';
					$out.= '<header class="sec-heading">';
					$out.= '<'.$tag.' class="h1 vers">'.$this->o['strings']['title'].'</'.$tag.'>';
					$out.= $this->addSpacer('med');
					$out.= '</header>';
				}
				$out.= '<div class="tile-text-poser';
				if($this->o['strings']['multicols'])
					$out.= ' multicols';
				$out.= '">';
					$out.= hyphenator::hyphenate(massif_settings::replaceStrings($this->o['strings']['text']));	
					if(!$this->o['strings']['button-label'])		
						$out.= $this->_addButton();
				$out.= '</div>';
			}

			if($this->link /* && $this->o['attributes']['grid-option-bg-white']*/ && !$this->isBlogOverview) {
				$out.= '</a>';
				if($this->o['strings']['button-label'])		
					$out.= $this->_addButton();

			} else {
				$out.= '</div>';
			}
		}
		return $out;
	}

	protected function _addVideo() {		

		$out = '';
		
		$media = rex_media::get($this->o['video']['mp4']);
		$mask = rex_media::get($this->o['media']['REX_MEDIA_3']);

		if(!$this->isBackend && $media) {
			$webm_media = rex_media::get(str_replace('.'.$media->getExtension(), '.webm', $media->getFilename()));
			if($mask) {
				$out.= '<div class="video-frame">';
			}
			$out.= '<video class="lazyload video" preload="none"';
			if($this->o['images'][0]) {
				$out.= ' data-poster="' . massif_img::getPath($this->o['images'][0], 'visual-2020') . '"';
			}
			if($mask) {
				$out.= ' data-autoplay="" playsinline="" -webkit-playsinline="" preload="none" loop=""';
				$out.= ' style="left:'.intval($mask->getValue('video_x')).'%;top:'.intval($mask->getValue('video_y')).'%;max-width:'.intval($mask->getValue('video_w')).'%;"';
			} else {
				$out.= ' controls';
			}
			$out.= '>';
			if($webm_media) {
				$out.= '<source src="'.$webm_media->getUrl().'" type="video/webm">';
			}
			$out.= '<source src="'.$media->getUrl().'" type="video/mp4">';
			$out.= '</video>';
			if($mask) {
				$out.= '<div class="video-mask">'.massif_img::get($mask->getFilename()).'</div>';
				$out.= '</div>'; // video-frame
			}
			/*
			$out.= '<div class="video-frame make-appear" ';
			if($this->o['video']['mp4'])
				$out.= 'data-mp4="/media/' . $this->o['video']['mp4'] . '" ';
			if($this->o['video']['webm'])
				$out.= 'data-webm="/media/' . $this->o['video']['webm'] . '" ';
			if($this->o['video']['ogv'])
				$out.= 'data-ogv="/media/' . $this->o['video']['ogv'] . '" ';
			$out.= 'data-poster="' . massif_img::getPath($this->o['images'][0], $imgType) . '"';
			$out.= '>';
			$out.= '</div>';
			*/
			//$out.= $this->_addImages();
		} else {
			$out.= massif_img::get($this->o['images'][0]);
		}

		/*
		if(!$isSlider) {
			if($inline) {
				$img = massif::getImg($this->o['images'][0], $imgType);
			} else {
				$img = massif::getBgImg($this->o['images'][0], $imgType);
			}
			//$img = massif::getBgImg($this->o['images'][0], $imgType);
		}
		else {
			$img = $this->_addSlider($inline);
		}

		if($this->link && !$isSlider) {
			if(!$this->isBackend && !$this->isBlogOverview) {
				$out.= '<a href="' . $this->link . '" ';
				if( $this->linkExt ) 
					$out.= 'target="_blank" rel="nofollow" ';
				$out.= 'title="' . $this->o['media']['image-legend'] . '" class="img-anchor"';
				if($this->o['media']['image-lightbox']) {
					$out.= ' data-featherlight="image"';
				}
				$out.= '>';
				if($this->o['media']['text-overlay']) {
					$out.= '<div class="ol"><h2 class="ol-pad">' . $this->o['media']['text-overlay'] . '</h2></div>';
				}
			}
			$out.= $img;
			if(!$this->isBackend && !$this->isBlogOverview) {
				$out.= '</a>';	
			}
		} else {
			if($isSlider) {
				$out.= '<a href="javascript:;" title="Nächstes Bild" class="slide-control">';
				$out.= $img;
				$out.= '</a>';
			} else {
				$out.= $img;
			}
		}
		*/
		
		return $out;
	}

	protected function _addImages($inline = true) {		

		$isSlider = $this->numImages > 1 ? true : false;

		$imgParams = ['inline-svg' => $this->o['attributes']['img-inline-svg']];

		$out = '';
		//$imgType = ($this->isFullScreenRow) ? 'grid-full' : 'grid-' . $this->o['attributes']['grid-width'];
		if($this->isFullScreenRow && ($this->o['attributes']['grid-width'] >= '8'))
			$imgParams['type'] = 'visual';
			
		if(!$isSlider) {
			
			$media = rex_media::get($this->o['images'][0]);
			if($media) {
				if($media->getExtension()=='json') {
					$img = '<div class="lottie" data-json="'.$media->getUrl().'"></div>';
				} else {
					$img = massif_img::get($this->o['images'][0], $imgParams);
				}
			}
		}
		else {
			//$img = $this->_addSlider($inline);
			$img = massif_img::getFlexSlider($this->o['images'], $imgParams);
		}

		if($img){
			$imgWrap = '<div class="img-wrap"';
			$imgWrap.= '>';
			$imgWrap.= '<div class="img-wrap-inner';
			if($this->o['attributes']['img-restrict-height']) {
				$imgWrap.= ' restrict-height';
			}
			$imgWrap.= '"';
			if($this->o['attributes']['img-max-width']) {
				$imgWrap.= ' style="max-width: '.$this->o['attributes']['img-max-width'].'"';
			}
			$imgWrap.= '>'.$img.'</div>';
			$imgWrap.= '</div>';
			$img = $imgWrap;
			if($this->link && !$isSlider) {
				if(!$this->isBackend && !$this->isBlogOverview) {
					$out.= '<a href="' . $this->link . '" ';
					if( $this->linkExt ) 
						$out.= 'target="_blank" rel="nofollow" ';
					$out.= 'title="' . $this->o['media']['image-legend'] . '" class="img-anchor';
					if($this->o['image-file-link'])
						$out.= ' has-moveup';
					else
						$out.= ' has-zoom';
					$out.= '"';
					if($this->o['media']['image-lightbox']) {
						$out.= ' data-featherlight="image"';
					}
					$out.= '>';
					if($this->o['media']['text-overlay']) {
						$out.= '<div class="ol"><p class="h2 ol-pad">' . $this->o['media']['text-overlay'] . '</p></div>';
					}
				}
				$out.= $img;
				if($this->isBlogOverview && $this->o['strings']['bonus']) {
					$out.= '<div class="blog-bonus"><b>Bonus</b></div>';
				}
				if(!$this->isBackend && !$this->isBlogOverview) {
					$out.= '</a>';	
				}
				if($this->isBackend && $this->o['media']['text-overlay']) {
					$out.= '<h3>' . $this->o['media']['text-overlay'] . '</h3>';
				}
			} else {
				if($isSlider) {
					$out.= '<a href="javascript:;" title="Nächstes Bild" class="slide-control">';
					$out.= $img;
					$out.= '</a>';
				} else {
					$out.= $img;
				}
			}	
		}
		
		//if($this->o['attributes']['grid-option-bg']/*$this->o['attributes']['grid-option-bg-white']*/) {
			//$out = '<div class="img-bg-alt bg-alt">'.$out.'</div>';
			/*if($this->link) {
				unset($this->link);
			}*/
		//}
		
		return $out;
	}
	
	protected function _addSlider($inline = true) {
		
		$imgType = ($this->isFullScreenRow) ? 'grid-fs-' . $this->o['attributes']['grid-width'] : 'grid-' . $this->o['attributes']['grid-width'];

		$out = '<ul class="rslides"';
		if($this->isBackend) {
			$out.= ' style="list-style:none;overflow:hidden;margin:0;padding:0;"';
		}
		$out.= '>';
		foreach($this->o['images'] as $img) {
			$out.= '<li';
			if($this->isBackend) {
				$out.= ' style="float: left; margin: 0 10px 10px 0;"';
			}
			$out.= '>';

			$out.= massif_img::get($img);
			//$out.= massif::getBgImg($img, $imgType);
			$out.= '</li>';
		}
		$out.= '</ul>';
				
		return $out;
		
	}
	
	protected function _addButton() {
		$out = '';

		if( $this->link && $this->o['strings']['button-label']) {
			$out.= '<div class="btn-set abs">';
				$out.= '<a href="' . $this->link . '" ';
				if( $this->linkExt ) 
					$out.= 'target="_blank" rel="nofollow" ';
				$out.= 'class="button">' . nl2br($this->o['strings']['button-label']) . '</a>'; 
			$out.= '</div>'; 
		}
		return $out;
		
	}
	
	protected function _addSizeInfo() {
		if(!$this->isBackend) return;
		$fs = '/12';
		return '<h3 class="rex-hl4" style="margin: 0;position:absolute;right:-1px;bottom:-1px;font-size:11px;margin-top:-1px;padding:4px;border:1px solid lightgray;background:#f1fcfa;text-transform:uppercase;"><b>' . $this->o['attributes']['grid-width'] . $fs . '</b></h3><div style="clear:both"></div>';
	}
	
	protected function _addAnchorInfo() {
		if($this->sliceId) {
			//return '<p style="font-size:11px;color:#188d12;float:right;clear:right"><b>Anker:</b> <span class="val">' . trim(seo42::getFullUrl($this->articleId)) . '#a' . $this->sliceId . '</span></p>';
			return '';
		} else {
			return '';
		}
	}
	
	protected static function __deconstruct() {
	}
	
}



?>