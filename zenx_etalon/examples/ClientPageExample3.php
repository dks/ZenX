<?php
require("UserEngineExample3.php");
function __autoload($class_name){ require_once '../ZenX/'.$class_name.'.php'; }

$zen = new UserEngineExample3();

// Next 3 lines need to be executed only once! 
//$zen->createStorage();
//$zen->customMysqlRequest("INSERT INTO ZX_goods_mul_supl VALUES (null,'Sonnie'),(null,'Cassia');");
//$zen->customMysqlRequest("INSERT INTO ZX_goods_mul_payops VALUES (null,'Cash'),(null,'Check'),(null,'Wire');");

$zen->runFullCycle();


// Next 1 line needs to be executed only if you want to erase this table
//$zen->destroyDataStorage();
?>
