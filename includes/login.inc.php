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


//new for sessioin management
$_SESSION['id'] = 'auth';

if (isset($_POST['submit'])) {

    include 'dbh.inc.php';

    //Sanitize inputs
    $uid = cleanChars($_POST['uid']);
    $pwd = cleanChars($_POST['pwd']);
    $ipAddr = $ipAddr;

    //Does this client has previous failed login attempts?
    $checkClient = "SELECT `failedLoginCount`, `timeStamp` FROM `failedLogins` WHERE `ip` = ?";
    $stmt = $conn->prepare($checkClient);
    $stmt->bindParam(1, $ipAddr);
    $result = $stmt->execute();
    $time = date("Y-m-d H:i:s");

    //New user, insert into database and login
    //"Initialise" attempts recording their IP, timestamp and setup a failed login count, based off IP and attempted uid
    if ($stmt->rowCount() == 0) {
        $addUser = "INSERT INTO `failedLogins` (`ip`, `timeStamp`, `failedLoginCount`, `lockOutCount`) VALUES (?, ?, '0', '0')"; //'$ipAddr', '$time'
        $stmt = $conn->prepare($addUser);
        $stmt->bindParam(1, $ipAddr);
        $stmt->bindParam(2, $time);

        if(!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }

        processLogin($conn,$uid,$pwd,$ipAddr);
    }

    //Handle subsequent visits for each client
    $getCount = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
    $stmt = $conn->prepare($getCount);
    $stmt->bindParam(1, $ipAddr);

    if (!$stmt->execute()) {
        die("Error: " . $stmt->error);
    } 
    //Assign count in variable so we can compare it for each failed login
    $failedLoginCount = $stmt->fetch()[0];

    if ($failedLoginCount >= 5) {
        //Assuming theres 5 failed logins from this IP now check the timestamp to lock them out for 3 minutes
        $checkTime = "SELECT `timeStamp` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
        $stmt = $conn->prepare($checkTime);
        $stmt->bindParam(1, $ipAddr);

        if(!$stmt->execute()) {
            die('Error: ' . $stmt->error);
        }
        $failedLoginTime = ($stmt->fetch()[0]);
        
        $currTime = date("Y-m-d H:i:s");
        $timeDiff = abs(strtotime($currTime) - strtotime($failedLoginTime));
        $_SESSION['timeLeft'] = 180 - $timeDiff; //Print to inform user of how many seconds remain on the lockout

        if((int)$timeDiff <= 180) {
            $_SESSION['lockedOut'] = "Due to multiple failed logins you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

            //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
            $time = date("Y-m-d H:i:s");
            $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
            $stmt = $conn->prepare($recordLogin);
            $stmt->bindParam(1, $ipAddr);
            $stmt->bindParam(2, $time);
            $stmt->bindParam(3, cleanChars($uid));

            if(!$stmt->execute()) {
                die("Errory: " . $stmt->error);
            }
            //Redirect given lockout is currently enabled
            header("location: ../index.php");
            exit();
        }

        //Update lockOutCount
        $updateLockOutCount = "UPDATE `failedLogins` SET `lockOutCount` = `lockOutCount` + 1 WHERE `ip` = ?"; //$ipAddr
        $stmt = $conn->prepare($updateLockOutCount);
        $stmt->bindParam(1, $ipAddr);

        if(!$stmt->execute()) {
            die("Error: " . $stmt->error);
        } else {
            //Otherwise update the lockout counter/timestamp
            $currTime = date("Y-m-d H:i:s");
            $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = '0', `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
            $stmt = $conn->prepare($updateCount);
            $stmt->bindParam(1, $currTime);
            $stmt->bindParam(2, $ipAddr);

            if(!$stmt->execute()) {
                die("Error: " . $stmt->error);
            }
        }
    }
    processLogin($conn,$uid,$pwd,$ipAddr);
}

function processLogin($conn, $uid, $pwd, $ipAddr) {
    // Errors handlers
    // Check if inputs are empty
    if (empty($uid) || empty($pwd)) {
        header("Location: ../index.php?login=empty");
        failedLogin($uid,$ipAddr);
    }

    $stmt = $conn->prepare("SELECT * FROM sapusers WHERE user_uid = ? and user_pwd = ?");
    $stmt->bindParam(1, $uid);
    $stmt->bindParam(2, $pwd);

    if (!$stmt->execute() || $stmt->rowCount() < 1) {
        failedLogin($uid,$ipAddr);
    }

    if ($row = $stmt->fetch()) {
        //Check password
        
        // $pwd inputted from user
        $hashedPwdCheck = $row['user_pwd'];

        if (strcmp($hashedPwdCheck, $pwd) !== 0){
            failedLogin($uid,$ipAddr);
        }
        //Initiate session
        $_SESSION['u_id'] = $row['user_id'];
        $_SESSION['u_uid'] = $row['user_uid'];
        $_SESSION['u_admin'] = $row['user_admin']; //Will be 0 for non admin users
        
        //Store successful login attempt, uid, timestamp, IP in log format for viewing at admin.php
        $time = date("Y-m-d H:i:s");
        $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'success')"; 
        $stmt = $conn->prepare($recordLogin);
        $stmt->bindParam(1, $ipAddr);
        $stmt->bindParam(2, $time);
        $stmt->bindParam(3, cleanChars($uid));

        if(!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }

        //new for session management
        // session_regenerate_id();
        // $_SESSION['Test'] = "Test Value";
        // setcookie($_SESSION['id'], session_id());
        // setcookie("test", "testCookie");
        
        setcookie("TestCookie", 'something from somewhere');

        header("Location: ../auth1.php");
    }
    exit();
} 

function failedLogin ($uid,$ipAddr) {
    include "dbh.inc.php";
    //When login fails redirect to index and set the failedMsg variable so it can be displayed on index
    $_SESSION['failedMsg'] = "The username " . cleanChars($uid) . " and password could not be authenticated at this moment.";
    
    //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
    $time = date("Y-m-d H:i:s");
    $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
    $stmt = $conn->prepare($recordLogin);
    $stmt->bindParam(1, $ipAddr);
    $stmt->bindParam(2, $time);
    $stmt->bindParam(3, cleanChars($uid));

    if(!$stmt->execute()) {
        die("Error 1: " . $stmt->error);
    } 
    //Update failed login count for client
    $currTime = date("Y-m-d H:i:s");
    $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = `failedLoginCount` + 1, `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
    $stmt = $conn->prepare($updateCount);
    $stmt->bindParam(1, $currTime);
    $stmt->bindParam(2, $ipAddr);

    if(!$stmt->execute()) {
        die("Error 2: " . $stmt->error);
    }
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