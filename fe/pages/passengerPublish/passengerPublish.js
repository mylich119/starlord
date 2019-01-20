const service = require('../../utils/service');
const config = require('../../utils/config');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    is_login: false,
    tags: config.passenger_tags,
    trip_id: null,
    user_id: null,
    form_data: {},
    loading_data: false,
    loading_submit: false,
    loading_profile: false,
    profile: app.globalData.profile || {},
    limit: {
      min_begin_date: service.moment().format('YYYY-MM-DD'),
      max_begin_date: service.moment().add(1, 'years').format('YYYY-MM-DD'),
    }
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    options = options || {};
    self.setData({
      is_login: app.globalData.is_login,
      trip_id: options.trip_id || null,
      user_id: options.user_id || null,
    });
    self.loadTemplate();
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    this.loadProfile();
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    return app.appShare();
  },

  loadTemplate: () => {
    const { trip_id, user_id } = self.data;
    if (trip_id && user_id) {
      self.setData({
        loading_data: true
      });
      service.passengerGetDetailByTripId({ trip_id, user_id }, (success, data) => {
        self.setData({
          loading_data: false,
          form_data: data || {}
        });
      });
    }
  },

  loadProfile: () => {
    self.setData({
      loading_profile: true,
      profile: app.globalData.profile || {},
    });
    service.getProfile(app, (success, data) => {
      self.setData({
        loading_profile: false,
        profile: data || {},
        is_login: app.globalData.is_login,
      });
    });
  },

  chooseLocation: function (e) {
    var locationType = e.currentTarget.dataset.location;
    wx.chooseLocation({
      success: function (res) {
        if (locationType == 'start') {
          self.setData({
            form_data: {
              ...self.data.form_data,
              start_location_address: res.address,
              start_location_name: res.name,
              start_location_point: `(${res.latitude},${res.longitude})`,
            }
          });
        } else {
          self.setData({
            form_data: {
              ...self.data.form_data,
              end_location_address: res.address,
              end_location_name: res.name,
              end_location_point: `(${res.latitude},${res.longitude})`,
            }
          });
        }
      },
    })
  },
  bindRadioChange: (e) => {
    self.setData({
      form_data: {
        ...self.data.form_data,
        begin_date: null,
        is_everyday: e.detail.value
      }
    });
  },
  bindDateChange: function (e) {
    self.setData({
      form_data: {
        ...self.data.form_data,
        begin_date: e.detail.value
      }
    });
  },
  bindTimeChange: function (e) {
    self.setData({
      form_data: {
        ...self.data.form_data,
        begin_time: e.detail.value
      }
    });
  },
  bindinput(e) {
    const { name } = e.currentTarget.dataset;
    self.setData({
      form_data: {
        ...self.data.form_data,
        [name]: e.detail.value,
      }
    });
  },
  
  toggleTag: (e) => {
    const { name } = e.currentTarget.dataset;
    let form_data = self.data.form_data || {};
    form_data[name] = form_data[name] ? 0 : 1;
    self.setData({
      form_data,
    });
  },

  /**
   * 微信授权获取个人信息
   */
  getUserInfo: (e) => {
    service.userCompleteUser(e.detail, app, self);
  },

  formSubmit: function (e) {
    const { detail } = e;
    const { target } = detail;
    const submitType = target.dataset.type;
    const { form_data, trip_id, tags, loading_submit, profile } = self.data;
    if (loading_submit) return;
    if (!form_data.begin_date && form_data.is_everyday != 1) {
      wx.showToast({
        icon: 'none', title: '日期不能为空',
      });
    } else if (!form_data.begin_time) {
      wx.showToast({
        icon: 'none', title: '时间不能为空',
      });
    } else if (!form_data.start_location_name) {
      wx.showToast({
        icon: 'none', title: '请选择始发点',
      });
    } else if (!form_data.end_location_name) {
      wx.showToast({
        icon: 'none', title: '请选择终点',
      });
    } else if (!profile.phone) {
      wx.showToast({
        icon: 'none', title: '请填写手机号码',
      });
    } else {
      let params = {
        is_everyday: form_data.is_everyday || '',
        begin_date: form_data.begin_date || '',
        begin_time: form_data.begin_time || '',
        start_location_name: form_data.start_location_name || '',
        start_location_address: form_data.start_location_address || '',
        start_location_point: form_data.start_location_point || '',
        end_location_name: form_data.end_location_name || '',
        end_location_address: form_data.end_location_address || '',
        end_location_point: form_data.end_location_point || '',
        price_everyone: form_data.price_everyone || '',
        people_num: form_data.people_num || '',
        tips: form_data.tips || '',
      };
      if (trip_id) {
        params.trip_id = trip_id;
      }
      tags.map(tag => {
        params[tag.value] = (form_data[tag.value] == 1) ? 1 : 0;
      });

      const callback = (success, tripInfo) => {
        if (success) {
          wx.showToast({
            title: '提交成功'
          });
          if (submitType == 'publish') {
            wx.redirectTo({
              url: `/pages/passengerPublishInfo/passengerPublishInfo?trip_id=${tripInfo.trip_id}&user_id=${tripInfo.user_id}&from_publish=1`,
            });
          } else {
            wx.navigateBack({
              delta: 1
            });
          }
        } else {
          self.setData({
            loading_submit: false
          });
        }
      }

      if (submitType == 'publish') {
        if (profile && profile.show_agreement == 1) {
          wx.navigateTo({
            url: '/pages/agreement/agreement',
          });
          return;
        }
        self.setData({
          loading_submit: true
        });
        service.passengerPublish(params, callback);
      } else {
        self.setData({
          loading_submit: true
        });
        service.passengerSave(params, callback);
      }
    }
  }
})