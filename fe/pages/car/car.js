// pages/car/car.js
const service = require('../../utils/service');
const config = require('../../utils/config');
const app = getApp();
let self;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    colors: config.car_colors,
    types: config.car_types,
    profile: null,
    car_color_index: -1,
    car_type_index: -1,
    loading_data: false,
    loading_update: false,
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
      loading_data: true,
      loading_update: false
    });
    

    const { user_config } = app.globalData;
    if (user_config && user_config.docoment) {
      this.setData({
        docoment: user_config.docoment,
      });
    }

    service.getProfile(app, this.onGetProfile);
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

  onGetProfile: (success, data) => {
    const { colors, types } = self.data;
    data = data || {};
    self.setData({
      loading_data: false,
      profile: data,
      car_type_index: types.indexOf(data.car_type),
      car_color_index: colors.indexOf(data.car_color),
    });
  },

  bindinput(e){
    const { name } = e.currentTarget.dataset;
    this.setData({
      profile: {
        ...this.data.profile,
        [name]: e.detail.value,
      }
    });
  },

  bindPickerChange(e) {
    const { name } = e.currentTarget.dataset;
    const { colors, types } = this.data;
    let value, index;
    switch(name) {
      case 'car_color':
        value = colors[e.detail.value];
      break;
      case 'car_type':
        value = this.data.types[e.detail.value];
        break;
    }
    this.setData({
      profile: {
        ...this.data.profile,
        [name]: value,
      },
      [`${name}_index`]: e.detail.value
    });
  },

  formSubmit() {
    const { profile } = self.data;

    if (!profile.car_plate) {
      wx.showToast({
        icon: 'none', title: '车牌不能为空',
      });
    } else if (!profile.car_brand) {
      wx.showToast({
        icon: 'none', title: '品牌不能为空',
      });
    } else if (!profile.car_model) {
      wx.showToast({
        icon: 'none', title: '型号不能为空',
      });
    } else if (!profile.car_color) {
      wx.showToast({
        icon: 'none', title: '颜色不能为空',
      });
    } else if (!profile.car_type) {
      wx.showToast({
        icon: 'none', title: '类型不能为空',
      });
    } else {
      const params = {
        car_plate: profile.car_plate,
        car_brand: profile.car_brand,
        car_model: profile.car_model,
        car_color: profile.car_color,
        car_type: profile.car_type,
      };
      self.setData({
        loading_update: true
      });
      console.error(params);
      service.updateUserCar(params, (success) => {
        self.setData({
          loading_update: false
        });
        if (success) {
          wx.showToast({
            title: '车辆信息提交成功',
          });
          if (self.data.back) {
            wx.navigateBack();
          }
        }
      });
    }
  },
})