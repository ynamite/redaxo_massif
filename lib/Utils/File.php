<?php

declare(strict_types=1);

namespace Ynamite\Massif\Utils;

use InvalidArgumentException;
use rex_dir;
use rex_file;
use rex_path;
use ZipArchive;

class File
{

  /**
   * Create a zip file from an array of files.
   *
   * @param string $filename The name of the zip file to create (without .zip extension).
   * @param array $files An array of file names to include in the zip (relative to the specified path).
   * @param string $path The directory path where the files are located (default is rex_path::media()).
   * 
   * @return string The path to the created zip file, or false on failure.
   */

  public static function createZip(string $filename = "", array $files = [], string $path = ''): string
  {
    if (!$filename || !is_array($files) || count($files) == 0) {
      throw new InvalidArgumentException('Filename and files are required to create a zip.');
    }
    if (!$path) $path = rex_path::media();
    $zipPath = $path . 'zips/';
    $zipFilePath = $zipPath . $filename . '.zip';
    if (file_exists($path . $filename)) rex_file::delete($zipFilePath);

    $zip = new ZipArchive();
    if (!is_dir($zipPath)) {
      rex_dir::create($zipPath);
    }
    $zip->open($zipFilePath, ZipArchive::CREATE);
    foreach ($files as $file) {
      $zip->addFile($path . $file, basename($file));
    }
    $zip->close();
    return $zipFilePath;
  }
}
