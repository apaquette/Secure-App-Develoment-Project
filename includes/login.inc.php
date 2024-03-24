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

        InitFailedLogins($database,$checkClient,$ipAddr);

        // If is locked out or fails login
        if(IsLockedOut($database, $ipAddr, $uid) || !ProcessLogin($database,$uid,$pwd,$ipAddr)){
            header("location: ../index.php");
            exit();
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