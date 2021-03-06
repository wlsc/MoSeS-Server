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

/*
 * @author: Wladimir Schmidt
 * @author: Zijad Maksuti
 * @author: Sandra Christina Amend
 */

/**
* Class that manages APK (I promise!)
*/
class ApkManager{

	public function __construct(){

	}

	/**
	 * Returns all APKs in DB
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 */
	public static function getAllApk($db, $apkTable){

		$sql = "SELECT *
				FROM ". $apkTable;

		$result = $db->query($sql);
		$array = $result->fetchAll(PDO::FETCH_ASSOC);

		return $array;
	}

	/**
	 * Returns all APKs in DB that are not published in a user-study
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 */
	public static function getNonStudyAllApk($db, $apkTable){

		$sql = "SELECT *
				FROM ". $apkTable ." WHERE private=0";

		$result = $db->query($sql);
		$array = $result->fetchAll(PDO::FETCH_ASSOC);

		return $array;
	}

	/**
	 * Returns all APKs in DB that are not published in a user-study and
	 * that can run on the specified android version.
	 *
	 * @param mixed $db the database instance
	 * @param mixed $apkTable the name of the apk table
	 * @param int $minAndroidVersion the minimal android version on which the apk should be runnable
	 * @return array an array containing all apks that meet the requirements. If no such apks exist, an empty array is returned.
	 */
	public static function getNonStudyAllApkRegardingMinAndroidVersion($db, $apkTable, $minAndroidVersion){

		$sql = "SELECT *
				FROM ". $apkTable ." WHERE private=0 AND ustudy_finished =0 AND androidversion<=".$minAndroidVersion;

		$result = $db->query($sql);
		$array = $result->fetchAll(PDO::FETCH_ASSOC);

		return $array;
	}

	/**
	 * Returns all APKs in DB that can run on the specified android version by the given user with the given device.
	 *
	 * @param mixed $db the database instance
	 * @param $userId the user id
	 * @param mixed $apkTable the name of the apk table
	 * @param int $minAndroidVersion the minimal android version on which the apk should be runnable
	 * @return array an array containing all apks that meet the requirements. If no such apks exist, an empty array is returned.
	 */
	public static function getAllApkRegardingMinAndroidVersion($logger, $db, $userId,$CONFIG, $deviceID, $minAndroidVersion){
		$logger->logInfo("getAllApkRegardingMinAndroidVersion() minAndroidVersion=".$minAndroidVersion);
		$array = ApkManager::getPublicAPKs($logger, $db, $userId, $CONFIG, $deviceID, $minAndroidVersion);
		 
		$array=array_merge_recursive($array, ApkManager::getAllGroupAPKs($logger, $db, $userId, $CONFIG, $deviceID, $minAndroidVersion));
		
		$inviteOnlyApksForDevice = ApkManager::getAllInviteOnlyApkRegardingDeviceid($logger, $db, $userId, $deviceID, $CONFIG);
		$array = array_merge_recursive($array, $inviteOnlyApksForDevice);
		return $array;
	}
	
	
	public static function getPublicAPKs($logger, $db, $userId,$CONFIG, $deviceID, $minAndroidVersion){
		$sql = "SELECT *
				FROM ". $CONFIG['DB_TABLE']['APK'] ." 
                WHERE private=0 AND 
                      ustudy_finished =0 AND 
                      inviteinstall=0 AND 
                      (CURDATE() >= startdate OR startdate IS NULL)
						AND 
                      androidversion<=".$minAndroidVersion;
		
        $logger->logInfo("getPublicAPKs() sql=".$sql);
		
		$result = $db->query($sql);
		$array = $result->fetchAll(PDO::FETCH_ASSOC);

		return $array;
	}
	
	public static function getAllGroupAPKs($logger, $db, $userId,$CONFIG, $deviceID, $minAndroidVersion){
		$groupName = LoginManager::getGroupName($logger, $db, $CONFIG['DB_TABLE']['USER'], $userId);
		$array = array();
		if(!empty($groupName)){
			$sqlGroupMembers = "SELECT members 
                                FROM ".$CONFIG['DB_TABLE']['RGROUP']." 
                                WHERE name='".$groupName."'";
			$result2 = $db->query($sqlGroupMembers);
			$rowMembers = $result2->fetch(PDO::FETCH_ASSOC);
			if(!empty($rowMembers)){
				$members = json_decode($rowMembers['members']);
				foreach($members as $member){
					$sqlGetAPKsPublishedByUser = "SELECT *
							                      FROM ". $CONFIG['DB_TABLE']['APK'] ." 
                                                  WHERE private=1 AND 
                                                        ustudy_finished =0 AND 
							                      		inviteinstall=0 AND 
                                                        androidversion<=".$minAndroidVersion." AND 
                                                        (CURDATE() >= startdate OR startdate IS NULL) AND  
                                                        userid=".$member;
					$result3 = $db->query($sqlGetAPKsPublishedByUser);
					$rowsAPKs = $result3->fetchAll(PDO::FETCH_ASSOC);
					$array = array_merge_recursive($array, $rowsAPKs);
				}
					
			}
		}
		return $array;
	}
	
	public static function getAllGroupAPKsWithInvite($logger, $db, $userId,$CONFIG, $deviceID, $minAndroidVersion){
		$groupName = LoginManager::getGroupName($logger, $db, $CONFIG['DB_TABLE']['USER'], $userId);
		$array = array();
		if(!empty($groupName)){
			$sqlGroupMembers = "SELECT members
                                FROM ".$CONFIG['DB_TABLE']['RGROUP']."
                                WHERE name='".$groupName."'";
			$result2 = $db->query($sqlGroupMembers);
			$rowMembers = $result2->fetch(PDO::FETCH_ASSOC);
			if(!empty($rowMembers)){
				$members = json_decode($rowMembers['members']);
				foreach($members as $member){
					$sqlGetAPKsPublishedByUser = "SELECT *
							                      FROM ". $CONFIG['DB_TABLE']['APK'] ."
                                                  WHERE private=1 AND
                                                        ustudy_finished =0 AND
							                      		inviteinstall=1 AND
                                                        androidversion<=".$minAndroidVersion." AND
                                                        (CURDATE() >= startdate OR startdate IS NULL) AND
                                                        userid=".$member;
					$result3 = $db->query($sqlGetAPKsPublishedByUser);
					$rowsAPKs = $result3->fetchAll(PDO::FETCH_ASSOC);
					$array = array_merge_recursive($array, $rowsAPKs);
				}
					
			}
		}
		
		// the user should see only the private apks with invite, which he has already installed OR for which he has aquired a notification
		$toBeReturned = array();
		$hardwareId = HardwareManager::getHardwareID($db, $CONFIG['DB_TABLE']['HARDWARE'], $userId, $deviceID);
		foreach ($array as $apk){
			// get devices that may install or have installed the apk
			$pendingDevices = json_decode($apk['pending_devices']);
			$installedOn = array();
			$tempInstalledOn = $apk['installed_on'];
			if(!empty($tempInstalledOn))
				$installedOn = json_decode($tempInstalledOn);
			if(in_array($hardwareId, $pendingDevices) || in_array($hardwareId, $installedOn))
				$toBeReturned[]=$apk;
				
		}
		
		return $toBeReturned;
		
		
	}
	
	
	/**
	 * Returns all invite only APKs in DB that can run on the device with the specified id.
	 *
	 * @param mixed $db the database instance
	 * @param $userId the user id
	 * @param mixed $apkTable the name of the apk table
	 * @return array an array containing all apks that meet the requirements. If no such apks exist, an empty array is returned.
	 */
	public static function getAllInviteOnlyApkRegardingDeviceid($logger, $db, $userId, $deviceID, $CONFIG){
		
        $sql = "SELECT * 
                FROM ". $CONFIG['DB_TABLE']['APK'] ." 
                WHERE inviteinstall=1 AND 
                      ustudy_finished =0 AND private=0";
			
		$logger->logInfo("getAllInviteOnlyApkRegardingDeviceid() sql=".$sql);
	
		$result = $db->query($sql);
		$array = $result->fetchAll(PDO::FETCH_ASSOC);
			
		$toBeReturned = array();
		$hardwareId = HardwareManager::getHardwareID($db, $CONFIG['DB_TABLE']['HARDWARE'], $userId, $deviceID);
		foreach ($array as $apk){
			// get devices that may install or have installed the apk
			$pendingDevices = json_decode($apk['pending_devices']);
			$installedOn = array();
			$tempInstalledOn = $apk['installed_on'];
			if(!empty($tempInstalledOn))
				$installedOn = json_decode($tempInstalledOn);
			if(in_array($hardwareId, $pendingDevices) || in_array($hardwareId, $installedOn))
				$toBeReturned[]=$apk;
			
		}
	
		return $toBeReturned;
	}


	/**
	 * Returns a particular APK.
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 * @param mixed $userID
	 * @param mixed $apkID
	 * @return mixed A row containing the apk or. If no apk with the provided apkID exists, an empty row is returned.
	 */
	public static function getApk($db, $apkTable, $apkID, $logger){

		$sql = "SELECT *
				FROM ". $apkTable ."
						WHERE apkid = ". intval($apkID);

        $logger->logInfo("getApk sql=".$sql);
                        
		$result = $db->query($sql);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		 
		return $row;
	}

	/**
	 * Increments the number that tells how many times the apk has been downloaded
	 * returns true if the apk with the provided id exists, false otherwise
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 * @param mixed $apkID
	 */
	public static function incrementAPKUsage($db, $apkTable, $apkID, $hwid, $logger){
		$logger->logInfo("incrementAPKUsage");
		// checking if the apk exists
		$sql = "SELECT * FROM ". $apkTable ." WHERE apkid = ". intval($apkID);
		$result = $db->query($sql);
		$row = $result->fetch();
		if(empty($row)){
			return false;
		}
		else{
			$devs = $row['installed_on'];
			if(empty($devs)){
				$devs = array();
			}
			else{
				$devs = json_decode($devs);
			}
			$devs[] = intval($hwid);
			$devs = array_unique($devs);
			sort($devs);
			$part = count($devs);
			$devs=json_encode($devs);
			$sql1 = "UPDATE " .$apkTable. " SET participated_count=".$part." WHERE apkid= ".$apkID;
			$sql2 = "UPDATE ".$apkTable . " SET installed_on='".$devs."' WHERE apkid=".$apkID;
			$db->exec($sql1);
			$db->exec($sql2);
		}
		return true;
	}


	/**
	 * Decrements the number that tells how many times the apk has been downloaded
	 * returns true if the apk with the provided id exists, false otherwise
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 * @param mixed $apkID
	 */
	public static function decrementAPKUsage($db, $apkTable, $apkID, $hwid, $logger){

		$logger->logInfo("decrementAPKUsage");
		// checking if the apk exists

		$sql = "SELECT * FROM ". $apkTable ." WHERE apkid = ". intval($apkID);
		$result = $db->query($sql);
		$row = $result->fetch();
		$devs = $row['installed_on'];
		if(empty($devs)){
			return;
		}
		else{
			$devs = json_decode($devs);
		}
		$new_devs = array();
		foreach($devs as $hw_old){
			if($hw_old != $hwid)
				$new_devs[] = $hw_old;
		}
		$new_devs = array_unique($new_devs);
		sort($new_devs);
		$part = count($new_devs);
		$new_devs=json_encode($new_devs);
		$sql1 = "UPDATE " .$apkTable. " SET participated_count=".$part." WHERE apkid= ".$apkID;
		$sql2 = "UPDATE ".$apkTable . " SET installed_on='".$new_devs."' WHERE apkid=".$apkID;
		$db->exec($sql1);
		$db->exec($sql2);
	}



	/**
	 * retrives restriction number of the given apkID
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 * @param mixed $apkID
	 * @return mixed
	 */
	public static function getRestrictionUserNumber($db, $apkTable, $apkID){

		$sql = "SELECT restriction_user_number FROM " .$apkTable. " WHERE apkid= ".$apkID;

		$result = $db->query($sql);
		$row = $result->fetch();

		if(!empty($row))
			return $row['restriction_user_number'];

		return null;

	}

	/**
	 * returns true if the deviceID is in the list of pending devices (for user study)
	 *
	 * @param mixed $db
	 * @param mixed $apkTable
	 * @param mixed $apkID
	 * @param mixed $userID
	 */
	public static function isSelectedDevice($db, $apkTable, $apkID, $hardwareID){

		$sql = "SELECT pending_devices FROM " .$apkTable. " WHERE apkid= ".$apkID;

		$result = $db->query($sql);
		$row = $result->fetch();

		if(!empty($row)){
			$selectedDevices = explode(',', $row['pending_devices']);
			if(in_array($hardwareID, $selectedDevices))
				return true;
		}

		return false;

	}

	/**
	 * Markes the userstudy as finished
	 *
	 * @param mixed $db the database
	 * @param mixed $apkTable the table name
	 * @param mixed $apkID the id of the apk whose user study should be marked as finished
	 * @param mixed $logger the logger
	 */
	public static function markUserStudyAsFinished($db, $apkTable, $apkID, $logger){
		//     	$logger->logInfo("markUserStudyAsFinished() called");
		// checking if the apk exists
		$sql = "UPDATE ". $apkTable ." SET ustudy_finished=1 WHERE apkid = ". intval($apkID);
		$db->exec($sql);
	}
	
	/**
	 * Markes the userstudy as definitely finished (all devices have been notified about an
	 * available survey if any)
	 *
	 * @param mixed $db the database
	 * @param mixed $apkTable the table name
	 * @param mixed $apkID the id of the apk whose user study should be marked as finished
	 * @param mixed $logger the logger
	 */
	public static function markUserStudyAsDefinitelyFinished($db, $apkTable, $apkID, $logger){
		//     	$logger->logInfo("markUserStudyAsFinished() called");
		// checking if the apk exists
		$sql = "UPDATE ". $apkTable ." SET ustudy_finished=2 WHERE apkid = ". intval($apkID);
		$db->exec($sql);
	}
	
	/**
	 * Demarks the "update available flag" of the apk in the database.
	 *
	 * @param mixed $db the database
	 * @param mixed $apkTable the table name
	 * @param mixed $apkID the id of the apk whose "update available flag" should be unset
	 * @param mixed $logger the logger
	 */
	public static function demarkUpdateAvailable($db, $apkTable, $apkID, $logger){
		$sql = "UPDATE ". $apkTable ." SET apk_updated=0 WHERE apkid = ". intval($apkID);
		$db->exec($sql);
	}

	/**
	 * Inserts current timestamp to time_enough_participants
	 *
	 * @param mixed $db the database
	 * @param mixed $apkTable the table name
	 * @param mixed $apkID the id of the apk in whose row the timetamp should be updated
	 */
	public static function insertTimestampToTimeEnoughParticipants($db, $apkTable, $apkID){
		$sql = "UPDATE ". $apkTable ." SET time_enough_participants=". time() ." WHERE apkid = ". intval($apkID);
		$db->exec($sql);
	}
	
	/**
	 * Returns an array containing hwids of all devices that have installed the specified APK
	 * @param unknown $db the database
	 * @param unknown $apkTableName the name of the apk table
	 * @param unknown $apkID the id of the apk
	 * @param unknown $logger the logger
	 * @return multitype:|mixed an array containing the hwids of the devices; if no devices are found an empty array is returned
	 */
	public static function getParticipatedDevices($db, $apkTableName, $apkID, $logger){
		$sql="SELECT installed_on FROM ".$apkTableName." WHERE apkid=".$apkID;
		$result = $db->query($sql);
		$row = $result->fetch(PDO::FETCH_ASSOC);
		if(empty($row)){
			$logger->logInfo("ApkManager::getParticipatedDevices() no participated devices found for apkID=".$apkID);
			return array();
		}
		else{
			$logger->logInfo("ApkManager::getParticipatedDevices() found participated devices for apkID=".$apkID);
			return json_decode($row['installed_on']);
		}
	}
    
    /**
    *  Updates user study, set the counter+1 that a user has sent his survey to server
    */
    public static function incrementSurveySendCounter($db, $apkTable, $apkID, $logger){
        $sql = "UPDATE ". $apkTable ." 
                SET survey_results_sent_count = survey_results_sent_count + 1 
                WHERE apkid = ". intval($apkID);
        $db->exec($sql);
    }

}
?>
