const service = require('../../utils/service');
const config = require('../../utils/config');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    group_id: null,
    form_data: {},
    loading_data: true,
    loading_submit: false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    self.setData({
      group_id: options.group_id
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
    service.getDetailByGroupId({group_id: self.data.group_id}, (success, data) => {
      self.setData({
        loading_data: false
      });
      if (success) {
        self.setData({
          form_data: data
        });
      }
    });
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

  bindinput(e) {
    const { name } = e.currentTarget.dataset;
    self.setData({
      form_data: {
        ...self.data.form_data,
        [name]: e.detail.value,
      }
    });
  },

  formSubmit: () => {
    const { form_data } = self.data;
    const params = {
      group_id: form_data.group_id,
      notice: form_data.notice,
    }
    if (!params.notice) {
      wx.showToast({
        icon: 'none', title: '群公告内容不能为空',
      });
      return;
    }
    self.setData({
      loading_submit: true
    });
    service.updateNotice(params, (success, data) => {
      self.setData({
        loading_submit: false
      });
      if (success) {
        wx.showToast({
          title: '群公告更新成功',
        });
        wx.navigateBack();
      }
    });
  },
})