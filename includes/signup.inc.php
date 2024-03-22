<?php
    if (isset($_POST['submit'])) {
        session_start();
        include_once 'dbh.inc.php';

        $uid = $_POST['uid'];
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
        $stmt = $conn->prepare($checkClient);
        $stmt->bindParam(1, $ipAddr);
        $stmt->execute();
		
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
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $uid);
        $stmt->execute();

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
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(1, $uid);
        $stmt->bindParam(2, $hashedPWD);
        $stmt->bindParam(3, $salt);
        
        if(!$stmt->execute()) {
            echo "Error: " . $stmt->error;
        }

        $_SESSION['register'] = "You've successfully registered as " . $uid . ".";

        header("Location: ../index.php");
    }

?>