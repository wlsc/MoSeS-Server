<?php /*******************************************************************************
 * Copyright 2013
 * Telecooperation (TK) Lab
 * Technische Universität Darmstadt
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *   http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 ******************************************************************************/ ?>
<?php

/**
 * @author: Zijad Maksuti
 */
 
// Cronjob for sending notifications about available apk update to clients

/* ustudy_finished encodings
* 0  user-study
* 1  finished
* 2 definitely finished and devices have been notified about a survey (if any)
*/

// get all apks
include_once('/home/dasense/moses/config.php');
include_once(MOSES_HOME."/include/functions/cronLogger.php");
include_once(MOSES_HOME. "/include/functions/dbconnect.php");
include_once (MOSES_HOME."/include/managers/ApkManager.php");
include_once (MOSES_HOME."/include/managers/LoginManager.php");
include_once (MOSES_HOME."/include/managers/HardwareManager.php");
include_once (MOSES_HOME."/include/managers/GooglePushManager.php");
include_once (MOSES_HOME."/include/managers/SurveyManager.php");

$logger->logInfo(" ###################### UPDATE CRONJOB END ############################## ");

// Select all user studies that have an updated apk
$sql = "SELECT * FROM " .$CONFIG['DB_TABLE']['APK']. " WHERE apk_updated = 1";
$result = $db->query($sql);
$rows = $result->fetchAll(PDO::FETCH_ASSOC);

// iterate over all user studies
foreach($rows as $row){
	// Get Apkid
	$APK_ID = $row['apkid'];
	$logger->logInfo("update.php APK_ID=".$APK_ID);
	
	// get all devices that have installed the apk
	$installedOn = $row['installed_on'];
	if(empty($installedOn))
		$installedOn = array();
	else
		$installedOn = json_decode($installedOn);
	
	if(!empty($installedOn)){
		GooglePushManager::sendSurveyAvailableToHardware($db, $APK_ID, $installedOn, $logger, $CONFIG);
	}
	ApkManager::demarkUpdateAvailable($db, $CONFIG['DB_TABLE']['APK'], $APK_ID, $logger);
}

$logger->logInfo(" ###################### UPDATE CRONJOB END ############################## ");
?>
