<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Base extends CI_Controller
{
    protected $_user = null;
    public static $traceId;

    public function __construct()
    {
        parent::__construct();
        self::$traceId = generateUuid();

        $this->load->model('service/UserService');
        $input = $this->input->post();
        if (empty($input['ticket'])) {
            throw new StatusException(Status::$message[Status::USER_HAS_NO_TICKET], Status::USER_HAS_NO_TICKET);
        }
        $user = $this->UserService->getUserByTicket($input['ticket']);

        //request_in_log
        $logDate = date("Y-m-d H:i:s", time());
        $logContent = $logDate . ' | ';
        $logContent .= 'request_in' . ' | ';
        $logContent .= 'uri=' . $this->uri->uri_string . ' | ';
        $logContent .= self::$traceId . ' | ';
        $logContent .= $user['user_id'] . ' | ';
        $logContent .= json_encode($input);

        error_log($logContent . "\n", 3, Config::BIZ_LOG_PATH);

        if (!empty($user) && is_array($user)) {
            if ($user['status'] == Config::USER_STATUS_FROZEN) {
                throw new StatusException(Status::$message[Status::USER_FROZEN], Status::USER_FROZEN);
            }
            $this->_user = $user;
        } else {
            throw new StatusException(Status::$message[Status::USER_HAS_NO_TICKET], Status::USER_HAS_NO_TICKET);
        }
    }

    protected function _returnSuccess($data = array())
    {
        //request_error_log
        $logDate = date("Y-m-d H:i:s", time());
        $logContent = $logDate . ' | ';
        $logContent .= 'request_out' . ' | ';
        $logContent .= 'uri=' . $this->uri->uri_string . ' | ';
        $logContent .= Base::$traceId . ' | ' ;
        $logContent .= 'errno=0';
        error_log($logContent . "\n", 3, Config::BIZ_LOG_PATH);

        echo json_encode(array('errno' => 0, 'errmsg' => '', 'data' => $data));
        exit;
    }
}
