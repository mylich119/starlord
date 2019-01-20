<?php

class TripDriverService extends CI_Model
{


    public function __construct()
    {
        parent::__construct();
    }

    public function getAllTripsCount()
    {
        $this->load->model('dao/TripDriverDao');
        $count = $this->TripDriverDao->getCountOfAll();

        return $count['total'];
    }

    //缓存，需要踢出
    public function getTripByTripId($userId, $tripId)
    {
        $this->load->model('dao/TripDriverDao');

        $cacheKey = 'TripDriverService_getTripByTripId' . $userId . $tripId;
        //缓存
        $this->load->model('redis/CacheRedis');
        $trip = $this->CacheRedis->getK($cacheKey);
        if ($trip != false) {
            return $trip;
        }

        $trip = $this->TripDriverDao->getOneByTripId($userId, $tripId);
        if (empty($trip)) {
            throw new StatusException(Status::$message[Status::TRIP_NOT_EXIST], Status::TRIP_NOT_EXIST);
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $trip);

        return $trip;
    }

    /*
    public function updateTrip($tripId, $userId, $tripDriverDetail)
    {
        if ($tripId == null || userId == null) {
            throw new StatusException(Status::$message[Status::TRIP_NOT_EXIST], Status::TRIP_NOT_EXIST);
        }

        $this->load->model('dao/TripDriverDao');

        $trip = $this->TripDriverDao->getOneByTripId($userId, $tripId);

        if ($trip['status'] != Config::TRIP_STATUS_NORMAL) {
            throw new StatusException(Status::$message[Status::TRIP_NOT_EXIST], Status::TRIP_NOT_EXIST);
        }

        $tripDriverDetail['share_img_url'] = $this->getDriverTripImageUrl($tripId, $tripDriverDetail['start_location_name'], $tripDriverDetail['end_location_name'], $tripDriverDetail['price_everyone'], $tripDriverDetail['price_total']);
        //只有正常状态的行程才允许编辑
        $this->TripDriverDao->updateByTripIdAndStatus($userId, $tripId, Config::TRIP_STATUS_NORMAL, $tripDriverDetail);

        return true;
    }
    */

    public function saveTripTemplate($tripId, $userId, $tripDriverDetail)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'TripDriverService_getMyTemplateList' . $userId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'TripDriverService_getTripByTripId' . $userId . $tripId;
        $this->CacheRedis->delK($cacheKey);


        $trip = array();
        $trip['trip_id'] = $tripId;
        $trip['user_id'] = $userId;
        $trip = array_merge($trip, $tripDriverDetail);

        $trip['status'] = Config::TRIP_STATUS_DRAFT;

        $this->load->model('dao/TripDriverDao');
        if ($tripId == null) {
            //创建新模板
            $this->load->model('redis/IdGenRedis');
            $trip['trip_id'] = $this->IdGenRedis->gen(Config::ID_GEN_KEY_TRIP);
            $this->TripDriverDao->insertOne($userId, $trip);
        } else {
            //更新旧模板
            $rows = $this->TripDriverDao->updateByTripIdAndStatus($userId, $tripId, Config::TRIP_STATUS_DRAFT, $trip);
            if ($rows == 0) {
                throw new StatusException(Status::$message[Status::TRIP_IS_NOT_TEMPLATE], Status::TRIP_IS_NOT_TEMPLATE);
            }
        }

        return true;
    }

    public function createNewTrip($userId, $tripDriverDetail, $user)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'TripDriverService_getMyTripList' . $userId;
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/TripDriverDao');
        $this->load->model('redis/IdGenRedis');

        $trip = array();
        $trip['user_id'] = $userId;
        $trip['trip_id'] = $this->IdGenRedis->gen(Config::ID_GEN_KEY_TRIP);
        $trip = array_merge($trip, $tripDriverDetail);
        $trip['status'] = Config::TRIP_STATUS_NORMAL;

        $trip['share_img_url'] = $this->getDriverTripImageUrl($trip['trip_id'], $trip['begin_date'], $trip['begin_time'], $trip['start_location_name'], $trip['end_location_name'], $trip['price_everyone'], $trip['price_total']);

        //插入用户信息快照
        $trip['user_info'] = json_encode(
            array(
                "user_id" => $user["user_id"],
                "phone" => $user["phone"],
                "nick_name" => $user["nick_name"],
                "gender" => $user["gender"],
                "city" => $user["city"],
                "province" => $user["province"],
                "country" => $user["country"],
                "avatar_url" => $user["avatar_url"],
                "car_plate" => $user["car_plate"],
                "car_brand" => $user["car_brand"],
                "car_model" => $user["car_model"],
                "car_color" => $user["car_color"],
                "car_type" => $user["car_type"],
            )
        );

        $this->load->model('api/WxApi');
        try {
            $trip['lbs_route_info'] = $this->WxApi->getRoutesByFromAndTo($trip['start_location_point'], $trip['end_location_point']);
        } catch (StatusException $e) {
            $trip['lbs_route_info'] = null;
            //日志
        }

        $newTrip = $this->TripDriverDao->insertOne($userId, $trip);

        return $newTrip;
    }

    public function addGroupInfoToTrip($userId, $tripId, $trip, $group)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'TripDriverService_getTripByTripId' . $userId . $tripId;
        $this->CacheRedis->delK($cacheKey);

        unset($group['id']);
        unset($group['status']);
        unset($group['is_del']);
        unset($group['created_time']);
        unset($group['modified_time']);

        $groupInfoJson = $trip['group_info'];
        if (empty($groupInfoJson)) {
            $groups = array();
            $groups[] = $group;
            $trip['group_info'] = json_encode($groups);
        } else {
            $groups = json_decode($groupInfoJson, true);
            $groups[] = $group;
            $trip['group_info'] = json_encode($groups);
        }

        //只有正常状态的行程才允许编辑
        $this->TripDriverDao->updateByTripIdAndStatus($userId, $tripId, Config::TRIP_STATUS_NORMAL, $trip);

        return;
    }

    public function deleteTrip($userId, $tripId)
    {
        //踢出缓存
        $this->load->model('redis/CacheRedis');
        $cacheKey = 'TripDriverService_getMyTripList' . $userId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'TripDriverService_getTripByTripId' . $userId . $tripId;
        $this->CacheRedis->delK($cacheKey);
        $cacheKey = 'TripDriverService_getMyTemplateList' . $userId;
        $this->CacheRedis->delK($cacheKey);

        $this->load->model('dao/TripDriverDao');
        $ret = $this->TripDriverDao->deleteOne($userId, $tripId);

        return $ret;
    }

    //缓存，需要踢出
    public function getMyTripList($userId)
    {
        $this->load->model('dao/TripDriverDao');

        $cacheKey = 'TripDriverService_getMyTripList' . $userId;
        //读取缓存
        $this->load->model('redis/CacheRedis');
        $trips = $this->CacheRedis->getK($cacheKey);
        if ($trips != false) {
            return $trips;
        }

        $trips = $this->TripDriverDao->getListByUserIdAndStatusArr($userId, array(Config::TRIP_STATUS_NORMAL, Config::TRIP_STATUS_CANCEL));
        if (empty($trips)) {
            return array();
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $trips);

        return $trips;
    }

    //缓存，需要踢出
    public function getMyTemplateList($userId)
    {
        $this->load->model('dao/TripDriverDao');

        $cacheKey = 'TripDriverService_getMyTemplateList' . $userId;
        //读取缓存
        $this->load->model('redis/CacheRedis');
        $tripsWithType = $this->CacheRedis->getK($cacheKey);
        if ($tripsWithType != false) {
            return $tripsWithType;
        }

        $trips = $this->TripDriverDao->getListByUserIdAndStatusArr($userId, array(Config::TRIP_STATUS_DRAFT));
        $tripsWithType = array();
        if (!empty($trips)) {
            foreach ($trips as $trip) {
                $trip['trip_type'] = Config::TRIP_TYPE_DRIVER;
                if ($trip['begin_date'] == Config::EVERYDAY_DATE) {
                    $trip['is_everyday'] = 1;
                } else {
                    $trip['is_everyday'] = 0;
                }
                $tripsWithType[] = $trip;
            }
        }

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $tripsWithType);

        return $tripsWithType;
    }

    private function getDriverTripImageUrl($tripId, $beginDate, $beginTime, $startLocationName, $endLocationName, $priceEveryone, $priceTotal)
    {
        $this->load->model('api/OssApi');

        $source = '/home/starlord/application/imgs/bg_driver.png';//车找人底图
        $firstNew = "/home/res/" . $tripId . "1.png";
        $secondNew = "/home/res/" . $tripId . "2.png";
        $thirdNew = "/home/res/" . $tripId . "3.png";
        $forthNew = "/home/res/" . $tripId . "4.png";

        $v = null;
        if ($beginDate == Config::EVERYDAY_DATE) {
            $v = '每天 ' . $beginTime;
        } else {
            $v = $beginDate . ' ' . $beginTime;
        }
        $firstLine = array(
            'wm_text' => $v,//这是开始时间
            'wm_type' => 'text',
            'wm_font_path' => '/home/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '26',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '124',
            'wm_hor_offset' => '72',
        );

        $v = null;
        if (mb_strlen($startLocationName) > Config::SHARE_LOC_NAME_LEN) {
            $v = mb_substr($startLocationName, 0, Config::SHARE_LOC_NAME_LEN);
            $v .= '...';
        } else {
            $v = $startLocationName;
        }
        $secondLine = array(
            'wm_text' => $v,//显示开始的位置名称，需要用php截断字符长度
            'wm_type' => 'text',
            'wm_x_transp' => 0,
            'wm_font_path' => '/home/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '26',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '190',
            'wm_hor_offset' => '72',
        );

        $v = null;
        if (mb_strlen($endLocationName) > Config::SHARE_LOC_NAME_LEN) {
            $v = mb_substr($endLocationName, 0, Config::SHARE_LOC_NAME_LEN);
            $v .= '...';
        } else {
            $v = $endLocationName;
        }
        $thirdLine = array(
            'wm_text' => $v,//显示结束的位置名称，需要用php截断字符长度
            'wm_type' => 'text',
            'wm_x_transp' => 0,
            'wm_font_path' => '/home/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '26',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '258',
            'wm_hor_offset' => '72',
        );

        if (empty($priceEveryone)) {
            $priceEveryone = '面议';
        } else {
            $priceEveryone .= '元/人';
        }
        if (empty($priceTotal)) {
            $priceTotal = '面议';
        }else{
            $priceTotal .= '元';
        }
        $v = '价格：' . $priceEveryone . ', 包车：' . $priceTotal;
        $forthLine = array(
            'wm_text' => $v,//显示结束的位置名称，需要用php截断字符长度
            'wm_type' => 'text',
            'wm_x_transp' => 0,
            'wm_font_path' => '/home/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '26',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '320',
            'wm_hor_offset' => '72',
        );

        $this->imgHandler($source, $firstNew, $firstLine, true);
        $this->imgHandler($firstNew, $secondNew, $secondLine, true);
        $this->imgHandler($secondNew, $thirdNew, $thirdLine, true);
        $this->imgHandler($thirdNew, $forthNew, $forthLine, true);

        $this->OssApi->uploadImg('prod/' . $tripId . '.png', $forthNew);

        unlink($firstNew);
        unlink($secondNew);
        unlink($thirdNew);
        unlink($forthNew);

        return $this->OssApi->getSignedUrlForGettingObject($tripId);
    }

    public function imgHandler($source, $new, $config, $output2File)
    {
        $config['image_library'] = 'gd2';
        $config['source_image'] = $source;
        $config['new_image'] = $new;
        $config['output_2_file'] = $output2File;

        $this->load->library('image_lib', $config);
        $this->image_lib->initialize($config);
        $this->image_lib->watermark();
        $this->image_lib->clear();
    }
}
