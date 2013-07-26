<?php
    /*
   * Prints survey's questions as JSON string
   * Select all surveys from DB
   */
   
   include_once("./config.php");
   include_once("./include/functions/dbconnect.php");
   
   $sql = 'SELECT * 
           FROM `'. $CONFIG['DB_TABLE']['QUESTION'] .'` 
           WHERE questid = '. $_POST['get_questions'];
            
   $result=$db->query($sql);
   $QUESTIONS = $result->fetchAll(PDO::FETCH_ASSOC);
   
   $RESULT = array();
   foreach($QUESTIONS as $Q){
        $RESULT[] = $Q['content']; 
   }
   
   die(json_encode($RESULT));
?>