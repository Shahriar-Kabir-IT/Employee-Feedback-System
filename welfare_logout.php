<?php
session_start();
session_destroy();
header('Location: welfare_login.php');
exit;
?>