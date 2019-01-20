<?php
spl_autoload_register(function ($class) {
    $map = array(
        'Base' => APPPATH . "controllers/Base.php",
        'CommonRedis' => APPPATH . "models/redis/CommonRedis.php",
        'DbTansactionHanlder' => APPPATH . "models/transaction/DbTansactionHanlder.php",
        'Status' => APPPATH . "exception/Status.php",
        'StatusException' => APPPATH . "exception/StatusException.php",
        'Curl' => APPPATH . "libraries/Curl.php",
        'Config' => APPPATH . "libraries/Config.php",
        'TripDriverDetail' => APPPATH . "models/object/TripDriverDetail.php",
        'TripPassengerDetail' => APPPATH . "models/object/TripPassengerDetail.php",
        'CommonDao' => APPPATH . "models/dao/CommonDao.php",
        'TripDao' => APPPATH . "models/dao/TripDao.php",
    );

    if (isset($map[$class]) && file_exists($map[$class])) {
        include_once $map[$class];
    }else{
        $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        $file = APPPATH . "models/api". DIRECTORY_SEPARATOR . $path . '.php';
        if (file_exists($file)) {
            include_once $file;
        }
    }

    if (isset($map[$class]) && file_exists($map[$class])) {
        include_once $map[$class];
    }
});

