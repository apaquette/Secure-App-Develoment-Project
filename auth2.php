<?php
	include_once 'header.php';

	if(!isset($_SESSION['u_id'])) {
		session_destroy();
		header("Location: index.php");
	}else{
		$user_id = $_SESSION['u_id'];
		$user_uid = $_SESSION['u_uid'];
	}
?>

<section class="main-container">
	<div class="main-wrapper">
		<h2>Auth page 2</h2>
		<?php
			$ViewFile = $_GET['FileToView'];

			if(file_get_contents ("$ViewFile"))    
			{
				$FileData = file_get_contents ("$ViewFile");
				echo $FileData;
			}else
			{
				echo "no file found";
			}
		?>
	</div>
</section>

<?php
	include_once 'footer.php';
?>