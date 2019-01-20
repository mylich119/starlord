<?php

class TripDriverDao extends TripDao
{
    public function __construct()
    {
        parent::__construct();
        $this->tablePrefix = "tripdriver_";
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
            "route",
            "price_everyone",
            "price_total",
            "seat_num",
            "driver_no_smoke",
            "driver_last_mile",
            "driver_goods",
            "driver_need_drive",
            "driver_chat",
            "driver_highway",
            "driver_pet",
            "driver_cooler",
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
