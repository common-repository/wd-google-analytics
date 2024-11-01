<?php
/**
 * Created by PhpStorm.
 * User: mher
 * Date: 6/10/18
 * Time: 2:07 PM
 */

abstract class  GAWD_file_generator {

  protected $file_name;
  protected $gawd_upload_dir;
  protected $file_dir;
  protected $file_url;
  protected $output;

  abstract protected function file_put_content();

  public function __construct($file_name){
    $this->file_name = $file_name;

    $upload_dir = wp_get_upload_dir();
    $this->gawd_upload_dir = $upload_dir['basedir'] . '/wd-google-analytics/export/';
    $this->file_dir = $this->gawd_upload_dir . $this->file_name;
    $this->file_url = $upload_dir['baseurl'] . '/wd-google-analytics/export/' . $this->file_name;

  }

  public function generate(){

    if($this->check_gawd_dir() === false) {
      GAWD_logs::add('failed_to_create_directory', 'Failed to create directory ' . $this->gawd_upload_dir);
      return false;
    }

    $this->create_file();


    return true;

  }

  private function create_file(){
    $this->output = fopen($this->file_dir, "w");

    $this->file_put_content();

    @fclose($this->output);
  }

  private function check_gawd_dir(){

    if(is_dir($this->gawd_upload_dir)) {
      return true;
    }

    if(mkdir($this->gawd_upload_dir, 0777, true) === true) {
      return true;
    }

    return false;
  }

  public function get_file_name(){
    return $this->file_name;
  }

  public function get_file_url(){
    return $this->file_url;
  }

  public function get_file_dir(){
    return $this->file_dir;
  }

}