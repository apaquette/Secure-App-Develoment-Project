<?php
	include_once 'header.php';
	include_once 'includes/methods.inc.php';
	
	ValidSession();
?>

<section class="main-container">
	<div class="main-wrapper">
		<h2>Auth page 2</h2>
		<?php
			
			if(!isset($_GET['FileToView']) || strpos($_GET['FileToView'], '../') !== false || strpos($_GET['FileToView'], '..\\') !== false){
				echo "no file found";
			}else{
				$ViewFile = realpath("C:/xampp/htdocs/Project 24/filesToDisplay/" . $_GET['FileToView']);
				if($ViewFile && strpos($ViewFile, "C:\\xampp\\htdocs\\Project 24\\filesToDisplay\\") === 0){
					$FileData = file_get_contents("$ViewFile");
					echo $FileData;
				}else{
					echo "no file found";
				}
			}
		?>
	</div>
</section>

<?php
	include_once 'footer.php';
?>