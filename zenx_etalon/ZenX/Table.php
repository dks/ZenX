<?php
/**
 * "ZenX" PHP data manupulation library.
 *
 * @author Konstantin Dvortsov <kostya.dvortsov@gmail.com>. You can 
 * also track me down at {@link http://dvortsov.tel dvortsov.tel }
 * @version 1.0
 * @package ZenX 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
/**
 * Table class represents an object that wraps a group of Field objects 
 * with some common properties. It is similar to the mysql table.
 *
 * @package ZenX
 * @author Konstatin Dvortsov
 */
class Table{
  /**
   * $fields variable is an array of Field objects assigned to this Table object.
   *
   * @var Array $fields Array of Field objects
   */
  protected $fields=array();
  /** 
   * $name variable contains name of this Table object.
   *
   * @var String $name Table name
   */
  protected $name;
  /**
   * Table class constructor.
   *
   * @param String $name Name to be given to this Table object
   * @return Table Table object
   */
  function __construct($name){
    $this->name=$name;  
  }
  /**
   * Empty implementation of class destructor
   */
  function __destruct(){}
  /**
   * addField() method adds Field object supplied as parameter to the Table.
   *
   * Since no Table can function properly without keys this method adds the 
   * Field object supplied as parameter to $fields array and then checks for
   * availability of the KEY type ("_KEYS") Field object within the same array.
   * If no Field object of the key type is found {@link NoTableKeyException} is 
   * thrown. So if you add Fields to the newly created Table object, the first
   * one to be added always MUST be key field.
   *
   * It also checks for the Field with the same name prior to adding, so if two
   * Field objects with the same name are added {@link DuplicateFieldNameException}
   * is thrown.
   *
   * Since there can be only one KEY field per Table, an attempt to add second 
   * Field object of the KEY type will cause {@link DuplicateKeyException} to be thrown.
   *
   * @param Field $f Field object
   * @throws NoTableKeyException
   * @throws DuplicateFieldNameException
   * @throws DuplicateKeyException
   */
  function addField($f){
    try{
      $nokey=true;
      if (count($this->fields)>0){
        foreach($this->fields as $tf){
          if ($tf->getName()==$f->getName())
            throw new DuplicateFieldNameException($f->getName()); 
          if ($tf->getType()=="_KEYS" && $f->getType()=="_KEYS")
            throw new DuplicateKeyException($f->getName()); 
          if ($tf->getType()=="_KEYS") $nokey=false;
          if ( $f->getType()=="_KEYS") $nokey=false;
        } 
        if ($nokey) throw new NoTableKeyException();
      }
    } catch (Exception $e){
      echo $e->errorMessage();
      exit(125);
    }
    $this->fields[]=$f;
  }
  /**
   * createField() is a convenience method creating Field object and adding it to the Table at the same time.
   *
   * @param String $n Field name
   * @param String $t Field type
   * @param String $c Field comment/description
   * @see Field::__construct()
   */
  function createField($n,$t,$c){
    $this->addField(new Field($n,$t,$c));
  }
  /**
   * getName() method returns Table name.
   *
   * @return String Table name
   */
  function getName(){
    return $this->name;
  }
  /**
   * getFields() method returns Fields objects assigned to this Table
   *
   * @return Array Array of Table objects
   */
  function getFields(){
    return $this->fields;
  }
  /**
   * getFieldByName() method returns Field object with the name specified as parameter.
   *
   * @param String Field name
   * @return Field Field object
   */
  function getFieldByName($name){
    foreach($this->getFields() as $field)
      if ($field->getName()==$name) return $field;
    return false;
  }
  /**
   * getFieldsCount() method returns the number of Field objects assigned to this Table.
   *
   * @return Integer Number of fields within this table
   */
  function getFieldsCount(){
    return count($this->fields);
  }
  /**
   * getKey() method returns Field object of the KEY type ("_KEYS").
   *
   * @return Field
   */
  function getKey(){
    foreach($this->getFields() as $f)
      if ($f->getType()=="_KEYS")
        return $f;
  }
}
/**
 * DuplicateFieldNameException is a custom Exception thrown in case two fields with the same name are added to the same Table.
 * 
 * @see Table::addField()
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class DuplicateFieldNameException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: Field name '".$this->getMessage()."' is already used".
      " in this table! (Must be unique in one table)\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
/**
 * DuplicateKeyException is a custom Exception thrown in case two key fields are added to the same Table.
 * 
 * @see Table::addField()
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class DuplicateKeyException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: Duplicate '_KEYS' type field not allowed! Field '".$this->getMessage()."'".
      " is a duplicate key in this table!\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
/**
 * NoTableKeyException is a custom Exception thrown in case no key field is available within the Table.
 * 
 * @see Table::addField()
 * @author Konstatin Dvortsov
 * @package ZenX
 */
class NoTableKeyException extends Exception{
  public function errorMessage(){
    $errorMsg = "\n<pre>\nZenX ERROR: No '_KEYS' type field defined for this table!".
      " (Must be one per table)\n".'in '.$this->getFile().' on line '.
      $this->getLine()."\n".$this->getTraceAsString()."\n</pre>\n";
    return $errorMsg;
  }
}
