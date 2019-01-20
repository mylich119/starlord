const service = require('../../utils/service');
const config = require('../../utils/config');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    tags: config.driver_tags,
    tabs: ['车找人', '人找车'],
    contentHeight: 0,
    page_params: {},
    params: {
      trip_type: 0
    },
    docoment: {},
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    self.setData({
      page_params: options || {},
    });
    wx.getSystemInfo({
      success: function (res) {
        self.setData({
          contentHeight: res.windowHeight - res.windowWidth / 750 * 68
        });
      }
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
    const { user_config } = app.globalData;
    if (user_config && user_config.docoment) {
      this.setData({
        docoment: user_config.docoment,
      });
    }
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

  navTabClick: function (e) {
    const currentTab = e.currentTarget.id;
    this.setData({
      params: {
        ...this.data.params,
        trip_type: currentTab
      },
      tags: currentTab == 0 ? config.driver_tags : config.passenger_tags
    });
  },
  onSearch: function (e) {
    const params = {
      ...self.data.page_params,
      ...self.data.params
    };
    if (!params.begin_date) {
      wx.showToast({
        icon: 'none', title: '请选择日期',
      });
    } else if (!params.begin_time) {
      wx.showToast({
        icon: 'none', title: '请选择时间',
      });
    } else if (!params.start_location_name) {
      wx.showToast({
        icon: 'none', title: '请选择始发点',
      });
    } else if (!params.end_location_name) {
      wx.showToast({
        icon: 'none', title: '请选择终点',
      });
    } else {
      const searchParams = {
        begin_date: params.begin_date || null,
        begin_time: params.begin_time || null,
        target_start: params.start_location_point || null,
        target_end: params.end_location_point || null,
        only_in_my_group: params.only_in_my_group || null,
      };
      const urlParams = Object.keys(searchParams).map(key => {
        return [key, searchParams[key]].join('=');
      }).join('&');


      const url = params.trip_type == 0 ? '/pages/driverList/driverList' : '/pages/passengerList/passengerList';
      wx.navigateTo({
        url: `${url}?${urlParams}`,
      })
    }
  },
  toggleTag: (e) => {
    const { name } = e.currentTarget.dataset;
    let params = self.data.params || {};
    params[name] = params[name] ? 0 : 1;
    self.setData({
      params,
    });
  },
  chooseLocation: function (e) {
    var locationType = e.currentTarget.dataset.location;
    wx.chooseLocation({
      success: function (res) {
        if (locationType == 'start') {
          self.setData({
            params: {
              ...self.data.params,
              start_location_address: res.address,
              start_location_name: res.name,
              start_location_point: `(${res.latitude},${res.longitude})`,
            }
          });
        } else {
          self.setData({
            params: {
              ...self.data.params,
              end_location_address: res.address,
              end_location_name: res.name,
              end_location_point: `(${res.latitude},${res.longitude})`,
            }
          });
        }
      },
    })
  },
  bindDateChange: function (e) {
    self.setData({
      params: {
        ...self.data.params,
        begin_date: e.detail.value
      }
    });
  },
  bindTimeChange: function (e) {
    self.setData({
      params: {
        ...self.data.params,
        begin_time: e.detail.value
      }
    });
  },
  bindSwitch: (e) => {
    self.setData({
      params: {
        ...self.data.params,
        only_in_my_group: e.detail.value ? 1 : 0
      }
    });
  },
})