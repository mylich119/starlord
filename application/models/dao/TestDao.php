<?php

class TestDao extends CI_Model
{
    const TABLE_NUM = 1000;

    protected $table = "";
    protected $fields = array(
        'id',
        'route',
        'start_loc',
        'end_loc',
    );

    protected $primaryKey = 'id';
    protected $db = null;
    protected $dbConfName = "default";
    protected $tablePrefix = "test_";
    protected static $dbResources = array();

    public function __construct()
    {
        parent::__construct();
    }

    public function getConn($dbConfName = null)
    {
        if ($dbConfName == null) {
            $dbConfName = $this->dbConfName;
        }
        if (!isset(self::$dbResources[$dbConfName])) {
            self::$dbResources[$dbConfName] = $this->load->database($dbConfName, true);
        }
        return self::$dbResources[$dbConfName];
    }

    public function reConn()
    {
        $this->db->reconnect();
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
            return $this->tablePrefix . (string)($shardKey % self::TABLE_NUM);
        }
    }

    public function getById($id)
    {
        $this->table = $this->_getShardedTable($id);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table . "where id = ?";

        $query = $this->db->query($sql, array($id));

        return $query->result();
    }

    public function getAll()
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select * from " . $this->table;

        $query = $this->db->query($sql, array());

        return $query->result();
    }


    public function add($testArr)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);

        $insertFields = $this->fields;
        array_shift($insertFields);
        $sql = "insert into " . $this->table ." (" . implode(",", $insertFields) . ") values(?, ?, ?)";
        $query = $this->db->query($sql, $testArr);

        return true;
    }


    public function search($target_start, $target_end, $count)
    {
        $this->table = $this->_getShardedTable(0);
        $this->db = $this->getConn($this->dbConfName);
        $sql = "select route, start_loc <-> end_loc total, (start_loc <-> point ?) + (end_loc <-> point ?) sum_distance from " . $this->table . " order by  start_loc <-> point ? limit " . $count;

        $query = $this->db->query($sql, array($target_start, $target_end, $target_start));

        return $query->result_array();
    }


}
