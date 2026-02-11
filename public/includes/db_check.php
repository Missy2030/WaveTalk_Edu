<?php
// includes/db_check.php
if (!isset($pdo)) {
    try {
        // Vérifier le chemin relatif
        $db_path = __DIR__ . '/../private/db_connection.php';
        if (file_exists($db_path)) {
            require_once $db_path;
        } else {
            // Essayer un autre chemin
            $db_path = __DIR__ . '/../../private/db_connection.php';
            if (file_exists($db_path)) {
                require_once $db_path;
            } else {
                error_log("DB connection file not found from: " . __DIR__);
                return;
            }
        }
    } catch (Exception $e) {
        error_log("DB connection failed in widget: " . $e->getMessage());
        return;
    }
}
?>