<?php
class UserEngineExample4 extends StorageEngineMysql{
  function __construct(){
    parent::__construct("localhost","user","pass","base");
    $t = new Table("employees");
    $t->createField("id","_KEYS","ID No.");
    $t->createField("name","_WORD","Employee Name");
    $t->createField("qual","_BOOL","Qualified");
    $t->createField("age","_RINT","Age");
    $this->registerTable($t);
  }
}
