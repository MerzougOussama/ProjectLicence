<?php 
include 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php');
}

if (!isAcheteur()) {
    redirectTo('dashboard.php');
}

$error = '';
$success = '';

// Traitement de suppression d'un article du panier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $item_id = intval($_POST['item_id']);
    
    $delete_query = "DELETE FROM cart_items WHERE id = ? AND user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("ii", $item_id, $_SESSION['user_id']);
    
    if ($delete_stmt->execute()) {
        $success = 'Article supprimé du panier.';
    } else {
        $error = 'Erreur lors de la suppression.';
    }
}

// Traitement de mise à jour de la quantité
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0 && $quantity <= MAX_CART_QUANTITY) {
        $update_query = "UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("iii", $quantity, $item_id, $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            $success = 'Quantité mise à jour.';
        } else {
            $error = 'Erreur lors de la mise à jour.';
        }
    } else {
        $error = 'Quantité invalide (1-' . MAX_CART_QUANTITY . ').';
    }
}

// Traitement de la commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $delivery_type = $_POST['delivery_type'];
    $delivery_address = sanitizeInput($_POST['delivery_address']);
    $delivery_phone = sanitizeInput($_POST['delivery_phone']);
    $delivery_wilaya = intval($_POST['delivery_wilaya']);
    $delivery_commune = sanitizeInput($_POST['delivery_commune']);
    $payment_method = $_POST['payment_method'];
    $notes = sanitizeInput($_POST['notes']);
    
    // Validation
    if (empty($delivery_type) || empty($delivery_address) || empty($delivery_phone) || 
        $delivery_wilaya <= 0 || empty($delivery_commune)) {
        $error = 'Veuillez remplir tous les champs obligatoires.';
    } elseif (!preg_match('/^[0-9+\-\s()]+$/', $delivery_phone)) {
        $error = 'Numéro de téléphone invalide.';
    } else {
        $conn->begin_transaction();
        
        try {
            // Récupérer les articles du panier
            $cart_query = "SELECT ci.*, p.title, p.price, p.stock, p.status 
                          FROM cart_items ci
                          JOIN products p ON ci.product_id = p.id
                          WHERE ci.user_id = ? AND p.status = 'available'";
            $cart_stmt = $conn->prepare($cart_query);
            $cart_stmt->bind_param("i", $_SESSION['user_id']);
            $cart_stmt->execute();
            $cart_result = $cart_stmt->get_result();
            
            if ($cart_result->num_rows == 0) {
                throw new Exception('Aucun article disponible dans votre panier.');
            }
            
            // Calculer le total
            $total_amount = 0;
            $order_items = [];
            
            while ($item = $cart_result->fetch_assoc()) {
                if ($item['quantity'] > $item['stock']) {
                    throw new Exception("Stock insuffisant pour {$item['title']}.");
                }
                
                $item_total = $item['price'] * $item['quantity'];
                $total_amount += $item_total;
                $order_items[] = $item;
            }
            
            // Ajouter les frais de livraison
            $delivery_query = "SELECT delivery_price FROM wilayas WHERE id = ?";
            $delivery_stmt = $conn->prepare($delivery_query);
            $delivery_stmt->bind_param("i", $delivery_wilaya);
            $delivery_stmt->execute();
            $delivery_result = $delivery_stmt->get_result();
            
            $delivery_price = 500; // Prix par défaut
            if ($delivery_result->num_rows > 0) {
                $delivery_price = $delivery_result->fetch_assoc()['delivery_price'];
            }
            $total_amount += $delivery_price;
            
            // Générer un numéro de commande
            $order_number = generateOrderNumber();
            
            // Créer la commande
            $order_query = "INSERT INTO orders (user_id, order_number, total_amount, delivery_type, 
                           delivery_address, delivery_phone, delivery_wilaya, delivery_commune, 
                           payment_method, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $order_stmt = $conn->prepare($order_query);
            $order_stmt->bind_param("isdsssssss", $_SESSION['user_id'], $order_number, $total_amount, 
                                   $delivery_type, $delivery_address, $delivery_phone, 
                                   $delivery_wilaya, $delivery_commune, $payment_method, $notes);
            
            if (!$order_stmt->execute()) {
                throw new Exception('Erreur lors de la création de la commande.');
            }
            
            $order_id = $conn->insert_id;
            
            // Ajouter les articles et mettre à jour le stock
            foreach ($order_items as $item) {
                $item_total = $item['price'] * $item['quantity'];
                
                $order_item_query = "INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_price) 
                                    VALUES (?, ?, ?, ?, ?)";
                $order_item_stmt = $conn->prepare($order_item_query);
                $order_item_stmt->bind_param("iiidd", $order_id, $item['product_id'], $item['quantity'], 
                                            $item['price'], $item_total);
                $order_item_stmt->execute();
                
                // Mettre à jour le stock
                $update_stock = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stock_stmt = $conn->prepare($update_stock);
                $stock_stmt->bind_param("ii", $item['quantity'], $item['product_id']);
                $stock_stmt->execute();
            }
            
            // Vider le panier
            $clear_cart = "DELETE FROM cart_items WHERE user_id = ?";
            $clear_stmt = $conn->prepare($clear_cart);
            $clear_stmt->bind_param("i", $_SESSION['user_id']);
            $clear_stmt->execute();
            
            $conn->commit();
            $success = "Commande #{$order_number} passée avec succès ! Total: " . number_format($total_amount, 2) . " " . CURRENCY;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Récupérer les articles du panier
$cart_query = "SELECT ci.*, p.title, p.price, p.image_url, p.stock, p.status, c.name as category_name, u.username as seller_name
               FROM cart_items ci
               JOIN products p ON ci.product_id = p.id
               JOIN categories c ON p.category_id = c.id
               JOIN users u ON p.bibliothecaire_id = u.id
               WHERE ci.user_id = ?
               ORDER BY ci.added_at DESC";
$cart_stmt = $conn->prepare($cart_query);
$cart_stmt->bind_param("i", $_SESSION['user_id']);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

// Calculer le total
$total_amount = 0;
$cart_items = [];
while ($item = $cart_result->fetch_assoc()) {
    $item['total_price'] = $item['price'] * $item['quantity'];
    $total_amount += $item['total_price'];
    $cart_items[] = $item;
}

// Récupérer les wilayas
$wilayas_query = "SELECT * FROM wilayas ORDER BY name";
$wilayas_result = $conn->query($wilayas_query);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Panier - Library</title>
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
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="cart.php" class="active">Panier</a></li>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <div class="dashboard-header">
                <h2>🛒 Mon Panier</h2>
                <p>Gérez vos articles et passez votre commande</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (!empty($cart_items)): ?>
                <div class="cart-section">
                    <!-- Articles du panier -->
                    <div class="cart-items">
                        <h3>Articles dans votre panier</h3>
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="item-image">
                                    <?php if ($item['image_url']): ?>
                                        <img src="<?php echo sanitizeInput($item['image_url']); ?>" 
                                             alt="<?php echo sanitizeInput($item['title']); ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="product-placeholder" style="display: none;">📦</div>
                                    <?php else: ?>
                                        <div class="product-placeholder">📦</div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-details">
                                    <h4><?php echo sanitizeInput($item['title']); ?></h4>
                                    <p class="item-category"><?php echo sanitizeInput($item['category_name']); ?></p>
                                    <p class="item-seller">Vendeur: <?php echo sanitizeInput($item['seller_name']); ?></p>
                                    <p class="item-price">Prix unitaire: <?php echo number_format($item['price'], 2); ?> <?php echo CURRENCY; ?></p>
                                    
                                    <?php if ($item['status'] !== 'available' || $item['stock'] <= 0): ?>
                                        <p class="item-unavailable">❌ Produit non disponible</p>
                                    <?php elseif ($item['stock'] < $item['quantity']): ?>
                                        <p class="item-warning">⚠️ Stock insuffisant (<?php echo $item['stock']; ?> disponible)</p>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="item-quantity">
                                    <form method="POST" action="" class="quantity-form">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <label>Quantité:</label>
                                        <select name="quantity" onchange="this.form.submit()">
                                            <?php for ($i = 1; $i <= min(MAX_CART_QUANTITY, $item['stock']); $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == $item['quantity'] ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <input type="hidden" name="update_quantity" value="1">
                                    </form>
                                </div>
                                
                                <div class="item-total">
                                    <p class="total-price"><?php echo number_format($item['total_price'], 2); ?> <?php echo CURRENCY; ?></p>
                                </div>
                                
                                <div class="item-actions">
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" name="remove_item" class="btn btn-small btn-danger" 
                                                onclick="return confirm('Supprimer cet article du panier ?')">
                                            🗑️ Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Formulaire de commande -->
                    <div class="order-form">
                        <div class="summary-card">
                            <h3>Finaliser la commande</h3>
                            
                            <div class="cart-summary">
                                <div class="summary-line">
                                    <span>Sous-total (<?php echo count($cart_items); ?> articles):</span>
                                    <span id="subtotal"><?php echo number_format($total_amount, 2); ?> <?php echo CURRENCY; ?></span>
                                </div>
                                <div class="summary-line">
                                    <span>Frais de livraison:</span>
                                    <span id="delivery-cost">À calculer</span>
                                </div>
                                <div class="summary-line total">
                                    <span><strong>Total:</strong></span>
                                    <span id="total-amount"><strong><?php echo number_format($total_amount, 2); ?> <?php echo CURRENCY; ?></strong></span>
                                </div>
                            </div>
                            
                            <form method="POST" action="" class="checkout-form">
                                <div class="form-section">
                                    <h4>🚚 Informations de livraison</h4>
                                    
                                    <div class="form-group">
                                        <label>Type de livraison *</label>
                                        <div class="radio-group">
                                            <label class="radio-option">
                                                <input type="radio" name="delivery_type" value="domicile" checked>
                                                <span>🏠 Livraison à domicile</span>
                                            </label>
                                            <label class="radio-option">
                                                <input type="radio" name="delivery_type" value="bureau">
                                                <span>🏢 Livraison au bureau</span>
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="delivery_wilaya">Wilaya *</label>
                                        <select id="delivery_wilaya" name="delivery_wilaya" required onchange="updateDeliveryPrice()">
                                            <option value="">Choisir une wilaya</option>
                                            <?php while ($wilaya = $wilayas_result->fetch_assoc()): ?>
                                                <option value="<?php echo $wilaya['id']; ?>" data-price="<?php echo $wilaya['delivery_price']; ?>">
                                                    <?php echo sanitizeInput($wilaya['code'] . ' - ' . $wilaya['name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="delivery_commune">Commune *</label>
                                        <input type="text" id="delivery_commune" name="delivery_commune" required 
                                               placeholder="Nom de la commune">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="delivery_address">Adresse complète *</label>
                                        <textarea id="delivery_address" name="delivery_address" rows="3" required 
                                                  placeholder="Adresse détaillée (rue, numéro, étage, etc.)"></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="delivery_phone">Téléphone *</label>
                                        <input type="tel" id="delivery_phone" name="delivery_phone" required 
                                               placeholder="Ex: 0555123456 ou +213555123456">
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h4>💳 Mode de paiement</h4>
                                    <div class="radio-group">
                                        <label class="radio-option">
                                            <input type="radio" name="payment_method" value="cash_on_delivery" checked>
                                            <span>💵 Paiement à la livraison (Cash)</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-section">
                                    <h4>📝 Notes (optionnel)</h4>
                                    <div class="form-group">
                                        <textarea name="notes" rows="3" 
                                                  placeholder="Instructions spéciales pour la livraison..."></textarea>
                                    </div>
                                </div>
                                
                                <div class="delivery-info">
                                    <p><strong>🚚 Livraison par ZR express</strong></p>
                                    <p>Délai: 24h - 48h ouvrables</p>
                                    <p>Suivi en temps réel</p>
                                </div>
                                
                                <button type="submit" name="place_order" class="btn btn-primary btn-full btn-large">
                                    Confirmer la commande
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="cart-actions">
                    <a href="products.php" class="btn btn-secondary">← Continuer les achats</a>
                </div>
                
            <?php else: ?>
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <h3>Votre panier est vide</h3>
                    <p>Découvrez nos produits et ajoutez-les à votre panier</p>
                    <a href="products.php" class="btn btn-primary">Voir les produits</a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        function updateDeliveryPrice() {
            const wilayaSelect = document.getElementById('delivery_wilaya');
            const selectedOption = wilayaSelect.options[wilayaSelect.selectedIndex];
            const deliveryPrice = selectedOption.getAttribute('data-price');
            const subtotal = <?php echo $total_amount; ?>;
            
            if (deliveryPrice) {
                const deliveryCost = parseFloat(deliveryPrice);
                const total = subtotal + deliveryCost;
                
                document.getElementById('delivery-cost').textContent = deliveryCost.toFixed(2) + ' <?php echo CURRENCY; ?>';
                document.getElementById('total-amount').innerHTML = '<strong>' + total.toFixed(2) + ' <?php echo CURRENCY; ?></strong>';
            } else {
                document.getElementById('delivery-cost').textContent = 'À calculer';
                document.getElementById('total-amount').innerHTML = '<strong>' + subtotal.toFixed(2) + ' <?php echo CURRENCY; ?></strong>';
            }
        }
    </script>
</body>
</html>
