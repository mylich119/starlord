<?php

class TestService extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

    }


    public function add($route, $start_loc, $end_loc)
    {
        $this->load->model('dao/TestDao');

        $locArr[] = $route;
        $locArr[] = $start_loc;
        $locArr[] = $end_loc;

        return $this->TestDao->add($locArr);
    }

    public function search($target_start, $target_end, $count)
    {
        $this->load->model('dao/TestDao');
        return $this->TestDao->search($target_start, $target_end, $count);

    }


    public function set($v)
    {
        $this->load->model('redis/CacheRedis');
        $this->CacheRedis->setKV('aaa', $v);
    }

    public function get()
    {
        $this->load->model('redis/CacheRedis');
        return $this->CacheRedis->getK('aaa');
    }

    public function del()
    {
        $this->load->model('redis/CacheRedis');
        return $this->CacheRedis->delK('aaa');
    }

    public function lock()
    {
        $this->load->model('redis/LockRedis');
        return $this->LockRedis->lock('aaa');
    }

    public function unlock()
    {
        $this->load->model('redis/LockRedis');
        return $this->LockRedis->unLock('aaa');
    }
}
