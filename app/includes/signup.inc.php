<?php
    session_start();

    if (isset($_POST['submit'])) {
        include_once '../../src/Database.php';
        include_once 'methods.inc.php';

        $ipAddr = GetIpAddress();
        $uid = CleanChars($_POST['uid']);
        $pwd = $_POST['pwd'];
        $database = new Database();

        InitFailedLogins($database,$checkClient,$ipAddr);
        if(!IsLockedOut($database, $ipAddr, $uid, "registrations"))
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