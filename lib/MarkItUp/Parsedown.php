<?php

namespace Ynamite\Massif;

use ParsedownExtra;
use rex_yrewrite;

/**
 * @internal
 */
final class Parsedown extends ParsedownExtra
{

  private function addTargetBlank(string $method, array $Excerpt): array|null
  {

    $return = parent::$method($Excerpt);
    if (!$return) {
      return null;
    }
    $url = $return['element']['attributes']['href'];
    if ($url && !self::isExternalUrl($url)) {
      return $return;
    }

    $return['element']['attributes']['target'] = '_blank';
    $return['element']['attributes']['rel'] = 'noopener noreferrer';
    return $return;
  }

  protected function inlineUrl($Excerpt)
  {
    return $this->addTargetBlank('inlineUrl', $Excerpt);
  }

  protected function inlineLink($Excerpt)
  {
    return $this->addTargetBlank('inlineLink', $Excerpt);
  }

  protected function inlineUrlTag($Excerpt)
  {
    return $this->addTargetBlank('inlineUrlTag', $Excerpt);
  }

  protected function isExternalUrl(string $url): bool
  {
    $parsedUrl = parse_url($url);

    // No host? It's likely a relative URL
    if (!isset($parsedUrl['host'])) {
      return false;
    }

    // Normalize incoming URL host and port
    $urlHost = strtolower($parsedUrl['host']);
    $urlPort = $parsedUrl['port'] ?? null;


    // Normalize current server host and port
    $serverHost = rex_yrewrite::getFullPath();
    $serverParts = parse_url((strpos($serverHost, '://') === false ? 'http://' : '') . $serverHost);
    $serverHostNorm = strtolower($serverParts['host'] ?? $serverHost);
    $serverPort = $serverParts['port'] ?? ($_SERVER['SERVER_PORT'] ?? null);

    // Strip www. for comparison
    $urlHost = preg_replace('/^www\./', '', $urlHost);
    $serverHostNorm = preg_replace('/^www\./', '', $serverHostNorm);

    // Compare host and port
    if ($urlHost !== $serverHostNorm) {
      return true;
    }

    if ($urlPort && $serverPort && $urlPort != $serverPort) {
      return true;
    }

    return false;
  }
}
