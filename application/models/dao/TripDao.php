<?php

class TripDao extends CommonDao
{
    const TABLE_NUM = 1;

    protected $table = "";
    protected $fields = array();

    protected $primaryKey = 'id';
    protected $dbConfName = "default";
    protected $tablePrefix = "";

    public function __construct()
    {
        parent::__construct();
    }

    protected function _getShardedTable($shardKey)
    {
        $this->db = $this->getConn($this->dbConfName);

        if (!isset($shardKey)) {
            throw new StatusException(Status::$message[Status::DAO_HAS_NO_SHARD_KEY], Status::DAO_HAS_NO_SHARD_KEY, var_export($this->oCommonDb, true));
        }
        if (ENVIRONMENT == 'development') {
            return $this->tablePrefix . '0';
        } else {
            //return $this->tablePrefix . (string)($shardKey % self::TABLE_NUM);
            return $this->tablePrefix . '0';
        }
    }

    public function getCountOfAll()
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $currentDate = date('Y-m-d');
        $sql = "select count(*) as total from " . $this->table . " where begin_date >= ? and status = ? and is_del = ?";

        $query = $this->db->query($sql, array($currentDate, Config::TRIP_STATUS_NORMAL, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        } else if ($query->num_rows() == 0) {
            return array();
        } else if ($query->num_rows() == 1) {
            return $query->row_array();
        } else if ($query->num_rows() > 1) {
            throw new StatusException(Status::$message[Status::DAO_MORE_THAN_ONE_RECORD], Status::DAO_MORE_THAN_ONE_RECORD, var_export($this->db, true));
        }
    }

    public function getOneByTripId($userId, $tripId)
    {
        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where user_id = ? and trip_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($userId, $tripId, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        } else if ($query->num_rows() == 0) {
            return array();
        } else if ($query->num_rows() == 1) {
            return $query->row_array();
        } else if ($query->num_rows() > 1) {
            throw new StatusException(Status::$message[Status::DAO_MORE_THAN_ONE_RECORD], Status::DAO_MORE_THAN_ONE_RECORD, var_export($this->db, true));
        }
    }

    public function insertOne($userId, $trip)
    {
        $currentTime = date("Y-M-d H:i:s", time());

        $trip['created_time'] = $currentTime;
        $trip['modified_time'] = $currentTime;
        $trip['is_del'] = Config::RECORD_EXISTS;

        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);

        if (empty($trip) || !is_array($trip) || count($trip) == 0) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_NO_FILED], Status::DAO_INSERT_NO_FILED, var_export($this->db, true));
        }

        $questionMarks = array();
        $bindParams = array();
        foreach ($trip as $k => $v) {
            $insertFields[] = $k;
            $bindParams[] = $v;
            $questionMarks[] = '?';
        }
        $sql = "insert into " . $this->table . " (" . implode(",", $insertFields) . ") values(" . implode(",", $questionMarks) . ")";
        $query = $this->db->query($sql, $bindParams);

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_FAIL], Status::DAO_INSERT_FAIL, var_export($this->db, true));
        }

        return $trip;
    }

    public function updateByTripIdAndStatus($userId, $tripId, $status, $trip)
    {
        if (empty($userId) || empty($trip) || !is_array($trip) || count($trip) == 0) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $trip['modified_time'] = $currentTime;

        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);

        $updateFields = array();
        $bindParams = array();
        foreach ($trip as $k => $v) {
            $updateFields[] = $k . " = " . "?";
            $bindParams[] = $v;
        }
        $bindParams[] = $userId;
        $bindParams[] = $tripId;
        $bindParams[] = Config::RECORD_EXISTS;
        $bindParams[] = $status;
        $sql = "update " . $this->table . " set  " . implode(",", $updateFields) . " where user_id = ? and trip_id = ? and is_del = ? and status = ?";

        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }

    public function deleteOne($userId, $tripId)
    {
        if (empty($userId) || empty($tripId)) {
            throw new StatusException(Status::$message[Status::DAO_DELETE_FAIL], Status::DAO_DELETE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);

        $bindParams[] = Config::RECORD_DELETED;
        $bindParams[] = $currentTime;
        $bindParams[] = $userId;
        $bindParams[] = $tripId;
        $sql = "update " . $this->table . " set  is_del = ? , modified_time = ?  where user_id = ? and trip_id = ?";

        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_DELETE_FAIL], Status::DAO_DELETE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }

    public function getListByUserIdAndStatusArr($userId, $statusArr)
    {
        if (empty($userId) || empty($statusArr) || !is_array($statusArr)) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }
        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);

        $questionMarks = array();
        $bindParams = array();
        $bindParams[] = $userId;
        $bindParams[] = Config::RECORD_EXISTS;
        foreach ($statusArr as $k => $v) {
            $bindParams[] = $v;
            $questionMarks[] = '?';
        }

        $sql = "select * from " . $this->table . " where user_id = ? and is_del = ? and status in (" . implode(",", $questionMarks) . ")";

        $query = $this->db->query($sql, $bindParams);


        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        return $query->result_array();
    }

    public function search($beginDate, $beginTime, $targetStart, $targetEnd)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $startTime = null;
        $endTime = null;

        if (strtotime($beginTime) >= strtotime("18:00:00")) {
            $startTime = date('H:i:s', strtotime($beginTime . " - 6 hours"));
            $endTime = "23:59:59";
        } else if (strtotime($beginTime) < strtotime("06:00:00")) {
            $startTime = "00:00:00";
            $endTime = date('H:i:s', strtotime($beginTime . " + 6 hours"));
        } else {
            $startTime = date('H:i:s', strtotime($beginTime . " - 6 hours"));
            $endTime = date('H:i:s', strtotime($beginTime . " + 6 hours"));
        }

        $sqlStart = "select * , start_location_point <-> end_location_point total_distance, (start_location_point <-> point ?) + (end_location_point <-> point ?) sum_distance from " . $this->table . " where (begin_date = ? or begin_date = ?) and begin_time >= ? and begin_time <= ? and is_del = ? and status = ? order by  start_location_point <-> point ? limit ?";
        $queryStart = $this->db->query($sqlStart, array($targetStart, $targetEnd, $beginDate, Config::EVERYDAY_DATE, $startTime, $endTime, Config::RECORD_EXISTS, Config::TRIP_STATUS_NORMAL, $targetStart, 100));
        if (!$queryStart) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        $sqlEnd = "select * , start_location_point <-> end_location_point total_distance, (start_location_point <-> point ?) + (end_location_point <-> point ?) sum_distance from " . $this->table . " where (begin_date = ? or begin_date = ?) and begin_time >= ? and begin_time <= ? and is_del = ? and status = ? order by  end_location_point <-> point ? limit ?";
        $queryEnd = $this->db->query($sqlEnd, array($targetStart, $targetEnd, $beginDate, Config::EVERYDAY_DATE, $startTime, $endTime, Config::RECORD_EXISTS, Config::TRIP_STATUS_NORMAL, $targetEnd, 100));
        if (!$queryEnd) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }


        $tripsStart = $queryStart->result_array();
        $tripsEnd = $queryEnd->result_array();
        if (empty($tripsStart)) {
            return $tripsEnd;
        }

        if (empty($tripsEnd)) {
            return $tripsStart;
        }

        //$tripsStart取出所有trip_id,$tripEnd中如果有trip_id重复的就忽略，剩余合并进入$tripsStart
        $tripIdMap = array();
        foreach ($tripsStart as $tripStart) {
            $tripIdMap[$tripStart['trip_id']] = 1;
        }

        foreach ($tripsEnd as $tripEnd) {
            if (isset($tripIdMap[$tripEnd['trip_id']])) {
                continue;
            } else {
                $tripsStart[] = $tripEnd;
            }
        }

        return $tripsStart;
    }

}
