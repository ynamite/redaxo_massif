<?php

namespace Ynamite\Massif;

use rex_article;
use rex_article_slice;
use rex_category;
use rex_factory_trait;
use rex_string;

class Nav
{
  use rex_factory_trait;

  private static ?self $instance = null;

  protected $dropdowns = true;
  protected $dropdownClasses = 'dropdown';
  protected $addDropdownToggles = true;
  protected $dropdownToggleClass = 'transition-transform duration-300 dropdown-arrow';
  protected $dropdownToggleContent = '▼';
  protected $dropdownToggleAriaLabel = 'Untermenü ein- oder ausklappen';
  protected $name = 'desktop'; // Name der Navigation
  protected $depth = 1; // Wieviele Ebene tief, ab der Startebene
  protected $open; // alles aufgeklappt, z.b. Sitemap
  protected $path = [];
  protected $classes = [];
  protected $linkclasses = [];
  protected $filter = [];
  protected $callbacks = [];

  protected $current_article_id = -1; // Aktueller Artikel
  protected $current_category_id = -1; // Aktuelle Katgorie

  private static $CACHE = [];

  protected function __construct()
  {
    // nichts zu tun
  }

  /**
   * @return static
   */
  public static function factory()
  {
    if (self::$instance) {
      return self::$instance;
    }

    return self::$instance = new self();
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
  public function get(
    int $category_id = 0,
    ?int $depth = null,
    bool $open = false,
    bool $ignore_offlines = true,
    string $name = '',
    ?bool $dropdowns = null,
    ?string $dropdownClasses = null,
    ?bool $addDropdownToggles = null,
    string $dropdownToggleClass = '',
    string $dropdownToggleContent = '',
    string $dropdownToggleAriaLabel = ''
  ) {
    if (!$this->_setActivePath()) {
      return false;
    }

    $this->dropdowns = $dropdowns ?? $this->dropdowns;
    $this->dropdownClasses = $dropdownClasses ?: $this->dropdownClasses;
    $this->addDropdownToggles = $addDropdownToggles ?? $this->addDropdownToggles;
    $this->dropdownToggleClass = $dropdownToggleClass ?: $this->dropdownToggleClass;
    $this->dropdownToggleContent = $dropdownToggleContent ?: $this->dropdownToggleContent;
    $this->dropdownToggleAriaLabel = $dropdownToggleAriaLabel ?: $this->dropdownToggleAriaLabel;
    $this->name = $name ?: $this->name;
    $this->depth = $depth ?? $this->depth;
    $this->open = $open;
    if ($ignore_offlines) {
      $this->addFilter('status', 1, '==');
    }

    return $this->_getNavigation($category_id);
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

  protected function checkCallbacks(rex_category $category, int $depth, &$li, &$a, &$label, $name)
  {
    $path = $category->getPathAsArray();
    if (!in_array(rex_article::getCurrentId(), $path)) {
      $path[] = rex_article::getCurrentId();
    }

    $params = [
      'category' => $category,
      'depth' => $depth,
      'li' => &$li,
      'a' => &$a,
      'label' => &$label,
      'name' => $name,
      'path' => $path,
    ];
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
            $result = $class->$method($params);
          } else {
            $result = $class::$method($params);
          }
        } else {
          $result = $callback($params);
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
    $nav_obj = null;
    if ($category_id < 1) {
      $nav_obj = isset(self::$CACHE['root_categories']) ? self::$CACHE['root_categories'] : null;
      if ($nav_obj === null) {
        self::$CACHE['root_categories'] = rex_category::getRootCategories();
        $nav_obj = self::$CACHE['root_categories'];
      }
    } else {
      $cat = isset(self::$CACHE['category_' . $category_id]) ? self::$CACHE['category_' . $category_id] : null;
      if ($cat === null) {
        self::$CACHE['category_' . $category_id] = rex_category::get($category_id);
        $cat = self::$CACHE['category_' . $category_id];
      }
      if (!$cat) {
        return;
      }
      $nav_obj = $cat->getChildren();
    }

    $lis = [];
    foreach ($nav_obj as $nav) {
      $li = [];
      $a = [];
      $label = htmlspecialchars($nav->getName());
      $li['class'] = [];
      $a['class'] = [];
      $a['title'] = htmlspecialchars($nav->getName());
      $a['href'] = [$nav->getUrl()];
      $dropdownToggle = '';
      $hasChildren = !empty($nav->getChildren());
      if ($this->dropdowns && $hasChildren) {
        $li['class'][] = 'has-children';
        $dropdownToggleTag = $this->name === 'desktop' ? 'div' : 'button';
        $dropdownToggleType = $this->name === 'mobile' ? ' type="button"' : '';
        $dropdownToggle = '<' . $dropdownToggleTag . $dropdownToggleType . ' role="button" aria-expanded="false" class="dropdown-toggle" aria-label="' . $this->dropdownToggleAriaLabel . '"><span class="' . $this->dropdownToggleClass . '">' . $this->dropdownToggleContent . '</span></' . $dropdownToggleTag . '>';
      }

      if ($this->checkFilter($nav, $depth) && $this->checkCallbacks($nav, $depth, $li, $a, $label, $this->name)) {
        $liPrepend = $li['prepend'] ?? '';
        $liAppend = $li['append'] ?? '';
        unset($li['prepend']);
        unset($li['append']);
        $aPrepend = $a['prepend'] ?? '';
        $aAppend = $a['append'] ?? '';
        unset($a['prepend']);
        unset($a['append']);
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
        $l .= $liPrepend;
        $l .= '<a ' . implode(' ', $a_attr) . '>' . $aPrepend . '<span>' . $label . '</span>' . $aAppend . '</a>';
        if ($this->addDropdownToggles) {
          $l .= $dropdownToggle;
        }
        ++$depth;
        if (($this->open ||
            $nav->getId() == $this->current_category_id ||
            in_array($nav->getId(), $this->path))
          && ($this->depth >= $depth || $this->depth < 0)
        ) {
          $l .= $this->_getNavigation($nav->getId(), $depth);
        }
        --$depth;
        $l .= $liAppend;
        $l .= '</li>';
        $lis[] = $l;
      }
    }
    if (count($lis) > 0) {
      $out = '<ul class="rex-navi' . $depth . ' rex-navi-depth-' . $depth . ' rex-navi-has-' . count($lis) . '-elements' . '">' . implode('', $lis) . '</ul>';
      if ($this->dropdowns && $depth > 1 && count($lis) > 0) {
        return '<div class="' . $this->dropdownClasses . '">' . $out . '</div>';
      }
      return $out;
    }

    return '';
  }
}
