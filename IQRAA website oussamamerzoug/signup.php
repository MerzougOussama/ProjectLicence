<?php 
include 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($role)) {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (!validateEmail($email)) {
        $error = 'Adresse email invalide.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!in_array($role, ['acheteur', 'bibliothecaire'])) {
        $error = 'Rôle invalide.';
    } else {
        // Vérifier si l'utilisateur existe déjà
        $check_query = "SELECT id FROM users WHERE username = ? OR email = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ss", $username, $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error = 'Un utilisateur avec ce nom ou cet email existe déjà.';
        } else {
            // Créer le nouvel utilisateur
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if ($insert_stmt->execute()) {
                $success = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
                // Réinitialiser le formulaire
                $_POST = array();
            } else {
                $error = 'Erreur lors de la création du compte.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Library</title>
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
                    <li><a href="login.php">Connexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="auth-main">
        <div class="auth-container">
            <div class="auth-form">
                <h2>Créer un compte</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" id="signupForm">
                    <div class="form-group">
                        <label for="username">Nom d'utilisateur *</label>
                        <input type="text" id="username" name="username" required 
                               value="<?php echo isset($_POST['username']) ? sanitizeInput($_POST['username']) : ''; ?>"
                               minlength="3" maxlength="50">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? sanitizeInput($_POST['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Mot de passe *</label>
                        <input type="password" id="password" name="password" required minlength="6">
                        <small>Au moins 6 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le mot de passe *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rôle *</label>
                        <select id="role" name="role" required>
                            <option value="">Choisir un rôle</option>
                            <option value="acheteur" <?php echo (isset($_POST['role']) && $_POST['role'] === 'acheteur') ? 'selected' : ''; ?>>
                                Acheteur - Je veux acheter des produits
                            </option>
                            <option value="bibliothecaire" <?php echo (isset($_POST['role']) && $_POST['role'] === 'bibliothecaire') ? 'selected' : ''; ?>>
                                Bibliothécaire - Je veux vendre des produits
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-full">S'inscrire</button>
                </form>
                
                <p class="auth-link">Déjà un compte ? <a href="login.php">Se connecter</a></p>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        document.getElementById('signupForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Les mots de passe ne correspondent pas.');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return false;
            }
        });
    </script>
</body>
</html>
