<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class Common extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

    }

    public function login()
    {
        $input = $this->input->post();
        $code = $input['code'];
        $isValid = $input['is_valid'];

        //check input
        if ($code == null) {
            throw new StatusException(Status::$message[Status::USER_LOGIN_CODE_INVALID], Status::USER_LOGIN_CODE_INVALID);
        }
        if ($isValid != Config::USER_REG_IS_VALID && $isValid != Config::USER_REG_IS_INVALID) {
            $isValid = Config::USER_REG_IS_INVALID;
        }

        //对于除了分享页以外的用户先准许注册，此处作为群外新用户入口的开关
        if ($isValid == Config::USER_REG_IS_INVALID) {
        }

        //从微信获取用户token
        $this->load->model('api/WxApi');
        $sessionKeyAndOpenIdArr = $this->WxApi->getSessionKeyAndOpenId($code);
        $sessionKey = $sessionKeyAndOpenIdArr['session_key'];
        $openId = $sessionKeyAndOpenIdArr['open_id'];
        $ticket = generateUuid();

        //创建或者更新用户
        $this->load->model('service/UserService');
        $user = $this->UserService->getUserByOpenId($openId);
        if (!empty($user) && is_array($user)) {
            if ($user['status'] == Config::USER_STATUS_FROZEN) {
                throw new StatusException(Status::$message[Status::USER_FROZEN], Status::USER_FROZEN);
            }

            //老用户更新ticket
            $this->UserService->updateSessionKeyAndTicketByUser($user, $sessionKey, $ticket);
        } else {
            //新用户
            $this->UserService->createNewUser($sessionKey, $openId, $ticket, $isValid);
        }

        $ret = array();
        $ret['ticket'] = $ticket;
        echo json_encode(array('errno' => 0, 'errmsg' => '', 'data' => $ret));
        exit;
    }
}
