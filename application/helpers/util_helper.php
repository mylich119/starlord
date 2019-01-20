<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('generateUuid')) {
    function generateUuid(){
        return uuid_create();
    }
}


