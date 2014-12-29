<?php

/*------------------------------------------------------------------------
# GRA4 
# ------------------------------------------------------------------------
# author    gra4.com
# copyright Copyright (C) 2012 gra4.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://gra4.com
# Technical Support:  http://gra4.com/groups/profile/96/gra4-webmasters-joomla
-------------------------------------------------------------------------*/

//called only once, when the client system connects to rga4
if (!defined('IN_GRA4')) {//make sure it can be called only from the php, not directly over the web
    exit;
}


//fetches data over the web
function GRA4FetchWebData($strUrl, $strHeadersToSend, &$aResultHeaders) {

//do we have CURL ?		it's ~30% faster than fopen
    if (in_array('curl', get_loaded_extensions())) 
    { //yes, we have cURL ! =)
        return GRA4FetchWebDataCurl($strUrl, $strHeadersToSend, $aResultHeaders);
    }
   return ("FATAL ERROR:  cUrl are not allowed in your system. GRA4 Does now know how to talk to the server...  =( ");
//Sorry, we die here. Functions for open wrappers were never completely tested yet.


//do we have fopen wrapper ?  
    if (ini_get(allow_url_fopen) == 1) 
    {
        return GRA4FetchWebDataFopen($strUrl, $strHeadersToSend, $aResultHeaders);
    }
//no, cURL is not available as well. Error out!
    return ("FATAL ERROR: URL-aware fopen wrappers and cUrl are not allowed in your system. GRA4 Does now know how to talk to the server...  =( ");
}


function GRA4FetchWebDataCurl($strUrl, $strHeadersToSend, &$aResultHeaders) //we return $aResultHeaders
{
    $aFilesToDelete = array(); //just to declare
    $aData = GRA4BuildCurlPostData($aFilesToDelete);
//print_r($aData);die;		
    $aHeadersToSend = explode("\r\n", trim($strHeadersToSend));
    $aHeadersToSend = GRA4HeadersToRFC($aHeadersToSend);
//print_r($aHeadersToSend);	
    $options = array(
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0, //works for any version of php	
        CURLOPT_FAILONERROR => false, //no show errors
        CURLOPT_RETURNTRANSFER => true, // return data
        CURLOPT_HEADER => true, // return headers
        CURLOPT_FOLLOWLOCATION => false, // no follow redirects
        CURLOPT_ENCODING => "", // handle all encodings
        CURLOPT_AUTOREFERER => true, // set referer on redirect
        CURLOPT_MAXREDIRS => 0, // stop after redirects
        CURLOPT_VERBOSE => false, //Writes output to STDERR if true
        CURLOPT_POST => true, // i am sending post data
        CURLOPT_POSTFIELDS => $aData, // this are my post vars
        CURLOPT_HTTPHEADER => $aHeadersToSend,
        CURLINFO_HEADER_OUT => true,
        CURLOPT_CONNECTTIMEOUT => 0, 		//indefinately
        CURLOPT_TIMEOUT => 60, 				//in seconds
    );

    
    if( !($ch = curl_init($strUrl)))
		return ("FATAL ERROR:  cUrl are not allowed in your system. GRA4 Does now know how to talk to the server...  =( ");
		
//compatibility with php 5.5	
//TODO redo file upload with CURLFile		
	@curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true); 

// set URL and other appropriate options
    curl_setopt_array($ch, $options);
// grab URL and pass it to the browser
    $strResultData = curl_exec($ch); //raw data, contians headers!
    if ($strResultData === false) {
        $strError = "cURL failed. Please reload this page. If the problem persists - check your PHP settings.";
        $strError .= "\n<br>" . curl_error($ch);
        $strError .= "\n<br>Current Maximum Upload Size is " . ini_get('upload_max_filesize');
        $strError .= "\n<br>Current Maximum POST Size is " . ini_get('post_max_size');
        return $strError;
    }
//print_r(curl_getinfo($ch));die("now:".date(DATE_RFC822));
    $aResultHeadersSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//TROF
//print_r($strResultData);die;
    $strResultHeaders = mb_substr($strResultData, 0, $aResultHeadersSize - mb_strlen("\r\n\r\n"));
//print_r("|$strResultHeaders|"); //die;	
    $aResultHeaders = explode("\r\n", $strResultHeaders);
    $strResultData = mb_substr($strResultData, $aResultHeadersSize); //skip headers
//print_r($strResultData);die;	 	
// close cURL resource, and free up system resources
    curl_close($ch);
//remove files if we had uploads
    for ($d = 0; $d < count($aFilesToDelete); $d++) 
    {
        if (file_exists($aFilesToDelete[$d])) 
        {
            unlink($aFilesToDelete[$d]);
        }
    }
    return $strResultData;
//	return ("NOT IMPLEMENTED: The URL-aware fopen wrappers are off in your configuration, so we have to use cURL, but  function GRA4FetchWebDataCurl() in ".__FILE__." is not written yet =( ");
}

function GRA4BuildCurlPostData(&$aFilesToDelete) //going to return $aFilesToDelete
{
    $aFileRet = array(); //here we going to keep formatted filenames to merge with POST  data
    $aFieldNames = array_keys($_FILES);
    for ($i = 0; $i < count($_FILES); $i++) 
    {
        $vFileName = $_FILES[$aFieldNames[$i]]['name'];
        if (is_array($vFileName)) 
        {
            for ($j = 0; $j < count($vFileName); $j++) 
            {
                $strFileName = $_FILES[$aFieldNames[$i]]['name'][$j];
                if (mb_strlen($strFileName) > 0) 
                {
                    $strRealFileName = sys_get_temp_dir() . '/' . $strFileName;
                    array_push($aFilesToDelete, $strRealFileName);
                    $strTempFileName = $_FILES[$aFieldNames[$i]]['tmp_name'][$j];
                    $strFileType = $_FILES[$aFieldNames[$i]]['type'][$j];

                    if (file_exists($strRealFileName)) 
                    {
                        unlink($strRealFileName);
                    }

                    move_uploaded_file($strTempFileName, $strRealFileName);
                    $bRet = file_exists($strRealFileName);

//NOTYPE
                    $aFileRet[$aFieldNames[$i] . '[' . $j . ']'] = '@' . $strRealFileName . ';type=' . $strFileType;
//                   $aFileRet[$aFieldNames[$i] . '[' . $j . ']'] = '@' . $strRealFileName . ';';					

//all error handling suppose to be done on GRA4 server,
// but you may want to uncomment it for the troubleshooting
                    /*
                      if($bRet !== TRUE)
                      {
                      die("Multiple files - can't move '" . $strTempFileName . "' to '". $strRealFileName."'\n<br>Please adjust upload settings for this server.");
                      }
                     */
                }//if (mb_strlen($strFileName) > 0) 
            }
        } 
        else //not array of files - single file
        {
            if (mb_strlen($vFileName) > 0) 
            {
                $strRealFileName = sys_get_temp_dir() . '/' . $vFileName;
                array_push($aFilesToDelete, $strRealFileName);
                $strTempFileName = $_FILES[$aFieldNames[$i]]['tmp_name'];
                $strFileType = $_FILES[$aFieldNames[$i]]['type'];

                if (file_exists($strRealFileName)) 
                {
                    unlink($strRealFileName);
                }
                move_uploaded_file($strTempFileName, $strRealFileName);
                $bRet = file_exists($strRealFileName);

//NOTYPE	
                $aFileRet[$aFieldNames[$i]] = '@' . $strRealFileName . ';type=' . $strFileType;
//                $aFileRet[$aFieldNames[$i]] = '@' . $strRealFileName . ';';				

//all error handling suppose to be done on GRA4 server,
// but you may want to uncomment it for the troubleshooting
                /*
                  if($bRet === TRUE)
                  {
                  die("Singe file - can't move '" . $strTempFileName . "' to '". $strRealFileName."'\n<br>Please adjust upload settings for this server.");
                  }
                 */
            }//if we have filename
        }//end single ile processing
    }//loop thtough $_FILES

    $aT = explode('&', http_build_query($_POST));
//quick fix for POST fields containing '@' we add "\r" before '@', it's gonna be trimmed out
	$aT = str_replace('=%40','=%0D%40',$aT); //IMPORTANT!
    
    unset($_POST);
	$_POST = array(); //so is not gonna hickup    

    for ($i = 0; $i < count($aT); $i++) {
        $aT2 = explode('=', $aT[$i]);
        if(isset($aT2[1]))
		{
       	 	if (get_magic_quotes_gpc() == 1) {
            	$_POST[urldecode($aT2[0])] = stripslashes(urldecode($aT2[1]));
	        } else {
    	        $_POST[urldecode($aT2[0])] = urldecode($aT2[1]);
        	}
    	}
    }
    $aRet = array_merge($_POST, $aFileRet);
    return($aRet);
}


//Some hosting configuration do not pass non-RFC headers ( like "GRA4_...") to server, 
// nor convert incoming non-RFC headers  to $_SERVER fields (why they even bother?)
//So, we have to cheat. This is butt-ugly and must ber redone in the next major release.
function GRA4HeadersToRFC($aHeadersToSend, $bEraseGra4Headers = true) //$bEraseGra4Headers for compatibility
{
//print_r($aHeadersToSend);
//return($aHeadersToSend); //if you want to swith the function off
	$strStandardRfcHeader = 'Pragma: '; //this one we are going to modify
	$strRfcHeader = ''; //to declare
	$iStandardRfcHeaderIndex = -1; //let's do it right. Now no browsers send "Pragma:" to server, but RFC does not forbid it
	$aReturn = array(); //going to return this one
	for($i=0; $i < count($aHeadersToSend); $i++)
	{
		if(mb_strpos($aHeadersToSend[$i],'GRA4_') === 0) //starts with. === 0, not FALSE
		{
			$strName = mb_substr($aHeadersToSend[$i],0,mb_strpos($aHeadersToSend[$i],': '));
			$strValue = mb_substr($aHeadersToSend[$i],mb_strpos($aHeadersToSend[$i],': ')+mb_strlen(': '));
			$strRfcHeader .= $strName . '=' . urlencode(trim($strValue)) . ' '; //urlencode - no spaces, space is separator
//echo("$i|$strName|$strValue|\n");
		}//if our header
		else //not our header
		{
			if($bEraseGra4Headers === true) //we put in the result only non-gra4 headers
			{
				array_push($aReturn,$aHeadersToSend[$i]); //going to need it
			}
			if(mb_strpos($aHeadersToSend[$i],$strStandardRfcHeader) === 0) //found it
			{
				$iStandardRfcHeaderIndex = $i; //we will update Pragma, not add new
			}
		}
		if($bEraseGra4Headers === false) //we put in the result all headers
		{
			array_push($aReturn,$aHeadersToSend[$i]); //going to need it
		}

	}//loop through all headers
//ok, let's form RFC-ok header and put it on place
	if($iStandardRfcHeaderIndex == -1) //we don't have Pragma. Most Likely
	{
		array_push($aReturn, $strStandardRfcHeader . $strRfcHeader);
	}
	else //we had Pragma... Wow! =)
	{
		$aReturn[$iStandardRfcHeaderIndex] = $aReturn[$iStandardRfcHeaderIndex] . ' ' . $strRfcHeader;
	}
//print_r($aReturn);	die(' EOF GRA4HeadersToRFC()');
	return($aReturn);
}//EOF GRA4HeadersToRFC()

//backward process (see above) - we do it when we receive headers from remote site
function GRA4RfcHeadersToServerVars($strRfcHeaders = '') //if not set - going to use $_SERVER['HTTP_PRAGMA']
{
	if($strRfcHeaders == '')
	{
		$strRfcHeaders = GRA4GetServerVar('HTTP_PRAGMA'); //we use 'pragma' - see above
	}
	$aGra4Headers = explode(' ', $strRfcHeaders); //space
	for($i=0; $i < count($aGra4Headers); $i++)
	{
		if(mb_strpos($aGra4Headers[$i],'GRA4_') === 0) //starts with. === 0, not FALSE
		{
			$strName = mb_substr($aGra4Headers[$i],0,mb_strpos($aGra4Headers[$i],'='));
			$strValue = urldecode(mb_substr($aGra4Headers[$i],mb_strpos($aGra4Headers[$i],'=')+mb_strlen('=')));
			$_SERVER['HTTP_PRAGMA'] = str_replace($aGra4Headers[$i],'',GRA4GetServerVar('HTTP_PRAGMA'));
			$_SERVER['HTTP_'.$strName] = $strValue;
		}//our field - header
	}//loop throug fields
}//EOF GRA4RfcHeadersToServerVars

//----------------------------------------------------------------------------------------------------
//bellow this line are functions not in use right now. Hopefuly someone will debut and test them one day
//technically they work. Or at least use to work (first GRA4 was running on fopen). But they were never tested on multyfile uploads,
//that's why we did switch to Curl. 
//Called in GRA4FetchWebDataFopen(), so NOT IN USE for now.
function GRA4BuildHttpContent() {
    $strPost = http_build_query($_POST);

    if ((count($_FILES) === 0)) {
        return $strPost; //we done
    }

    $strRet = '';
    $strContentType = GRA4GetServerVar('CONTENT_TYPE');
    $boundary = mb_substr($strContentType, mb_strpos($strContentType, 'boundary=') + mb_strlen('boundary='));

    $aPost = explode('&', urldecode($strPost));
    for ($i = 0; $i < count($aPost); $i++) {
        $aKeyValue = explode('=', $aPost[$i]);
        $strRet .= "--$boundary\n";
        $strRet .= "Content-Disposition: form-data; name=\"" . $aKeyValue[0] . "\"\n\n" . $aKeyValue[1] . "\n";
    }

    $strRet .= "--$boundary\n";
//Collect Filedata
    foreach ($_FILES as $key => $file) {
        if (mb_strlen($file['tmp_name']) == 0) {
            continue;
        }
        $fileContents = file_get_contents($file['tmp_name']);
        if (!unlink($file['tmp_name'])) {
//			GRA4Log("Can't delete",$file['tmp_name']);
        }

        $strRet .= "Content-Disposition: form-data; name=\"{$key}\"; filename=\"{$file['name']}\"\n";
        $strRet .= "Content-Type: " . $file['type'] . "\n";
        $strRet .= "Content-Transfer-Encoding: binary\n\n";
        $strRet .= $fileContents . "\n";
        $strRet .= "--$boundary--\n";
    }

    /*
      echo("FILES:\n");print_r($_FILES);
      echo("POST:\n");print_r($_POST);
      echo("SERVER:\n");print_r($_SERVER);
      echo("strRet:\n");print_r($strRet);
      die;
     */
    return $strRet;
}//EOF GRA4BuildHttpContent

//file_get_contents implementetion NOT IN USE for now
function GRA4FetchWebDataFopen($strUrl, $strHeadersToSend, &$aResultHeaders) {
//ATT! Does not work for all webservers (sometimes headers are not sent out)
    $strContentToSend = GRA4BuildHttpContent();
    $strMethod = GRA4GetServerVar('REQUEST_METHOD'); //must use the same 
 
    $aHeadersToSend = explode("\r\n", trim($strHeadersToSend));
    $aHeadersToSend = GRA4HeadersToRFC($aHeadersToSend);
    $strHeadersToSend = implode("\r\n",$aHeadersToSend)."\r\n";
//print_r($strHeadersToSend);

    $opts = array(
        'http' => array(
            'max_redirects' => 0,
            'method' => $strMethod,
            'header' => $strHeadersToSend,
            'protocol_version' => 1.0, //so we do not depend on php version
            'ignore_errors' => true, //
            'content' => $strContentToSend,
        )
    );
//print_r($opts); die;
    $context = stream_context_create($opts);
    stream_context_set_option($context, 'http', 'header', 'User-Agent: RRR\r\n');
//print_r(stream_context_get_options($context));	die;
    $strResultData = file_get_contents($strUrl, false, $context);
    $aResultHeaders = $http_response_header;
    return $strResultData;
}//EOF GRA4FetchWebDataFopen()


//No closing php tag


