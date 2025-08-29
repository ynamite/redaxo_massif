<?php

namespace Ynamite\Massif;

use rex_markdown;
use rex_string;

/**
 * Markdown parser.
 *
 * @author gharlan
 *
 * @package redaxo\core
 */
class Markdown extends rex_markdown
{
  /**
   * Parses markdown code.
   *
   * @param string $code Markdown code
   * @param array<self::*, bool>|bool $options
   *
   * @return string HTML code
   */
  public function parse($code, $options = [])
  {
    // deprecated bool param
    $options = is_bool($options) ? [self::SOFT_LINE_BREAKS => $options] : $options;

    $parser = new Parsedown();
    $parser->setBreaksEnabled($options[self::SOFT_LINE_BREAKS] ?? true);

    return rex_string::sanitizeHtml($parser->text($code));
  }
}
