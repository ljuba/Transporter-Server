<?php
class Logger {
    /**
     * Singleton instance
     *
     * @var Object of db class
     */
    private static $_instance = null;

    public static $logFileLocation;

    //Log levels
    const INFO = 'INFO';
    const WARN = 'WARN';
    const CRITICAL = 'CRIT';

    /**
     * Private constructor
     *
     * @return None
     */
    private function __construct() {}

    /**
     * Get an instance of the Logger object
     *
     * @return Object of this class
     */
    public static function getInstance() {
        if(!self::$_instance) {
            $className = __CLASS__;
            self::$_instance = new $className();
        }

        return self::$_instance;
    }

    public static function configure() {
        self::$logFileLocation = Configuration::getLogFileLocation();
    }

    /**
     * Log to file. The <code>Configuration</code> class must be initialized
     * before calling this method
     *
     * @param $str - Thes tring to be logged
     * @param $level - The log level
     * @param $package
     * @return unknown_type
     */
    public function log($str, $level, $package) {
        $filepath = self::$logFileLocation;
        $log = date('Y-m-d H:i:s')."\t".$level."\t".'['.$package.']'."\t".$str."\n";
        $rc = file_put_contents($filepath, $log, FILE_APPEND);
    }
}
