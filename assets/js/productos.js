document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formProducto');
    const btnCancelar = document.getElementById('cancelarEdicion');
    let editandoId = null;
    
    // Evento para formulario
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            action: editandoId ? 'editar' : 'crear',
            id: editandoId,
            nombre: document.getElementById('nombre').value,
            precio: parseFloat(document.getElementById('precio').value)
        };
        
        fetch('/productos/procesar.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        });
    });
    
    // Evento para cancelar edición
    btnCancelar.addEventListener('click', function() {
        editandoId = null;
        form.reset();
        document.getElementById('productoId').value = '';
        this.style.display = 'none';
    });
    
    // Eventos para botones de editar
    document.querySelectorAll('.btn-editar').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            editandoId = parseInt(this.dataset.id);
            
            document.getElementById('productoId').value = editandoId;
            document.getElementById('nombre').value = row.cells[0].textContent;
            document.getElementById('precio').value = parseFloat(row.cells[1].textContent.replace('C$ ', ''));
            
            btnCancelar.style.display = 'inline-block';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
    
    // Eventos para botones de activar/desactivar
    document.querySelectorAll('.btn-eliminar').forEach(btn => {
        btn.addEventListener('click', function() {
            if (confirm('¿Estás seguro de cambiar el estado de este producto?')) {
                const data = {
                    action: 'toggle',
                    id: parseInt(this.dataset.id)
                };
                
                fetch('/productos/procesar.php', {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: {
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
            }
        });
    });
});