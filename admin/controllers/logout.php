<?php
/**
 * PASSBALL Cup - Logout del administrador
 */

session_start();
unset($_SESSION['admin']);

header("Location: ../login.php");
exit;