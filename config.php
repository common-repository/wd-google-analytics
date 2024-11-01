<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 1/19/18
 * Time: 4:34 PM
 */

if(!defined('GAWD_DIR')) {

  define('GAWD_DIR', dirname(__FILE__));
  define('GAWD_LIB_DIR', GAWD_DIR . '/library');
  define('GWD_NAME', plugin_basename(dirname(__FILE__)));
  define('GAWD_URL', plugins_url(plugin_basename(dirname(__FILE__))));
  define('GAWD_INC', GAWD_URL . '/inc');
  define('GAWD_VERSION', '1.2.12');

  $upload_dir = wp_upload_dir();
  define('GAWD_UPLOAD_DIR', $upload_dir['basedir'] . '/' . plugin_basename(dirname(__FILE__)));
}