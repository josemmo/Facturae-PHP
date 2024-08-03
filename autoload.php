<?php
/**
 * This file is an extremely simple autoloader for environments where Composer is not available.
 */

spl_autoload_register(static function ($class) {
  // Verify we're importing a class from this library
  if (mb_strpos($class, 'josemmo\\Facturae\\') !== 0) {
    return false;
  }

  // Find file
  $path = __DIR__ . "/src/" . str_replace('\\', '/', mb_substr($class, 17)) . ".php";
  if (!file_exists($path)) {
    return false;
  }

  // Import file
  require $path;
  return true;
});
