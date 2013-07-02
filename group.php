<?php
//Starting the session
session_start();
ob_start();

if(!isset($_SESSION['USER_LOGGED_IN']))
    header("Location: " . dirname($_SERVER['PHP_SELF'])."/");   
    
include_once("./config.php");
include_once("./include/functions/func.php");
include_once("./include/functions/dbconnect.php");

$sql = 'SELECT u.rgroup, rg.members 
        FROM '. $CONFIG['DB_TABLE']['USER'] .' u 
        LEFT JOIN '. $CONFIG['DB_TABLE']['RGROUP'] .' rg 
        ON u.rgroup=rg.name 
        WHERE u.userid='. $_SESSION['USER_ID'];

$result = $db->query($sql);
$row = $result->fetch(PDO::FETCH_ASSOC);

$group_members_array_ids = json_decode($row['members']);
$group_members_count = count($group_members_array_ids);
$groupname = $row['rgroup'];
$GROUP_MEMBERS = array();
$group_device_count = 0;
$group_has_private_apks = '';
$GROUP_UNIQUE_DEVICES = array();

if(!empty($group_members_array_ids)){
 foreach($group_members_array_ids as $member_id){
             
     $sql = 'SELECT * 
             FROM '. $CONFIG['DB_TABLE']['USER'] .' u 
             WHERE u.userid='. $member_id;
             
     $result = $db->query($sql);
     $user_info = $result->fetch(PDO::FETCH_ASSOC);
     
     if(!empty($user_info)){
         $user_info['NUM_OF_DEVICES'] = 0;
         $GROUP_MEMBERS[] = $user_info;
     }
     
     /*
     * Requesting user's devices
     */
     
     $sql = 'SELECT h.hwid, h.uid, h.uniqueid, h.modelname, h.androidversion, h.c2dm  
             FROM '. $CONFIG['DB_TABLE']['HARDWARE'] .' h
             WHERE h.uid='. $member_id;
             
     $result = $db->query($sql);
     $user_devices = $result->fetchAll(PDO::FETCH_ASSOC);
     
     if(!empty($user_devices)){
         $group_device_count += count($user_devices);
         $user_info['NUM_OF_DEVICES'] = $group_device_count;
         $GROUP_MEMBERS[count($GROUP_MEMBERS)-1] = $user_info;
         
         $tmp_unique_devices = array();
         foreach($user_devices as $device){
              if(!in_array($device['uniqueid'], $tmp_unique_devices)){
                  $tmp_unique_devices[] = $device['uniqueid'];
                  $GROUP_UNIQUE_DEVICES[] = $device;
              }
         } 
     }
     
     $apk_sql = "SELECT apktitle 
                 FROM ".$CONFIG['DB_TABLE']['APK']. " 
                 WHERE locked=1 AND userid=" . $member_id;
                 
     $req_apk = $db->query($apk_sql);
     $apk_rows = $req_apk->fetchAll();
     
     if(!empty($apk_rows)){
         $i=0;
         foreach($apk_rows as $apk){
            $group_has_private_apks .= $i > 0 ? ", " : "";
            $group_has_private_apks .= $apk['apktitle'];
            $i++;
        }
     }
 }
}

//Import of the header  
include_once("./include/_header.php");                   
?>
  
<title>Hauptseite von MoSeS - Devices</title>

<?php  //Import of the menu
include_once("./include/_menu.php");
?>

    <!-- Main Block -->
    <div class="hero-unit">
        <h2>You're member of group: <?php echo $groupname; ?></h2>
        <button class="btn btn-info" id="btnLeaveGroup" value="<?php echo $_SESSION['USER_ID']; ?>">Leave group</button>
        <h4>This group has <?php 
                echo $group_members_count > 1 ? $group_members_count.' members' : '1 member (you)' 
            ?> with <?php 
                    echo $group_device_count > 1 ? $group_device_count.' devices' : $group_device_count.' device'; 
                ?></h4>
            <div class="accordion" id="accordionFather">
            <?php
               for($i=0; $i<count($GROUP_MEMBERS); $i++){
                   $MEMBER = $GROUP_MEMBERS[$i];
            ?>
              <div class="accordion-group">
                <div class="accordion-heading">
                  <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordionFather" href="#collapseMembers<?php echo $i; ?>">
                    <?php
                       echo $MEMBER['firstname']." ".$MEMBER['lastname'].($MEMBER['userid'] == $_SESSION['USER_ID'] ? ' (you)' : ''); 
                    ?>
                  </a>
                </div>
                <div id="collapseMembers<?php 
                    echo $i;
                    
                    // for selected collapse use "collapse in" for class 
                    ?>" class="accordion-body collapse">
                  <div class="accordion-inner">
                  <?php
                        switch($MEMBER['usergroupid']){
                            case 0: echo 'Access level: Not Confirmed User <br>';
                                    break;
                            
                            case 1: echo 'Access level: User <br>';
                                    break;
                                    
                            case 2: echo 'Access level: Scientist <br>';
                                    break;
                            
                            case 3: echo 'Access level: Administrator <br>';
                                    break;
                            
                            default: echo "Usergroupid broken!";
                        }
                        echo 'E-mail: '.$MEMBER['email'].' <br>';
                        echo 'Number of devices: '.$MEMBER['NUM_OF_DEVICES'];
                    ?>  
                  </div>
                </div>
              </div>
              <?php
                   }
               ?>
            </div>
        <h5>This group has private apps: <?php echo $group_has_private_apks; ?></h5>
        <br>
        <h4>This group has <?php echo count($GROUP_UNIQUE_DEVICES); ?> unique device<?php echo (count($GROUP_UNIQUE_DEVICES) > 1 ? 's' : ''); ?>!</h4>
        <div class="accordion" id="accordionFather2">
            <?php
               for($i=0; $i<count($GROUP_UNIQUE_DEVICES); $i++){
                   $DEVICE = $GROUP_UNIQUE_DEVICES[$i];
            ?>
              <div class="accordion-group">
                <div class="accordion-heading">
                  <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordionFather2" href="#collapseDevices<?php echo $i; ?>">
                    <?php
                       echo $DEVICE['modelname'].($DEVICE['uid'] == $_SESSION['USER_ID'] ? ' (yours)' : ''); 
                    ?>
                  </a>
                </div>
                <div id="collapseDevices<?php 
                    echo $i;
                    
                    // for selected collapse use "collapse in" for class 
                    ?>" class="accordion-body collapse">
                  <div class="accordion-inner">
                  <?php
                        echo 'Android version: '. getAPILevel($DEVICE['androidversion']) .' <br>';
                        echo 'Model name: '.$DEVICE['modelname'].' <br>';
                        echo 'C2DM is '.(!empty($DEVICE['c2dm']) ? 'active' : 'not active');
                    ?>  
                  </div>
                </div>
              </div>
              <?php
                   }
               ?>
            </div> 
    </div>
    <!-- / Main Block -->
    
    <hr>

 <?php

//Import of the slider
include_once("./include/_login.php");
//Import of the footer
include_once("./include/_footer.php");

?>
<script type="text/javascript">

$('#btnLeaveGroup').click(function(){
    $.ajax({
        dataType: "POST",
        url: 'content_provider.php',
        data: {'leaveGroup': $('#btnLeaveGroup').val()},
        success: function(result){
            if(result == '0'){
                alert('Wow! alles ok!');
            }
        }
       });
});

</script>