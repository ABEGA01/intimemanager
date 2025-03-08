<?php
/**
 * Retourne le chemin de base de l'application
 */
function getBasePath() {
    $scriptDir = dirname($_SERVER['PHP_SELF']);
    return rtrim($scriptDir, '/');
}

/**
 * Retourne le chemin complet d'un fichier
 */
function getAssetPath($path) {
    return getBasePath() . '/' . ltrim($path, '/');
}

/**
 * Retourne le chemin complet d'un fichier CSS
 */
function getCssPath($file) {
    return getAssetPath('css/' . $file);
}

/**
 * Retourne le chemin complet d'un fichier JavaScript
 */
function getJsPath($file) {
    return getAssetPath('js/' . $file);
}

/**
 * Retourne le chemin complet d'une image
 */
function getImagePath($file) {
    return getAssetPath('images/' . $file);
}

/**
 * Retourne le chemin complet d'une page
 */
function getPagePath($page) {
    return getBasePath() . '/' . $page;
}

/**
 * Vérifie si la page courante est la page active
 */
function isActivePage($page) {
    return basename($_SERVER['PHP_SELF']) === $page;
}

/**
 * Retourne la classe active si la page courante correspond
 */
function getActiveClass($page) {
    return isActivePage($page) ? ' active' : '';
}

/**
 * Échappe les caractères HTML
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formate un nombre en monnaie
 */
function formatMoney($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Formate une date
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

/**
 * Vérifie si l'utilisateur est admin
 */
function isAdmin() {
    return isset($_SESSION['ROLE']) && $_SESSION['ROLE'] === 'ADMIN';
}

/**
 * Vérifie si l'utilisateur est connecté
 */
function isLoggedIn() {
    return isset($_SESSION['IDUTILISATEUR']);
}

/**
 * Redirige vers une page
 */
function redirect($page) {
    header('Location: ' . getPagePath($page));
    exit();
}

/**
 * Affiche un message d'erreur
 */
function showError($message) {
    $_SESSION['error_message'] = $message;
}

/**
 * Affiche un message de succès
 */
function showSuccess($message) {
    $_SESSION['success_message'] = $message;
}

/**
 * Récupère et supprime un message flash
 */
function getFlashMessage($type) {
    if (isset($_SESSION[$type . '_message'])) {
        $message = $_SESSION[$type . '_message'];
        unset($_SESSION[$type . '_message']);
        return $message;
    }
    return null;
} 