<?php

require "YtClient.php";


class YtThumbs {

  const DIR_DATA = 'data';
  const DIR_CACHE = 'cache';
  const THUMB_ORIGIN_NAME = 'thumb_origin.jpg';
  const THUMB_MAIN_NAME = 'thumb_main.jpg';
  const THUMB_MAIN_WIDTH = 1280;
  const THUMB_MAIN_HEIGHT = 720;
  const PLAY_BUTTON_SIZE = 200;
  const THUMB_MAIN_TYPE = 'main';
  const THUMB_ORIGIN_TYPE = 'origin';

  private $yt_code = NULL;
  private $target_dir_path = NULL;
  private $thumb_origin_path = NULL;
  private $thumb_main_path = NULL;
  private $yt = NULL;

  function YtThumbs() {
    $this->yt = new YtClient(self::DIR_DATA);
  }

  function load_thumb_origin() {
    $yt_video_info = $this->yt->get_video_info($this->yt_code);
    if ($yt_video_info['status']) {
      $yt_video_data = $yt_video_info['data'];
      if (array_key_exists('thumbnail', $yt_video_data)) {
        $thumbnail_array = $yt_video_data['thumbnail'];
        if (array_key_exists('thumbnails', $thumbnail_array)) {
          $thumbnails_array = $thumbnail_array['thumbnails'];
          $thumb_url = '';
          $thumb_width = 0;
          $thumb_height = 0;
          $thumb_status = false;
          foreach ($thumbnails_array as $thumb_array) {
            if (array_key_exists('url', $thumb_array) and
                array_key_exists('width', $thumb_array) and
                array_key_exists('height', $thumb_array)) {
              $target_thumb_url = $thumb_array['url'];
              $target_thumb_width = (int) $thumb_array['width'];
              $target_thumb_height = (int) $thumb_array['height'];
              if ($target_thumb_width > $thumb_width and $target_thumb_height > $thumb_height) {
                $thumb_width = $target_thumb_width;
                $thumb_height = $target_thumb_height;
                $thumb_url = $target_thumb_url;
                $thumb_status = true;
              }
            }
          }
          if ($thumb_status) {
            $video_thumb_data = $this->yt->get_video_thumb($this->yt_code, substr($thumb_url, strrpos($thumb_url, '/') + 1));
            if ($video_thumb_data['status']) {
              file_put_contents($this->thumb_origin_path, $video_thumb_data['data']);
            }
          }
        }
      }
    }
  }

  function process_thumbs() {
    $jpg_origin_obj = false;
    $jpg_origin_width = 0;
    $jpg_origin_height = 0;
    if (file_exists($this->thumb_origin_path)) {
      $jpg_origin_obj = imagecreatefromjpeg($this->thumb_origin_path);
      list($jpg_origin_width, $jpg_origin_height) = getimagesize($this->thumb_origin_path);
    }
    $jpg_main_obj = imagecreatetruecolor(self::THUMB_MAIN_WIDTH, self::THUMB_MAIN_HEIGHT);
    $ink_main_draw = imagecolorallocate($jpg_main_obj, 204, 224, 238);
    $ink_main_back = imagecolorallocate($jpg_main_obj, 208, 208, 208);
    if ($jpg_origin_obj !== false) {
      imagecopyresampled($jpg_main_obj,
                         $jpg_origin_obj,
                         0,
                         0,
                         0,
                         0,
                         self::THUMB_MAIN_WIDTH,
                         self::THUMB_MAIN_HEIGHT,
                         $jpg_origin_width,
                         $jpg_origin_height);
    } else {
      imagefill($jpg_main_obj, 0, 0, $ink_main_back);
    }
    $button_points = array((int) self::THUMB_MAIN_WIDTH / 2 - self::PLAY_BUTTON_SIZE / 2,
                           (int) self::THUMB_MAIN_HEIGHT / 2,
                           (int) self::THUMB_MAIN_WIDTH / 2 + self::PLAY_BUTTON_SIZE / 2,
                           (int) self::THUMB_MAIN_HEIGHT / 2 - self::PLAY_BUTTON_SIZE / 2,
                           (int) self::THUMB_MAIN_WIDTH / 2 + self::PLAY_BUTTON_SIZE / 2,
                           (int) self::THUMB_MAIN_HEIGHT / 2 + self::PLAY_BUTTON_SIZE / 2);
    imagefilledpolygon($jpg_main_obj, $button_points, 3, $ink_main_draw);
    imagejpeg($jpg_main_obj, $this->thumb_main_path, 95);
  }

  function processor() {
    $this->target_dir_path = self::DIR_DATA . '/' . self::DIR_CACHE . '/' . substr($this->yt_code, 0, 2) . '/' . $this->yt_code;
    $this->thumb_origin_path = $this->target_dir_path . '/' . self::THUMB_ORIGIN_NAME;
    $this->thumb_main_path = $this->target_dir_path . '/' . self::THUMB_MAIN_NAME;
    if (!file_exists($this->target_dir_path)) {
      mkdir($this->target_dir_path, 0777, true);
    }
    if (!file_exists($this->thumb_origin_path)) $this->load_thumb_origin();
    if (!file_exists($this->thumb_main_path)) $this->process_thumbs();
  }

  function show_thumb($path) {
    if (file_exists($path)) {
      echo file_get_contents($path);
    }
  }

  function show($yt_code, $thumbs_type) {
    if ($yt_code !== NULL) {
      preg_match('/^[a-z|A-Z|0-9|\-|_]{11}$/', $yt_code, $yt_code_array);
      if (count($yt_code_array) == 1) {
        $this->yt_code = $yt_code_array[0];
      }
    }
    if ($thumbs_type !== NULL) {
      if ($thumbs_type == self::THUMB_MAIN_TYPE) {
        $this->thumbs_type = $thumbs_type;
      } else if ($thumbs_type == self::THUMB_ORIGIN_TYPE) {
        $this->thumbs_type = $thumbs_type;
      }
    }
    if ($this->yt_code !== NULL) {
      //header('Content-Type: text/plain');
      header('Content-Type: image/jpeg');
      $this->processor();
      if ($thumbs_type === NULL or $thumbs_type == self::THUMB_MAIN_TYPE) {
        $this->show_thumb($this->thumb_main_path);
      } else if ($thumbs_type == self::THUMB_ORIGIN_TYPE) {
        $this->show_thumb($this->thumb_origin_path);
      } else {
        $this->show_thumb($this->thumb_main_path);
      }
    } else {
      header('Content-Type: text/plain');
      echo 'error: invalid request!';
    }
  }

}

$yt_code = NULL;
$thumbs_type = NULL;
$ytthumbs = new YtThumbs();
if (isset($_GET['yt_code'])) $yt_code = $_GET['yt_code'];
if (isset($_GET['thumbs_type'])) $thumbs_type = $_GET['thumbs_type'];
$ytthumbs->show($yt_code, $thumbs_type);

?>