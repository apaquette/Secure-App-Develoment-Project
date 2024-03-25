<?php declare(strict_types=1);
    class Database{
        private $host = "localhost";
        private $username = "TEST";
        private $password = "";
        private $name = "secureappdev";

        public function GetConnection(){
            try{
                $conn = new PDO("mysql:host=$this->host", $this->username, $this->password);
                $conn->exec("USE $this->name");
            }catch (PDOException $e) {
                throw $e;
            }
            return $conn;
        }

        // PROCESS QUERY
        public function ProcessQuery($query, $params = []){
            $conn = $this->GetConnection();
            $stmt = $conn->prepare($query);
            for($i = 1; $i <= sizeof($params); $i++){
                $stmt->bindParam($i, $params[$i - 1]);
            }

            if(!$stmt->execute()) {
                die("Error: " . $stmt->error);
            }
            
            return $stmt;
        }

        // CREATE DATABASE
        public function Create(){
            try {
                // Connect to MySQL server
                $tempConn = new PDO("mysql:host=$this->host", $this->username, $this->password);

                // Set the PDO error mode to exception
                $tempConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $existingDatabases = $tempConn->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
                
                                                    
                if (!in_array('secureappdev', $existingDatabases)) {
                    // Create a new database
                    $sql = "CREATE DATABASE secureappdev";
                    $tempConn->exec($sql);
                    
                    $sql = "USE secureappdev";
                    $tempConn->exec($sql);
                    
                    $makeUsers = "CREATE TABLE `sapusers` 
                    (
                    `user_id` int(11) NOT NULL AUTO_INCREMENT,
                    user_uid varchar(256) NOT NULL,
                    user_pwd varchar(256) NOT NULL,
                    user_salt varchar(32) NOT NULL,
                    user_admin int(2) NOT NULL DEFAULT 0,
                    primary key (`user_id`))";
                    
                    $tempConn->exec($makeUsers);
                    
                    
                    $salt = bin2hex(random_bytes(16));
                    $saltedPassword = 'AdminPass1!' . $salt;
                    $adminPass = hash('sha256', $saltedPassword);
                    $makeAdmin = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_salt`, `user_admin`) VALUES ('admin', '" . $adminPass . "','". $salt ."', '1')";

                    $tempConn->exec($makeAdmin);

                    $salt = bin2hex(random_bytes(16));
                    $saltedPassword = 'Password1!' . $salt;
                    $userPass = hash('sha256', $saltedPassword);
                    $makeUser = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_salt`, `user_admin`) VALUES ('user1', '" . $userPass . "','". $salt ."', '0')";
                    
                    $tempConn->exec($makeUser);
                    
                    
                    //Make table to track pre-auth sessions that should be blocked for failed login attempts
                    $makeCounter = "CREATE TABLE `failedLogins`
                    (
                        `event_id` int(11) NOT NULL AUTO_INCREMENT,
                        `ip` varchar(128) NOT NULL,
                        `timeStamp` datetime NOT NULL,
                        `failedLoginCount` int(11) NOT NULL,
                        `lockOutCount` int(11) NOT NULL,
                        primary key (`event_id`)
                    )";
                    $tempConn->exec($makeCounter);
                    
                    $loginEvents = "CREATE TABLE `loginEvents`
                    (
                    `event_id` int(11) NOT NULL AUTO_INCREMENT,
                    `ip` varchar(128) NOT NULL,
                    `timeStamp` datetime NOT NULL,
                    `user_id` varchar(50) NOT NULL,
                    `outcome` varchar(7) NOT NULL,
                    primary key (`event_id`)
                    )";
                    $tempConn->exec($loginEvents);
                }
            } catch (PDOException $e) {
                echo "Error: " . $e->getMessage();
            }
            $tempConn = null; // Close the database connection
        }

        public function CreateSuccessMsg(){
            echo "<br>Database created successfully<br>";
            echo "Table 'users' created successfully<br>";
            echo "Admin Added (Username = admin, Password =AdminPass1!<br>";
            echo "User Added (Username = user1, Password =Password1!<br>";
        }
    }
?>