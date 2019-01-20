<?php

class TripPassengerDao extends TripDao
{
    public function __construct()
    {
        parent::__construct();
        $this->tablePrefix = "trippassenger_";
        $this->fields = array(
            "id",
            "trip_id",
            "user_id",
            "user_info",
            "group_info",
            "begin_date",
            "begin_time",
            "start_location_name",
            "start_location_address",
            "start_location_point",
            "end_location_name",
            "end_location_address",
            "end_location_point",
            "price_everyone",
            "people_num",
            "passenger_no_smoke",
            "passenger_last_mile",
            "passenger_goods",
            "passenger_can_drive",
            "passenger_chat",
            "passenger_luggage",
            "passenger_pet",
            "passenger_no_carsickness",
            "tips",
            "share_img_url",
            "lbs_route_info",
            "status",
            "is_del",
            "created_time",
            "modified_time",
        );
    }
}
