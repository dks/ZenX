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
 * StorageEngineHtmlForm class extends MainEngineAbstract class and implements
 * html form data sending from user to server, it does not have any storage though.
 *
 * @package ZenX
 * @author Konstatin Dvortsov
 */
abstract class StorageEngineHtmlForm extends MainEngineAbstract {
	/**
	 * Class constructor.
	 *
	 * Invokes parent class constructor.
	 *
	 * @see connect()
	 */
  function __construct(){
    parent::__construct();
  }//EOF
	/**
	 * setEngineParameters() method adds engine specific settings using 
	 * {@link MainEngineAbstract::setParameters()} method.
	 */
  function setEngineParameters(){
		$this->ops['listSearchable']=false;
	}//EOF
	/**
	 * createStorage() method performs all necessary routines to create tables from 
	 * {@link MainEngineAbstract::$tables} array.
	 *
	 * @throws MySqlNoFolderCreateRightsException
	 * @see MainEngineAbstract::setParameters()
	 * @see $storageDataTypes
	 * @see $storageMultiTypes
	 */
  function createStorage() {  }//EOF
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
            $data[$fn]=$data[$fn."_nv"];
        }}
        unset($data[$fn."_nv"]);
      }
    }
		$uploadedData=null;
    foreach($data as $k=>$v) if ($v!="") $uploadedData[$k]=$v;
    foreach($table->getFields() as $field){//file/image handling
			if ($field->getProp("isFile")){ 
      	$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
        $fn=$field->getName();
        if (isset($_FILES[$fn]) && $_FILES[$fn]['error']==0){
          $tmp=basename($_FILES[$fn]['name']);
          $ext=substr($tmp,$ps=strrpos($tmp,"."),strlen($tmp)-$ps);
          $imgfn =$folder."/".$tabName."_".$fn.$ext;
          $imgToDel=glob($folder."/".$table->getName()."_".$fn.".*");
          set_error_handler(array($this,"fileUploadErrorHandler"),E_WARNING);
          foreach($imgToDel as $itd) unlink($itd);
          move_uploaded_file($_FILES[$fn]['tmp_name'],$imgfn);
          restore_error_handler();
          if ($field->getProp("isImage")) $this->imageResize($imgfn,$field);
					$uploadedData[$fn]=$imgfn;
    }}}//file/image handling
		$this->saveData($uploadedData);
  }//EOF

	abstract function saveData($data);
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
	 * @todo Implement file storage process (or amend image storage to file storage).
	 */
  function updateRecord($data){ }//EOF
	/**
	 * getRecordById() retrieves record using the key supplied.
	 *
	 * @param Integer $id Identification number (Key) of the element
	 * @return Array Associative array where keys are field names and values are actual data
	 * @todo Implement file storage process (or amend image storage to file storage).
	 */
  function getRecordById($id){ }//EOF
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
  function getMultiItems($listName){ }//EOF
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
	/**
	 * getTotalRecords() returns number of total records in current table.
	 *
	 * @return Integer Number of total records in the current table.
	 */
  function getTotalRecords(){ }//EOF
	/**
	 * getLastRequestTotalRecords() returns number of total records retrieved by last request.
	 *
	 * It is basically covenience method calling "SELECT FOUND_ROWS()".
	 *
	 * @return Integer Number of total records retrieved by last request.
	 */
  function getLastRequestTotalRecords(){ }//EOF
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
    foreach($this->getCurrentTable()->getFields() as $f) $values[0][$f->getName()]=$f->getNote();
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
  function deleteRecords($idList){ }//EOF
	/**
	 * destroyDataStorage() method deletes all tables from database and images related to these
	 * tables from image folder.
	 *
	 * This is convenience method to delete all data related to current class, mainly used for 
	 * debugging purposes, when class structure changes, not to trace all dependencies manually.
	 *
	 * @todo Implement file storage process (or amend image storage to file storage).
	 */
  function destroyDataStorage(){ }//EOF
	/**
   * setFindParameters() method creates mysql search request using search/filter parameters. 
   *
	 * This method must iterates through session variables and if variable with the name matching 
	 * to the one of the field names of current table is found, method creates mysql search parameter 
	 * and pass it to {@link MainEngineAbstract::addParameters()} method within the "findParameters" array.
	 *
	 * Please note that user-defined search fields set by 'customSearchFields' option must be handled manually.
	 */
  function setFindParameters(){ }//EOF
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
  function isUnique($fn,$value,$keyVal){ }//EOF
	function runFullCycle(){
		$this->runInputCycle();
	}
  function runInputCycle(){
    $vars=$this->retrieveVars();
    if ($vars) $this->validateVars($vars);
    $id=null; $p=is_null($vars)?"VIEW":"SAVE";
    if ($p=="SAVE" && !empty($this->inputErrors)) $p="IERR";
    if (!empty($this->inputNotifications)) $this->reportNotifications();
		switch($p){
			default:
			case "VIEW":$this->printHtmlForm($id); break;
			case "SAVE": $this->addNewRecord($vars); break;
			case "IERR": $this->reportErrors(); 
									 $this->printHtmlForm($id,$vars); break;
		}
	}
}
