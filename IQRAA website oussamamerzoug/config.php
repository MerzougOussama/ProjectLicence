<?php
session_start();

// Configuration de la base de données
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'bibliotheque_db';

// Connexion à la base de données
try {
    $conn = new mysqli($host, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Erreur de connexion : " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
} catch (Exception $e) {
    die("Erreur de base de données : " . $e->getMessage());
}

// Fonctions utilitaires
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isBibliothecaire() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'bibliothecaire';
}

function isAcheteur() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'acheteur';
}

function redirectTo($page) {
    header("Location: $page");
    exit();
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateOrderNumber($conn) {
    do {
        $order_number = 'CMD' . rand(10000, 99999);
        $check_query = "SELECT id FROM orders WHERE order_number = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $order_number);
        $stmt->execute();
        $stmt->store_result();
    } while ($stmt->num_rows > 0);

    return $order_number;
}


// Fonction pour obtenir le nombre d'articles dans le panier
function getCartCount() {
    global $conn;
    if (!isLoggedIn()) return 0;
    
    $query = "SELECT SUM(quantity) as total FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    return $result['total'] ?? 0;
}

// Constantes
define('CURRENCY', 'DA');
define('MAX_CART_QUANTITY', 10);
define('MAX_STOCK', 999);
?>
