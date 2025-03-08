<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once('includes/functions.php');

if (!isLoggedIn() && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    redirect('login.php');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="theme-color" content="#2c3e50">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="format-detection" content="telephone=no">
    <meta name="description" content="IntimeManager - Système de gestion de stock">
    <meta name="keywords" content="gestion de stock, inventaire, vente, articles">
    
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - IntimeManager' : 'IntimeManager'; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/images/favicon.png">
    <link rel="apple-touch-icon" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/images/apple-touch-icon.png">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/css/fonts.css" rel="stylesheet">
    <link href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/css/style.css" rel="stylesheet">
    <link href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/css/custom.css" rel="stylesheet">
    
    <!-- Polyfill pour les navigateurs plus anciens -->
    <script src="https://polyfill.io/v3/polyfill.min.js?features=IntersectionObserver"></script>
    
    <?php if (isset($additionalStyles)): ?>
        <?php foreach ($additionalStyles as $style): ?>
            <link href="<?php echo $style; ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="<?php echo isset($bodyClass) ? $bodyClass : ''; ?>">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/index.php">
                <img src="<?php echo dirname($_SERVER['PHP_SELF']); ?>/images/intimelogo.png" alt="IntimeManager Logo">
                IntimeManager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : ''; ?>" 
                           href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/index.php">
                            <i class="fas fa-home me-1"></i>Accueil
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['ListeArticles.php', 'GestionCategories.php']) ? 'active' : ''; ?>" 
                           href="#" id="navbarDropdownArticles" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-box me-1"></i>Articles
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdownArticles">
                            <li>
                                <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'ListeArticles.php' ? 'active' : ''; ?>" 
                                   href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/ListeArticles.php">
                                    <i class="fas fa-list me-1"></i>Liste des Articles
                                </a>
                            </li>
                            <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                            <li>
                                <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) === 'GestionCategories.php' ? 'active' : ''; ?>" 
                                   href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/GestionCategories.php">
                                    <i class="fas fa-tags me-1"></i>Gestion des Catégories
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ListeVentes.php' ? 'active' : ''; ?>" 
                           href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/ListeVentes.php">
                            <i class="fas fa-shopping-cart me-1"></i>Ventes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'ListeFournisseurs.php' ? 'active' : ''; ?>" 
                           href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/ListeFournisseurs.php">
                            <i class="fas fa-truck me-1"></i>Fournisseurs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'Rapports.php' ? 'active' : ''; ?>" 
                           href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/Rapports.php">
                            <i class="fas fa-chart-bar me-1"></i>Rapports
                        </a>
                    </li>
                    <?php if ($_SESSION['ROLE'] === 'ADMIN'): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'GestionUtilisateurs.php' ? 'active' : ''; ?>" 
                           href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/GestionUtilisateurs.php">
                            <i class="fas fa-users me-1"></i>Utilisateurs
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo dirname($_SERVER['PHP_SELF']); ?>/logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Container principal -->
    <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo htmlspecialchars($_SESSION['success_message']);
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo htmlspecialchars($_SESSION['error_message']);
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 