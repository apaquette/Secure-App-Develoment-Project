<?php
      include_once 'header.php';
      include_once '../src/Database.php';
      include_once 'includes/methods.inc.php';
      
      ValidSession();
      
      //not an admin, but is logged in
      if($_SESSION['u_admin'] == 0){
            header("Location: index.php");
      }
?>

<section class="main-container">
      <div class="main-wrapper">
            <h2>Login Events</h2>
            <div class="admin-entry-count">
                  <?php
                        $database = new Database();
                        $stmt = $database->ProcessQuery("SELECT count(event_id) AS num_rows FROM loginevents");
                        $total = $stmt->fetch()[0];
                  ?>
                  <p><i>Total entry count: <?php echo $total; ?></i></p>
            </div>
            <?php
                  $stmt = $database->ProcessQuery("SELECT * FROM loginevents");
                  
                  while ($loginevent = $stmt->fetch()) {
                        $id = $loginevent['event_id'];
                        $ipAddr = $loginevent['ip'];
                        $time = $loginevent['timeStamp'];
                        $user_id = $loginevent['user_id'];
                        $outcome = $loginevent['outcome'];

                        echo 
                        "<div class='admin-content'>
                              Entry ID: <b>$id</b>
                              <br>
                              <form class='admin-form' method='GET'>
                                    <label>IP Address: </label><input type='text' name='IP' value='$ipAddr' ><br>
                                    <label>Timestamp: </label><input type='text' name='timestamp' value='$time' ><br>
                                    <label>User ID: </label><input type='text' name='timestamp' value='$user_id' ><br>
                                    <label>Outcome: </label><input type='text' name='timestamp' value='$outcome' >
                              </form>
                              <br>
                        </div>";
                  }
            ?>
      </div>
</section>
<?php
      include_once 'footer.php';
?>
