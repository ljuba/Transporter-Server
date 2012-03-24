<?php
/**
 * Description of TableUpdate
 *
 * @author thejo
 */
abstract class TableUpdate {
    protected $table;
    protected static $version = 0;
    protected static $liveVersion = 0;

    /**
     * Key - The tag column of the table being updated
     * Value - The object representing the table being updated. The table should
     * contain a "tag" column
     * 
     * @var Array
     */
    protected $updateInfo = array();

    /**
     * Key - Tag
     * Value - Array with key as column and value a string of the form (old value | new value)
     * @var Array
     */
    protected $changedTags = array();
    /**
     * Values are the tags that were added
     * @var Array
     */
    protected $addedTags = array();
    /**
     * Values are tags that were removed
     * 
     * @var Array
     */
    protected $removedTags = array();

    /**
     * @var DB
     */
    protected $dbObj = null;

    /**
     *
     * @param String $table
     */
    function __construct($table) {
        $this->table = $table;
        $this->dbObj = DBPool::getInstance();
    }

    public function updateDB() {
        
        //Get the current values
        $oldValues = $this->getAllExisting();

        $this->dbObj->start_transaction();

        try {
            //Determine the diff between existing values and new values
            $addedTags = array();
            $removedTags = array();
            $changedTags = array();

            if (false !== $oldValues) {
                foreach($oldValues as $tag => $v) {
                    $changes = array();
                    
                    if( array_key_exists($tag, $this->updateInfo) ) {
                        $changes = $this->dataUpdated($oldValues[$tag],
                                $this->updateInfo[$tag]);
                        if(count($changes) > 0) {
                            $changedTags[$tag] = $changes;
                        }
                    } else {
                        $removedTags[] = $tag;
                    }
                }

                //Check if any new rows were added
                foreach ($this->updateInfo as $u) {
                    if( ! array_key_exists($u->getTag(), $oldValues) ) {
                        $addedTags[] = $u->getTag();
                    }
                }
            }
            
            $this->changedTags = $changedTags;
            $this->addedTags = $addedTags;
            $this->removedTags = $removedTags;

            //Add the new rows as new version
            $this->add($this->updateInfo);

        } catch (DBException $ex) {
            $this->dbObj->rollback();

            throw new Exception($ex->getMessage());
        }

        $this->dbObj->end_transaction();
    }

    /**
     * Delete rows from the database
     * @param array $dbRows - An array of DB row objects
     *
    protected function delete(array $dbRows) {

        $ids = array();

        foreach($dbRows as $r) {
            $ids[] = $r->id;
        }

        $query = "DELETE FROM ". $this->table ." WHERE id IN (". implode(",", $ids) .")";
        $this->dbObj->query($query);

        if($this->dbObj->rows_affected > 0) {
            //TODO: Add an INFO log
        } else {
            //TODO: Add a CRIT log
            throw new DBException("Deletion failed [table:". $this->table ."]");
        }

       self::$wasUpdated = true;

    } */

    /**
     * Each table maintains its own version number
     * 
     * @return Integer
     */
    public static function getVersion() {
        if(0 == TableUpdate::$version) {
            TableUpdate::calculateVersion();
        }

        return TableUpdate::$version;
    }

    public static function setVersion($version) {
        TableUpdate::$version = $version;
    }

    private static function calculateVersion() {
        $dbObj = DBPool::getInstance();
        $dbObj->query("INSERT INTO version (created_date) VALUES (NOW())");
        $version = $dbObj->getLastInsertId("version_id_seq");

        TableUpdate::$version = $version;
    }

    /**
     * Returns the current live version number
     * 
     * @return Integer
     */
    public static function getLiveVersion() {
        if(0 == TableUpdate::$liveVersion) {
            TableUpdate::calculateLiveVersion();
        }

        return TableUpdate::$liveVersion;
    }

    private static function calculateLiveVersion() {
        $dbObj = DBPool::getInstance();
        $liveVersion = $dbObj->get_var("SELECT id FROM version WHERE active=true");

        TableUpdate::$liveVersion = (int) $liveVersion;
    }

    protected function getChangesAsString() {
        $data = "Version: Current - ". TableUpdate::getVersion() .
            " | Live - " . TableUpdate::getLiveVersion() . "\n";
        if(count($this->addedTags) > 0) {
            $data .= "Added:\n";
            $data .= implode(", ", $this->addedTags)  . "\n\n";
        }
        if(count($this->removedTags) > 0) {
            $data .= "Removed:\n";
            $data .= implode(", ", $this->removedTags)  . "\n\n";
        }

        if(count($this->changedTags) > 0) {
            $data .= "Changes:\n";

            foreach($this->changedTags as $tag => $changes) {
                $data .= "$tag\n";
                foreach ($changes as $column => $change) {
                    $data .= "$column - $change\n";
                }

                $data .= "\n\n\n";
            }
        }

        return $data;
    }

    protected function saveChangesToFile() {
        if( (count($this->addedTags) == 0) && (count($this->removedTags) == 0) &&
                (count($this->changedTags) == 0)) {
            return false;
        }

        $fileName = Util::getChangeLogFile();
        $data = "*** ". $this->table ." ***\n";
        $data .= $this->getChangesAsString();

        file_put_contents($fileName, $data, FILE_APPEND);

        $this->dbObj->bindParams( array( TableUpdate::getVersion() ) );
        $this->dbObj->query("UPDATE version SET changes_present=true WHERE id=?");
    }

    abstract protected function getAllExisting();
    abstract protected function add(array $data);

    /**
     * Classes which extend TableUpdate should check for updates as appropriate
     * and return true if data has changed
     * 
     * @return boolean
     */
    //abstract protected function dataUpdated(Direction $o, Direction $n);
}
