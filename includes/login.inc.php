<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $ipAddr=$_SERVER['HTTP_CLIENT_IP'];
} elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ipAddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
} else {
    $ipAddr=$_SERVER['REMOTE_ADDR'];
}

session_start();

if (isset($_POST['submit'])) {
    include 'dbh.inc.php';

    //Sanitize inputs
    $uid = cleanChars($_POST['uid']);
    $pwd = $_POST['pwd'];

    //Does this client has previous failed login attempts?
    $checkClient = "SELECT `failedLoginCount`, `timeStamp` FROM `failedLogins` WHERE `ip` = ?";
    $stmt = ProcessQuery($checkClient, $conn, [$ipAddr]);
    
    //New user, insert into database and login
    //"Initialise" attempts recording their IP, timestamp and setup a failed login count, based off IP and attempted uid
    if ($stmt->rowCount() == 0) {
        $time = date("Y-m-d H:i:s");
        $addUser = "INSERT INTO `failedLogins` (`ip`, `timeStamp`, `failedLoginCount`, `lockOutCount`) VALUES (?, ?, '0', '0')"; //'$ipAddr', '$time'
        $stmt = ProcessQuery($addUser, $conn, [$ipAddr, $time]);
        processLogin($conn,$uid,$pwd,$ipAddr);
    }

    //Handle subsequent visits for each client
    $getCount = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
    $stmt = ProcessQuery($getCount, $conn, [$ipAddr]);

    if ($stmt->fetch()[0] >= 5) {
        //Assuming theres 5 failed logins from this IP now check the timestamp to lock them out for 3 minutes
        $checkTime = "SELECT `timeStamp` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
        $stmt = ProcessQuery($checkTime, $conn, [$ipAddr]);

        $failedLoginTime = ($stmt->fetch()[0]);
        
        $currTime = date("Y-m-d H:i:s");
        $timeDiff = abs(strtotime($currTime) - strtotime($failedLoginTime));
        $_SESSION['timeLeft'] = 180 - $timeDiff; //Print to inform user of how many seconds remain on the lockout

        if((int)$timeDiff <= 180) {
            $_SESSION['lockedOut'] = "Due to multiple failed logins you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

            //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
            $time = date("Y-m-d H:i:s");
            $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
            $stmt = ProcessQuery($recordLogin, $conn, [$ipAddr, $time, $uid]);
            //Redirect given lockout is currently enabled
            header("location: ../index.php");
            exit();
        }

        //Update lockOutCount
        $updateLockOutCount = "UPDATE `failedLogins` SET `lockOutCount` = `lockOutCount` + 1 WHERE `ip` = ?"; //$ipAddr
        $stmt = ProcessQuery($updateLockOutCount, $conn, [$ipAddr]);

        //Otherwise update the lockout counter/timestamp
        $currTime = date("Y-m-d H:i:s");
        $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = '0', `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
        $stmt = ProcessQuery($updateCount, $conn, [$currTime, $ipAddr]);
    }
    processLogin($conn,$uid,$pwd,$ipAddr);
}

function processLogin($conn, $uid, $pwd, $ipAddr) {
    // Errors handlers
    // Check if inputs are empty
    if (empty($uid) || empty($pwd)) {
        header("Location: ../index.php?login=empty");
        failedLogin($conn,$uid,$ipAddr);
    }

    $stmt = $conn->prepare("SELECT * FROM sapusers WHERE user_uid = ?");
    $stmt->bindParam(1, $uid);
    //$stmt->bindParam(2, $pwd);

    if (!$stmt->execute() || $stmt->rowCount() < 1) {
        failedLogin($conn,$uid,$ipAddr);
    }

    if ($row = $stmt->fetch()) {
        //Check password
        $pwd .= $row['user_salt'];
        $pwd = hash('sha256', $pwd);

        // $pwd inputted from user
        $hashedPwdCheck = $row['user_pwd'];

        if (strcmp($hashedPwdCheck, $pwd) !== 0){
            failedLogin($conn,$uid,$ipAddr);
        }
        //Initiate session
        $_SESSION['u_id'] = $row['user_id'];
        $_SESSION['u_uid'] = $row['user_uid'];
        $_SESSION['u_admin'] = $row['user_admin']; //Will be 0 for non admin users
        
        //Store successful login attempt, uid, timestamp, IP in log format for viewing at admin.php
        $time = date("Y-m-d H:i:s");
        $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'success')"; 
        $stmt = ProcessQuery($recordLogin, $conn, [$ipAddr, $time, $uid]);

        session_regenerate_id();
        $_COOKIE['PHPSESSID'] = session_id();
       header("Location: ../auth1.php");
    }
    exit();
} 

function failedLogin ($conn, $uid,$ipAddr) {
    //include "dbh.inc.php";
    //When login fails redirect to index and set the failedMsg variable so it can be displayed on index
    $_SESSION['failedMsg'] = "The username " . $uid . " and password could not be authenticated at this moment.";
    
    //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
    $time = date("Y-m-d H:i:s");
    $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
    $stmt = ProcessQuery($recordLogin, $conn, [$ipAddr, $time, $uid]);

    //Update failed login count for client
    $currTime = date("Y-m-d H:i:s");
    $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = `failedLoginCount` + 1, `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
    $stmt = ProcessQuery($updateCount, $conn, [$currTime, $ipAddr]);
    header("Location: ../index.php");
    exit();
}

function cleanChars($val){
    $sanitized = '';
    foreach (str_split($val) as $char) {
		switch($char){
			case '&':
				$sanitized .= "&amp;";
				break;
			case '<':
				$sanitized .= "&lt;";
				break;
			case '>':
				$sanitized .= "&gt;";
				break;
			case '"':
				$sanitized .= "&quot;";
				break;
			case '\'':
				$sanitized .= "&#x27;";
				break;
			case '/':
				$sanitized .= "&#x2F;";
				break;
			case '(':
				$sanitized .= "&#x00028;";
				break;
			case ')':
				$sanitized .= "&#x00029;";
				break;
			case '{':
				$sanitized .= "&lcub;";
				break;
			case '}':
				$sanitized .= "&rcub;";
				break;
			default:
				$sanitized .= $char;
				break;
		}
	}
    //return htmlspecialchars($val);
	return $sanitized;
}