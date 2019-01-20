//index.js
const service = require('../../utils/service');
const app = getApp();
let self;
Page({
  data: {
    notice_list: [],
    list: [],
    loading: false,
    app_init: false,
    docoment: app.globalData.user_config.docoment || {}
  },
  onLoad: function (r) {
    self = this;
    self.setData({
      docoment: app.globalData.user_config.docoment
    });
  },
  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    const { user_config, app_init } = app.globalData;
    if (user_config && user_config.docoment) {
      this.setData({
        notice_list: user_config.docoment.notice_list,
        app_init: app_init || false
      });
    }
    this.onLoadData();
  },
  onPullDownRefresh: function () {
    // setTimeout(wx.stopPullDownRefresh, 2000);
    this.onLoadData();
  },

  onLoadData: () => {
    self.setData({ loading: true});
    service.getGroupListByUserId((success, data) => {
      if (success) {
        self.setData({
          list: data,
          loading: false
        });
      } else {
        self.setData({ loading: false });
      }

      wx.stopPullDownRefresh();
    });
  }
})
