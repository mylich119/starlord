const service = require('../../utils/service');
const config = require('../../utils/config');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    trip_id: null,
    user_id: null,
    detail: {},
    loading_data: true,
    app_init: false,
    share_page_info: null,
    docoment: {}
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    wx.showShareMenu({
      withShareTicket: true
    });
    options = options || {};
    this.setData({
      trip_id: options.trip_id || null,
      user_id: options.user_id || null,
      docoment: app.globalData.user_config.docoment
    });
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
    self.setData({
      loading_data: true,
      app_init: app.globalData.app_init || false,
      share_page_info: (app.globalData.user_config && app.globalData.user_config.docoment) ? app.globalData.user_config.docoment.share_page_info : null
    });
    wx.startPullDownRefresh();
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
    const { trip_id, user_id } = self.data;
    const { wx_config } = app.globalData;
    const { shareTicket } = wx_config;
    // if (!trip_id || !user_id || !shareTicket) {
    if (!trip_id || !user_id) {
      wx.showToast({
        title: '页面参数不正确',
        icon: null
      });
      wx.stopPullDownRefresh();
      return;
    }
    self.setData({ loading_data: true });
    const params = {
      trip_id, user_id, shareTicket,
      trip_type: 1
    };
    const callback = (success, data) => {
      wx.stopPullDownRefresh();
      self.setData({ loading_data: false });
      if (success && data) {
        data = service.parsePassengerTripDetail(data);
        self.setData({
          detail: data || {}
        });

        const isShareInfoNeedPopup = wx.getStorageSync(`share_info_has_showed_${trip_id}_${data.group_id}`);
        if (data.is_share_owner == 1 && !isShareInfoNeedPopup) {
          wx.showModal({
            title: '提示',
            content: self.data.docoment.share_page_info,
            showCancel: false,
            confirmText: '知道了',
            success(res) {
              if (res.confirm) {
                wx.setStorageSync(`share_info_has_showed_${trip_id}_${data.group_id}`, true);
              }
            }
          })
        }
      }
    }
    service.getTripDetailInSharePage(params, callback);
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
    const { user_config } = app.globalData;
    const share_title = (user_config && user_config.docoment && user_config.docoment.share_description) ? user_config.docoment.share_description : null;
    const { trip_id, user_id } = self.data;

    return {
      title: share_title,
      path: `/pages/passengerPublishShare/passengerPublishShare?trip_id=${trip_id}&user_id=${user_id}`,
      imageUrl: '../../images/address.png'
    };
  },

  nativeBack: () => {
    wx.reLaunch({
      url: '/pages/index/index',
    })
  },
  makeCall: function (e) {
    const { phone } = e.currentTarget.dataset;
    wx.makePhoneCall({
      phoneNumber: phone,
    });
  },
})
