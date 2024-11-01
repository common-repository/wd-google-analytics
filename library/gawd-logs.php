<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 12/20/17
 * Time: 2:14 PM
 */

class GAWD_logs {

  private static $logs = null;

  public static function get(){

    if(self::$logs === null) {

      $opt = get_option('gawd_logs');
      if($opt === false) {
        $opt = array();
      }
      self::$logs = $opt;
    }

    return self::$logs;
  }

  public static function add($key = "", $log = "", $fail = true){
    $logs = self::get();
    $logs[$key] = array(
      'fail' => $fail,
      'log' => $log,
      'date' => date('Y-m-d H:i:s e')
    );
    self::$logs = $logs;
    update_option('gawd_logs', self::$logs);
  }

  public static function clear(){
    delete_option('gawd_logs');
    self::$logs = array();
  }

  public static function print_logs(){
    $logs = GAWD_logs::get();
    uasort($logs, array("GAWD_logs", "sort_logs_by_date"));
    require_once(GAWD_DIR . '/admin/pages/logs.php');
  }

  public static function sort_logs_by_date($a, $b){
    return ($a['date'] <= $b['date']);
  }

}