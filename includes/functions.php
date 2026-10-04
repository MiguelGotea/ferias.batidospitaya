<?php
require_once 'db.php';

function formatearFecha($fecha) {
    $meses = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
    $fechaObj = new DateTime($fecha);
    $dia = $fechaObj->format('d');
    $mes = $meses[(int)$fechaObj->format('m') - 1];
    $anio = $fechaObj->format('y');
    return "$dia-$mes-$anio";
}

function formatearHora($hora) {
    $horaObj = new DateTime($hora);
    return $horaObj->format('h:i a');
}

function obtenerProductos() {
    global $db;
    $stmt = $db->query("SELECT * FROM productos WHERE activo = 1");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function crearVenta($tipoPago) {
    global $db;
    $fechaActual = date('Y-m-d H:i:s');
    $fechaHoy = date('Y-m-d');
    $stmt = $db->prepare("INSERT INTO ventas (tipo_pago, fecha_hora, fecha_registro) VALUES (?, ?, ?)");
    $stmt->execute([$tipoPago, $fechaActual, $fechaHoy]);
    return $db->lastInsertId();
}

// Otras funciones útiles...
// Agregar estas funciones al archivo existente

function obtenerVentaActiva() {
    global $db;
    $stmt = $db->query("SELECT id FROM ventas WHERE cerrada = 0 ORDER BY id DESC LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerVentaPorId($id) {
    global $db;
    $stmt = $db->prepare("SELECT v.*, COUNT(d.id) as items 
                         FROM ventas v 
                         LEFT JOIN detalles_venta d ON v.id = d.venta_id 
                         WHERE v.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerDetallesVenta($ventaId) {
    global $db;
    $stmt = $db->prepare("SELECT d.*, p.nombre 
                         FROM detalles_venta d 
                         JOIN productos p ON d.producto_id = p.id 
                         WHERE d.venta_id = ?");
    $stmt->execute([$ventaId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function procesarVenta($productos, $tipoPago, $nombreCliente = null, $clubPitaya = null) {
    global $db;
    
    try {
        $db->beginTransaction();
        
        $fechaActual = date('Y-m-d H:i:s');
        $fechaHoy = date('Y-m-d');
        
        // Crear venta con fecha_hora y fecha_registro de Nicaragua
        $stmtVenta = $db->prepare("INSERT INTO ventas (tipo_pago, nombre_cliente, club_pitaya, fecha_hora, fecha_registro) VALUES (?, ?, ?, ?, ?)");
        $stmtVenta->execute([$tipoPago, $nombreCliente, $clubPitaya, $fechaActual, $fechaHoy]);
        $ventaId = $db->lastInsertId();
        
        // Agregar detalles (capturando nombre, precio actual y hora)
        $stmtDetalle = $db->prepare("INSERT INTO detalles_venta 
                                   (venta_id, producto_id, cantidad, precio_unitario, notas, nombre_producto, precio_unitario_original, fecha_hora) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($productos as $producto) {
            // Validación más flexible para permitir precios 0
            if (!isset($producto['id']) || !isset($producto['cantidad']) || $producto['cantidad'] <= 0) {
                throw new Exception('Producto inválido: ID o cantidad incorrecta');
            }
            
            // Obtener datos actuales del producto
            $stmtProducto = $db->prepare("SELECT nombre, precio FROM productos WHERE id = ?");
            $stmtProducto->execute([$producto['id']]);
            $productoActual = $stmtProducto->fetch(PDO::FETCH_ASSOC);
            
            if (!$productoActual) {
                throw new Exception('Producto no encontrado: ID ' . $producto['id']);
            }
            
            // Usar el precio del producto de la base de datos, no el que viene del frontend
            $precioFinal = $productoActual['precio'];
            
            $stmtDetalle->execute([
                $ventaId,
                $producto['id'],
                $producto['cantidad'],
                $precioFinal,  // Usar precio de la BD
                $producto['notas'] ?? '',
                $productoActual['nombre'],  // Nombre actual
                $productoActual['precio'],   // Precio actual
                $fechaActual
            ]);
        }
        
        $db->commit();
        return ['success' => true, 'ventaId' => $ventaId];
    } catch (PDOException $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function cerrarEvento() {
    global $db;
    
    try {
        $db->beginTransaction();
        
        // Obtener ventas no cerradas
        $stmtVentas = $db->query("SELECT * FROM ventas WHERE cerrada = 0");
        $ventas = $stmtVentas->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($ventas)) {
            return ['success' => false, 'message' => 'No hay ventas pendientes de cerrar'];
        }
        
        // Calcular totales
        $totalVentas = 0;
        $totalPos = 0;
        $totalEfectivo = 0;
        $fechaCierre = date('Y-m-d H:i:s');
        
        foreach ($ventas as $venta) {
            $stmtDetalles = $db->prepare("SELECT SUM(precio_unitario * cantidad) as total 
                                         FROM detalles_venta 
                                         WHERE venta_id = ?");
            $stmtDetalles->execute([$venta['id']]);
            $total = $stmtDetalles->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
            
            $totalVentas += $total;
            
            if ($venta['tipo_pago'] === 'POS') {
                $totalPos += $total;
            } else {
                $totalEfectivo += $total;
            }
            
            // Marcar venta como cerrada
            $stmtCerrar = $db->prepare("UPDATE ventas SET cerrada = 1, fecha_cierre = ? WHERE id = ?");
            $stmtCerrar->execute([$fechaCierre, $venta['id']]);
        }
        
        // Registrar cierre con la misma fecha_hora exacta de Nicaragua
        $stmtCierre = $db->prepare("INSERT INTO cierres 
                                   (fecha_hora, total_ventas, total_pos, total_efectivo) 
                                   VALUES (?, ?, ?, ?)");
        $stmtCierre->execute([$fechaCierre, $totalVentas, $totalPos, $totalEfectivo]);
        
        $db->commit();
        return ['success' => true];
    } catch (PDOException $e) {
        $db->rollBack();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

function obtenerCierres() {
    global $db;
    $stmt = $db->query("SELECT * FROM cierres ORDER BY fecha_hora DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerCierrePorId($id) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM cierres WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerVentasPorCierre($cierreId) {
    global $db;
    $stmt = $db->prepare("SELECT v.* 
                         FROM ventas v
                         JOIN cierres c ON (v.fecha_cierre = c.fecha_hora OR v.fecha_cierre = DATE_SUB(c.fecha_hora, INTERVAL 6 HOUR))
                         WHERE c.id = ?
                         ORDER BY v.id");
    $stmt->execute([$cierreId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerProductosVendidosPorCierre($cierreId) {
    global $db;
    
    $query = "
        SELECT 
            dv.id,
            dv.venta_id,
            v.fecha_hora as fecha_venta,
            v.tipo_pago,
            dv.producto_id,
            p.nombre as nombre_producto,
            dv.cantidad,
            dv.precio_unitario,
            dv.notas
        FROM detalles_venta dv
        JOIN productos p ON dv.producto_id = p.id
        JOIN ventas v ON dv.venta_id = v.id
        JOIN cierres c ON (v.fecha_cierre = c.fecha_hora OR v.fecha_cierre = DATE_SUB(c.fecha_hora, INTERVAL 6 HOUR))
        WHERE c.id = ?
        ORDER BY v.fecha_hora, dv.venta_id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$cierreId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerTodosCierres() {
    global $db;
    $stmt = $db->query("SELECT * FROM cierres ORDER BY fecha_hora DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerDatosCierre($cierreId) {
    global $db;
    $stmt = $db->prepare("SELECT * FROM cierres WHERE id = ?");
    $stmt->execute([$cierreId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function obtenerProductosDeCierre($cierreId) {
    global $db;
    
    $query = "
        SELECT 
            dv.id,
            dv.venta_id,
            v.fecha_hora as fecha_venta,
            v.tipo_pago,
            dv.producto_id,
            p.nombre as nombre_producto,
            dv.cantidad,
            dv.precio_unitario,
            dv.notas
        FROM cierres c
        JOIN ventas v ON (v.fecha_cierre = c.fecha_hora OR v.fecha_cierre = DATE_SUB(c.fecha_hora, INTERVAL 6 HOUR))
        JOIN detalles_venta dv ON dv.venta_id = v.id
        JOIN productos p ON p.id = dv.producto_id
        WHERE c.id = ?
        ORDER BY v.fecha_hora, dv.venta_id
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$cierreId]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}