<?php
    session_start();

    if (isset($_POST['submit'])) {
        include_once '../../src/LoginManager.php';
        include_once 'methods.inc.php';

        $loginManager = LoginManager::getInstance();
        $ipAddr = $loginManager->GetIpAddress();
        $uid = CleanChars($_POST['uid']);
        $pwd = $_POST['pwd'];

        if(!$loginManager->IsLockedOut($ipAddr, $uid, "registrations")){
            $loginManager->ProcessRegistration($uid,$pwd,$ipAddr);
        }

        header("Location: ../index.php");
    }
?>