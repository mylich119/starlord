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
    from_search: 0,
    search_my: 0,
    loading_data: true,
    detail: {},
    hide_share: false,
    need_publish_guide: 0,
    docoment: app.globalData.user_config.docoment,
    is_hide_user: false,
    isModalVisible: false
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
      from_search: options.from_search == 1 ? 1 : 0,
      search_my: options.search_my == 1 ? 1 : 0,
      from_publish: options.from_publish == 1 ? 1 : 0,
      hide_share: !!(options.hide_share == 1),
      is_hide_user: options.is_hide_user || false,
      need_publish_guide: app.globalData.profile ? app.globalData.profile.need_publish_guide : 0,
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
      loading_data: true
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
    if (trip_id && user_id) {
      self.setData({
        loading_data: true
      });
      service.passengerGetDetailByTripId({ trip_id, user_id }, (success, data) => {
        wx.stopPullDownRefresh();
        self.setData({
          loading_data: false,
          detail: data || {}
        });
      });
    }
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
    const { trip_id, user_id, detail } = self.data;

    return {
      title: share_title,
      path: `/pages/passengerPublishShare/passengerPublishShare?trip_id=${trip_id}&user_id=${user_id}`,
      imageUrl: (detail && detail.share_img_url) ? detail.share_img_url : null
    };
  },

  makeCall: function (e) {
    const { phone } = e.currentTarget.dataset;
    wx.makePhoneCall({
      phoneNumber: phone,
    });
  },
  showGuideModal: function () {
    const { need_publish_guide } = self.data;
    if (need_publish_guide == 1) {
      self.setData({isModalVisible: true})
    }
  },
  onIKnow: function() {
    self.setData({isModalVisible: false});
    service.updateUserPublishGuide((success) => {
      if (success) {
        self.loadProfile();
      }
    })
  },
  onClipboard: (e) => {
    const { content } = e.currentTarget.dataset;
    wx.setClipboardData({
      data: content,
    })
  },
  loadProfile() {
    service.getProfile(app, (success, data) => {
      const profile = data || {};
      self.setData({ need_publish_guide: profile.need_publish_guide });
    });
  }
})
