<?php
    session_start();

    // if any of the parameters aren't set, destroy session and return to index
    if (!isset($_GET['reset'],$_SESSION['u_uid'], $_SESSION['csrf']) || $_GET['csrf'] != $_SESSION['csrf']) {
        $_SESSION['resetError'] = "Error code 1";
        session_destroy();
        exit();
    }

    include_once '../../src/LoginManager.php';
    $loginManager = LoginManager::getInstance();

    $oldpass = $_GET['old'];            // old password
    $newpass = $_GET['new'];            // new password
    $newConfirm = $_GET['new_confirm']; // new password confirm
    $uid = $_SESSION['u_uid'];          // session uid

    if($loginManager->ResetPassword($uid, $oldpass, $newpass, $newConfirm)){
        header("Location: ./logout.inc.php");
        exit();
    }

    header("Location: ../index.php");
?>