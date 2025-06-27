    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Función para formatear números como moneda
        function formatCurrency(amount) {
            return new Intl.NumberFormat('es-MX', {
                style: 'currency',
                currency: 'MXN'
            }).format(amount);
        }

        // Función para mostrar alertas
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            if (alertContainer) {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show`;
                alert.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                alertContainer.appendChild(alert);
                
                // Auto-remover después de 5 segundos
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 5000);
            }
        }

        // Función para confirmar eliminación
        function confirmDelete(message = '¿Estás seguro de que deseas eliminar este elemento?') {
            return confirm(message);
        }

        // Inicializar tooltips de Bootstrap
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Función para actualizar gráficos en tiempo real
        function updateChart(chartId, data) {
            const chart = Chart.getChart(chartId);
            if (chart) {
                chart.data = data;
                chart.update();
            }
        }

        // Validación de formularios
        function validateForm(formId) {
            const form = document.getElementById(formId);
            if (form) {
                return form.checkValidity();
            }
            return false;
        }
    </script>
    
    <!-- Footer -->
    <footer class="bg-dark text-light py-3">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <small class="text-light">
                        <i class="fas fa-chart-line me-2"></i>
                        Contabilidad Familiar
                    </small>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-light">
                        Software creado por 
                        <a href="https://miksoftware.com" target="_blank" class="text-info text-decoration-none">
                            <strong>MikSoftware.com</strong>
                        </a>
                    </small>
                </div>
            </div>
        </div>
    </footer>
</body>
</html>
