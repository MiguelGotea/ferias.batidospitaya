<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = $_POST['password'] ?? '';

    if ($clave === PASSWORD_VENTAS) {
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['rol'] = 'ventas';
        header('Location: /ventas/');
        exit;
    } elseif ($clave === PASSWORD_CIERRES) {
        session_start();
        $_SESSION['loggedin'] = true;
        $_SESSION['rol'] = 'cierres';
        header('Location: /cierres/');
        exit;
    } else {
        $error = "Contraseña incorrecta";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Batidos Pitaya</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/img/icon12.png">
</head>
<body>
    <div class="login-container">
        <img src="/assets/img/Logo.svg" alt="Batidos Pitaya" class="logo">
        <form method="post">
            <input type="password" name="password" placeholder="Contraseña" required>
            <button type="submit">Ingresar</button>
            <?php if (isset($error)): ?>
                <p class="error"><?= $error ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>