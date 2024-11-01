<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 1/26/18
 * Time: 1:30 PM
 */

class GAWD_exception_handler {

  private static $instance = null;
  private $gawd_ajax_instance = null;

  private function __construct(){
  }

  public function add($e, $e_class_name, $function_name){

    $this->add_log($e, $e_class_name, $function_name);

    $msg = $e->getMessage();
    if(strpos($msg, 'web property:') !== false && strpos($msg, 'not found.') !== false) {
      $this->gawd_ajax_instance->send_response(false, 'gawd_no_property');
    }

  }

  private function add_log($e, $e_class_name, $function_name){
    $key = $function_name . '****' . $e_class_name;
    $log = $e->getMessage() . ". CODE:" . $e->getCode() . " .FUNC:" . $function_name . " .E_CLASSNAME:" . $e_class_name;

    GAWD_logs::add($key, $log);
    if($e_class_name === "Exception") {
      GAWD_helper::write_log_into_file($e->getMessage() . "----" . $function_name . " function");
    }
  }

  public function set_gawd_ajax_instance($gawd_ajax_instance){
    $this->gawd_ajax_instance = $gawd_ajax_instance;
  }


  public static function get_instance(){
    if(null === self::$instance) {
      self::$instance = new self();
    }

    return self::$instance;
  }

}