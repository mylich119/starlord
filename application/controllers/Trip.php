<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Trip extends Base
{

    public function __construct()
    {
        parent::__construct();

    }

    public function getTripDetailInSharePage()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripUserId = $input['user_id'];
        $tripId = $input['trip_id'];
        $tripType = $input['trip_type'];

        $this->load->model('api/WxApi');
        $this->load->model('service/GroupService');
        $this->load->model('service/GroupTripService');
        $this->load->model('service/GroupService');
        $this->load->model('service/GroupUserService');

        //先检查群是否存在，如果不存在则创建群，最终获取group_id
        DbTansactionHanlder::begin('default');
        try {
            $encryptedData = $input['encryptedData'];
            $iv = $input['iv'];
            $sessionKey = $user['wx_session_key'];

            $trip = $this->_getDetailByTripId($tripType, $tripUserId, $tripId);
            $retTrip = $trip;

            if (!empty($encryptedData)) {
                //绑定群相关信息
                $groupInfo = $this->WxApi->decryptGroupInfo($sessionKey, $encryptedData, $iv);
                $wxGid = $groupInfo['openGId'];
                $group = $this->GroupService->getByWxGid($wxGid);
                if (empty($group)) {
                    //如果不存在群组，则创建member_num为0的新群组
                    $group = $this->GroupService->createNewGroup($wxGid);
                }
                $groupId = $group['group_id'];
                $retTrip['group_id'] = $groupId;

                //绑定人
                try {
                    //检查人是否存在
                    $this->GroupUserService->ensureUserBelongToGroup($userId, $groupId);
                } catch (Exception $e) {
                    //如果不存在
                    $ret = $this->GroupUserService->add($userId, $groupId, $wxGid);
                    if ($ret) {
                        //用户是第一次加入群，需要把group的member_num加1
                        $this->GroupService->increaseMember($groupId);
                    }
                }

                //绑定行程
                try {
                    //检查行程是否在群里

                    $this->GroupTripService->ensureGroupHasTrip($groupId, $tripId);
                } catch (Exception $e) {
                    //否则把行程加群
                    $this->GroupTripService->publishTripToGroup($tripId, $groupId, $trip, $tripType);
                    $this->GroupService->increaseTripInGroup($groupId);

                    if ($tripType == Config::TRIP_TYPE_DRIVER) {
                        $this->TripDriverService->addGroupInfoToTrip($tripUserId, $tripId, $trip, $group);
                    } else {
                        $this->TripPassengerService->addGroupInfoToTrip($tripUserId, $tripId, $trip, $group);
                    }
                }

                if ($userId == $retTrip['user_id']) {
                    $retTrip['is_share_owner'] = Config::IS_SHARE_OWNER;
                } else {
                    $retTrip['is_share_owner'] = Config::IS_NOT_SHARE_OWNER;
                }
            }

            $this->_formatOutputTrip($retTrip);
            DbTansactionHanlder::commit('default');
            $this->_returnSuccess($retTrip);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //获取群内行程列表
    //分页
    public function driverGetListByGroupId()
    {
        $trips = $this->_getListByGroupId(Config::TRIP_TYPE_DRIVER);
        $input = $this->input->post();
        $page = $input['page'];

        if (empty($page)) {
            $page = 0;
        }

        $retTrips = array_slice($trips, $page * Config::TRIP_EACH_PAGE, Config::TRIP_EACH_PAGE);
        $hasNext = true;
        if (count($retTrips) < Config::TRIP_EACH_PAGE) {
            $hasNext = false;
        }
        $this->_returnSuccess(
            array(
                'page' => $page,
                'has_next' => $hasNext,
                'trips' => $retTrips,
            )
        );

    }

    //分页
    public function passengerGetListByGroupId()
    {
        $trips = $this->_getListByGroupId(Config::TRIP_TYPE_PASSENGER);
        $input = $this->input->post();
        $page = $input['page'];

        if (empty($page)) {
            $page = 0;
        }

        $retTrips = array_slice($trips, $page * Config::TRIP_EACH_PAGE, Config::TRIP_EACH_PAGE);
        $hasNext = true;
        if (count($retTrips) < Config::TRIP_EACH_PAGE) {
            $hasNext = false;
        }
        $this->_returnSuccess(
            array(
                'page' => $page,
                'has_next' => $hasNext,
                'trips' => $retTrips,
            )
        );

    }

    private function _sortTripsInGroup($trips)
    {
        $topTripsSortKeys = array();
        $topTrips = array();

        $restTripsSortKeys = array();
        $restTrips = array();

        foreach ($trips as $trip) {
            $this->_formatOutputTrip($trip);

            unset($trip['group_info']);
            unset($trip['route']);
            unset($trip['driver_no_smoke']);
            unset($trip['driver_last_mile']);
            unset($trip['driver_goods']);
            unset($trip['driver_need_drive']);
            unset($trip['driver_chat']);
            unset($trip['driver_highway']);
            unset($trip['driver_pet']);
            unset($trip['driver_cooler']);
            unset($trip['tips']);
            unset($trip['share_img_url']);
            unset($trip['lbs_route_info']);
            unset($trip['passenger_no_smoke']);
            unset($trip['passenger_last_mile']);
            unset($trip['passenger_goods']);
            unset($trip['passenger_can_drive']);
            unset($trip['passenger_chat']);
            unset($trip['passenger_luggage']);
            unset($trip['passenger_pet']);
            unset($trip['passenger_no_carsickness']);

            if (empty($trip['top_time'])) {
                $restTripsSortKeys[] = $trip['created_time'];
                $restTrips[] = $trip;
            } else {
                $topTripsSortKeys[] = $trip['top_time'];
                $topTrips[] = $trip;
            }
        }

        array_multisort($topTripsSortKeys, SORT_DESC, SORT_REGULAR, $topTrips);
        array_multisort($restTripsSortKeys, SORT_DESC, SORT_REGULAR, $restTrips);

        return array_merge($topTrips, $restTrips);
    }

    private function _getListByGroupId($tripType)
    {
        $this->load->model('service/TripPassengerService');
        $this->load->model('service/GroupTripService');
        $this->load->model('service/GroupUserService');

        $user = $this->_user;
        $userId = $user['user_id'];

        $input = $this->input->post();
        $groupId = $input['group_id'];
        if ($groupId == null) {
            throw new StatusException(Status::$message[Status::GROUP_NOT_EXIST], Status::GROUP_NOT_EXIST);
        }

        //确保群内有该用户
        $this->GroupUserService->ensureUserBelongToGroup($userId, $groupId);

        //获取当前date之后的trips
        $trips = $this->GroupTripService->getCurrentTripIdsByGroupIdAndTripType($groupId, $tripType);

        //格式化群内行程,按照createdtime排序，toptime置顶
        return $this->_sortTripsInGroup($trips);
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //获取行程详情
    public function driverGetDetailByTripId()
    {
        $input = $this->input->post();
        $userId = $input['user_id'];
        $tripId = $input['trip_id'];

        $trip = $this->_getDetailByTripId(Config::TRIP_TYPE_DRIVER, $userId, $tripId);
        $this->_formatOutputTrip($trip);
        $this->_returnSuccess($trip);
    }

    public function passengerGetDetailByTripId()
    {
        $input = $this->input->post();
        $userId = $input['user_id'];
        $tripId = $input['trip_id'];

        $trip = $this->_getDetailByTripId(Config::TRIP_TYPE_PASSENGER, $userId, $tripId);
        $this->_formatOutputTrip($trip);
        $this->_returnSuccess($trip);
    }

    private function _getDetailByTripId($tripType, $userId, $tripId)
    {

        if ($tripId == null) {
            throw new StatusException(Status::$message[Status::TRIP_NOT_EXIST], Status::TRIP_NOT_EXIST);
        }

        //无需鉴权，所有用户都能看行程详情，因为分享页需要
        $trip = null;
        if ($tripType == Config::TRIP_TYPE_DRIVER) {
            $this->load->model('service/TripDriverService');
            $trip = $this->TripDriverService->getTripByTripId($userId, $tripId);

        } else {
            $this->load->model('service/TripPassengerService');
            $trip = $this->TripPassengerService->getTripByTripId($userId, $tripId);
        }

        return $trip;
    }
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //保存行程到模板
    public function driverSave()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];

        $tripDriverDetail = new TripDriverDetail($input);

        $this->load->model('service/TripDriverService');

        DbTansactionHanlder::begin('default');
        try {
            $ret = $this->TripDriverService->saveTripTemplate($tripId, $userId, $tripDriverDetail->getTripArray());
            DbTansactionHanlder::commit('default');
            $this->_returnSuccess($ret);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    public function passengerSave()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];

        $tripPassengerDetail = new TripPassengerDetail($input);

        $this->load->model('service/TripPassengerService');

        DbTansactionHanlder::begin('default');
        try {
            $ret = $this->TripPassengerService->saveTripTemplate($tripId, $userId, $tripPassengerDetail->getTripArray());
            DbTansactionHanlder::commit('default');
            $this->_returnSuccess($ret);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }

    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //发布行程
    public function driverPublish()
    {
        $input = $this->input->post();
        $user = $this->_user;

        //没有手机号码的人无法发布行程
        if (empty($user['phone'])) {
            throw new StatusException(Status::$message[Status::TRIP_HAS_NO_AUTH_TO_PUBLISH], Status::TRIP_HAS_NO_AUTH_TO_PUBLISH);
        }

        $userId = $user['user_id'];
        $tripDriverDetail = new TripDriverDetail($input);

        $this->load->model('service/TripDriverService');

        if ($user['status'] == Config::USER_STATUS_FROZEN || $user['audit_status'] == Config::USER_AUDIT_STATUS_FAIL) {
            throw new StatusException(Status::$message[Status::TRIP_HAS_NO_AUTH_TO_PUBLISH], Status::TRIP_HAS_NO_AUTH_TO_PUBLISH);
        }
        DbTansactionHanlder::begin('default');
        try {
            //发布到trip表
            $newTrip = $this->TripDriverService->createNewTrip($userId, $tripDriverDetail->getTripArray(), $this->_user);
            DbTansactionHanlder::commit('default');

            $newTrip['trip_id'] = $newTrip['trip_id'] . "";

            $this->_formatOutputTrip($newTrip);

            $this->_returnSuccess($newTrip);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    public function passengerPublish()
    {
        $input = $this->input->post();
        $user = $this->_user;

        //没有手机号码的人无法发布行程
        if (empty($user['phone'])) {
            throw new StatusException(Status::$message[Status::TRIP_HAS_NO_AUTH_TO_PUBLISH], Status::TRIP_HAS_NO_AUTH_TO_PUBLISH);
        }

        $userId = $user['user_id'];

        $tripPassengerDetail = new TripPassengerDetail($input);

        $this->load->model('service/TripPassengerService');

        if ($user['status'] == Config::USER_STATUS_FROZEN || $user['audit_status'] == Config::USER_AUDIT_STATUS_FAIL) {
            throw new StatusException(Status::$message[Status::TRIP_HAS_NO_AUTH_TO_PUBLISH], Status::TRIP_HAS_NO_AUTH_TO_PUBLISH);
        }
        DbTansactionHanlder::begin('default');
        try {
            //发布到trip表
            $newTrip = $this->TripPassengerService->createNewTrip($userId, $tripPassengerDetail->getTripArray(), $this->_user);
            DbTansactionHanlder::commit('default');

            $newTrip['trip_id'] = $newTrip['trip_id'] . "";

            $this->_formatOutputTrip($newTrip);

            $this->_returnSuccess($newTrip);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //更新我的行程
    /*
    public function driverUpdateMy()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];

        $tripDriverDetail = new TripDriverDetail($input);

        $this->load->model('service/TripDriverService');
        DbTansactionHanlder::begin('default');
        try {
            $ret = $this->TripDriverService->updateTrip($tripId, $userId, $tripDriverDetail->getTripArray());
            DbTansactionHanlder::commit('default');

            $this->_returnSuccess($ret);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    public function passengerUpdateMy()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];

        $tripPassengerDetail = new TripPassengerDetail($input);

        $this->load->model('service/TripPassengerService');

        DbTansactionHanlder::begin('default');
        try {
            $ret = $this->TripPassengerService->updateTrip($tripId, $userId, $tripPassengerDetail->getTripArray());
            DbTansactionHanlder::commit('default');

            $this->_returnSuccess($ret);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }
    */
    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //取消我的行程
    public function driverDeleteMy()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];

        $this->load->model('service/TripDriverService');
        DbTansactionHanlder::begin('default');
        try {
            $trip = $this->TripDriverService->getTripByTripId($userId, $tripId);
            //鉴权不过，无法删除
            if (empty($trip)) {
                throw new StatusException(Status::$message[Status::TRIP_NOT_EXIST], Status::TRIP_NOT_EXIST);
            }

            $this->TripDriverService->deleteTrip($userId, $tripId);

            //获取用户所在群的id
            $this->load->model('service/GroupUserService');
            $groups = $this->GroupUserService->getGroupsByUserId($userId);
            $groupIds = array();
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $groupIds[] = $group['group_id'];
                }
            }
            if (!empty($groupIds)) {
                $this->load->model('service/GroupTripService');
                $this->load->model('service/GroupService');
                $this->GroupTripService->deleteTripsFromGroup($tripId, $groupIds, Config::TRIP_TYPE_DRIVER);
                $this->GroupService->decreaseTripInGroups($groupIds);
            }

            DbTansactionHanlder::commit('default');
            $this->_returnSuccess(null);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    public function passengerDeleteMy()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];

        $this->load->model('service/TripPassengerService');
        DbTansactionHanlder::begin('default');
        try {
            $trip = $this->TripPassengerService->getTripByTripId($userId, $tripId);
            //鉴权不过，无法删除
            if (empty($trip)) {
                throw new StatusException(Status::$message[Status::TRIP_NOT_EXIST], Status::TRIP_NOT_EXIST);
            }

            $this->TripPassengerService->deleteTrip($userId, $tripId);

            //获取用户所在群的id
            $this->load->model('service/GroupUserService');
            $groups = $this->GroupUserService->getGroupsByUserId($userId);
            $groupIds = array();
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    $groupIds[] = $group['group_id'];
                }
            }
            if (!empty($groupIds)) {
                $this->load->model('service/GroupTripService');
                $this->load->model('service/GroupService');
                $this->GroupTripService->deleteTripsFromGroup($tripId, $groupIds, Config::TRIP_TYPE_PASSENGER);
                $this->GroupService->decreaseTripInGroups($groupIds);
            }

            DbTansactionHanlder::commit('default');
            $this->_returnSuccess(null);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //获取我的行程列表
    //分页
    public function driverGetMyList()
    {
        $user = $this->_user;
        $userId = $user['user_id'];

        $input = $this->input->post();
        $page = $input['page'];


        $this->load->model('service/TripDriverService');

        $trips = $this->TripDriverService->getMyTripList($userId);

        $resTrips = array();
        if (!empty($trips)) {
            foreach ($trips as $trip) {
                $this->_formatOutputTrip($trip);
                $resTrips[] = $trip;
            }
        }

        $trips = $this->_sortTripsByCreatedTime($resTrips);
        if (empty($page)) {
            $page = 0;
        }

        $retTrips = array_slice($trips, $page * Config::TRIP_EACH_PAGE, Config::TRIP_EACH_PAGE);
        $hasNext = true;
        if (count($retTrips) < Config::TRIP_EACH_PAGE) {
            $hasNext = false;
        }
        $this->_returnSuccess(
            array(
                'page' => $page,
                'has_next' => $hasNext,
                'trips' => $retTrips,
            )
        );

    }

    //分页
    public function passengerGetMyList()
    {
        $user = $this->_user;
        $userId = $user['user_id'];

        $input = $this->input->post();
        $page = $input['page'];

        $this->load->model('service/TripPassengerService');

        $trips = $this->TripPassengerService->getMyTripList($userId);

        $resTrips = array();
        if (!empty($trips)) {
            foreach ($trips as $trip) {
                $this->_formatOutputTrip($trip);
                $resTrips[] = $trip;
            }
        }

        $trips = $this->_sortTripsByCreatedTime($resTrips);
        if (empty($page)) {
            $page = 0;
        }

        $retTrips = array_slice($trips, $page * Config::TRIP_EACH_PAGE, Config::TRIP_EACH_PAGE);
        $hasNext = true;
        if (count($retTrips) < Config::TRIP_EACH_PAGE) {
            $hasNext = false;
        }
        $this->_returnSuccess(
            array(
                'page' => $page,
                'has_next' => $hasNext,
                'trips' => $retTrips,
            )
        );

    }

    private function _sortTripsByCreatedTime($trips)
    {
        if (empty($trips) || !is_array($trips)) {
            return array();
        }

        $sortKeys = array();

        foreach ($trips as $trip) {

            unset($trip['group_info']);
            unset($trip['route']);
            unset($trip['driver_no_smoke']);
            unset($trip['driver_last_mile']);
            unset($trip['driver_goods']);
            unset($trip['driver_need_drive']);
            unset($trip['driver_chat']);
            unset($trip['driver_highway']);
            unset($trip['driver_pet']);
            unset($trip['driver_cooler']);
            unset($trip['tips']);
            unset($trip['share_img_url']);
            unset($trip['lbs_route_info']);
            unset($trip['passenger_no_smoke']);
            unset($trip['passenger_last_mile']);
            unset($trip['passenger_goods']);
            unset($trip['passenger_can_drive']);
            unset($trip['passenger_chat']);
            unset($trip['passenger_luggage']);
            unset($trip['passenger_pet']);
            unset($trip['passenger_no_carsickness']);
            $sortTrips[] = $trip;
            $sortKeys[] = $trip['created_time'];
        }

        array_multisort($sortKeys, SORT_DESC, SORT_REGULAR, $sortTrips);
        return $sortTrips;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //获取我的行程模板，包括人找车和车找人
    public function getTemplateList()
    {
        $user = $this->_user;
        $userId = $user['user_id'];

        $this->load->model('service/TripDriverService');
        $this->load->model('service/TripPassengerService');

        $driverTemplates = $this->TripDriverService->getMyTemplateList($userId);
        $passengerTemplates = $this->TripPassengerService->getMyTemplateList($userId);

        $this->_returnSuccess($this->_sortTripsByCreatedTime(array_merge($driverTemplates, $passengerTemplates)));;
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
    //删除模板，通过is_del物理删除
    public function deleteTemplate()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $tripId = $input['trip_id'];
        $tripType = $input['trip_type'];

        DbTansactionHanlder::begin('default');
        try {
            if ($tripType == Config::TRIP_TYPE_DRIVER) {
                $this->load->model('service/TripDriverService');
                $trip = $this->TripDriverService->getTripByTripId($userId, $tripId);
                if ($trip['status'] != Config::TRIP_STATUS_DRAFT) {
                    throw new StatusException(Status::$message[Status::TRIP_IS_NOT_TEMPLATE], Status::TRIP_IS_NOT_TEMPLATE);
                }
                $ret = $this->TripDriverService->deleteTrip($userId, $tripId);
            }
            if ($tripType == Config::TRIP_TYPE_PASSENGER) {
                $this->load->model('service/TripPassengerService');
                $trip = $this->TripPassengerService->getTripByTripId($userId, $tripId);
                if ($trip['status'] != Config::TRIP_STATUS_DRAFT) {
                    throw new StatusException(Status::$message[Status::TRIP_IS_NOT_TEMPLATE], Status::TRIP_IS_NOT_TEMPLATE);
                }
                $ret = $this->TripPassengerService->deleteTrip($userId, $tripId);
            }

            DbTansactionHanlder::commit('default');
            $this->_returnSuccess($ret);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }

    //--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------

    private function _formatOutputTrip(&$trip)
    {
        $currentDate = date('Y-m-d');

        if (isset($trip['begin_date']) && $currentDate > $trip['begin_date']) {
            $trip['is_expired'] = Config::TRIP_IS_EXPIRED;
        } else {
            $trip['is_expired'] = Config::TRIP_IS_NOT_EXPIRED;
        }

        if ($trip['begin_date'] == Config::EVERYDAY_DATE) {
            $trip['is_everyday'] = Config::TRIP_HAPPENS_EVERYDAY;
        } else {
            $trip['is_everyday'] = Config::TRIP_HAPPENS_ONCE;
        }

        $tmp = str_replace('(', '[', $trip['start_location_point']);
        $trip['start_location_point'] = str_replace(')', ']', $tmp);

        $tmp = str_replace('(', '[', $trip['end_location_point']);
        $trip['end_location_point'] = str_replace(')', ']', $tmp);
    }
}
