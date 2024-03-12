<?php

    $dbServername = "localhost";
    $dbUsername = "TEST";
    $dbPassword = "";
    $dbName = "secureappdev";

	try{
    	// $conn = mysqli_connect($dbServername, $dbUsername, $dbPassword, $dbName);
        $conn = new PDO("mysql:host=$dbServername", $dbUsername, $dbPassword);
        $conn->exec("USE $dbName");
	}
	catch (PDOException $e) {
            //echo "Error: " . $e->getMessage();
	}
	
?>