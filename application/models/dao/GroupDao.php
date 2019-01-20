<?php

class GroupDao extends CommonDao
{
    const TABLE_NUM = 1;

    protected $table = "";
    protected $fields = array(
        "id",
        "group_id",
        "wx_gid",
        "member_num",
        "trip_num",
        "owner_user_id",
        "owner_avatar_url",
        "owner_nick_name",
        "owner_wx_id",
        "notice",
        "status",
        "is_del",
        "created_time",
        "modified_time",
    );

    protected $primaryKey = 'id';
    protected $tablePrefix = "group_";
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

    public function getAllGroupIds()
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select group_id  from " . $this->table . " where  is_del = ?";

        $query = $this->db->query($sql, array(Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        return $query->result_array();
    }

    public function getCountOfAll()
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select count(*) as total from " . $this->table . " where  is_del = ?";

        $query = $this->db->query($sql, array(Config::RECORD_EXISTS));

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

    public function insertOne($groupId, $group)
    {
        if (empty($groupId) || !is_array($group) || count($group) == 0) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_NO_FILED], Status::DAO_INSERT_NO_FILED, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $group['created_time'] = $currentTime;
        $group['modified_time'] = $currentTime;
        $group['is_del'] = Config::RECORD_EXISTS;

        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $questionMarks = array();
        $bindParams = array();
        foreach ($group as $k => $v) {
            $insertFields[] = $k;
            $bindParams[] = $v;
            $questionMarks[] = '?';
        }
        $sql = "insert into " . $this->table . " (" . implode(",", $insertFields) . ") values(" . implode(",", $questionMarks) . ")";
        $query = $this->db->query($sql, $bindParams);

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_FAIL], Status::DAO_INSERT_FAIL, var_export($this->db, true));
        }

        return $group;
    }

    public function getOneByWxGid($wxGid)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where wx_gid = ? and is_del = ?";

        $query = $this->db->query($sql, array($wxGid, Config::RECORD_EXISTS));

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

    public function getOneBGroupId($groupId)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where group_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($groupId, Config::RECORD_EXISTS));

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

    public function getListByGroupIds($groupIds)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $questionMarks = array();
        $bindParams = array();
        foreach ($groupIds as $k => $v) {
            $bindParams[] = $v;
            $questionMarks[] = '?';
        }
        $bindParams[] = Config::RECORD_EXISTS;
        $sql = "select * from " . $this->table . " where group_id in (" . implode(",", $questionMarks) . ") and is_del = ? ";

        $query = $this->db->query($sql, $bindParams);

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        return $query->result_array();
    }

    public function updateByGroupId($groupId, $group)
    {
        if (empty($groupId) || !is_array($group) || count($group) == 0) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $group['modified_time'] = $currentTime;

        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $updateFields = array();
        $bindParams = array();
        foreach ($group as $k => $v) {
            $updateFields[] = $k . " = " . "?";
            $bindParams[] = $v;
        }
        $bindParams[] = $groupId;
        $bindParams[] = Config::RECORD_EXISTS;
        $sql = "update " . $this->table . " set  " . implode(",", $updateFields) . " where group_id = ? and is_del = ?";

        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_UPDATE_FAIL], Status::DAO_UPDATE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }
}
