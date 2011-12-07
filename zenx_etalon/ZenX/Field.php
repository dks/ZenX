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
 * Field class represents an object that defines data type and properties.
 *
 * @package ZenX
 * @author Konstatin Dvortsov
 */
class Field{
  /**
   * $fname variable contains field name.
   *
   * Field name is a unique (withing a table) identifier of the field, this value is used to name
   * table fields within data storage engines. In order to prevent data storage engine specific 
   * problems it is recommended to use Latin characters as a field name.
   * 
   * @var String $fname Field name.
   */
  protected $fname;
  /**
   * $ftype variable contains field data type.
   *
   * @var String $ftype Field data type, on of the data types defined in DataDefiner class
   * @see DataDefiner
   */
  protected $ftype;
  /**
   * $fnote variable contains field description.
   *
   * Value of this variable is used as the table column name/header during the data output,
   * it may also be used as field description/comment at the data storage engine.
   *
   * @var String $ftype Field description
   */
  protected $fnote;
  /**
   * Field class constructor.
   *
   * This method creates Field object based on the parameters supplied. It first 
   * checks the availability of the data type description within the DataDefiner
   * class and then creates Field object. {@link NoSuchTypeException} exception is 
   * thrown if data type is not listed within DataDefiner class.
   *
   * @param String $n Field name
   * @param String $t Field data type
   * @param Strign $c Field comment/description
   * @return Field Field object
   * @throws NoSuchTypeException
   */
  function __construct($n,$t,$c){
    foreach(DataDefiner::$dataTypes as $k=>$v) $fields[]=$k;
    try{ 
      if (!in_array($t,$fields)) 
        throw new NoSuchTypeException($t);
      $this->fname=$n;
      $this->ftype=$t;
      $this->fnote=$c;
    } catch(NoSuchTypeException $e){
      echo $e->errorMessage(); 
      exit(125);
    }
    $fields=null;
  }
  /**
   * Empty implementation of class destructor.
   */
  function __destruct(){}
  /**
   * getName() method returns Field object name.
   *
   * @return String Field name
   */
  function getName(){ return $this->fname; }
  /**
   * getType() method returns Field object type.
   *
   * @return String Field type
   * @see DataDefiner
   * @tutorial ZenX.pkg#datatypes
   */
  function getType(){ return $this->ftype; }
  /**
   * getNote() method returns Field object comment/description.
   *
   * @return String Field comment
   */
  function getNote(){ return $this->fnote; }
  /**
   * getProp() method returns Field object property specified as parameter.
   *
   * This method accesses {@link DataDefiner::$dataTypes} variable with data type
   * definitions and tries to obtain value of the array key specified as parameter.
   * If key matching to the supplied string is not found (no such property defined),
   * this method returns false.
   *
   * @param String $property Name of the data type property
   * @return Mixed Data type / Field property
   */
  function getProp($property){
    return isset(DataDefiner::$dataTypes[$this->ftype][$property])?
      DataDefiner::$dataTypes[$this->ftype][$property]:false;
  }
}
/**
 * NoSuchTypeException is a custom exception thrown if requested data type does not exist.
 *
 * @see Field::__construct()
 * @author Konstantin Dvortsov
 * @package ZenX
 */
class NoSuchTypeException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: type '".$this->getMessage()."' is not specified".
      " in DataDefiner class!\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
