<?php
// admin/logout.php - Logout Admin
session_start();
session_unset();
session_destroy();
header('Location: index.php');
exit;

