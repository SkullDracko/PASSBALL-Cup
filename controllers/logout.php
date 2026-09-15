<?php
/**
 * PASSBALL Cup - Logout del participante
 */

session_start();
unset($_SESSION['usuario']);
unset($_SESSION['admin']);
session_unset();
session_destroy();
header("Location: ../login.php");
exit;