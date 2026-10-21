<?php
session_start();
// On vide toutes les variables de session
$_SESSION = array();
// On détruit la session physique sur le serveur
session_destroy();
// Redirection immédiate vers la page de connexion
header("Location: login.php");
exit();
?>
