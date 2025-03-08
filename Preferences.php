<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Mise à jour des préférences dans la base de données
        $stmt = $con->prepare("UPDATE utilisateur SET THEME = ?, PRIMARY_COLOR = ?, SECONDARY_COLOR = ? WHERE IDUTILISATEUR = ?");
        $stmt->execute([$_POST['theme'], $_POST['primary_color'], $_POST['secondary_color'], $_SESSION['IDUTILISATEUR']]);

        // Mise à jour du fichier CSS
        $cssContent = file_get_contents('css/custom.css');
        $cssContent = preg_replace('/--primary-color:\s*#[0-9a-fA-F]{6}/', '--primary-color: ' . $_POST['primary_color'], $cssContent);
        $cssContent = preg_replace('/--secondary-color:\s*#[0-9a-fA-F]{6}/', '--secondary-color: ' . $_POST['secondary_color'], $cssContent);
        file_put_contents('css/custom.css', $cssContent);

        // Mise à jour des variables de session
        $_SESSION['THEME'] = $_POST['theme'];
        $_SESSION['PRIMARY_COLOR'] = $_POST['primary_color'];
        $_SESSION['SECONDARY_COLOR'] = $_POST['secondary_color'];

        $success = "Préférences mises à jour avec succès !";
    } catch (Exception $e) {
        $error = "Erreur lors de la mise à jour des préférences : " . $e->getMessage();
    }
}

// Récupération des préférences actuelles
try {
    $stmt = $con->prepare("SELECT THEME, PRIMARY_COLOR, SECONDARY_COLOR FROM utilisateur WHERE IDUTILISATEUR = :id");
    $stmt->execute(['id' => $_SESSION['IDUTILISATEUR']]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Préférences - IntimeManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/custom.css" rel="stylesheet">
    <style>
        .color-option {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: transform 0.2s ease;
        }
        .color-option:hover {
            transform: scale(1.1);
        }
        .color-option.selected {
            border-color: #000;
        }
        .theme-preview {
            width: 100%;
            height: 200px;
            border-radius: 10px;
            margin-top: 10px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            transition: all 0.3s ease;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/intimelogo.png" alt="IntimeManager Logo">
                IntimeManager
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
                        <a class="nav-link" href="ListeVentes.php">
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
                    <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="GestionUtilisateurs.php">
                            <i class="fas fa-users me-1"></i>Utilisateurs
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link active" href="Preferences.php">
                            <i class="fas fa-cog me-1"></i>Préférences
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
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">
                            <i class="fas fa-paint-brush me-2"></i>Personnalisation de l'Interface
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (isset($success)): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php 
                                echo htmlspecialchars($success);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="">
                            <div class="mb-4">
                                <label class="form-label">Mode d'affichage</label>
                                <div class="d-flex gap-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme" id="theme_light" 
                                               value="light" <?php echo ($preferences['THEME'] ?? 'light') === 'light' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="theme_light">
                                            <i class="fas fa-sun me-1"></i>Clair
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="theme" id="theme_dark" 
                                               value="dark" <?php echo ($preferences['THEME'] ?? '') === 'dark' ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="theme_dark">
                                            <i class="fas fa-moon me-1"></i>Sombre
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Couleur principale</label>
                                <div class="d-flex flex-wrap">
                                    <div class="color-option selected" style="background-color: #2c3e50;" data-color="#2c3e50"></div>
                                    <div class="color-option" style="background-color: #3498db;" data-color="#3498db"></div>
                                    <div class="color-option" style="background-color: #e74c3c;" data-color="#e74c3c"></div>
                                    <div class="color-option" style="background-color: #2ecc71;" data-color="#2ecc71"></div>
                                    <div class="color-option" style="background-color: #f1c40f;" data-color="#f1c40f"></div>
                                    <div class="color-option" style="background-color: #9b59b6;" data-color="#9b59b6"></div>
                                    <div class="color-option" style="background-color: #e67e22;" data-color="#e67e22"></div>
                                </div>
                                <input type="hidden" name="primary_color" id="primary_color" value="<?php echo $preferences['PRIMARY_COLOR'] ?? '#2c3e50'; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Couleur secondaire</label>
                                <div class="d-flex flex-wrap">
                                    <div class="color-option selected" style="background-color: #3498db;" data-color="#3498db"></div>
                                    <div class="color-option" style="background-color: #2c3e50;" data-color="#2c3e50"></div>
                                    <div class="color-option" style="background-color: #e74c3c;" data-color="#e74c3c"></div>
                                    <div class="color-option" style="background-color: #2ecc71;" data-color="#2ecc71"></div>
                                    <div class="color-option" style="background-color: #f1c40f;" data-color="#f1c40f"></div>
                                    <div class="color-option" style="background-color: #9b59b6;" data-color="#9b59b6"></div>
                                    <div class="color-option" style="background-color: #e67e22;" data-color="#e67e22"></div>
                                </div>
                                <input type="hidden" name="secondary_color" id="secondary_color" value="<?php echo $preferences['SECONDARY_COLOR'] ?? '#3498db'; ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Aperçu</label>
                                <div class="theme-preview"></div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i>Enregistrer les préférences
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des couleurs
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', function() {
                // Retirer la sélection de tous les autres éléments du même groupe
                this.parentElement.querySelectorAll('.color-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                // Ajouter la sélection à l'élément cliqué
                this.classList.add('selected');
                
                // Mettre à jour l'input caché correspondant
                const color = this.dataset.color;
                if (this.parentElement.previousElementSibling.textContent.includes('principale')) {
                    document.getElementById('primary_color').value = color;
                } else {
                    document.getElementById('secondary_color').value = color;
                }
                
                // Mettre à jour l'aperçu
                updatePreview();
            });
        });

        // Mise à jour de l'aperçu
        function updatePreview() {
            const primaryColor = document.getElementById('primary_color').value;
            const secondaryColor = document.getElementById('secondary_color').value;
            const preview = document.querySelector('.theme-preview');
            preview.style.background = `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})`;
        }

        // Initialisation de l'aperçu
        updatePreview();
    </script>
</body>
</html> 