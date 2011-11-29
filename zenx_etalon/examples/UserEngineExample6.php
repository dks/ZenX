<?php
class UserEngineExample6 extends StorageEngineMysql{
  function __construct(){
    parent::__construct("localhost","user","pass","base");
    $t = new Table("fotofolder");
    $t->createField("idno","_KEYS","ID");
    $t->createField("name","_WORD","Picture Description");
    $t->createField("big","_IMGS","Hi-Res Picture");
    $t->createField("small","_ICON","Picture Preview");
    $this->registerTable($t);
  }
	function imageResize($imgfn,$field){
		if ($field->getName()=='big') {
			copy($imgfn,($copyfn=str_replace('big','small',$imgfn)));
			parent::imageResize($copyfn,new Field('small','_ICON','Picture Preview'));
		}
		parent::imageResize($imgfn,$field);
	}
}
