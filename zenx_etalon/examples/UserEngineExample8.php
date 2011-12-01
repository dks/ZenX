<?php
class FormEngineExample extends StorageEngineHtmlForm{
  function __construct(){
    parent::__construct();
    $t = new Table("formdata");
    $t->createField("idn","_KEYS","ID");
    $t->createField("str","_WORD","Simple Sentence");
    $t->createField("txt","_TEXT","Sample Text");
    $t->createField("num","_RINT","Integer");
    $t->createField("fot","_IMGS","Photo");
    $this->registerTable($t);
  }
	function saveData($data){
		echo "<h1>Your data has been send to server!</h1>";
		echo "<div class='report'><pre>"; var_dump($data); echo "</pre></div>";
		
		if (file_put_contents("ZenxDataDump.txt",serialize($data))) echo "<p>File Successfully Saved</p>";
		else echo "<p>File Save Error!</p>";

		$my_file = isset($data['fot'])?$data['fot']:null;
		$my_path = "";
		$my_name = "ZenX Example Script";
		$to_mail = "kostya.dvortsov@gmail.com";
		$my_replyto = $my_mail = "example@example.com";
		$my_subject = "Data From The ZENX Form Sent To You on ".date("Y-m-d");
		$my_message = null;
		foreach($data as $k=>$v) $my_message.="$k = $v <br>\n\r";
		$this->mail_attachment($my_file, $my_path, $to_mail, $my_mail, $my_name, $my_replyto, $my_subject, $my_message);
	}

	function mail_attachment($filename, $path, $mailto, $from_mail, $from_name, $replyto, $subject, $message) {
		$content=null;
		if ($filename){ 
			$file = $path.$filename;
			$file_size = filesize($file);
			$handle = fopen($file, "r");
			$content = fread($handle, $file_size);
			fclose($handle);
			$content = chunk_split(base64_encode($content));
    	$name = basename($file);
		}
    $uid = md5(uniqid(time()));
    $header = "From: ".$from_name." <".$from_mail.">\r\n";
    $header .= "Reply-To: ".$replyto."\r\n";
    //$header .= "Bcc: example@example.com\r\n";
    $header .= "MIME-Version: 1.0\r\n";
    $header .= "Content-Type: multipart/mixed; boundary=\"".$uid."\"\r\n\r\n";
    $header .= "This is a multi-part message in MIME format.\r\n";
    $header .= "--".$uid."\r\n";
    $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
    $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $header .= $message."\r\n\r\n";
    $header .= "--".$uid."\r\n";
		if ($filename){
			$header .= "Content-Type: application/octet-stream; name=\"".$filename."\"\r\n"; 
			// use different content types here
			$header .= "Content-Transfer-Encoding: base64\r\n";
			$header .= "Content-Disposition: attachment; filename=\"".$filename."\"\r\n\r\n";
			$header .= $content."\r\n\r\n";
			$header .= "--".$uid."--";
		}
    if (mail($mailto, $subject, "", $header)) {
        echo "mail send ... OK"; // or use booleans here
    } else {
        echo "mail send ... ERROR!";
    }
	}
}
