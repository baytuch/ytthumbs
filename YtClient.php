<?php

class YtClient {

  const USER_AGENT = 'YtThumbsBot/1.0 (+http://it-hobby.km.ua)';
  const SERVICE_URL = 'https://www.youtube.com';
  const SERVICE_IMAGES_URL = 'https://i.ytimg.com';
  const SERVICE_PLAY_PREFIX = 'watch?v=';
  const	SERVICE_TIMEOUT = 15;
  const CERT_NAME = 'cacert.pem';

  private $data_dir = '';

  function YtClient($data_dir) {
    $this->data_dir = $data_dir;
    #$this->get_video_info('Tj8uqmMxwfY');
  }

  function client($path, $mode = false) {
    $res['status'] = false;
    $res['data'] = '';
    $ch = curl_init();
    if ($mode) {
      curl_setopt($ch, CURLOPT_URL, self::SERVICE_IMAGES_URL . '/vi/' . $path);
    } else {
      curl_setopt($ch, CURLOPT_URL, self::SERVICE_URL . '/' . $path);
    }
    curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
    curl_setopt($ch, CURLOPT_CAINFO, realpath($this->data_dir . '/' . self::CERT_NAME));
    curl_setopt($ch, CURLOPT_CAPATH, realpath($this->data_dir . '/' . self::CERT_NAME));
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, self::SERVICE_TIMEOUT);
    $http_body = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //echo curl_error($ch);
    curl_close($ch);
    if ($http_code == '200') {
      $res['status'] = true;
      $res['data'] = $http_body; 
    }
    //print_r($res);
    return $res;
  }

  function get_video_info($yt_code) {
    $res['status'] = false;
    $path = self::SERVICE_PLAY_PREFIX . $yt_code;
    $video_data = $this->client($path);
    if ($video_data['status']) {
      preg_match('@\\\\\"videoDetails\\\\\":({(\\\\\"\w+\\\\\":(\\\\\"(|.*?[^\\\\])\\\\\"|[\w|\d|\.]+|\[.*?\]|{.*?\]})[,|}|])+)@',
                 $video_data['data'],
                 $output_array);
      if (isset($output_array[1])) {
        $video_info_raw = str_replace('\/', '/', $output_array[1]);
        $video_info_raw = str_replace('\\\\', '\\', $video_info_raw);
        $video_info_raw = str_replace('\"', '"', $video_info_raw);
        $video_info_obj = json_decode($video_info_raw, true);
        if ($video_info_obj !== NULL) {
          $res['data'] = $video_info_obj;
          $res['status'] = true;
        }
      }
    }
    return $res;
  }

  function get_video_thumb($yt_code, $file_name) {
    $res['status'] = false;
    $path = $yt_code . '/' . $file_name;
    $video_data = $this->client($path, true);
    if ($video_data['status']) {
      $res['status'] = true;
      $res['data'] = $video_data['data'];
    }
    return $res;
  }
}

?>