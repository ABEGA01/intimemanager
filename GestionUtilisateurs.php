<?php
session_start();
require_once('Connexion.php');

// Vérification de la connexion et des droits d'administration
if (!isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: login.php');
    exit();
}

// Vérification des droits d'administration
try {
    $stmt = $con->prepare("SELECT ROLE FROM utilisateur WHERE IDUTILISATEUR = :id");
    $stmt->execute(['id' => $_SESSION['IDUTILISATEUR']]);
    $user_role = $stmt->fetchColumn();

    if ($user_role !== 'ADMIN') {
        header('Location: index.php');
        exit();
    }
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'add') {
            $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $password = $_POST['password'];
            $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

            if (!$nom || !$email || !$password || !$role) {
                throw new Exception('Veuillez remplir tous les champs obligatoires');
            }

            // Vérification si l'email existe déjà
            $stmt = $con->prepare("SELECT COUNT(*) FROM utilisateur WHERE EMAIL = :email");
            $stmt->execute(['email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cet email est déjà utilisé');
            }

            // Insertion du nouvel utilisateur
            $stmt = $con->prepare("INSERT INTO utilisateur (NOMUTILISATEUR, EMAIL, MOTDEPASSE, ROLE) 
                                 VALUES (:nom, :email, :password, :role)");
            $stmt->execute([
                'nom' => $nom,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role
            ]);

            // Journalisation de l'action
            $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                                 VALUES (:id, 'AJOUT_UTILISATEUR', 'Ajout de l\'utilisateur : ' || :nom)");
            $stmt->execute([
                'id' => $_SESSION['IDUTILISATEUR'],
                'nom' => $nom
            ]);

            $_SESSION['success_message'] = 'Utilisateur ajouté avec succès';

        } elseif ($_POST['action'] === 'update') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            $role = filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING);

            if (!$id || !$nom || !$email || !$role) {
                throw new Exception('Veuillez remplir tous les champs obligatoires');
            }

            // Vérification si l'email existe déjà (sauf pour l'utilisateur en cours de modification)
            $stmt = $con->prepare("SELECT COUNT(*) FROM utilisateur WHERE EMAIL = :email AND IDUTILISATEUR != :id");
            $stmt->execute(['email' => $email, 'id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Cet email est déjà utilisé');
            }

            // Mise à jour de l'utilisateur
            $sql = "UPDATE utilisateur SET NOMUTILISATEUR = :nom, EMAIL = :email, ROLE = :role";
            $params = ['nom' => $nom, 'email' => $email, 'role' => $role, 'id' => $id];

            // Si un nouveau mot de passe est fourni
            if (!empty($_POST['password'])) {
                $sql .= ", MOTDEPASSE = :password";
                $params['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE IDUTILISATEUR = :id";
            $stmt = $con->prepare($sql);
            $stmt->execute($params);

            // Journalisation de l'action
            $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                                 VALUES (:id, 'MODIFICATION_UTILISATEUR', 'Modification de l\'utilisateur : ' || :nom)");
            $stmt->execute([
                'id' => $_SESSION['IDUTILISATEUR'],
                'nom' => $nom
            ]);

            $_SESSION['success_message'] = 'Utilisateur modifié avec succès';

        } elseif ($_POST['action'] === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (!$id) {
                throw new Exception('ID utilisateur invalide');
            }

            // Vérification que l'utilisateur n'est pas l'administrateur actuel
            if ($id == $_SESSION['IDUTILISATEUR']) {
                throw new Exception('Vous ne pouvez pas supprimer votre propre compte');
            }

            // Récupération du nom de l'utilisateur pour le journal
            $stmt = $con->prepare("SELECT NOMUTILISATEUR FROM utilisateur WHERE IDUTILISATEUR = :id");
            $stmt->execute(['id' => $id]);
            $nom = $stmt->fetchColumn();

            // Suppression de l'utilisateur
            $stmt = $con->prepare("DELETE FROM utilisateur WHERE IDUTILISATEUR = :id");
            $stmt->execute(['id' => $id]);

            // Journalisation de l'action
            $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) 
                                 VALUES (:id, 'SUPPRESSION_UTILISATEUR', 'Suppression de l\'utilisateur : ' || :nom)");
            $stmt->execute([
                'id' => $_SESSION['IDUTILISATEUR'],
                'nom' => $nom
            ]);

            $_SESSION['success_message'] = 'Utilisateur supprimé avec succès';
        }

        header('Location: GestionUtilisateurs.php');
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération de la liste des utilisateurs
try {
    $stmt = $con->query("SELECT * FROM utilisateur ORDER BY NOMUTILISATEUR");
    $utilisateurs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die('Erreur : ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Gestion de Stock</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        .table th {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
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
                    <li class="nav-item">
                        <a class="nav-link active" href="GestionUtilisateurs.php">
                            <i class="fas fa-users me-1"></i>Utilisateurs
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
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-users me-2"></i>Gestion des Utilisateurs
                </h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-1"></i>Nouvel Utilisateur
                </button>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php 
                        echo htmlspecialchars($_SESSION['success_message']);
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Date de création</th>
                                <th>Dernière connexion</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $utilisateur): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($utilisateur['NOMUTILISATEUR']); ?></td>
                                <td><?php echo htmlspecialchars($utilisateur['EMAIL']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $utilisateur['ROLE'] === 'ADMIN' ? 'danger' : 'primary'; ?>">
                                        <?php echo $utilisateur['ROLE']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($utilisateur['DATECREATION'])); ?></td>
                                <td>
                                    <?php 
                                    echo $utilisateur['DERNIERECONNEXION'] 
                                        ? date('d/m/Y H:i', strtotime($utilisateur['DERNIERECONNEXION']))
                                        : 'Jamais';
                                    ?>
                                </td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-primary"
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($utilisateur)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($utilisateur['IDUTILISATEUR'] != $_SESSION['IDUTILISATEUR']): ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteUser(<?php echo $utilisateur['IDUTILISATEUR']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
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

    <!-- Modal Ajout Utilisateur -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Rôle *</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="EMPLOYE">Employé</option>
                                <option value="ADMIN">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Modification Utilisateur -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier l'Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_nom" class="form-label">Nom *</label>
                            <input type="text" class="form-control" id="edit_nom" name="nom" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email *</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_password" class="form-label">Nouveau mot de passe (laisser vide pour ne pas modifier)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>

                        <div class="mb-3">
                            <label for="edit_role" class="form-label">Rôle *</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <option value="EMPLOYE">Employé</option>
                                <option value="ADMIN">Administrateur</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Suppression Utilisateur -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmer la Suppression</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Êtes-vous sûr de vouloir supprimer cet utilisateur ?</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-danger">Supprimer</button>
                    </form>
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
        function editUser(user) {
            document.getElementById('edit_id').value = user.IDUTILISATEUR;
            document.getElementById('edit_nom').value = user.NOMUTILISATEUR;
            document.getElementById('edit_email').value = user.EMAIL;
            document.getElementById('edit_role').value = user.ROLE;
            
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        }

        function deleteUser(id) {
            document.getElementById('delete_id').value = id;
            new bootstrap.Modal(document.getElementById('deleteUserModal')).show();
        }
    </script>
</body>
</html> 