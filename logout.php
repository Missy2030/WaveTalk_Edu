<?php
session_start();
session_destroy();
header("Location: thankyou.php"); // Redirige vers la page de remerciements
exit();
?>