const service = require('../../utils/service');
const config = require('../../utils/config');
const WxParse = require('../../utils/wxParse/wxParse.js');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
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
    const { user_config } = app.globalData;
    const { docoment } = (user_config || {}) || {};
    const contact = docoment.contact || ''
    WxParse.wxParse('contact', 'html', contact, self, 5);
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

  },

  /**
   * 复制微信号
   */
  clipboardWechatData: function () {
    wx.setClipboardData({
      data: this.data.wechat,
    })
  },

  /**
   * 拨打客服电话
   */
  makePhoneCall: function () {
    wx.makePhoneCall({
      phoneNumber: this.data.phone,
    });
  },

})