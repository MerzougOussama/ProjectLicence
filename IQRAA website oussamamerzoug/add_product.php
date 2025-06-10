<?php 
include 'config.php';

if (!isLoggedIn() || !isBibliothecaire()) {
    redirectTo('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image_url = trim($_POST['image_url']);
    $stock = intval($_POST['stock']) ?: 1;
    
    if (empty($title) || empty($description) || $price <= 0 || $category_id <= 0) {
        $error = 'Veuillez remplir tous les champs obligatoires avec des valeurs valides.';
    } else {
        $query = "INSERT INTO products (title, description, price, category_id, bibliothecaire_id, image_url, stock) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdissi", $title, $description, $price, $category_id, $_SESSION['user_id'], $image_url, $stock);
        
        if ($stmt->execute()) {
            $success = 'Produit ajouté avec succès !';
            // Réinitialiser le formulaire
            $_POST = array();
        } else {
            $error = 'Erreur lors de l\'ajout du produit.';
        }
    }
}

// Récupérer les catégories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un produit - Library</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo"><a href="index.php">🖊IQRAA</a></h1>
                <ul class="nav-menu">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="products.php">Produits</a></li>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="auth-main">
        <div class="container">
            <div class="auth-container" style="max-width: 600px;">
                <div class="auth-form">
                    <h2>Ajouter un nouveau produit</h2>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-error"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" data-autosave="add_product">
                        <div class="form-group">
                            <label for="title">Titre du produit *</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4" required 
                                      style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="category_id">Catégorie *</label>
                            <select id="category_id" name="category_id" required>
                                <option value="">Choisir une catégorie</option>
                                <?php while ($category = $categories_result->fetch_assoc()): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="price">Prix (€) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0.01" required 
                                   value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="stock">Stock</label>
                            <input type="number" id="stock" name="stock" min="1" 
                                   value="<?php echo isset($_POST['stock']) ? htmlspecialchars($_POST['stock']) : '1'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="image_url">URL de l'image (optionnel)</label>
                            <input type="url" id="image_url" name="image_url" 
                                   value="<?php echo isset($_POST['image_url']) ? htmlspecialchars($_POST['image_url']) : ''; ?>"
                                   placeholder="https://exemple.com/image.jpg">
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-full">Ajouter le produit</button>
                    </form>
                    
                    <p class="auth-link"><a href="dashboard.php">← Retour au dashboard</a></p>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
