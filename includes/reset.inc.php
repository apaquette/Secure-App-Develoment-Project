<?php

    //If user is not logged in or requesting to reset, redirect
    include 'dbh.inc.php';
    session_start();

    // if any of the parameters aren't set, destroy session and return to index
    if (!isset($_GET['reset'],$_SESSION['u_uid'], $_SESSION['csrf']) || $_GET['csrf'] != $_SESSION['csrf']) {
        $_SESSION['resetError'] = "Error code 1";
        session_destroy();
        header("Location: ../index.php");
    }

    $oldpass = $_GET['old'];            // old password
    $newpass = $_GET['new'];            // new password
    $newConfirm = $_GET['new_confirm']; // new password confirm
    $uid = $_SESSION['u_uid'];          // session uid

    if(ResetPassword($conn, $uid, $oldpass, $newpass, $newConfirm)){
        header("Location: ./logout.inc.php");
    }
    header("Location: ../index.php");

    function ResetPassword($conn, $uid, $oldpass, $newpass, $newConfirm){
        // ERROR CHECKING
        $stmt = ProcessQuery("SELECT * FROM `sapusers` WHERE `user_uid` = ?", $conn, [$uid]);
        $user = $stmt->fetch();
        $oldpassSalted = $oldpass . $user['user_salt'];
        $oldpassHashed = hash('sha256', $oldpassSalted);

        $resetError = null;
        if (empty($oldpass || $newpass)) { // if old or new passwords are empty
            $resetError = "Error code 2";
        } else if($stmt->rowCount() <= 0){ // If there are no rows
            $resetError = "Error code 6";
        } else if (strcmp($oldpassHashed, $user['user_pwd']) !== 0) { //if the old pass doesn't match database pass
            $resetError = "Error code 4";
        } else if ($newConfirm != $newpass) { //if the passwords don't match
            $resetError = "Error code 5";
        }

        //if any errors occured, unset the token and return to the index
        if($resetError != null){
            $_SESSION['resetError'] = $resetError; // assign error
            unset($_SESSION['csrf']); // unset token
            header("Location: ../index.php"); // navigate to index
            return false;
        }
        // ERROR CHECKING END


        // CHANGE PASSWORD
        $salt = bin2hex(random_bytes(16));
        $saltedPassword = $newpass . $salt;
        $hashedPass = hash('sha256', $saltedPassword);


        unset($_SESSION['csrf']); //unset csrf
        $changePass = "UPDATE `sapusers` SET `user_pwd` = ?, `user_salt` = ? WHERE `user_uid` = ?"; //$newpass, $uid
        $stmt = ProcessQuery($changePass, $conn, [$hashedPass, $salt, $uid]);
        
        return true;
        
    }
?>