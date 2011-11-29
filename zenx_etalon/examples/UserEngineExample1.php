<?php
class UserEngineExample1 extends StorageEngineMysql{
  function __construct(){
    parent::__construct("localhost","user","pass","base");
    $t = new Table("testTable1");
    $t->createField("idn","_KEYS","ID");
    $t->createField("str","_WORD","Simple Sentence");
    $this->registerTable($t);
  }
}
