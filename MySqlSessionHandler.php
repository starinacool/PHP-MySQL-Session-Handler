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
		 * session id
		 */   
		protected $id;

    /**
     * Set db data 
     * @param    string    $dbHost
     * @param    string    $dbUser
     * @param    string    $dbPassword
     * @param    string    $dbDatabase
     */
    public function __construct($dbHost=null, $dbUser=null, $dbPassword=null, $dbDatabase=null)
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
   
        $this->dbConnection = new mysqli($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbDatabase);

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

								error_log('NEWSES: read ' . $id);
                $record = $result->fetch_assoc();
                $this->hits=$record['hits'];
                $this->archive=$record['archive'];
								$this->id=$id;

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

        return false;
    }

    /**
     * Write the session
     * @param int session id
     * @param string data of the session
     */
    public function write($id, $data)
    {

				error_log('NEWSES: write ' . $id);
        $updt=array();
        $this->hits++;
        if ( strlen($data) > 32768 ) {

            $data=gzdeflate($data);
            $gz='1';
        } else {

            $gz='0';
        }

        $updt[]=" data='".$this->dbConnection->escape_string($data)."'";
        $updt[]=" hits='".$this->hits."'";
        $updt[]=" timestamp='".time()."'";
        $updt[]=" gz='".$gz."'";
        
        if ( $this->hits == 1 || $this->id != $id ) {

           $updt[]=" archive='0' ";
           $updt[]=" id='".$this->dbConnection->escape_string($id)."'";
           $sql = sprintf("INSERT INTO %s SET".join(',',$updt).";COMMIT"
             ,$this->dbTable);

        } else {
	
           if ( $this->archive == '1' ) {
	   	
               $updt[]=" archive='0'";
            }

            $sql = sprintf("UPDATE %s SET".join(',',$updt)." WHERE id='%s' AND archive='%s' ;COMMIT",
               $this->dbTable,
               $this->dbConnection->escape_string($id),
               $this->archive);
        }
				error_log('NEWSES: write ' . $id . ' ' . $sql);
        if ( $this->dbConnection->multi_query($sql) ) {
					
					while ($this->dbConnection->next_result());
					return true;
				}
				return false;
    }

    /**
     * Destoroy the session
     * @param int session id
     * @return bool
     */
    public function destroy($id)
    {
				error_log('NEWSES: destroy ' . $id);
        $sql = sprintf("DELETE FROM %s WHERE `id` = '%s';COMMIT", $this->dbTable, $this->dbConnection->escape_string($id));
        if ( $this->dbConnection->multi_query($sql) ) {
					
					while ($this->dbConnection->next_result());
          return true;
				}
				return false;
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
				error_log('NEWSES: gc ' . $max);
    
        //Removing robot sessions (sessions with one hit, which have not been used for half an hour)
        $sql = sprintf("DELETE FROM %s WHERE archive=0 AND hits=1 AND `timestamp` < '%s'", $this->dbTable, $time - 1800);
        $this->dbConnection->query($sql);
    
        //Moving sessions older then one hour to archive partition    
        $sql = sprintf("UPDATE %s SET archive='1' WHERE archive=0 AND `timestamp` < '%s'", $this->dbTable, $time - 3600);
        $this->dbConnection->query($sql);

        //Removing old sessions
        $sql = sprintf("DELETE FROM %s WHERE archive=1 AND `timestamp` < '%s';COMMIT", $this->dbTable, $time - intval($max));
				$this->dbConnection->query($sql);
        
				return $this->dbConnection->query('COMMIT');
    }
}
