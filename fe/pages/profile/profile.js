// pages/profile/profile.js
const service = require('../../utils/service');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    loading_profile: true,
    is_login: false,
    profile: app.globalData.profile || {},
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    this.setData({
      is_login: app.globalData.is_login
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
    this.loadProfile();
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
   * 微信授权获取个人信息
   */
  getUserInfo: (e) => {
    self.setData({
      loading_profile: true
    });
    service.userCompleteUser(e.detail, app, self, (success) => {
      self.loadProfile();
    });
  },

  loadProfile: () => {
    self.setData({
      loading_profile: true
    });
    service.getProfile(app, (success, data) => {
      wx.stopPullDownRefresh();
      self.setData({
        loading_profile: false,
        profile: data || {},
        is_login: app.globalData.is_login,
      });
    });
  },
})