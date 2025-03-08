    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> IntimeManager. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <p class="mb-0">Version 1.0.0</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Spinner de chargement -->
    <div id="loading-spinner" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="position-absolute top-50 start-50 translate-middle text-white text-center">
            <div class="spinner-border mb-2" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mb-0">Chargement...</p>
        </div>
    </div>

    <!-- Container pour les alertes -->
    <div id="alert-container" class="position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

    <!-- Scripts -->
    <?php if (isset($needjQuery)): ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <?php endif; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <script>
        // Gestion du spinner de chargement
        const loadingSpinner = document.getElementById('loading-spinner');
        
        function showLoading() {
            loadingSpinner.classList.remove('d-none');
        }
        
        function hideLoading() {
            loadingSpinner.classList.add('d-none');
        }

        // Gestion des alertes
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-dismissible fade show`;
            alert.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertContainer.appendChild(alert);
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Gestion du mode sombre
        function toggleDarkMode() {
            document.body.classList.toggle('dark-mode');
            localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
        }

        // Vérification du mode sombre au chargement
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }

        // Gestion des formulaires
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', () => {
                showLoading();
            });
        });

        // Gestion des liens
        document.querySelectorAll('a').forEach(link => {
            if (!link.target && !link.href.includes('#')) {
                link.addEventListener('click', () => {
                    showLoading();
                });
            }
        });

        // Gestion du scroll
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) {
                window.cancelAnimationFrame(scrollTimeout);
            }
            scrollTimeout = window.requestAnimationFrame(() => {
                // Code à exécuter après le scroll
            });
        });

        // Gestion des erreurs AJAX
        if (typeof jQuery !== 'undefined') {
            $(document).ajaxError((event, jqXHR, settings, error) => {
                hideLoading();
                showAlert('Une erreur est survenue. Veuillez réessayer.', 'danger');
            });
        }

        // Gestion des erreurs JavaScript
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            console.error('Error: ' + msg + '\nURL: ' + url + '\nLine: ' + lineNo + '\nColumn: ' + columnNo + '\nError object: ' + JSON.stringify(error));
            showAlert('Une erreur est survenue. Veuillez réessayer.', 'danger');
            return false;
        };

        // Gestion des promesses non gérées
        window.addEventListener('unhandledrejection', event => {
            console.error('Unhandled promise rejection:', event.reason);
            showAlert('Une erreur est survenue. Veuillez réessayer.', 'danger');
        });

        // Gestion des performances
        if ('performance' in window) {
            window.addEventListener('load', () => {
                const timing = window.performance.timing;
                const loadTime = timing.loadEventEnd - timing.navigationStart;
                console.log('Page load time:', loadTime + 'ms');
            });
        }

        // Gestion des appareils tactiles
        document.addEventListener('touchstart', function() {}, {passive: true});
        document.addEventListener('touchmove', function() {}, {passive: true});

        // Gestion des événements de visibilité
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                // Page masquée
            } else {
                // Page visible
            }
        });

        // Gestion des événements en ligne/hors ligne
        window.addEventListener('online', () => {
            showAlert('Vous êtes en ligne', 'success');
        });

        window.addEventListener('offline', () => {
            showAlert('Vous êtes hors ligne', 'warning');
        });
    </script>
</body>
</html> 