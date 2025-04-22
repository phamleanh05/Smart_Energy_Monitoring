// PHP file to get LEDs state stored in the table/database.
<?php
  include 'database.php';
  
  //---------------------------------------- Condition to check that POST value is not empty.
  if (!empty($_POST)) {
    // keep track post values
    $device_name = $_POST['device_name'];
    
    $myObj = (object)array();
    
    //........................................ 
    $pdo = Database::connect();
    $sql = 'SELECT * FROM light_status WHERE id="' . $id . '"';
    foreach ($pdo->query($sql) as $row) {
      $myObj->device_name = $row['device_name'];
      $myObj->Light_1 = $row['Light_1'];
      
      $myJSON = json_encode($myObj);
      
      echo $myJSON;
    }
    Database::disconnect();
    //........................................ 
  }
  //---------------------------------------- 
?>