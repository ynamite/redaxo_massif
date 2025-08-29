<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex_article;
use rex_response;
use rex_yform_manager_dataset;

class RexUrl
{
  /**
   * Handle offline URLs by checking the status and date fields of the dataset.
   * @param array<string, array{
   *   url: string,
   *   id: int,
   *   'ns-id': int,
   *   ns: string,
   *   'table-name'?: ?string,  // required but nullable
   *   'user-path'?: ?string    // required but nullable
   * }> $urlManagerData
   */
  public static function handleOfflineURL(array $urlManagerData = []): void
  {
    $isOnline = false;
    $dataset = rex_yform_manager_dataset::get($urlManagerData['id'], $urlManagerData['table-name']);
    if ($dataset) {
      $isOnline = (int)$dataset->getValue('status') === 1;
      if ($dataset->hasValue('date_show_start')) {
        if ($dataset->getValue('date_show_start') !== '0000-00-00 00:00:00' && strtotime($dataset->getValue('date_show_start')) >= time()) {
          $isOnline = false;
        }
      }
      if ($dataset->hasValue('date_show_end')) {
        if ($dataset->getValue('date_show_end') !== '0000-00-00 00:00:00' && strtotime($dataset->getValue('date_show_end')) <= time()) {
          $isOnline = false;
        }
      }
    }
    if (!$isOnline) {
      $dataset->setValue('status', 0);
      $dataset->save();
      rex_response::sendRedirect(rex_getUrl(rex_article::getNotfoundArticleId()), rex_response::HTTP_MOVED_TEMPORARILY);
      exit();
    }
  }
}
