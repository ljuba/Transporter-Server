<?php
/**
 *
 * ezPDO
 *
 * @author Thejo
 *
 */
class DB {
    //Version
    const VERSION = "0.1";

    //Supported databases
    const POSTGRESQL = 'pgsql';
    const MYSQL = 'mysql';

    /**
     * PDO connection object pool
     * @var Array of PDO objects
     */
    private static $pool = array();

    //Connection details
    private $dbType;
    private $dbName;
    private $dbHost;
    private $dbUser;
    private $dbPassword;
    private $persistentConnection = true;
    private $instanceName;

    //Output types
    const OBJECT = 'OBJECT';
    const ARRAY_A = 'ASSOCIATIVE ARRAY';
    const ARRAY_N = 'INDEXED ARRAY';

    public $num_rows = 0;
    public $rows_affected = 0;
    public $num_queries = 0;
    public $insert_id = null;
    public $debug = true;

    //Internal fields
    private $last_query;
    private $last_result;
    private $func_call;
    private $last_error;

    private $statementHandle;
    private $boundParams = array();
    private $debugBoundParams = array();

    /**
     * Constructor
     */
    public function __construct(array $d) {
        if(! isset($d['db_type']) || ! isset($d['db_name']) ) {
            throw new DBException("DB type and name are required");
        }

        $this->dbType = strtolower($d['db_type']);
        $this->dbName = $d['db_name'];
        $this->dbHost = isset($d['db_host']) ? $d['db_host'] : 'localhost';
        $this->dbUser = isset($d['db_user']) ? $d['db_user'] : '';
        $this->dbPassword = isset($d['db_password']) ? $d['db_password'] : '';
        $this->persistentConnection = isset($d['persistent']) && (false == $d['persistent']) ? false : true;
        $this->instanceName = isset($d['instance_name']) ? $d['instance_name'] : $this->generateInstanceName();
    }

    private function generateInstanceName() {
        return "$this->dbHost-$this->dbType-$this->dbName";
    }

    /**
     * Get the PDO instance associated with this DB object
     *
     * @return Object of PDO class
     * @throws DBException
     */
    public function getHandle() {
        if(isset(self::$pool[$this->instanceName]) &&
            self::$pool[$this->instanceName] instanceof PDO) {
            return self::$pool[$this->instanceName];
        }

        $dsn = "$this->dbType:host=$this->dbHost;dbname=$this->dbName";
        try {
            self::$pool[$this->instanceName] = new PDO($dsn, $this->dbUser, $this->dbPassword, array(
                PDO::ATTR_PERSISTENT => $this->persistentConnection,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => true
            ));
        } catch (PDOException $e) {
            throw new DBException($e->getMessage(), $e->getCode());
        }

        return self::$pool[$this->instanceName];
    }

    public function get_results($query = null, $output = self::OBJECT) {

        // Log how the function was called
        $this->func_call = "\$db->get_results(\"$query\", $output)";

        // If there is a query then perform it if not then use cached results..
        if ( $query ) {
            $this->query($query);
        }

        // Send back array of objects. Each row is an object
        if ( $output == self::OBJECT ) {
            return $this->last_result;

        } else if ( $output == self::ARRAY_A || $output == self::ARRAY_N ) {
            if ( $this->last_result ) {
                $i=0;
                foreach( $this->last_result as $row ) {
                    $new_array[$i] = get_object_vars($row);

                    if ( $output == self::ARRAY_N ) {
                        $new_array[$i] = array_values($new_array[$i]);
                    }

                    $i++;
                }

                return $new_array;

            } else {
                return null;
            }
        }
    }

    public function get_row($query = null, $output = self::OBJECT, $row_num = 0) {

        // Log how the function was called
        $this->func_call = "\$db->get_row(\"$query\",$output,$row_num)";

        // If there is a query then perform it if not then use cached results..
        if ( $query ) {
            $this->query($query);
        }

        // If the output is an object then return object using the row offset..
        if ( $output == self::OBJECT ) {
            return isset($this->last_result[$row_num]) ?
                    $this->last_result[$row_num] : null;
        }
        // If the output is an associative array then return row as such..
        else if ( $output == self::ARRAY_A ) {
            return isset($this->last_result[$row_num]) ?
                    get_object_vars($this->last_result[$row_num]) : null;
        }
        // If the output is an numerical array then return row as such..
        else if ( $output == self::ARRAY_N ) {
            return isset($this->last_result[$row_num]) ?
                    array_values(get_object_vars($this->last_result[$row_num])) : null;
        }
        // If invalid output type was specified..
        else {
            throw new DBException(" \$db->get_row(string query, output type, int offset)
                                    -- Output type must be one of: DB::OBJECT, DB::ARRAY_A, DB::ARRAY_N");
        }
    }

    public function get_var($query = null, $col_num = 0, $row_num = 0) {

        // Log how the function was called
        $this->func_call = "\$db->get_var(\"$query\",$col_num,$row_num)";

        // If there is a query then perform it if not then use cached results..
        if ( $query ) {
            $this->query($query);
        }

        // Extract var out of cached results based on $col_num and $row_num vals
        if ( isset($this->last_result[$row_num]) ) {
            $values = array_values(get_object_vars($this->last_result[$row_num]));
        }

        // If there is a value return it else return null
        return (isset($values[$col_num]) && $values[$col_num]!== '') ? $values[$col_num] : null;
    }

    public function get_col($query=null,$x=0) {
        $resultRows = $this->get_results($query, self::ARRAY_N);
        $rowCount = count($resultRows);
        $colValues = array();

        foreach($resultRows as $row) {
            $colValues[] = $row[$x];
        }

        return $colValues;
    }

    public function escape($str) {
        return $this->getHandle()->quote($str);
    }

    private function flush() {
        // Get rid of these
        $this->last_result = null;
        $this->last_query = null;
        $this->num_rows = 0;
        $this->rows_affected = 0;

        $this->statementHandle = null;
    }

    public function query($query) {
        // Flush cached values..
        $this->flush();

        // For reg expressions
        $query = trim($query);

        // Keep track of the last query for debug..
        $this->last_query = $query;

        // Count how many queries there have been
        $this->num_queries++;

        try {
            //Get a handle
            $dbh = $this->getHandle();

            if(count($this->boundParams) > 0) {
                $this->statementHandle = $dbh->prepare($query);

                //Try the commented code below if you have problems with data types
                $this->statementHandle->execute($this->boundParams);
                /*
                foreach ($this->boundParams as $k => $v) {
                    $this->statementHandle->bindParam($k+1, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
                }
                $this->statementHandle->execute();
                */

            } else {
                $this->statementHandle = $dbh->query($query);
            }

            $this->rows_affected = $this->num_rows = $this->statementHandle->rowCount();

            //Get the last insert id if the database is MySQL
            //Postgres needs an explicit call to $this->getLastInsertId() with a sequence name
            if ( preg_match("/^(insert|replace)\s+/i",$query) && $this->dbType == self::MYSQL) {
                $this->insert_id = $this->getLastInsertId();
            }
            //Get the results if it is a select query
            else if ( preg_match("/^select\s+/i",$query) ) {
                $this->last_result = $this->statementHandle->fetchAll(PDO::FETCH_OBJ);
            }

        } catch (Exception $e) {
            $this->afterQueryCleanUp();
            $this->last_error = $e->getMessage();
            throw new DBException($e->getMessage(), $e->getCode());
        }

        $this->afterQueryCleanUp();

        //Return the number of rows affected or returned by query
        return $this->num_rows;
    }

    private function afterQueryCleanUp() {
        if($this->statementHandle instanceof PDOStatement) {
            $this->statementHandle->closeCursor();
        }

        $this->debugBoundParams = $this->boundParams;
        $this->boundParams = array();
    }

    public function bindParams(array $params){
        $this->boundParams = $params;
    }

    /**
     * Get the last insert ID when a new row is inserted
     * For MySQL, the $this->insert_id field is automatically populated
     * For PostgreSQL, this method has to be explicitly called with the
     * name of the sequence
     *
     * @param $sequence - Postgres sequence name
     * @return Integer
     */
    public function getLastInsertId($sequence = '') {
        return $this->getHandle()->lastInsertId($sequence);
    }

    public function start_transaction() {
        $this->getHandle()->beginTransaction();
    }

    public function end_transaction() {
        $this->getHandle()->commit();
    }

    public function rollback() {
        $this->getHandle()->rollBack();
    }

    public function getInstanceName() {
        return $this->instanceName;
    }

    public function debug() {

        // Start outup buffering
        ob_start();

        echo "<blockquote>";

        if ( $this->last_error )
        {
            echo "<font face=arial size=2 color=000099><b>Last Error --</b> [<font color=000000><b>$this->last_error</b></font>]<p>";
        }

        echo "<font face=arial size=2 color=000099><b>Query</b> [$this->num_queries] <b>--</b> ";
        echo "[<font color=000000><b>$this->last_query</b></font>]</font><p>";
        if( count($this->debugBoundParams) > 0 ) {
            echo'<font face=arial size=2 color=000099><b>Bound Parameters</b><pre>';
            var_dump($this->debugBoundParams);
            echo'</pre></font>';
        }

            echo "<font face=arial size=2 color=000099><b>Query Result..</b></font>";
            echo "<blockquote>";

        if ( $this->last_result )
        {

            // =====================================================
            // Results top rows

            echo "<table cellpadding=5 cellspacing=1 bgcolor=555555>";
            echo "<tr bgcolor=eeeeee><td nowrap valign=bottom><font color=555599 face=arial size=2><b>(row)</b></font></td>";


            foreach ( $this->last_result[0] as $key => $value ) {
                echo "<td nowrap align=left valign=top><span style='font-family: arial; font-size: 10pt; font-weight: bold;'>{$key}</span></td>";
            }

            echo "</tr>";

            // ======================================================
            // print main results

            $i=0;
            //var_dump($this->get_results(null,self::ARRAY_N));
            foreach ( $this->get_results(null,self::ARRAY_N) as $one_row ) {
                $i++;
                echo "<tr bgcolor=ffffff><td bgcolor=eeeeee nowrap align=middle><font size=2 color=555599 face=arial>$i</font></td>";

                foreach ( $one_row as $item ) {
                    echo "<td nowrap><font face=arial size=2>$item</font></td>";
                }

                echo "</tr>";
            }

            echo "</table>";

        } // if col_info
        else {
            echo "<font face=arial size=2>No Results</font>";
        }

        // Stop output buffering and capture debug HTML
        $html = ob_get_contents();
        ob_end_clean();

        // Only echo output if it is turned on
        if ( $this->debug )
        {
            echo $html;
        }

        return $html;

    }
}