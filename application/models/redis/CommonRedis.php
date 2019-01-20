<?php

class CommonRedis extends CI_Model
{
    protected static $aRedisResource = array();
    protected $sRedisConfName = 'default';
    protected $aHost = null;
    
    public function __construct()
    {
        parent::__construct();
    }

    public function getConn($sRedisConfName = null)
    {
        if ($sRedisConfName == null) {
            $sRedisConfName = $this->sRedisConfName;
        } else {
            $this->sRedisConfName = $sRedisConfName;
        }

        $aRedisConnection = $this->config->item($sRedisConfName);
        $this->aHost = $this->shardHost($aRedisConnection);

        if (!isset(self::$aRedisResource[$sRedisConfName])) {
            $oRedis = new Redis();
            $bRet = $oRedis->pconnect($this->aHost['host'], $this->aHost['port'], $this->aHost['timeout']);
            if(!$bRet){
                throw new StatusException(Status::$message[Status::REDIS_CONNECT_ERROR], Status::REDIS_CONNECT_ERROR,json_encode($this->aHost));
            }
            self::$aRedisResource[$sRedisConfName] = $oRedis;
        } else {
            $oRedis = self::$aRedisResource[$sRedisConfName];
            try {
                $oRedis->ping();
            } catch (RedisException $e) {
                $oRedis = new Redis();
                $bRet = $oRedis->pconnect($this->aHost['host'], $this->aHost['port'], $this->aHost['timeout']);
                if(!$bRet){
                    throw new StatusException(Status::$message[Status::REDIS_CONNECT_ERROR], Status::REDIS_CONNECT_ERROR,json_encode($this->aHost));
                }
                self::$aRedisResource[$sRedisConfName] = $oRedis;
            }
        }

        return self::$aRedisResource[$sRedisConfName];
    }

    private function shardHost($aRedisConnection){

        return $aRedisConnection[array_rand($aRedisConnection)];
    }

    public function __call($sMethodName, $aArgumentList){
        $fStartTime = microtime(true);

        $oRedis = $this->getConn();

        /*if (!method_exists($oRedis, $sMethodName)) {
            throw new StatusException(Status::$message[Status::REDIS_HAS_NO_METHOD], Status::REDIS_HAS_NO_METHOD, $sMethodName);
        }*/
        try {
            $aResult = call_user_func_array(array($oRedis, $sMethodName), $aArgumentList);

            $fEndTime = microtime(true);
            $fProcTime = ($fEndTime - $fStartTime) * 1000;

            return $aResult;
        } catch (RedisException $e) {
            $fEndTime = microtime(true);
            $fProcTime = ($fEndTime - $fStartTime) * 1000;


            throw new StatusException(Status::$message[Status::REDIS_EXECUTE_ERROR], Status::REDIS_EXECUTE_ERROR, $e->getMessage());
        }
    }
}
