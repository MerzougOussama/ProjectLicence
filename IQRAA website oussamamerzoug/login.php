<?php 
include 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $query = "SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirection selon le rôle
                if ($user['role'] === 'bibliothecaire') {
                    redirectTo('dashboard.php');
                } else {
                    redirectTo('products.php');
                }
            } else {
                $error = 'Mot de passe incorrect.';
            }
        } else {
            $error = 'Utilisateur non trouvé.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Library</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo"><a href="index.php">🖊IQRAA</a></h1>
                <ul class="nav-menu">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="products.php">Produits</a></li>
                    <li><a href="signup.php">Inscription</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-form">
                <h2>Se connecter</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur ou Email *</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? sanitizeInput($_POST['username']) : ''; ?>"
                               placeholder="Votre nom d'utilisateur ou email">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" required
                               placeholder="Votre mot de passe">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">Se connecter</button>
                </form>
                
                <div class="auth-links">
                    <p class="auth-link">Pas encore de compte ? <a href="signup.php">S'inscrire</a></p>
                    <div class="demo-accounts">
                        <h4>Comptes de démonstration :</h4>
                        <p><strong>Admin :</strong> admin / password</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
