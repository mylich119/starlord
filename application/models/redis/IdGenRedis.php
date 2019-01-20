<?php

class IdGenRedis extends CommonRedis
{
    public function __construct()
    {
        parent::__construct();
    }


    public function gen($sKey = 'default')
    {
        $current = time();
        $count = $this->incr($sKey . $current);
        if ($count == 0) {
            $this->setTimeout($sKey . $current, 10);
        }
        $temp_num = 10000000;
        $new_num = $count + $temp_num;
        return $current . substr($new_num, 1, 7); //即截取掉最前面的“1”
    }
}
