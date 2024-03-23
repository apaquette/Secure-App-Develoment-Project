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

    
    // ERROR CHECKING
    $stmt = ProcessQuery("SELECT * FROM `sapusers` WHERE `user_uid` = ?", $conn, [$uid]);
    $resetError = null;
    if (empty($oldpass || $newpass)) { // if old or new passwords are empty
        $resetError = "Error code 2";
    } else if($stmt->rowCount() <= 0){ // If there are no rows
        $resetError = "Error code 6";
    } else if (strcmp($oldpass, $stmt->fetch()['user_pwd']) !== 0) { //if the password is empty
        $resetError = "Error code 4";
    } else if ($newConfirm != $newpass) { //if the passwords don't match
        $resetError = "Error code 5";
    }

    //if any errors occured, unset the token and return to the index
    if($resetError != null){
        $_SESSION['resetError'] = $resetError; // assign error
        unset($_SESSION['csrf']); // unset token
        header("Location: ../index.php"); // navigate to index
        exit(); //early exit
    }
    // ERROR CHECKING END


    // CHANGE PASSWORD
    $salt = bin2hex(random_bytes(16));
    $saltedPassword = $newPass . $salt;
    $hashedPass = hash('sha256', $saltedPassword);


    $changePass = "UPDATE `sapusers` SET `user_pwd` = ?, `user_salt` = ? WHERE `user_uid` = ?"; //$newpass, $uid
    $stmt = $conn->prepare($changePass);
    $stmt->bindParam(1, $hashedPass);
    $stmt->bindParam(2, $salt);
    $stmt->bindParam(3, $uid);
    
    unset($_SESSION['csrf']); //unset csrf
    
    //if statement execution fails
    if(!$stmt->execute()) {
        echo "Error: " . $stmt->error; // display error
        exit(); //early exit
    }
    header("Location: ./logout.inc.php");
?>