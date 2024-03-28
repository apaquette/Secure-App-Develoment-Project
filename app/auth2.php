<?php
	include_once 'header.php';
	include_once 'includes/methods.inc.php';

	ValidSession();
?>

<section class="main-container">
	<div class="main-wrapper">
		<h2>Auth page 2</h2>
		<?php
			
			// if not file is provided OR file contains navigation chars
			if(!isset($_GET['FileToView']) || strpos($_GET['FileToView'], '../') !== false || strpos($_GET['FileToView'], '..\\') !== false || 
				!(realpath("C:/xampp/htdocs/Project 24/app/filesToDisplay/" . $_GET['FileToView']) && strpos(realpath("C:/xampp/htdocs/Project 24/app/filesToDisplay/" . $_GET['FileToView']), "C:\\xampp\\htdocs\\Project 24\\app\\filesToDisplay\\") === 0)){
				echo "no file found";
				exit();
			}

			$ViewFile = realpath("C:/xampp/htdocs/Project 24/app/filesToDisplay/" . $_GET['FileToView']);
			$FileData = file_get_contents("$ViewFile");
			echo $FileData;
		?>
	</div>
</section>

<?php
	include_once 'footer.php';
?>