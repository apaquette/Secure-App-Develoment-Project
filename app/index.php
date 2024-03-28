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
			echo "<br>";
					
			// CREATE DATABASE
			if (isset($_POST['createDatabase'])) {
				include_once 'includes/dbh.inc.php';
				$dataBase = new Database();
				$dataBase->Create();
			}

			echo "<br>";
			
			// messages for failure, registration, and reset password error
			$messages = ['failedMsg', 'register', 'resetError'];
			foreach($messages as $msg){
				if(isset($_SESSION[$msg])){
					echo $_SESSION[$msg];
					unset($_SESSION[$msg]);
				}
			}

			if(isset($_SESSION['lockedOut'],$_SESSION['timeLeft'])) {
				echo $_SESSION['lockedOut'];
				unset($_SESSION['lockedOut']);
				echo " (" . $_SESSION['timeLeft'] . " seconds remaining).";
				unset($_SESSION['timeLeft']);
			}
		?>
	</div>
</section>

<?php include_once 'footer.php'; ?>