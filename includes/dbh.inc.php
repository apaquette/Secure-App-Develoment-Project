<?php

    $dbServername = "localhost";
    $dbUsername = "TEST";
    $dbPassword = "";
    $dbName = "secureappdev";

	try{
        $conn = new PDO("mysql:host=$dbServername", $dbUsername, $dbPassword);
        $conn->exec("USE $dbName");
	}
	catch (PDOException $e) {
            //echo "Error: " . $e->getMessage();
	}
	
?>