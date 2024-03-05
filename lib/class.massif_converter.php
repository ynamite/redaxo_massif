<?php

class massif_converter
{

  public $tables = [
    'rex_article' => 'yconverter_rex_article',
    'rex_article_slice' => 'yconverter_rex_article_slice',
    'rex_media' => 'yconverter_rex_media',
    'rex_media_category' => 'yconverter_rex_media_category',
  ];

  public $columns = [
    'rex_media' => ['file_id' => 'id'],
    'rex_article' => ['seo_description' => 'yrewrite_description'],
  ];

  public $nullValues = [
    'rex_article' => [
      'catname' => '',
      'catpriority' => 0,
      'startarticle' => 0,
      'status' => 0,
      'parent_id' => 0,
      'yrewrite_description' => '',
      'revision' => 0,
    ],
    'rex_article_slice' => [
      'status' => 0,
      'revision' => 0,
    ]

  ];

  private $log = [];
  private $logOutput = '';

  public function __construct()
  {
  }

  private function getTableColumns($table): array
  {
    $sql = rex_sql::factory();
    $sql->setQuery('SHOW COLUMNS FROM `' . $table . '`');
    $columns = $sql->getArray();
    $output = [];
    foreach ($columns as $column) {
      $output[$column['Field']] = $column['Field'];
    }
    return $output;
  }

  private function setLog($action, $table, $rowKey, $oldKey = null, $newKey = null, $value = null)
  {
    if ($action == 'row') {
      $this->log[$table][$rowKey][] = [
        'action' => $action,
      ];
    } else {
      $this->log[$table][$rowKey][] = [
        'action' => $action,
        'oldKey' => $oldKey,
        'newKey' => $newKey,
        'value' => $value,
      ];
    }
  }

  public function getLogOutput($detailled = false)
  {
    $htmlAction = [
      'insert' => '<span style="color: green;">insert data</span>',
      'update' => '<span style="color: orange;">update column name and insert data</span>',
      'delete' => '<span style="color: red;">delete</span>'
    ];
    $output = '<pre>';
    foreach ($this->log as $table => $rows) {
      $output .= '<h2>' . $table . '</h2>';
      $rowsChanged = 0;
      $rowsInserted = 0;
      foreach ($rows as $row) {
        foreach ($row as $column) {
          if ($detailled) {
            if ($column['action'] == 'row') {
              $output .= '<hr>';
            } else {
              if ($column['oldKey'] == $column['newKey']) {
                $output .= $htmlAction[$column['action']] . ' `' . $column['oldKey'] . '` "' . $column['value'] . '"<br>';
              } else {
                $output .= $htmlAction[$column['action']] . ' `' . $column['oldKey'] . '` => `' . $column['newKey'] . '` with value "' . $column['value'] . '"<br>';
              }
            }
          } else {
            $rowsChanged += $column['action'] == 'update' ? 1 : 0;
          }
        }
        $rowsInserted++;
      }
      $output .= '<span style="color: orange;">' . $rowsChanged . ' columns changed</span><br>';
      $output .= '<span style="color: green;">' . $rowsInserted . ' datasets inserted</span><br>';
    }
    $output .= '</pre>';
    return $output;
  }

  private function convertTables($tables = []): Bool
  {
    $sql = rex_sql::factory();
    foreach ($tables as $table) {
      $newRow = [];
      $newColumns = $this->getTableColumns($table);
      $sql->setQuery('SELECT * FROM `' . $this->tables[$table] . '`');
      $rows = $sql->getArray();
      $columnsChanged = [];
      foreach ($rows as $rowKey => $row) {
        foreach ($row as $key => $value) {
          if (isset($newColumns[$key])) {
            $newRow[$key] = $value ? $value : (isset($this->nullValues[$table][$key]) ? $this->nullValues[$table][$key] : null);
            $this->setLog('insert', $table, $rowKey, $key, $key, $value,);
          } else {
            if (isset($this->columns[$table][$key])) {
              $newkey = $this->columns[$table][$key];
              $columnsChanged[$key] = $newkey;
              $newRow[$newkey] = $value ? $value : (isset($this->nullValues[$table][$newkey]) ? $this->nullValues[$table][$newkey] : null);
              if (!isset($columnsChanged[$key]))
                $this->setLog('update', $table, $rowKey, $key, $newkey, $value);
            }
          }
        }
        $this->setLog('row', $table, $rowKey);


        $sql->setTable($table);
        $sql->setValues($newRow);
        $sql->insert();
      }

      /*
      foreach ($columns as $column) {
        if (isset($newColumns[$column['Field']])) {
          $newRow[$newColumns[$column['Field']]] = $column['Field'];
        } else {
          $newRow[$this->columns[$table]][$column['Field']] = $this->columns[$table];
        }
      

        $sql->setTable($table);
        $sql->setValues($newRow);
        $sql->insert();
        */
    }
    return true;
  }

  private function convertArticles(): Bool
  {

    $tables = [
      'rex_article', 'rex_article_slice',
    ];
    return $this->convertTables($tables);
  }

  private function convertMedia(): Bool
  {

    $tables = [
      'rex_media', 'rex_media_category',
    ];
    return $this->convertTables($tables);
  }

  public function convert(): Bool
  {
    $convert = $this->convertArticles();
    if (!$convert) {
      return false;
    }
    // $convert = $this->convertMedia();
    if (!$convert) {
      return false;
    }
    return true;
  }

  public static function factory()
  {
    return new self();
  }
}
