<?php

class WxApi extends CI_Model
{
    const APPID = 'wx1f9fb9bce77cd0e4';
    const SECRET = '993c780c8cdf242ce477cb50580ea381';
    const LBS_KEY = 'SMBBZ-FP5CJ-V4OFT-FXMXR-QPEOJ-BOFIO';
    const LBS_SEC = 'mGGQQexjQrbSCzERYuOk8q4PkxD7PJ8o';

    public function getSessionKeyAndOpenId($code)
    {
        $data = array(
            'appid' => self::APPID,
            'secret' => self::SECRET,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        );

        $t1 = microtime(true);
        $ret = Curl::get('https://api.weixin.qq.com/sns/jscode2session?', $data);
        $t2 = microtime(true);

        $logDate = date("Y-m-d H:i:s", time());
        $logContent = $logDate . ' | ';
        $logContent .= 'wx_login_proc_time' . ' | ';
        $logContent .= Base::$traceId . ' | ';
        $logContent .= 'time=' . ($t2 - $t1);
        error_log($logContent . "\n", 3, Config::BIZ_LOG_PATH);

        $session = json_decode($ret, true);

        if (isset($session['errcode']) && $session['errcode'] != 0) {
            throw new StatusException(Status::$message[Status::WX_FETCH_SESSION_FAIL], Status::WX_FETCH_SESSION_FAIL, json_encode($session));
        } else {
            return array(
                'open_id' => $session['openid'],
                'session_key' => $session['session_key'],
            );
        }
    }

    public function decryptUserInfo($sessionKey, $encryptedData, $iv)
    {
        if (strlen($sessionKey) != 24) {
            throw new StatusException(Status::$message[Status::USER_HAS_NO_TICKET], Status::USER_HAS_NO_TICKET);
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            throw new StatusException(Status::$message[Status::WX_DECRYPT_ERROR], Status::WX_DECRYPT_ERROR);
        }

        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $data = json_decode($result, true);
        if ($data == NULL) {
            throw new StatusException(Status::$message[Status::WX_DECRYPT_ERROR], Status::WX_DECRYPT_ERROR);
        }
        if ($data['watermark']['appid'] != self::APPID) {
            throw new StatusException(Status::$message[Status::WX_DECRYPT_ERROR], Status::WX_DECRYPT_ERROR);
        }
        return $data;
    }

    public function decryptGroupInfo($sessionKey, $encryptedData, $iv)
    {
        if (strlen($sessionKey) != 24) {
            throw new StatusException(Status::$message[Status::USER_HAS_NO_TICKET], Status::USER_HAS_NO_TICKET);
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            throw new StatusException(Status::$message[Status::WX_DECRYPT_ERROR], Status::WX_DECRYPT_ERROR);
        }

        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
        $data = json_decode($result, true);
        if ($data == NULL) {
            throw new StatusException(Status::$message[Status::WX_DECRYPT_ERROR], Status::WX_DECRYPT_ERROR);
        }

        return $data;
    }

    public function getRoutesByFromAndTo($from, $to)
    {
        $tmp = rtrim($from, ')');
        $from = ltrim($tmp, '(');
        $tmp = rtrim($to, ')');
        $to = ltrim($tmp, '(');
        $str = '/ws/direction/v1/driving/?' . 'from=' . $from . '&key=' . self::LBS_KEY . '&output=json&policy=LEAST_TIME&to=' . $to . self::LBS_SEC;
        $sig = md5($str);
        $data = array(
            'from' => $from,
            'to' => $to,
            'output' => 'json',
            'policy' => 'LEAST_TIME',
            'key' => self::LBS_KEY,
            'sig' => $sig,
        );

        $t1 = microtime(true);
        $ret = Curl::get('https://apis.map.qq.com/ws/direction/v1/driving/?', $data);

        $session = json_decode($ret, true);
        if (isset($session['status']) && $session['status'] != 0) {
            throw new StatusException(Status::$message[Status::WX_FETCH_LBS_ROUTE_FAIL], Status::WX_FETCH_LBS_ROUTE_FAIL, json_encode($session));
        } else {
            $routes = $session['result']['routes'];
            if (empty($routes) || !is_array($routes)) {
                throw new StatusException(Status::$message[Status::WX_FETCH_LBS_NO_ROUTE_FOUND], Status::WX_FETCH_LBS_NO_ROUTE_FOUND);
            }
            $polyLine = $routes[0]['polyline'];
            $count = count($polyLine);

            //过长的路径进行采样
            if ($count > Config::POLY_LINE_MAX_POINT) {
                $newPolyLine = array();
                $ratio = $count / Config::POLY_LINE_MAX_POINT * 1.0;
                $i = 0.0;
                $lastIdx = 0;
                while ($i < $count) {
                    $idx = intval($i);
                    if ($idx % 2 != 0) {
                        $idx -= 1;
                    }

                    $lonMov = 0;
                    $latMov = 0;
                    if (($idx - $lastIdx) > 2) {
                        $restIdx = $idx - 2;

                        while ($restIdx > $lastIdx) {
                            $lonMov += $polyLine[$restIdx];
                            $latMov += $polyLine[$restIdx + 1];
                            $restIdx = $restIdx - 2;
                        }
                    }
                    $newPolyLine[] = $polyLine[$idx] + $lonMov;
                    $newPolyLine[] = $polyLine[$idx + 1] + $latMov;

                    $lastIdx = $idx;
                    $i += $ratio + 1;
                    if ($i >= $count) {
                        $idx += 2;
                        while ($idx < ($count - 2)) {
                            $newPolyLine[] = $polyLine[$idx];
                            $newPolyLine[] = $polyLine[$idx + 1];
                            $idx += 2;
                        }
                    }
                }
                $polyLine = $newPolyLine;
            }

            $t2 = microtime(true);

            $logDate = date("Y-m-d H:i:s", time());
            $logContent = $logDate . ' | ';
            $logContent .= 'wx_lbs_proc_time' . ' | ';
            $logContent .= Base::$traceId . ' | ';
            $logContent .= 'time=' . ($t2 - $t1);
            error_log($logContent . "\n", 3, Config::BIZ_LOG_PATH);

            return json_encode(array('polyline' => $polyLine));

        }
    }
}
