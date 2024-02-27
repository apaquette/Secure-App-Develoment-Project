    <?php
        include_once 'header.php';
    ?>

        <section class="main-container">
            <div class="main-wrapper">
                <h2>Successful Logout</h2>

                <?php
                    if (!isset($_SESSION['u_id'])) {
                        echo "You are now logged out!";
                    }

                    if(isset($_SESSION['resetSuccess'])) {
						echo $_SESSION['resetSuccess'];
						unset($_SESSION['resetSuccess']);
					}
                ?>
            </div>
        </section>

    <?php
        include_once 'footer.php';
    ?>
