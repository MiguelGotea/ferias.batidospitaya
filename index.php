<?php
require_once 'includes/auth.php';
// Redirigir según el rol de la sesión
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'cierres') {
    header('Location: /cierres/');
} else {
    header('Location: /ventas/');
}
exit;