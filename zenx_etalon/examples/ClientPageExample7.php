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
		</style>
	</head>
	<body>

<?php
require("UserEngineExample7.php");
function __autoload($class_name){ require_once '../ZenX/'.$class_name.'.php'; }

$zen = new UserEngineExample7();
//$zen->destroyDataStorage();
//$zen->createStorage();
$zen->setParameters(array("listSortHeaders"=>array("idno","name")));
$zen->runFullCycle();

if ($zen->getCurrentPhase()=="IN"){
	if ($record=$zen->getCurrentRecord()){
		$currentFotoId=$record['idno'];
		$zen->setCurrentTable($zen->getTableByName("comments"));
		$zen->resetParameters();
		$zen->setParameters(array(
			"listHiddenFields"=>array("pref")
			,"findParameters"=>array("pref=$currentFotoId")
			,"noPages"=>true
			,"listDeletable"=>false
			,"listShowKeys"=>false
			,"listViewable"=>false
		));
		echo "<p>Comments List:</p>";
		$zen->showDefaultList();
		echo "<p><a href='ClientPageExample7-1.php?id=$currentFotoId'>Add or Remove Comment</a></p>";
	}
}

if ($zen->getCurrentPhase()=="OUT"){
  if ($zen->deletedIds){
    $zen->customMysqlRequest("DELETE FROM ZX_comments WHERE pref IN (".$zen->deletedIds.");");

		// Below is the example of Manual Pictures Deletion. Not required in our case though.
		// 
		//	//1. Obtain Pictures Ids
		//
		//	$picIds=$zen->customMysqlRequest( 
		//	"SELECT id FROM ZX_sometable WHERE refToMainTableId IN (".$zen->deletedIds.");");
		//
		//	//2. Delete Foto Files
		//
		//	if ($picIds){
		//		foreach($picIds as $id){
		//      $imgname=null;
		//      $imgfn=glob("../img/sometable_someimagefield_".$id['idfieldname'].".*");
		//      if (!empty($imgfn)) $imgname=$imgfn[0]; 
		//      if ($imgname) unlink($imgname);
		//			}
		//		}
		//
		//	//3. Delete Foto Records
		//
		//	$core->customMysqlRequest(
		//	"DELETE FROM ZX_sometable WHERE refToMainTableId IN (".$zen->deletedIds.");");

	}
}


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
