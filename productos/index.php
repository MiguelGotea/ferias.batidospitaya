<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$productos = obtenerProductos();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos - Batidos Pitaya</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" href="/assets/img/icon12.png">
</head>
<body>
    <header>
        <img src="/assets/img/Logo.svg" alt="Batidos Pitaya" class="logo">
        <a href="/ventas/" class="btn">VOLVER A VENTAS</a>
    </header>
    
    <main class="productos-container">
        <h1>Administrar Productos</h1>
        
        <form id="formProducto" class="producto-form">
            <input type="hidden" id="productoId">
            <div class="form-group">
                <label for="nombre">Nombre:</label>
                <input type="text" id="nombre" required>
            </div>
            <div class="form-group">
                <label for="precio">Precio (C$):</label>
                <input type="number" id="precio" step="0.01" min="0" required>
            </div>
            <button type="submit" class="btn">Guardar</button>
            <button type="button" id="cancelarEdicion" class="btn btn-secundario">Cancelar</button>
        </form>
        
        <div class="productos-list">
            <h2>Productos Disponibles</h2>
            <table class="tabla-productos">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr>
                            <td><?= htmlspecialchars($producto['nombre']) ?></td>
                            <td>C$ <?= number_format($producto['precio'], 2) ?></td>
                            <td><?= $producto['activo'] ? 'Activo' : 'Inactivo' ?></td>
                            <td>
                                <button class="btn-editar" data-id="<?= $producto['id'] ?>">Editar</button>
                                <button class="btn-eliminar" data-id="<?= $producto['id'] ?>"><?= $producto['activo'] ? 'Desactivar' : 'Activar' ?></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
    
    <script src="/assets/js/productos.js"></script>
</body>
</html>