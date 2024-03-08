<?php

namespace Ynamite\Massif_be;

use rex_addon;
use rex_be_controller;
use rex_context;
use rex_clang;
use rex_yform_manager_table;
use rex_yform_manager;
use Exception;
use rex_view;
use rex_extension;
use rex_csrf_token;
use rex_type;

class package
{

  public $clangId;

  protected $page;
  protected $package;
  protected $packageId;
  protected $pageKey;
  protected $bePage;
  protected $subpageKey;
  protected $customPage;
  protected $customPagePath;
  protected $fromPackage;
  protected $tableName;
  protected $yformTable;
  protected $subpage;
  protected $filterKey;
  protected $filterValue;

  protected $context;

  public function __construct(rex_addon $package)
  {
    $this->clangId = rex_request('clang_id', 'int', rex_clang::getCurrentId());
    $this->package = $package;
    $this->packageId = $package->getPackageId();
    $this->pageKey = rex_be_controller::getCurrentPagePart(2);
    $this->bePage = rex_be_controller::getCurrentPageObject();
    $this->subpageKey = $this->bePage->getKey();
    $this->customPage = $package->getProperty('page')['subpages'][$this->pageKey]['customPage'] ?? false;
    $this->customPagePath = $package->getProperty('page')['subpages'][$this->pageKey]['customPagePath'] ?? false;
    $this->fromPackage = $package->getProperty('page')['subpages'][$this->pageKey]['package'] ?? $this->packageId;
    $this->tableName = $package->getProperty('page')['subpages'][$this->pageKey]['yform/table'] ?? null;
    $this->subpage = $package->getProperty('page')['subpages'][$this->pageKey]['subpages'][$this->subpageKey] ?? null;
    $this->filterKey = $package->getProperty('page')['subpages'][$this->pageKey]['subpages'][$this->subpageKey]['filterKey'] ?? null;
    $this->filterValue = $package->getProperty('page')['subpages'][$this->pageKey]['subpages'][$this->subpageKey]['filterValue'] ?? null;
    if ($this->subpage) {
      if ($this->subpageKey) {
        $this->customPage = $this->subpage['customPage'] ?? false;
        $this->customPagePath = $this->subpage['customPagePath'] ?? false;
        $this->fromPackage = $this->subpage['package'] ?? $this->packageId;
        if (isset($this->subpage['yform/table'])) $this->tableName = $this->subpage['yform/table'];
      }
    }

    if ($this->customPagePath) {
      $this->customPage = true;
      $this->subpage = rex_be_controller::getCurrentPageObject();
      $this->subpage->setSubPath($this->package->getAddon()->getPath('pages/' . $this->customPagePath));
    } else if ($this->tableName) {
      $this->yformTable = rex_yform_manager_table::get($this->tableName);

      $_csrf_key = $this->yformTable->getCSRFKey();
      $token = rex_csrf_token::factory($_csrf_key)->getUrlParams();

      $this->context = new rex_context([
        'page' => rex_be_controller::getCurrentPage(),
        'clang' => $this->clangId,
        'func' => rex_request('func', 'string', ''),
        'data_id' => rex_request('data_id', 'int', 0),
        'tableName' => rex_request('tableName', 'string', ''),
        array_key_first($token) => array_shift($token)
      ]);

      if ($this->filterKey && $this->filterValue) {
        $_REQUEST['rex_yform_filter'][$this->filterKey] = $this->filterValue;
      }

      $this->registerExtensionPoints();
    }
  }

  protected function registerExtensionPoints()
  {

    if ($this->tableName) {
      rex_extension::register('YFORM_MANAGER_DATA_PAGE_HEADER', function (\rex_extension_point $ep) {
        $ep->setSubject('');
      });
    }

    rex_extension::register('YFORM_DATA_LIST_QUERY', function (\rex_extension_point $ep) {

      $query = $ep->getSubject();
      $filter = $ep->getParam('filter');

      $query->where('clang_id', $this->clangId);
      $filter['clang_id'] = $this->clangId;

      $ep->setSubject($query);
      $ep->setParam('filter', $filter);
    });

    rex_extension::register(['YFORM_MANAGER_DATA_EDIT_FILTER', 'YFORM_MANAGER_DATA_EDIT_SET'], function (\rex_extension_point $ep) {

      $filter = $ep->getSubject();
      $ep->setSubject(array_merge($filter, ['clang_id' => $this->clangId]));
    });

    rex_extension::register('YFORM_DATA_LIST_QUERY', function (\rex_extension_point $ep) {

      $query = $ep->getSubject();
      $filter = $ep->getParam('filter');

      $query->where('clang_id', $this->clangId);
      $filter['clang_id'] = $this->clangId;

      $ep->setSubject($query);
      $ep->setParam('filter', $filter);
    });

    rex_extension::register(['YFORM_MANAGER_DATA_EDIT_FILTER', 'YFORM_MANAGER_DATA_EDIT_SET'], function (\rex_extension_point $ep) {
      $filter = $ep->getSubject();
      $ep->setSubject(array_merge($filter, ['clang_id' => $this->clangId]));
    });
  }

  public function getPage()
  {
    $out = '';
    if ($this->customPage) {
      $out .=  rex_view::title($this->package->getProperty('page')['title']);
    } else {
      $out .= rex_view::clangSwitchAsButtons($this->context, true);
      $out .=  $this->backendNav($this->package->getProperty('page')['title'], $this->package->getProperty($this->packageId . '_pages'));
    }
    // //

    $out .= '<div class="' . $this->package->getProperty('package') . '-table ' . $this->subpageKey . '-table">';
    if ($this->customPage) {
      ob_start();
      rex_be_controller::includeCurrentPageSubPath();
      $out .= rex_type::string(ob_get_clean());
    } elseif ($this->tableName) {
      $out .=  self::getYformTable($this->yformTable, [], [], ['clang_id' => $this->clangId]);
    }
    $out .= '</div>';
    $out .= $this->addCssAndJs();

    return $out;
  }

  public function addCssAndJs()
  {
    $out = '';
    return $out;
  }

  public static function getYformTable(rex_yform_manager_table $table, $disableTableFuncs = [], $tableValues = [], $addLinkVars = [])
  {

    $tableFunctions = ['add', 'delete', 'search', 'export', 'truncate_table'];
    foreach ($disableTableFuncs as $func) {
      if (($key = array_search($func, $tableFunctions)) !== false) {
        unset($tableFunctions[$key]);
      }
    }

    foreach ($tableValues as $offset => $value) {
      $table->offsetSet($offset, $value);
    }

    try {
      $page = new rex_yform_manager();
      $page->setTable($table);
      $page->setLinkVars(array_merge(['page' => rex_be_controller::getCurrentPage(), 'tableName' => $table->getTableName()], $addLinkVars));
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


  public function backendNav($head, $pages)
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
