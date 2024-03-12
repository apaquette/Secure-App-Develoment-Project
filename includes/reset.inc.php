<?php

//If user is not logged in or requesting to reset, redirect
include 'dbh.inc.php';
session_start();

if (!isset($_GET['reset'],$_SESSION['u_uid'])) {
    $_SESSION['resetError'] = "Error code 1";
    header("Location: ../index.php");
} else {
    $oldpass = $_GET['old'];
    $newConfirm = $_GET['new_confirm'];
    $newpass = $_GET['new'];

    if (empty($oldpass || $newpass)) {
        $_SESSION['resetError'] = "Error code 2";
    } else {
        
        $uid = $_SESSION['u_uid'];

        $checkOld = "SELECT * FROM `sapusers` WHERE `user_uid` = ?"; //$uid
        $stmt = $conn->prepare($checkOld);
        $stmt->bindParam(1, $uid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) { 

            $row = mysqli_fetch_assoc($result); 

			
            if (strcmp($oldpass, $row['user_pwd']) !== 0) {
                $_SESSION['resetError'] = "Error code 4";
                header("Location: ../index.php");
                exit();
            } else {
                if ($newConfirm == $newpass) { //confirm they match

                    $changePass = "UPDATE `sapusers` SET `user_pwd` = ? WHERE `user_uid` = ?"; //$newpass, $uid
                    $stmt = $conn->prepare($changePass);
                    $stmt->bindParam(1, $newpass);
                    $stmt->bindParam(2, $uid);
                            
                    if(!$stmt->execute()) {
                        echo "Error: " . $stmt->error;
                    }

                    header("Location: ./logout.inc.php");
                    exit();
                } else {
                    $_SESSION['resetError'] = "Error code 5";
                    header("Location: ../index.php");
                    exit();
                }
            }
        } else {
            $_SESSION['resetError'] = "Error code 6"; 
            header("Location: ../index.php");
            exit();
        }
    }
}