<?php
/**
 * "ZenX" PHP data manipulation library.
 *
 * @author Konstantin Dvortsov <kostya.dvortsov@gmail.com>. You can 
 * also track me down at {@link http://dvortsov.tel dvortsov.tel }
 * @version 1.0
 * @package ZenX 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
/**
 * DataDefiner class contains purely data type definitions and data type filters.
 *
 * This class is set out separately specifically for easy extendability of data
 * types. In order to define your own data type you just need to add new values to
 * $dataTypes and optionally (however it is highly recommended for security/
 * reliability reasons) define data type filter.
 *
 * Please note that general files/images input filter is hardcoded in 
 * {@link MainEngineAbstract::validateVars()} method, however you can create your own
 * data type specific detailed filters here.
 * 
 * @tutorial ZenX.pkg#datatypes
 * @package ZenX
 * @author Konstatin Dvortsov
 */
class DataDefiner{
  public static $dataTypes=array(
    // _KEYS - is a numeric identifier of the record in database
    // _KEYS field is hardcoded in some classes and can not be removed or altered
    "_KEYS" => array(
      "sTag" => "<input type='hidden' name='",
      "mTag"=>"' value='", 
      "eTag" => "' />"
    ),
    // _WORD - is a short text
    "_WORD" => array(
      "sTag" => "<input type='text' name='",
      "mTag"=>"' value='",
      "eTag" =>"' />"
    ),
    // _TEXT - is a long text of arbitrary length
    "_TEXT" => array(
      "sTag" => "<textarea name='",
      "mTag"=>"' rows='4' cols='40'>",
      "eTag" =>"</textarea>"
    ),
    // _RINT - is a regular integer, values up to 2147483647 (PHP int limit
    //  for 32 bit system, unsigned not supported).
    "_RINT" => array(
      "sTag" => "<input type='text' name='",
      "mTag"=>"' value='", 
      "eTag" => "' maxlegth='11' />"
    ),
    // _RFLT - is a regular float.
    "_RFLT" => array(
      "sTag" => "<input type='text' name='",
      "mTag"=>"' value='", 
      "eTag" => "' maxlegth='11' />"
    ),
    // _LLST - limited list
    "_LLST" => array(
      "omni" => "multiple1",
      "prefix1" => "<select name='",
      "prefix2" => "'>",
      "postfix" => "</select>",
      "sTag" => "<option value='",
      "selected" => "' selected='selected", 
      "mTag"=>"'>",
      "eTag" =>"</option>"
    ),
    // _RLST - radio buttons list
    "_RLST" => array(
      "omni" => "multiple2",
      "sTag" => "<input type='radio' name='",
      "selected" => "' checked='checked", 
      "mTag"=>"' value='",
      "eTag" =>"' />"
    ),
    // _ELST - extendable list
    "_ELST" => array(
      "omni" => "multiple1",
      "prefix1" => "<select name='",
      "prefix2" => "'>",
      "postfix" => "</select>",
      "sTag" => "<option value='",
      "selected" => "' selected='selected", 
      "mTag" =>"'>",
      "eTag" =>"</option>",
      "extendable" =>true
    ),
    // _DATE - a date
    "_DATE" => array(
      "sTag" => "<input type='text' name='",
      "mTag"=>"' value='",
      "eTag" =>"' />"
    ),
    // _BOOL - boolean value
    "_BOOL" => array(
      "sTag" => "<input type='checkbox' name='",
      "mTag"=>"'",
      "selected" => " checked='checked'", 
      "eTag" =>" class='zx_bul' />"
    ),
    //_IMGS - standard images
    "_IMGS" => array(
      "sTag" => "<input type='file' name='",
      "mTag" => "' />", 
      "eTag" => "<input type='hidden' name='MAX_FILE_SIZE' value='1000000' />",
      "isFile" => true,
      "maxBytes" => 1000000,
      "isImage" => true,
      "mustResize" => true,
      "width" => 800,
      "height" => 600
    ),
    //_ICON - small icon image
    "_ICON" => array(
      "sTag" => "<input type='file' name='",
      "mTag" => "' />", 
      "eTag" => "<input type='hidden' name='MAX_FILE_SIZE' value='300000' />",
      "isFile" => true,
      "maxBytes" => 300000,
      "isImage" => true,
      "mustResize" => true,
      "width" => 50,
      "height" => 50
    ),
    //_FILE - general files
    "_FILE" => array(
      "sTag" => "<input type='file' name='",
      "mTag" => "' />", 
      "eTag" => "<input type='hidden' name='MAX_FILE_SIZE' value='1000000' />",
      "isFile" => true,
      "maxBytes" => 1000000
    ),
  );
  /**
	 * filterKeys() method implements filter for ID "_KEYS" field.
	 *
	 * This filter passes through only positive numbers or empty value (required to allow creation
	 * of new record).
	 *
	 * @param Integer $s Record ID number (key)
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typekeys
	 */
  function filterKeys($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    $s=filter_var($s,FILTER_SANITIZE_NUMBER_INT);
    $tmp=filter_var($s,FILTER_VALIDATE_INT,array("options"=>array("min_range"=>"0")));
    if ($tmp==false && $s!=="") $r['ERR'][]=Signs::WRONGKEY;
    else $s=$tmp;
    $r['VAL']=$s;
    return $r; 
  }
  /**
	 * filterWord() method implements filter for short text "_WORD" field.
	 *
	 * This filter passes through only String values having length shorter than 300 bytes. 
	 * Keep in mind that characters other than English consume from 2 to 4 bytes. Since value 
	 * is filtered through FILTER_SANITIZE_STRING it will strip tags.
	 *
	 * @param String $s String value to be filtered
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typeword
	 */
  function filterWord($s){ 
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    if (mb_strlen($s,'8bit')>299) { 
      $r['WARN'][]=Signs::STRTOOLONG;
      preg_match_all('`.`u', $s, $arr);
      $counter=0; $newstr="";
      foreach($arr[0] as $str) {
        $counter+=mb_strlen($str,'8bit');
        if ($counter>299) break;
        $newstr.=$str; }
      $s=$newstr; }
    $s=filter_var($s,FILTER_SANITIZE_STRING);
    $r['VAL']=$s;
    return $r; 
  }
  /**
	 * filterText() method implements filter for long text "_TEXT" field.
	 *
	 * This filter passes through only String values having length shorter than 65535 bytes. 
	 * Keep in mind that characters other than English consume from 2 to 4 bytes. 
	 *
	 * @param String $s String value to be filtered
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typetext
	 */
  function filterText($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    if (mb_strlen($s,'8bit')>65535) $r['WARN'][]=Signs::TEXTTOOLONG;
    $r['VAL']=$s;
    return $r; 
  }
	/**
	 * filterRint() method implements filter for regular integer "_RINT" field.
	 *
	 * This filter first sanitizes input value as integer and then validates it. If value has been trimmed
	 * during sanitization warning will be shown, if value can not be validated after sanitization error 
	 * is shown.
	 *
	 * @param Integer $s Integer value for filtering
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typerint
	 */
  function filterRint($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    if ($s!=""){
      $s_prev=$s;
      $s=filter_var($s,FILTER_SANITIZE_NUMBER_INT);
      if (strlen($s_prev)!=strlen($s))
      $r['WARN'][]=Signs::NUMBERCLEARED;
      $tmp=filter_var($s,FILTER_VALIDATE_INT);
      if ($tmp===false && $s!=="") $r['ERR'][]=Signs::WRONGINT;
      else $s=$tmp; }
    $r['VAL']=$s;
    return $r; 
  }
	/**
	 * filterRflt() method implements filter for regular float "_RFLT" field.
	 *
	 * This filter first sanitizes input value as float and then validates it. If value has been trimmed
	 * during sanitization warning will be shown, if value can not be validated after sanitization error 
	 * is shown.
	 *
	 * @param Float $s Float value for filtering
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typerflt
	 */
  function filterRflt($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    if ($s!=""){
      $s_prev=$s;
      $s=filter_var($s,FILTER_SANITIZE_NUMBER_FLOAT,FILTER_FLAG_ALLOW_FRACTION);
      if (strlen($s_prev)!=strlen($s))
      $r['WARN'][]=Signs::NUMBERCLEARED;
      $tmp=filter_var($s,FILTER_VALIDATE_FLOAT);
      if ($tmp==false && $s!=="") $r['ERR'][]=Signs::WRONGFLOAT;
      else $s=$tmp; }
    $r['VAL']=$s;
    return $r;
  }
	/**
	 * filterBool() method implements filter for boolean "_BOOL" field.
	 *
	 * The filter validates value as boolean. Empty input (since html forms do not send empty values)
	 * will be turned to 0/false.
	 *
	 * @param Boolean $s Boolean value for filtering
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typebool
	 */
  function filterBool($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    $s=filter_var($s,FILTER_VALIDATE_BOOLEAN);
    if (!$s) $s=0; 
    $r['VAL']=$s;
    return $r; 
  }
	/**
	 * filterDate() method implements filter for date "_DATE" field.
	 *
	 * The filter allows only values in YYYY-MM-DD format. Any of "-"," ","\","/","." symbols can be
	 * used as separator. Years must start from only 19 or 20 (19xx|20xx). Any other values will 
	 * result in validation error. Days are not checked for validity (i.e. Feb 31 will be a valid date).
	 *
	 * @param Date $s Date value for filtering
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typedate
	 */
  function filterDate($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    if ($s!=""){
      if(filter_var($s,FILTER_VALIDATE_REGEXP,array("options"=>array("regexp"=>
        "/^(19|20)\d\d[- \/.](0[1-9]|1[012])[- \/.](0[1-9]|[12][0-9]|3[01])$/")))===false)
        $r['ERR'][]=Signs::WRONGDATE;
    }
    $r['VAL']=$s;
    return $r; 
  }
	/**
	 * filterRlst() method implements filter for radio buttons list "_RLST" field.
	 * 
	 * Since regular list contains only reference numbers this method is merely a wrapper for
	 * {@link filterKeys()} method.
	 *
	 * @param Integer $s Record ID number (key)
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typerlst
	 */
  function filterRlst($s){ return self::filterKeys($s); }
	/**
	 * filterLlst() method implements filter for limited list "_LLST" field.
	 * 
	 * Since limited list contains only reference numbers this method is merely a wrapper for
	 * {@link filterKeys()} method.
	 *
	 * @param Integer $s Record ID number (key)
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typellst
	 */
	function filterLlst($s){ return self::filterKeys($s); }
	/**
	 * filterElst() method implements filter for extendable list "_ELST" field.
	 *
	 * This method allows only for string values having length less than 61 byte.
	 * Keep in mind that characters other than English consume from 2 to 4 bytes. Since value 
	 * is filtered through FILTER_SANITIZE_STRING it will strip tags.
	 *
	 * @param String $s String list value for filtering
	 * @return Array Associative array with three elements, "VAL" containing filtered value,
	 * "ERR" and "WARN" containing error or warning messages respectively, if filtering resulted
	 * in error or warning.
	 * @tutorial ZenX.pkg#typeelst
	 */
  function filterElst($s){
    $r=array("VAL"=>"","ERR"=>array(),"WARN"=>array());
    if (mb_strlen($s,'8bit')>60) { 
      $r['WARN'][]=Signs::STRTOOLONG;
      preg_match_all('`.`u', $s, $arr);
      $counter=0; $newstr="";
      foreach($arr[0] as $str) {
        $counter+=mb_strlen($str,'8bit');
        if ($counter>60) break;
        $newstr.=$str; }
      $s=$newstr; }
    $s=filter_var($s,FILTER_SANITIZE_STRING);
    $r['VAL']=$s;
    return $r; 
  }
	//function filterImgs(){$r=array("val"=>"","err"=>array(),"warn"=>array());/*check*/$r['val']=$s;return $r;}
	//function filterIcon(){$r=array("val"=>"","err"=>array(),"warn"=>array());/*check*/$r['val']=$s;return $r;}
}
