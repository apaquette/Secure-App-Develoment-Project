<?php
// CHARACTER SANITIAZION
    function CleanChars($val){
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

    // SESSION VALIDATION
    function ValidSession(){
        if(!isset($_SESSION['u_id'], $_COOKIE["PHPSESSID"]) || $_COOKIE["PHPSESSID"] != session_id()){
            session_destroy();
            header("Location: index.php");
        }
    }

    // INITIALIZE FAILED LOGINS FOR IP IF IT WASN'T BEFORE
    function InitFailedLogins($database,$checkClient,$ipAddr){
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
    }

    // CHECK IF IP IS LOCKED OUT
    function IsLockedOut($database, $ipAddr, $uid){
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
                return true;
            }

            //Update lockOutCount
            $updateLockOutCount = "UPDATE `failedLogins` SET `lockOutCount` = `lockOutCount` + 1 WHERE `ip` = ?"; //$ipAddr
            $stmt = $database->ProcessQuery($updateLockOutCount, [$ipAddr]);

            //Otherwise update the lockout counter/timestamp
            $currTime = date("Y-m-d H:i:s");
            $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = '0', `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
            $stmt = $database->ProcessQuery($updateCount, [$currTime, $ipAddr]);
            return false;
        }
    }
    

?>