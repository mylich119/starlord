<?php

class GroupTripDao extends CommonDao
{
    const TABLE_NUM = 1;

    protected $table = "";
    protected $fields = array(
        "id",
        "trip_id",
        "group_id",
        "top_time",
        "trip_begin_date",
        "trip_type",
        "status",
        "extend_json_info",
        "is_del",
        "created_time",
        "modified_time",
    );

    protected $primaryKey = 'id';
    protected $tablePrefix = "grouptrip_";
    protected $dbConfName = "default";

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

    public function getCountByGroupId($groupId, $date)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $sql = "select count(*) as total from " . $this->table . " where group_id = ? and trip_begin_date >= ? and is_del = ?";

        $query = $this->db->query($sql, array($groupId, $date, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

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

    public function getListByGroupIdAndDateWithTopTime($groupId, $date, $tripType)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $sql = "select * from " . $this->table . " where group_id = ? and trip_begin_date >= ? and trip_type = ? and is_del = ? and  top_time is not null order by top_time desc limit 500";

        $query = $this->db->query($sql, array($groupId, $date, $tripType, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        return $query->result_array();
    }

    public function getListByGroupIdAndDateWithoutTopTime($groupId, $date, $tripType)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $sql = "select * from " . $this->table . " where group_id = ? and trip_begin_date >= ? and trip_type = ? and is_del = ? and  top_time is null order by created_time desc limit 500";

        $query = $this->db->query($sql, array($groupId, $date, $tripType, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        return $query->result_array();
    }

    public function insertMulti($groupTrips)
    {
        if (empty($groupTrips) || !is_array($groupTrips)) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_NO_FILED], Status::DAO_INSERT_NO_FILED, var_export($this->db, true));
        }
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $currentTime = date("Y-M-d H:i:s", time());

        $insertFields = $this->fields;
        array_shift($insertFields);

        $bindParams = array();
        $insertValues = array();

        foreach ($groupTrips as $v) {
            $questionMarks = array();
            $v['created_time'] = $currentTime;
            $v['modified_time'] = $currentTime;
            $v['is_del'] = Config::RECORD_EXISTS;
            foreach ($insertFields as $field) {
                $questionMarks[] = '?';
                $bindParams[] = $v[$field];
            }
            $insertValues[] = "(" . implode(",", $questionMarks) . ")";
        }

        $sql = "insert into " . $this->table . " (" . implode(",", $insertFields) . ") values " . implode(",", $insertValues);
        $query = $this->db->query($sql, $bindParams);

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_FAIL], Status::DAO_INSERT_FAIL, var_export($this->db, true));
        }

        return true;
    }

    public function getOneByGroupIdAndTripId($groupId, $tripId)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where group_id = ? and trip_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($groupId, $tripId, Config::RECORD_EXISTS));

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

    public function updateByTripId($groupId, $tripId, $groupTrip)
    {
        if (empty($groupId) || empty($tripId) || !is_array($groupTrip) || count($groupTrip) == 0) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $groupTrip['modified_time'] = $currentTime;

        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $updateFields = array();
        $bindParams = array();
        foreach ($groupTrip as $k => $v) {
            $updateFields[] = $k . " = " . "?";
            $bindParams[] = $v;
        }
        $bindParams[] = $groupId;
        $bindParams[] = $tripId;
        $bindParams[] = Config::RECORD_EXISTS;
        $sql = "update " . $this->table . " set  " . implode(",", $updateFields) . " where group_id = ? and trip_id = ? and is_del = ?";

        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }


    public function deleteByTripId($tripId)
    {
        if (empty($tripId)) {
            throw new StatusException(Status::$message[Status::DAO_DELETE_FAIL], Status::DAO_DELETE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $sql = "update " . $this->table . " set  is_del = ? , modified_time = ?  where trip_id = ? ";
        $bindParams[] = Config::RECORD_DELETED;
        $bindParams[] = $currentTime;
        $bindParams[] = $tripId;

        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_DELETE_FAIL], Status::DAO_DELETE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }
}
