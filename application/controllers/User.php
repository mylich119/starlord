<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class User extends Base
{

    public function __construct()
    {
        parent::__construct();

    }


    public function getProfile()
    {
        $user = $this->_user;
        $showUser = array();
        $showUser["phone"] = $user["phone"];
        $showUser["nick_name"] = $user["nick_name"];
        $showUser["gender"] = $user["gender"];
        $showUser["city"] = $user["city"];
        $showUser["province"] = $user["province"];
        $showUser["country"] = $user["country"];
        $showUser["avatar_url"] = $user["avatar_url"];
        $showUser["car_plate"] = $user["car_plate"];
        $showUser["car_brand"] = $user["car_brand"];
        $showUser["car_model"] = $user["car_model"];
        $showUser["car_color"] = $user["car_color"];
        $showUser["car_type"] = $user["car_type"];
        $showUser["audit_status"] = $user["audit_status"];
        $showUser["show_agreement"] = $user["show_agreement"];
        $showUser["need_publish_guide"] = $user["need_publish_guide"];

        $this->_returnSuccess($showUser);
    }

    public function completeUser()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $this->load->model('service/UserService');
        $this->load->model('api/WxApi');

        $rawData = $input['rawData'];
        $signature = $input['signature'];
        $encryptedData = $input['encryptedData'];
        $iv = $input['iv'];
        $sessionKey = $user['wx_session_key'];

        if ($signature != sha1($rawData . $sessionKey)) {
            throw new StatusException(Status::$message[Status::WX_DECRYPT_ERROR], Status::WX_DECRYPT_ERROR);
        }

        $userInfo = $this->WxApi->decryptUserInfo($sessionKey, $encryptedData, $iv);

        $user['wx_union_id'] = $userInfo['unionId'];
        $user['nick_name'] = $userInfo['nickName'];
        $user['gender'] = $userInfo['gender'];
        $user['city'] = $userInfo['city'];
        $user['province'] = $userInfo['province'];
        $user['country'] = $userInfo['country'];
        $user['avatar_url'] = $userInfo['avatarUrl'];
        $user['audit_status'] = Config::USER_AUDIT_STATUS_OK;

        $ret = $this->UserService->updateUser($user);
        $this->_returnSuccess($ret);
    }

    public function updateUserCar()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $this->load->model('service/UserService');

        $user['car_plate'] = $input['car_plate'];
        $user['car_brand'] = $input['car_brand'];
        $user['car_model'] = $input['car_model'];
        $user['car_color'] = $input['car_color'];
        $user['car_type'] = $input['car_type'];

        $ret = $this->UserService->updateUser($user);
        $this->_returnSuccess($ret);
    }

    public function updateUserAgreement()
    {
        $user = $this->_user;
        $this->load->model('service/UserService');

        $user['show_agreement'] = Config::USER_HAS_READ;

        $ret = $this->UserService->updateUser($user);
        $this->_returnSuccess($ret);
    }

    public function updateUserPhone()
    {
        $input = $this->input->post();
        $user = $this->_user;
        $this->load->model('service/UserService');

        $user['phone'] = $input['phone'];
        $this->_checkPhone($user['phone']);

        $ret = $this->UserService->updateUser($user);
        $this->_returnSuccess($ret);

    }

    public function updateUserPublishGuide()
    {
        $user = $this->_user;
        $this->load->model('service/UserService');

        $user['need_publish_guide'] = Config::USER_IGNORE_PUBLISH_GUIDE;

        $ret = $this->UserService->updateUser($user);
        $this->_returnSuccess($ret);
    }

    private function _checkPhone($phone)
    {
        $preg = '/^1\d{10}$/ims';

        if (!preg_match($preg, $phone)) {
            throw new StatusException(Status::$message[Status::USER_PHONE_INVALID], Status::USER_PHONE_INVALID);
        }
    }


    public function config()
    {
        $user = $this->_user;

        $cacheKey = 'User_config' . $user['user_id'];
        //缓存
        $this->load->model('redis/CacheRedis');
        $config = $this->CacheRedis->getK($cacheKey);

        if ($config != false) {
            $this->_returnSuccess($config);
        }

        $this->load->model('service/TripDriverService');
        $this->load->model('service/TripPassengerService');
        $this->load->model('service/GroupService');

        $totalTripNum = $this->TripDriverService->getAllTripsCount() + $this->TripPassengerService->getAllTripsCount();
        $totalGroupNum = $this->GroupService->getAllGroupsCount();


        $config = array(
            'expire' => 3600,
            'cert' => '!@#QWE!@#Dvvdfsvf',
            'switch' => array(
                //'9999' => $user['is_valid'] == Config::USER_REG_IS_VALID ? 0 : 1,                    //正常进入页面功能or展示维护公告
                '9999' => 0,                    //正常进入页面功能or展示维护公告
                '9999_context' => "维护中，预计12：00开放使用，非常抱歉。",                    //正常进入页面功能or展示维护公告
                'search_all_group' => Config::SEARCH_ALL,            //搜索页展示是否跨群选项or写死文案只能群内搜索
                'show_agreement' => 1,        //是否展示安全协议，当读完安全协议后，需要在服务端user表内和本地都置为否
                'trip_publish_to_all_group' => 0,        //如果非空，值为发布选择群上面的提示文案，如果为空发布时候不弹出选择群
            ),
            'docoment' => array(
                'share_description' => '通过管家发布行程到本群，拼车更高效',            //分享小卡片上的描述语句
                'notice_list' => array(
                    '以下列表中的群和你的拼车群一一对应',
                    '转发行程到拼车群后，不要忘记在【群内点击】你的分享，将行程加入拼车群管家',
                ),                //拼车群tab上的公告
                'group_page_info' => '在拼车群分享行程并点击你的分享，该群所有行程将汇总管理到以上列表',
                'platform_info' => '目前共有' . $totalGroupNum . '个拼车群使用【拼车群管家】管理，共有' . $totalTripNum . '个有效行程',    //搜索页上方的平台信息，说明现在平台有x个微信群，y个行程
                'search_tip' => '可以搜索到所有通过【拼车群管家】发布到拼车群的行程',        //搜索页搜索按钮下面的提示信息
                'group_owner_info' => '在拼车群内点击任一拼车群管家的分享行程，即可在我的拼车群列表中添加该群',        //群主信息
                'publish_tip' => '发布行程到你的拼车群，同群好友快速查询，其他群的拼友也可以搜到你的行程',        //发布首页下面的说明文字
                'first_publish_alert' => '发布行程到拼车群后，不要忘记点击你的分享，将行程加入拼车群管家',        //发布首页下面的说明文字
                'publish_finish_tip' => '已发布的行程，在【我的】->【我的行程】可以查看、删除、分享',        //发布完成返回按钮下的文案
                'share_tip' => '发布行程到拼车群后，不要忘记点击你的分享，将行程加入拼车群管家',    //发布到拼车群按钮下的文案
                'car_tip' => '发布行程时自动显示的车辆信息',        //我的车辆编辑页面保存按钮下的文案
                'user_tip' => '发布行程时自动显示的手机号',    //我的资料编辑页面保存按钮下的文案
                'share_page_info' => '行程已经加入拼车群管家',
                'adopt' => '如果您是本群群主，请加客服（wx号：pinchequnguanjia）,成为该群管理员；<br />ps：群主同意的情况下，其他成员也可以担任管理员。<br />管理员福利：发布公告，置顶群内行程，为您的微信群拉新扩充人脉~更多功能开发中',        //没有群主的群的认领文案
                'contact' => '如果您需要成为群管理员，或者有软件问题反馈<br />
请联系客服：微信号  pinchequnguanjia<br />',        //联系客服页的内容
                'faq' => '1 【拼车群管家】是什么？<br />
首创的微信拼车群管理，只需要转发行程到微信群内，即可汇总、管理并搜索拼车群和行程。<br />
<br />
2，如何将群加入群管理列表？<br />
从微信群中点击任意【拼车群管家】的行程分享即可。<br />
<br />
3，如何将行程汇总到【拼车群管家】？<br />
发布行程到拼车群内，并点击一下，就可以将行程加入【拼车群管家】的群管理，并被收录进入管家的顺路搜索引擎。<br />
<br />
4，我是群主，有什么特权吗？<br />
请加客服微信：pinchequnguanjia  您不仅是微信群主，更是拼车群管家的群主，您可置顶群内行程，发布公告，为群友提供更便捷舒心的拼车体验，活跃您的微信群，扩大您的人脉。（更多福利开发中）<br />
<br />
5，我不在任何微信拼车群内，怎么办？<br />
您可在管家“搜索”页面，搜索需要的行程，查看该群群主联系方式来加群。（小技巧：也可以自己组建一个微信群，将行程转发到该群内并点击，这样也能被收录,其他拼友都能搜到）<br />',
                'agreement' => '拼车群管家群信息管理工具服务协议<br />
为了更好的提升拼车群群友对快速检索群内外拼车信息的需求，拼车群管家应运而生，以便于作为拼车群的管理工具，将群内外的拼车信息和用户连接起来。“车主”和“合乘人”须且仅能在线下达成合乘的一致意见，并完成合乘交易，拼车群管家对双方的交易不承担任何担保责任。为此，拼车群管家作出如下服务约定：<br />
1、服务内容及性质<br />
拼车群管家仅提供信息发布平台服务，并不介入合乘交易且不提供任何形式的担保。合乘人和车主应当对合乘信息和合乘交易秉持合理的审慎义务，对于因合乘交易发生的任何纠纷，拼车群管家均不负有解决纠纷、先行赔付等责任。<br />
2、风险提示<br />
合乘信息由合乘人自主、免费发布，拼车群管家对发布合乘信息的车主身份和发布的合乘信息的真实性，以及合乘人的身份不做验证和担保，对合乘交易的签约和履行不做担保，即：<br />
2.1 合乘人同意不就去车主发布的合乘内容或所作所为追究拼车群管家的责任。<br />
2.2 合乘人同意对自身行为之合法性单独承担责任，拼车群管家对合乘人的行为的合法性不承担责任。<br />
2.3 车主同意对自身行为之合法性单独承担责任，拼车群管家对车主的行为的合法性不承担责任。<br />
2.4 拼车群管家对于车主、合乘人由于使用该平台而造成的任何金钱、商誉、名誉的损失，或任何特殊的、间接的、或结果性的损失都不负任何责任。<br />
3、定义<br />
车主指注册拼车群管家用户账号并在“拼车群管家”发布合乘信息的个人。<br />
合乘信息指车主按照“拼车群管家”要求，填写并发布的有关合乘路线、发车时间、车型、联系方式、费用等信息。<br />
合乘人指注册拼车群管家用户账号并根据合乘信息自由选择车主以合乘方式完成出行的个人、企业或其他组织。<br />
注册拼车群管家用户账号指拼车群管家仅要求注册人提供可接受服务通知的手机号，并不对注册人的真实、合法身份进行任何形式的验证。<br />
4、协议主体<br />
拼车群管家由南京豆年网络科技发展有限公司提供，且该公司为本协议的签约主体，受此协议的约束。<br />
5、管辖<br />
因本协议发生的纠纷均受中华人民共和国法律管辖，且由合乘信息发布所在地人民法院南京市江北新区法院管辖。<br />
<br />
拼车群管家仅提供“顺风车”信息发布平台服务，不对车主和合乘人身份和合乘信息进行验证和担保，并不承担任何责任，请自行辨明，谨慎使用。<br />
合乘注意事项<br />
车主篇：<br />
1.确认合乘人身份信息。详细记录合乘人姓名、年龄、身份证、个人及家庭联系方式等。<br />
2.与合乘人约好出发时间，出发前1天最好给合乘人通一次电话提醒对方。<br />
3.要明确合乘所需的费用。尤其是一旦发生交通违章造成罚款的分担，以及车辆发生问题所带来的维修费用的承担。<br />
4.明确合乘费用以及额外费用的支付方式以及时间。<br />
5.合理规划时间，总路程不宜超过十小时，不走夜路。 不要疲劳驾驶，同时保管好个人财物。 <br />
6.车主对于没有驾驶过所乘车型的同车人，尽量不要让其参与驾驶。<br />
7.女性车主应有熟悉的男性成年亲友相伴。车主应将了解到的对方信息发送给至少一名亲友，以备出现问题后联系使用，并有意让对方知道这个情况。<br />
8.事先确定好关于吸烟等细节的规则，特别是有女性和小孩在场的时候。<br />
9.签协议和买保险是非常有必要的。<br />
合乘人篇：<br />
1.与陌生车主合乘时，要注意预防骗子或劫车者。合乘人应在发车前要求车主提供身份证、个人方式、车牌号、驾驶证、车辆等信息，并在上车前进行验证。<br />
2.合乘人应将了解到的车主信息发送给至少一名亲友，以备出现问题后联系使用，并有意让对方知道这个情况。<br />
3.合乘人应事先了解驾驶员的技术水平、所用车型。关键是有无跑长途的经历。<br />
4.明确合乘费用以及额外费用的支付方式以及时间。<br />
5.总路程不宜超过10小时，不走夜路。避免心急赶路和疲劳驾驶，合理安排休息。<br />
6.不要在途中向同车不熟悉的人炫耀自己的或者所携带财物 情况。<br />
7.女性合乘人应有熟悉的男性成年亲友相伴。<br />
8.事先确定好关于吸烟等细节的规则，特别是有女性和小孩在场的时候 。<br />
9.签协议和买保险是非常有必要的。<br />',
            ),
        );

        //设置缓存
        $this->CacheRedis->setK($cacheKey, $config);

        $this->_returnSuccess($config);
    }
}

