<?php

class GroupUserService extends CI_Model
{


    public function __construct()
    {
        parent::__construct();

    }

    public function getCountByGroupId($groupId)
    {
        $this->load->model('dao/GroupUserDao');
        $ret = $this->GroupUserDao->getCountByGroupId($groupId);
        return $ret['total'];
    }

    //缓存，需要踢出
    public function ensureUserBelongToGroup($userId, $groupId)
    {
        $cacheKey = 'GroupUserService_ensureUserBelongToGroup' . $userId . $groupId;
        //缓存
        $this->load->model('redis/CacheRedis');
        $ret = $this->CacheRedis->getK($cacheKey);
        if ($ret != false) {
            return;
        }

        $this->load->model('dao/GroupUserDao');
        if ($userId == null || $groupId == null) {
            throw new StatusException(Status::$message[Status::GROUP_EXCLUDE_USER], Status::GROUP_EXCLUDE_USER);
        }

        $ret = $this->GroupUserDao->getOneByGroupIdAndUserId($userId, $groupId);
        if (empty($ret)) {
            throw new StatusException(Status::$message[Status::GROUP_USER_INVALID], Status::GROUP_USER_INVALID);
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, '1');

        return;
    }

    //缓存，需要踢出
    public function getGroupsByUserId($userId)
    {
        $cacheKey = 'GroupUserService_getGroupsByUserId' . $userId;
        //缓存
        $this->load->model('redis/CacheRedis');
        $groups = $this->CacheRedis->getK($cacheKey);
        if ($groups != false) {
            return $groups;
        }

        $this->load->model('dao/GroupUserDao');
        if ($userId == null) {
            throw new StatusException(Status::$message[Status::GROUP_USER_INVALID], Status::GROUP_USER_INVALID);
        }

        $ret = $this->GroupUserDao->getGroupsByUserId($userId);
        if (empty($ret)) {
            $groups = array();
        } else {
            $groups = array();
            foreach ($ret as $v) {
                $groups[] = array('group_id' => $v['group_id'], 'wx_gid' => $v['wx_gid']);
            }
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $groups);

        return $groups;
    }

    public function add($userId, $groupId, $wxGid)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'GroupUserService_ensureUserBelongToGroup' . $userId . $groupId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupUserService_getGroupsByUserId' . $userId;
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/GroupUserDao');
        if ($userId == null || $groupId == null) {
            throw new StatusException(Status::$message[Status::GROUP_USER_INVALID], Status::GROUP_USER_INVALID);
        }

        $ret = $this->GroupUserDao->getOneByGroupIdAndUserId($userId, $groupId);
        if (empty($ret) || !is_array($ret) || count($ret) == 0) {
            $this->GroupUserDao->insertOne($userId, $groupId, $wxGid);
            return true;
        }

        return false;
    }

    public function delete($userId, $groupId)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'GroupUserService_ensureUserBelongToGroup' . $userId . $groupId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupUserService_getGroupsByUserId' . $userId;
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/GroupUserDao');
        if ($userId == null || $groupId == null) {
            throw new StatusException(Status::$message[Status::GROUP_USER_INVALID], Status::GROUP_USER_INVALID);
        }

        return $this->GroupUserDao->deleteOne($userId, $groupId);
    }
}
