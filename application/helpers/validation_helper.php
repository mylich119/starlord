<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

if (!function_exists('ensureNotNull')) {
    function ensureNotNull($v){
        if (!isset($v)) {
            throw new StatusException(Status::$message[Status::VALIDATION_IS_NULL], Status::VALIDATION_IS_NULL, var_export($v, true));
        }

        return $v;
    }
}

if (!function_exists('ensureNull')) {
    function ensureNull($v){
        if ($v !== null) {
            throw new StatusException(Status::$message[Status::VALIDATION_IS_NOT_NULL], Status::VALIDATION_IS_NOT_NULL, var_export($v, true));
        }
        return $v;
    }
}


if (!function_exists('ensureEqual')) {
    function ensureEqual($v, $b){
        if ($v != $b) {
            throw new StatusException(Status::$message[Status::VALIDATION_NOT_EQUAL], Status::VALIDATION_NOT_EQUAL, $v . ' and ' . $b . 'is not equal');
        }
        return $v;
    }
}


if (!function_exists('ensureGE')) {
    function ensureGE($v, $b){
        if (!isset($v) || $v < $b) {
            throw new StatusException(Status::$message[Status::VALIDATION_GREATER_OR_EQUAL], Status::VALIDATION_GREATER_OR_EQUAL, $v . ' is not greater or equal than ' . $b );
        }
        return $v;
    }
}

if (!function_exists('ensureNotEqual')) {
    function ensureNotEqual($v, $b){
        if ($v == $b) {
            throw new StatusException(Status::$message[Status::VALIDATION_EQUAL], Status::VALIDATION_EQUAL, $v . ' and ' . $b . ' is equal');
        }
        return $v;
    }
}

if (!function_exists('ensureArray')) {
    function ensureArray($v){
        if (!is_array($v)) {
            throw new StatusException(Status::$message[Status::VALIDATION_ARRAY], Status::VALIDATION_ARRAY, var_export($v, true));
        }
        return $v;
    }
}

if (!function_exists('ensureTrue')) {
    function ensureTrue($v){
        if ($v != true) {
            throw new StatusException(Status::$message[Status::VALIDATION_NOT_TRUE], Status::VALIDATION_NOT_TRUE, var_export($v, true));
        }
        return $v;
    }
}

if (!function_exists('ensureFalse')) {
    function ensureFalse($v){
        if ($v != false) {
            throw new StatusException(Status::$message[Status::VALIDATION_NOT_FALSE], Status::VALIDATION_NOT_FALSE, var_export($v, true));
        }
        return $v;
    }
}
