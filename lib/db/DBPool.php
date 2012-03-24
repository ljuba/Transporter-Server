<?php
/**
 *
 * Maintains pool of DB objects
 *
 * @author Thejo
 */
final class DBPool {
    private static $_objectPool = array();

    public static function setInstance(DB $dbObj) {
        self::$_objectPool[$dbObj->getInstanceName()] = $dbObj;
    }

        /**
         *
         * @param String $instanceName
         * @return DB
         */
    public static function getInstance($instanceName = '') {
        $dbObj = null;

        //If instance name is not available, pass the first instance
        //from the pool
        if('' == $instanceName) {
            $values = array_values(self::$_objectPool);
            $dbObj = $values[0];
        } else {
            $dbObj = self::$_objectPool[$instanceName];
        }

        if($dbObj instanceof DB) {
            return $dbObj;
        } else {
            throw new DBException("DB object $instanceName not found!");
        }
    }
}