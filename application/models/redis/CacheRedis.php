<?php

class CacheRedis extends CommonRedis
{
    const CACHE_EXPIRE_SECONDS = 60;
    const CACHE_PREFIX = 'STARLORD_CACHE_';
    const OPEN_CACHE = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function getK($sKey)
    {
        $sCacheKey = self::CACHE_PREFIX . $sKey;
        if (self::OPEN_CACHE) {
            return unserialize($this->get($sCacheKey));
        } else {
            return null;
        }
    }

    public function delK($sKey)
    {
        $sCacheKey = self::CACHE_PREFIX . $sKey;
        return $this->delete($sCacheKey);
    }

    public function setK($sKey, $sValue)
    {
        $sCacheKey = self::CACHE_PREFIX . $sKey;
        return $this->setEx($sCacheKey, self::CACHE_EXPIRE_SECONDS, serialize($sValue));
    }

}
