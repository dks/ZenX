<?php
/**
 * "ZenX" PHP data manipulation engine.
  *
 * @author Konstantin Dvortsov <kostya.dvortsov@gmail.com>. You can 
 * also track me down at {@link http://dvortsov.tel dvortsov.tel }
 * @version 1.0
 * @package ZenX 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
/**
 * MainEngineAbstract class is the core of ZenX library.
 *
 * It contains most of the methods not related to particular storage engine.
 * It is mainly dealing with data verification, session variables and data rendering.
 * Three main things to start from is the class constructor, {@link runFullCycle()} method and
 * ops variable that contains current class settings.
 * 
 * One thing to mention is the image handling. It is actually must be under storage engine class, 
 * however I failed to separate it from main class, so it may be a job for future improvements.
 * Ideally storage engine could implement sockets and/or save images directly to data base/ 
 * storage file, but this implementation relies heavily on standard PHP functions so files are 
 * uploaded to server via standard HTML forms first and handled thereafter. Current implementation
 * of storage engine {@link StorageEngineMysql} only saves file name at the data base and the file
 * itself is kept at server.
 *
 * @author Konstatin Dvortsov
 * @see runFullCycle()
 * @see ops
 * @package ZenX
 */
abstract class MainEngineAbstract implements Signs{
  /**
   * Tables variable contains array of table objects.
   *
   * @see Table
   * @var Array Array of Table objects
   */
  protected $tables=array();
  /**
   * Ops variable is an array of miscellaneous setup parameters.
   *
   * Ops variable is one of the key elements of MainEngineAbstract class. It 
   * contains list of class setting variables which define most of its behavior
   * and visual appearance. Thought it is not directly accessible it can be easily
   * modified by setParameters() and addParameters() methods. In case you need to
   * see its contents for debugging purposes you can use dumpParameters() method.
   * Since list of possible options is quite long, possible parameter values are
   * shown in a separate file.
   *
   * @see setParameters()
   * @see addParameters()
   * @see dumpParameters()
   * @see resetParameters()
   * @tutorial ZenX.pkg#options
   * @var Array Multi-dimensional associative array of engine settings/properties
   */
  protected $ops=array();
  /**
   * currentTable variable contains current Table object.
   *
   * Current table contains a Table object to which all actions and settings will be
   * applied. 
   * 
   * @see setCurrentTable()
   * @see getCurrentTable()
   * @var Table Contains current Table object
   */
  protected $currentTable=null;
  /**
   * inputError variable contains array with data verification errors.
   *
   * At the phase of data acceptance prior to saving data is verified for correctness.
   * Any errors found during data verification are saved to this array. If this variable
   * is not empty data is not saved to storage and returned to user instead.
   *
   * @see reportErrors()
   * @see validateVars()
   * @see $inputNotifications
   * @var Array Nested associative array where first key is a Table description (not Name!),
   * second key is a sequential number and value is the text error description.
   */
  protected $inputErrors=null;
  /**
   * inputNotifications variable contains array with data verification warnings.
   *
   * At the phase of data acceptance prior to saving data is verified for correctness.
   * Any minor errors are automatically corrected and notification describing what was
   * changed is saved to this array. Non-empty notification array does not prevent data 
   * from saving into storage.
   *
   * @see reportNotifications()
   * @see validateVars()
   * @see $inputErrors
   * @var Array Nested associative array where first key is a Table description (not Name!),
   * second key is a sequential number and value is the text notification.
   */
  protected $inputNotifications=null;
  /**
   * curRec is variable for temporary storage of the record set.
   *
   * curRec variable is used for temporary storage of the record set retrieved by last 
   * getCurrentRecord() call. It is used to prevent duplicate calls for accessing the same data.
   *
   * @see getCurrentRecord()
   * @var Array Associative array with keys containing field names and values containing 
   * record values.
   */
  protected $curRec=null;
  /**
   * deletedIds variable contains list of ID's of the recently deleted records.
   *
   * deletedIds is a comma-separated list the ID's of the records deleted by last deleteItems() 
   * call. Though it is used within the deleteRecords() function for a temporary storage there
   * is no real need in it. It's main purpose is to be able to get the list of ID's from outside
   * of the engine for user-definded handling.
   *
   * @see deleteRecords()
   * @see deleteItems()
   * @var String Comma separated list of deleted records ID's.
   */
  public $deletedIds=null;
  /**
   * __construct() is the main class constructor.
   *
   * This is the main class constructor which basically starts session, sets up engine
   * parameters and does some minor data definition validation.
   *
   * @see resetParameters()
   * @see verifyImageFields()
   */
  function __construct(){
    @session_start();
    $this->resetParameters();
    $this->verifyImageFields();
  }
  /**
   * __destruct() is an empty implementation of the class destructor.
   * 
   * You may want to override this method in order to free up some memory for large projects.
   */
  function __destruct(){}
  /**
   * setParameters() is a convenience method for setting engine parameters.
   *
   * One of the key difference from the addParameter() method is that it overrides
   * values of the parameters with the same name.
   *
   * @tutorial ZenX.pkg#options
   * @see $ops
   * @see addParameters()
   * @param Array $params Associative array with key=>value pairs where value can be another array,
   * depending on the parameter type. For details see options tutorial.
   */
  function setParameters($params){ 
    if (!empty($params))foreach($params as $k=>$v)
      $this->ops[$k]=$v;
  }//EOF
  /**
   * addParameters() is a convenience method for setting engine parameters.
   *
   * One of the key difference from the setParameter() method is that it does not override
   * values of the parameters with the same name, but append new values instead. Its typically
   * used to add more record search parameters, thus narrowing the list of matches.
   *
   * @tutorial ZenX.pkg#options
   * @see $ops
   * @see setParameters()
   * @see setNavigationParameters()
   * @param Array $params Associative array with key=>value pairs where value can be another array,
   * depending on the parameter type. For details see options tutorial.
   */
  function addParameters($params){ 
    if (!empty($params))foreach($params as $k=>$v)
      if (!empty($this->ops[$k]) && is_array($this->ops[$k]))
        $this->ops[$k]=array_merge($this->ops[$k],$v);
      else $this->ops[$k]=$v;
  }//EOF
  /**
   * dumpParameters() is a convenience method for viewing the content of $ops variable.
   *
   * Parameters dump is used mainly for debugging purposes at the development phase.
   *
   * @see $ops
   */
  function dumpParameters(){
    echo "<pre>"; var_dump($this->ops); echo "</pre>";
  }//EOF

  /**
   * resetParameters() function empties $ops variable and fills it up with default settings.
   *
   * Make sure you call this method if you use more than one table on the same page. You may
   * want to override this function to have different default settings. This method is called
   * at the class constructor. This method calls setEngineParameters() method.
   *
   * @see $ops
   * @see setParameters()
   * @see addParameters()
   * @see __construct()
   * @see setEngineParameters()
   */
  function resetParameters(){
    $this->ops=array();
    $this->setParameters(array(
      "sessionPrefix"=>"ZX_",
      "listType"=>0,
      "formType"=>0,
      "listShowKeys"=>true,
      "formShowKeys"=>true,
      "listViewable"=>true,
      "listDeletable"=>true,
      "listSearchable"=>true,
      "imageFolder"=>"../img",
      "fileFolder"=>"../dat",
      "recordsPerPage"=>50
    ));
    $this->setEngineParameters();
  }//EOF
  /**
   * setCurrentTable() method assigns a Table object to currentTable variable.
   *
   * Due to the nature of PHP language since Table object pre-exists it is 
   * passed by reference.
   *
   * @see $currentTable
   * @see getCurrentTable()
   * @param Table $table Table object to be selected as current
   */
  function setCurrentTable($table){ 
    $this->currentTable=$table;
  }//EOF
  /**
   * getCurrentTable() method returns Table object from currentTable variable.
   *
   * @see $currentTable
   * @see setCurrentTable()
   * @see Table
   * @return Table Table object marked as current
   */
  function getCurrentTable(){ 
    try {
      if ($this->currentTable==null)
        throw new NoCurrentTableException();
    } catch (Exception $e){
      echo $e->errorMessage();
      exit(125);
    }
    return $this->currentTable; 
  }//EOF
  /**
   * registerTable() method adds a Table object to $tables array and sets it current.
   *
   * @see $tables
   * @see setCurrentTable()
   * @param Table $table Table object to be registered.
   */
  function registerTable($table){ 
    $this->tables[]=$table; 
    $this->setCurrentTable($table);
  }//EOF
  /**
   * getAllTables() method returns $tables array containing all Table objects registered 
   * at this instance.
   *
   * @see $tables
   * @see registerTable()
   * @return Array Array with Table objects
   */
  function getAllTables(){ return $this->tables; }
  /**
   * getTableByName() method returns Table object with the specified name.
   *
   * This function basically iterates through $tables array and returns Table 
   * object this the name matching a string specified as parameter.
   *
   * @see $tables
   * @see Table
   * @param String $tableName Name of the table to be retrieved.
   * @return Table Table object with the name as specified by parameter string
   */
  function getTableByName($tableName){
    foreach($this->tables as $table)
      if ($table->getName()==$tableName)
        return $table;
  }//EOF
  /**
   * getCurrentRecord() method returns current record if it exists.
   *
   * This method is typically used during the input phase and contains
   * the data array of the record currently accessed. One of the main purposes
   * of this method and its difference with getRecordById() is that it saves 
   * the record to memory to prevent redundant accesses to data storage.
   *
   * @return Array Associative array where keys are field names and values are
   * record values
   * @tutorial ZenX.pkg#inphase
   * @see getRecordById()
   */
  function getCurrentRecord(){
    if ($this->curRec) return $this->curRec;
    else {
      $id=(isset($_REQUEST['ZX_TARGET']) && 
        is_numeric($_REQUEST['ZX_TARGET']))?
        $_REQUEST['ZX_TARGET']:null;
      if (is_null($id)){ 
        $vars=$this->retrieveVars();
        if (isset($vars[$key=$this->getCurrentTable()->getKey()->getName()])
        && is_numeric($vars[$key])) $id=$vars[$key];
      }
      if ($id) return $this->curRec=$this->getRecordById($id);
      else return null;
    }
  }//EOF
  /**
   * dumpTables() is a convenience method showing $tables array contents for debigging purposes.
   *
   * @see $tables
   */
  function dumpTables() { echo "\n<pre>\n"; var_dump($this->tables); echo "\n</pre>\n";  }
  /**
   * getRecordById() method returns specific record and must be overrided by storage engine class.
   *
   * At the MainEngineAbstract class this method is not implemented. However in data storage engines
   * this method must access the data storage and retrieve record specified by ID given as a parameter.
   * The format of the return result must be and associative array where key is a field name and value
   * is the value of that field taken from the data storage for this specific record.
   *
   * @see Field
   * @param String|int $id Identification number (Key) of the record to be retrieved.
   */
  abstract function getRecordById($id);
  /**
   * setEngineParameters() method implements storage engine specific variables setup.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must set-up necessary Storage Engine specific parameters. This method is called from the
   * resetParameters() method after setParameters() call, so it will override default parameters if
   * parameter names of the MainEngineAbstract class and storage engine class are the same.
   *
   * @return Array Associative array where keys are field names and values are record values
   */
  abstract function setEngineParameters();
  /**
   * addNewRecord() method adds a record to data storage.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must append (insert) new data row (record) to the storage. All data is verified before 
   * it is passed to this method, so there is no need in data check.
   *
   * @see updateRecord()
   * @see deleteRecords()
   * @see validateVars()
   * @param Array $data Associative array with key=>value pairs where key is a field name and value
   * is the data to be saved.
   */
  abstract function addNewRecord($data);
  /**
   * updateRecord() method changes values of the record fields at data storage.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must change (update) existing data row (record). All data is verified before 
   * it is passed to this method, so there is no need in data verification. This method, however,
   * must check $data array for existence of ID (Key) field, indicating what record to alter and
   * throw {@link NoUpdateKeySuppliedException} if not found.
   *
   * @see addNewRecord()
   * @see deleteRecords()
   * @see validateVars()
   * @param Array $data Associative array with key=>value pairs where key is a field name and value
   * is the data to be saved. 
   * @throws NoUpdateKeySuppliedException
   * @return int Key (ID) of the record in data storage
   */
  abstract function updateRecord($data);
  /**
   * deleteRecords() method erases specified records at data storage.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must erase (delete) all records with the ids, specified as parameter.
   *
   * @see deleteItems()
   * @see addNewRecord()
   * @see updateRecord()

   * @param String $idList Comma-separated list of ID's (Keys) of the records to be deleted.
   */
  abstract function deleteRecords($idList);
  /**
   * getTotalRecords() method returns total number of records at the current table at data storage.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must return total number of records from the current table at data storage. This method is 
   * not used by ZenX engine and is supplied only for user convenience.
   *
   * @return Integer Total number of current table records.
   */
  abstract function getTotalRecords();
  /**
   * getLastRequestTotalRecords() method returns total number of records matching filtering parameters.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must return total number of records matching filtering parameters regardless of start and 
   * end offset. It is basically analogue of mysql "SELECT FOUND_ROWS()" function. This function is used by
   * ZenX engine for pagination.
   *
   * @return Integer Total number of records matching predefined search parameters

   */
  abstract function getLastRequestTotalRecords();
  /**
   * destroyDataStorage() method destroys (erases) all tables of the class instance at data storage.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must implemend all nessessary routines to erase all tables of the current class instance 
   * that called this method. Typically it is used only during development phase in order to erase 
   * unneeded data.
   *

   * @see createStorage()
   */
  abstract function destroyDataStorage();
  /**
   * setFindParameters() method retrieves search/filter parameters from the class instance and 
   * creates data engine specific request. 
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must iterate through session variables and if variable with the name matching to the
   * one of the field names of current table is found method must create data storage engine
   * specific search parameter and pass it to {@link MainEngineAbstract::addParameters()} method
   * within the "findParameters" array. 
   *
   * @see addParameters()
   * @see setNavigationParameters()
   */
  abstract function setFindParameters();
  /**
   * createStorage() method creates data storage.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must implement all necessary routines in order to create (not connect to existing, but
   * to create a non-existent) data storage.
   * 

   * @see destroyDataStorage()
   */
  abstract function createStorage();
  /**
   * getMultiItems() method retrieves all records from the table linked to the field with the name
   * specified at the parameter.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must retrieve all records from the table linked to the field.
   *
   * @tutorial ZenX.pkg#datamulti
   * @param String $listName Name of the field with linked table.
   * @return Array Associative array where key is linked table record ID and value
   * is record value. 
   */
  abstract function getMultiItems($listName);
  /**
   * getData() is one of the core methods; it retrieves records from the data storage confirming 
   * specified parameters.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must retrieve records from the current table matching the specified criteria.
   *
   * @param Integer $rowLimit Maximum number of records to be retrieved
   * @param Integer $startOffset Starting offset within the number of records to be returned
   * @param String $orderField Name of the field to order by
   * @param Boolean $reverseOrder Flag specifying whether to use descending order
   * @param String $findParams Search/filtering criteria
   * @see setFindParameters()
   * @see addParameters()
   * @see showDefaultList()
   */
  abstract function getData($rowLimit=null,$startOffset=null,
  $orderField=null,$reverseOrder=false,$findParams=null);
  /**
   * isUnique() method determines whether value supplied to the field specified by parameter
   * at the current table would be unique.
   *
   * At the MainEngineAbstract class this method is not implemented. In data storage engine this 
   * method must request data storage to check whether there are any records containing same value
   * at the specific field. If there are no such records or it is record with the same key as supplied
   * (in case of update to the existing record) then it should return true, or return false otherwise.
   *
   * @param String $fieldName Name of the field to be checked
   * @param String $value Value to be checked
   * @param Integer $keyValue Value of the Key field of the current Table
   * @return Boolean Aswer to whether this record value would be unique
   */
  abstract function isUnique($fieldName, $value, $keyValue);
  /**
   * This is an initialization function with empty implementation. 
   *
   * Use is at your own convenience. Since it is one of the first functions that 
   * is called during the page creation its typical uses can be security check, 
   * setting variables preparation, session cookies hanling and so on. If you call
   * it manually before main loop and do not need it to be called again, it can be
   * disabled by setting "noInit" parameter to true.
   */
  function initialize(){}
  /**
   * showCustomList() method is a user-defined implementation of showList() method.
   *
   * At the MainEngineAbstract class this method has empty implementation. It must be 
   * overriden by subclass in order to be used. If you want engine to call this method
   * instead of the default one you must set "listType" parameter to 1 (default is 0). 
   *
   * @see showList()
   * @see showDefaultList()
   * @see setParameters()
   */
  function showCustomList(){}
  /**
   * printCustomHtmlForm() method is a user-defined implementation of printHtmlForm() method.
   *
   * At the MainEngineAbstract class this method has empty implementation. It must be 
   * overriden by subclass in order to be used. If you want engine to call this method
   * instead of the default one you must set "formType" parameter to 1 (default is 0). 
   *
   * @see printHtmlForm()
   * @see printDefaultHtmlForm()
   * @see setParameters()
   */
  function printCustomHtmlForm($id,$override=null){} 
  /**
   * getClientInfo() method obtains user agent information using javascript.
   *
   * This method is among the first ones to be called at the {@link runFullCycle()}
   * method. It prints javascript that obtains user environment variables and redirects
   * back to server. Once recieved by server those variables are saved to PHP $_SESSION
   * array. On consequent page openings since ZX_CLINFO session variable is set, no 
   * javascript printing / redirection occurs. Thus changes to client variable during
   * the same session will not be captured.
   *
   * By default this function is off, in order to turn it on you must set "clientInfoOn" 
   * parameter to true. 
   *
   * Below is the list of the user evironment session variables. You may want to override
   * this function to get more information.
   *
   * ZX_CLINFO_browser_code<br />
   * ZX_CLINFO_browser_name<br />
   * ZX_CLINFO_browser_version<br />
   * ZX_CLINFO_platform<br />
   * ZX_CLINFO_user_agent_header<br />
   * ZX_CLINFO_screen_width<br />
   * ZX_CLINFO_screen_height<br />
   * ZX_CLINFO<br />
   *
   * @see setParameters()
   * @see runFullCycle()
   */
  function getClientInfo(){
    if (!isset($_SESSION['ZX_CLINFO'])){
      if (empty($_REQUEST['ZX_CLINFO'])){
        ?>
          <form name='ZX_CLIENT_INFO' method='post' action='<?php 
            echo htmlspecialchars($_SERVER['PHP_SELF']); ?>'>
          <input type='hidden' name='ZX_CLINFO' value='on' />
          <input type='hidden' name='ZX_CLINFO_browser_code' value='' />
          <input type='hidden' name='ZX_CLINFO_browser_name' value='' />
          <input type='hidden' name='ZX_CLINFO_browser_version' value='' />
          <input type='hidden' name='ZX_CLINFO_platform' value='' />
          <input type='hidden' name='ZX_CLINFO_user_agent_header' value='' />
          <input type='hidden' name='ZX_CLINFO_screen_width' value='' />
          <input type='hidden' name='ZX_CLINFO_screen_height' value='' />
          </form>
          <script language="javascript"><!--
            var now = new Date();
            var time = now.getTimezoneOffset();
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_browser_code.value=navigator.appCodeName;
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_browser_name.value=navigator.appName;
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_browser_version.value=navigator.appVersion;
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_platform.value=navigator.platform;
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_user_agent_header.value=navigator.userAgent;
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_screen_width.value=screen.width;
            window.document.ZX_CLIENT_INFO.ZX_CLINFO_screen_height.value=screen.height;
            window.document.forms['ZX_CLIENT_INFO'].submit();
          -->
          </script>
        <?php 
      } else {
        $_SESSION['ZX_CLINFO_browser_code']=$_REQUEST['ZX_CLINFO_browser_code'];
        $_SESSION['ZX_CLINFO_browser_name']=$_REQUEST['ZX_CLINFO_browser_name'];
        $_SESSION['ZX_CLINFO_browser_version']=$_REQUEST['ZX_CLINFO_browser_version'];
        $_SESSION['ZX_CLINFO_platform']=$_REQUEST['ZX_CLINFO_platform'];
        $_SESSION['ZX_CLINFO_user_agent_header']=$_REQUEST['ZX_CLINFO_user_agent_header'];
        $_SESSION['ZX_CLINFO_screen_width']=$_REQUEST['ZX_CLINFO_screen_width'];
        $_SESSION['ZX_CLINFO_screen_height']=$_REQUEST['ZX_CLINFO_screen_height'];
        $_SESSION['ZX_CLINFO']=$_REQUEST['ZX_CLINFO'];
      }
    }
  }//EOF
  /**
   * getHtmlForm() method obtains a record and supplies values with names and relevant HTML tags.
   *
   * This is one of the core methods used during the data editing ("IN") phase. When viewing the
   * data it retrieves record from data storage using the ID supplied as parameter, if no ID 
   * supplied it returns form with HTML tag filled with no values or default values,if any. 
   * During the data saving phase if data verification error is found $override array is supplied
   * to the method that overrides values from the storage (if any), so the user could correct 
   * wrong data.
   *
	 * @tutorial ZenX.pkg#formCustomPreprocessing
   * @see printHtmlForm()
   * @param Integer $id ID (Key) of the record to be retrieved and processed.
   * @param Array $override Associative array where keys are table field names and values 
   * are record values.
   * @return Array Associative array where keys are table field descriptions (Not names!)
   * obtained by {@link Field::getNote()} method and values are record values wrapped by 
   * relevant HTML tags.
   */
  function getHtmlForm($id=null,$override=null){
    $form=array(); $values=null;
    $table=$this->getCurrentTable();
    if ($override) $values=$override;
    else if ($id) $values=$this->getRecordById($id);
    if ($values) $this->curRec=$values;
    foreach($table->getFields() as $field){
      $s=null;
      $omni=$field->getProp("omni");
      $fn=$field->getName();
      if ($this->isFieldEditable($fn)){
        if ($omni==false){ 
          $s = $field->getProp("sTag");
          $s.= $field->getName();
          $s.= $field->getProp("mTag");
          if ($field->getProp("selected")!=false){ //bools
            if (isset($values[$fn]) && $values[$fn]==1) $s.=$field->getProp("selected");
            else if (isset($this->ops['formDefaults']) && !isset($values) &&
              in_array($fn,array_keys($this->ops['formDefaults'])))
                $s.=$field->getProp("selected");
					} else { //text
						if (isset($values[$fn])){
							if ($field->getProp("isFile")) $s.="<label><input type='checkbox' name='${fn}_nv' />".Signs::FILEDELONUPDATE."</label>";
							$s.=$values[$fn];
						}	else if (isset($this->ops['formDefaults']) && !isset($values) &&
								in_array($fn,array_keys($this->ops['formDefaults'])))
							$s.=$this->ops['formDefaults'][$fn];
					}
          $s.= $field->getProp("eTag");
        }
        if ($omni=="multiple1"){
          $s = $field->getProp("prefix1");
          $s.= $field->getName();
          $s.= $field->getProp("prefix2");
          $s.= $field->getProp("sTag");
          $s.= $field->getProp("mTag");
          $s.= $field->getProp("eTag");
          $listItems = $this->getMultiItems($field->getName()); 
          foreach($listItems as $k => $v){
            $s.= $field->getProp("sTag");
            $s.= $k;
            if (isset($values[$fn])){
              if ($values[$fn]==$k) $s.= $field->getProp("selected"); 
            } else if (isset($this->ops['formDefaults']) && !isset($values) &&
              in_array($fn,array_keys($this->ops['formDefaults'])))
                if ($this->ops['formDefaults'][$fn]==$k) $s.=$field->getProp("selected");
            $s.= $field->getProp("mTag");
            $s.= $v;
            $s.= $field->getProp("eTag");
          }
          $s.= $field->getProp("postfix");
        }
        if ($omni=="multiple2"){
          $s ="";
          $listItems = $this->getMultiItems($field->getName()); 
          foreach($listItems as $k => $v){
            $s.= $field->getProp("sTag");
            $s.= $fn;
            if (isset($values[$fn])){
              if ($values[$fn]==$k) $s.= $field->getProp("selected"); 
            } else if (isset($this->ops['formDefaults']) &&  !isset($values) &&
              in_array($fn,array_keys($this->ops['formDefaults'])))
                if ($this->ops['formDefaults'][$fn]==$k) $s.=$field->getProp("selected");
            $s.= $field->getProp("mTag");
            $s.= $k;
            $s.= $field->getProp("eTag");
            $s.= $v;
            $s.="<br />";
          }
        }
        if ($field->getProp("extendable")){
          $s.="<br /><input type='text' name='".$fn."_nv' />";
        }
      } else {
        if ($omni==false) {
          if (isset($values[$fn])) $s=nl2br($values[$fn]);
          else if (isset($this->ops['formDefaults']) && !isset($values) &&
            in_array($fn,array_keys($this->ops['formDefaults'])))
              $s=nl2br($this->ops['formDefaults'][$fn]);
        } else {
          $listItems = $this->getMultiItems($field->getName()); 
          foreach($listItems as $k => $v)
            if (isset($values[$fn]) && $values[$fn]==$k) $s=$v;
          if (empty($values[$table->getKey()->getName()]) && isset($this->ops['formDefaults']) && 
            in_array($fn,array_keys($this->ops['formDefaults']))){
            foreach($listItems as $k => $v)
              if ($this->ops['formDefaults'][$fn]==$k) $s=$v;
						}}
				if (isset($this->ops['formDefaultsWriteThrough']) &&
            in_array($fn,$this->ops['formDefaultsWriteThrough'])){
            $s.="<input type='hidden' name='$fn' value='".$this->ops['formDefaults'][$fn]."' />";
			}}
      if (!(isset($this->ops['formHiddenFields']) && in_array($fn,$this->ops['formHiddenFields'])))
      $form[$field->getNote()]=$s;
    }//EOL
    if (isset($this->ops['formCustomPreprocessing']))
     if (is_array($this->ops['formCustomPreprocessing'])){
      foreach($this->ops['formCustomPreprocessing'] as $formPP)
        if (method_exists($this,$formPP))
          $form=call_user_func(array($this,$formPP),$form);
     }else
       echo "\n<pre>\nZenX WARNING:'formCustomPreprocessing' option must be an Array!!\n</pre>\n";
    return $form;
  }//EOF
  /**
   * getCurrentPhase() method determines what phase script is in.
   *
   * This method is mainly to be used out of the class for user-defined behaviour, for example
   * it can be used to display different page headers depending on whether it is "OUT" phase
   * (data listing) or "IN" phase (data input). Please note the deletion ("DELETE") action is 
   * also considered to be "OUT" phase, since it is immediately followed by listing ("SHOW") action.
   *
   * @see showPhaseHeaders()
   * @tutorial ZenX.pkg#concepts
   * @tutorial ZenX.pkg#outphase
   * @tutorial ZenX.pkg#inphase
   * @return String "IN" or "OUT" word
   */
  function getCurrentPhase(){
    $a=isset($_REQUEST['ZX_ACTION'])?$_REQUEST['ZX_ACTION']:"SHOW";
    switch($a){
      default:
      case "FIND": 
      case "DELETE": 
      case "SHOW": $phase="OUT"; break;
      case "VIEW": $phase="IN"; break;
    }
    return $phase;
  }//EOF
  /**
   * showPhaseHeaders() method outputs user defined headers at the specific phase.
   *
   * If user sets parameters "inPhaseHeader" or "outPhaseHeader" this function will 
   * output relevant header, depending on the current phase. This function is called
   * automatically from the {@link runFullCycle() } method.
   *
   * @see getCurrentPhase()
   * @see showPhaseFooters()
   */
  function showPhaseHeaders(){
    $ph=$this->getCurrentPhase();
    if (isset($this->ops['inPhaseHeader']) && $ph=="IN") 
      echo $this->ops['inPhaseHeader'];
    if (isset($this->ops['outPhaseHeader']) && $ph=="OUT")
      echo $this->ops['outPhaseHeader'];
  }//EOF
  /**
   * showPhaseFooters() method outputs user defined footer at the specific phase.
   *
   * If user sets parameters "inPhaseFooter" or "outPhaseFooter" this function will 
   * output relevant footers, depending on the current phase. This function is called
   * automatically from the {@link runFullCycle() } method.
   *
   * @see getCurrentPhase()
   * @see showPhaseHeaders()
   */
  function showPhaseFooters(){
    $ph=$this->getCurrentPhase();
    if (isset($this->ops['inPhaseFooter']) && $ph=="IN") 
      echo $this->ops['inPhaseFooter'];
    if (isset($this->ops['outPhaseFooter']) && $ph=="OUT")
      echo $this->ops['outPhaseFooter'];
  }//EOF
  /**
   * runFullCycle() method is the main ZenX method, it is a wrapper for all other actions.
   *
   * This method wraps all other method calls. Once you have completed class initialization 
   * and set-up you must call this method in order to run all actions.
   *
   * @see __construct()
   * @see setParameters()
   * @tutorial ZenX.pkg#concepts
   */
  function runFullCycle(){
    if (isset($this->ops['clientInfoOn']) && $this->ops['clientInfoOn']) $this->getClientInfo();
    if (!isset($this->ops['noInit']) || !$this->ops['noInit']) $this->initialize();
    $this->showPhaseHeaders();
    $this->setNavigationParameters();
    $a=isset($_REQUEST['ZX_ACTION'])?$_REQUEST['ZX_ACTION']:"SHOW";
    $this->setFindParameters(); 
    switch($a){
      default:
      case "FIND": 
      case "SHOW": $this->showList(); break;
      case "VIEW": $this->runInputCycle(); break;
      case "DELETE": $this->deleteItems(); $this->showList(); break;
    }
    $this->showPhaseFooters();
  }//EOF
  /**
   * runInputCycle() method is the main ZenX method for data input and editing.
   *
   * This method wraps other method calls related to data input and editing. In the first 
   * place it checks for any variables sumbitted to the script and if any it initiates 
   * variables check and saving section, otherwise it prints out data input form and tries
   * to fill it with values (if any).
   *
   * @see runFullCycle()
   * @see setParameters()
   * @tutorial ZenX.pkg#concepts
   */
  function runInputCycle(){
    $vars=$this->retrieveVars();
    if ($vars) $this->validateVars($vars);
		//echo "<pre>"; print_r($vars); echo "</pre><br />";
    $id=(isset($_REQUEST['ZX_TARGET']) && 
      is_numeric($_REQUEST['ZX_TARGET']))?
      $_REQUEST['ZX_TARGET']:null;
    $id=(is_null($id) && isset($vars[$key=$this->getCurrentTable()
      ->getKey()->getName()]) && is_numeric($vars[$key]))?$vars[$key]:$id;
    $p=is_null($vars)?"VIEW":"SAVE";
    if ($p=="SAVE" && !empty($this->inputErrors)) $p="IERR";
    if (!empty($this->inputNotifications)) $this->reportNotifications();
    switch($p){
      default:
      case "VIEW": $this->printHtmlForm($id); break;
      case "SAVE": if ($id) $this->updateRecord($vars); 
                   else $id=$this->addNewRecord($vars);
                   $this->printHtmlForm($id); break;
      case "IERR": $this->reportErrors(); 
                   $this->printHtmlForm($id,$vars); break;
    }
  }//EOF
  /**
   * reportErrors() method prints out errors found during data verification.
   *
   * This method prints out html table with class='zx_err' containing textual
   * description of the fields and error causes. Error descriptions are taken
   * from the Signs interface.
   *
   * @see reportNotifications()
   * @see Signs
   */
  function reportErrors(){
    echo "<table class='zx_err'><thead><tr><td colspan='2'>".
      Signs::SAVEERRHEADER."</td></tr></thead><tbody>";
    foreach($this->inputErrors as $k=>$subarr)
      foreach($subarr as $v)
      echo "<tr><td>".Signs::ERRORAT." '$k':</td><td>$v</td></tr>";
    echo "</tbody></table>";
  }//EOF
  /**
   * reportNotifications() method prints out warnings found during data verification.
   *
   * This method prints out html table with class='zx_wrn' containing textual
   * description of the fields and warning causes. Warning descriptions are taken
   * from the Signs interface.
   *
   * @see reportErrors()
   * @see Signs
   */
  function reportNotifications(){
    echo "<table class='zx_wrn'><thead><tr><td colspan='2'>".
      Signs::SAVENOTIFHEADER."</td></tr></thead><tbody>";
    foreach($this->inputNotifications as $k=>$subarr)
      foreach($subarr as $v)
      echo "<tr><td>".Signs::NOTEAT." '$k':</td><td>$v</td></tr>";
    echo "</tbody></table>";
  }//EOF
  /**
   * printHtmlForm() method calls default or user-defined method for data entry printout.
   *
   * This method is influenced by "formType" parameter, if its value is 0 (default) it calls
   * default method, 1 calls custom form printing method.
   *
   * @see printCustomHtmlForm()
   * @see printDefaultHtmlForm()
   * @see setParameters()
   */
  function printHtmlForm($id,$override=null){
    switch($this->ops['formType']){
      default:
      case 0: $this->printDefaultHtmlForm($id,$override); break;
      case 1: $this->printCustomHtmlForm($id,$override); break;
    }
  }//EOF
  /**
   * printDefaultHtmlForm() prints data entry form.
   *
   * This method prints out html "div" with class="zx_frm" which contains form and table with 
   * data entry fields. Default implementation of this method is highly customizable via engine
   * parameters.
   *
   * @see printHtmlForm()
   * @see setParameters()
   * @tutorial ZenX.pkg#formoptions
   */
  function printDefaultHtmlForm($id,$override=null){
    echo "<div class='zx_frm'><form action='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."'".
      " enctype='multipart/form-data' method='post'>".
      "<p><input type='hidden' name='ZX_ACTION' value='VIEW' /></p>".
      "<table><tbody>";
    foreach($this->getHtmlForm($id,$override) as $k=>$v){
      if (($tabKey=$this->getCurrentTable()->getKey()->getNote())==$k){
        $kf=$this->getCurrentTable()->getKey()->getName();
        if ($this->isFieldEditable($kf)){ $keybuffer=$v;  
          $value=str_replace(array("<input type='hidden' name='$kf' value='","' />"),"",$v);
        } else $value=$v;
        if ($this->ops['formShowKeys'])
          echo "<tr><th>$k</th><td>$value</td></tr>";
      }
      else echo "<tr><th>$k</th><td>$v</td></tr>";
    }
    if (isset($this->ops['formRelayParameters']))
      foreach($this->ops['formRelayParameters'] as $k => $v)
        echo "<input type='hidden' name='$k' value='$v' />";
		if ($this->isFieldEditable($this->getCurrentTable()->getKey()->getName())){
			echo "<tr class='zx_frb'><th colspan='2'>"; 
			if (isset($keybuffer)) echo $keybuffer;
			echo "<input type='submit' value='".Signs::SUBMIT
				."' title='".Signs::SUBMITHINT."' /></th></tr>";
		}
		echo "</tbody></table></form>";
    if (!isset($this->ops['hideFormBackLink']) || 
      (isset($this->ops['hideFormBackLink']) && !$this->ops['hideFormBackLink'])){
        if (!isset($this->ops['customFormBackLink'])){
          echo "<a href='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."?ZX_PAGE=";
          if (isset($_SESSION['ZX_PAGE'])) echo $_SESSION['ZX_PAGE']; else echo "0";
          if (isset($this->ops['formBackLinkModifier']))  echo "&amp;".$this->ops['formBackLinkModifier'];
          echo "' title='".Signs::BACKHINT."'>".Signs::BACK."</a>";
        } else {
         echo $this->ops['customFormBackLink'];
        }
       }
    echo "</div>";
  }//EOF
  /**
   * retrieveVars() method filters $_REQUEST array and obtains variables relevant to ZenX engine.
   *
   * This method will retrieve all request variables that have matching field name within ANY of 
   * the tables. It will also get user-defined field values set by parameters "customSearchFields"
   * and "formRelayParameters". This method is called by the following methods: runInputCycle(),
   * getCurrentRecord(), setNavigationParameters().
   *
   * @see setParameters()
   * @see validateVars()
   * @see runInputCycle()
   * @tutorial ZenX.pkg#formoptions
   * @return Array Associative array where key is variable name that has a match within field
   * names and value is the value submitted to engine.
   */
  function retrieveVars(){
    $allfields=array();
    foreach($this->getAllTables() as $table)
      foreach($table->getFields() as $field)
        $allfields[]=$field->getName();
    if (isset($this->ops['customSearchFields']))
      foreach($this->ops['customSearchFields'] as $k => $v)
        $allfields[]=$k;
    if (isset($this->ops['formRelayParameters']))
      foreach($this->ops['formRelayParameters'] as $k => $v)
        $allfields[]=$k;
    foreach($_REQUEST as $key => $val) 
      if (in_array($key,$allfields) or in_array(str_replace("_nv","",$key),$allfields)) $list[$key]=$val;
    if (!isset($list)) $list=null;
    return $list;
  }//EOF
  /**
   * isFieldEditable() method determines whether the specific field is allowed for editing.
   *
   * This is convenienve method that checks whether field is listed at "restrictedFields" parameter
   * and returns true if not (means it is allowed for editing).
   *
   * @param String $name Name of the field that needs to be checked wether it is editable
   * @return Boolean True if field is editable, false otherwise.
   */
  function isFieldEditable($name){
    if (isset($this->ops['restrictedFields'])) 
      $editable=!(in_array($name,$this->ops['restrictedFields'])?true:false); 
    else 
      $editable=true;
    return $editable;
  }//EOF
  /**
   * validateVars() method checkes and filters values submitted to engine against 
   * field settings and data types.
   *
   * This method is called immediately after retrieveVars() within the runInputCycle().
   * It accepts array returned by retrieveVars() method, and checks whether fields this
   * data intended for are allowed to be edited and applies data type specific filters
   * preventing storage engine specific data incompatibility and security issues. If some
   * errors are found they are added to notification and errors array and reportErrors()
   * and/or reportNotifications() methods are called.
   *
   * Behaviour of this function is highly influences by current settings, in particular by 
   * the following parameters: formDefaultsWriteThrough, restrictedFields, notNullFields,
   * uniqueFields and all data definition limitations.
   *
   * @see DataDefiner
   * @see isFieldEditable()
   * @see retrieveVars()
   * @see runInputCycle()
   * @see setParameters()
   * @see reportErrors()
   * @see reportNotifications()
   * @tutorial ZenX.pkg#formoptions
   * @param Array &$vars Associative array where keys are field names and values are
   * values submitted to engine.
   * @return Array Associative array where keys are field names and values are filtered
   * and sanitized data submitted to engine.
   */
  function validateVars(&$vars){
    $table=$this->getCurrentTable();
    if (!$this->isFieldEditable($table->getKey()->getName()))
      $this->inputErrors[$table->getKey()->getNote()][]=Signs::KEYBLOCKED;
    $allempty=true;
    foreach($table->getFields() as $field){
      $t=$field->getType();
      $fn=$field->getName();
      // 'selected' option for single fields implies boolean type
      // need special handler for 'false' values, since they are not submitted
      if ($field->getProp("omni")==false && $field->getProp("selected") && !isset($vars[$fn]))
        $vars[$fn]=false;
      foreach($vars as $k=>&$v){
        $v=trim($v); 
        if ($fn==$k){
          if (!$this->isFieldEditable($k) &&  
            !(isset($this->ops['formDefaultsWriteThrough']) && 
            in_array($fn,$this->ops['formDefaultsWriteThrough'])))
              $this->inputErrors[$field->getNote()][]=Signs::FIELDBLOCKED;

          if (isset($this->ops['notNullFields']) 
            && in_array($fn,$this->ops['notNullFields'])
            && empty($v))
              $this->inputErrors[$field->getNote()][]=Signs::CANTBENULL;

          if (isset($this->ops['uniqueFields']) 
            && in_array($fn,$this->ops['uniqueFields']))
              if (!$this->isUnique($fn,$v,$vars[$table->getKey()->getName()]))
                $this->inputErrors[$field->getNote()][]=Signs::MUSTBEUNIQUE;

          $func=$this->getFilterName($t);
          if ($field->getProp("extendable")){
            if(!empty($vars[$k."_nv"])){
              $filterReport=array("VAL"=>$vars[$k."_nv"],"ERR"=>array(),"WARN"=>array());
              if (method_exists("DataDefiner",$func))
                $filterReport=call_user_func(array("DataDefiner",$func),$vars[$k."_nv"]);
              $vars[$k."_nv"]=$filterReport['VAL'];
            }
          } else {
            $filterReport=array("VAL"=>$v,"ERR"=>array(),"WARN"=>array());
            if (method_exists("DataDefiner",$func))
              $filterReport=call_user_func(array("DataDefiner",$func),$v);
            $v=$filterReport['VAL'];
            if (!empty($v)) $allempty=false;
          }
          foreach($filterReport['ERR'] as $error) $this->inputErrors[$field->getNote()][]=$error;
          foreach($filterReport['WARN'] as $msg) $this->inputNotifications[$field->getNote()][]=$msg;
        }
      }// end of data array iteration
			if ($field->getProp("isFile")){//files and images upload check
				if (isset($vars[$fn."_nv"])){//if file is set to be deleted
      		$folder=$field->getProp("isImage")?$this->ops['imageFolder']:$this->ops['fileFolder'];
					unset($vars[$fn.'_nv']);
					$filename=null;
					$filn=glob($folder."/".$table->getName()."_".$fn."_".$vars[$table->getKey()->getName()].".*");
					if (!empty($filn)) $filename=$filn[0]; else $filename=null;
					if ($filename) unlink($filename);
				}
				if (isset($_FILES[$fn])){
					if ($_FILES[$fn]['error']!=0 && $_FILES[$fn]['error']!=4){
						switch($_FILES[$fn]['error']){
							case 1: $e=Signs::FUEEXCEEDSERV; break;
							case 2: $e=Signs::FUEEXCEEDFORM; break;
							case 3: $e=Signs::FUEPARTIAL; break;
							case 6: $e=Signs::FUENOTMPDIR; break;
							case 7: $e=Signs::FUECANTWRITE; break;
							case 8: $e=Signs::FUEEXTERR; break;
							default: $e=$_FILES[$fn]['error']; break;
						} $this->inputNotifications[$field->getNote()][]=$e;
					} 
					// in case table has only key field (rest of fields are of file types)
					if (count($vars)==1 && array_pop(array_keys($vars))==$table->getKey()->getName())
					if ($_FILES[$fn]['error']==0) $allempty=false;
				}
				$settings=DataDefiner::$dataTypes[$field->getType()];
				if (@filesize($_FILES[$fn]['tmp_name'])>$settings['maxBytes']){
					$this->inputNotifications[$field->getNote()][]=Signs::FILESIZETOOBIG;
					unlink($_FILES[$fn]['tmp_name']);
					unset($_FILES);
				}
			}
    }// end of table fields iteration
    if ($allempty) $this->inputErrors[Signs::GENSAVEERR][]=Signs::GENSAVEERRTXT;
    return $vars;
  }
  /**
   * getFilterName() creates data filtering method name according to standard rules.
   *
   * This is small covenience method that creates data filtering method name that is
   * assumed to be within DataDefiner class  according to the rules  based on input
   * string. Called from validateVars() method.
   *
   * @param String Field name
   * @return String Data filter name
   * @see DataDefiner
   * @see validateVars()
   */
  function getFilterName($string){
    $ext=substr($string,1,strlen($string)-1);
    $begin=substr($ext,0,1);
    $end=substr($ext,1,strlen($ext)-1);
    $ext=$begin.strtolower($end);
    return "filter".$ext;
  }//EOF
  /**
   * deleteItems() creates list of items to be deleted from the form submitted data.
   *
   * This is small convenience method that retrieves list of ids from specifically 
   * formatted form submission data. It forms then a comma-separated list of ID's 
   * to be deleted that is passes as parameter to deleteRecords() method and at the
   * same time sets deletedIds variable.
   *
   * @see deleteRecords()
   * @see deletedIds
   */
  function deleteItems(){
    $pattern="ZX_".$this->getCurrentTable()->getName()."_DEL_";
    foreach($_REQUEST as $k => $v)
      if (strpos($k,$pattern)!==false) 
        $itemsToDelete[]=substr($k,strlen($pattern),strlen($k));
    if (isset($itemsToDelete))
      $this->deleteRecords($this->deletedIds=implode($itemsToDelete,","));
  }//EOF
  /**
   * showDefaultList() method implements all data preparation routines and outputs the
   * data list (table).
   *
   * This is one of the core methods of the MainEngineAbstract class during the "OUT" phase.
   * It is reponsible for implementation of setting-dependant behaviour, data output 
   * preprocessing and visualization. Once all preparations are completed it outputs 
   * table with class='zx_lst'.
   *
   * @tutorial ZenX.pkg#outphase
   * @tutorial ZenX.pkg#listoptions
   * @see setParameters()
   * @see showList()
   */
  function showDefaultList(){
    $table=$this->getCurrentTable();
    $orderField=isset($_SESSION['ZX_SORT'])?trim(strip_tags($_SESSION['ZX_SORT'])):null;
    if (!$orderField) $orderField=isset($this->ops['orderBy'])?$this->ops['orderBy']:null;
    $reverseOrder=isset($_SESSION['ZX_SORD'])?(bool)$_SESSION['ZX_SORD']:null;
		if ($reverseOrder===null) 
			$reverseOrder=isset($this->ops['reverseOrder'])?(bool)$this->ops['reverseOrder']:false;
    if (isset($this->ops['ignoreSortParameters']) && $this->ops['ignoreSortParameters']==true)
    { $orderField=null; $reverseOrder=false; }
    $findParams=isset($this->ops['findParameters'])?$this->ops['findParameters']:null;
    if (isset($this->ops['ignoreFindParameters']) && $this->ops['ignoreFindParameters']==true)
    { $findParams=null; }
    if (isset($this->ops['customGetData']) && method_exists($this,$this->ops['customGetData'])){
      $this->customOrderField=$orderField;
      $this->customReverseOrder=$reverseOrder;
      $data=call_user_func(array($this,$this->ops['customGetData']));
      //Custom request jumps over standard preprocessing
    }else{// standard preprocessing start
      if (!isset($this->ops['noPages']) || !$this->ops['noPages']){
        $pg=isset($_SESSION['ZX_PAGE'])?$_SESSION['ZX_PAGE']:null;
        $soff=$this->ops['recordsPerPage']*$pg;
        $data=$this->getData($this->ops['recordsPerPage'],$soff,$orderField,$reverseOrder,$findParams);
      } else $data=$this->getData(null,null,$orderField,$reverseOrder,$findParams);
      $totalRecords=$this->getLastRequestTotalRecords();
      $multics=array(); $bools=array();
      foreach($table->getFields() as $field){
        if ($field->getProp("omni")) $multics[]=$field->getName();
        else { if ($field->getProp("selected")) $bools[]=$field->getName(); }
      }
      for($ext=1;$ext<count($data);$ext++){// Coverting boolean values to simbols
        foreach($bools as $b){
          $data[$ext][$b]=((bool)$data[$ext][$b])?"+":"";
      }}
      for($ext=1;$ext<count($data);$ext++){// Replacing Multi references with values
        foreach($multics as $m){
          $data[$ext][$m]=$data[$ext][$m."_val"];
          unset($data[$ext][$m."_val"]);
      }}
    }
    if (isset($this->ops['listCustomPreprocessing'])) 
      if (is_array($this->ops['listCustomPreprocessing'])){
        foreach($this->ops['listCustomPreprocessing'] as $listPP)
          if (method_exists($this,$listPP))
            $data=call_user_func(array($this,$listPP),$data);
      }else
       echo "\n<pre>\nZenX WARNING:'listCustomPreprocessing' option must be an Array!!\n</pre>\n";
    if (isset($this->ops['fieldReorder'])){
      require_once("Utilities.php");
      $order=$this->ops['fieldReorder'];
      for($i=0;$i<count($data);$i++) array_reorder($data[$i],$order,
        isset($this->ops['eraseNotListedInReorder'])?
        (!(bool)$this->ops['eraseNotListedInReorder']):true);
    }// standard preprocessing end
    // Printing Section
    if ($this->ops['listDeletable']) echo // header print start
      "<form action='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."'>".
      "<p><input type='hidden' name='ZX_ACTION' value='DELETE' /></p>";
    echo "<table class='zx_lst'>"; $hbuff="<thead><tr>";
    $cbuff=null;  $sn=1; // <--header column sequential number
    if ($this->ops['listViewable']) { $hbuff.="<th> </th>"; $cbuff.="<col class='zx_c".$sn++."' />";  }
    if ($this->ops['listDeletable']) {
      $hbuff.="<th><input type='submit' value='".Signs::DELSIMBOL."' title='".Signs::REMOVE."' /></th>";
      $cbuff.="<col class='zx_c".$sn++."' />"; 
    }
    foreach($data[0] as $k=>$v){//Printing table header
      if ($table->getKey()->getName()==$k){ 
        if ($this->ops['listShowKeys']){
          if (isset($this->ops['listSortHeaders']) && in_array($k,$this->ops['listSortHeaders'])){
            $hbuff.="<th><a href='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."?ZX_SORT=$k&amp;ZX_SORD=".
              (int)!$reverseOrder."'";
            if (isset($this->ops['listSortPopups'][$k]))
              $hbuff.=" title='".$this->ops['listSortPopups'][$k]."' ";
            $hbuff.=">".$v."</a>";
						if ($orderField==$k) $hbuff.="&nbsp;".(((bool)$reverseOrder)?"▼":"▲");
						$hbuff.="</th>";
          }else $hbuff.="<th>".$v."</th>"; 
          $cbuff.="<col class='zx_c".$sn++."' />";
        }
      } else { 
        if (!(isset($this->ops['listHiddenFields']) && in_array($k,$this->ops['listHiddenFields']))){
          if (isset($this->ops['listSortHeaders']) && in_array($k,$this->ops['listSortHeaders'])){
            $hbuff.="<th><a href='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."?ZX_SORT=$k&amp;ZX_SORD=".
              (int)!$reverseOrder."'";
            if (isset($this->ops['listSortPopups'][$k]))
              $hbuff.=" title='".$this->ops['listSortPopups'][$k]."' ";
            $hbuff.=">".nl2br($v)."</a>";
						if ($orderField==$k) $hbuff.="&nbsp;".(((bool)$reverseOrder)?"▼":"▲");
						$hbuff.="</th>";
          }else $hbuff.="<th>".nl2br($v)."</th>";
          $cbuff.="<col class='zx_c".$sn++."' />"; 
        }
      }
    }//End of header
    echo $cbuff.$hbuff."</tr></thead><tbody>";
    for($i=1;$i<count($data);$i++){
      if (isset($this->ops['conditionalRowClass'])){//start of row conditional classes
        $classes=null;
        foreach($this->ops['conditionalRowClass'] as $cond){
          $tempResult=null; $RESULT=false;
          foreach($cond as $ck=>$cv){
            if ($ck=="0") continue;
              $VAL=$data[$i][$ck];
              $condition="return ".$cv.";";
              $tempResult[]=(bool)eval($condition);
            }
            if (in_array(false,$tempResult)) $RESULT=false; else $RESULT=true;
            if ($RESULT) $classes.=" ".$cond[0];
          }
          if (trim($classes)) echo "<tr class='$classes'>";
          else echo "<tr>";
        }else echo "<tr>"; //end row of conditional classes
      foreach($data[$i] as $k=>$v){ 
        if ($table->getKey()->getName()==$k){//<<< AUX BUTTONS START
          if ($this->ops['listViewable']) echo
            "<td><a href='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."?ZX_TARGET=$v&amp;".
            "ZX_ACTION=VIEW' title='".Signs::VIEWHINT."'>".Signs::VIEWFORM."</a></td>";
          if ($this->ops['listDeletable']) { 
            echo "<td>";
            if (!empty($data[$i][$k])){
              if (isset($this->ops['conditionalDeleteDisable'])){
                $TOTRES=false;
                foreach($this->ops['conditionalDeleteDisable'] as $cond){
                  $tempResult=null; $RESULT=false;
                  if (is_array($cond)){
                    foreach($cond as $dk=>$dv){
                      $VAL=$data[$i][$dk];
                      $tempResult[]=(bool)eval("return $dv;");
                    }
                    if (in_array(false,$tempResult)) $RESULT=false; else $RESULT=true;
                    if ($RESULT) $TOTRES=true;
                  } else { 
                    if (!isset($warnOnceCDDD)) {
                      $warnOnceCDDD=true;
                      echo "\n<pre>\nZenX WARNING:'conditionalDeleteDisable' option must".
                        " be two-layer nested Array!!\n</pre>\n";
                }}}
                if (!$TOTRES) echo "<input type='checkbox' name='ZX_".$table->getName()."_DEL_"."$v' />";
              } else echo "<input type='checkbox' name='ZX_".$table->getName()."_DEL_"."$v' />";
            }
            echo "</td>"; 
          }
				}//<<< AUX BUTTONS END
			}
      foreach($data[$i] as $k=>$v){ 
        //COREPRINT START
        if ($table->getKey()->getName()==$k){//Key field printing
          if ($this->ops['listShowKeys']){
            if (isset($this->ops['conditionalCellClass']) && //start of key cell conditional classes
              in_array($k,array_keys($this->ops['conditionalCellClass']))){
                $classes=null;
                foreach($this->ops['conditionalCellClass'][$k] as $cond){
                  $tempResult=null; $RESULT=false;
                  foreach($cond as $ck=>$cv){
                    if ($ck=="0") continue;
                      $VAL=$data[$i][$ck];
                      $condition="return ".$cv.";";
                      $tempResult[]=(bool)eval($condition);
                  }
                  if (in_array(false,$tempResult)) $RESULT=false; else $RESULT=true;
                  if ($RESULT) $classes.=" ".$cond[0];
                }
                if (trim($classes)) echo "<td class='$classes'>";
                else echo "<td>";
              }else echo "<td>"; //end of key cell conditional classes
            if (isset($this->ops['linkOuts']) && in_array($k,array_keys($this->ops['linkOuts']))){
              if (!empty($data[$i][$this->ops['linkOuts'][$k]['value']])){
                echo   "<a href='".$this->ops['linkOuts'][$k]['target']."?".
                                   $this->ops['linkOuts'][$k]['name']."=".
                         $data[$i][$this->ops['linkOuts'][$k]['value']]."' ";    
                if (!isset($this->ops['jumpToSelf']) || !$this->ops['jumpToSelf']) echo "target='_blank'";
                if (isset($this->ops['linkOuts'][$k]['popup'])){
                  if (isset($data[$i][$this->ops['linkOuts'][$k]['popup']]))
                    echo " title='".$data[$i][$this->ops['linkOuts'][$k]['popup']]."'";
                  else
                    echo " title='".$this->ops['linkOuts'][$k]['popup']."'";
                }
                echo ">$v</a></td>";
              } else echo "$v</td>";
            } else if (isset($this->ops['jumpOuts']) && in_array($k,array_keys($this->ops['jumpOuts']))){
              if (!empty($data[$i][$this->ops['jumpOuts'][$k]])){
                echo "<a href='".$data[$i][$this->ops['jumpOuts'][$k]]."'";
                if (!isset($this->ops['jumpToSelf']) || !$this->ops['jumpToSelf']) echo " target='_blank'";
                if (isset($this->ops['jumpOutPopups'][$k])){
                  if (isset($data[$i][$this->ops['jumpOutPopups'][$k]]))
                    echo " title='".$data[$i][$this->ops['jumpOutPopups'][$k]]."'";
                  else
                    echo " title='".$this->ops['jumpOutPopups'][$k]."'";
                }
                echo ">".nl2br($v)."</a></td>";
              } else echo "$v</td>";
            } else echo "$v</td>";}
        } else {//Other fields printing
          if (!(isset($this->ops['listHiddenFields']) && in_array($k,$this->ops['listHiddenFields']))){
            if (isset($this->ops['conditionalCellClass']) && //start of cell conditional classes
              in_array($k,array_keys($this->ops['conditionalCellClass']))){
                $classes=null;
                foreach($this->ops['conditionalCellClass'][$k] as $cond){
                  $tempResult=null; $RESULT=false;
                  foreach($cond as $ck=>$cv){
                    if ($ck=="0") continue;
                    if (isset($data[$i][$ck])){
                      $VAL=$data[$i][$ck];
                      $condition="return ".$cv.";";
                      $tempResult[]=(bool)eval($condition);
                  }}
                  if ($tempResult && in_array(false,$tempResult))
                    $RESULT=false; else $RESULT=true;
                  if ($RESULT) $classes.=" ".$cond[0];
                }
                if (trim($classes)) echo "<td class='$classes'>";
                else echo "<td>";
              }else echo "<td>"; //end of cell conditional classes
            if (isset($this->ops['linkOuts']) && in_array($k,array_keys($this->ops['linkOuts']))){
              if (!empty($data[$i][$this->ops['linkOuts'][$k]['value']])){
                  echo "<a href='".$this->ops['linkOuts'][$k]['target']."?".
                                   $this->ops['linkOuts'][$k]['name']."=".
                   nl2br($data[$i][$this->ops['linkOuts'][$k]['value']])."'";
                if (!isset($this->ops['jumpToSelf']) || !$this->ops['jumpToSelf']) echo " target='_blank'";
                if (isset($this->ops['linkOuts'][$k]['popup'])){
                  if (isset($data[$i][$this->ops['linkOuts'][$k]['popup']]))
                    echo " title='".$data[$i][$this->ops['linkOuts'][$k]['popup']]."'";
                  else
                    echo " title='".$this->ops['linkOuts'][$k]['popup']."'";
                }
                echo ">$v</a></td>";
              } else echo "".nl2br($v)."</td>";
            } else if (isset($this->ops['jumpOuts']) && in_array($k,array_keys($this->ops['jumpOuts']))){
              if (!empty($data[$i][$this->ops['jumpOuts'][$k]])){
                echo "<a href='".$data[$i][$this->ops['jumpOuts'][$k]]."' ";
                if (!isset($this->ops['jumpToSelf']) || !$this->ops['jumpToSelf']) echo "target='_blank'";
                if (isset($this->ops['jumpOutPopups'][$k])){
                  if (isset($data[$i][$this->ops['jumpOutPopups'][$k]]))
                    echo " title='".$data[$i][$this->ops['jumpOutPopups'][$k]]."'";
                  else
                    echo " title='".$this->ops['jumpOutPopups'][$k]."'";
                }
                echo ">".nl2br($v)."</a></td>";
              } else echo "".nl2br($v)."</td>";
            } else echo "".nl2br($v)."</td>";
          }
        }//COREPRINT END
      }//End of Fields Iteration
      echo "</tr>";
    }//End of Rows Iteration
    echo "</tbody></table>";
    if ($this->ops['listDeletable']) echo "</form>";
    if ((!isset($this->ops['noPages']) || !$this->ops['noPages']) && isset($totalRecords)){
      echo "<div class='zx_pgs'>";
      if (!isset($this->ops['listHidePagesWord']) || (isset($this->ops['listHidePagesWord']) && 
        $this->ops['listHidePagesWord']==false))
        echo Signs::PAGES;
      for($i=0;$i<(int)$totalRecords/$this->ops['recordsPerPage'];$i++)
        echo "<a href='?ZX_PAGE=$i'>".($i+1)."</a> ";
      echo "</div>";
    }
  }//EOF
  /**
   * showList() outputs "Add" button, search form and data list, depending on the current setting.
   *
   * @see showDefaultList()
   * @see showCustomList()
   * @see showSearchForm()
   * @see showAddRecordButton()
   */
  function showList(){
    if ($this->ops['listDeletable']) $this->showAddRecordButton();
    if ($this->ops['listSearchable']) $this->showSearchForm();
    switch($this->ops['listType']){
      default:
      case 0: $this->showDefaultList(); break;
      case 1: $this->showCustomList(); break;
    }
  }//EOF
  /**
   * showAddRecordButton() outputs "Add" button referring to new record data entry page.
   */
  function showAddRecordButton(){
    echo "<div class='zx_add'><form action='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)
      ."' method='post'><p>".
      "<input type='hidden' name='ZX_ACTION' value='VIEW' />".
      "<input type='submit' value='".Signs::ADDNEW."' title='".Signs::ADDHINT."' /></p></form></div>";
  }//EOF
  /**
   * getSearchForm() creates array with search fields.
   *
   * This method creates array with the search fields, where keys are field descriptions obtained by
   * {@link Field::getNote()} method and values are pre-formatted html input tags. This array is usually
   * passed to showSearchForm() method for the final preparation and printout, however you may create
   * your own methods that use this array as the raw data for custom fancy printing of search form.
   * This array also contains the following reserved keys:<br />
   * ZX_SF_START - contains form header ("form" tag etc.)<br />
   * ZX_SF_END_LEFT - contains submit button<br />
   * ZX_SF_END_RIGHT - contains reset button<br />
   * ZX_SF_END - contains closing "form" tag<br />
   *
   * @see showSearchForm()
   * @return Array Associative array with key=>value pairs
   */
  function getSearchForm(){
    $table=$this->getCurrentTable();
    $sf['ZX_SF_START']="<form action='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)."'>".
         "<p><input type='hidden' name='ZX_ACTION' value='FIND' /></p>";
    foreach($table->getFields() as $field){ 
      if (($field==$table->getKey() && !$this->ops['listShowKeys']) 
        || $field->getProp("isFile") || (isset($this->ops['findHiddenFields']) 
          && in_array($field->getName(),$this->ops['findHiddenFields']))) continue;
      $sf[$field->getNote()]="<input type='text' name='".$field->getName();
      if (isset($_SESSION[$this->ops['sessionPrefix'].$field->getName()])) 
        $sf[$field->getNote()].="' value='".$_SESSION[$this->ops['sessionPrefix'].$field->getName()];
      $sf[$field->getNote()].="' />";
    }
    if (isset($this->ops['customSearchFields'])){
      foreach($this->ops['customSearchFields'] as $k => $v){
        $sf[$v]="<input type='text' name='".$k;
        if (isset($_SESSION[$this->ops['sessionPrefix'].$k]))
          $sf[$v].="' value='".$_SESSION[$this->ops['sessionPrefix'].$k];
        $sf[$v].="' />";
    }}
    $sf['ZX_SF_END_LEFT']="<input type='submit' value='".Signs::SEARCH."' title='".Signs::SEARCHHINT."' />";
    $sf['ZX_SF_END_RIGHT']="<a href='".htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES)
      ."' title='".Signs::RESETHINT."'>".Signs::RESET."</a>";
    $sf['ZX_SF_END']="</form>";
    return $sf;
  }//EOF
  /**
   * showSearchForm() method prints out search form at the listing page.
   *
   * This is small method that obtains form data from getSearchForm() method and prints 
   * it inside table with class='zx_fnd'.
   *
   * @see getSearchForm()
   */
  function showSearchForm(){
    $form=$this->getSearchForm();
    echo $form['ZX_SF_START'];
    echo "<table class='zx_fnd'>";
    echo "<tr><th colspan='2'>".Signs::SEARCH."</th></tr>";
    foreach($form as $k=>$v)
      if ($k!="ZX_SF_START" && $k!="ZX_SF_END" && $k!="ZX_SF_END_LEFT" && $k!="ZX_SF_END_RIGHT")
        echo "<tr><td>$k</td><td>$v</td></tr>";
    echo "<tr><td>".$form['ZX_SF_END_LEFT']."</td><td>".$form['ZX_SF_END_RIGHT']."</td></tr>";
    echo "</table>";
    echo $form['ZX_SF_END'];
  }//EOF
  /**
   * setNavigationParameters() sets cookies containing the state of user navigation.
   * 
   * This method checks data submission from page buttons, search/filter fields (if any)
   * and sets relevant cookies, so that if user goes to "details" page ("IN" phase) and then
   * gets back it will be shown the same page we went away from. Since there maybe several 
   * combination of navigation parameters behaviour of this method is slightly complicated.
   * There are following cookie/post/get variables, containing navigation states:<br />
   * ZX_PAGE - contains page currently viewed<br />
   * ZX_SORT - contains name of field selected for sorting by<br />
   * ZX_SORD - contains sort order (1 - descending / 0 - ascending)<br />
   * ZX_xxxx - where xxxx is a field name to perform search in<br />
   * <br />
   * When one of the nav parameters changes (and send via POST/GET) it may reset others 
   * or add itself to existing ones, depending on its priority. Naturally it does not make any
   * sence to keep page number, if new filtering parameter is entered, since amount of pages and 
   * page contents are changed. So the parameter priority is as follows:<br /><br />
   * E: X X X<br />
   * F: X X O<br />
   * S: X O -<br />
   * P: O - -<br /><br />
   * Column 1 - Request, Column 2 - Page, Column 3 - Sort, Column 4 - Search/Filter.<br />
   * Legend for Column 1: F - filter, S - sort, P - page, E - empty.<br />Legend for Columns 2,3,4:
   * X - unset, O - set, "-" - no changes (keep)<br /><br /> 
   * So, for example, empty request will erase all navigation cookies (highest priority), and request
   * containing field name (F) will erase page and sort cookies, and set filter cookie. The least
   * priority is with the page, request with page parameter will set new page cookie, but remain all
   * others cookes untouched.<br />
   *
   * Cookies with navigation parameters must be parsed by data storage engine to form string parameters
   * and hand over these parameters to {@link addParameters()} method. Since the content of the 
   * search/filter parameter is storage engine dependant it can not be implemented at the MainEngineAbstract
   * class. For example {@link StorageEngineMysql::setFindParameters()} method can grab cookie with
   * name "user" and value "Smith" and create search parameter as mysql query string: 
   * "findParameters"=>array("user='Smith%'"). It will, then, be given to addParameters() method like this:
   * $this->addParameters(array("findParameters"=>array("user='Smith%'")));
   *
   * @see setFindParameters()
   */
  function setNavigationParameters(){
    $fields=$this->getCurrentTable()->getFields();
    if ((!isset($_REQUEST['ZX_ACTION']) || (isset($_REQUEST['ZX_ACTION']) && 
      $_REQUEST['ZX_ACTION']!="FIND" && $_REQUEST['ZX_ACTION']!="VIEW")) && 
      !isset($_REQUEST['ZX_PAGE']) && !isset($_REQUEST['ZX_SORT'])) {// nothing is requested - first time visit
        unset($_SESSION['ZX_PAGE']);
        unset($_SESSION['ZX_SORT']); unset($_SESSION['ZX_SORD']);
        foreach($fields as $f) unset($_SESSION[$this->ops['sessionPrefix'].$f->getName()]);
        if (isset($this->ops['customSearchFields']))
          foreach(array_keys($this->ops['customSearchFields']) as $cfk)
            unset($_SESSION[$this->ops['sessionPrefix'].$cfk]);
    }
    if (isset($_REQUEST['ZX_ACTION']) && $_REQUEST['ZX_ACTION']=="FIND"){// find requested
      unset($_SESSION['ZX_PAGE']);
      foreach($fields as $f) unset($_SESSION[$this->ops['sessionPrefix'].$f->getName()]);
      $vars=$this->retrieveVars();
      foreach($fields as $f)
        foreach($vars as $k=>$v)
          if ($f->getName()==$k) if ($v) $_SESSION[$this->ops['sessionPrefix'].$k]=$v;
      if (isset($this->ops['customSearchFields']))
        foreach(array_keys($this->ops['customSearchFields']) as $cfk)
          foreach($vars as $k=>$v)
            if ($cfk==$k) if ($v) $_SESSION[$this->ops['sessionPrefix'].$k]=$v;
    }
    if (isset($_REQUEST['ZX_SORT']) && isset($_REQUEST['ZX_SORD'])){// sort requested
      unset($_SESSION['ZX_PAGE']);
      $_SESSION['ZX_SORT']=$_REQUEST['ZX_SORT'];
      $_SESSION['ZX_SORD']=$_REQUEST['ZX_SORD'];
    }
    if (isset($_REQUEST['ZX_PAGE'])){
      $_SESSION['ZX_PAGE']=$_REQUEST['ZX_PAGE'];
    }
    //echo "<pre>"; print_r($_SESSION); echo "</pre>";
  }//EOF
  /**
   * verifyImageFields() method checks image data type definitions for correctness.
   *
   * This is small convenience method that is called at the class constructor, that checks
   * whether all necessary properties are defined for the image data types, since incorrect
   * data definition may lead to unexpected behaviour during the runtime. If some properties
   * are not defined it will throw {@link MissingImageDefinitionException}.
   *
   * @see __construct()
   * @see DataDefiner
   * @throws MissingImageDefinitionException
   */
  function verifyImageFields(){
    foreach(DataDefiner::$dataTypes as $type=> $def){
      if (isset($def['isImage']) && $def["isImage"]){
        try{
          $prop="maxBytes"; if (!isset($def[$prop])) throw new 
            MissingImageDefinitionException($prop."' is defined for type '".$type);
          $prop="width"; if (!isset($def[$prop])) throw new 
            MissingImageDefinitionException($prop."' is defined for type '".$type);
          $prop="height"; if (!isset($def[$prop])) throw new 
            MissingImageDefinitionException($prop."' is defined for type '".$type);
          $prop="mustResize"; if (!isset($def[$prop])) throw new 
            MissingImageDefinitionException($prop."' is defined for type '".$type);
        }catch(Exception $e){
          echo $e->errorMessage();
          exit(125);
    }}}
  }//EOF
  /**
   * imageResize() method resizes uploaded images to the dimensions set up in data type definition.
   *
   * This is short convenience method that utilizes external {@link SimpleImage} class in order to 
   * resize images to the dimensions set up in data type definition.
   *
   * It is actually must be under storage engine class, however I failed to separate image handling
   * from main class, so it may be a job for future improvements.
   *
   * @param String $imgfn Image file name
   * @param Field $field Field object that contains data of the image type
   * @see DataDefiner
   */
	function imageResize($imgfn,$field){
		if (file_exists($imgfn)){
			$settings=DataDefiner::$dataTypes[$field->getType()];
			if (filesize($imgfn)>$settings['maxBytes']) {
				unlink($imgfn);
			} else {
				if ($settings['mustResize']){
					$image = new SimpleImage();
					$image->load($imgfn);
					$Lw=$settings['width'];
					$Lh=$settings['height'];
					$W=$image->getWidth();
					$H=$image->getHeight();
					if ($Lw<$W && $Lh<$H){
						$dW=$W-$Lw;
						$dH=$H-$Lh;
						if ($dW>$dH) $image->resizeToWidth($Lw);
						else $image->resizeToHeight($Lh);
					} else if ($Lw<$W) $image->resizeToWidth($Lw);
					else if ($Lh<$H) $image->resizeToHeight($Lh);
					$image->save($imgfn);
	}}}}//EOF
	/**
   * microtime_float() is a utility function that returns unix time with microseconds precision
   * 
   * Utility method used for scipts time profiling.
   * 
   * @return float Current timestamp
   */
  function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
  }
	/**
	 * fileUploadErrorHandler() provides custom implementation of image folder access rights error.
	 *
	 * @param Integer $errno PHP error number
	 * @param String $errstr PHP error message
	 * @param String $errfile File where error occured
	 * @param Integer $errline Error cause line number
	 */
  function fileUploadErrorHandler($errno, $errstr, $errfile, $errline){
    if ($errno==2){
      echo "<pre>\nZenX ERROR: can not save file to the image storage folder!\n";
      echo "Please correct PHP access rights to this folder!\n";
    } else {
      echo "<pre>\nZenX ERROR: unknown file upload error!\n";
    }
      echo "--------------------------------------------------------------------\n";
      echo "Native PHP warning number is: $errno\nNative PHP warning message is:\n";
      echo "$errstr\n</pre>\n";
  }//EOF
}
/**
 * NoCurrentTableException is a custom exception that is thrown by
 * {@link MainEngineAbstract::getCurrentTable() } method in case 
 * {@link MainEngineAbstract::$currentTable } is not set.
 *
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class NoCurrentTableException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: No table is defined as current! Data".
      " manupulations are not allowed unless current table is specified!\n".
      'Error at '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
/**
 * NoUpdateKeySuppliedException is a custom exception that is thrown if key is not supplied at the 
 * data array passed to {@link MainEngineAbstract::updateRecord()} method.
 *
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class NoUpdateKeySuppliedException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: No '".$this->getMessage()."' ['_KEYS' type field] ".
      "supplied within the data array!\nCan not update record without proper".
      " identification!\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
/**
 * MissingImageDefinitionException is a custom exception for the incorrect image data definition.
 *
 * @see MainEngineAbstract::verifyImageFields()
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class MissingImageDefinitionException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: No '".$this->getMessage()."'! ".
      "Please define this property at the DataDefiner class!\n".
      "This property is obligatory for image data type definitions.\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
