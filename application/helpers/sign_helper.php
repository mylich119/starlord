<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

define("POPE_SIGN_KEY","pope_action_proxy");

if(!function_exists("buildSign4System")){
    function buildSign4System($aParams){
        if (empty($aParams)) {
            return '';
        }
        $key = POPE_SIGN_KEY;
        unset($aParams['sign']);
        ksort($aParams);
        $query_string = '';
        foreach ($aParams as $k => $v) {
            if (is_array($v)) {
                $v = json_encode($v);
            }
            $query_string .= "{$k}={$v}&";
        }
        $sig = mb_strtoupper(md5($query_string . "key=" . md5($key)));
        return $sig;
    }
}
