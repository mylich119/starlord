<?php

class SearchService extends CI_Model
{
    public function __construct()
    {
        parent::__construct();

    }

    //缓存
    public function search($tripType, $beginDate, $beginTime, $targetStart, $targetEnd)
    {
        $cacheKey = 'SearchService_search' . $tripType . $beginDate . $beginTime . $targetStart . $targetEnd;
        //缓存
        $this->load->model('redis/CacheRedis');
        $resTrips = $this->CacheRedis->getK($cacheKey);
        if ($resTrips != false) {
            return $resTrips;
        }

        $startAndEndRoundTrips = null;
        if ($tripType == Config::TRIP_TYPE_DRIVER) {
            $this->load->model('dao/TripDriverDao');
            $startAndEndRoundTrips = $this->TripDriverDao->search($beginDate, $beginTime, $targetStart, $targetEnd);
        } else {
            $this->load->model('dao/TripPassengerDao');
            $startAndEndRoundTrips = $this->TripPassengerDao->search($beginDate, $beginTime, $targetStart, $targetEnd);
        }


        if (empty($startAndEndRoundTrips)) {
            return array();
        }

        $resTrips = array();
        $sortKeys = array();

        foreach ($startAndEndRoundTrips as $trip) {
            $ratio = $trip['sum_distance'] / $trip['total_distance'];
            $score = (0.7 - $ratio) / 0.7 * 100;
            if ($score < 0) {
                continue;
            }
            unset($trip['sum_distance']);
            unset($trip['total_distance']);
            $trip['score'] = intval($score);

            $sortKeys[] = $score;
            $resTrips[] = $trip;
        }

        array_multisort($sortKeys, SORT_DESC, SORT_NUMERIC, $resTrips);


        //设置缓存
        $this->CacheRedis->setK($cacheKey, $resTrips);

        return $resTrips;
    }


}
