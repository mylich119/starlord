// pages/info/info.js
const service = require('../../utils/service');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    is_login: false,
    profile: {},
    loading_data: false,
    loading_submit: false,
    docoment: {},
    back: false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    this.setData({
      back: !!(options.back == 1)
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
    this.setData({
      is_login: app.globalData.is_login
    });
    const { user_config } = app.globalData;
    if (user_config && user_config.docoment) {
      this.setData({
        docoment: user_config.docoment,
      });
    }
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
    self.loadProfile();
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

  /**
   * 获取本机手机号码
   */
  getPhoneNumber: function (e) {
    service.userCompleteUser(e.detail);
  },
  /**
   * 微信授权获取个人信息
   */
  getUserInfo: (e) => {
    service.userCompleteUser(e.detail, app, self, (success) => {
      if (success) {
        self.loadProfile();
      }
    });
  },

  loadProfile: () => {
    if (!self.data.is_login) {
      wx.stopPullDownRefresh();
      return;
    }
    self.setData({
      loading_data: true
    });
    service.getProfile(app, (success, data) => {
      self.setData({
        loading_data: false,
        profile: data || {}
      });
      wx.stopPullDownRefresh();
    });
  },

  bindinput(e) {
    const { name } = e.currentTarget.dataset;
    self.setData({
      profile: {
        ...self.data.profile,
        [name]: e.detail.value,
      }
    });
  },

  onSubmit: () => {
    const { loading_submit, profile } = self.data;
    if (loading_submit) return;
    if (!profile.phone) {
      wx.showToast({
        icon: 'none', title: '手机号码不能为空',
      });
      return;
    }
    self.setData({
      loading_submit: true
    });
    service.updateUserPhone({phone: profile.phone}, (success) => {
      self.setData({
        loading_submit: false
      });
      if (success) {
        wx.showToast({
          title: '信息更改成功'
        });
        if (self.data.back) {
          wx.navigateBack();
        }
      }
    });
  },
})