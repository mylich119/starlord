<?php
if (!function_exists('preController')) {
    function preController()
    {
        //exception handling
        set_exception_handler('returnFail');
    }
}
