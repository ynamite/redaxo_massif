<?php

namespace Ynamite\Massif;

use rex;
use rex_article;
use rex_article_slice;
use rex_category;
use rex_clang;
use rex_factory_trait;
use rex_sql;
use rex_string;

class Nav
{

    use rex_factory_trait;

    protected $depth; // Wieviele Ebene tief, ab der Startebene
    protected $open; // alles aufgeklappt, z.b. Sitemap
    protected $path = [];
    protected $classes = [];
    protected $linkclasses = [];
    protected $filter = [];
    protected $callbacks = [];

    protected $current_article_id = -1; // Aktueller Artikel
    protected $current_category_id = -1; // Aktuelle Katgorie

    protected function __construct()
    {
        // nichts zu tun
    }

    /**
     * @return static
     */
    public static function factory()
    {
        $class = self::getFactoryClass();
        return new $class();
    }

    /**
     * Generiert eine Navigation.
     *
     * @param int  $category_id     Id der Wurzelkategorie
     * @param int  $depth           Anzahl der Ebenen die angezeigt werden sollen
     * @param bool $open            True, wenn nur Elemente der aktiven Kategorie angezeigt werden sollen, sonst FALSE
     * @param bool $ignore_offlines FALSE, wenn offline Elemente angezeigt werden, sonst TRUE
     *
     * @return string
     */
    public function get($category_id = 0, $depth = 3, $open = false, $ignore_offlines = true)
    {
        if (!$this->_setActivePath()) {
            return false;
        }

        $this->depth = $depth;
        $this->open = $open;
        if ($ignore_offlines) {
            $this->addFilter('status', 1, '==');
        }

        return $this->_getNavigation($category_id);
    }

    /**
     * @see get()
     */
    public function show($category_id = 0, $depth = 3, $open = false, $ignore_offlines = false)
    {
        echo $this->get($category_id, $depth, $open, $ignore_offlines);
    }

    /**
     * Generiert eine Breadcrumb-Navigation.
     *
     * @param string $startPageLabel Label der Startseite, falls FALSE keine Start-Page anzeigen
     * @param bool   $includeCurrent True wenn der aktuelle Artikel enthalten sein soll, sonst FALSE
     * @param int    $category_id    Id der Wurzelkategorie
     *
     * @return string
     */
    public function getBreadcrumb($startPageLabel, $includeCurrent = false, $category_id = 0)
    {
        if (!$this->_setActivePath()) {
            return false;
        }

        $path = $this->path;

        $i = 1;
        $lis = '';

        if ($startPageLabel) {
            $lis .= '<li class="rex-lvl' . $i . '"><a href="' . rex_getUrl(rex_article::getSiteStartArticleId()) . '">' . htmlspecialchars($startPageLabel) . '</a></li>';
            ++$i;

            // StartArticle nicht doppelt anzeigen
            if (isset($path[0]) && $path[0] == rex_article::getSiteStartArticleId()) {
                unset($path[0]);
            }
        }

        $show = !$category_id;
        foreach ($path as $pathItem) {
            if (!$show) {
                if ($pathItem == $category_id) {
                    $show = true;
                } else {
                    continue;
                }
            }

            $cat = rex_category::get($pathItem);
            $lis .= '<li class="rex-lvl' . $i . '"><a href="' . $cat->getUrl() . '">' . htmlspecialchars($cat->getName()) . '</a></li>';
            ++$i;
        }

        if ($includeCurrent) {
            if ($art = rex_article::get($this->current_article_id)) {
                if (!$art->isStartArticle()) {
                    $lis .= '<li class="rex-lvl' . $i . '">' . htmlspecialchars($art->getName()) . '</li>';
                }
            } else {
                $cat = rex_category::get($this->current_article_id);
                $lis .= '<li class="rex-lvl' . $i . '">' . htmlspecialchars($cat->getName()) . '</li>';
            }
        }

        return '<ul class="rex-breadcrumb">' . $lis . '</ul>';
    }

    /**
     * @see getBreadcrumb()
     */
    public function showBreadcrumb($startPageLabel = false, $includeCurrent = false, $category_id = 0)
    {
        echo $this->getBreadcrumb($startPageLabel, $includeCurrent, $category_id);
    }

    public function setClasses($classes)
    {
        $this->classes = $classes;
    }

    public function setLinkClasses($classes)
    {
        $this->linkclasses = $classes;
    }

    /**
     * Fügt einen Filter hinzu.
     *
     * @param string     $metafield Datenbankfeld der Kategorie
     * @param mixed      $value     Wert für den Vergleich
     * @param string     $type      Art des Vergleichs =/</.
     * @param int|string $depth     "" wenn auf allen Ebenen, wenn definiert, dann wird der Filter nur auf dieser Ebene angewendet
     */
    public function addFilter($metafield = 'id', $value = '1', $type = '=', $depth = '')
    {
        $this->filter[] = ['metafield' => $metafield, 'value' => $value, 'type' => $type, 'depth' => $depth];
    }

    /**
     * Fügt einen Callback hinzu.
     *
     * @param callable   $callback z.B. myFunc oder myClass::myMethod
     * @param int|string $depth    "" wenn auf allen Ebenen, wenn definiert, dann wird der Filter nur auf dieser Ebene angewendet
     */
    public function addCallback($callback, $depth = '')
    {
        if ($callback != '') {
            $this->callbacks[] = ['callback' => $callback, 'depth' => $depth];
        }
    }

    private function _setActivePath()
    {
        $article_id = rex_article::getCurrentId();
        if ($OOArt = rex_article::get($article_id)) {
            $path = trim($OOArt->getPath(), '|');

            $this->path = [];
            if ($path != '') {
                $this->path = explode('|', $path);
            }

            $this->current_article_id = $article_id;
            $this->current_category_id = $OOArt->getCategoryId();
            return true;
        }

        return false;
    }

    protected function checkFilter(rex_category $category, $depth)
    {
        foreach ($this->filter as $f) {
            if ($f['depth'] == '' || $f['depth'] == $depth) {
                $mf = $category->getValue($f['metafield']);
                $va = $f['value'];
                switch ($f['type']) {
                    case '<>':
                    case '!=':
                        if ($mf == $va) {
                            return false;
                        }
                        break;
                    case '>':
                        if ($mf <= $va) {
                            return false;
                        }
                        break;
                    case '<':
                        if ($mf >= $va) {
                            return false;
                        }
                        break;
                    case '=>':
                    case '>=':
                        if ($mf < $va) {
                            return false;
                        }
                        break;
                    case '=<':
                    case '<=':
                        if ($mf > $va) {
                            return false;
                        }
                        break;
                    case 'regex':
                        if (!preg_match($va, $mf)) {
                            return false;
                        }
                        break;
                    case '=':
                    case '==':
                    default:
                        // =
                        if ($mf != $va) {
                            return false;
                        }
                }
            }
        }
        return true;
    }

    protected function checkCallbacks(rex_category $category, $depth, &$li, &$a, &$name)
    {
        foreach ($this->callbacks as $c) {
            if ($c['depth'] == '' || $c['depth'] == $depth) {
                $callback = $c['callback'];
                if (is_string($callback)) {
                    $callback = explode('::', $callback, 2);
                    if (count($callback) < 2) {
                        $callback = $callback[0];
                    }
                }
                if (is_array($callback) && count($callback) > 1) {
                    list($class, $method) = $callback;
                    if (is_object($class)) {
                        $result = $class->$method($category, $depth, $li, $a, $name);
                    } else {
                        $result = $class::$method($category, $depth, $li, $a, $name);
                    }
                } else {
                    $result = $callback($category, $depth, $li, $a, $name);
                }
                if (!$result) {
                    return false;
                }
            }
        }
        return true;
    }

    protected function _getNavigation($category_id, $depth = 1)
    {
        if ($category_id < 1) {
            $nav_obj = rex_category::getRootCategories();
        } else {
            $cat = rex_category::get($category_id);
            if (!$cat) {
                return;
            }
            $nav_obj = rex_category::get($category_id)->getChildren();
        }

        $lis = [];
        foreach ($nav_obj as $nav) {
            $li = [];
            $a = [];
            $name = htmlspecialchars($nav->getName());
            $li['class'] = [];
            $a['class'] = [];
            $a['title'] = htmlspecialchars($nav->getName());
            $a['href'] = [$nav->getUrl()];
            if ($this->checkFilter($nav, $depth) && $this->checkCallbacks($nav, $depth, $li, $a, $name)) {
                $li['class'][] = 'rex-article-' . $nav->getId();
                // classes abhaengig vom pfad
                if ($nav->getId() == $this->current_category_id) {
                    $li['class'][] = 'rex-current';
                    $a['class'][] = 'rex-current';
                } elseif (in_array($nav->getId(), $this->path)) {
                    $li['class'][] = 'rex-active';
                    $a['class'][] = 'rex-active';
                } else {
                    $li['class'][] = 'rex-normal';
                }
                if (isset($this->linkclasses[($depth - 1)])) {
                    $a['class'][] = $this->linkclasses[($depth - 1)];
                }
                if (isset($this->classes[($depth - 1)])) {
                    $li['class'][] = $this->classes[($depth - 1)];
                }
                $li_attr = [];
                foreach ($li as $attr => $v) {
                    $li_attr[] = $attr . '="' . implode(' ', $v) . '"';
                }
                $a_attr = [];
                foreach ($a as $attr => $v) {
                    if (is_array($v))
                        $a_attr[] = $attr . '="' . implode(' ', $v) . '"';
                    else {
                        $a_attr[] = $attr . '="' . $v . '"';
                    }
                }
                $l = '<li ' . implode(' ', $li_attr) . '>';
                $l .= '<a ' . implode(' ', $a_attr) . '>' . $name . '</a>';
                ++$depth;
                if (($this->open ||
                        $nav->getId() == $this->current_category_id ||
                        in_array($nav->getId(), $this->path))
                    && ($this->depth >= $depth || $this->depth < 0)
                ) {
                    $l .= $this->_getNavigation($nav->getId(), $depth);
                }
                if (
                    in_array($nav->getId(), [24, 14]) &&
                    ($nav->getId() == $this->current_category_id ||
                        in_array($nav->getId(), $this->path))
                ) {
                    $slices = rex_article_slice::getSlicesForArticle($nav->getId(), 1, 0, true);
                    $sliceLis = [];
                    foreach ($slices as $slice) {
                        if ($slice->getModuleId() != 163 || $slice->getValue(20) != "1") continue;
                        $label = $slice->getValue(1);
                        $sliceLis[] = '<li class="rex-slice-' . $slice->getId() . '"><a href="' . rex_getUrl($slice->getArticleId(), $slice->getClangId()) . '#' . rex_string::normalize($label) . '-' . $slice->getId() . '">' . $label . '</a></li>';
                    }
                    if (count($sliceLis) > 0) {
                        $l .= '<ul class="rex-navi' . $depth . ' rex-navi-depth-' . $depth . ' rex-navi-has-' . count($sliceLis) . '-elements rex-slice-nav">' . implode('', $sliceLis) . '</ul>';
                    }
                }
                --$depth;
                $l .= '</li>';
                $lis[] = $l;
            }
        }
        if (count($lis) > 0) {
            return '<ul class="rex-navi' . $depth . ' rex-navi-depth-' . $depth . ' rex-navi-has-' . count($lis) . '-elements' . ($depth > 1 ? ' dropdown-menu slideIn dropdown-animation' : '') . '">' . implode('', $lis) . '</ul>';
        }
        return '';
    }

    /*
	*	create navigation from redaxo cats
	*/

    public static function createLangNav($options_user = '')
    {

        if (!is_array($options_user))
            $options_user = array();

        $options = array(
            'navId'                    =>        'lang-nav',                // {string}		ID des äussersten DIVs
            'navClass'                =>        'lang-nav vers',                // {string}		Klasse des äussersten DIVs

            'nowrap'                =>        false,                    // {boolean}	true: der äussere DIV wird entfernt

            'showLangCodeOnly'        =>        true,                    // {boolean}	true: es wird nur der zweistellige Sprach-Code ausgegeben, zBsp. 'De', 'Fr' usw.

            'showDescription'        =>        false,                    // {boolean}	true: für die Sprachnavigation wird ein Label ausgegeben, default Label: 'Sprache'
            'description'            =>        'Sprache',                // {string}		das Label welches ausgegeben werden soll, showDescription muss auf true sein

            'addSeparator'            =>        false,                    // {boolean}	true: zwischen jedem Navigationselement wird ein Trennzeichen angezeigt
            'separatorChar'            =>        '',                    // {string}		das gewünschte Trennzeichen
            'separatorTag'            =>        'span',                    // {string}		HTML-Tag welches das Trennzeichen umschliesst
            'separatorTagClass'        =>        '',                        // {string}		CSS-Klasse für das Trennzeichen-Tag

            'addImages'                =>        false,                    // {boolean}	true: jedem Navigationselement wird ein Bild (aus dem Array images) hinzugefügt
            'imagesInline'            =>        false,                    // {boolean}	true: statt eine CSS-Hintergrundbild, wird ein inline Bild eingefügt
            'images'                =>        array(),                // {array}		ein Array mit Bildpfaden, die Bilder werden schlicht der Reihe nach eingefügt
            'imagesPath'            =>        '/theme/img/',            // {string}		Bildpfad
        );

        // Extending defaults array with user options
        $options = array_merge($options, $options_user);

        $counter = 0;

        $out = '';

        foreach (rex_clang::getAll(true) as $lang) {

            $text = ($options['showLangCodeOnly']) ? $lang->getValue('code') : $lang->getValue('name');

            //$out .= '<li class="lang-item li-lang-'.$lang->getValue('code').' li-lang-'.$lang->getValue('id').'">';
            $url = rex_getUrl(rex_article::getCurrentId(), $lang->getValue('id'));
            $urlManager = rex::getProperty('url-manager-data');
            if ($urlManager) {
                $url = rex_sql::factory()->setTable(rex::getTable('url_generator_url'))
                    ->setWhere('data_id=:data_id AND clang_id=:clang_id', ['data_id' => $urlManager['data-id'], 'clang_id' => $lang->getValue('id')])->select('url')
                    ->getValue('url');
                // $url = rex_getUrl('', $lang->getValue('id'), [$urlManager['profile-ns'] => $urlManager['data-id']]);
                // $url = rex_getUrl(null, $lang->getValue('id'), [$urlManager['profile-ns'] => $urlManager['data-id']]);
                // dump(rex_getUrl(null, 2, [$urlManager['profile-ns'] => $urlManager['data-id']]));
            }

            $out .= '<a href="' . $url;

            $out .= '" title="' . $lang->getValue('name') . '" class="flag-' . $lang->getValue('code') . ' lang-' . $lang->getValue('id');

            if (rex_clang::getCurrentId() == $lang->getValue('id')) {
                $out .= ' active';
            }
            $out .= '"';

            if ($options['addImages'] && !$options['imagesInline'] && isset($options['images'][$counter])) {
                $out .= ' style="background-image: url(' . $options['imagesPath'] . $options['images'][$counter] . ')"';
            }

            $out .= '>';

            if ($options['addImages'] && $options['imagesInline'] && isset($options['images'][$counter])) {
                $out .= '<img src="' . $options['imagesPath'] . $options['images'][$counter] . '" alt="' . $lang->getValue('name') . '" id="lang-img-' . $lang->getValue('id') . '" class="lang-img-' . $lang->getValue('code') . ' lang-img--' . $lang->getValue('id') . '" />';
            }

            $out .= $text . '</a>';
            //$out.= '</li>';

            $counter++;
        }
        //$out = '<ul class="'.$options['navClass'].'-list">' . $out . '</ul>';
        if ($options['showDescription'])
            $out = '<div class="lang-label flag-' . rex_clang::getCurrent()->getValue('code') . ' lang-' . rex_clang::getCurrentId() . '">' . $options['description'] . ' <span class="fa-angle-right fa"></span></div>' . $out;

        if (!$options['nowrap'])
            return '<div class="' . $options['navClass'] . '" id="lang-nav">' . $out . '</div>';
        else
            return $out;
    }


    /*
	*	create nav from articles
	*/

    public static function articles($parent_id = 0, $options_user = array())
    {
        $options['ignoreOnline'] = 1;
        $options['addStartArticle'] = 0;
        $options['include'] = array();
        $options['exclude'] = array(2);
        $options['list'] = false;
        $options['class'] = '';
        $options = array_merge($options, $options_user);
        $items = ($parent_id == 0) ? rex_article::getRootArticles($options['ignoreOnline']) : rex_category::get($parent_id)->getArticles($options['ignoreOnline']);
        if (count($items) == 0) return;
        $out = '<nav class="article-nav';
        if ($options['class']) {
            $out .= ' ' . $options['class'];
        }
        $out .= '">';

        if ($options['list']) {
            $out .= '<ul>';
        }
        if ($options['addStartArticle']) {
            $start_article = rex_article::getSiteStartArticle();
            if ($start_article) {
                $item_out = '<a href="' . $start_article->getUrl() . '" title="' . $start_article->getName() . '" class="article-nav-item';
                if ($start_article->getId() == rex_article::getCurrentId()) {
                    $item_out .= ' active';
                }
                $item_out .= '">' . $start_article->getName() . '</a>';

                if ($options['list']) {
                    $out .= '<li>' . $item_out . '</li>';
                }
            }
        }
        foreach ($items as $item) {
            if ($item->getTemplateId() === 2) continue;
            if (!in_array($item->getId(), $options['exclude']) && !$item->isStartArticle() && (count($options['include']) < 1 || in_array($item->getId(), $options['include']))) {
                $item_out = '<a href="' . $item->getUrl() . '" title="' . $item->getName() . '" class="article-nav-item';
                if ($item->getId() == rex_article::getCurrentId()) {
                    $item_out .= ' active';
                }
                $item_out .= '"';
                $attr = 'target="_self"';
                if ($item->getValue('yrewrite_url_type') === 'REDIRECTION_EXTERNAL')
                    $attr = 'target="_blank" rel="nofollow noopener noreferrer"';


                $item_out .= $attr . '>' . $item->getName() . '</a>';
                if ($options['list']) {
                    $out .= '<li>' . $item_out . '</li>';
                } else {
                    $out .= $item_out;
                }
            }
        }
        if ($options['list']) {
            $out .= '</ul>';
        }
        $out .= '</nav>';

        return $out;
    }
}
