<?php
// Configuración básica
// Clave para el equipo de ventas en feria (accede a /ventas/ y puede cerrar evento)
define('PASSWORD_VENTAS', 'ferias123$');
// Clave para consulta de cierres (accede directamente a /cierres/, solo lectura)
define('PASSWORD_CIERRES', 'ventasf123$');
// Alias de compatibilidad (por si queda alguna referencia directa)
define('PASSWORD', PASSWORD_VENTAS);
date_default_timezone_set('America/Managua'); // UTC-6
setlocale(LC_MONETARY, 'es_NI');