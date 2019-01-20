<?php

class UserDao extends CommonDao
{
    const TABLE_NUM = 1;

    protected $table = "";
    protected $fields = array(
        "id",
        "user_id",
        "phone",
        "wx_open_id",
        "wx_union_id",
        "wx_session_key",
        "ticket",
        "nick_name",
        "gender",
        "city",
        "province",
        "country",
        "avatar_url",
        "car_plate",
        "car_brand",
        "car_model",
        "car_color",
        "car_type",
        "is_valid",
        "audit_status",
        "need_publish_guide",
        "show_agreement",
        "status",
        "is_del",
        "created_time",
        "modified_time",
    );

    protected $primaryKey = 'id';
    protected $tablePrefix = "user_";
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
            if (self::TABLE_NUM == 1) {
                return $this->tablePrefix . "0";
            }
            //return $this->tablePrefix . (string)($shardKey % self::TABLE_NUM);
            return $this->tablePrefix . '0';
        }
    }

    public function getOneByOpenId($openId)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where wx_open_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($openId, Config::RECORD_EXISTS));

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

    public function getOneByTicket($ticket)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where ticket = ? and is_del = ?";

        $query = $this->db->query($sql, array($ticket, Config::RECORD_EXISTS));

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

    public function getOneByUserId($userId)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where user_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($userId, Config::RECORD_EXISTS));

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


    public function insertOne($user)
    {
        $currentTime = date("Y-M-d H:i:s", time());

        $user['created_time'] = $currentTime;
        $user['modified_time'] = $currentTime;
        $user['is_del'] = Config::RECORD_EXISTS;

        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        if (empty($user) || !is_array($user) || count($user) == 0) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_NO_FILED], Status::DAO_INSERT_NO_FILED, var_export($this->db, true));
        }

        $questionMarks = array();
        $bindParams = array();
        foreach ($user as $k => $v) {
            $insertFields[] = $k;
            $bindParams[] = $v;
            $questionMarks[] = '?';
        }
        $sql = "insert into " . $this->table . " (" . implode(",", $insertFields) . ") values(" . implode(",", $questionMarks) . ")";
        $query = $this->db->query($sql, $bindParams);

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_FAIL], Status::DAO_INSERT_FAIL, var_export($this->db, true));
        }

        return true;
    }

    public function updateOneByUserId($userId, $user)
    {
        if (empty($userId) || empty($user) || !is_array($user) || count($user) == 0) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $user['modified_time'] = $currentTime;

        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $updateFields = array();
        $bindParams = array();
        foreach ($user as $k => $v) {
            $updateFields[] = $k . " = " . "?";
            $bindParams[] = $v;
        }
        $bindParams[] = $userId;
        $bindParams[] = Config::RECORD_EXISTS;
        $sql = "update " . $this->table . " set  " . implode(",", $updateFields) . " where user_id = ? and is_del = ?";
        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }
}
