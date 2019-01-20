<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Config
{
    const BIZ_LOG_PATH = '/home/log/biz.log';

    const TRIP_EACH_PAGE = 20;

    //用户状态
    const USER_STATUS_OK = 0;
    const USER_STATUS_FROZEN = 1;

    //用户补全资料状态
    const USER_AUDIT_STATUS_OK = 1;
    const USER_AUDIT_STATUS_FAIL = 0;

    //用户是否需要展示补发引导图
    const USER_NEED_PUBLISH_GUIDE = 1;
    const USER_IGNORE_PUBLISH_GUIDE = 0;

    //用户查看安全协议
    const USER_HAS_READ = 0;
    const USER_HAS_NOT_READ = 1;

    //开始业务
    const USER_REG_IS_VALID = 1;
    const USER_REG_IS_INVALID = 0;

    //IDGEN的appkey
    const ID_GEN_KEY_USER = 'user_';
    const ID_GEN_KEY_TRIP = 'trip_';
    const ID_GEN_KEY_GROUP = 'group_';

    //数据库记录
    const RECORD_EXISTS = 0;
    const RECORD_DELETED = 1;

    //行程类型
    const TRIP_TYPE_DRIVER = 0;
    const TRIP_TYPE_PASSENGER = 1;

    //每天类型的行程的date常量
    const EVERYDAY_DATE = '2085-11-23';

    //行程状态
    const TRIP_STATUS_DRAFT = 0;
    const TRIP_STATUS_NORMAL = 1;
    const TRIP_STATUS_CANCEL = 2;

    //群内行程状态
    const GROUP_TRIP_STATUS_DEFAULT = 0;

    //群状态
    const GROUP_STATUS_DEFAULT = 0;

    //群成员状态
    const GROUP_USER_STATUS_DEFAULT = 0;

    //搜索范围标记
    const SEARCH_ALL = 0;
    const SEARCH_ONLY_IN_MY_GROUP = 1;

    //分享落地页的行程归属
    const IS_SHARE_OWNER = 1;
    const IS_NOT_SHARE_OWNER = 0;

    //行程是否过期
    const TRIP_IS_EXPIRED = 1;
    const TRIP_IS_NOT_EXPIRED = 0;

    //行程是否是每天的行程
    const TRIP_HAPPENS_EVERYDAY = 1;
    const TRIP_HAPPENS_ONCE = 0;

    const SHARE_LOC_NAME_LEN = 13;

    const POLY_LINE_MAX_POINT = 500;

}
