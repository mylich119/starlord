<?php
class Status
{
    const SUCCESS = 0;

    //oss
    const OSS_CONNECT_FAIL = 801;

    //wx
    const WX_FETCH_SESSION_FAIL = 901;
    const WX_DECRYPT_ERROR = 902;
    const WX_FETCH_LBS_ROUTE_FAIL = 903;
    const WX_FETCH_LBS_NO_ROUTE_FOUND = 904;

    //user
    const USER_LOGIN_CODE_INVALID = 1001;
    const USER_LOGIN_VALID_FLAG_INVALID = 1002;
    const USER_LOGIN_OPEN_ID_INVALID = 1003;
    const USER_HAS_NO_TICKET = 1004;
    const USER_TICKET_NOT_EXIST = 1005;
    const USER_FROZEN = 1006;
    const USER_NOT_EXIST = 1007;
    const USER_PHONE_INVALID = 1008;

    //group
    const GROUP_NOT_EXIST = 1101;
    const GROUP_EXCLUDE_USER = 1102;
    const GROUP_USER_INVALID = 1103;
    const GROUP_NO_AUTH_UPDATE_NOTICE = 1104;
    const GROUP_HAS_NO_TRIP = 1105;
    const GROUP_OWNER_CAN_NOT_EXIT = 1106;

    //trip
    const TRIP_NOT_EXIST = 1201;
    const TRIP_HAS_NO_AUTH_TO_PUBLISH = 1202;
    const TRIP_IS_NOT_TEMPLATE = 1203;
    const TRIP_IS_NOT_NORMAL = 1204;
    const TRIP_PARAMS_INVALID = 1205;


    //validation
    const VALIDATION_IS_NULL = 10001;
    const VALIDATION_IS_NOT_NULL = 10002;
    const VALIDATION_EQUAL = 10003;
    const VALIDATION_NOT_EQUAL = 10004;
    const VALIDATION_NOT_TRUE = 10005;
    const VALIDATION_NOT_FALSE = 10006;
    const VALIDATION_GREATER_OR_EQUAL = 10007;
    const VALIDATION_ARRAY = 10008;

    //dao
    const DAO_INSERT_FAIL = 11001;
    const DAO_FETCH_FAIL = 11002;
    const DAO_MISS_FIELD = 11003;
    const DAO_MORE_THAN_ONE_RECORD = 11004;
    const DAO_UPDATE_WITHOUT_CONDITION = 11005;
    const DAO_UPDATE_FAIL = 11006;
    const DAO_HAS_NO_SHARD_KEY = 11007;
    const DAO_INSERT_NO_FILED = 11008;
    const DAO_DELETE_FAIL = 11009;

    //rpc
    const RPC_CALL_FAIL = 12001;
    const REQUEST_SIGN_ERROR = 12002;


    //redis
    const REDIS_HAS_NO_METHOD = 13001;
    const REDIS_EXECUTE_ERROR = 13002;
    const REDIS_LOCK_WAIT_TIMEOUT = 13003;
    const REDIS_CONNECT_ERROR = 13004;

    //parameter error
    const PARAM_ERROR = 14001;

    static public $message = array(
        self::SUCCESS => '',

        //oss
        self::OSS_CONNECT_FAIL => 'oss失败',

        //wx
        self::WX_FETCH_SESSION_FAIL => '获取微信session失败',
        self::WX_DECRYPT_ERROR => '微信解密失败',
        self::WX_FETCH_LBS_ROUTE_FAIL => '获取路径规划失败',
        self::WX_FETCH_LBS_NO_ROUTE_FOUND => '为发现路径',

        //user
        self::USER_LOGIN_CODE_INVALID => 'login code不能为空',
        self::USER_LOGIN_VALID_FLAG_INVALID => 'is_valid只能为0或1',
        self::USER_LOGIN_OPEN_ID_INVALID => 'open id不能为空',
        self::USER_HAS_NO_TICKET => 'ticket错误',
        self::USER_TICKET_NOT_EXIST => 'ticket对应用户不存在，请先登录',
        self::USER_FROZEN => '用户冻结不允许登录',
        self::USER_NOT_EXIST => '用户不存在',
        self::USER_PHONE_INVALID => '手机号格式错误',

        //group
        self::GROUP_NOT_EXIST => '群不存在',
        self::GROUP_EXCLUDE_USER => '用户不在群内',
        self::GROUP_USER_INVALID => '群用户非法',
        self::GROUP_NO_AUTH_UPDATE_NOTICE => '只有群主能修改公告',
        self::GROUP_HAS_NO_TRIP => '群内无此行程',
        self::GROUP_OWNER_CAN_NOT_EXIT => '群主无法退群',

        //trip
        self::TRIP_NOT_EXIST => '行程不存在',
        self::TRIP_HAS_NO_AUTH_TO_PUBLISH => '用户无权限发布行程',
        self::TRIP_IS_NOT_TEMPLATE => '行程不是模板无法编辑',
        self::TRIP_IS_NOT_NORMAL => '行程不是常规行程',
        self::TRIP_PARAMS_INVALID => '行程参数不合法',

        //validation
        self::VALIDATION_IS_NULL => '值为空',
        self::VALIDATION_IS_NOT_NULL => '值不为空',
        self::VALIDATION_EQUAL => '两个值相等',
        self::VALIDATION_NOT_EQUAL => '两个值不相等',
        self::VALIDATION_NOT_TRUE => '值不为TRUE',
        self::VALIDATION_NOT_FALSE => '值不为FALSE',
        self::VALIDATION_GREATER_OR_EQUAL => "值应该大于等于目标值",
        self::VALIDATION_ARRAY => '值应该是数组',

        //dao
        self::DAO_INSERT_FAIL => '数据库插入失败',
        self::DAO_FETCH_FAIL => '数据库读取失败',
        self::DAO_MISS_FIELD => '缺少数据库字段',
        self::DAO_MORE_THAN_ONE_RECORD => '返回多于一条记录',
        self::DAO_UPDATE_WITHOUT_CONDITION => '禁止不带条件update数据库',
        self::DAO_UPDATE_FAIL => '数据库更新失败',
        self::DAO_HAS_NO_SHARD_KEY => '没有分表key',
        self::DAO_INSERT_NO_FILED => '没有插入任何数据',
        self::DAO_DELETE_FAIL => '数据库删除失败',


        //rpc
        self::RPC_CALL_FAIL => 'curl异常失败',
        self::REQUEST_SIGN_ERROR => '请求签名验证失败',

        //redis
        self::REDIS_HAS_NO_METHOD => 'redis方法不存在',
        self::REDIS_EXECUTE_ERROR => 'redis执行错误',
        self::REDIS_LOCK_WAIT_TIMEOUT => '等待锁超时',
        self::REDIS_CONNECT_ERROR => 'redis连接错误',

        //parameter error
        self::PARAM_ERROR => "请求参数错误",
    );
}
