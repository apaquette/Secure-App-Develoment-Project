<?php include_once 'header.php'; ?>
<section class="main-container">
	<div class="main-wrapper">
		<h2>Homepage</h2>
		Welcome to this Super Secure PHP Application.
		<form method="post" action="">
			<input type="submit" name="createDatabase" value="Create / Reset Database & Table">
			<br><br><br>
		</form>
		<?php
			//DATABASE SETUP
			$host = "localhost";
			$username = "TEST";
			$password = "";
			
			echo "<br>";
					
			// CREATE DATABASE
			if (isset($_POST['createDatabase'])) {
				try {
					// Connect to MySQL server
					$conn = new PDO("mysql:host=$host", $username, $password);

					// Set the PDO error mode to exception
					$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

					$existingDatabases = $conn->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
					echo "<br>";
														
					if (!in_array('secureappdev', $existingDatabases)) {
						// Create a new database
						$sql = "CREATE DATABASE secureappdev";
						$conn->exec($sql);
						echo "Database created successfully<br>";
						$sql = "USE secureappdev";
						$conn->exec($sql);
						
						$makeUsers = "CREATE TABLE `sapusers` 
						(
						`user_id` int(11) NOT NULL AUTO_INCREMENT,
						user_uid varchar(256) NOT NULL,
						user_pwd varchar(256) NOT NULL,
						user_admin int(2) NOT NULL DEFAULT 0,
						primary key (`user_id`))";
						
						$conn->exec($makeUsers);
						echo "Table 'users' created successfully<br>"; 

						$makeAdmin = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_admin`) VALUES ('admin', 'AdminPass1!', '1')";
						$conn->exec($makeAdmin);
						echo "Admin Added (Username = admin, Password =AdminPass1!<br>";
						
						$makeAdmin = "INSERT INTO `sapusers` (`user_uid`, `user_pwd`, `user_admin`) VALUES ('user1', 'Password1!', '0')";
						$conn->exec($makeAdmin);
						echo "User Added (Username = user1, Password =Password1!<br>";
						
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
						$conn->exec($makeCounter);
						
						$loginEvents = "CREATE TABLE `loginEvents`
						(
						`event_id` int(11) NOT NULL AUTO_INCREMENT,
						`ip` varchar(128) NOT NULL,
						`timeStamp` datetime NOT NULL,
						`user_id` varchar(50) NOT NULL,
						`outcome` varchar(7) NOT NULL,
						primary key (`event_id`)
						)";
						$conn->exec($loginEvents);
					}

				} catch (PDOException $e) {
					echo "Error: " . $e->getMessage();
				}
				$conn = null; // Close the database connection
			}
											
			//Message if login fails 
			echo "<br>";
			
			// messages for failure, registration, and reset password error
			$messages = ['failedMsg', 'register', 'resetError'];
			foreach($messages as $msg){
				if(isset($_SESSION[$msg])){
					echo $_SESSION[$msg];
					unset($_SESSION[$msg]);
				}
			}

			//Message if locked out 
			if(isset($_SESSION['lockedOut'])) {
				echo $_SESSION['lockedOut'];
				unset($_SESSION['lockedOut']);
				//Remaining seconds for current lockout
				if(isset($_SESSION['timeLeft'])) {
					echo " (" . $_SESSION['timeLeft'] . " seconds remaining).";
					unset($_SESSION['timeLeft']);
				}
			}
		?>
	</div>
</section>

<?php include_once 'footer.php'; ?>