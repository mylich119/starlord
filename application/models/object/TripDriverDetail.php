<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class TripDriverDetail
{
    public $beginDate;
    public $beginTime;
    public $startLocationName;
    public $startLocationAddress;
    public $startLocationPoint;
    public $endLocationName;
    public $endLocationAddress;
    public $endLocationPoint;
    public $route;
    public $priceEveryone;
    public $priceTotal;
    public $seatNum;
    public $driverNoSmoke;
    public $driverLastMile;
    public $driverGoods;
    public $driverNeedDrive;
    public $driverChat;
    public $driverHighway;
    public $driverPet;
    public $driverCooler;
    public $tips;

    public function __construct($input)
    {
        if (isset($input["is_everyday"]) && $input["is_everyday"] == 1) {
            $this->beginDate = Config::EVERYDAY_DATE;
        } else {
            $this->beginDate = $input["begin_date"];
        }

        $tmp = str_replace('[','(',  $input['start_location_point']);
        $input['start_location_point'] = str_replace(']', ')', $tmp);
        $tmp = str_replace('[','(',  $input['end_location_point']);
        $input['end_location_point'] = str_replace(']', ')', $tmp);

        $this->beginTime = $input["begin_time"];
        $this->startLocationName = $input["start_location_name"];
        $this->startLocationAddress = $input["start_location_address"];
        $this->startLocationPoint = $input["start_location_point"];
        $this->endLocationName = $input["end_location_name"];
        $this->endLocationAddress = $input["end_location_address"];
        $this->endLocationPoint = $input["end_location_point"];

        //以下是选填
        $this->route = empty($input["route"]) ? null : $input["route"];
        $this->priceEveryone = empty($input["price_everyone"]) ? null : $input["price_everyone"];
        $this->priceTotal = empty($input["price_total"]) ? null : $input["price_total"];
        $this->seatNum = empty($input["seat_num"]) ? null : $input["seat_num"];
        $this->driverNoSmoke = empty($input["driver_no_smoke"]) ? null : $input["driver_no_smoke"];
        $this->driverLastMile = empty($input["driver_last_mile"]) ? null : $input["driver_last_mile"];
        $this->driverGoods = empty($input["driver_goods"]) ? null : $input["driver_goods"];
        $this->driverNeedDrive = empty($input["driver_need_drive"]) ? null : $input["driver_need_drive"];
        $this->driverChat = empty($input["driver_chat"]) ? null : $input["driver_chat"];
        $this->driverHighway = empty($input["driver_highway"]) ? null : $input["driver_highway"];
        $this->driverPet = empty($input["driver_pet"]) ? null : $input["driver_pet"];
        $this->driverCooler = empty($input["driver_cooler"]) ? null : $input["driver_cooler"];
        $this->tips = empty($input["tips"]) ? null : $input["tips"];
    }

    public function getTripArray()
    {
        if (empty($this->beginDate)
            || empty($this->beginTime)
            || empty($this->startLocationName)
            || empty($this->startLocationAddress)
            || empty($this->startLocationPoint)
            || empty($this->endLocationName)
            || empty($this->endLocationAddress)
            || empty($this->endLocationPoint)) {
            throw new StatusException(Status::$message[Status::TRIP_PARAMS_INVALID], Status::TRIP_PARAMS_INVALID);
        }

        return array(
            "begin_date" => $this->beginDate,
            "begin_time" => $this->beginTime,
            "start_location_name" => $this->startLocationName,
            "start_location_address" => $this->startLocationAddress,
            "start_location_point" => $this->startLocationPoint,
            "end_location_name" => $this->endLocationName,
            "end_location_address" => $this->endLocationAddress,
            "end_location_point" => $this->endLocationPoint,
            "route" => $this->route,
            "price_everyone" => $this->priceEveryone,
            "price_total" => $this->priceTotal,
            "seat_num" => $this->seatNum,
            "driver_no_smoke" => $this->driverNoSmoke,
            "driver_last_mile" => $this->driverLastMile,
            "driver_goods" => $this->driverGoods,
            "driver_need_drive" => $this->driverNeedDrive,
            "driver_chat" => $this->driverChat,
            "driver_highway" => $this->driverHighway,
            "driver_pet" => $this->driverPet,
            "driver_cooler" => $this->driverCooler,
            "tips" => $this->tips,
        );
    }

}


