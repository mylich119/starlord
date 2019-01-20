// pages/publish/publish.js
const service = require('../../utils/service');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    templates: [],
    docoment: {}
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
    service.getTemplateList((success, data) => {
      self.setData({
        templates: data || []
      });
      wx.stopPullDownRefresh();
    });
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

  goPage: function (e) {
    const { page, tripid, userid } = e.currentTarget.dataset;
    let url = `/pages/${page}/${page}`;
    if (tripid && userid) {
      url = `${url}?trip_id=${tripid}&user_id=${userid}`
    }
    wx.navigateTo({
      url
    })
  },

  onDeleteTemplate: function (e) {
    const { tripid, triptype } = e.currentTarget.dataset;
    wx.showModal({
      title: '删除模板',
      content: '您确定删除该模板吗？',
      success(res) {
        if (res.confirm) {
          wx.showLoading({mask: true});
          service.deleteTemplate({
            trip_id: tripid,
            trip_type: triptype
          }, (success, data) => {
            wx.hideLoading();
            if (success) {
              wx.startPullDownRefresh();
            }
          });
        }
      }
    })
  }
})