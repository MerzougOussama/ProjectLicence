<?php 
include 'config.php';

if (!isLoggedIn() || !isAcheteur()) {
    redirectTo('login.php');
}

$error = '';
$success = '';
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Traitement de l'ajout/modification d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = trim($_POST['comment']);
    
    if ($product_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
        $error = 'Veuillez remplir tous les champs avec des valeurs valides.';
    } else {
        // Vérifier que l'utilisateur a acheté ce produit
        $check_purchase = "SELECT id FROM purchases WHERE product_id = ? AND buyer_id = ?";
        $check_stmt = $conn->prepare($check_purchase);
        $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows === 0) {
            $error = 'Vous ne pouvez laisser un avis que pour les produits que vous avez achetés.';
        } else {
            // Vérifier si un avis existe déjà
            $check_review = "SELECT id FROM reviews WHERE product_id = ? AND buyer_id = ?";
            $check_review_stmt = $conn->prepare($check_review);
            $check_review_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
            $check_review_stmt->execute();
            $existing_review = $check_review_stmt->get_result();
            
            if ($existing_review->num_rows > 0) {
                // Mettre à jour l'avis existant
                $update_query = "UPDATE reviews SET rating = ?, comment = ? WHERE product_id = ? AND buyer_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("isii", $rating, $comment, $product_id, $_SESSION['user_id']);
                
                if ($update_stmt->execute()) {
                    $success = 'Votre avis a été mis à jour avec succès !';
                } else {
                    $error = 'Erreur lors de la mise à jour de l\'avis.';
                }
            } else {
                // Créer un nouvel avis
                $insert_query = "INSERT INTO reviews (product_id, buyer_id, rating, comment) VALUES (?, ?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param("iiis", $product_id, $_SESSION['user_id'], $rating, $comment);
                
                if ($insert_stmt->execute()) {
                    $success = 'Votre avis a été ajouté avec succès !';
                } else {
                    $error = 'Erreur lors de l\'ajout de l\'avis.';
                }
            }
        }
    }
}

// Récupérer les produits achetés par l'utilisateur
$purchased_products_query = "SELECT DISTINCT p.id, p.title, pu.purchase_date,
    r.id as review_id, r.rating, r.comment, r.created_at as review_date
FROM purchases pu
JOIN products p ON pu.product_id = p.id
LEFT JOIN reviews r ON p.id = r.product_id AND r.buyer_id = ?
WHERE pu.buyer_id = ?
ORDER BY pu.purchase_date DESC";

$products_stmt = $conn->prepare($purchased_products_query);
$products_stmt->bind_param("ii", $_SESSION['user_id'], $_SESSION['user_id']);
$products_stmt->execute();
$purchased_products = $products_stmt->get_result();

// Si un produit spécifique est demandé, récupérer ses détails
$selected_product = null;
if ($product_id > 0) {
    $product_query = "SELECT p.*, 
        r.rating as my_rating, r.comment as my_comment
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id AND r.buyer_id = ?
    WHERE p.id = ?";
    
    $product_stmt = $conn->prepare($product_query);
    $product_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    $product_stmt->execute();
    $product_result = $product_stmt->get_result();
    
    if ($product_result->num_rows > 0) {
        $selected_product = $product_result->fetch_assoc();
        
        // Vérifier que l'utilisateur a acheté ce produit
        $verify_purchase = "SELECT id FROM purchases WHERE product_id = ? AND buyer_id = ?";
        $verify_stmt = $conn->prepare($verify_purchase);
        $verify_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
        $verify_stmt->execute();
        
        if ($verify_stmt->get_result()->num_rows === 0) {
            $selected_product = null;
            $error = 'Vous ne pouvez laisser un avis que pour les produits que vous avez achetés.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Avis - Library</title>
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

    <main class="dashboard-main">
        <div class="container">
            <div class="dashboard-header">
                <h2>⭐ Mes Avis</h2>
                <p>Gérez vos avis sur les produits achetés</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if ($selected_product): ?>
                <!-- Formulaire d'avis pour un produit spécifique -->
                <div class="dashboard-section">
                    <h3><?php echo $selected_product['my_rating'] ? 'Modifier mon avis' : 'Laisser un avis'; ?></h3>
                    <div class="product-review-form">
                        <div class="product-info-mini">
                            <h4><?php echo htmlspecialchars($selected_product['title']); ?></h4>
                            <p><?php echo number_format($selected_product['price'], 2); ?> €</p>
                        </div>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            
                            <div class="form-group">
                                <label>Note *</label>
                                <div class="star-rating">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star-input <?php echo ($selected_product['my_rating'] && $i <= $selected_product['my_rating']) ? 'filled' : ''; ?>" 
                                              data-rating="<?php echo $i; ?>">⭐</span>
                                    <?php endfor; ?>
                                    <input type="hidden" name="rating" value="<?php echo $selected_product['my_rating'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="comment">Commentaire *</label>
                                <textarea id="comment" name="comment" rows="4" required 
                                          style="width: 100%; padding: 0.75rem; border: 1px solid #ddd; border-radius: 4px; font-family: inherit; resize: vertical;"><?php echo htmlspecialchars($selected_product['my_comment'] ?? ''); ?></textarea>
                            </div>
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" name="submit_review" class="btn btn-primary">
                                    <?php echo $selected_product['my_rating'] ? 'Mettre à jour' : 'Publier l\'avis'; ?>
                                </button>
                                <a href="reviews.php" class="btn btn-secondary">Annuler</a>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des produits achetés -->
            <div class="dashboard-section">
                <h3>Produits achetés</h3>
                <div class="table-container">
                    <table class="dashboard-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Date d'achat</th>
                                <th>Mon avis</th>
                                <th>Date de l'avis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $purchased_products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($product['title']); ?></strong>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($product['purchase_date'])); ?></td>
                                <td>
                                    <?php if ($product['review_id']): ?>
                                        <div class="rating">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <span class="star <?php echo $i <= $product['rating'] ? 'filled' : ''; ?>">⭐</span>
                                            <?php endfor; ?>
                                            <span style="margin-left: 5px;"><?php echo $product['rating']; ?>/5</span>
                                        </div>
                                        <p style="margin-top: 0.5rem; font-size: 0.9rem; color: #555;">
                                            <?php echo htmlspecialchars(substr($product['comment'], 0, 100)); ?>
                                            <?php if (strlen($product['comment']) > 100) echo '...'; ?>
                                        </p>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-style: italic;">Aucun avis</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['review_date']): ?>
                                        <?php echo date('d/m/Y', strtotime($product['review_date'])); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="reviews.php?product_id=<?php echo $product['id']; ?>" class="btn btn-small">
                                        <?php echo $product['review_id'] ? 'Modifier' : 'Ajouter un avis'; ?>
                                    </a>
                                    <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-small btn-secondary">Voir produit</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="dashboard.php" class="btn btn-secondary">← Retour au dashboard</a>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        // Gestion des étoiles pour la notation
        document.addEventListener('DOMContentLoaded', function() {
            const starInputs = document.querySelectorAll('.star-input');
            const ratingInput = document.querySelector('input[name="rating"]');
            
            if (starInputs.length > 0 && ratingInput) {
                starInputs.forEach((star, index) => {
                    star.addEventListener('click', function() {
                        const rating = index + 1;
                        ratingInput.value = rating;
                        
                        // Mettre à jour l'affichage
                        starInputs.forEach((s, i) => {
                            if (i < rating) {
                                s.classList.add('filled');
                            } else {
                                s.classList.remove('filled');
                            }
                        });
                    });
                    
                    star.addEventListener('mouseover', function() {
                        const rating = index + 1;
                        starInputs.forEach((s, i) => {
                            if (i < rating) {
                                s.style.color = '#f39c12';
                            } else {
                                s.style.color = '#ddd';
                            }
                        });
                    });
                });
                
                // Restaurer l'affichage au survol
                document.querySelector('.star-rating').addEventListener('mouseleave', function() {
                    const currentRating = parseInt(ratingInput.value) || 0;
                    starInputs.forEach((s, i) => {
                        if (i < currentRating) {
                            s.style.color = '#f39c12';
                            s.classList.add('filled');
                        } else {
                            s.style.color = '#ddd';
                            s.classList.remove('filled');
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
