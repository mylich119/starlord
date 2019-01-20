<?php

class LockRedis extends CommonRedis
{
    const LOCK_VALUE = 1;
    const LOCK_EXPIRE_SECONDS = 10;
    const LOCK_WAIT_COUNT = 5;
    const LOCK_PREFIX = 'STARLORD_LOCK_';

    public function __construct(){
        parent::__construct();
    }

    public function lockK($sKey){
        $sLockKey = self::LOCK_PREFIX . $sKey;
        $iLockWaitCount = self::LOCK_WAIT_COUNT;
        while(!$this->_lock($sLockKey)){
            sleep(1);
            $iLockWaitCount --;
            if($iLockWaitCount == 0){
                throw new StatusException(Status::$message[Status::REDIS_LOCK_WAIT_TIMEOUT], Status::REDIS_LOCK_WAIT_TIMEOUT, $sLockKey);
            }
        }
    }

    public function unLockK($sKey){
        $sLockKey = self::LOCK_PREFIX . $sKey;
        return $this->delete($sLockKey);
    }

    private function _lock($sKey){

        $bRet = $this->setNx($sKey, self::LOCK_VALUE);
        if($bRet){
            $this->expire($sKey,self::LOCK_EXPIRE_SECONDS );
            return true;
        }else{
            return false;
        }
    }
}
