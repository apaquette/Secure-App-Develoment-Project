<?php
    $ipAddr=$_SERVER['REMOTE_ADDR'];
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ipAddr=$_SERVER['HTTP_CLIENT_IP'];
    } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipAddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    session_start();

    if (isset($_POST['submit'])) {
        include_once 'dbh.inc.php';
        include_once 'methods.inc.php';

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

        //CHECK IF USER IS LOCKED OUT
        $checkClient = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?";
        $stmt = $database->ProcessQuery($checkClient, [$ipAddr]);
		
        if ($stmt->fetch()[0] >= 5) {
            //Assuming theres 5 failed logins from this IP now check the timestamp to lock them out for 3 minutes
            $checkTime = "SELECT `timeStamp` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
            $stmt = $database->ProcessQuery($checkTime, [$ipAddr]);

            $failedLoginTime = ($stmt->fetch()[0]);
            
            $currTime = date("Y-m-d H:i:s");
            $timeDiff = abs(strtotime($currTime) - strtotime($failedLoginTime));
            $_SESSION['timeLeft'] = 180 - $timeDiff; //Print to inform user of how many seconds remain on the lockout

            if((int)$timeDiff <= 180) {
                $_SESSION['lockedOut'] = "Due to multiple failed registrations you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

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
        ProcessRegistration($database,$uid,$pwd,$ipAddr);
        header("Location: ../index.php");
    }

    function ProcessRegistration($database,$uid,$pwd,$ipAddr){
        // Check for empty fields
        if (empty($uid) || empty($pwd)) {
            $_SESSION['register'] = "Cannot submit empty username or password.";
            return FailedRegistration($database,$uid,$ipAddr);
        }
        
        //Check to make sure only alphabetical characters are used for the username
        if (!preg_match("/^[a-zA-Z]*$/", $uid)) {
            $_SESSION['register'] = "Username must only contain alphabetic characters.";
            return FailedRegistration($database,$uid,$ipAddr);
        }

        $stmt = $database->ProcessQuery("SELECT * FROM `sapusers` WHERE `user_uid` = ?", [$uid]);
        //If the user already exists, prevent them from signing up
        if ($stmt->rowCount() > 0) {
            $_SESSION['register'] = "Error.";
            return FailedRegistration($database,$uid,$ipAddr);
        }

        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $pwd . $salt;
        $hashedPWD = hash('sha256', $saltedPassword);

        $sql = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_salt`) VALUES (?, ?, ?)"; 
        $stmt = $database->ProcessQuery($sql, [$uid, $hashedPWD, $salt]);
        
        $_SESSION['register'] = "You've successfully registered as " . $uid . ".";

        
        return true;
    }

    function FailedRegistration($database,$uid,$ipAddr){
        //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
        $time = date("Y-m-d H:i:s");
        $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
        $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);

        //Update failed login count for client
        $currTime = date("Y-m-d H:i:s");
        $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = `failedLoginCount` + 1, `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
        $stmt = $database->ProcessQuery($updateCount, [$currTime, $ipAddr]);
        header("Location: ../index.php");
        return false;
    }
?>