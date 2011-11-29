<?php
/**
 * "ZenX" PHP data manupulation library.
 *
 * @author Konstantin Dvortsov <kostya.dvortsov@gmail.com>. You can 
 * also track me down at {@link http://dvortsov.tel dvortsov.tel }
 * @version 1.0
 * @package ZenX 
 * @license http://opensource.org/licenses/gpl-3.0.html GNU Public License
 */
/**
 * Signs interface contains constants with descriptions of visual elements
 * and error explanations. Override/replace this class for localization.
 *
 * @package ZenX
 * @author Konstatin Dvortsov
 */
interface Signs{
  const VIEWFORM="Details";
  const VIEWHINT="View Details";
  const REMOVE="Delete Selected Records";
  const DELSIMBOL="✘";
  const SUBMIT="Save";
  const SUBMITHINT="Save Data Shown on Screen";
  const BACK="Back";
  const BACKHINT="Back to Records List";
  const ADDNEW="Add";
  const ADDHINT="Add New Record";
  const SEARCH="Search";
  const SEARCHHINT="Find Records Matching the Specified Criteria";
  const RESET="Reset";
  const RESETHINT="Clear Search and Sort Parameters";
  const KEYBLOCKED="This table is write-protected!";
  const FIELDBLOCKED="This field is write-protected!";
  const STRTOOLONG="The string in this field was trimmed due to oversize!";
  const WRONGKEY="Wrong Key Format!";
  const WRONGINT="Wrong Integer Format!";
	const WRONGFLOAT="Wrong Float Number Format!";
  const TEXTTOOLONG="Text size is too big! Text can not exceed 64 kb!";
  const GENSAVEERR="all fields";
  const GENSAVEERRTXT="All fields can not be empty!";
  const SAVEERRHEADER="Error!";
  const ERRORAT="Error at Field ";
  const SAVENOTIFHEADER="Warning!";
  const NOTEAT="Data at Field ";
  const WRONGDATE="Wrong Date Format! Please input date in YYYY-MM-DD format!";
  const NUMBERCLEARED="Invalid characters in numeric field were deleted!";
  const PAGES="Pages: ";
  const CANTBENULL="This field can not be empty, please fill it in!";
  const MUSTBEUNIQUE="Such value already exists! Values in this field must be unique!";
  const FUEEXCEEDSERV="File was not uploaded! File size exceeds limit set at server!";
  const FUEEXCEEDFORM="File was not uploaded! File size exceeds limit set by sender!";
  const FUEPARTIAL="File was not uploaded! File was uploaded partially!";
  const FUENOTMPDIR="File was not uploaded! Access to temporary file folded is restricted!";
  const FUECANTWRITE="File was not uploaded! File write error!";
  const FUEEXTERR="File was not uploaded! One of PHP extensions broke the uploading process!";
  const FILESIZETOOBIG="File was not uploaded! File size exceeds limit set by administrator for this date type!";
  const IMGDELONUPDATE="Delete Existing Picture";
}
