<?php
function returnFail(Exception $e)
{
    header("Content-Type: text/json");

    if ($e instanceof StatusException) {
        //request_error_log
        $logDate = date("Y-m-d H:i:s", time());
        $logContent = $logDate . ' | ';
        $logContent .= 'request_error' . ' | ';
        $logContent .= Base::$traceId . ' | ';
        $logContent .= 'errno=' . $e->getCode() . ' | ';
        $logContent .= 'errmsg=' . $e->getMessage() . ' | ';
        $logContent .= 'cause=' . $e->getCause();
        error_log($logContent . "\n", 3, Config::BIZ_LOG_PATH);

        echo json_encode(array('errno' => $e->getCode(), 'errmsg' => $e->getMessage(), 'data' => (object)null));
    } else {
        //request_error_log
        $logDate = date("Y-m-d H:i:s", time());
        $logContent = $logDate . ' | ';
        $logContent .= 'request_error' . ' | ';
        $logContent .= Base::$traceId . ' | ';
        $logContent .= 'errno=' . $e->getCode() . ' | ';
        $logContent .= 'errmsg=' . $e->getMessage();
        error_log($logContent . "\n", 3, Config::BIZ_LOG_PATH);

        echo json_encode(array('errno' => $e->getCode(), 'errmsg' => $e->getMessage(), 'data' => (object)null));
    }

    exit;
}
