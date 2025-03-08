<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Vérification des droits d'administration
if ($_SESSION['ROLE'] !== 'ADMIN') {
    $_SESSION['error_message'] = 'Vous n\'avez pas les droits nécessaires pour accéder à cette page.';
    header('Location: ListeArticles.php');
    exit();
}

// Variables pour l'en-tête
$pageTitle = 'Gestion des Catégories';
$bodyClass = 'categories-page';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Ajout d'une catégorie
        if (isset($_POST['ajouter_categorie'])) {
            $stmt = $con->prepare("INSERT INTO categorie (NOMCATEGORIE, DESCRIPTION) VALUES (?, ?)");
            $stmt->execute([$_POST['nom_categorie'], $_POST['description']]);
            $_SESSION['success_message'] = "Catégorie ajoutée avec succès.";
        }
        
        // Modification d'une catégorie
        elseif (isset($_POST['modifier_categorie'])) {
            $stmt = $con->prepare("UPDATE categorie SET NOMCATEGORIE = ?, DESCRIPTION = ? WHERE IDCATEGORIE = ?");
            $stmt->execute([$_POST['nom_categorie'], $_POST['description'], $_POST['id_categorie']]);
            $_SESSION['success_message'] = "Catégorie modifiée avec succès.";
        }
        
        // Suppression d'une catégorie
        elseif (isset($_POST['supprimer_categorie'])) {
            // Vérifier si la catégorie est utilisée
            $stmt = $con->prepare("SELECT COUNT(*) FROM article WHERE IDCATEGORIE = ?");
            $stmt->execute([$_POST['id_categorie']]);
            $count = $stmt->fetchColumn();
            
            if ($count > 0) {
                $_SESSION['error_message'] = "Impossible de supprimer cette catégorie car elle est utilisée par des articles.";
            } else {
                $stmt = $con->prepare("DELETE FROM categorie WHERE IDCATEGORIE = ?");
                $stmt->execute([$_POST['id_categorie']]);
                $_SESSION['success_message'] = "Catégorie supprimée avec succès.";
            }
        }
        
        // Redirection pour éviter la soumission multiple du formulaire
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur : " . $e->getMessage();
    }
}

// Récupération des catégories
try {
    $stmt = $con->query("SELECT c.*, COUNT(a.IDARTICLE) as nombre_articles 
                         FROM categorie c 
                         LEFT JOIN article a ON c.IDCATEGORIE = a.IDCATEGORIE 
                         GROUP BY c.IDCATEGORIE 
                         ORDER BY c.NOMCATEGORIE");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Inclusion de l'en-tête
require_once('includes/header.php');
?>

<div class="container mt-4">
    <div class="row">
        <!-- Formulaire d'ajout/modification -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter une catégorie
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="" id="categorieForm">
                        <input type="hidden" name="id_categorie" id="id_categorie">
                        
                        <div class="mb-3">
                            <label for="nom_categorie" class="form-label">Nom de la catégorie</label>
                            <input type="text" class="form-control" id="nom_categorie" name="nom_categorie" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="ajouter_categorie" id="submitBtn" class="btn btn-primary">
                                <i class="fas fa-plus-circle me-1"></i>Ajouter
                            </button>
                            <button type="button" id="resetBtn" class="btn btn-secondary d-none">
                                <i class="fas fa-times me-1"></i>Annuler
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Liste des catégories -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i>Liste des catégories
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Description</th>
                                    <th>Articles</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $categorie): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($categorie['NOMCATEGORIE']); ?></td>
                                    <td><?php echo htmlspecialchars($categorie['DESCRIPTION']); ?></td>
                                    <td><?php echo $categorie['nombre_articles']; ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info edit-btn" 
                                                data-id="<?php echo $categorie['IDCATEGORIE']; ?>"
                                                data-nom="<?php echo htmlspecialchars($categorie['NOMCATEGORIE']); ?>"
                                                data-description="<?php echo htmlspecialchars($categorie['DESCRIPTION']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($categorie['nombre_articles'] == 0): ?>
                                        <form method="post" action="" style="display: inline;" 
                                              onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette catégorie ?');">
                                            <input type="hidden" name="id_categorie" value="<?php echo $categorie['IDCATEGORIE']; ?>">
                                            <button type="submit" name="supprimer_categorie" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('categorieForm');
    const submitBtn = document.getElementById('submitBtn');
    const resetBtn = document.getElementById('resetBtn');
    const editBtns = document.querySelectorAll('.edit-btn');
    
    // Fonction pour réinitialiser le formulaire
    function resetForm() {
        form.reset();
        form.elements['id_categorie'].value = '';
        submitBtn.innerHTML = '<i class="fas fa-plus-circle me-1"></i>Ajouter';
        submitBtn.name = 'ajouter_categorie';
        resetBtn.classList.add('d-none');
    }
    
    // Gestionnaire pour le bouton de réinitialisation
    resetBtn.addEventListener('click', resetForm);
    
    // Gestionnaire pour les boutons de modification
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nom = this.dataset.nom;
            const description = this.dataset.description;
            
            form.elements['id_categorie'].value = id;
            form.elements['nom_categorie'].value = nom;
            form.elements['description'].value = description;
            
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Modifier';
            submitBtn.name = 'modifier_categorie';
            resetBtn.classList.remove('d-none');
            
            // Scroll vers le formulaire sur mobile
            if (window.innerWidth < 768) {
                form.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
</script>

<?php
// Inclusion du pied de page
require_once('includes/footer.php');
?> 