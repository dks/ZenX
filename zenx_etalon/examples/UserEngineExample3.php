<?php
class UserEngineExample3 extends StorageEngineMysql{
  function __construct(){
    parent::__construct("localhost","user","pass","base");
    $t = new Table("goods");
    $t->createField("number","_KEYS","ID No.");
    $t->createField("name","_WORD","Item Name");
    $t->createField("supl","_LLST","Supplier");
    $t->createField("stock","_BOOL","Available");
    $t->createField("cost","_RINT","Price");
    $t->createField("descr","_TEXT","Item Description");
    $t->createField("make","_ELST","Manufacturer");
    $t->createField("payops","_RLST","Payment Options");
    $t->createField("pic","_IMGS","Item Picture");
    $this->registerTable($t);
  }
}
