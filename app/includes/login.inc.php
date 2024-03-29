<?php
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);

    session_start();

    if (isset($_POST['submit'])) {
        
        include '../../src/LoginManager.php';
        include 'methods.inc.php';
        
        $database = Database::getInstance();
        if(!$database->ConnectionExists()){
            $_SESSION['failedMsg'] = "Database does not exist";
            header("location: ../index.php");
            exit();
        }
        
        $loginManager = LoginManager::getInstance();

        $ipAddr = $loginManager->GetIpAddress();
        $uid = CleanChars($_POST['uid']); //Sanitize inputs
        $pwd = $_POST['pwd'];

        // If is locked out or fails login
        if($loginManager->IsLockedOut($ipAddr, $uid, "logins") || !$loginManager->ProcessLogin($uid,$pwd,$ipAddr)){
            header("location: ../index.php");
            exit();
        }

        header("Location: ../auth1.php");
    }
?>