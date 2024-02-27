<?php


if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ipAddr=$_SERVER['HTTP_CLIENT_IP'];
} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipAddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
}
  else {
    $ipAddr=$_SERVER['REMOTE_ADDR'];
}

session_start();



if (isset($_POST['submit'])) {

    include 'dbh.inc.php';

    //Sanitize inputs
    $uid = $_POST['uid'];
    $pwd = $_POST['pwd'];
    $ipAddr = $ipAddr;

    //Does this client has previous failed login attempts?
    $checkClient = "SELECT `failedLoginCount`, `timeStamp` FROM `failedLogins` WHERE `ip` = ?";
    $stmt = $conn->prepare($checkClient);
    $stmt->bind_param("s", $ipAddr);
    $stmt->execute();
    $result = $stmt->get_result(); 
    $time = date("Y-m-d H:i:s");

    //New user, insert into database and login
    //"Initialise" attempts recording their IP, timestamp and setup a failed login count, based off IP and attempted uid
    if ($result->num_rows == 0) {

        $addUser = "INSERT INTO `failedLogins` (`ip`, `timeStamp`, `failedLoginCount`, `lockOutCount`) VALUES (?, ?, '0', '0')"; //'$ipAddr', '$time'
        $stmt = $conn->prepare($addUser);
        $stmt->bind_param("ss", $ipAddr, $time);

        if(!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }

        processLogin($conn,$uid,$pwd,$ipAddr);
        
        //Handle subsequent visits for each client
    } else {
        $getCount = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
        $stmt = $conn->prepare($getCount);
        $stmt->bind_param("s", $ipAddr);
        $stmt->execute();
        $result = $stmt->get_result();

            if (!$result) {
                die("Error: " . $stmt->error);
            } else { 
                //Assign count in variable so we can compare it for each failed login
                $failedLoginCount = ($result->fetch_row()[0]);

                if ($failedLoginCount >= 5) {
                    //Assuming theres 5 failed logins from this IP now check the timestamp to lock them out for 3 minutes
                    $checkTime = "SELECT `timeStamp` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
                    $stmt = $conn->prepare($checkTime);
                    $stmt->bind_param("s", $ipAddr);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    if(!$result) {
                        die('Error: ' . $stmt->error);
                    } else {
                        $failedLoginTime = ($result->fetch_row()[0]);
                    }

                    $currTime = date("Y-m-d H:i:s");
                    $timeDiff = abs(strtotime($currTime) - strtotime($failedLoginTime));
                    $_SESSION['timeLeft'] = 180 - $timeDiff; //Print to inform user of how many seconds remain on the lockout

                    if((int)$timeDiff <= 180) {
                        $_SESSION['lockedOut'] = "Due to multiple failed logins you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

                        //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
                        $time = date("Y-m-d H:i:s");
                        $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
                        $stmt = $conn->prepare($recordLogin);
                        $stmt->bind_param("sss", $ipAddr, $time, cleanChars($uid));
                        $stmt->execute();

                        if(!$stmt->execute()) {
                            die("Errory: " . $stmt->error);
                        }
                        //Redirect given lockout is currently enabled
                        header("location: ../index.php");
                        
                    } else {

                        //Update lockOutCount
                        $updateLockOutCount = "UPDATE `failedLogins` SET `lockOutCount` = `lockOutCount` + 1 WHERE `ip` = ?"; //$ipAddr
                        $stmt = $conn->prepare($updateLockOutCount);
                        $stmt->bind_param("s", $ipAddr);

                        if(!$stmt->execute()) {
                            die("Errorz: " . $stmt->error);
                        } else {

                            //Otherwise update the lockout counter/timestamp
                            $currTime = date("Y-m-d H:i:s");
                            $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = '0', `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
                            $stmt = $conn->prepare($updateCount);
                            $stmt->bind_param("ss", $currTime, $ipAddr);

                            if(!$stmt->execute()) {
                                die("Error: " . $stmt->error);
                            }
                            
                            processLogin($conn,$uid,$pwd,$ipAddr); 
                        }
                    }
                    
                } else {
                    processLogin($conn,$uid,$pwd,$ipAddr);
                }
            }
    }
}

function processLogin($conn, $uid, $pwd, $ipAddr) {
    // Errors handlers
    // Check if inputs are empty
    if (empty($uid) || empty($pwd)) {

        header("Location: ../index.php?login=empty");
        failedLogin($uid,$ipAddr);
        exit();

    } else {

		try{
		$sql = "SELECT * FROM sapusers WHERE user_uid = '" .$uid. "' and user_pwd ='" .$pwd. "'";
		$result = $conn->query($sql);
		
		}catch (Exception $e) {
			echo 'Caught exception: ',  $e->getMessage(), "\n";
			failedLogin($e->getMessage(),$ipAddr);
		}
		
        if ($result->num_rows < 1) {
            
            //failedLogin($sql,$ipAddr);
			failedLogin($uid,$ipAddr);

        } else {

            if ($row = mysqli_fetch_assoc($result)) {
                //Check password
				
				// $pwd inputted from user
                $hashedPwdCheck = $row['user_pwd'];

                if (strcmp($hashedPwdCheck, $pwd) !== 0){

                    failedLogin($uid,$ipAddr);

                } else{
                    //Initiate session
                    $_SESSION['u_id'] = $row['user_id'];
                    $_SESSION['u_uid'] = $row['user_uid'];
                    $_SESSION['u_admin'] = $row['user_admin']; //Will be 0 for non admin users
                    
                    //Store successful login attempt, uid, timestamp, IP in log format for viewing at admin.php
                    $time = date("Y-m-d H:i:s");
                    $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'success')"; 
                    $stmt = $conn->prepare($recordLogin);
                    $stmt->bind_param("sss", $ipAddr, $time, cleanChars($uid));

                    if(!$stmt->execute()) {
                        die("Errorx: " . $stmt->error);
                    } else {
                        header("Location: ../auth1.php");
                        exit();
                    }
                }
            }
        }
    }
} 

function failedLogin ($uid,$ipAddr) {
    include "dbh.inc.php";
    //When login fails redirect to index and set the failedMsg variable so it can be displayed on index
    $_SESSION['failedMsg'] = "The username " . cleanChars($uid) . " and password could not be authenticated at this moment.";
    
    //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
    $time = date("Y-m-d H:i:s");
    $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
    $stmt = $conn->prepare($recordLogin);
    $stmt->bind_param("sss", $ipAddr, $time, cleanChars($uid));

    if(!$stmt->execute()) {
        die("Error 1: " . $stmt->error);
    } else {
        //Update failed login count for client
        $currTime = date("Y-m-d H:i:s");
        $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = `failedLoginCount` + 1, `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
        $stmt = $conn->prepare($updateCount);
        $stmt->bind_param("ss", $currTime, $ipAddr);

        if(!$stmt->execute()) {
            die("Error 2: " . $stmt->error);
        } else {
            header("Location: ../index.php");
            exit();
        }
    }
    
}

function cleanChars($val)
{
return $val;
}