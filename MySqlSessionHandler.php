<?php

/**
* A PHP session handler to keep session data within a MySQL database
*
* @author     Manuel Reinhard <manu@sprain.ch>
* @link        https://github.com/sprain/PHP-MySQL-Session-Handler
*/

class MySqlSessionHandler{

    /**
     * a database MySQLi connection resource, hostname, user, password and database
     * @var resource
     */
    protected $dbConnection, $dbHost, $dbUser, $dbPassword, $dbDatabase;
    
    /**
     * default name of the DB table which handles the sessions
     * @var string
     */
    protected $dbTable='session';

    /**
     * hits in session
     */
    protected $hits=0;
    
    /**
     * from archive or not 0/1
     */ 
    protected $archive=0;

    /**
     * Set db data 
     * @param    string    $dbHost
     * @param    string    $dbUser
     * @param    string    $dbPassword
     * @param    string    $dbDatabase
     */
    public function _construct($dbHost=null, $dbUser=null, $dbPassword=null, $dbDatabase=null)
    {

        $this->dbHost=$dbHost;
        $this->dbUser=$dbUser;
        $this->dbPassword=$dbPassword;
        $this->dbDatabase=$dbDatabase;
        
    }


    /**
     * Inject DB connection from outside
     * @param     object    $dbConnection    expects MySQLi object
     */
    public function setDbTable($dbTable)
    {
        $this->dbTable = $dbTable;
    }

    /**
     * Open the session
     * @return bool
     */
    public function open()
    {
   
        $this->dbConnection = new mysqli('p:'.$this->dbHost, $this->dbUser, $this->dbPassword, $this->dbDatabase);

        if (mysqli_connect_error()) {
            throw new Exception('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
        }
        return true;

    }

    /**
     * Close the session
     * @return bool
     */
    public function close()
    {

        return true;
    }

    /**
     * Read the session
     * @param int session id
     * @return string string of the sessoin
     */
    public function read($id)
    {
        $sql = sprintf("SELECT data, hits, archive, gz FROM %s WHERE id = '%s' ORDER BY archive LIMIT 1 FOR UPDATE", $this->dbTable, $this->dbConnection->escape_string($id));
        if ($result = $this->dbConnection->query($sql)) {
            if ($result->num_rows && $result->num_rows > 0) {

                $record = $result->fetch_assoc();
                $this->hits=$record['hits'];
                $this->archive=$record['archive'];
                if ( $gz == '1' ) {
        
                    return gzinflate($record['data']);
                }
                return $record['data'];

            } else {
                return false;
            }
        } else {
            return false;
        }

        return true;
    }

    /**
     * Write the session
     * @param int session id
     * @param string data of the session
     */
    public function write($id, $data)
    {

        $this->hits++;
        if ( strlen($data) > 32768 ) {

            $data=gzdeflate($data);
            $gz='1';
        } else {

            $gz='0';
        }

        if ( $this->hits == 1 ) {

           $sql = sprintf("INSERT INTO %s VALUES('%s', '%s', '%s', '%s', '%s', '%s');commit",
               $this->dbTable,
               $this->dbConnection->escape_string($id),
               $this->dbConnection->escape_string($data),
               $this->dbConnection->escape_string($this->hits),
               '0',
               time(),
               $gz);
        } else {

            $sql = sprintf("UPDATE %s SET data='%s', archive='%s', hits='%s', timestamp='%s', gz='%s' WHERE id='%s' and archive='%s' ;commit",
               $this->dbTable,
               $this->dbConnection->escape_string($data),
               '0',
               $this->dbConnection->escape_string($this->hits),
               time(),
               $gz,
               $this->dbConnection->escape_string($id),
               $this->archive);
        }
        return $this->dbConnection->multi_query($sql);
    }

    /**
     * Destoroy the session
     * @param int session id
     * @return bool
     */
    public function destroy($id)
    {
        $sql = sprintf("DELETE FROM %s WHERE `id` = '%s'", $this->dbTable, $this->dbConnection->escape_string($id));
        return $this->dbConnection->query($sql);
    }

    /**
     * Garbage Collector
     * @param int life time (sec.)
     * @return bool
     * @see session.gc_divisor      100
     * @see session.gc_maxlifetime 1440
     * @see session.gc_probability    1
     * @usage execution rate 1/100
     *        (session.gc_probability/session.gc_divisor)
     */
    public function gc($max)
    {
    
        $time=time();
    
        //Removing robot sessions (sessions with one hit, which have not been used for half an hour)
        $sql = sprintf("DELETE FROM %s WHERE archive=0 AND hits=1 AND `timestamp` < '%s'", $this->dbTable, $time - 1800);
        $this->dbConnection->query($sql);
    
        //Moving sessions older then one hour to archive partition    
        $sql = sprintf("UPDATE %s SET archive='1' WHERE archive=0 and `timestamp` < '%s'", $this->dbTable, $time - 3600);
        $this->dbConnection->query($sql);

        //Removing old sessions
        $sql = sprintf("DELETE FROM %s WHERE archive=1 and `timestamp` < '%s'", $this->dbTable, $time - intval($max));
        return $this->dbConnection->query($sql);
    }
}
