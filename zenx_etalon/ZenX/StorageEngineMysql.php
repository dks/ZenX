<?php
/**
 * "ZenX" PHP data manipulation library.
 *
 * @author Konstantin Dvortsov <kostya.dvortsov@gmail.com>. You can 
 * also track me down at {@link http://dvortsov.tel dvortsov.tel }
 * @version 1.0
 * @package ZenX 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
/**
 * StorageEngineMysql class extends MainEngineAbstract class and implements
 * data storage specific functions for MySQL database.
 *
 * @package ZenX
 * @author Konstatin Dvortsov
 */
class StorageEngineMysql extends MainEngineAbstract {
	/**
	 * $connection variable stores active database connection resource.
	 *
	 * @var resource Active connection to database 
	 */
	protected $connection;
	/**
	 * $storageDataTypes array contains storage engine specific data types descriptions.
	 *
	 * If new data type description were added to the DataDefinition class they must also be added here.
	 * It is basically key=>value array where key is the data type and value is mysql table column description.
	 *
	 * @see DataDefiner
	 * @see getTypeCreationStatement()
	 * @see $storageMultiTypes
	 * @var Array Array with engine specific data type descriptions.
	 */
  static private $storageDataTypes=array(
    "_KEYS" => "int unsigned NOT NULL auto_increment",
    "_WORD" => "varchar(300)",
    "_TEXT" => "text",
    "_RINT" => "int",
    "_RFLT" => "float",
    "_LLST" => "int unsigned",
    "_ELST" => "int unsigned",
    "_RLST" => "int unsigned",
    "_BOOL" => "tinyint(1)",
    "_DATE" => "date",
    "_IMGS" => "",
    "_ICON" => "",
    "_FILE" => ""
	);
	/**
	 * $storageMultiTypes array contains storage engine specific data types descriptions for "multi" types.
	 *
	 * If new data type description were added to the DataDefinition class they must also be added here. 
	 * It is basically key=>value array where key is the data type and value is mysql table column description.
	 *
	 * @see DataDefiner
	 * @see getMultiCreationStatement()
	 * @see $storageDataTypes
	 * @var Array Array with engine specific data "multi" type descriptions.
	 */
  static private $storageMultiTypes=array(
    "_LLST" => "varchar(300)",
    "_RLST" => "varchar(300)",
    "_ELST" => "varchar(300)"
  );
	/**
	 * Class constructor.
	 *
	 * Invokes parent class constructor and creates database connection.
	 *
	 * @param String $host Host name for database connection
	 * @param String $user User name for database connection
	 * @param String $pass Password for database connection
	 * @param String $base Name of the data base to connect to.
	 * @see connect()
	 */
  function __construct($host,$user,$pass,$base){
    parent::__construct();
    $this->connect($host,$user,$pass,$base);
  }//EOF
	/**
	 * setEngineParameters() method adds engine specific settings using 
	 * {@link MainEngineAbstract::setParameters()} method.
	 */
  function setEngineParameters(){
    $this->setParameters(array(
      "mysqlPrefix"=>"ZX_",
      "mysqlMultiSuffix"=>"_mul_"
    ));
  }//EOF
	/**
	 * connect() method establishes connection to data base.
	 *
	 * @param String $host Host name for database connection
	 * @param String $user User name for database connection
	 * @param String $pass Password for database connection
	 * @param String $base Name of the data base to connect to.
	 * @see $connection
	 */
  function connect($host,$user,$pass,$base){
    $this->connection=mysql_connect($host, $user,$pass) or die(mysql_error());
    mysql_select_db($base,$this->connection) or die (mysql_error());
    mysql_set_charset('utf8',$this->connection);
	}//EOF
	/**
	 * getTypeCreationStatemen() method retrieves mysql data (row) creation statement from $storageDataTypes.
	 *
	 * If the statments is missing {@link MySqlDataTypeUndefinedException} is thrown.
	 * @see $storageDataTypes
	 * @throws MySqlDataTypeUndefinedException
	 * @return String MySQL table field creation statement
	 */
  function getTypeCreationStatement($type){
    try{
      if (!isset(self::$storageDataTypes[$type]))
        throw new MySqlDataTypeUndefinedException($type);
    }catch(Exception $e){
      echo $e->errorMessage();
      exit(125);
    }
    return self::$storageDataTypes[$type];
  }//EOF
	/**
	 * getMultiCreationStatemen() method retrieves mysql data (row) creation statement from $storageMultiTypes.
	 *
	 * If the statments is missing {@link MySqlMultiTypeUndefinedException} is thrown.
	 * @see $storageMultiTypes
	 * @throws MySqlMultiTypeUndefinedException
	 * @return String MySQL table field creation statement
	 */
  function getMultiCreationStatement($type){
    try{
      if (!isset(self::$storageMultiTypes[$type]))
        throw new MySqlMultiTypeUndefinedException($type);
    }catch(Exception $e){
      echo $e->errorMessage();
      exit(125);
    }
    return self::$storageMultiTypes[$type];
  }//EOF
	/**
	 * createStorage() method performs all necessary routines to create tables from 
	 * {@link MainEngineAbstract::$tables} array.
	 *
	 * This is utility method for automatic creation of mysql tables. Tables are created 
	 * considering the following parameters:
	 * 'mysqlPrefix' parameter at {@link MainEngineAbstract::$ops} is the string to prepend table name.
	 * 'imageFolder' parameter at {@link MainEngineAbstract::$ops} is the name of folder storing images,
	 * must have read/write access.
	 * 'fileFolder' parameter at {@link MainEngineAbstract::$ops} is the name of folder storing files
	 * (other than images), must have read/write access.
	 * 'mysqlMultiSuffix' parameter at {@link MainEngineAbstract::$ops} is the suffix used to form
	 * multiple values tables (referenced values).
	 *
	 * Data charset is set to UTF-8. If image/file folder can not be accessed 
	 * {@link MySqlNoFolderCreateRightsException} is thrown.
	 *
	 * Please note, that current implementation stores files/images externally in file system instead of
	 * database, image tables contain only reference numbers for file names identification.
	 *
	 * @throws MySqlNoFolderCreateRightsException
	 * @see MainEngineAbstract::setParameters()
	 * @see $storageDataTypes
	 * @see $storageMultiTypes
	 */
  function createStorage() {
    foreach($this->getAllTables() as $table){
      $ss = "CREATE TABLE IF NOT EXISTS `".$this->ops['mysqlPrefix'].$table->getName()."` (\r\n";
      foreach($table->getFields() as $field){
        $type = $field->getType();
        $name = $field->getName();
        $comm = $field->getNote();
        $crst = $this->getTypeCreationStatement($type);
        if (!$field->getProp("isFile")) $ss.="`$name` ".$crst." COMMENT '".$comm."',\r\n";
        else {
          $dir=($field->getProp("isImage"))?$this->ops['imageFolder']:$this->ops['fileFolder'];
          if (!is_dir($dir)) {
            try{
              if (!@mkdir($dir))
                throw new MySqlNoFolderCreateRightsException($dir);
            }catch(Exception $e){
              echo $e->errorMessage();
              exit(125);
            }
          }
        }
        if ($type=="_KEYS") $pk=$name;
        if ($field->getProp("omni")!==false){
          $aux ="CREATE TABLE IF NOT EXISTS `".$this->ops['mysqlPrefix'].
            $table->getName().$this->ops['mysqlMultiSuffix'].$name."`(\r\n";
          $aux.="`id` int unsigned NOT NULL auto_increment,\r\n";
          $aux.="`data` ".$this->getMultiCreationStatement($type).",\r\n";
          $aux.="PRIMARY KEY(`id`)\r\n";
          $aux.=") ENGINE=MyISAM DEFAULT CHARSET=utf8;\r\n";
          mysql_query($aux) or die(mysql_error());
          $aux=null;
        }
      }
      $ss.="PRIMARY KEY(`".$pk."`)\r\n";
      $ss.=") ENGINE=MyISAM DEFAULT CHARSET=utf8;\r\n";
      mysql_query($ss) or die(mysql_error());
    }
  }//EOF
	/**
	 * addNewRecord() method stores data supplied as argument to database.
	 *
	 * This method is purely mysql database related, so any filtering / preprocessing is done
	 * before this method is invoked. If image folder cannot be accessed {@link fileUploadErrorHandler}
	 * method is invoked, however it is set to WARNING level and does not stop script execution.
	 * Currently only image storing (in addition to standard text, of course) is implemented. 
   * This method does not check whether supplied parameters array is all empty since it is 
   * done at the filtering stage. Supplying empty $data array will lead to mysql error.
	 *
	 * @param Array $data Associative array where keys are field names and values is data to be stored.
	 * @throws fileUploadErrorHandler
   * @return int Key (ID) of the record in data storage
	 */
  function addNewRecord($data){
    $table=$this->getCurrentTable();
    $tabName=$table->getName();
    foreach($table->getFields() as $field){
      $fn=$field->getName();
      if ($field->getProp("extendable")){
        if (in_array($fn."_nv",array_keys($data))){
          if (trim($data[$fn."_nv"])!=""){
            $ss="INSERT INTO ".$this->ops['mysqlPrefix'].$tabName.$this->ops['mysqlMultiSuffix'].
              $fn." VALUES(null,'".mysql_real_escape_string($data[$fn."_nv"])."');";
            mysql_query($ss) or die(mysql_error()); $ss=null;
            $data[$fn]=mysql_insert_id();
        }}
        unset($data[$fn."_nv"]);
      }
    }
    $ss="INSERT INTO ".$this->ops['mysqlPrefix'].$tabName." SET ";
    foreach($data as $k=>$v) if ($v!="") $ss.="$k='".mysql_real_escape_string($v)."',";
    $ss=substr($ss,0,strlen($ss)-1).";";
		// in case table has only key field (rest of fields are of file types) 
		if (count($data)==1 && array_search("",$data)==$table->getKey()->getName())
			$ss="INSERT INTO ".$this->ops['mysqlPrefix'].$tabName." VALUES(null);";
    mysql_query($ss) or die(mysql_error());
    $last_id=mysql_insert_id();
    foreach($table->getFields() as $field){//file/image handling
			if ($field->getProp("isFile")){ 
      	$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
        $fn=$field->getName();
        if (isset($_FILES[$fn]) && $_FILES[$fn]['error']==0){
          $tmp=basename($_FILES[$fn]['name']);
          $ext=substr($tmp,$ps=strrpos($tmp,"."),strlen($tmp)-$ps);
          $imgfn =$folder."/".$tabName."_".$fn."_".$last_id.$ext;
          $imgToDel=glob($folder."/".$table->getName()."_".$fn."_".$last_id.".*");
          set_error_handler(array($this,"fileUploadErrorHandler"),E_WARNING);
          foreach($imgToDel as $itd) unlink($itd);
          move_uploaded_file($_FILES[$fn]['tmp_name'],$imgfn);
          restore_error_handler();
          if ($field->getProp("isImage")) $this->imageResize($imgfn,$field);
    }}}//file/image handling
    return $last_id;
  }//EOF
	/**
	 * updateRecord() method stores data supplied as argument to database, replacing the old values.
	 *
	 * This method is purely mysql database related, so any filtering / preprocessing is done
	 * before this method is invoked. If image folder cannot be accessed {@link fileUploadErrorHandler}
	 * method is invoked, however it is set to WARNING level and does not stop script execution.
	 * Currently only image storing is implemented. This method does not check whether supplied 
	 * parameters array is all empty since it is done at the filtering stage. Supplying empty
	 * $data array will lead to mysql error.
	 *
	 * If supplied data array does not contain table key {@link NoUpdateKeySuppliedException} is thrown.
	 *
	 * @param Array $data Associative array where keys are field names and values is data to be stored.
	 * @throws fileUploadErrorHandler
	 * @throws NoUpdateKeySuppliedException
	 */
  function updateRecord($data){
		$needCleanUp=false;
    $table=$this->getCurrentTable();
    $tabName=$table->getName();
    $key=$table->getKey()->getName();
    try{
      if (in_array($key,array_keys($data))==false)
        throw new NoUpdateKeySuppliedException($key);
    }catch(Exception $e){
      echo $e->errorMessage();
      exit(125);
    }
    foreach($table->getFields() as $field){
      if ($field->getProp("extendable")){
        $fn=$field->getName();
        if (in_array($fn."_nv",array_keys($data)) && trim($data[$fn."_nv"])!=""){  
				    $needCleanUp=true;
            $ss="INSERT INTO ".$this->ops['mysqlPrefix'].$tabName.$this->ops['mysqlMultiSuffix'].
              $fn." VALUES(null,'".mysql_real_escape_string($data[$fn."_nv"])."');";
            mysql_query($ss) or die(mysql_error()); $ss=null;
            $data[$fn]=mysql_insert_id();
        }
        unset($data[$fn."_nv"]);
    }}
    $ss="UPDATE ".$this->ops['mysqlPrefix'].$tabName." SET";
		foreach($data as $k=>$v){ 
			if ($k!=$key){
				if (trim($v)!=""){
					$ss.=" $k='".mysql_real_escape_string($v)."',"; 
				} else if (isset($this->ops['acceptEmptyValues']) && in_array($k,$this->ops['acceptEmptyValues'])){
					$ss.=" $k=null,";
		}}}
    $ss=substr($ss,0,strlen($ss)-1);
    $ss.=" WHERE $key='".$data[$key]."';";
		// in case table has only key field (rest of fields are of file types) update NOT REQUIRED
		if (!(count($data)==1 && array_pop(array_keys($data))==$table->getKey()->getName()))
    	mysql_query($ss) or die(mysql_error());
    if ($needCleanUp) $this->cleanupMultiValues();
    foreach($table->getFields() as $field){//file/image handling
			if ($field->getProp("isFile")){ 
      	$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
        $last_id=$data[$key];
        $fn=$field->getName();
        if (isset($_FILES[$fn]) && $_FILES[$fn]['error']==0){
          $tmp=basename($_FILES[$fn]['name']);
          $ext=substr($tmp,$ps=strrpos($tmp,"."),strlen($tmp)-$ps);
          $imgfn =$folder."/".$tabName."_".$fn."_".$last_id.$ext;
          $imgToDel=glob($folder."/".$table->getName()."_".$fn."_".$last_id.".*");
          set_error_handler(array($this,"fileUploadErrorHandler"),E_WARNING);
          foreach($imgToDel as $itd) unlink($itd);
          move_uploaded_file($_FILES[$fn]['tmp_name'],$imgfn);
          restore_error_handler();
          if ($field->getProp("isImage")) $this->imageResize($imgfn,$field);
    }}}//file/image handling
  }
	/**
	 * getRecordById() retrieves record using the key supplied.
	 *
	 * @param Integer $id Identification number (Key) of the element
	 * @return Array Associative array where keys are field names and values are actual data
   * @todo Document "fileDescriptors" option 
	 */
  function getRecordById($id){
    $table=$this->getCurrentTable();
    $tabName=$table->getName();
    $values=array_fill(0,$table->getFieldsCount(),"");
    $ss="SELECT *";
    foreach($table->getFields() as $field){
      if ($field->getProp("omni")!==false){
        $fn=$field->getName();
        $ss.=",(SELECT data FROM ".
          $this->ops['mysqlPrefix'].$tabName.$this->ops['mysqlMultiSuffix'].$fn." WHERE ".
          $this->ops['mysqlPrefix'].$tabName.$this->ops['mysqlMultiSuffix'].$fn.".id=".
          $this->ops['mysqlPrefix'].$tabName.".$fn LIMIT 1) AS ".$fn."_val";
      }
    }
    $ss.=" FROM ".$this->ops['mysqlPrefix'].$tabName
      ." WHERE ".$table->getKey()->getName()."='$id'".
      " LIMIT 1;";
    $res=mysql_query($ss) or die(mysql_error());
    if (mysql_num_rows($res)!=0) $values=mysql_fetch_assoc($res);
    foreach($table->getFields() as $field){//file|image handling
      if ($field->getProp("isFile")){
      	$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
        $fn=$field->getName();
          $imgname=null;
          $imgfn=glob($folder."/".$tabName."_".$fn."_".$values[$table->getKey()->getName()].".*");
          if (!empty($imgfn)) $imgname=$imgfn[0]; else $imgname=null;
          if ($imgname){
						if ($field->getProp('isImage')) $values[$fn]="<img src='$imgname' alt='simg' />";
						else {
							$desc=$field->getNote();
							if (isset($this->ops['fileDescriptors']) && in_array($fn,array_keys($this->ops['fileDescriptors']))){
                if (isset($values[$this->ops['fileDescriptors'][$fn]])) $desc=$values[$this->ops['fileDescriptors'][$fn]];
                else $desc=$this->ops['fileDescriptors'][$fn];
							} $values[$fn]="<a href='$imgname' class='zx_att'>$desc</a>";
					}} else $values[$fn]=null;
    }}//file|image handling
    return $values;
  }//EOF
	/**
	 * getMultiItems retrives referenced values of the "multi" field.
	 *
	 * This is utility method used only by the engine itself. It is basically selects 
	 * all available values from the referenced list (multi field), the numeric values from 
	 * the main "multi" list are replaced by actual values retrieved by this method during 
	 * form or list creation.
	 *
	 * @param String $listName Name of the "multi" field.
	 * @return Array Array where keys are numeric indexes of the values at the reference table,
	 * values are actual string values.
	 * @tutorial ZenX.pkg#datamulti
	 */
  function getMultiItems($listName){
    $list=array();
		$ss="SELECT * FROM ".$this->ops['mysqlPrefix'].
			$this->getCurrentTable()->getName().$this->ops['mysqlMultiSuffix'].$listName;
		if ($this->getCurrentTable()->getFieldByName($listName)->getProp('extendable'))
			$ss.=" ORDER BY data ";
		$ss.=";";
    $res=mysql_query($ss) or die(mysql_error());
    if (mysql_num_rows($res)!=0) 
      while($row=mysql_fetch_assoc($res))
        $list[$row['id']]=$row['data'];
    return $list;
  }//EOF
	/**
	 * customMysqlRequest() is a wrapper for any user-defined MySQL request.
	 *
	 * This is convenience method for custom MySQL request, to be used during user-defined
	 * and/or user-overriden methods creation. If request did not matched any record null is returned.
	 *
	 * @param String $ss MySQL request
	 * @return Array Two layer array, where outer one is simple array where each cell contains
	 * associative array with key=>value pairs, where keys are field names. 
	 */
  function customMysqlRequest($ss){
    $res=mysql_query($ss) or die(mysql_error());
    $values=null;
    if (is_resource($res) && mysql_num_rows($res)!=0) 
      while($row=mysql_fetch_assoc($res))
        $values[]=$row;
    return $values;
  }//EOF
	/**
	 * getTotalRecords() returns number of total records in current table.
	 *
	 * @return Integer Number of total records in the current table.
	 */
  function getTotalRecords(){
    $ss="SELECT COUNT(*) FROM ".$this->ops['mysqlPrefix'].$this->getCurrentTable()->getName().";";
    $res=mysql_query($ss);
      return mysql_result($res,0);
	}//EOF
	/**
	 * getLastRequestTotalRecords() returns number of total records retrieved by last request.
	 *
	 * It is basically covenience method calling "SELECT FOUND_ROWS()".
	 *
	 * @return Integer Number of total records retrieved by last request.
	 */
  function getLastRequestTotalRecords(){
    $res=mysql_query("SELECT FOUND_ROWS();");
      return mysql_result($res,0);
  }//EOF
	/**
	 * getData() retrieves rows in accordance with the specified parameters.
	 *
	 * getData() is the core data retrieval method, that accepts multiple parameters and 
	 * returns rows matching them. Search is performed within the current table.
	 *
	 * @param Integer $rowLimit Maximum number of rows to be returned.
	 * @param Integer $startOffset Returned rows index offset.
	 * @param String $orderField Name of the field to order by.
	 * @param Boolean $reverseOrder Flag indicating whether to use descending results ordering
	 * @param Array $findParams Array of strings specifying MySQL search conditions. For example "name=Jonh".
	 * @return Array Two layer array where cells in outer array contain associative array with key=>value pairs,
	 * where keys are field names.
	 * @todo Implement file storage process (or amend image storage to file storage).
	 */
  function getData($rowLimit=null,$startOffset=null,
  $orderField=null,$reverseOrder=false,$findParams=null){
    $table=$this->getCurrentTable();
    $tabName=$table->getName();
    $ss="SELECT SQL_CALC_FOUND_ROWS *";
    foreach($table->getFields() as $field){
      if ($field->getProp("omni")!==false){
        $fn=$field->getName();
        $ss.=",(SELECT data FROM ".
          $this->ops['mysqlPrefix'].$tabName.$this->ops['mysqlMultiSuffix'].$fn." WHERE ".
          $this->ops['mysqlPrefix'].$tabName.$this->ops['mysqlMultiSuffix'].$fn.".id=".
          $this->ops['mysqlPrefix'].$tabName.".$fn LIMIT 1) AS ".$fn."_val";
      }
    }
    $ss.=" FROM ".$this->ops['mysqlPrefix'].$tabName." ";
		if (isset($findParams)) $ss.=" WHERE ".implode(" AND ",$findParams); 	
		if (!$orderField || $orderField=="") 
      $orderField=$table->getKey()->getName();
    $ss.=" ORDER BY $orderField";
    if ($reverseOrder) $ss.=" DESC";
    if ($rowLimit && !$startOffset) 
      $ss.=" LIMIT $rowLimit";
    if ($rowLimit && $startOffset)
      $ss.=" LIMIT $startOffset,$rowLimit";
    // echo 
    $ss.=";";
    $res=mysql_query($ss) or die(mysql_error());
    $once=false;
    foreach($table->getFields() as $f) $values[0][$f->getName()]=$f->getNote();
    if (mysql_num_rows($res)!=0) 
      while($row=mysql_fetch_assoc($res))
        $values[]=$row;
    foreach($table->getFields() as $field){//file|image handling
      if ($field->getProp("isFile")){
      	$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
        $fn=$field->getName();
        for($i=1;$i<count($values);$i++){
          $imgname=null;
          $imgfn=glob($folder."/".$tabName."_".$fn."_".$values[$i][$table->getKey()->getName()].".*");
          if (!empty($imgfn)) $imgname=$imgfn[0]; else $imgname=null;
          if ($imgname){
						if ($field->getProp('isImage')) $values[$i][$fn]="<img src='$imgname' alt='simg' />";
						else {
							$desc=$field->getNote();
							if (isset($this->ops['fileDescriptors']) && in_array($fn,array_keys($this->ops['fileDescriptors']))){
                if (isset($values[$i][$this->ops['fileDescriptors'][$fn]])) $desc=$values[$i][$this->ops['fileDescriptors'][$fn]];
                else $desc=$this->ops['fileDescriptors'][$fn];
							} $values[$i][$fn]="<a href='$imgname' class='zx_att'>$desc</a>";
					}} else $values[$i][$fn]=null;
				}
    }}//file|image handling
    return $values;
  }//EOF
	/**
	 * deleteRecords() method deletes records with the specified IDs from the current table.
	 *
	 * Additionaly to deletion of records from the current table this method invokes 
	 * {@link cleanupMultiValues()} and deletes images from image folder.
	 *
	 * @param String Comma separated list of ID's.
	 * @todo Implement file storage process (or amend image storage to file storage).
	 */
  function deleteRecords($idList){
    $table=$this->getCurrentTable();
    $ss="DELETE FROM ".$this->ops['mysqlPrefix'].$table->getName()." WHERE ".
      $table->getKey()->getName()." IN ($idList);";
    $res=mysql_query($ss) or die(mysql_error());
    $ss=null;
    $this->cleanupMultiValues();
    foreach($table->getFields() as $field){//file|image handling
      if ($field->getProp("isFile")){
      	$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
        $fn=$field->getName();
        $ids=explode(",",$idList);
        foreach($ids as $id){
          $imgname=null;
          $imgfn=glob($folder."/".$table->getName()."_".$fn."_".
            $id.".*");
          if (!empty($imgfn)) $imgname=$imgfn[0]; else $imgname=null;
          if ($imgname) unlink($imgname);
    }}}//file|image handling
  }//EOF
	/**
	 * destroyDataStorage() method deletes all tables from database and images related to these
	 * tables from image folder.
	 *
	 * This is convenience method to delete all data related to current class, mainly used for 
	 * debugging purposes, when class structure changes, not to trace all dependencies manually.
	 *
	 * @todo Implement file storage process (or amend image storage to file storage).
	 */
  function destroyDataStorage(){
    foreach($this->getAllTables() as $table){
      $ss="DROP TABLE IF EXISTS ".$this->ops['mysqlPrefix'].$table->getName().";";
      $res=mysql_query($ss) or die(mysql_error());
      $ss=null;
      foreach($table->getFields() as $field){
        $fn=$field->getName();
        if ($field->getProp("omni")!==false){
          $ss="DROP TABLE IF EXISTS ".$this->ops['mysqlPrefix'].$table->getName().
            $this->ops['mysqlMultiSuffix'].$fn;
          mysql_query($ss) or die(mysql_error());
          $ss=null;
        }
        if ($field->getProp("isImage")){//image handling
            $imgname=null;
            $imgfn=glob($this->ops['imageFolder']."/".$table->getName()."_".$fn."_*.*");
            if (!empty($imgfn)){
              foreach($imgfn as $imgname) unlink($imgname);
        }}//image handling
      }
    }
  }//EOF
	/**
	 * cleanupMultiValues() method deletes values from "multi" reference table that are not referenced 
	 * from the main "multi" field.
	 *
	 * This is utility method used by engine to clean up reference table from unused values.
	 */
  function cleanupMultiValues(){
    $table=$this->getCurrentTable();
    foreach($table->getFields() as $field){
      if ($field->getProp("omni")!==false && $field->getProp("extendable")){
        $fn=$field->getName();
        $ss="DELETE t1.*,t2.* FROM ".
          $this->ops['mysqlPrefix'].$table->getName().$this->ops['mysqlMultiSuffix'].$fn.
          " AS t1 LEFT OUTER JOIN ".$this->ops['mysqlPrefix'].$table->getName().
          " AS t2 ON t2.".$fn."=t1.id WHERE t2.".$fn." IS NULL;";
        mysql_query($ss) or die(mysql_error());
        $ss=null;
    }}
  }//EOF
	/**
   * setFindParameters() method creates mysql search request using search/filter parameters. 
   *
	 * This method must iterates through session variables and if variable with the name matching 
	 * to the one of the field names of current table is found, method creates mysql search parameter 
	 * and pass it to {@link MainEngineAbstract::addParameters()} method within the "findParameters" array.
	 *
	 * Please note that user-defined search fields set by 'customSearchFields' option must be handled manually.
	 */
  function setFindParameters(){
    //select only prefix vars and clean-off prefixes
    if (isset($_SESSION))
      if (isset($this->ops['sessionPrefix'])){
        foreach($_SESSION as $k=>$v){
          if (strpos($k,$this->ops['sessionPrefix'])!==false)
            $targets[str_replace($this->ops['sessionPrefix'],"",$k)]=$v;
        }
      } else { $targets=$_SESSION; }
    //main composition loop
    if (isset($targets)) foreach($targets as $k=>$v) if ($v){ 
      $v=mysql_real_escape_string($v);
      foreach($this->getCurrentTable()->getFields() as $field){
        if ($field->getName()==$k){ 
          if ($field->getProp("omni"))
          $params[]="$k IN (SELECT id FROM ".
            $this->ops['mysqlPrefix'].$this->getCurrentTable()->getName().
            $this->ops['mysqlMultiSuffix'].$k." WHERE data LIKE '$v%')";
          else $params[]="$k LIKE '$v%'";
    }}} // NOTE: customSearchFields must be handled manually
    if (isset($params) && $params)
      $this->addParameters(array("findParameters"=>$params));
  }//EOF
	/**
	 * isUnique() method determines whether the value supplied is unique.
	 * 
	 * This is mysql specific implementation of the parent method.
	 *
	 * @param String $fn Name of the field to be checked
   * @param String $value Value to be checked
   * @param Integer $keyVal Value of the Key field of the current Table
   * @return Boolean Aswer to whether this record value would be unique
	 */
  function isUnique($fn,$value,$keyVal){
    $table=$this->getCurrentTable();
    $ss="SELECT ".$table->getKey()->getName()." FROM ".$this->ops['mysqlPrefix'].$table->getName()
      ." WHERE $fn='$value';";
    $res=mysql_query($ss) or die(mysql_error());
    if (($res) && (mysql_num_rows($res)==0 || mysql_result($res,0)==$keyVal)) return true;
    else return false;
  }//EOF
}

/**
 * MySqlDataTypeUndefinedException is a custom exception thrown when no MySQL-specific data 
 * definition is avaliable for given data type.
 *
 * @see StorageEngineMysql::$storageDataTypes
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class MySqlDataTypeUndefinedException extends Exception{
  public function errorMessage(){
    $errorMsg = "<pre>\nZenX ERROR: Data type '".$this->getMessage()."' ".
      "is not defined at StorageEngineMysql class!\n".
      "Please add type definition to \$storageDataTypes array!\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
/**
 * MySqlMultiTypeUndefinedException is a custom exception thrown when no MySQL-specific data 
 * definition is avaliable for given "multi" data type.
 *
 * @see StorageEngineMysql::$storageMultiTypes
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class MySqlMultiTypeUndefinedException extends Exception{
  public function errorMessage(){
    $errorMsg = "<pre>\nZenX ERROR: Data type '".$this->getMessage()."' ".
      "is not defined at StorageEngineMysql class!\n".
      "Please add type definition to \$storageMultiTypes array!\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
/**
 * MySqlNoFolderCreateRightsException is a custom exception thrown when PHP can not create folders
 * for storing images.
 *
 * @tutorial ZenX.pkg#imageFolder
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class MySqlNoFolderCreateRightsException extends Exception{
  public function errorMessage(){
    $errorMsg = "<pre>\nZenX ERROR: Could not create image folder '".$this->getMessage()."' ".
      "at current location!\n".
      "Please correct PHP access rights for this folder!\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
