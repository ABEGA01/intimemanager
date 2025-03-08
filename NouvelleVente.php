<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Récupération des articles pour l'autocomplétion
try {
    // Suppression de la condition WHERE pour voir tous les articles
    $stmt = $con->query("SELECT a.IDARTICLE, a.REFERENCE, a.NOMARTICLE, a.PRIXVENTE, a.QUANTITESTOCK, 
                        c.NOMCATEGORIE, f.NOMFOURNISSEUR
                        FROM article a
                        LEFT JOIN categorie c ON a.IDCATEGORIE = c.IDCATEGORIE
                        LEFT JOIN fournisseur f ON a.IDFOURNISSEUR = f.IDFOURNISSEUR
                        ORDER BY a.NOMARTICLE");
    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Afficher le nombre d'articles trouvés
    error_log("Nombre d'articles trouvés : " . count($articles));
    
    // Debug: Afficher les articles trouvés
    foreach ($articles as $article) {
        error_log("Article trouvé : " . print_r($article, true));
    }
    
    // Vérification si des articles ont été trouvés
    if (empty($articles)) {
        $_SESSION['warning_message'] = 'Aucun article trouvé dans la base de données. Veuillez d\'abord ajouter des articles.';
    }
} catch (Exception $e) {
    error_log("Erreur SQL : " . $e->getMessage());
    die('Erreur lors de la récupération des articles : ' . $e->getMessage());
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validation des données
        $articles = $_POST['articles'] ?? [];
        $quantites = $_POST['quantites'] ?? [];
        $total = filter_input(INPUT_POST, 'total', FILTER_VALIDATE_FLOAT);
        $modePaiement = filter_input(INPUT_POST, 'mode_paiement', FILTER_SANITIZE_STRING);

        if (empty($articles) || empty($quantites) || !$total || !$modePaiement) {
            throw new Exception('Veuillez remplir tous les champs obligatoires');
        }

        // Début de la transaction
        $con->beginTransaction();

        // Génération du numéro de vente unique
        $date = date('Ymd');
        $stmt = $con->query("SELECT MAX(CAST(SUBSTRING(NUMEROVENTE, 9) AS UNSIGNED)) as max_num 
                            FROM vente 
                            WHERE NUMEROVENTE LIKE '$date%'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $nextNum = ($result['max_num'] ?? 0) + 1;
        $numeroVente = $date . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        // Insertion de la vente
        $stmt = $con->prepare("INSERT INTO vente (NUMEROVENTE, IDUTILISATEUR, DATEVENTE, MONTANTTOTAL, MONTANTFINAL, MODEPAIEMENT) 
                              VALUES (:numero_vente, :utilisateur, NOW(), :total, :total, :mode_paiement)");
        $stmt->execute([
            'numero_vente' => $numeroVente,
            'utilisateur' => $_SESSION['IDUTILISATEUR'],
            'total' => $total,
            'mode_paiement' => $modePaiement
        ]);
        $idVente = $con->lastInsertId();

        // Insertion des détails de la vente et mise à jour du stock
        foreach ($articles as $index => $idArticle) {
            $quantite = $quantites[$index];
            
            // Vérification du stock disponible
            $stmt = $con->prepare("SELECT QUANTITESTOCK FROM article WHERE IDARTICLE = :id");
            $stmt->execute(['id' => $idArticle]);
            $stockDisponible = $stmt->fetchColumn();

            if ($stockDisponible < $quantite) {
                throw new Exception('Stock insuffisant pour certains articles');
            }

            // Insertion du détail de vente
            $stmt = $con->prepare("INSERT INTO detail_vente (IDVENTE, IDARTICLE, QUANTITE, PRIXUNITAIRE) 
                                  VALUES (:vente, :article, :quantite, 
                                  (SELECT PRIXVENTE FROM article WHERE IDARTICLE = :article))");
            $stmt->execute([
                'vente' => $idVente,
                'article' => $idArticle,
                'quantite' => $quantite
            ]);

            // Mise à jour du stock
            $stmt = $con->prepare("UPDATE article 
                                  SET QUANTITESTOCK = QUANTITESTOCK - :quantite 
                                  WHERE IDARTICLE = :id");
            $stmt->execute([
                'quantite' => $quantite,
                'id' => $idArticle
            ]);

            // Enregistrement du mouvement de stock
            $stmt = $con->prepare("INSERT INTO mouvement_stock (IDARTICLE, TYPE, QUANTITE, IDUTILISATEUR, COMMENTAIRE) 
                                  VALUES (:article, 'SORTIE', :quantite, :utilisateur, 'Vente #' || :vente)");
            $stmt->execute([
                'article' => $idArticle,
                'quantite' => $quantite,
                'utilisateur' => $_SESSION['IDUTILISATEUR'],
                'vente' => $idVente
            ]);
        }

        // Journalisation de l'action
        $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                              VALUES (:id, 'NOUVELLE_VENTE', 'Nouvelle vente #' || :vente || ' pour ' || :total || '€')");
        $stmt->execute([
            'id' => $_SESSION['IDUTILISATEUR'],
            'vente' => $idVente,
            'total' => $total
        ]);

        // Validation de la transaction
        $con->commit();

        $_SESSION['success_message'] = 'Vente enregistrée avec succès';
        header('Location: ListeVentes.php');
        exit();

    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle Vente - Gestion de Stock</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
        }
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: white !important;
            font-weight: bold;
        }
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: white !important;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .article-row {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        .total-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Gestion de Stock
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="ListeArticles.php">
                            <i class="fas fa-box me-1"></i>Articles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="NouvelleVente.php">
                            <i class="fas fa-shopping-cart me-1"></i>Ventes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="ListeFournisseurs.php">
                            <i class="fas fa-truck me-1"></i>Fournisseurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="Rapports.php">
                            <i class="fas fa-chart-bar me-1"></i>Rapports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-cart me-2"></i>Nouvelle Vente
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="" class="needs-validation" novalidate>
                            <div id="articles-container">
                                <!-- Les lignes d'articles seront ajoutées ici dynamiquement -->
                            </div>

                            <button type="button" class="btn btn-success mb-3" onclick="ajouterArticle()">
                                <i class="fas fa-plus me-1"></i>Ajouter un article
                            </button>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mode_paiement" class="form-label">Mode de paiement *</label>
                                        <select class="form-select" id="mode_paiement" name="mode_paiement" required>
                                            <option value="">Sélectionnez un mode de paiement</option>
                                            <option value="ESPECES">Espèces</option>
                                            <option value="CARTE">Carte bancaire</option>
                                            <option value="VIREMENT">Virement</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="total-section">
                                        <h5 class="mb-2">Total de la vente</h5>
                                        <h3 class="mb-0" id="total-vente">0,00 €</h3>
                                        <input type="hidden" name="total" id="total-input" value="0">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="ListeVentes.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-arrow-left me-1"></i>Retour
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer la vente
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-3">
        <div class="container text-center">
            <p class="mb-0">&copy; 2024 Gestion de Stock. Tous droits réservés.</p>
        </div>
    </footer>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Données des articles pour l'autocomplétion
        const articles = <?php echo json_encode($articles); ?>;

        // Fonction pour ajouter une nouvelle ligne d'article
        function ajouterArticle() {
            const container = document.getElementById('articles-container');
            const index = container.children.length;
            
            // Debug: Afficher les articles dans la console
            console.log('Articles disponibles:', articles);
            
            const row = document.createElement('div');
            row.className = 'article-row';
            row.innerHTML = `
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Article *</label>
                        <select class="form-select article-select" name="articles[]" required>
                            <option value="">Sélectionnez un article</option>
                            ${articles && articles.length > 0 ? articles.map(article => `
                                <option value="${article.IDARTICLE}" 
                                        data-prix="${article.PRIXVENTE}"
                                        data-stock="${article.QUANTITESTOCK}">
                                    ${article.NOMARTICLE} (${article.REFERENCE}) - Stock: ${article.QUANTITESTOCK} - Prix: ${article.PRIXVENTE}€
                                </option>
                            `).join('') : '<option value="">Aucun article disponible</option>'}
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Quantité *</label>
                        <input type="number" class="form-control quantite-input" name="quantites[]" 
                               min="1" required>
                    </div>
                    <div class="col-md-2 mb-3 d-flex align-items-end">
                        <button type="button" class="btn btn-danger w-100" onclick="this.closest('.article-row').remove(); calculerTotal();">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(row);
            
            // Initialisation de Select2 pour le nouveau select
            $(row).find('.article-select').select2({
                placeholder: 'Sélectionnez un article',
                allowClear: true,
                width: '100%'
            });

            // Ajout des événements
            $(row).find('.article-select').on('change', verifierStock);
            $(row).find('.quantite-input').on('input', calculerTotal);
        }

        // Fonction pour vérifier le stock disponible
        function verifierStock() {
            const select = $(this);
            const option = select.find('option:selected');
            const stock = parseInt(option.data('stock'));
            const quantiteInput = select.closest('.row').find('.quantite-input');
            
            quantiteInput.attr('max', stock);
            if (parseInt(quantiteInput.val()) > stock) {
                quantiteInput.val(stock);
            }
        }

        // Fonction pour calculer le total
        function calculerTotal() {
            let total = 0;
            document.querySelectorAll('.article-row').forEach(row => {
                const select = row.querySelector('.article-select');
                const quantite = parseInt(row.querySelector('.quantite-input').value) || 0;
                const prix = parseFloat(select.options[select.selectedIndex]?.dataset.prix) || 0;
                total += quantite * prix;
            });
            
            document.getElementById('total-vente').textContent = total.toFixed(2) + ' €';
            document.getElementById('total-input').value = total;
        }

        // Initialisation
        $(document).ready(function() {
            // Ajout de la première ligne d'article
            ajouterArticle();

            // Validation du formulaire
            const forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html> 