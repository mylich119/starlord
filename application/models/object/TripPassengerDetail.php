<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class TripPassengerDetail
{
    public $beginDate;
    public $beginTime;
    public $startLocationName;
    public $startLocationAddress;
    public $startLocationPoint;
    public $endLocationName;
    public $endLocationAddress;
    public $endLocationPoint;
    public $priceEveryone;
    public $peopleNum;
    public $passengerNoSmoke;
    public $passengerLastMile;
    public $passengerGoods;
    public $passengerCanDrive;
    public $passengerChat;
    public $passengerLuggage;
    public $passengerPet;
    public $passengerNoCarsickness;
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
        $this->priceEveryone = empty($input["price_everyone"]) ? null : $input["price_everyone"];
        $this->peopleNum = empty($input["people_num"]) ? null : $input["people_num"];
        $this->passengerNoSmoke = empty($input["passenger_no_smoke"]) ? null : $input["passenger_no_smoke"];
        $this->passengerLastMile = empty($input["passenger_last_mile"]) ? null : $input["passenger_last_mile"];
        $this->passengerGoods = empty($input["passenger_goods"]) ? null : $input["passenger_goods"];
        $this->passengerCanDrive = empty($input["passenger_can_drive"]) ? null : $input["passenger_can_drive"];
        $this->passengerChat = empty($input["passenger_chat"]) ? null : $input["passenger_chat"];
        $this->passengerLuggage = empty($input["passenger_luggage"]) ? null : $input["passenger_luggage"];
        $this->passengerPet = empty($input["passenger_pet"]) ? null : $input["passenger_pet"];
        $this->passengerNoCarsickness = empty($input["passenger_no_carsickness"]) ? null : $input["passenger_no_carsickness"];
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
            "price_everyone" => $this->priceEveryone,
            "people_num" => $this->peopleNum,
            "passenger_no_smoke" => $this->passengerNoSmoke,
            "passenger_last_mile" => $this->passengerLastMile,
            "passenger_goods" => $this->passengerGoods,
            "passenger_can_drive" => $this->passengerCanDrive,
            "passenger_chat" => $this->passengerChat,
            "passenger_luggage" => $this->passengerLuggage,
            "passenger_pet" => $this->passengerPet,
            "passenger_no_carsickness" => $this->passengerNoCarsickness,
            "tips" => $this->tips,
        );
    }
}