<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Cron extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();

    }


    public function updateAllGroupsWithMemberAndTripNum()
    {
        $this->load->model('service/GroupService');
        $this->load->model('service/GroupUserService');
        $this->load->model('service/GroupTripService');

        $ret = $this->GroupService->getAllGroupIds();
        foreach ($ret as $v) {
            $groupId = $v['group_id'];
            $tripNum = $this->GroupTripService->getCountByGroupId($groupId);
            $memberNum = $this->GroupUserService->getCountByGroupId($groupId);
            $this->GroupService->updateUserAndTripCount($groupId, $memberNum, $tripNum);
            //log
        }
    }

}