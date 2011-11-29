<?php session_start(); ?>
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
			body	{ text-align: center; }
		</style>
	</head>
	<body>

<?php
require("UserEngineExample7.php");
function __autoload($class_name){ require_once '../ZenX/'.$class_name.'.php'; }

if (isset($_REQUEST['id'])){ 
	$currentFotoId=$_SESSION['id']=$_REQUEST['id'];
} else if (isset($_SESSION['id'])){
	$currentFotoId=$_SESSION['id'];
} else {
	echo "Can not tie comments to foto! Please enter from the main page!!!";
	exit(125);
}

$zen = new UserEngineExample7();
$zen->setCurrentTable($zen->getTableByName("comments"));
$zen->setParameters(array(
	 "listHiddenFields"=>array("pref")
	,"findParameters"=>array("pref=$currentFotoId")

	,"restrictedFields"=>array("date")
	,"formDefaults"=>array("date"=>date("Y-m-d"))
	,"formDefaultsWriteThrough"=>array("date")

	,"formHiddenFields"=>array("pref")
	,"formRelayParameters"=>array("pref"=>$currentFotoId)
	
	,"noPages"=>true
));
$zen->runFullCycle();
		echo "<p><a href='ClientPageExample7.php?ZX_ACTION=VIEW&amp;ZX_TARGET=$currentFotoId'>Back To Foto</a></p>";

?>

		<div>
			<br />
			<a href="http://validator.w3.org/check?uri=referer">
				<img src="http://www.w3.org/Icons/valid-xhtml11-blue" alt="Valid XHTML 1.1" />
			</a>
			<a href="http://jigsaw.w3.org/css-validator/check/referer">
				<img src="http://jigsaw.w3.org/css-validator/images/vcss-blue" alt="Valid CSS!" />
			</a>
		</div>
	</body>
</html>
