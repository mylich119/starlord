/**
 * 所有请求
 */
const config = require('./config');
const moment = require('./moment.min.js');
moment.locale('zh-cn', {
  longDateFormat: {
    LT: 'HH:mm'
  }
});
let app;

/** 重启当前页面 */
const reLaunchCurrentPage = () => {
  const pages = getCurrentPages();
  if (pages.length > 0) {
    const currPage = pages[pages.length - 1];
    const urlParams = Object.keys(currPage.options).map(key => {
      return [key, currPage.options[key]].join('=');
    }).join('&');
    wx.reLaunch({
      url: `/${currPage.route}?${urlParams}`,
    })
  }
}

/** 路线解压 */
const parsePolyline = (data) => {
  if (!data) return null;
  data = JSON.parse(data);
  let polyline = [];
  const coors = (data && data.polyline) ? data.polyline : [];
  //坐标解压（返回的点串坐标，通过前向差分进行压缩)
  const kr = 1000000, coorLength = coors.length;
  for (let i = 2; i < coorLength; i++) {
    coors[i] = Number(coors[i - 2]) + Number(coors[i]) / kr;
  }
  //将解压后的坐标放入点串数组polyline中
  for (var i = 0; i < coors.length; i += 2) {
    polyline.push({ latitude: coors[i], longitude: coors[i + 1] });
  }
  return polyline;
}

/**
 * 通用request
 * uri: string | required | 不包含host如登录：common/login
 * data: json | required | request的数据
 * callback: function | required | 请求的返回函数，不管请求是否正确都调用callback
 * myOptions: json | optional | 自定义request 的option，跟默认的options做merge
 */
const request = (uri, data, callback, myOptions = {}) => {
  console.debug(`http request`, uri, data);
  const ticket = wx.getStorageSync(config.storage_ticket);
  const defaultCallBack = () => {
    console.warn(`${uri}:无callback请求`);
  }

  if (!app.globalData.app_init && ['common/login', 'user/config'].indexOf(uri) == -1) return;

  // callback 返回2个参数，第一个参数为是否返回success，第二个参数为返回数据
  callback = callback || defaultCallBack;
  const defaultOptions = {
    url: `${config.host}/${uri}`,
    method: 'POST',
    data: {
      ...data,
      ticket: ticket
    },
    header: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    success (res) {
      const response = res.data;
      console.debug(`http resonse`, uri, response);
      if (response.errno != 0) {
        // 登录失效，重新登录
        if (response.errno == 1004) {
          app.globalData.app_init = false;
          login(() => {
            request(uri, data, callback, myOptions);
          });
        } else {
          wx.showToast({
            title: response.errmsg || '请求错误，请重试',
            icon: 'none'
          });
        }
        callback(false, response.data);
      } else {
        callback(true, response.data);
      }
    },
    fail () {
      wx.showToast({
        title: '请求错误，请重试',
        icon: 'none',
        duration: 2500
      });
      callback(false);
    }
  };
  wx.request({
    ...defaultOptions,
    ...myOptions
  })
}
/** 登录 */
const login = (loginCb) => {
  wx.login({
    success(res) {
      const callback = (success, data) => {
        if (!success) return;
        app.globalData.app_init = true;
        wx.setStorageSync(config.storage_ticket, data.ticket);
        if (loginCb) loginCb(data.ticket);
      }
      if (res.code) {
        let params = {
          code: res.code
        };
        if (app.globalData && app.globalData.wx_config && app.globalData.wx_config.shareTicket) {
          params.is_valid = 1;
        }
        request('common/login', params, callback);
      } else {
        wx.showToast({
          title: '登录失败，请重试',
          icon: 'none'
        });
      }
    },
    fail () {
      wx.showToast({
        title: '登录失败，请重试',
        icon: 'none'
      });
    }
  })
}

/** 用户配置信息 */
const userConfig = (myApp) => {
  app = myApp;

  const callback = (success, data) => {
    if (!success) return;
    app.globalData.app_init = true;
    app.globalData.user_config = data;

    // system maintain
    if (data.switch && data.switch['9999'] == 1) {
      wx.reLaunch({
        url: '/pages/9999/9999',
      });
      return;
    } else {
      const pages = getCurrentPages();
      if (pages.length > 0) {
        const currPage = pages[pages.length - 1];
        if (currPage.route == 'pages/9999/9999') {
          wx.reLaunch({
            url: '/pages/index/index',
          });
          return;
        }
      }
    }

    // 刷新当前页面
    reLaunchCurrentPage();
  }
  request('user/config', {}, callback);
}

/** 获取用户profile */
const getProfile = (app, callback) => {
  request('user/getProfile', null, (success, data) => {
    if (success) {
      if (data.audit_status == 1) {
        app.globalData.is_login = true;
      }
      app.globalData.profile = data;
    }
    if (callback) {
      callback(success, data);
    }
  });
}

/** 上传用户信息 */
const userCompleteUser = (detail, app, page, callback) => {
  callback = callback || (() => {});
  if (detail.errMsg != 'getUserInfo:ok') {
    wx.showToast({
      title: '无法获取用户信息',
      icon: 'none'
    });
    callback(false);
  } else {
    const data = {
      rawData: detail.rawData,
      iv: detail.iv,
      signature: detail.signature,
      encryptedData: detail.encryptedData
    };

    app.globalData.is_login = true;
    page.setData({
      is_login: true
    });
    request('user/completeUser', data, callback);
  }
}

/** 更改手机号码 */
const updateUserPhone = (data, callback) => {
  request('user/updateUserPhone', data, callback);
}

/** 更改车辆信息 */
const updateUserCar = (data, callback) => {
  request('user/updateUserCar', data, callback);
}

/** 同意用户协议 */
const updateUserAgreement = (callback) => {
  request('user/updateUserAgreement', {}, callback);
}

/** 更改用户发布引导信息 */
const updateUserPublishGuide = (callback) => {
  request('user/updateUserPublishGuide', {}, callback);
}

/** 获取发布模板 */
const getTemplateList = (callback) => {
  request('trip/getTemplateList', null, callback);
}
/** 删除发布模板 */
const deleteTemplate = (data,callback) => {
  request('trip/deleteTemplate', data, callback);
}

/** 获取群列表(包含群详情) */
const getGroupListByUserId = (callback) => {
  request('group/getListByUserId', null, callback);
}
const getDetailByGroupId = (data, callback) => {
  request('group/getDetailByGroupId', data, callback);
}
const exitGroup = (data, callback) => {
  request('group/exitGroup', data, callback);
}
const updateNotice = (data, callback) => {
  request('group/updateNotice', data, callback);
}
//置顶
const topOneTrip = (data, callback) => {
  request('group/topOneTrip', data, callback);
}
const unTopOneTrip = (data, callback) => {
  request('group/unTopOneTrip', data, callback);
}

/** 获取分享群的信息 */
const getTripDetailInSharePage = (data, callback) => {
  const makeRequest = (params) => {
    params = params || {};
    request('trip/getTripDetailInSharePage', {
      user_id: data.user_id,
      trip_id: data.trip_id,
      trip_type: data.trip_type,
      ...params,
    }, callback);
  }
  const successCb = (r) => {
    if (r.errMsg == 'getShareInfo:ok') {
      const params = {
        iv: r.iv,
        encryptedData: r.encryptedData,
      };
      makeRequest(params);
    } else {
      makeRequest();
    }
    // todo 这个请求应该是封装在makeRequest()了，请确认
    // request('trip/getTripDetailInSharePage', params, callback);
  }

  wx.getShareInfo({ shareTicket: data.shareTicket, success: successCb, fail: makeRequest });
}

/**
 * 车找人发布、保存
 */
const parseDriverTripDetail = (responseData) => {
  let tags = [];
  config.driver_tags.map(tag => {
    if (responseData[tag.value] == 1) {
      tags.push(tag.label);
    }
  });
  responseData.tags = tags;
  if (responseData.user_info) {
    responseData.user_info = JSON.parse(responseData.user_info);
  }

  responseData.begin_time = moment(`${responseData.begin_date} ${responseData.begin_time}`).format('LT');
  responseData.group_info = responseData.group_info ? JSON.parse(responseData.group_info) : [];
  responseData.markers = [];
  responseData.include_points = [];
  if (responseData.lbs_route_info) {
    const points = parsePolyline(responseData.lbs_route_info);
    responseData.include_points = points || [];
    responseData.polyline = [{
      points: points,
      width: 4,
      color: '#3cc51f'
    }];
  }
  if (responseData.start_location_point) {
    const start_location_point = JSON.parse(responseData.start_location_point);
    if (start_location_point.length == 2) {
      responseData.markers.push({
        id: 'start',
        longitude: start_location_point[1],
        latitude: start_location_point[0],
        iconPath: '/images/map_start.png',
        width: 30,
        height: 30,
        anchor: { x: 0.5, y: 0.5 }
      });
    }
  }
  if (responseData.end_location_point) {
    const end_location_point = JSON.parse(responseData.end_location_point);
    if (end_location_point.length == 2) {
      responseData.markers.push({
        id: 'end',
        longitude: end_location_point[1],
        latitude: end_location_point[0],
        iconPath: '/images/map_end.png',
        width: 30,
        height: 30,
        anchor: { x: 0.5, y: 0.5 }
      });
    }
  }

  if (responseData.include_points.length == 0) {
    responseData.include_points = responseData.markers;
  }
  return responseData;
}
const driverPublish = (data, callback) => {
  request('trip/driverPublish', data, callback);
}
const driverSave = (data, callback) => {
  request('trip/driverSave', data, callback);
}
const driverGetDetailByTripId = (data, callback) => {
  request('trip/driverGetDetailByTripId', data, (success, responseData) => {
    if (success && responseData) {
      responseData = parseDriverTripDetail(responseData);
    }

    callback(success, responseData);
  });
}
const driverGetMyList = (params, callback) => {
  request('trip/driverGetMyList', params, (success, data) => {
    if (success && data && data.trips && data.trips.length > 0) {
      data.trips = data.trips.map(item => {
        item.begin_time = moment(`${item.begin_date} ${item.begin_time}`).format('LT');
        item.user_info = item.user_info ? JSON.parse(item.user_info) : {};
        return item;
      });
    }
    if (callback) {
      callback(success, data);
    }
  });
}
const driverDeleteMy = (data, callback) => {
  request('trip/driverDeleteMy', data, callback);
}
const driverGetListByGroupId = (data, callback) => {
  request('trip/driverGetListByGroupId', data, (success, data) => {
    if (success && data && data.trips && data.trips.length > 0) {
      data.trips = data.trips.map(item => {
        item.begin_time = moment(`${item.begin_date} ${item.begin_time}`).format('LT');
        item.user_info = item.user_info ? JSON.parse(item.user_info) : {};
        return item;
      });
    }
    if (callback) {
      callback(success, data);
    }
  });
}
const driverSearch = (data, callback) => {
  request('search/all', { ...data, trip_type: 0 }, (success, data) => {
    if (success && data && data.trips && data.trips.length > 0) {
      data.trips = data.trips.map(item => {
        item.begin_time = moment(`${item.begin_date} ${item.begin_time}`).format('LT');
        item.user_info = item.user_info ? JSON.parse(item.user_info) : {};
        return item;
      });
    }
    if (callback) {
      callback(success, data);
    }
  });
}

/**
 * 人找车发布、保存
 */
const parsePassengerTripDetail = (responseData) => {
  let tags = [];
  config.passenger_tags.map(tag => {
    if (responseData[tag.value] == 1) {
      tags.push(tag.label);
    }
  });
  responseData.tags = tags;
  if (responseData.user_info) {
    responseData.user_info = JSON.parse(responseData.user_info);
  }
  responseData.begin_time = moment(`${responseData.begin_date} ${responseData.begin_time}`).format('LT');
  responseData.group_info = responseData.group_info ? JSON.parse(responseData.group_info) : [];
  if (responseData.lbs_route_info) {
    const points = parsePolyline(responseData.lbs_route_info);
    responseData.include_points = points || [];
    responseData.polyline = [{
      points: points,
      width: 4,
      color: '#3cc51f'
    }];
  }
  responseData.markers = [];
  responseData.include_points = [];
  if (responseData.start_location_point) {
    const start_location_point = JSON.parse(responseData.start_location_point);
    if (start_location_point.length == 2) {
      responseData.markers.push({
        id: 'start',
        longitude: start_location_point[1],
        latitude: start_location_point[0],
        iconPath: '/images/map_start.png',
        width: 30,
        height: 30,
        anchor: { x: 0.5, y: 0.5 }
      });
    }
  }
  if (responseData.end_location_point) {
    const end_location_point = JSON.parse(responseData.end_location_point);
    if (end_location_point.length == 2) {
      responseData.markers.push({
        id: 'end',
        longitude: end_location_point[1],
        latitude: end_location_point[0],
        iconPath: '/images/map_end.png',
        width: 30,
        height: 30,
        anchor: { x: 0.5, y: 0.5 }
      });
    }
  }

  if (responseData.include_points.length == 0) {
    responseData.include_points = responseData.markers;
  }
  return responseData;
}
const passengerPublish = (data, callback) => {
  request('trip/passengerPublish', data, callback);
}
const passengerSave = (data, callback) => {
  request('trip/passengerSave', data, callback);
}
const passengerGetDetailByTripId = (data, callback) => {
  request('trip/passengerGetDetailByTripId', data, (success, responseData) => {
    if (success && responseData) {
      responseData = parsePassengerTripDetail(responseData);
    }

    callback(success, responseData);
  });
}
const passengerGetMyList = (params, callback) => {
  request('trip/passengerGetMyList', params, (success, data) => {
    if (success && data && data.trips && data.trips.length > 0) {
      data.trips = data.trips.map(item => {
        item.begin_time = moment(`${item.begin_date} ${item.begin_time}`).format('LT');
        item.user_info = item.user_info ? JSON.parse(item.user_info) : {};
        return item;
      });
    }
    if (callback) {
      callback(success, data);
    }
  });
}
const passengerDeleteMy = (data, callback) => {
  request('trip/passengerDeleteMy', data, callback);
}
const passengerGetListByGroupId = (data, callback) => {
  request('trip/passengerGetListByGroupId', data, (success, data) => {
    if (success && data && data.trips && data.trips.length > 0) {
      data.trips = data.trips.map(item => {
        item.begin_time = moment(`${item.begin_date} ${item.begin_time}`).format('LT');
        item.user_info = item.user_info ? JSON.parse(item.user_info) : {};
        return item;
      });
    }
    if (callback) {
      callback(success, data);
    }
  });
}
const passengerSearch = (data, callback) => {
  request('search/all', { ...data, trip_type: 1 }, (success, data) => {
    if (success && data && data.trips && data.trips.length > 0) {
      data.trips = data.trips.map(item => {
        item.begin_time = moment(`${item.begin_date} ${item.begin_time}`).format('LT');
        item.user_info = item.user_info ? JSON.parse(item.user_info) : {};
        return item;
      });
    }
    if (callback) {
      callback(success, data);
    }
  });
}

module.exports = {
  moment,
  reLaunchCurrentPage,
  request,
  login,
  getProfile,
  updateUserCar,
  updateUserAgreement,
  updateUserPublishGuide,
  userConfig,
  userCompleteUser,
  updateUserPhone,
  getTemplateList,
  deleteTemplate,
  getGroupListByUserId,
  getDetailByGroupId,
  getTripDetailInSharePage,
  exitGroup,
  updateNotice,
  topOneTrip,
  unTopOneTrip,
  parseDriverTripDetail,
  driverPublish,
  driverSave,
  driverGetDetailByTripId,
  driverGetMyList,
  driverDeleteMy,
  driverGetListByGroupId,
  driverSearch,
  parsePassengerTripDetail,
  passengerPublish,
  passengerSave,
  passengerGetDetailByTripId,
  passengerGetMyList,
  passengerDeleteMy,
  passengerGetListByGroupId,
  passengerSearch,
}
