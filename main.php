#!/usr/bin/php -q
<?php

// For Platypus $argv contains:
// [0] - Absolute to the running script
// [1...n] - Absolute path to each dropped file
// var_dump($argv);
date_default_timezone_set('Asia/Jakarta');
define('NOW', microtime(true));

// find the files dropped onto the Platypus app icon:
$dropped = array_slice($argv, 1);
// $log = array();
// $log["sucess"] = 0;
$success = 0;
$error = array();

if (!empty($dropped)) {
  echo "Processing..." . PHP_EOL;
  foreach ($dropped as $item) {
    if(is_dir($item)) {
      $files = ListFiles($item);
      if(!empty($files)) {
        foreach ($files as $psd) {
          CreateTiff($psd, dirname($psd));
        }
      }
    }
    else if(is_file($item)) {
      $finfo = finfo_open(FILEINFO_MIME_TYPE); 
      $mime = finfo_file($finfo, $item);
      if($mime == "image/vnd.adobe.photoshop") {
        CreateTiff($item, dirname($item));
      }
    }
    else {
      echo "- «" . basename($item) . "» is a X File!!!" . PHP_EOL;
    }
  }
  echo PHP_EOL . "[RECAP]" . PHP_EOL;
  printf("  -> %d file(s) converted sucessfuly" . PHP_EOL, $success);
  if(count($error) > 0) {
    printf("  -> %d error(s) occured:" . PHP_EOL, count($error));
    foreach($error as $entry) {
      echo $entry . PHP_EOL;
    }
  }
  printf("  -> Processing time: %f secondes @ %s" . PHP_EOL, microtime(true) - NOW, date('H:i:s'));
} else {
  echo "Please drag'n drop some files or Menu » File » Open..." . PHP_EOL;
  exit;
}


function ListFiles($directory, $extension = "psd") {
  $paths = glob($directory . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT);
  $files = glob($directory . "*." . $extension);
  foreach ($paths as $path) {
    $files = array_merge($files, ListFiles($path, $extension));
  }
  return $files;
}

function CreateTiff($source, $destination) {
  $start = microtime(true);
  global $success, $error;
  $destination = escapeshellarg($destination . "/" . basename($source, ".psd") . ".tif");
  $source = escapeshellarg($source);
  $space = exec("sips --getProperty space " . $source);
  $space = trim($space, "space: ");
  $width = exec("sips --getProperty dpiWidth " . $source);
  $width = trim($width, "dpiWidth: ");
  $height = exec("sips --getProperty dpiHeight " . $source);
  $height = trim($height, "dpiHeight: ");
  if($space != "CMYK") {
    printf("- file «%s» has wrong color space." . PHP_EOL, basename($source));
    array_push($error, "    [" . basename($source) . "] bad color space");
  } else if ((int)$width < 300 || (int)$height < 300) {
    printf("- file «%s» resolution is too low." . PHP_EOL, basename($source));
    array_push($error, "    [" . basename($source) . "] bad resolution");
  } else {
    exec("sips -s format tiff -s formatOptions lzw " . $source . " --out " . $destination);
    printf("+ file «%s» in %f secondes" . PHP_EOL, basename($source), microtime(true) - $start);
    $success++;
  }
}