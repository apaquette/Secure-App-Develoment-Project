<?php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);

    $ipAddr=$_SERVER['REMOTE_ADDR'];
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddr=$_SERVER['HTTP_CLIENT_IP'];
    } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    session_start();

    if (isset($_POST['submit'])) {
        include 'dbh.inc.php';
        include 'methods.inc.php';

        //Sanitize inputs
        $uid = CleanChars($_POST['uid']);
        $pwd = $_POST['pwd'];

        $database = new Database();

        //Does this client has previous failed login attempts?
        $checkClient = "SELECT `failedLoginCount`, `timeStamp` FROM `failedLogins` WHERE `ip` = ?";
        $stmt = $database->ProcessQuery($checkClient, [$ipAddr]);
        
        //New user, insert into database and login
        //"Initialise" attempts recording their IP, timestamp and setup a failed login count, based off IP and attempted uid
        if ($stmt->rowCount() == 0) {
            $time = date("Y-m-d H:i:s");
            $addUser = "INSERT INTO `failedLogins` (`ip`, `timeStamp`, `failedLoginCount`, `lockOutCount`) VALUES (?, ?, '0', '0')"; //'$ipAddr', '$time'
            $stmt = $database->ProcessQuery($addUser, [$ipAddr, $time]);
        }

        //Handle subsequent visits for each client
        $getCount = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
        $stmt = $database->ProcessQuery($getCount, [$ipAddr]);

        if ($stmt->fetch()[0] >= 5) {
            //Assuming theres 5 failed logins from this IP now check the timestamp to lock them out for 3 minutes
            $checkTime = "SELECT `timeStamp` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
            $stmt = $database->ProcessQuery($checkTime, [$ipAddr]);

            $failedLoginTime = ($stmt->fetch()[0]);
            
            $currTime = date("Y-m-d H:i:s");
            $timeDiff = abs(strtotime($currTime) - strtotime($failedLoginTime));
            $_SESSION['timeLeft'] = 180 - $timeDiff; //Print to inform user of how many seconds remain on the lockout

            if((int)$timeDiff <= 180) {
                $_SESSION['lockedOut'] = "Due to multiple failed logins you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

                //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
                $time = date("Y-m-d H:i:s");
                $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
                $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);
                //Redirect given lockout is currently enabled
                header("location: ../index.php");
                exit();
            }

            //Update lockOutCount
            $updateLockOutCount = "UPDATE `failedLogins` SET `lockOutCount` = `lockOutCount` + 1 WHERE `ip` = ?"; //$ipAddr
            $stmt = $database->ProcessQuery($updateLockOutCount, [$ipAddr]);

            //Otherwise update the lockout counter/timestamp
            $currTime = date("Y-m-d H:i:s");
            $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = '0', `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
            $stmt = $database->ProcessQuery($updateCount, [$currTime, $ipAddr]);
        }
        if(ProcessLogin($database,$uid,$pwd,$ipAddr)){
            header("Location: ../auth1.php");
        }
        header("Location: ../index.php");
    }

    function ProcessLogin($database,$uid, $pwd, $ipAddr) {
        // Errors handlers
        // Check if inputs are empty
        $conn = $database->GetConnection();
        if (empty($uid) || empty($pwd)) {
            //header("Location: ../index.php?login=empty");
            return FailedLogin($database,$uid,$ipAddr);
        }

        $stmt = $conn->prepare("SELECT * FROM sapusers WHERE user_uid = ?");
        $stmt->bindParam(1, $uid);
        //$stmt->bindParam(2, $pwd);

        if (!$stmt->execute() || $stmt->rowCount() < 1) {
            return FailedLogin($database,$uid,$ipAddr);
        }

        if ($row = $stmt->fetch()) {
            //Check password
            $pwd .= $row['user_salt'];
            $pwd = hash('sha256', $pwd);

            // $pwd inputted from user
            $hashedPwdCheck = $row['user_pwd'];

            if (strcmp($hashedPwdCheck, $pwd) !== 0){
                return FailedLogin($database,$uid,$ipAddr);
            }
            //Initiate session
            $_SESSION['u_id'] = $row['user_id'];
            $_SESSION['u_uid'] = $row['user_uid'];
            $_SESSION['u_admin'] = $row['user_admin']; //Will be 0 for non admin users
            
            //Store successful login attempt, uid, timestamp, IP in log format for viewing at admin.php
            $time = date("Y-m-d H:i:s");
            $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'success')"; 
            $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);

            session_regenerate_id();
            $_COOKIE['PHPSESSID'] = session_id();
            
            return true;
        }
        return false;
    } 

    function FailedLogin($database, $uid,$ipAddr) {
        //include "dbh.inc.php";
        //When login fails redirect to index and set the failedMsg variable so it can be displayed on index
        $_SESSION['failedMsg'] = "The username " . $uid . " and password could not be authenticated at this moment.";
        
        //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
        $time = date("Y-m-d H:i:s");
        $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
        $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);

        //Update failed login count for client
        $currTime = date("Y-m-d H:i:s");
        $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = `failedLoginCount` + 1, `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
        $stmt = $database->ProcessQuery($updateCount, [$currTime, $ipAddr]);
        return false;
    }
?>