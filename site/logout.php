<?php
session_start();
unset($_SESSION['loggedIn']);
unset($_SESSION['username']);
session_destroy();
$redirectUrl = urldecode($_GET['redirect']);
header('Location: index.php');
exit();
?>