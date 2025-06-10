<?php 
include 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php');
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirectTo('dashboard.php');
}

$order_id = (int)$_GET['id'];

// Récupérer les détails de la commande
$order_query = "SELECT o.*, w.name as wilaya_name FROM orders o 
                LEFT JOIN wilayas w ON o.delivery_wilaya = w.id 
                WHERE o.id = ? AND o.user_id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$order_stmt->execute();
$order_result = $order_stmt->get_result();

if ($order_result->num_rows === 0) {
    redirectTo('dashboard.php');
}

$order = $order_result->fetch_assoc();

// Récupérer les articles de la commande
$items_query = "SELECT oi.*, p.title, p.image_url, c.name as category_name 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN categories c ON p.category_id = c.id 
                WHERE oi.order_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commande #<?php echo sanitizeInput($order['order_number']); ?> - Library</title>
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
                    <?php if (isAcheteur()): ?>
                        <li><a href="cart.php">Panier</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <div class="breadcrumb">
                <a href="dashboard.php">← Retour au dashboard</a>
            </div>

            <div class="order-detail">
                <div class="order-header">
                    <h2>Commande #<?php echo sanitizeInput($order['order_number']); ?></h2>
                    <div class="order-status">
                        <span class="status-badge status-<?php echo $order['status']; ?>">
                            <?php 
                            switch ($order['status']) {
                                case 'pending':
                                    echo '⏳ En attente de confirmation';
                                    break;
                                case 'confirmed':
                                    echo '✅ Confirmée';
                                    break;
                                case 'shipped':
                                    echo '🚚 Expédiée';
                                    break;
                                case 'delivered':
                                    echo '📦 Livrée';
                                    break;
                                case 'cancelled':
                                    echo '❌ Annulée';
                                    break;
                            }
                            ?>
                        </span>
                    </div>
                </div>

                <div class="order-info-grid">
                    <div class="order-info-card">
                        <h3>📅 Informations de commande</h3>
                        <p><strong>Date de commande:</strong> <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?></p>
                        <p><strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y à H:i', strtotime($order['updated_at'])); ?></p>
                        <p><strong>Mode de paiement:</strong> 
                            <?php echo $order['payment_method'] === 'cash_on_delivery' ? '💵 Paiement à la livraison' : '🏦 Virement bancaire'; ?>
                        </p>
                        <?php if ($order['yalidine_tracking']): ?>
                            <p><strong>Suivi Yalidine:</strong> <?php echo sanitizeInput($order['yalidine_tracking']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="order-info-card">
                        <h3>🚚 Adresse de livraison</h3>
                        <p><strong>Type:</strong> <?php echo $order['delivery_type'] === 'domicile' ? '🏠 Domicile' : '🏢 Bureau'; ?></p>
                        <p><strong>Wilaya:</strong> <?php echo sanitizeInput($order['wilaya_name']); ?></p>
                        <p><strong>Commune:</strong> <?php echo sanitizeInput($order['delivery_commune']); ?></p>
                        <p><strong>Adresse:</strong> <?php echo nl2br(sanitizeInput($order['delivery_address'])); ?></p>
                        <p><strong>Téléphone:</strong> <?php echo sanitizeInput($order['delivery_phone']); ?></p>
                    </div>
                </div>

                <?php if ($order['notes']): ?>
                    <div class="order-notes">
                        <h3>📝 Notes</h3>
                        <p><?php echo nl2br(sanitizeInput($order['notes'])); ?></p>
                    </div>
                <?php endif; ?>

                <div class="order-items">
                    <h3>📦 Articles commandés</h3>
                    <div class="items-list">
                        <?php while ($item = $items_result->fetch_assoc()): ?>
                            <div class="order-item">
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
                                    <p class="item-quantity">Quantité: <?php echo $item['quantity']; ?></p>
                                    <p class="item-price">Prix unitaire: <?php echo number_format($item['unit_price'], 2); ?> <?php echo CURRENCY; ?></p>
                                </div>
                                <div class="item-total">
                                    <p class="total-price"><?php echo number_format($item['total_price'], 2); ?> <?php echo CURRENCY; ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="order-summary">
                    <h3>💰 Récapitulatif</h3>
                    <div class="summary-lines">
                        <?php
                        // Calculer le sous-total
                        $items_stmt->execute();
                        $items_result = $items_stmt->get_result();
                        $subtotal = 0;
                        while ($item = $items_result->fetch_assoc()) {
                            $subtotal += $item['total_price'];
                        }
                        $delivery_cost = $order['total_amount'] - $subtotal;
                        ?>
                        <div class="summary-line">
                            <span>Sous-total:</span>
                            <span><?php echo number_format($subtotal, 2); ?> <?php echo CURRENCY; ?></span>
                        </div>
                        <div class="summary-line">
                            <span>Frais de livraison:</span>
                            <span><?php echo number_format($delivery_cost, 2); ?> <?php echo CURRENCY; ?></span>
                        </div>
                        <div class="summary-line total">
                            <span><strong>Total:</strong></span>
                            <span><strong><?php echo number_format($order['total_amount'], 2); ?> <?php echo CURRENCY; ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <?php if ($order['status'] === 'pending'): ?>
                        <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                            Annuler la commande
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn btn-secondary" onclick="window.print()">
                        🖨️ Imprimer
                    </button>
                    
                    <a href="dashboard.php" class="btn btn-primary">
                        Retour au dashboard
                    </a>
                </div>
            </div>
        </div>
    </main>

    <script src="script.js"></script>
    <script>
        function cancelOrder(orderId) {
            if (confirm('Êtes-vous sûr de vouloir annuler cette commande ?')) {
                // Ici vous pouvez ajouter la logique d'annulation
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'order_id=' + orderId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erreur lors de l\'annulation: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Erreur de connexion');
                });
            }
        }
    </script>
</body>
</html>
