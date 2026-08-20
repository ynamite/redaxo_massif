<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use rex_article;
use rex_response;
use rex_yform_manager_dataset;
use Url as UrlManager;

class RexUrl
{

  /**
   * @phpstan-type ManagerItem array{
   *     url: string,
   *     id: int,
   *     ns-id: int,
   *     ns: string,
   *     table-name: ?string,
   *     user-path: ?string
   * }
   */
  /** @var array<string, ManagerItem> $urlManagerData */
  public static array $urlManagerData = [];

  /**
   * Check if the given dataset is online.
   * Redirects to the notfound article (and exits) when the dataset is
   * missing or offline.
   *
   * @param rex_yform_manager_dataset|null $dataset
   *
   * @return bool
   */
  public static function isOnline(?rex_yform_manager_dataset $dataset = null): bool
  {
    $isOnline = false;
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
      if (!$isOnline && $dataset->hasValue('status') && (int)$dataset->getValue('status') === 1) {
        $dataset->setValue('status', 0);
        $dataset->save();
      }
    }
    if (!$isOnline) {
      rex_response::sendRedirect(rex_getUrl(rex_article::getNotfoundArticleId()), rex_response::HTTP_MOVED_TEMPORARILY);
      exit;
    }
    return true;
  }

  /**
   * Get the URL manager data for the given namespace.
   *
   * @param string $ns
   * 
   * @return array
   */
  public static function getUrlManager(string $ns = ''): array
  {
    if ($ns && isset(self::$urlManagerData[$ns])) {
      return self::$urlManagerData[$ns];
    }
    $manager = UrlManager\Url::resolveCurrent();
    if ($manager) {
      if ($profile = $manager->getProfile()) {
        $ns = $ns ? $ns : $profile->getNamespace();
        self::$urlManagerData[$ns] = [];
        self::$urlManagerData[$ns]['url'] = $manager->getUrl()->getPath();
        self::$urlManagerData[$ns]['id'] = $manager->getDatasetId();
        self::$urlManagerData[$ns]['ns-id'] = $profile->getId();
        self::$urlManagerData[$ns]['ns'] = $profile->getNamespace();
        self::$urlManagerData[$ns]['table-name'] = $profile->getTableName();
        self::$urlManagerData[$ns]['dataset'] = $manager->getDataset();
        // $pageClass .= ' url-manager-page url-profile-' . $profile->getNamespace();
        if ($manager->isUserPath()) {
          $segments = $manager->getUrl()->getSegments();
          foreach ($profile->getUserPaths() as $value => $label) {
            if (in_array($value, $segments)) {
              self::$urlManagerData[$ns]['user-path'] = $label;
            }
          }
        }
      }
    }
    return self::$urlManagerData[$ns] ?? [];
  }
}
