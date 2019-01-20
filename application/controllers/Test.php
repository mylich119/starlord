<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Test extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

    }

/*
    public function add()
    {
        $this->load->model('service/TestService');
        $input = $this->input->post();
        for ($i = 1; $i < 100000; $i++) {
            $route = "测试路径" . $i;
            echo $route . "\n";
            $start_loc = "(" . (string)(39.0 + mt_rand() / mt_getrandmax() * 2) . "," . (string)(115.0 + mt_rand() / mt_getrandmax() * 2) . ")";
            $end_loc = "(" . (string)(39.0 + mt_rand() / mt_getrandmax() * 2) . "," . (string)(115.0 + mt_rand() / mt_getrandmax() * 2) . ")";
            $this->TestService->add($route, $start_loc, $end_loc);
        }


        $this->_returnSuccess(null);
    }

    public function search()
    {
        $target_start = "(39.533898169423,116.99423118029)";
        $target_end = "(39.658079674774,115.95184874113)";
        $this->load->model('service/TestService');

        $this->_returnSuccess($this->TestService->search($target_start, $target_end, 100));

    }

    public function setkey()
    {
        $this->load->model('service/TestService');
        $input = $this->input->post();

        $this->_returnSuccess($this->TestService->set($input['a']));

    }

    public function getkey()
    {
        $this->load->model('service/TestService');

        $this->_returnSuccess($this->TestService->get());

    }

    public function delkey()
    {
        $this->load->model('service/TestService');

        $this->_returnSuccess($this->TestService->del());

    }

    public function lock()
    {
        $this->load->model('service/TestService');

        $this->_returnSuccess($this->TestService->lock());

    }

    public function unlock()
    {
        $this->load->model('service/TestService');

        $this->_returnSuccess($this->TestService->unlock());

    }


    public function img()
    {
        $this->load->model('api/OssApi');

        $source = '/home/chuanhui/starlord/application/imgs/bg_driver.png';//车找人底图
        // $source = '/home/chuanhui/starlord/application/imgs/bg_passenger.png';//人找车底图
        $firstNew = "/home/chuanhui/starlord/res/testpng1.png";
        $secondNew = "/home/chuanhui/starlord/res/testpng2.png";
        $thirdNew = "/home/chuanhui/starlord/res/testpng3.png";
        $forthNew = "/home/chuanhui/starlord/res/testpng4.png";

        $firstLine = array(
            'wm_text' => '2019-12-23 20:12',//这是开始时间
            'wm_type' => 'text',
            'wm_font_path' => '/home/chuanhui/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '26',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '103',
            'wm_hor_offset' => '69',
        );

        $secondLine = array(
            'wm_text' => '这是开始的位置名称',//显示开始的位置名称，需要用php截断字符长度
            'wm_type' => 'text',
            'wm_x_transp' => 0,
            'wm_font_path' => '/home/chuanhui/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '22',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '160',
            'wm_hor_offset' => '69',
        );

        $thirdLine = array(
            'wm_text' => '这是结束的位置名称',//显示结束的位置名称，需要用php截断字符长度
            'wm_type' => 'text',
            'wm_x_transp' => 0,
            'wm_font_path' => '/home/chuanhui/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '22',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '216',
            'wm_hor_offset' => '69',
        );

        $forthLine = array(
            'wm_text' => '价格100, 座位5',//显示结束的位置名称，需要用php截断字符长度
            'wm_type' => 'text',
            'wm_x_transp' => 0,
            'wm_font_path' => '/home/chuanhui/starlord/application/ttf/simhei.ttf',
            'wm_font_size' => '22',
            'wm_font_color' => '333333',
            'wm_vrt_alignment' => 'top',
            'wm_hor_alignment' => 'left',
            'wm_vrt_offset' => '269',
            'wm_hor_offset' => '69',
        );

        $this->imgHandler($source, $firstNew, $firstLine, true);
        $this->imgHandler($firstNew, $secondNew, $secondLine, true);
        $this->imgHandler($secondNew, $thirdNew, $thirdLine, true);
        $this->imgHandler($thirdNew, $forthNew, $forthLine, true);

        $this->OssApi->uploadImg('test/111.png', $forthNew);

        unlink($firstNew);
        unlink($secondNew);
        unlink($thirdNew);
        unlink($forthNew);

        $this->_returnSuccess($this->OssApi->getSignedUrlForGettingObject('test/111.png'));
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

        if ($user['status'] == Config::USER_STATUS_FROZEN || $user['status'] == Config::USER_AUDIT_STATUS_FAIL) {
            throw new StatusException(Status::$message[Status::TRIP_HAS_NO_AUTH_TO_PUBLISH], Status::TRIP_HAS_NO_AUTH_TO_PUBLISH);
        }
        DbTansactionHanlder::begin('default');
        try {
            //发布到trip表
            $newTrip = $this->TripDriverService->createNewTrip($userId, $tripDriverDetail->getTripArray(), $this->_user);
            DbTansactionHanlder::commit('default');

            $newTrip['trip_id'] = $newTrip['trip_id'] . "";

            $this->_formatTripWithExpireAndIsEveryday($newTrip);

            $this->_returnSuccess($newTrip);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }


    protected function _returnSuccess($data = array())
    {
        echo json_encode(array('errno' => 0, 'errmsg' => '', 'data' => $data));
        exit;
    }


    //发布行程
    public function tripsGen()
    {
        $input = $this->input->post();

        $input['begin_date'] = '2085-11-23';
        $input['begin_time'] = '14:45:00';

        for ($i = 1; $i <= 100000; $i++) {
            $f1 = 39.034068857643;
            $f2 = 39.2318581994503;
            $a = (rand(0, 10000)) / 10000;
            $slon = $f1 + ($f2 - $f1) * $a;
            $f1 = 117.055377802462;
            $f2 = 117.319610825447;
            $a = (rand(0, 10000)) / 10000;
            $slat = $f1 + ($f2 - $f1) * $a;
            $input['start_location_name'] = '起始点地址名'.$i;
            $input['start_location_address'] = '起始点地址详情'.$i;
            $input['start_location_point'] = '(' . $slon . ',' . $slat . ')';

            $f1 = 39.7466386284469;
            $f2 = 40.0784863733239;
            $a = (rand(0, 10000)) / 10000;
            $elon = $f1 + ($f2 - $f1) * $a;
            $f1 = 116.152100405201;
            $f2 = 116.622714095226;
            $a = (rand(0, 10000)) / 10000;
            $elat = $f1 + ($f2 - $f1) * $a;
            $input['end_location_name'] = '终点地址名'.$i;
            $input['end_location_address'] = '终点地址详情'.$i;
            $input['end_location_point'] = '(' . $elon . ',' . $elat . ')';

            $input['route'] = '路过，路过';
            $input['price_everyone'] = 100;
            $input['price_total'] = 350;
            $input['seat_num'] = 4;
            $input['driver_no_smoke'] = null;
            $input['driver_last_mile'] = null;
            $input['driver_goods'] = null;
            $input['driver_need_drive'] = null;
            $input['driver_chat'] = null;
            $input['driver_highway'] = null;
            $input['driver_pet'] = null;
            $input['driver_cooler'] = null;
            $input['tips'] = '备注';
            $tripDriverDetail = new TripDriverDetail($input);

            $this->load->model('service/TripDriverService');
            $this->load->model('service/GroupTripService');
            $this->load->model('service/GroupService');
            $this->load->model('service/UserService');

            $userId = '15473006340000001';
            $groupId = '15473009950000001';
            $user = $this->UserService->getUserByUserId($userId);

            DbTansactionHanlder::begin('default');
            try {
                //发布到trip表
                $newTrip = $this->TripDriverService->createNewTrip($userId, $tripDriverDetail->getTripArray(), $user);
                $tripId = $newTrip['trip_id'];
                $groups = $this->GroupService->getByGroupIds(array($groupId));
                $group = $groups[0];
                try {
                    //检查行程是否在群里
                    $this->GroupTripService->ensureGroupHasTrip($groupId, $tripId);
                } catch (Exception $e) {
                    //否则把行程加群
                    $newTrip['trip_id'] = $tripId . "";
                    $this->GroupTripService->publishTripToGroup($tripId, $groupId, $newTrip, Config::TRIP_TYPE_DRIVER);
                    $this->GroupService->increaseTripInGroup(array($groupId));
                    $this->TripDriverService->addGroupInfoToTrip($userId, $tripId, $newTrip, $group);
                }
            } catch (Exception $e) {
                DbTansactionHanlder::rollBack('default');
                throw $e;
            }
            DbTansactionHanlder::commit('default');
        }

        $this->_returnSuccess(true);
    }

    public function idgentest(){
        $this->load->model('redis/IdGenRedis');
        $this->_returnSuccess($this->IdGenRedis->gen(Config::ID_GEN_KEY_TRIP));

    }

*/
}
