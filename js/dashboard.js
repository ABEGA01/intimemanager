// Configuration des graphiques
document.addEventListener('DOMContentLoaded', function() {
    // Graphique des ventes par mois
    const ventesCtx = document.getElementById('ventesChart').getContext('2d');
    new Chart(ventesCtx, {
        type: 'line',
        data: {
            labels: ventes_par_mois.map(item => item.mois),
            datasets: [{
                label: 'Montant des ventes',
                data: ventes_par_mois.map(item => item.montant_total),
                borderColor: '#3498db',
                backgroundColor: 'rgba(52, 152, 219, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' €';
                        }
                    }
                }
            }
        }
    });

    // Graphique des articles par catégorie
    const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
    new Chart(categoriesCtx, {
        type: 'doughnut',
        data: {
            labels: articles_par_categorie.map(item => item.NOMCATEGORIE),
            datasets: [{
                data: articles_par_categorie.map(item => item.nombre_articles),
                backgroundColor: [
                    '#3498db',
                    '#2ecc71',
                    '#e74c3c',
                    '#f1c40f',
                    '#9b59b6',
                    '#34495e',
                    '#1abc9c',
                    '#e67e22'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
}); 