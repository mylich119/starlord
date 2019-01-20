<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Curl
{

    static public function get($url, $params = array())
    {

        $ci = curl_init();
        $url = $url . http_build_query($params);
        curl_setopt($ci, CURLOPT_URL, $url);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ci, CURLOPT_TIMEOUT, 5);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ci, CURLOPT_HEADER, false);

        $startTime = microtime(true);
        $response = curl_exec($ci);
        $endTime = microtime(true);
        $procTime = ($endTime - $startTime) * 1000;

        if (!$response) {
            return false;
        }

        return $response;
    }
}
