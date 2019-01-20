<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Search extends Base
{

    public function __construct()
    {
        parent::__construct();

    }

    //分页
    public function all()
    {
        $input = $this->input->post();
        $user = $this->_user;

        $userId = $user['user_id'];
        $tripType = $input['trip_type'];
        $beginTime = $input['begin_time'];
        $beginDate = $input['begin_date'];
        $targetStart = $input['target_start'];
        $targetEnd = $input['target_end'];
        $onlyInMyGroup = $input['only_in_my_group'];

        $page = $input['page'];

        $this->load->model('service/SearchService');
        $this->load->model('service/GroupUserService');
        $this->load->model('service/SearchService');

        $trips = $this->SearchService->search($tripType, $beginDate, $beginTime, $targetStart, $targetEnd);

        //过滤只属于自己群的行程
        if ($onlyInMyGroup == Config::SEARCH_ONLY_IN_MY_GROUP && !empty($trips) && !empty($groups)) {
            $groups = $this->GroupUserService->getGroupsByUserId($userId);
            $groupIdMap = array();
            foreach ($groups as $group) {
                $groupIdMap[$group['group_id']] = 1;
            }

            $filteredTrips = array();
            foreach ($trips as $trip) {
                $groupInfos = json_decode($trip['group_info'], true);
                if (empty($groupInfos)) {
                    continue;
                }
                foreach ($groupInfos as $groupInfo) {
                    if (isset($groupIdMap[$groupInfo['group_id']])) {
                        $filteredTrips[] = $trip;
                    }
                }
            }

            $trips = $filteredTrips;
        }

        $tripsFormatted = array();
        foreach ($trips as $t) {
            $this->_formatTripWithExpireAndIsEveryday($t);
            //针对搜索所有的情况下，如果行程相关的群信息没有群主，则将整个groupinfo注释掉
            $needCleanGroupInfo = true;
            if ($onlyInMyGroup == Config::SEARCH_ALL) {
                if (isset($t['group_info']) && !empty($t['group_info'])) {
                    $group_infos = json_decode($t['group_info'], true);
                    if (is_array($group_infos) && !empty($group_infos)) {
                        foreach ($group_infos as $group_info) {
                            if (!empty($group_info['owner_user_id'])) {
                                $needCleanGroupInfo = false;
                            }
                        }
                    }
                }
            }
            if ($needCleanGroupInfo) {
                $t['group_info'] = null;
            }
            $tripsFormatted[] = $t;
        }

        if (empty($page)) {
            $page = 0;
        }

        $retTrips = array_slice($tripsFormatted, $page * Config::TRIP_EACH_PAGE, Config::TRIP_EACH_PAGE);
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


    private function _formatTripWithExpireAndIsEveryday(&$trip)
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
        unset($trip['people_num']);
        unset($trip['passenger_no_smoke']);
        unset($trip['passenger_last_mile']);
        unset($trip['passenger_goods']);
        unset($trip['passenger_can_drive']);
        unset($trip['passenger_chat']);
        unset($trip['passenger_luggage']);
        unset($trip['passenger_pet']);
        unset($trip['passenger_no_carsickness']);

    }
}
