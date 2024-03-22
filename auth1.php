<?php
	include_once 'header.php';
	//Session Validation
	if (!isset($_SESSION['u_id'], $_COOKIE["PHPSESSID"]) || $_COOKIE["PHPSESSID"] != session_id()) {
		session_destroy();
		header("Location: index.php");
		exit();
	}

	$user_id = $_SESSION['u_id'];
	$user_uid = $_SESSION['u_uid'];
?>
        <section class="main-container">
            <div class="main-wrapper">
                <h2>Auth page 1</h2>
				Only authenticated users should be able to see this Page(1).
            </div>
        </section>
	
<?php	
	echo "<br>";
	//Reflect user's name on the page
	if(isset($_SESSION['u_id'])) {
		$user_uid = $_SESSION['u_uid'];
		echo "You're logged in as " . cleanChars($user_uid);
	}

	function cleanChars($val){
		$sanitized = '';
		foreach (str_split($val) as $char) {
			switch($char){
				case '&':
					$sanitized .= "&amp;";
					break;
				case '<':
					$sanitized .= "&lt;";
					break;
				case '>':
					$sanitized .= "&gt;";
					break;
				case '"':
					$sanitized .= "&quot;";
					break;
				case '\'':
					$sanitized .= "&#x27;";
					break;
				case '/':
					$sanitized .= "&#x2F;";
					break;
				case '(':
					$sanitized .= "&#x00028;";
					break;
				case ')':
					$sanitized .= "&#x00029;";
					break;
				case '{':
					$sanitized .= "&lcub;";
					break;
				case '}':
					$sanitized .= "&rcub;";
					break;
				default:
					$sanitized .= $char;
					break;
			}
		}
		//return htmlspecialchars($val);
		return $sanitized;
	}
?>

<?php
	include_once 'footer.php';
?>