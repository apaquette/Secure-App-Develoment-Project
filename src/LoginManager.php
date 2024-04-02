<?php declare(strict_types=1);
    include_once 'Database.php';
    class LoginManager{
        private static $instance = null;
        private $UidRegex = "/^[a-zA-Z]*$/";
        private $PwdRegex = '/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?\/\\~-]).{8,}$/';

        private function __construct(){ }

        public static function getInstance(){
            if(self::$instance === null)
                self::$instance = new LoginManager();
            return self::$instance;
        }

        function GetIpAddress(){
            $ipAddr=$_SERVER['REMOTE_ADDR'];
            if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ipAddr=$_SERVER['HTTP_CLIENT_IP'];
            } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipAddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
            }


            if($ipAddr === null) $ipAddr = "";

            return $ipAddr;
        }

        //PROCESS LOGIN
        function ProcessLogin($uid,$pwd,$ipAddr) {
            // Errors handlers
            // Check if inputs are empty
            $database = Database::getInstance();
            $conn = $database->GetConnection();

            if($uid == null) $uid = "";

            if($pwd == null) $pwd = "";

            if (empty($uid) || empty($pwd))
                return $this->FailedLogin($uid,$ipAddr);
    
            $stmt = $conn->prepare("SELECT * FROM sapusers WHERE user_uid = ?");
            $stmt->bindParam(1, $uid);
    
            if (!$stmt->execute() || $stmt->rowCount() < 1)
                return $this->FailedLogin($uid,$ipAddr);
    
            $user = $stmt->fetch();
            $pwd .= $user['user_salt'];
            $pwd = hash('sha256', $pwd);
    
            // $pwd inputted from user
            $hashedPwdCheck = $user['user_pwd'];
    
            if (strcmp($hashedPwdCheck, $pwd) !== 0)
                return $this->FailedLogin($uid,$ipAddr);
            
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
        
        // PROCESS FAILED LOGIN
        function FailedLogin($uid,$ipAddr) {
            $this->InitFailedLogins($ipAddr);
            if($uid == null) $uid = "";
            
            $database = Database::getInstance();
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

        // RESET USER PASSWORD
        function ResetPassword($uid, $oldpass, $newpass, $newConfirm){
            // ERROR CHECKING
            $database = Database::getInstance();
            $stmt = $database->ProcessQuery("SELECT * FROM `sapusers` WHERE `user_uid` = ?", [$uid]);
            $user = $stmt->fetch();
            $oldpassSalted = $oldpass . $user['user_salt'];
            $oldpassHashed = hash('sha256', $oldpassSalted);
    
            $resetError = null;
            if (empty($oldpass || $newpass)) { // if old or new passwords are empty
                $resetError = "Error code 2";
            } else if($stmt->rowCount() <= 0){ // If the user doesn't exist
                $resetError = "Error code 6";
            } else if (strcmp($oldpassHashed, $user['user_pwd']) !== 0) { // if the old pass doesn't match database pass
                $resetError = "Error code 4";
            } else if ($newConfirm != $newpass) { // if the passwords don't match
                $resetError = "Error code 5";
            }else if(!preg_match($this->PwdRegex, $newpass)){
                $resetError = "Fails password requirements";
            }
    
            // if any errors occured, unset the token and return to the index
            if($resetError != null){
                $_SESSION['resetError'] = $resetError; // assign error
                unset($_SESSION['csrf']); // unset token
                return false;
            }
            // ERROR CHECKING END
    
    
            // CHANGE PASSWORD
            $salt = bin2hex(random_bytes(16));
            $saltedPassword = $newpass . $salt;
            $hashedPass = hash('sha256', $saltedPassword);
    
            unset($_SESSION['csrf']); //unset csrf
            $changePass = "UPDATE `sapusers` SET `user_pwd` = ?, `user_salt` = ? WHERE `user_uid` = ?"; //$newpass, $uid
            $stmt = $database->ProcessQuery($changePass, [$hashedPass, $salt, $uid]);
            
            return true;
        }

        // INITIALIZE FAILED LOGINS FOR IP IF IT ISN'T ALREADY
        function InitFailedLogins($ipAddr){
            $database = Database::getInstance();
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
        function IsLockedOut($ipAddr, $uid, $event){
            if($uid == null) $uid = "";
            
            $this->InitFailedLogins($ipAddr);
            $database = Database::getInstance();
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
                    $_SESSION['lockedOut'] = "Due to multiple failed ".$event." you're now locked out, please try again in 3 minutes"; //Should also stop user if they try to register

                    //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
                    $time = date("Y-m-d H:i:s");
                    $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
                    $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);
                    
                    //header("location: ../index.php"); //Redirect given lockout is currently enabled
                    return true;
                }

                //Update lockOutCount
                $updateLockOutCount = "UPDATE `failedLogins` SET `lockOutCount` = `lockOutCount` + 1 WHERE `ip` = ?"; //$ipAddr
                $stmt = $database->ProcessQuery($updateLockOutCount, [$ipAddr]);

                //Otherwise update the lockout counter/timestamp
                $currTime = date("Y-m-d H:i:s");
                $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = '0', `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
                $stmt = $database->ProcessQuery($updateCount, [$currTime, $ipAddr]);
            }
            
            return false;
        }

        // PROCESS REGISTRATION
        function ProcessRegistration($uid,$pwd,$ipAddr){
            if($uid == null) $uid = "";
            // Check for empty fields
            if (empty($uid) || empty($pwd)) {
                $_SESSION['register'] = "Cannot submit empty username or password.";
                return $this->FailedRegistration($uid,$ipAddr);
            }
            
            //Check to make sure only alphabetical characters are used for the username
            if (!preg_match($this->UidRegex, $uid)) {
                $_SESSION['register'] = "Username must only contain alphabetic characters.";
                return $this->FailedRegistration($uid,$ipAddr);
            }

            if(!preg_match($this->PwdRegex, $pwd)){
                $_SESSION['register'] = "Password must meet requierements.";
                return $this->FailedRegistration($uid,$ipAddr);
            }

            $database = Database::getInstance();
            $stmt = $database->ProcessQuery("SELECT * FROM `sapusers` WHERE `user_uid` = ?", [$uid]);
            //If the user already exists, prevent them from signing up
            if ($stmt->rowCount() > 0) {
                $_SESSION['register'] = "Error.";
                return $this->FailedRegistration($uid,$ipAddr);
            }
    
            $salt = bin2hex(random_bytes(16));
            $saltedPassword = $pwd . $salt;
            $hashedPWD = hash('sha256', $saltedPassword);
    
            $sql = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_salt`) VALUES (?, ?, ?)"; 
            $stmt = $database->ProcessQuery($sql, [$uid, $hashedPWD, $salt]);
            
            $_SESSION['register'] = "You've successfully registered as " . $uid . ".";

            return true;
        }

        // PROCESS FAILED REGISTRATION
        function FailedRegistration($uid,$ipAddr){
            if($uid == null) $uid = "";
            //Store unsuccessful login attempt, uid, timestamp, IP in log format for viewing at admin.php
            $database = Database::getInstance();
            $time = date("Y-m-d H:i:s");
            $recordLogin = "INSERT INTO `loginEvents` (`ip`, `timeStamp`, `user_id`, `outcome`) VALUES (?, ?, ?, 'fail')"; //$ipAddr, $time, $uid
            $stmt = $database->ProcessQuery($recordLogin, [$ipAddr, $time, $uid]);
    
            //Update failed login count for client
            $currTime = date("Y-m-d H:i:s");
            $updateCount = "UPDATE `failedLogins` SET `failedLoginCount` = `failedLoginCount` + 1, `timeStamp` = ? WHERE `ip` = ?"; //$currTime, $ipAddr
            $stmt = $database->ProcessQuery($updateCount, [$currTime, $ipAddr]);
            return false;
        }
    }
?>