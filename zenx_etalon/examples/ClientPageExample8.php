<?php echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN" 
	xmlns="http://www.w3.org/1999/xhtml" 
	xml:lang="en" 
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
	xsi:schemaLocation="http://www.w3.org/1999/xhtml http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd"
>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Picture Gallery</title>
		<link rel="stylesheet" href="ClientPageExample4.css" type="text/css" />
		<style type="text/css">
			.zx_lst	{ width: 50%; margin:0 auto; }
			.zx_lst td { text-align: center; }
			.zx_lst td + td + td { text-align: center; }
			body	{ text-align: center; }
			.report { text-align: left; font-family: monospace; }
		</style>
	</head>
	<body>
		<h2>SEND YOUR DATA TO SERVER WITH ZENX!</h2>
<?php
require("UserEngineExample8.php");
function __autoload($class_name){ require_once '../ZenX/'.$class_name.'.php'; }

$zen = new FormEngineExample();
$zen->setParameters(array("formHiddenFields"=>array("idn")));
$zen->runFullCycle();
?>
