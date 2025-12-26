<?php
/* require_once(LIB_PATH_INC.DS."config.php"); */
require_once(__DIR__ . '/config.php');

class MySqli_DB {

    public $con;
    public $query_id;

    function __construct() {
      try {
          $this->db_connect();
      } catch (Exception $e) {
          // Log the error but don't die
          error_log("Database connection error: " . $e->getMessage());
          $this->con = null;
          // For development, you might want to show the error
          if (defined('DEBUG') && DEBUG) {
              echo "Database connection failed: " . $e->getMessage();
          }
      }
    }

/*--------------------------------------------------------------*/
/* Function for Open database connection
/*--------------------------------------------------------------*/
public function db_connect()
{
  $this->con = mysqli_connect(DB_HOST,DB_USER,DB_PASS);
  if(!$this->con)
         {
           throw new Exception("Database connection failed: " . mysqli_connect_error());
         } else {
           $select_db = $this->con->select_db(DB_NAME);
             if(!$select_db)
             {
               throw new Exception("Failed to Select Database: " . mysqli_connect_error());
             }
             // Set MySQL timezone to Sri Lanka (Asia/Colombo)
             $this->con->query("SET time_zone = '+05:30'");
         }
}
/*--------------------------------------------------------------*/
/* Function for Close database connection
/*--------------------------------------------------------------*/

public function db_disconnect()
{
  if(isset($this->con))
  {
    mysqli_close($this->con);
    unset($this->con);
  }
}
/*--------------------------------------------------------------*/
/* Function for mysqli query
/*--------------------------------------------------------------*/
public function query($sql)
   {
      // Check if connection exists
      if (!$this->con) {
          throw new Exception("Database connection not established");
      }

      if (trim($sql != "")) {
          $this->query_id = $this->con->query($sql);
      }
      if (!$this->query_id) {
        // only for Develope mode
              throw new Exception("Error on this Query: " . $sql . " - MySQL Error: " . mysqli_error($this->con));
       // For production mode
        //  throw new Exception("Error on Query");
      }

       return $this->query_id;

   }

/*--------------------------------------------------------------*/
/* Function for Query Helper
/*--------------------------------------------------------------*/
public function fetch_array($statement)
{
  return mysqli_fetch_array($statement);
}
public function fetch_object($statement)
{
  return mysqli_fetch_object($statement);
}
public function fetch_assoc($statement)
{
  return mysqli_fetch_assoc($statement);
}
public function num_rows($statement)
{
  return mysqli_num_rows($statement);
}
public function insert_id()
{
  return mysqli_insert_id($this->con);
}
public function affected_rows()
{
  return mysqli_affected_rows($this->con);
}
/*--------------------------------------------------------------*/
 /* Function for Remove escapes special
 /* characters in a string for use in an SQL statement
 /*--------------------------------------------------------------*/
 public function escape($str){
   if (!$this->con) {
       throw new Exception("Database connection not established");
   }
   return $this->con->real_escape_string($str);
 }
/*--------------------------------------------------------------*/
/* Function for while loop
/*--------------------------------------------------------------*/
public function while_loop($loop){
 global $db;
   $results = array();
   while ($result = $this->fetch_array($loop)) {
      $results[] = $result;
   }
 return $results;
}

}

$db = new MySqli_DB();

?>
