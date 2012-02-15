<?php
/**
 * Description of Configuration
 *
 * @author thejo
 */
class Configuration {

    /**
     * An array of application configuration values
     * @var Array
     */
    private static $appConfig = array();

    public static function getAppConfig() {
        return self::$appConfig;
    }

    public static function setAppConfig(array $config) {
        self::$appConfig = $config;
    }

    public static function getBasePath() {
        if(! isset(self::$appConfig['base_path']) ) {
            throw new BaseException("Mandatory configuration value missing (base path)", 100);
        }

        return self::$appConfig['base_path'];
    }

    public static function getLogFileLocation() {
        if(! isset(self::$appConfig['log_file']) ) {
            throw new BaseException("Mandatory configuration value missing (Log file)", 100);
        }

        return self::$appConfig['log_file'];
    }

    public static function getEnvironment() {
        if(! isset(self::$appConfig['environment']) ) {
                throw new BaseException("Mandatory configuration value missing (Environment)", 105);
        }

        return self::$appConfig['environment'];
    }
}
?>
