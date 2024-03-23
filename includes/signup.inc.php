<?php
    if (isset($_POST['submit'])) {
        session_start();
        include_once 'dbh.inc.php';

        $uid = cleanChars($_POST['uid']);
        $pwd = $_POST['pwd'];

        if(!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ipAddr=$_SERVER['HTTP_CLIENT_IP'];
        } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddr=$_SERVER['HTTP_X_FORWARDED_FOR'];
        }else {
            $ipAddr=$_SERVER['REMOTE_ADDR'];
        }

        //CHECK IF USER IS LOCKED OUT
        $checkClient = "SELECT `failedLoginCount` FROM `failedLogins` WHERE `ip` = ?";
        $stmt = ProcessQuery($checkClient, $conn, [$ipAddr]);
		
        if ($stmt->fetch()[0] == 5) {
            // Looks like a place to lockout after 5 attempts 
			// time is an issue here as it is not considered
        }
        
        // Check for empty fields
        if (empty($uid) || empty($pwd)) {
            $_SESSION['register'] = "Cannot submit empty username or password.";
            header("Location: ../index.php");
            exit();
        } 
        //Check to make sure only alphabetical characters are used for the username
        if (!preg_match("/^[a-zA-Z]*$/", $uid)) {
            $_SESSION['register'] = "Username must only contain alphabetic characters.";
            header("Location: ../index.php");
            exit();
        }

        $sql = "SELECT * FROM `sapusers` WHERE `user_uid` = ?";
        $stmt = ProcessQuery($sql, $conn, [$uid]);

        //If the user already exists, prevent them from signing up
        if ($stmt->rowCount() > 0) {
            $_SESSION['register'] = "Error.";
            header("Location: ../index.php");
            exit();
        }

        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $pwd . $salt;
        $hashedPWD = hash('sha256', $saltedPassword);

        $sql = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_salt`) VALUES (?, ?, ?)"; 
        $stmt = ProcessQuery($sql, $conn, [$uid, $hashedPWD, $salt]);
        
        $_SESSION['register'] = "You've successfully registered as " . $uid . ".";

        header("Location: ../index.php");
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