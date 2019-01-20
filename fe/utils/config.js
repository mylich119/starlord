/**
 * 配置文件
 */

const config = {
  host: 'https://api.pinche.mobi',
  storage_ticket: 'user_ticket',// 本地存储ticket的名称

  // qq地图key
  qq_map_key: 'SMBBZ-FP5CJ-V4OFT-FXMXR-QPEOJ-BOFIO',

  // 分享页面
  share_pages: [
    'pages/driverPublishInfo/driverPublishInfo',
    'pages/passengerPublishInfo/passengerPublishInfo'
  ],

  // 车辆信息
  car_colors: ['白色', '黑色', '银色', '灰色', '蓝色', '红色', '金色', '棕色', '其他色'],
  car_types: ['轿车', 'SUV', '商务车', '大巴', '皮卡'],

  // 发布信息
  driver_tags: [
    { value: 'driver_no_smoke', label: '无烟车' },
    { value: 'driver_last_mile', label: '接送到家' },
    { value: 'driver_goods', label: '可捎货' },
    { value: 'driver_need_drive', label: '会开车优先' },
    { value: 'driver_chat', label: '健谈优先' },
    { value: 'driver_highway', label: '全程高速' },
    { value: 'driver_pet', label: '可带宠物' },
    { value: 'driver_cooler', label: '空调开放' },
  ],
  passenger_tags: [
    { value: 'passenger_no_smoke', label: '不抽烟' },
    { value: 'passenger_last_mile', label: '要求接送到家' },
    { value: 'passenger_goods', label: '纯捎货' },
    { value: 'passenger_can_drive', label: '愿意换开车' },
    { value: 'passenger_chat', label: '健谈' },
    { value: 'passenger_luggage', label: '有大件行李' },
    { value: 'passenger_pet', label: '有宠物' },
    { value: 'passenger_no_carsickness', label: '不晕车' },
  ],
}

module.exports = config