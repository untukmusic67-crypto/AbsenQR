<?php
// api/admin_logout.php
session_start();
session_unset();
session_destroy();
header("Location: ../pages/login.php");
exit;
?>