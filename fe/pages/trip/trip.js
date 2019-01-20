// pages/trip/trip.js
const service = require('../../utils/service');
const app = getApp();
let self;
Page({
  /**
   * 页面的初始数据
   */
  data: {
    tabs: ['车找人', '人找车'],
    currentTab: 0,
    contentHeight: 0,
    loading_passenger: false,
    loading_more_passenger: false,
    loading_driver: false,
    loading_more_driver: false,
    driverTrips: {
      trips: [],
      has_next: false,
      page: 0,
    },
    passengerTrips: {
      trips: [],
      has_next: false,
      page: 0,
    },
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    self = this;
    wx.getSystemInfo({
      success: function (res) {
        self.setData({
          contentHeight: res.windowHeight - res.windowWidth / 750 * 68
        });
      }
    });
    self.loadData();
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

  passengerGetMyList: () => {
    const { loading_passenger } = self.data;
    if (loading_passenger) return;

    self.setData({ loading_passenger: true });
    service.passengerGetMyList(null, (success, data) => {
      self.setData({ loading_passenger: false });
      if (success) {
        self.setData({
          passengerTrips: {
            ...data,
            page: 0,
          }
        });
      }
    });
  },
  passengerGetMoreMyList: () => {
    const { loading_more_passenger, loading_passenger, passengerTrips } = self.data;
    const page = passengerTrips.page + 1;
    if (loading_more_passenger || loading_passenger) return;

    self.setData({ loading_more_passenger: true });
    service.passengerGetMyList({ page: page }, (success, data) => {
      self.setData({ loading_more_passenger: false });
      if (success) {
        self.setData({
          passengerTrips: {
            trips: passengerTrips.trips.concat(data.trips),
            has_next: data.has_next,
            page: page,
          }
        });
      }
    });
  },
  driverGetMyList: () => {
    if (self.data.loading_driver) return;
    self.setData({ loading_driver: true });
    service.driverGetMyList(null, (success, data) => {
      self.setData({ loading_driver: false });
      if (success) {
        self.setData({
          driverTrips: {
            ...data,
            page: 0,
          }
        });
      }
    });
  },

  driverGetMoreMyList: () => {
    const { loading_more_driver, loading_driver, driverTrips } = self.data;
    const page = driverTrips.page + 1;
    if (loading_more_driver || loading_driver) return;

    self.setData({ loading_more_driver: true });
    service.driverGetMyList({ page: page }, (success, data) => {
      self.setData({ loading_more_driver: false });
      if (success) {
        self.setData({
          driverTrips: {
            trips: driverTrips.trips.concat(data.trips),
            has_next: data.has_next,
            page: page
          }
        });
      }
    });
  },

  loadData: () => {
    const { currentTab } = self.data;
    if (currentTab == 0) {
      self.driverGetMyList();
    } else {
      self.passengerGetMyList();
    }
  },

  bindTabChange: function (e) {
    var current = e.detail.current;
    this.setData({
      currentTab: current
    });
    this.loadData();
  },
  navTabClick: function (e) {
    this.setData({
      currentTab: e.currentTarget.id
    });
    this.loadData();
  },
  onCancelTrip: (e) => {
    const { tripid, triptype } = e.target.dataset;
    wx.showModal({
      title: '删除行程',
      content: '您确定删除该行程吗？',
      success(res) {
        if (res.confirm) {
          wx.showLoading({mask: true});
          if (triptype == 'driver') {
            service.driverDeleteMy({
              trip_id: tripid,
            }, (success) => {
              wx.hideLoading();
              if (success) {
                wx.showToast({
                  title: '行程已删除',
                });
                self.driverGetMyList();
              }
            });
          } else if (triptype == 'passenger') {
            service.passengerDeleteMy({
              trip_id: tripid,
            }, (success) => {
              wx.hideLoading();
              if (success) {
                wx.showToast({
                  title: '行程已删除',
                });
                self.passengerGetMyList();
              }
            });
          }
        }
      }
    })
  },
  makeCall: function (e) {
    const { phone } = e.currentTarget.dataset;
    wx.makePhoneCall({
      phoneNumber: phone,
    });
  },

  loadMore: (e) => {
    const { param } = e.currentTarget.dataset;
    if (param == 'passenger') {
      self.passengerGetMoreMyList();
    } else if (param == 'driver') {
      self.driverGetMoreMyList();
    }
  },
})