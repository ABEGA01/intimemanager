<?php
session_start();
require_once('Connexion.php');

// Vérification si l'utilisateur est déjà connecté
if (isset($_SESSION['IDUTILISATEUR'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if ($email && $password) {
        try {
            $stmt = $con->prepare("SELECT * FROM utilisateur WHERE EMAIL = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['MOTDEPASSE'])) {
                // Connexion réussie
                $_SESSION['IDUTILISATEUR'] = $user['IDUTILISATEUR'];
                $_SESSION['NOMUTILISATEUR'] = $user['NOMUTILISATEUR'];
                $_SESSION['ROLE'] = $user['ROLE'];

                // Mise à jour de la dernière connexion
                $stmt = $con->prepare("UPDATE utilisateur SET DERNIERECONNEXION = NOW() WHERE IDUTILISATEUR = :id");
                $stmt->execute(['id' => $user['IDUTILISATEUR']]);

                // Journalisation de la connexion
                $stmt = $con->prepare("INSERT INTO journal_action (IDUTILISATEUR, ACTION, DETAILS) VALUES (:id, 'CONNEXION', 'Connexion réussie')");
                $stmt->execute(['id' => $user['IDUTILISATEUR']]);

                header('Location: index.php');
                exit();
            } else {
                $error = 'Email ou mot de passe incorrect';
            }
        } catch (Exception $e) {
            $error = 'Erreur de connexion : ' . $e->getMessage();
        }
    } else {
        $error = 'Veuillez remplir tous les champs';
    }
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
    
    <title>Connexion - IntimeManager</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="images/favicon.png">
    <link rel="apple-touch-icon" href="images/apple-touch-icon.png">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #3498db;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 1rem;
            margin: 1rem;
        }

        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            padding: 2rem;
            width: 100%;
            -webkit-transform: translate3d(0,0,0);
            transform: translate3d(0,0,0);
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            -webkit-perspective: 1000;
            perspective: 1000;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header img {
            width: 120px;
            height: 120px;
            margin-bottom: 1.5rem;
            -webkit-transform: translate3d(0,0,0);
            transform: translate3d(0,0,0);
            object-fit: contain;
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
            filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        }

        .login-header h2 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
            font-size: 1.8rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-control {
            border-radius: 8px;
            padding: 0.8rem 1rem;
            font-size: 1rem;
            border: 1px solid #dee2e6;
            -webkit-transition: all 0.2s ease;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
            outline: none;
        }

        .input-group-text {
            background-color: var(--light-color);
            border: 1px solid #dee2e6;
            color: var(--primary-color);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
            width: 100%;
            padding: 0.8rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 8px;
            -webkit-transition: all 0.2s ease;
            transition: all 0.2s ease;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            border: none;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background-color: var(--danger-color);
            color: white;
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
            -webkit-animation: fadeIn 0.5s ease-out;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .login-container {
                padding: 0.5rem;
                margin: 0.5rem;
            }

            .login-card {
                padding: 1.5rem;
            }

            .login-header img {
                width: 100px;
                height: 100px;
            }

            .login-header h2 {
                font-size: 1.5rem;
            }

            .form-control, .btn {
                font-size: 16px; /* Prevent zoom on iOS */
            }
        }

        /* Cross-browser compatibility */
        input[type="email"],
        input[type="password"] {
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        /* Print styles */
        @media print {
            body {
                background: none;
            }

            .login-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
                    <div class="login-header">
                <img src="images/intimelogo.png" alt="IntimeManager Logo">
                <h2>IntimeManager</h2>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

            <form method="post" action="" autocomplete="off">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-envelope"></i>
                                </span>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               required 
                               autocomplete="email"
                               placeholder="Votre email">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-lock"></i>
                                </span>
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required 
                               autocomplete="current-password"
                               placeholder="Votre mot de passe">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                        </button>
                    </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent form resubmission on refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Prevent zoom on iOS
        document.addEventListener('gesturestart', function(e) {
            e.preventDefault();
        });

        // Add touch feedback
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.98)';
            });
            button.addEventListener('touchend', function() {
                this.style.transform = '';
            });
        });
    </script>
</body>
</html> 