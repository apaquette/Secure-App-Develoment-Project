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
        }

        //CHECK IF USER IS LOCKED OUT
        $checkClient = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?";
        $stmt = ProcessQuery($checkClient, $conn, [$ipAddr]);
		
        if ($stmt->fetch()[0] >= 5) {
            //Assuming theres 5 failed logins from this IP now check the timestamp to lock them out for 3 minutes
            $checkTime = "SELECT `timeStamp` FROM `failedLogins` WHERE `ip` = ?"; //$ipAddr
            $stmt = ProcessQuery($checkTime, $conn, [$ipAddr]);

            $failedLoginTime = ($stmt->fetch()[0]);
            
            $currTime = date("Y-m-d H:i:s");
            $timeDiff = abs(strtotime($currTime) - strtotime($failedLoginTime));
            $_SESSION['timeLeft'] = 180 - $timeDiff; //Print to inform user of how many seconds remain on the lockout

            if((int)$timeDiff <= 180) {
                $_SESSION['lockedOut'] = "Due to multiple failed registrations you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

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
        processRegistration($conn,$uid,$pwd,$ipAddr);
    }

    function processRegistration($conn,$uid,$pwd,$ipAddr){
        // Check for empty fields
        if (empty($uid) || empty($pwd)) {
            $_SESSION['register'] = "Cannot submit empty username or password.";
            failedRegistration($conn,$uid,$ipAddr);
        }
        
        //Check to make sure only alphabetical characters are used for the username
        if (!preg_match("/^[a-zA-Z]*$/", $uid)) {
            $_SESSION['register'] = "Username must only contain alphabetic characters.";
            failedRegistration($conn,$uid,$ipAddr);
        }

        $stmt = ProcessQuery("SELECT * FROM `sapusers` WHERE `user_uid` = ?", $conn, [$uid]);
        //If the user already exists, prevent them from signing up
        if ($stmt->rowCount() > 0) {
            $_SESSION['register'] = "Error.";
            failedRegistration($conn,$uid,$ipAddr);
        }

        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $pwd . $salt;
        $hashedPWD = hash('sha256', $saltedPassword);

        $sql = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_salt`) VALUES (?, ?, ?)"; 
        $stmt = ProcessQuery($sql, $conn, [$uid, $hashedPWD, $salt]);
        
        $_SESSION['register'] = "You've successfully registered as " . $uid . ".";

        header("Location: ../index.php");
    }

    function failedRegistration($conn,$uid,$ipAddr){
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
?>