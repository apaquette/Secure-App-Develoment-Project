<?php
    include_once 'header.php';
?>

    <section class="main-container">
        <div class="main-wrapper">
            <h2>Signup</h2>
            Please note your username must only contain alphabetic characters.
            <br><br>
			Please ensure your password conforms to the complexity rules:
			<br><br>
			• Be at least 8 characters long<br>
			• Contain a mix of uppercase and lowercase<br>
			• Contain a digit<br>
            • Contain a special character<br>
            <form class="signup-form" action="includes/signup.inc.php" method="POST">
                <input type="text" name="uid" value="" placeholder="Username" required>
                <input type="password" name="pwd" value="" placeholder="Password" pattern="/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[!@#$%^&*()_+{}\[\]:;<>,.?\/\\~-]).{8,}$/" required>

                <button type="submit" name="submit">Register now</button>
            </form>
        </div>
    </section>

<?php
    include_once 'footer.php';
?>
