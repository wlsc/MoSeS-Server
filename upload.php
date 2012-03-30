<?php
session_start();

if(!isset($_SESSION['USER_LOGGED_IN']))
    die('Only registered users may access that file!');

//print_r($_POST);

include_once("./config.php");
include_once(MOSES_HOME."/include/functions/func.php");
include_once(MOSES_HOME."/include/functions/logger.php");
    
/**
*  SETTINGS FOR UPLOAD
*/
$allowedTypes = array('.apk');
$maxFileSize = 3145728; // 3MB
$uploadPath = './apk/'; // folder to save to

$filename = $_FILES['userfile']['name']; // gets filename
$fileExt = substr($filename, strripos($filename, '.'), strlen($filename)-1);
    
/**
* Connect to DB and get hashes for folder and file
*/
include_once(MOSES_HOME. "/include/functions/dbconnect.php");

$sql = "SELECT hash 
        FROM ". $CONFIG['DB_TABLE']['USER'] ." 
        WHERE userid = ". $_SESSION["USER_ID"];
       
$result = $db->query($sql);
$row = $result->fetch();

if(!empty($row)){
  
$HASH_DIR = $row['hash'];   
$HASH_FILE = md5(time() . $filename);

$uploadPath .= $HASH_DIR . "/";

// check if directory exists
clearstatcache();

if(!is_dir($uploadPath)){
    $oldumask = umask(0);
    if(!mkdir($uploadPath, 0777, true)){
        // folder failed to create
        umask($oldumask);
        header("Location: ucp.php?m=upload&res=0");
    }
    umask($oldumask); 
}
   
}else{
   // no hash for user found
   header("Location: ucp.php?m=upload&res=0");
}

/**
* Checking for necessary conditions
*/
if(!in_array($fileExt, $allowedTypes))
  header("Location: ucp.php?m=upload&res=2");
  //die('That filetype not allowed. Sorry.');
  //print_r($_FILES);
 //print_r(fileperms($_FILES['userfile']['tmp_name'])); 
if(filesize($_FILES['userfile']['tmp_name']) > $maxFileSize)
  header("Location: ucp.php?m=upload&res=3");
  //die('This file is too large. Sorry.');
       
if(!is_writable($uploadPath))
  header("Location: ucp.php?m=upload&res=4");
  //die("You don't have permission to upload.");
 
chmod($_FILES['userfile']['tmp_name'], 0777);       

/**
* Moving file into its directory and storing that data in DB
*/
if(is_uploaded_file($_FILES['userfile']['tmp_name']) 
    && move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadPath . $HASH_FILE . $fileExt)){
    
    // fix file permission
    if(!chmod($uploadPath . $HASH_FILE . $fileExt, 0777)){
       header("Location: ucp.php?m=upload&res=4"); 
    }
     
    /**
    * Building sensors string in JSON-Array-Format
    */
    if(isset($_POST['sensors']) && is_array($_POST['sensors']) && count($_POST['sensors']) > 0){
        
        $RAW_SENSOR_LIST = $_POST['sensors'];
        $SENSOR_LIST_STRING = '[';
        
        foreach($RAW_SENSOR_LIST as $sensor){
          $SENSOR_LIST_STRING .= $sensor .','; 
        }
        
        $SENSOR_LIST_STRING = substr($SENSOR_LIST_STRING, 0, -1) . ']';
        
    }else{
        $SENSOR_LIST_STRING = '[]';
    }
    
    /**
    * Parsing description of APKs
    */
    $APK_DESCRIPTION = '';
    
    if(isset($_POST['apk_description'])){
        
       $RAW_APK_DESCRIPTION = trim($_POST['apk_description']);
       
       // TODO: make some SQL-Injection security here
       
       $APK_DESCRIPTION = $RAW_APK_DESCRIPTION;
        
    }
    
    $APK_TITLE = trim($_POST['apk_title']);
    
    // TODO: add some security here
    $APK_ANDROID_VERSION = '';
    if(isset($_POST['apk_android_version'])){
        $APK_ANDROID_VERSION = trim($_POST['apk_android_version']);    
    }
    
    $APK_VERSION = '';
    if(isset($_POST['apk_version'])){
        $APK_VERSION = trim($_POST['apk_version']);    
    }
    
    $RESTRICTION_USER_NUMBER = -1;
    $SELECTED_USERS_LIST = '';
    
    // PREPARING VARIABLES FOR INSERTION TO DB
    $candidates = array();
    $pending_users = array();
    $notified_users = array();
    $USTUDY_FINISHED = 1;
    
    if(isset($_POST['restrict_users_number']) && isset($_POST['number_restricted_users'])){
        
        $RESTRICTION_CHECHED = (preg_replace('/[^0-9]/', '', $_POST['restrict_users_number']) == '1') ? true : false;
        
        // USER STUDY REQUESTED
        if($RESTRICTION_CHECHED){
            $RESTRICTION_USER_NUMBER = preg_replace('/[^0-9]/', '', $_POST['number_restricted_users']);
            
            include_once(MOSES_HOME."/include/managers/HardwareManager.php");
            // get the list of candidates with the specified android version
            $rows =  HardwareManager::getCandidatesForAndroid($db, $CONFIG['DB_TABLE']['HARDWARE'], $APK_ANDROID_VERSION);
            
            // check the filters
            if(!empty($rows)){
                
                $logger->logInfo("ROWS i upload.php #########################");
                $logger->logInfo(print_r($rows, true));
                
                foreach($rows as $hardware){
                    
                    
                    $hwFilter_array = json_decode($hardware['filter']);
                    $apkSensors_array = json_decode($SENSOR_LIST_STRING);
                    
                    $logger->logInfo("hwFilter_array #########################");
                    $logger->logInfo(print_r($hwFilter_array, true));
                    
                    if(isFilterMatch($hwFilter_array, $apkSensors_array)){
                        $candidates[] = intval($hardware['hwid']);
                }
            }
            
            shuffle($candidates);
            
            }
        }
        
        $USTUDY_FINISHED = 0; // a user study has to be done
        
    }
    
    
    // WRITE APK TO DATABASE AND START USER STUDY IF NEEDED
   
    // convert to json 
    $candidates = json_encode($candidates);
    $pending_users = json_encode($pending_users);
    $notified_users = json_encode($notified_users);
    
    /**
    * Store filename, hash in DB and other informations
    */
    $sql = "INSERT INTO ". $CONFIG['DB_TABLE']['APK'] ." (userid, userhash, apkname, apk_version
                             apkhash, sensors, description,
                             apktitle, restriction_device_number, pending_devices,
                             candidates, notified_devices, androidversion, ustudy_finished)
                              VALUES 
                              (". $_SESSION["USER_ID"] .", '". $HASH_DIR ."', '". $filename ."', '".$APK_VERSION."',
                              '". $HASH_FILE ."', '". $SENSOR_LIST_STRING ."', '". $APK_DESCRIPTION ."',
                              '". $APK_TITLE ."', ". $RESTRICTION_USER_NUMBER .", '". $pending_users ."',
                              '". $candidates ."', '". $notified_users ."', '". $APK_ANDROID_VERSION ."', ". $USTUDY_FINISHED .")";
    // WARNING: hashed filename is WITHOUT .apk extention!
                             
    $db->exec($sql);
    //$db->close();
    
    //$LAST_INSERTED_ID = $db->lastInsertId();
    
  //  include_once(MOSES_HOME ."/include/managers/GooglePushManager.php");
                      
    
    // ##### TEMP ##############################
    
   // $temp = array();
        
   // foreach(json_decode($candidates) as $candidate){
     //   $sql = "SELECT c2dm FROM hardware WHERE hwid=".$candidate;
      //  $result = $db->query($sql);
       // $row = $result->fetch();
        //if(!empty($row)){
        //    $temp[] = array("c2dm" => $row['c2dm']);
        //}
    //}
    
    
   // $logger->logInfo("PUSH SENT TO #########################");
    //$logger->logInfo(print_r($temp, true));
    
    
   //GooglePushManager::googlePushSend($LAST_INSERTED_ID, $temp, $logger);
   
   //include_once(MOSES_HOME."/ustudy.php");
   

    header("Location: ucp.php?m=upload&res=1");
    //echo 'Your file "'. $filename .'" was successfully uploaded.';
}else{
    header("Location: ucp.php?m=upload&res=0");
    //echo 'Some error occured while uploading a file. Please try again later.';
}

?>