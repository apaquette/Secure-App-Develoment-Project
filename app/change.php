<?php 
    include_once 'header.php'; 
    include_once 'includes/methods.inc.php';
	
	ValidSession();
?>

<section class="main-container">
    <div class="main-wrapper">
        <h2>Change Password</h2>
        <br>
        <br>
        Please ensure your new password conforms to the complexity rules:
        <br>
        • Be at least 8 characters long<br>
        • Contain a mix of uppercase and lowercase<br>
        • Contain a digit<br>
        • Contain a special character<br>
        <form class="signup-form" action="includes/reset.inc.php" method="GET">
            <input type="password" name="old" value="" placeholder="Old Password" pattern="/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?\/\\~-]).{8,}$/" required>
            <input type="password" name="new" value="" placeholder="New Password" pattern="/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?\/\\~-]).{8,}$/" required>
            <input type="password" name="new_confirm" value="" placeholder="Confirm New Password" pattern="/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?\/\\~-]).{8,}$/" required>
            <button type="submit" name="reset" value="yes">Reset</button>
            <?php 
                //session_start();
                //Generate CSRF token
                if (empty($_SESSION['csrf'])) {
                    $_SESSION['csrf'] = bin2hex(random_bytes(35));
                }
            ?>
            <input type="hidden" id="csrf" name="csrf" value="<?php echo $_SESSION['csrf'] ?? '' ?>">
        </form>
    </div>
</section>

<?php include_once 'footer.php';?>
