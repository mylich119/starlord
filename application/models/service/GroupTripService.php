<?php

class GroupTripService extends CI_Model
{


    public function __construct()
    {
        parent::__construct();

    }

    public function getCountByGroupId($groupId)
    {
        $currentDate = date('Y-m-d');

        $this->load->model('dao/GroupTripDao');
        $ret = $this->GroupTripDao->getCountByGroupId($groupId, $currentDate);
        return $ret['total'];
    }

    //缓存，需要踢出
    //确认群内有行程
    public function ensureGroupHasTrip($groupId, $tripId)
    {
        $cacheKey = 'GroupTripService_ensureGroupHasTrip' . $groupId . $tripId;
        //缓存
        $this->load->model('redis/CacheRedis');
        $ret = $this->CacheRedis->getK($cacheKey);
        if ($ret != false) {
            return;
        }

        $this->load->model('dao/GroupTripDao');

        $groupTrip = $this->GroupTripDao->getOneByGroupIdAndTripId($groupId, $tripId);
        if (empty($groupTrip)) {
            throw new StatusException(Status::$message[Status::GROUP_HAS_NO_TRIP], Status::GROUP_HAS_NO_TRIP);
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, '1');

        return;
    }

    //缓存，需要踢出
    //获取当前date之后的，status为正常的行程tripid
    public function getCurrentTripIdsByGroupIdAndTripType($groupId, $tripType)
    {
        $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . $tripType;
        //缓存
        $this->load->model('redis/CacheRedis');
        $ret = $this->CacheRedis->getK($cacheKey);
        if ($ret != false) {
            return $ret;
        }

        $currentDate = date('Y-m-d');
        $this->load->model('dao/GroupTripDao');

        $groupTripsWithTopTime = $this->GroupTripDao->getListByGroupIdAndDateWithTopTime($groupId, $currentDate, $tripType);
        $groupTripsWithoutTopTime = $this->GroupTripDao->getListByGroupIdAndDateWithoutTopTime($groupId, $currentDate, $tripType);
        $groupTrips = array_merge($groupTripsWithTopTime, $groupTripsWithoutTopTime);
        $ret = array();

        //从group的trip extend info中解压出行程快照，快照在发布和更新行程的时候写入
        if (is_array($groupTrips) && count($groupTrips) > 0) {
            foreach ($groupTrips as $groupTrip) {
                $trip = json_decode($groupTrip['extend_json_info'], true);
                $trip['top_time'] = $groupTrip['top_time'];
                $ret[] = $trip;
            }
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $ret);

        return $ret;
    }


    public function publishTripToGroup($tripId, $groupId, $trip, $tripType)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'GroupTripService_ensureGroupHasTrip' . $groupId . $tripId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . $tripType;
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/GroupTripDao');

        $groupTrips = array();
        $groupTrip = array();
        $groupTrip['trip_id'] = $tripId;
        $groupTrip['group_id'] = $groupId;
        $groupTrip['top_time'] = null;
        $groupTrip['trip_begin_date'] = $trip['begin_date'];
        $groupTrip['trip_type'] = $tripType;
        $groupTrip['status'] = Config::GROUP_TRIP_STATUS_DEFAULT;
        $groupTrip['extend_json_info'] = json_encode($trip);
        $groupTrips[] = $groupTrip;

        $this->GroupTripDao->insertMulti($groupTrips);
    }

    public function deleteTripsFromGroup($tripId, $groupIds, $tripType)
    {
        $this->load->model('redis/CacheRedis');
        $this->load->model('dao/GroupTripDao');

        //踢出缓存
        foreach ($groupIds as $groupId) {
            $cacheKey = 'GroupTripService_ensureGroupHasTrip' . $groupId . $tripId;
            $this->CacheRedis->delK($cacheKey);
            $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . $tripType;
            $this->CacheRedis->delK($cacheKey);
        }

        return $this->GroupTripDao->deleteByTripId($tripId);
    }


    public function topOneTrip($groupId, $tripId)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'GroupTripService_ensureGroupHasTrip' . $groupId . $tripId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . Config::TRIP_TYPE_DRIVER;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . Config::TRIP_TYPE_DRIVER;
        $this->CacheRedis->delK($cacheKey);

        $currentTime = date("Y-M-d H:i:s", time());
        $this->load->model('dao/GroupTripDao');

        $groupTrip = $this->GroupTripDao->getOneByGroupIdAndTripId($groupId, $tripId);
        if (empty($groupTrip)) {
            throw new StatusException(Status::$message[Status::GROUP_HAS_NO_TRIP], Status::GROUP_HAS_NO_TRIP);
        }

        $groupTrip['top_time'] = $currentTime;
        return $this->GroupTripDao->updateByTripId($groupId, $tripId, $groupTrip);
    }

    public function unTopOneTrip($groupId, $tripId)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'GroupTripService_ensureGroupHasTrip' . $groupId . $tripId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . Config::TRIP_TYPE_DRIVER;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'GroupTripService_getCurrentTripIdsByGroupIdAndTripType' . $groupId . Config::TRIP_TYPE_DRIVER;
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/GroupTripDao');

        $groupTrip = $this->GroupTripDao->getOneByGroupIdAndTripId($groupId, $tripId);
        if (empty($groupTrip)) {
            throw new StatusException(Status::$message[Status::GROUP_HAS_NO_TRIP], Status::GROUP_HAS_NO_TRIP);
        }
        $groupTrip['top_time'] = null;
        return $this->GroupTripDao->updateByTripId($groupId, $tripId, $groupTrip);
    }

}
