const service = require('../../utils/service');
const config = require('../../utils/config');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    list: {
      trips: [],
      has_next: false,
      page: 0,
    },
    loading: false,
    loading_more: false,
    params: {}
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    self.setData({
      params: options
    });
    wx.startPullDownRefresh();
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
    self.setData({
      loading: true
    });
    service.driverSearch(self.data.params, (success, data) => {
      wx.stopPullDownRefresh();
      self.setData({
        loading: false,
        list: {
          ...data,
          page: 0,
        }
      });
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
  makeCall: function (e) {
    const { phone } = e.currentTarget.dataset;
    wx.makePhoneCall({
      phoneNumber: phone,
    });
  },

  loadMore: () => {
    const { loading_more, loading, list } = self.data;
    const page = list.page + 1;
    if (loading_more || loading) return;

    self.setData({ loading_more: true });
    service.driverSearch({
      ...self.data.params,
      page
    }, (success, data) => {
      self.setData({ loading_more: false });
      if (success) {
        self.setData({
          list: {
            trips: list.trips.concat(data.trips),
            has_next: data.has_next,
            page: page
          }
        });
      }
    });
  }
})