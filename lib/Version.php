<?php
class Version {

    const PACKAGE = "VERSION";

    public static function getVersions() {
        $dbObj = DBPool::getInstance();
        $versions = array();

        $versionsDb = $dbObj->get_results("SELECT * FROM version ORDER BY id");

        if (null != $versionsDb) {
            $versions = $versionsDb;
        }

        return $versions;
    }

    /**
     * Deletes old versions that were never active
     */
    public static function deleteOldVersions() {
        $config = Configuration::getAppConfig();
        $dbObj = DBPool::getInstance();

        $maxVersionCount = $config['max version count'];
        $minVersionCount = $config['min version count'];

        if ($maxVersionCount <=
                $dbObj->get_var("SELECT COUNT(1) FROM version WHERE active=false AND was_active=false")) {

            $oldVersionsArray = array();
            $oldVersions = $dbObj->get_results("SELECT id FROM version
                            WHERE active=false AND was_active=false
                            ORDER by id
                            LIMIT ". ($maxVersionCount - $minVersionCount));
            foreach ($oldVersions as $o) {
                $oldVersionsArray[] = $o->id;
            }

            if (count($oldVersionsArray) > 0) {
                $query = "DELETE FROM version WHERE id IN (".
                            implode(",", $oldVersionsArray) .")";
                $dbObj->query($query);

                //Delete data from the disk
                foreach ($oldVersionsArray as $version) {
                    $dataDirPath = Configuration::getBasePath() . "www/data/".Util::XML_FILE."/$version";
                    $imageDirPath = Configuration::getBasePath() . "www/data/".Util::IMAGE_FILE."/$version";
                    $changeDirPath = Configuration::getBasePath() . "www/data/".Util::CHANGE_FILE."/$version";

                    Util::deleteAll($dataDirPath);
                    Util::deleteAll($imageDirPath);
                    Util::deleteAll($changeDirPath);
                }

                $logger = Logger::getInstance();
                $logStr = "Deleted versions ". implode(",", $oldVersionsArray);
                $logger->log($logStr, Logger::INFO, Version::PACKAGE);
            }
        }
    }

    public static function setAsActive($version) {
        $dbObj = DBPool::getInstance();

        //Check if the version number is valid
        $dbObj->bindParams(array($version));
        $versionDetails = $dbObj->get_row("SELECT * FROM version WHERE id=?");
        if (null != $versionDetails) {
            if ($versionDetails->images_generated != true) {
                throw new Exception("Map images have to be generated before
                    activating a version.");
            }

            $dbObj->start_transaction();
            $dbObj->query("UPDATE version SET active=false");
            $dbObj->bindParams(array($version));
            $dbObj->query("UPDATE version SET active=true, was_active=true WHERE id=?");
            $dbObj->end_transaction();
        } else {
            throw new Exception("Invalid version number");
        }
    }

    /**
     *
     * @return Boolean - true if changes are present in the current version
     */
    public static function changesPresentInCurrentVersion() {
        $dbObj = DBPool::getInstance();

        $query = "SELECT changes_present FROM version WHERE id = " .
                    TableUpdate::getVersion();
        if (true == $dbObj->get_var($query)) {
            return true;
        } else {
            return false;
        }
    }
}
