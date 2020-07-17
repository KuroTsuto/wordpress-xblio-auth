<?php
namespace BosconianDynamics\XblioAuth;

class Build {
  static function copy_autoloaders() {
    copy(
      __DIR__ . '/vendor/autoload.php',
      __DIR__ . '/build/autoload.php'
    );

    recurse_copy(
      __DIR__ . '/vendor/composer',
      __DIR__ . '/build/composer'
    );

    recurse_copy(
      __DIR__ . '/vendor/symfony',
      __DIR__ . '/build/symfony'
    );
  }
}

function recurse_copy($src,$dst) { 
  $dir = opendir($src); 
  @mkdir($dst); 
  while(false !== ( $file = readdir($dir)) ) { 
      if (( $file != '.' ) && ( $file != '..' )) { 
          if ( is_dir($src . '/' . $file) ) { 
              recurse_copy($src . '/' . $file,$dst . '/' . $file); 
          } 
          else { 
              copy($src . '/' . $file,$dst . '/' . $file); 
          } 
      } 
  } 
  closedir($dir); 
} 