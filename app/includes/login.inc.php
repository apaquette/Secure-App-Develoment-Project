<?php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);

    session_start();

    if (isset($_POST['submit'])) {
        include '../../src/Database.php';
        include 'methods.inc.php';

        $ipAddr = GetIpAddress();
        $uid = CleanChars($_POST['uid']); //Sanitize inputs
        $pwd = $_POST['pwd'];
        $database = new Database();

        InitFailedLogins($database,$checkClient,$ipAddr);

        // If is locked out or fails login
        if(IsLockedOut($database, $ipAddr, $uid, "logins") || !ProcessLogin($database,$uid,$pwd,$ipAddr)){
            header("location: ../index.php");
            exit();
        }

        header("Location: ../auth1.php");
    }

    function ProcessLogin($database,$uid,$pwd,$ipAddr) {
        // Errors handlers
        // Check if inputs are empty
        $conn = $database->GetConnection();
        if (empty($uid) || empty($pwd)) {
            //header("Location: ../index.php?login=empty");
            return FailedLogin($database,$uid,$ipAddr);
        }

        $stmt = $conn->prepare("SELECT * FROM sapusers WHERE user_uid = ?");
        $stmt->bindParam(1, $uid);

        if (!$stmt->execute() || $stmt->rowCount() < 1) {
            return FailedLogin($database,$uid,$ipAddr);
        }

        $user = $stmt->fetch();
        $pwd .= $user['user_salt'];
        $pwd = hash('sha256', $pwd);

        // $pwd inputted from user
        $hashedPwdCheck = $user['user_pwd'];

        if (strcmp($hashedPwdCheck, $pwd) !== 0){
            return FailedLogin($database,$uid,$ipAddr);
        }
        //Initiate session
        $_SESSION['u_id'] = $user['user_id'];
        $_SESSION['u_uid'] = $user['user_uid'];
        $_SESSION['u_admin'] = $user['user_admin']; //Will be 0 for non admin users
        
        //Store successful login attempt, uid, timestamp, IP in log format for viewing at admin.php
        $time = date("Y-m-d H:i:s");
        $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'success')"; 
        $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);

        session_regenerate_id();
        $_COOKIE['PHPSESSID'] = session_id();
        
        return true;
    }

    function FailedLogin($database,$uid,$ipAddr) {
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