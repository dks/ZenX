<?php
require("UserEngineExample1.php");
function __autoload($class_name){ require_once '../ZenX/'.$class_name.'.php'; }

$zen = new UserEngineExample1();
$zen->createStorage();
$zen->runFullCycle();
?>
