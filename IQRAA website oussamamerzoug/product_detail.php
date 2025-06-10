<?php 
include 'config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectTo('products.php');
}

$product_id = (int)$_GET['id'];

// Récupérer les détails du produit
$query = "SELECT p.*, c.name as category_name, u.username as bibliothecaire_name,
          (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
          (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
          FROM products p 
          JOIN categories c ON p.category_id = c.id 
          JOIN users u ON p.bibliothecaire_id = u.id 
          WHERE p.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirectTo('products.php');
}

$product = $result->fetch_assoc();

// Messages
$cart_message = '';
$cart_status = '';
$review_message = '';
$review_status = '';

// Traitement de l'ajout au panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        redirectTo('login.php');
    }
    
    if (!isAcheteur()) {
        $cart_message = 'Seuls les acheteurs peuvent ajouter des produits au panier.';
        $cart_status = 'error';
    } else {
        $quantity = intval($_POST['quantity']) ?: 1;
        
        // Vérifications
        if ($product['status'] !== 'available') {
            $cart_message = 'Ce produit n\'est plus disponible.';
            $cart_status = 'error';
        } elseif ($product['bibliothecaire_id'] == $_SESSION['user_id']) {
            $cart_message = 'Vous ne pouvez pas ajouter votre propre produit au panier.';
            $cart_status = 'error';
        } elseif ($product['stock'] < $quantity) {
            $cart_message = 'Stock insuffisant pour cette quantité.';
            $cart_status = 'error';
        } else {
            // Vérifier si le produit est déjà dans le panier
            $check_cart = "SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
            $check_stmt = $conn->prepare($check_cart);
            $check_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Mettre à jour la quantité
                $existing_item = $check_result->fetch_assoc();
                $new_quantity = min($existing_item['quantity'] + $quantity, $product['stock'], MAX_CART_QUANTITY);
                
                $update_cart = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
                $update_stmt = $conn->prepare($update_cart);
                $update_stmt->bind_param("iii", $new_quantity, $_SESSION['user_id'], $product_id);
                
                if ($update_stmt->execute()) {
                    $cart_message = 'Quantité mise à jour dans votre panier !';
                    $cart_status = 'success';
                } else {
                    $cart_message = 'Erreur lors de la mise à jour du panier.';
                    $cart_status = 'error';
                }
            } else {
                // Ajouter au panier
                $add_cart = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)";
                $add_stmt = $conn->prepare($add_cart);
                $add_stmt->bind_param("iii", $_SESSION['user_id'], $product_id, $quantity);
                
                if ($add_stmt->execute()) {
                    $cart_message = 'Produit ajouté à votre panier !';
                    $cart_status = 'success';
                } else {
                    $cart_message = 'Erreur lors de l\'ajout au panier.';
                    $cart_status = 'error';
                }
            }
        }
    }
}

// Vérifier si le produit est déjà dans le panier
$in_cart = false;
$cart_quantity = 0;
if (isLoggedIn()) {
    $cart_check = "SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?";
    $cart_check_stmt = $conn->prepare($cart_check);
    $cart_check_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    $cart_check_stmt->execute();
    $cart_check_result = $cart_check_stmt->get_result();
    
    if ($cart_check_result->num_rows > 0) {
        $in_cart = true;
        $cart_quantity = $cart_check_result->fetch_assoc()['quantity'];
    }
}

// Vérifier si l'utilisateur a acheté ce produit
$has_purchased = false;
if (isLoggedIn() && isAcheteur()) {
    $purchase_check = "SELECT COUNT(*) as count FROM order_items oi 
                      JOIN orders o ON oi.order_id = o.id 
                      WHERE o.user_id = ? AND oi.product_id = ? AND o.status != 'cancelled'";
    $purchase_stmt = $conn->prepare($purchase_check);
    $purchase_stmt->bind_param("ii", $_SESSION['user_id'], $product_id);
    $purchase_stmt->execute();
    $purchase_result = $purchase_stmt->get_result();
    $has_purchased = $purchase_result->fetch_assoc()['count'] > 0;
}

// Traitement de l'ajout d'avis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    if (!isLoggedIn() || !isAcheteur()) {
        $review_message = 'Vous devez être connecté en tant qu\'acheteur pour laisser un avis.';
        $review_status = 'error';
    } elseif (!$has_purchased) {
        $review_message = 'Vous devez avoir acheté ce produit pour laisser un avis.';
        $review_status = 'error';
    } else {
        $rating = intval($_POST['rating']);
        $comment = sanitizeInput($_POST['comment']);
        
        if ($rating < 1 || $rating > 5) {
            $review_message = 'Veuillez sélectionner une note valide (1-5).';
            $review_status = 'error';
        } elseif (empty($comment)) {
            $review_message = 'Veuillez entrer un commentaire.';
            $review_status = 'error';
        } else {
            // Vérifier si l'utilisateur a déjà laissé un avis
            $check_review = "SELECT id FROM reviews WHERE product_id = ? AND buyer_id = ?";
            $check_review_stmt = $conn->prepare($check_review);
            $check_review_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
            $check_review_stmt->execute();
            
            if ($check_review_stmt->get_result()->num_rows > 0) {
                $review_message = 'Vous avez déjà laissé un avis pour ce produit.';
                $review_status = 'error';
            } else {
                $add_review_query = "INSERT INTO reviews (product_id, buyer_id, rating, comment) VALUES (?, ?, ?, ?)";
                $add_review_stmt = $conn->prepare($add_review_query);
                $add_review_stmt->bind_param("iiis", $product_id, $_SESSION['user_id'], $rating, $comment);
                
                if ($add_review_stmt->execute()) {
                    $review_message = 'Votre avis a été ajouté avec succès !';
                    $review_status = 'success';
                } else {
                    $review_message = 'Erreur lors de l\'ajout de votre avis.';
                    $review_status = 'error';
                }
            }
        }
    }
}

// Récupérer les avis
$reviews_query = "SELECT r.*, u.username FROM reviews r 
                  JOIN users u ON r.buyer_id = u.id 
                  WHERE r.product_id = ? 
                  ORDER BY r.created_at DESC";
$reviews_stmt = $conn->prepare($reviews_query);
$reviews_stmt->bind_param("i", $product_id);
$reviews_stmt->execute();
$reviews_result = $reviews_stmt->get_result();

// Produits similaires
$similar_query = "SELECT p.*, c.name as category_name FROM products p 
                  JOIN categories c ON p.category_id = c.id 
                  WHERE p.category_id = ? AND p.id != ? AND p.status = 'available' AND p.stock > 0
                  ORDER BY RAND() LIMIT 4";
$similar_stmt = $conn->prepare($similar_query);
$similar_stmt->bind_param("ii", $product['category_id'], $product_id);
$similar_stmt->execute();
$similar_result = $similar_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo sanitizeInput($product['title']); ?> - Library</title>
    <link rel="stylesheet" href="style.css">
    <meta name="description" content="<?php echo sanitizeInput(substr($product['description'], 0, 160)); ?>">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo"><a href="index.php">🖊IQRAA</a></h1>
                <ul class="nav-menu">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="products.php">Produits</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <?php if (isAcheteur()): ?>
                            <li><a href="cart.php">Panier <?php $count = getCartCount(); echo $count > 0 ? "($count)" : ''; ?></a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Connexion</a></li>
                        <li><a href="signup.php">Inscription</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="product-detail-main">
        <div class="container">
            <div class="breadcrumb">
                <a href="products.php">← Retour aux produits</a>
                <span> / </span>
                <a href="products.php?category=<?php echo $product['category_id']; ?>"><?php echo sanitizeInput($product['category_name']); ?></a>
                <span> / </span>
                <span><?php echo sanitizeInput($product['title']); ?></span>
            </div>

            <?php if ($cart_message): ?>
                <div class="alert alert-<?php echo $cart_status; ?>">
                    <?php echo $cart_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($review_message): ?>
                <div class="alert alert-<?php echo $review_status; ?>">
                    <?php echo $review_message; ?>
                </div>
            <?php endif; ?>

            <div class="product-detail">
                <div class="product-detail-image">
                    <?php if ($product['image_url']): ?>
                        <img src="<?php echo sanitizeInput($product['image_url']); ?>" 
                             alt="<?php echo sanitizeInput($product['title']); ?>"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="product-placeholder-large" style="display: none;">📦</div>
                    <?php else: ?>
                        <div class="product-placeholder-large">📦</div>
                    <?php endif; ?>
                </div>

                <div class="product-detail-info">
                    <h1><?php echo sanitizeInput($product['title']); ?></h1>
                    
                    <div class="product-meta">
                        <span class="category">📂 <?php echo sanitizeInput($product['category_name']); ?></span>
                        <span class="seller">👤 <?php echo sanitizeInput($product['bibliothecaire_name']); ?></span>
                        <span class="date">📅 <?php echo date('d/m/Y', strtotime($product['created_at'])); ?></span>
                    </div>

                    <?php if ($product['review_count'] > 0): ?>
                        <div class="product-rating">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?php echo $i <= round($product['avg_rating']) ? 'filled' : ''; ?>">⭐</span>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-text">
                                <?php echo number_format($product['avg_rating'], 1); ?>/5 
                                (<?php echo $product['review_count']; ?> avis)
                            </span>
                        </div>
                    <?php endif; ?>

                    <div class="product-description">
                        <h3>Description</h3>
                        <p><?php echo nl2br(sanitizeInput($product['description'])); ?></p>
                    </div>

                    <div class="product-purchase">
                        <div class="price-section">
                            <span class="price"><?php echo number_format($product['price'], 2); ?> <?php echo CURRENCY; ?></span>
                            <div class="product-status">
                                <span class="status <?php echo $product['status']; ?>">
                                    <?php echo $product['status'] === 'available' ? '✅ Disponible' : '❌ Vendu'; ?>
                                </span>
                                <?php if ($product['status'] === 'available'): ?>
                                    <span class="stock-info">
                                        📦 Stock: <?php echo $product['stock']; ?> 
                                        <?php echo $product['stock'] > 1 ? 'unités' : 'unité'; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if (isLoggedIn() && isAcheteur() && $product['status'] === 'available' && $product['stock'] > 0): ?>
                            <?php if ($product['bibliothecaire_id'] == $_SESSION['user_id']): ?>
                                <div class="purchase-note">
                                    <p>📝 Ceci est votre propre produit.</p>
                                </div>
                            <?php else: ?>
                                <?php if ($in_cart): ?>
                                    <div class="cart-status">
                                        <p>✅ Déjà dans votre panier (Quantité: <?php echo $cart_quantity; ?>)</p>
                                        <div class="cart-actions">
                                            <a href="cart.php" class="btn btn-secondary">Voir le panier</a>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <form method="POST" action="" class="add-to-cart-form">
                                    <div class="quantity-selector">
                                        <label for="quantity">Quantité:</label>
                                        <select name="quantity" id="quantity">
                                            <?php for ($i = 1; $i <= min(MAX_CART_QUANTITY, $product['stock']); $i++): ?>
                                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-large">
                                        🛒 <?php echo $in_cart ? 'Mettre à jour le panier' : 'Ajouter au panier'; ?>
                                    </button>
                                </form>
                                
                                <div class="purchase-info">
                                    <small>💡 Ajoutez au panier puis finalisez votre commande avec livraison Yalidine</small>
                                </div>
                            <?php endif; ?>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="login.php" class="btn btn-primary btn-large">Se connecter pour commander</a>
                        <?php elseif (!isAcheteur()): ?>
                            <p class="purchase-note">Seuls les acheteurs peuvent commander des produits.</p>
                        <?php elseif ($product['stock'] <= 0): ?>
                            <p class="purchase-note">❌ Produit en rupture de stock.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Section des avis -->
            <div class="reviews-section">
                <h3>Avis des clients</h3>

                <?php if (isLoggedIn() && isAcheteur() && $has_purchased): ?>
                    <?php
                    // Vérifier si l'utilisateur a déjà laissé un avis
                    $user_review_check = "SELECT id FROM reviews WHERE product_id = ? AND buyer_id = ?";
                    $user_review_stmt = $conn->prepare($user_review_check);
                    $user_review_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
                    $user_review_stmt->execute();
                    $user_has_reviewed = $user_review_stmt->get_result()->num_rows > 0;
                    ?>
                    
                    <?php if (!$user_has_reviewed): ?>
                        <div class="add-review-form">
                            <h4>Laissez votre avis</h4>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="rating">Note *</label>
                                    <div class="star-rating">
                                        <?php for ($i = 5; $i >= 1; $i--): ?>
                                            <input type="radio" name="rating" value="<?php echo $i; ?>" id="star<?php echo $i; ?>" required>
                                            <label for="star<?php echo $i; ?>">⭐</label>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label for="comment">Commentaire *</label>
                                    <textarea name="comment" id="comment" rows="4" required 
                                              placeholder="Partagez votre expérience avec ce produit..."></textarea>
                                </div>
                                <button type="submit" name="add_review" class="btn btn-primary">Publier l'avis</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($reviews_result->num_rows > 0): ?>
                    <div class="reviews-list">
                        <?php while ($review = $reviews_result->fetch_assoc()): ?>
                            <div class="review-item">
                                <div class="review-header">
                                    <span class="reviewer-name"><?php echo sanitizeInput($review['username']); ?></span>
                                    <div class="rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>">⭐</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="review-date"><?php echo date('d/m/Y', strtotime($review['created_at'])); ?></span>
                                </div>
                                <div class="review-comment">
                                    <?php echo nl2br(sanitizeInput($review['comment'])); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="no-reviews">Aucun avis pour ce produit.</p>
                <?php endif; ?>
            </div>

            <!-- Produits similaires -->
            <?php if ($similar_result->num_rows > 0): ?>
                <div class="similar-products">
                    <h3>Produits similaires</h3>
                    <div class="products-grid">
                        <?php while ($similar = $similar_result->fetch_assoc()): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php if ($similar['image_url']): ?>
                                        <img src="<?php echo sanitizeInput($similar['image_url']); ?>" 
                                             alt="<?php echo sanitizeInput($similar['title']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="product-placeholder" style="display: none;">📦</div>
                                    <?php else: ?>
                                        <div class="product-placeholder">📦</div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h4><?php echo sanitizeInput($similar['title']); ?></h4>
                                    <p class="category"><?php echo sanitizeInput($similar['category_name']); ?></p>
                                    <p class="price"><?php echo number_format($similar['price'], 2); ?> <?php echo CURRENCY; ?></p>
                                    <a href="product_detail.php?id=<?php echo $similar['id']; ?>" class="btn btn-primary">Voir détails</a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
