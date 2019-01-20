<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Group extends Base
{

    public function __construct()
    {
        parent::__construct();

    }


    public function getDetailByGroupId()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];

        $groupId = $input['group_id'];
        $this->load->model('service/GroupService');
        $group = $this->GroupService->getByGroupId($groupId);

        if ($group['owner_user_id'] == $userId) {
            $group['is_owner'] = 1;
        } else {
            $group['is_owner'] = 0;
        }

        $this->_returnSuccess($group);
    }

    public function updateNotice()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $groupId = $input['group_id'];
        $notice = $input['notice'];

        //先检查是否为群主
        $this->load->model('service/GroupService');
        $group = $this->GroupService->getByGroupId($groupId);
        if ($group['owner_user_id'] != $userId) {
            throw new StatusException(Status::$message[Status::GROUP_NO_AUTH_UPDATE_NOTICE], Status::GROUP_NO_AUTH_UPDATE_NOTICE);
        }

        $ret = $this->GroupService->updateNotice($groupId, $notice);

        $this->_returnSuccess($ret);
    }

    public function getListByUserId()
    {
        $user = $this->_user;
        $userId = $user['user_id'];

        $this->load->model('service/GroupUserService');
        $this->load->model('service/GroupService');

        $groups = $this->GroupUserService->getGroupsByUserId($userId);
        $groupDetails = array();
        if (!empty($groups) && is_array($groups)) {
            foreach ($groups as $group) {
                try {
                    $groupId = $group['group_id'];
                    $groupDetail = $this->GroupService->getByGroupId($groupId);
                    $groupDetails[] = $groupDetail;
                } catch (StatusException $e) {
                    continue;
                }
            }
        } else {
            $this->_returnSuccess(array());
        }

        $this->_returnSuccess($groupDetails);
    }


    public function topOneTrip()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $groupId = $input['group_id'];
        $tripId = $input['trip_id'];

        //先检查是否为群主
        $this->load->model('service/GroupService');
        $group = $this->GroupService->getByGroupId($groupId);
        if ($group['owner_user_id'] != $userId) {
            throw new StatusException(Status::$message[Status::GROUP_NO_AUTH_UPDATE_NOTICE], Status::GROUP_NO_AUTH_UPDATE_NOTICE);
        }

        $this->load->model('service/GroupTripService');
        $ret = $this->GroupTripService->topOneTrip($groupId, $tripId);

        $this->_returnSuccess($ret);
    }


    public function unTopOneTrip()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $groupId = $input['group_id'];
        $tripId = $input['trip_id'];

        //先检查是否为群主
        $this->load->model('service/GroupService');
        $group = $this->GroupService->getByGroupId($groupId);
        if ($group['owner_user_id'] != $userId) {
            throw new StatusException(Status::$message[Status::GROUP_NO_AUTH_UPDATE_NOTICE], Status::GROUP_NO_AUTH_UPDATE_NOTICE);
        }

        $this->load->model('service/GroupTripService');
        $ret = $this->GroupTripService->unTopOneTrip($groupId, $tripId);

        $this->_returnSuccess($ret);
    }

    public function exitGroup()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $userId = $user['user_id'];
        $groupId = $input['group_id'];
        $this->load->model('service/GroupUserService');
        DbTansactionHanlder::begin('default');
        try {
            //确保群内有该用户
            $this->GroupUserService->ensureUserBelongToGroup($userId, $groupId);

            //先检查是否为群主，群主无法退群
            $this->load->model('service/GroupService');
            $group = $this->GroupService->getByGroupId($groupId);

            if ($group['owner_user_id'] == $userId) {
                throw new StatusException(Status::$message[Status::GROUP_OWNER_CAN_NOT_EXIT], Status::GROUP_OWNER_CAN_NOT_EXIT);
            }

            $ret = $this->GroupUserService->delete($userId, $groupId);
            if ($ret) {
                //需要把group的member_num减少1
                $this->GroupService->decreaseMember($group['group_id']);
            }

            DbTansactionHanlder::commit('default');
            $this->_returnSuccess($ret);
        } catch (Exception $e) {
            DbTansactionHanlder::rollBack('default');
            throw $e;
        }
    }
}
