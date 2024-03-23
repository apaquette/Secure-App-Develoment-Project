<?php

    $dbServername = "localhost";
    $dbUsername = "TEST";
    $dbPassword = "";
    $dbName = "secureappdev";

	try{
        $conn = new PDO("mysql:host=$dbServername", $dbUsername, $dbPassword);
        $conn->exec("USE $dbName");
	}catch (PDOException $e) {
        //echo "Error: " . $e->getMessage();
	}

    function ProcessQuery($query, $conn, $params = []){
        $stmt = $conn->prepare($query);
        for($i = 1; $i <= sizeof($params); $i++){
            $stmt->bindParam($i, $params[$i - 1]);
        }

        if(!$stmt->execute()) {
            die("Error: " . $stmt->error);
        }
        return $stmt;
    }
?>