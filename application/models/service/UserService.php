<?php

class UserService extends CI_Model
{


    public function __construct()
    {
        parent::__construct();

    }

    //缓存，需要踢出
    public function getUserByOpenId($openId)
    {
        $cacheKey = 'UserService_getUserByOpenId' . $openId;
        //缓存
        $this->load->model('redis/CacheRedis');
        $user = $this->CacheRedis->getK($cacheKey);
        if ($user != false) {
            return $user;
        }

        $this->load->model('dao/UserDao');
        $user = $this->UserDao->getOneByOpenId($openId);

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $user);

        return $user;
    }

    //缓存，需要踢出
    public function getUserByTicket($ticket)
    {
        $cacheKey = 'UserService_getUserByTicket' . $ticket;
        //缓存
        $this->load->model('redis/CacheRedis');
        $user = $this->CacheRedis->getK($cacheKey);
        if ($user != false) {
            return $user;
        }

        $this->load->model('dao/UserDao');
        $user = $this->UserDao->getOneByTicket($ticket);

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $user);

        return $user;
    }

    //缓存，需要踢出
    public function getUserByUserId($userId)
    {
        $cacheKey = 'UserService_getUserByUserId' . $userId;
        //缓存
        $this->load->model('redis/CacheRedis');
        $user = $this->CacheRedis->getK($cacheKey);
        if ($user != false) {
            return $user;
        }

        $this->load->model('dao/UserDao');

        $user = $this->UserDao->getOneByUserId($userId);

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $user);

        return $user;
    }

    public function createNewUser($sessionKey, $openId, $ticket, $isValid)
    {
        $this->load->model('redis/IdGenRedis');
        $this->load->model('dao/UserDao');

        $user = array();
        $user['user_id'] = $this->IdGenRedis->gen(Config::ID_GEN_KEY_USER);
        $user['wx_session_key'] = $sessionKey;
        $user['wx_open_id'] = $openId;
        $user['ticket'] = $ticket;
        $user['is_valid'] = $isValid;
        $user['audit_status'] = Config::USER_AUDIT_STATUS_FAIL;
        $user['need_publish_guide'] = Config::USER_NEED_PUBLISH_GUIDE;
        $user['status'] = Config::USER_STATUS_OK;
        $user['show_agreement'] = Config::USER_HAS_NOT_READ;

        return $this->UserDao->insertOne($user);
    }

    public function updateSessionKeyAndTicketByUser($oldUser, $sessionKey, $ticket)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'UserService_getUserByOpenId' . $oldUser['wx_open_id'];
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'UserService_getUserByTicket' . $oldUser['ticket'];
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'UserService_getUserByUserId' . $oldUser['user_id'];
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/UserDao');
        $user = array();
        $user['wx_session_key'] = $sessionKey;
        $user['ticket'] = $ticket;

        return $this->UserDao->updateOneByUserId($oldUser['user_id'], $user);
    }

    public function updateUser($user)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'UserService_getUserByOpenId' . $user['wx_open_id'];
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'UserService_getUserByTicket' . $user['ticket'];
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'UserService_getUserByUserId' . $user['user_id'];
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/UserDao');
        if (empty($user) || empty($user['user_id'])) {
            throw new StatusException(Status::$message[Status::USER_NOT_EXIST], Status::USER_NOT_EXIST);
        }

        return $this->UserDao->updateOneByUserId($user['user_id'], $user);
    }
}
