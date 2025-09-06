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

  protected $dropdowns = true;
  protected $addDropdownToggleClass = 'transition-transform duration-300 dropdown-arrow iconify bi--chevron-down';
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
  public function get(
    int $category_id = 0,
    ?int $depth = null,
    bool $open = false,
    bool $ignore_offlines = true,
    string $name = '',
    ?bool $dropdowns = null,
    string $dropdownToggleClass = ''
  ) {
    if (!$this->_setActivePath()) {
      return false;
    }

    $this->dropdowns = $dropdowns ?? $this->dropdowns;
    $this->addDropdownToggleClass = $dropdownToggleClass ?: $this->addDropdownToggleClass;
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
    $params = [
      'category' => $category,
      'depth' => $depth,
      'li' => &$li,
      'a' => &$a,
      'label' => &$label,
      'name' => $name,
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
        $dropdownToggle = '<' . $dropdownToggleTag . $dropdownToggleType . ' aria-expanded="false" class="dropdown-toggle" aria-label="Untermenü ein- oder ausklappen"><span class="' . $this->addDropdownToggleClass . '"></span></' . $dropdownToggleTag . '>';
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
        $l .= $dropdownToggle;
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
        $l .= $liAppend;
        $l .= '</li>';
        $lis[] = $l;
      }
    }
    if (count($lis) > 0) {
      return '<ul class="rex-navi' . $depth . ' rex-navi-depth-' . $depth . ' rex-navi-has-' . count($lis) . '-elements' . '">' . implode('', $lis) . '</ul>';
    }
    return '';
  }
}
