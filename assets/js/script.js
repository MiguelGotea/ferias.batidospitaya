document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let productosSeleccionados = [];
    let tipoPagoSeleccionado = null;
    let ventaId = null;
    
    // Elementos del DOM
    const tablaPedido = document.getElementById('tablaPedido').getElementsByTagName('tbody')[0];
    const totalPedido = document.getElementById('totalPedido');
    const btnImprimir = document.getElementById('imprimirBtn');
    const btnCerrarEvento = document.getElementById('cerrarEvento');
    
    // Eventos para productos
    document.querySelectorAll('.producto-card').forEach(card => {
        card.addEventListener('click', function() {
            const productoId = parseInt(this.dataset.id);
            const productoNombre = this.querySelector('h3').textContent;
            const productoPrecio = parseFloat(this.dataset.precio);
            
            // Verificar si el producto ya está en el pedido
            const productoExistente = productosSeleccionados.find(p => p.id === productoId);
            
            if (productoExistente) {
                productoExistente.cantidad += 1;
            } else {
                productosSeleccionados.push({
                    id: productoId,
                    nombre: productoNombre,
                    precio: productoPrecio,
                    cantidad: 1,
                    notas: ''
                });
            }
            
            actualizarTablaPedido();
        });
    });
    
    // Función para actualizar la tabla del pedido
    function actualizarTablaPedido() {
        tablaPedido.innerHTML = '';
        let total = 0;
        
        productosSeleccionados.forEach((producto, index) => {
            const subtotal = producto.precio * producto.cantidad;
            total += subtotal;
            
            const row = tablaPedido.insertRow();
            
            // Celda de nombre
            row.insertCell(0).textContent = producto.nombre;
            
            // Celda de cantidad
            const cellCantidad = row.insertCell(1);
            cellCantidad.innerHTML = `
                <button class="btn-cantidad" data-index="${index}" data-accion="restar">-</button>
                <span>${producto.cantidad}</span>
                <button class="btn-cantidad" data-index="${index}" data-accion="sumar">+</button>
            `;
            
            // Celda de notas
            const cellNotas = row.insertCell(2);
            const inputNotas = document.createElement('input');
            inputNotas.type = 'text';
            inputNotas.value = producto.notas;
            inputNotas.placeholder = 'Notas...';
            inputNotas.addEventListener('change', function() {
                productosSeleccionados[index].notas = this.value;
            });
            cellNotas.appendChild(inputNotas);
            
            // Celda de acciones
            const cellAcciones = row.insertCell(3);
            const btnEliminar = document.createElement('button');
            btnEliminar.textContent = '×';
            btnEliminar.className = 'btn-eliminar';
            btnEliminar.addEventListener('click', function() {
                productosSeleccionados.splice(index, 1);
                actualizarTablaPedido();
            });
            cellAcciones.appendChild(btnEliminar);
        });
        
        totalPedido.textContent = total.toFixed(2);
        btnImprimir.disabled = productosSeleccionados.length === 0 || tipoPagoSeleccionado === null;
    }
    
    // Eventos para botones de cantidad
    tablaPedido.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-cantidad')) {
            const index = parseInt(e.target.dataset.index);
            const accion = e.target.dataset.accion;
            
            if (accion === 'sumar') {
                productosSeleccionados[index].cantidad += 1;
            } else if (accion === 'restar' && productosSeleccionados[index].cantidad > 1) {
                productosSeleccionados[index].cantidad -= 1;
            }
            
            actualizarTablaPedido();
        }
    });
    
    // Eventos para métodos de pago
    document.querySelectorAll('.btn-pago').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.btn-pago').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            tipoPagoSeleccionado = this.dataset.tipo;
            btnImprimir.disabled = productosSeleccionados.length === 0;
        });
    });
    
    // Evento para imprimir
    btnImprimir.addEventListener('click', function() {
        if (productosSeleccionados.length === 0 || tipoPagoSeleccionado === null) return;
        
        // Enviar datos al servidor para procesar la venta
        const data = {
            productos: productosSeleccionados,
            tipoPago: tipoPagoSeleccionado
        };
        
        fetch('/ventas/procesar.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                ventaId = data.ventaId;
                // Abrir ventana de impresión
                window.open(`/ventas/imprimir.php?id=${ventaId}`, '_blank');
                
                // Reiniciar pedido
                productosSeleccionados = [];
                tipoPagoSeleccionado = null;
                document.querySelectorAll('.btn-pago').forEach(b => b.classList.remove('active'));
                actualizarTablaPedido();
            } else {
                alert('Error al procesar la venta: ' + data.message);
            }
        });
    });
    
    // Evento para cerrar evento
    btnCerrarEvento.addEventListener('click', function() {
        if (confirm('¿Estás seguro de que deseas cerrar el evento? Esto generará un reporte de todas las ventas no cerradas.')) {
            fetch('/cierres/generar.php', {
                method: 'POST'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = '/cierres/';
                } else {
                    alert('Error al cerrar el evento: ' + data.message);
                }
            });
        }
    });
});