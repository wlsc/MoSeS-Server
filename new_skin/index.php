<?php
session_start();
  
include_once("./include/_header.php");

if(isset($_POST["submit"]) && $_POST["submit"] == "1"){
  echo "<meta http-equiv='refresh' content='0;URL=./'>"; 
}
  
?>
  
<title>Hauptseite von MoSeS - Home</title>

<?php  
include_once("./include/_menu.php");

?>
  
<div id="header">
    <div id="logo">
        <h1><a href="./index.php">Mobile Sensing System</a></h1>
    </div>
</div>
<!-- <div id="splash">&nbsp;</div> -->
<!-- end #header -->
  
<?php

if(isset($_POST["submit"]) && $_POST["submit"] == "1"){
        
  include_once("./include/functions/dbconnect.php");   
  
  $USER_LOGIN =  $_POST["login"];
  $USER_PASSWORD =  $_POST["password"];
  
  $result = $db->query("SELECT * FROM user WHERE login = '". $USER_LOGIN ."' AND password = '". $USER_PASSWORD ."'");
  
  $row = $result->fetch();    
  
  if(!empty($row)){
            
      if($row["confirmed"] == 1){
      
          $USER_CONFIRMED = 1;
          $_SESSION["USER_LOGGED_IN"] = 1;    
          
          $_SESSION["USER_ID"] =   $row["userid"];
          $_SESSION["GROUP_ID"] =  $row["usergroupid"];
          $_SESSION["LOGIN"] =     $row["login"];    
          $_SESSION["PASSWORD"] =  $row["password"];
          $_SESSION["FIRSTNAME"] = $row["firstname"];
          $_SESSION["LASTNAME"] =  $row["lastname"];
          
          // we have an admin here logged in
          if($row["usergroupid"] == 3){
              $_SESSION["ADMIN_ACCOUNT"] =  "YES";    
          }
          
      }else{
          $USER_CONFIRMED = 0;
      }
  }
}
?>  

<div id="page">
        <div id="page-bgtop">
            <div id="page-bgbtm">
                <div id="page_content">
                    <div class="post">
                        <h2 class="title">Welcome Friend!</h2>
                        <div class="entry">
                            <p><img src="images/moses_logo.jpg" width="143" height="143" alt="" class="alignleft border" />This is der Moses. You must love him.</p>
                            <p>This site is under construction!</p>
                        </div>
                        <div style="clear: both;">&nbsp;</div>
                        <p class="meta">Posted by Admin on February 12, 2012</p>
                    </div>
                    <div style="clear: both;">&nbsp;</div>
                </div>
                <!-- end #content -->
                <div style="clear: both;">&nbsp;</div>
            </div>
        </div>
    </div>
    <!-- end #page -->
</div>

<?php
include_once("./include/_login_slider.php");

include_once("./include/_footer.php");  
?>