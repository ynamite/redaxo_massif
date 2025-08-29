<?php

namespace Ynamite\Massif;

use FriendsOfRedaxo\MarkItUp\MarkItUp as MarkItUpImpl;
use FriendsOfRedaxo\MarkItUp\Textile;

final class MarkItUp extends MarkItUpImpl
{
  public static function parseOutput(string $type, string $content): bool|string
  {
    $content = str_replace('<br />', '', $content);

    switch ($type) {
      case 'markdown':
        /**
         * Der alte Code (bis V3.7) setze auf der eigenen Markdown-Klasse auf.
         * Da Markdown in längst im REDAXO-Core steht (rex_markdown) und auch
         * MarkItUp den Core-Vendor nutzt, kann man auch gleich auf rex_markdown gehen.
         */
        $parser = Markdown::factory();
        return self::replaceYFormLink($parser->parse($content));
      case 'textile':
        return self::replaceYFormLink(Textile::custom_parse($content));
    }

    return false;
  }
}
