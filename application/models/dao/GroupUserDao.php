<?php

class GroupUserDao extends CommonDao
{
    const TABLE_NUM = 1;

    protected $table = "";
    protected $fields = array(
        "id",
        "user_id",
        "group_id",
        "status",
        "is_del",
        "created_time",
        "modified_time",
    );

    protected $primaryKey = 'id';
    protected $tablePrefix = "groupuser_";
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

    public function getCountByGroupId($groupId)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select count(*) as total from " . $this->table . " where group_id = ? and is_del = ?";

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


    public function getOneByGroupIdAndUserId($userId, $groupId)
    {
        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where user_id = ? and group_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($userId, $groupId, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        } else if ($query->num_rows() == 0) {
            return array();
        } else if ($query->num_rows() == 1) {
            return $query->row_array();
        } else if ($query->num_rows() > 1) {
            throw new StatusException(Status::$message[Status::DAO_MORE_THAN_ONE_RECORD], Status::DAO_MORE_THAN_ONE_RECORD, var_export($this->db, true));
        }

        return $query->row_array();
    }

    public function getGroupsByUserId($userId)
    {
        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . " where user_id = ? and is_del = ?";

        $query = $this->db->query($sql, array($userId, Config::RECORD_EXISTS));

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_FETCH_FAIL], Status::DAO_FETCH_FAIL, var_export($this->db, true));
        }

        return $query->result_array();
    }

    public function insertOne($userId, $groupId, $wxGid)
    {
        if (empty($userId) || empty($groupId)) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_NO_FILED], Status::DAO_INSERT_NO_FILED, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $groupUser = array();
        $groupUser['user_id'] = $userId;
        $groupUser['group_id'] = $groupId;
        $groupUser['wx_gid'] = $wxGid;
        $groupUser['status'] = Config::GROUP_USER_STATUS_DEFAULT;
        $groupUser['is_del'] = Config::RECORD_EXISTS;
        $groupUser['created_time'] = $currentTime;
        $groupUser['modified_time'] = $currentTime;

        $this->table = $this->_getShardedTable($groupId);
        $this->db = $this->getConn($this->dbConfName);

        $questionMarks = array();
        $bindParams = array();
        foreach ($groupUser as $k => $v) {
            $insertFields[] = $k;
            $bindParams[] = $v;
            $questionMarks[] = '?';
        }
        $sql = "insert into " . $this->table . " (" . implode(",", $insertFields) . ") values(" . implode(",", $questionMarks) . ")";
        $query = $this->db->query($sql, $bindParams);

        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_INSERT_FAIL], Status::DAO_INSERT_FAIL, var_export($this->db, true));
        }

        return $groupUser;
    }

    public function deleteOne($userId, $groupId)
    {
        if (empty($userId) || empty($groupId)) {
            throw new StatusException(Status::$message[Status::DAO_DELETE_FAIL], Status::DAO_DELETE_FAIL, var_export($this->db, true));
        }

        $currentTime = date("Y-M-d H:i:s", time());

        $this->table = $this->_getShardedTable($userId);
        $this->db = $this->getConn($this->dbConfName);

        $bindParams[] = Config::RECORD_DELETED;
        $bindParams[] = $currentTime;
        $bindParams[] = $userId;
        $bindParams[] = $groupId;
        $sql = "update " . $this->table . " set  is_del = ? , modified_time = ?  where user_id = ? and group_id = ?";

        $query = $this->db->query($sql, $bindParams);
        if (!$query) {
            throw new StatusException(Status::$message[Status::DAO_DELETE_FAIL], Status::DAO_DELETE_FAIL, var_export($this->db, true));
        }

        return $this->db->affected_rows();
    }
}
