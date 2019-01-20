<?php

class DbTansactionHanlder
{
    protected static $aDbResources = array();

    public  static function begin($sDbConfName){
        $oDb = self::_getInstance($sDbConfName);
        $oDb->trans_begin();
    }

    public static function commit($sDbConfName){
        $oDb = self::_getInstance($sDbConfName);
        $oDb->trans_commit();
    }

    public static function rollBack($sDbConfName){
        $oDb = self::_getInstance($sDbConfName);
        $oDb->trans_rollback();
    }

    private static function _getInstance($sDbConfName){
        if (!isset(self::$aDbResources[$sDbConfName])) {
            $oCommonDao = new CommonDao();
            self::$aDbResources[$sDbConfName] = $oCommonDao->getConn($sDbConfName);
        }
        return self::$aDbResources[$sDbConfName];
    }
}
