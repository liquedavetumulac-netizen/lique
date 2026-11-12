<?php
// Include this file AFTER config.php on pages that require login.
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
