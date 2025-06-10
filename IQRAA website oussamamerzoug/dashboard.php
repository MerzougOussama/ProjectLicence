<?php 
include 'config.php';

if (!isLoggedIn()) {
    redirectTo('login.php');
}

$error = '';
$success = '';

// Traitement de l'ajout de produit (pour les bibliothécaires)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product']) && isBibliothecaire()) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $image_url = sanitizeInput($_POST['image_url']);
    $stock = intval($_POST['stock']) ?: 1;
    
    if (empty($title) || empty($description) || $price <= 0 || $category_id <= 0 || $stock <= 0) {
        $error = 'Veuillez remplir tous les champs obligatoires avec des valeurs valides.';
    } elseif ($stock > MAX_STOCK) {
        $error = 'Le stock ne peut pas dépasser ' . MAX_STOCK . ' unités.';
    } else {
        $query = "INSERT INTO products (title, description, price, category_id, bibliothecaire_id, image_url, stock) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssdissi", $title, $description, $price, $category_id, $_SESSION['user_id'], $image_url, $stock);
        
        if ($stmt->execute()) {
            $success = 'Produit ajouté avec succès !';
            $_POST = array();
        } else {
            $error = 'Erreur lors de l\'ajout du produit.';
        }
    }
}

// Traitement de suppression de produit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product']) && isBibliothecaire()) {
    $product_id = intval($_POST['product_id']);
    
    $check_query = "SELECT id FROM products WHERE id = ? AND bibliothecaire_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $delete_query = "DELETE FROM products WHERE id = ? AND bibliothecaire_id = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param("ii", $product_id, $_SESSION['user_id']);
        
        if ($delete_stmt->execute()) {
            $success = 'Produit supprimé avec succès !';
        } else {
            $error = 'Erreur lors de la suppression du produit.';
        }
    } else {
        $error = 'Produit non trouvé ou non autorisé.';
    }
}

// Traitement de mise à jour du statut de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status']) && isBibliothecaire()) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitizeInput($_POST['new_status']);
    
    // Vérifier que la commande contient des produits du bibliothécaire
    $check_query = "SELECT DISTINCT o.id FROM orders o 
                    JOIN order_items oi ON o.id = oi.order_id 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE o.id = ? AND p.bibliothecaire_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $new_status, $order_id);
        
        if ($update_stmt->execute()) {
            $success = 'Statut de la commande mis à jour avec succès !';
        } else {
            $error = 'Erreur lors de la mise à jour du statut.';
        }
    } else {
        $error = 'Commande non trouvée ou non autorisée.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Library</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Styles supplémentaires pour la gestion des commandes */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 12px;
            width: 90%;
            max-width: 900px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            position: absolute;
            right: 1rem;
            top: 1rem;
            line-height: 1;
        }
        .close:hover {
            color: #000;
        }
        .order-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .detail-card {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        .detail-card h4 {
            margin-bottom: 1rem;
            color: #2c3e50;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .detail-item {
            margin-bottom: 0.75rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .detail-label {
            font-weight: 500;
            color: #555;
            min-width: 120px;
        }
        .detail-value {
            color: #2c3e50;
            text-align: right;
            flex: 1;
        }
        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-items-table th,
        .order-items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }
        .order-items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        .order-items-table tfoot td {
            background: #f8f9fa;
            font-weight: 600;
        }
        .status-update-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 8px;
            margin-top: 1rem;
            border: 1px solid #e9ecef;
        }
        .status-update-form h5 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        .search-filters {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border: 1px solid #e9ecef;
        }
        .filter-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: end;
        }
        .orders-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .summary-stat {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
        }
        .summary-stat h4 {
            margin: 0;
            font-size: 2rem;
            font-weight: bold;
        }
        .summary-stat p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
        }
        .client-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .client-name {
            font-weight: 600;
            color: #2c3e50;
        }
        .client-contact {
            font-size: 0.9rem;
            color: #7f8c8d;
        }
        @media (max-width: 768px) {
            .filter-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .order-details-grid {
                grid-template-columns: 1fr;
            }
            .modal-content {
                margin: 2% auto;
                width: 95%;
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo"><a href="index.php">🖊IQRAA</a></h1>
                <ul class="nav-menu">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="products.php">Produits</a></li>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <?php if (isAcheteur()): ?>
                        <li><a href="cart.php">Panier <?php $count = getCartCount(); echo $count > 0 ? "($count)" : ''; ?></a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Déconnexion</a></li>
                </ul>
            </div>
        </nav>
    </header>

    <main class="dashboard-main">
        <div class="container">
            <div class="dashboard-header">
                <h2>Tableau de bord</h2>
                <p>Bienvenue, <?php echo sanitizeInput($_SESSION['username']); ?> (<?php echo ucfirst($_SESSION['role']); ?>)</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (isBibliothecaire()): ?>
                <!-- Dashboard Bibliothécaire -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h3>📦 Mes Produits</h3>
                        <?php
                        $count_query = "SELECT COUNT(*) as count FROM products WHERE bibliothecaire_id = ?";
                        $count_stmt = $conn->prepare($count_query);
                        $count_stmt->bind_param("i", $_SESSION['user_id']);
                        $count_stmt->execute();
                        $count_result = $count_stmt->get_result();
                        $product_count = $count_result->fetch_assoc()['count'];
                        ?>
                        <p class="dashboard-number"><?php echo $product_count; ?></p>
                        <p class="dashboard-subtitle">Produits publiés</p>
                    </div>

                    <div class="dashboard-card">
                        <h3>💰 Commandes</h3>
                        <?php
                        $orders_query = "SELECT COUNT(DISTINCT o.id) as count, SUM(oi.total_price) as total 
                                        FROM orders o 
                                        JOIN order_items oi ON o.id = oi.order_id 
                                        JOIN products p ON oi.product_id = p.id 
                                        WHERE p.bibliothecaire_id = ? AND o.status != 'cancelled'";
                        $orders_stmt = $conn->prepare($orders_query);
                        $orders_stmt->bind_param("i", $_SESSION['user_id']);
                        $orders_stmt->execute();
                        $orders_result = $orders_stmt->get_result();
                        $orders_data = $orders_result->fetch_assoc();
                        ?>
                        <p class="dashboard-number"><?php echo $orders_data['count'] ?? 0; ?></p>
                        <p class="dashboard-subtitle"><?php echo number_format($orders_data['total'] ?? 0, 2); ?> <?php echo CURRENCY; ?></p>
                    </div>

                    <div class="dashboard-card">
                        <h3>⭐ Avis</h3>
                        <?php
                        $reviews_query = "SELECT COUNT(*) as count, AVG(rating) as avg_rating FROM reviews r 
                                         JOIN products p ON r.product_id = p.id 
                                         WHERE p.bibliothecaire_id = ?";
                        $reviews_stmt = $conn->prepare($reviews_query);
                        $reviews_stmt->bind_param("i", $_SESSION['user_id']);
                        $reviews_stmt->execute();
                        $reviews_result = $reviews_stmt->get_result();
                        $reviews_data = $reviews_result->fetch_assoc();
                        ?>
                        <p class="dashboard-number"><?php echo $reviews_data['count']; ?></p>
                        <p class="dashboard-subtitle">Moyenne: <?php echo number_format($reviews_data['avg_rating'] ?? 0, 1); ?>/5</p>
                    </div>

                    <div class="dashboard-card">
                        <h3>📊 Stock Total</h3>
                        <?php
                        $stock_query = "SELECT SUM(stock) as total_stock FROM products WHERE bibliothecaire_id = ? AND status = 'available'";
                        $stock_stmt = $conn->prepare($stock_query);
                        $stock_stmt->bind_param("i", $_SESSION['user_id']);
                        $stock_stmt->execute();
                        $stock_result = $stock_stmt->get_result();
                        $total_stock = $stock_result->fetch_assoc()['total_stock'] ?? 0;
                        ?>
                        <p class="dashboard-number"><?php echo $total_stock; ?></p>
                        <p class="dashboard-subtitle">Unités en stock</p>
                    </div>
                </div>

                <!-- Section Gestion des Commandes -->
                <div class="dashboard-section">
                    <h3>🛒 Gestion des Commandes et Clients</h3>
                    <p class="dashboard-subtitle">Visualisez et gérez toutes les commandes contenant vos produits avec les informations clients</p>
                    
                    <!-- Statistiques rapides des commandes -->
                    <?php
                    // Statistiques pour les commandes du bibliothécaire
                    $stats_query = "SELECT 
                                    COUNT(DISTINCT CASE WHEN o.status = 'pending' THEN o.id END) as pending_orders,
                                    COUNT(DISTINCT CASE WHEN o.status = 'confirmed' THEN o.id END) as confirmed_orders,
                                    COUNT(DISTINCT CASE WHEN o.status = 'shipped' THEN o.id END) as shipped_orders,
                                    COUNT(DISTINCT CASE WHEN o.status = 'delivered' THEN o.id END) as delivered_orders,
                                    COUNT(DISTINCT o.user_id) as unique_customers
                                    FROM orders o 
                                    JOIN order_items oi ON o.id = oi.order_id 
                                    JOIN products p ON oi.product_id = p.id 
                                    WHERE p.bibliothecaire_id = ?";
    $stats_stmt = $conn->prepare($stats_query);
    $stats_stmt->bind_param("i", $_SESSION['user_id']);
    $stats_stmt->execute();
    $stats = $stats_stmt->get_result()->fetch_assoc();
    ?>
    
    <div class="orders-summary">
        <div class="summary-stat" style="background: linear-gradient(135deg, #f39c12, #e67e22);">
            <h4><?php echo $stats['pending_orders']; ?></h4>
            <p>⏳ En attente</p>
        </div>
        <div class="summary-stat" style="background: linear-gradient(135deg, #27ae60, #229954);">
            <h4><?php echo $stats['confirmed_orders']; ?></h4>
            <p>✅ Confirmées</p>
        </div>
        <div class="summary-stat" style="background: linear-gradient(135deg, #3498db, #2980b9);">
            <h4><?php echo $stats['shipped_orders']; ?></h4>
            <p>🚚 Expédiées</p>
        </div>
        <div class="summary-stat" style="background: linear-gradient(135deg, #9b59b6, #8e44ad);">
            <h4><?php echo $stats['unique_customers']; ?></h4>
            <p>👥 Clients uniques</p>
        </div>
    </div>
                    
                    <!-- Filtres de recherche -->
                    <div class="search-filters">
                        <h4 style="margin-bottom: 1rem; color: #2c3e50;">🔍 Filtres de recherche</h4>
                        <form method="GET" action="">
                            <div class="filter-row">
                                <div class="form-group">
                                    <label for="search">Rechercher</label>
                                    <input type="text" id="search" name="search" 
                                           placeholder="Numéro de commande, nom client, email, téléphone..."
                                           value="<?php echo isset($_GET['search']) ? sanitizeInput($_GET['search']) : ''; ?>">
                                </div>
                                <div class="form-group">
                                    <label for="status_filter">Statut</label>
                                    <select id="status_filter" name="status_filter">
                                        <option value="">Tous les statuts</option>
                                        <option value="pending" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'pending') ? 'selected' : ''; ?>>⏳ En attente</option>
                                        <option value="confirmed" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'confirmed') ? 'selected' : ''; ?>>✅ Confirmée</option>
                                        <option value="shipped" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'shipped') ? 'selected' : ''; ?>>🚚 Expédiée</option>
                                        <option value="delivered" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'delivered') ? 'selected' : ''; ?>>📦 Livrée</option>
                                        <option value="cancelled" <?php echo (isset($_GET['status_filter']) && $_GET['status_filter'] == 'cancelled') ? 'selected' : ''; ?>>❌ Annulée</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="date_filter">Période</label>
                                    <select id="date_filter" name="date_filter">
                                        <option value="">Toutes les dates</option>
                                        <option value="today" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'today') ? 'selected' : ''; ?>>Aujourd'hui</option>
                                        <option value="week" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'week') ? 'selected' : ''; ?>>Cette semaine</option>
                                        <option value="month" <?php echo (isset($_GET['date_filter']) && $_GET['date_filter'] == 'month') ? 'selected' : ''; ?>>Ce mois</option>
                                    </select>
                                </div>
                                <button type="submit" class="btn btn-primary">🔍 Filtrer</button>
                            </div>
                        </form>
                    </div>

                    <!-- Tableau des commandes -->
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>📋 Commande</th>
                                    <th>👤 Client</th>
                                    <th>📅 Date</th>
                                    <th>💰 Montant</th>
                                    <th>📦 Livraison</th>
                                    <th>📊 Statut</th>
                                    <th>⚡ Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Construction de la requête avec filtres
                                $where_conditions = ["p.bibliothecaire_id = ?"];
                                $params = [$_SESSION['user_id']];
                                $param_types = "i";

                                if (isset($_GET['search']) && !empty($_GET['search'])) {
                                    $search = '%' . $_GET['search'] . '%';
                                    $where_conditions[] = "(o.order_number LIKE ? OR u.username LIKE ? OR u.email LIKE ? OR o.delivery_phone LIKE ?)";
                                    $params = array_merge($params, [$search, $search, $search, $search]);
                                    $param_types .= "ssss";
                                }

                                if (isset($_GET['status_filter']) && !empty($_GET['status_filter'])) {
                                    $where_conditions[] = "o.status = ?";
                                    $params[] = $_GET['status_filter'];
                                    $param_types .= "s";
                                }

                                if (isset($_GET['date_filter']) && !empty($_GET['date_filter'])) {
                                    switch ($_GET['date_filter']) {
                                        case 'today':
                                            $where_conditions[] = "DATE(o.created_at) = CURDATE()";
                                            break;
                                        case 'week':
                                            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                                            break;
                                        case 'month':
                                            $where_conditions[] = "o.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                                            break;
                                    }
                                }

                                $where_clause = implode(" AND ", $where_conditions);

                                $orders_query = "SELECT DISTINCT o.*, u.username, u.email,
                                               SUM(oi.total_price) as order_total,
                                               COUNT(oi.id) as items_count,
                                               w.name as wilaya_name
                                               FROM orders o 
                                               JOIN order_items oi ON o.id = oi.order_id 
                                               JOIN products p ON oi.product_id = p.id 
                                               JOIN users u ON o.user_id = u.id
                                               LEFT JOIN wilayas w ON o.delivery_wilaya = w.code
                                               WHERE $where_clause
                                               GROUP BY o.id
                                               ORDER BY o.created_at DESC";
                                
                                $orders_stmt = $conn->prepare($orders_query);
                                if (!empty($params)) {
                                    $orders_stmt->bind_param($param_types, ...$params);
                                }
                                $orders_stmt->execute();
                                $orders_result = $orders_stmt->get_result();
                                
                                if ($orders_result->num_rows > 0):
                                    while ($order = $orders_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong>#<?php echo sanitizeInput($order['order_number']); ?></strong>
                                            <br><small style="color: #7f8c8d;"><?php echo $order['items_count']; ?> article(s) • <?php echo ucfirst($order['payment_method'] == 'cash_on_delivery' ? 'Paiement à la livraison' : 'Virement bancaire'); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="client-info">
                                            <span class="client-name"><?php echo sanitizeInput($order['username']); ?></span>
                                            <span class="client-contact">📧 <?php echo sanitizeInput($order['email']); ?></span>
                                            <span class="client-contact">📞 <?php echo sanitizeInput($order['delivery_phone']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></strong>
                                            <br><small style="color: #7f8c8d;"><?php echo date('H:i', strtotime($order['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong style="color: #27ae60;"><?php echo number_format($order['total_amount'], 2); ?> DA</strong>
                                            <br><small style="color: #7f8c8d;">Vos produits: <?php echo number_format($order['order_total'], 2); ?> DA</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo sanitizeInput($order['wilaya_name'] ?? $order['delivery_wilaya']); ?></strong>
                                            <br><small style="color: #7f8c8d;"><?php echo sanitizeInput($order['delivery_commune']); ?></small>
                                            <br><small style="color: <?php echo $order['delivery_type'] == 'domicile' ? '#3498db' : '#9b59b6'; ?>;">
                                                <?php echo $order['delivery_type'] == 'domicile' ? '🏠 Domicile' : '🏢 Bureau'; ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php 
                                            switch ($order['status']) {
                                                case 'pending': echo '⏳ En attente'; break;
                                                case 'confirmed': echo '✅ Confirmée'; break;
                                                case 'shipped': echo '🚚 Expédiée'; break;
                                                case 'delivered': echo '📦 Livrée'; break;
                                                case 'cancelled': echo '❌ Annulée'; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-small" onclick="showOrderDetails(<?php echo $order['id']; ?>)" title="Voir les détails complets">
                                                👁️ Détails
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div style="padding: 3rem; color: #7f8c8d;">
                                            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
                                            <h3 style="color: #2c3e50; margin-bottom: 0.5rem;">Aucune commande trouvée</h3>
                                            <p>Les commandes apparaîtront ici dès qu'un client achètera vos produits.</p>
                                            <?php if (isset($_GET['search']) || isset($_GET['status_filter']) || isset($_GET['date_filter'])): ?>
                                                <p><small>Essayez de modifier vos critères de recherche.</small></p>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal pour les détails de commande -->
                <div id="orderModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeOrderModal()">&times;</span>
                        <div id="orderDetailsContent">
                            <div style="text-align: center; padding: 2rem; color: #7f8c8d;">
                                <div style="font-size: 2rem; margin-bottom: 1rem;">⏳</div>
                                <p>Chargement des détails...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Formulaire d'ajout de produit -->
                <div class="dashboard-section">
                    <h3>➕ Ajouter un nouveau produit</h3>
                    <form method="POST" action="" class="product-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="title">Titre du produit *</label>
                                <input type="text" id="title" name="title" required maxlength="100"
                                       value="<?php echo isset($_POST['title']) ? sanitizeInput($_POST['title']) : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="category_id">Catégorie *</label>
                                <select id="category_id" name="category_id" required>
                                    <option value="">Choisir une catégorie</option>
                                    <?php
                                    $categories_query = "SELECT * FROM categories ORDER BY name";
                                    $categories_result = $conn->query($categories_query);
                                    while ($category = $categories_result->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $category['id']; ?>"
                                                <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                            <?php echo sanitizeInput($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description *</label>
                            <textarea id="description" name="description" rows="4" required maxlength="1000"><?php echo isset($_POST['description']) ? sanitizeInput($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Prix (<?php echo CURRENCY; ?>) *</label>
                                <input type="number" id="price" name="price" step="0.01" min="0.01" required
                                       value="<?php echo isset($_POST['price']) ? $_POST['price'] : ''; ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="stock">Stock *</label>
                                <input type="number" id="stock" name="stock" min="1" max="<?php echo MAX_STOCK; ?>" required
                                       value="<?php echo isset($_POST['stock']) ? $_POST['stock'] : '1'; ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="image_url">URL de l'image (optionnel)</label>
                            <input type="url" id="image_url" name="image_url" 
                                   value="<?php echo isset($_POST['image_url']) ? sanitizeInput($_POST['image_url']) : ''; ?>"
                                   placeholder="https://exemple.com/image.jpg">
                        </div>
                        
                        <button type="submit" name="add_product" class="btn btn-primary">➕ Ajouter le produit</button>
                    </form>
                </div>

                <!-- Liste des produits -->
                <div class="dashboard-section">
                    <h3>📦 Mes produits</h3>
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Titre</th>
                                    <th>Catégorie</th>
                                    <th>Prix</th>
                                    <th>Stock</th>
                                    <th>Statut</th>
                                    <th>Avis</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $products_query = "SELECT p.*, c.name as category_name,
                                                  (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count,
                                                  (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating
                                                  FROM products p 
                                                  JOIN categories c ON p.category_id = c.id 
                                                  WHERE p.bibliothecaire_id = ? 
                                                  ORDER BY p.created_at DESC";
                                $products_stmt = $conn->prepare($products_query);
                                $products_stmt->bind_param("i", $_SESSION['user_id']);
                                $products_stmt->execute();
                                $products_result = $products_stmt->get_result();
                                
                                if ($products_result->num_rows > 0):
                                    while ($product = $products_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo sanitizeInput($product['title']); ?></strong>
                                        <br><small><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></small>
                                    </td>
                                    <td><?php echo sanitizeInput($product['category_name']); ?></td>
                                    <td><?php echo number_format($product['price'], 2); ?> <?php echo CURRENCY; ?></td>
                                    <td>
                                        <span class="stock-badge <?php echo $product['stock'] <= 5 ? 'low' : ''; ?>">
                                            <?php echo $product['stock']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status <?php echo $product['status']; ?>">
                                            <?php echo $product['status'] === 'available' ? 'Disponible' : 'Vendu'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['review_count'] > 0): ?>
                                            <div class="rating-small">
                                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                                    <span class="star <?php echo $i <= round($product['avg_rating']) ? 'filled' : ''; ?>">⭐</span>
                                                <?php endfor; ?>
                                                <br><small><?php echo $product['review_count']; ?> avis</small>
                                            </div>
                                        <?php else: ?>
                                            <small>Aucun avis</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-small">Voir</a>
                                            <form method="POST" action="" style="display: inline;" 
                                                  onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <button type="submit" name="delete_product" class="btn btn-small btn-danger">Supprimer</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <p>Aucun produit ajouté pour le moment.</p>
                                        <p><small>Utilisez le formulaire ci-dessus pour ajouter votre premier produit.</small></p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php else: ?>
                <!-- Dashboard Acheteur (code existant inchangé) -->
                <div class="dashboard-grid">
                    <div class="dashboard-card">
                        <h3>🛒 Mes Commandes</h3>
                        <?php
                        $orders_query = "SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE user_id = ?";
                        $orders_stmt = $conn->prepare($orders_query);
                        $orders_stmt->bind_param("i", $_SESSION['user_id']);
                        $orders_stmt->execute();
                        $orders_result = $orders_stmt->get_result();
                        $orders_data = $orders_result->fetch_assoc();
                        ?>
                        <p class="dashboard-number"><?php echo $orders_data['count']; ?></p>
                        <p class="dashboard-subtitle"><?php echo number_format($orders_data['total'] ?? 0, 2); ?> <?php echo CURRENCY; ?></p>
                    </div>

                    <div class="dashboard-card">
                        <h3>🛒 Mon Panier</h3>
                        <?php $cart_count = getCartCount(); ?>
                        <p class="dashboard-number"><?php echo $cart_count; ?></p>
                        <p class="dashboard-subtitle">Articles</p>
                        <a href="cart.php" class="btn btn-primary">Voir le panier</a>
                    </div>

                    <div class="dashboard-card">
                        <h3>⭐ Mes Avis</h3>
                        <?php
                        $my_reviews_query = "SELECT COUNT(*) as count FROM reviews WHERE buyer_id = ?";
                        $my_reviews_stmt = $conn->prepare($my_reviews_query);
                        $my_reviews_stmt->bind_param("i", $_SESSION['user_id']);
                        $my_reviews_stmt->execute();
                        $my_reviews_result = $my_reviews_stmt->get_result();
                        $my_reviews_count = $my_reviews_result->fetch_assoc()['count'];
                        ?>
                        <p class="dashboard-number"><?php echo $my_reviews_count; ?></p>
                        <p class="dashboard-subtitle">Avis publiés</p>
                    </div>

                    <div class="dashboard-card">
                        <h3>📦 Dernière Commande</h3>
                        <?php
                        $last_order_query = "SELECT order_number, status, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
                        $last_order_stmt = $conn->prepare($last_order_query);
                        $last_order_stmt->bind_param("i", $_SESSION['user_id']);
                        $last_order_stmt->execute();
                        $last_order_result = $last_order_stmt->get_result();
                        
                        if ($last_order_result->num_rows > 0):
                            $last_order = $last_order_result->fetch_assoc();
                        ?>
                            <p class="dashboard-number">#<?php echo sanitizeInput($last_order['order_number']); ?></p>
                            <p class="dashboard-subtitle">
                                <?php 
                                switch ($last_order['status']) {
                                    case 'pending': echo '⏳ En attente'; break;
                                    case 'confirmed': echo '✅ Confirmée'; break;
                                    case 'shipped': echo '🚚 Expédiée'; break;
                                    case 'delivered': echo '📦 Livrée'; break;
                                    case 'cancelled': echo '❌ Annulée'; break;
                                }
                                ?>
                            </p>
                        <?php else: ?>
                            <p class="dashboard-number">-</p>
                            <p class="dashboard-subtitle">Aucune commande</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Historique des commandes -->
                <div class="dashboard-section">
                    <h3>Mes commandes récentes</h3>
                    <div class="table-container">
                        <table class="dashboard-table">
                            <thead>
                                <tr>
                                    <th>Numéro</th>
                                    <th>Date</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recent_orders_query = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
                                $recent_orders_stmt = $conn->prepare($recent_orders_query);
                                $recent_orders_stmt->bind_param("i", $_SESSION['user_id']);
                                $recent_orders_stmt->execute();
                                $recent_orders_result = $recent_orders_stmt->get_result();
                                
                                if ($recent_orders_result->num_rows > 0):
                                    while ($order = $recent_orders_result->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><strong>#<?php echo sanitizeInput($order['order_number']); ?></strong></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                                    <td><?php echo number_format($order['total_amount'], 2); ?> <?php echo CURRENCY; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php 
                                            switch ($order['status']) {
                                                case 'pending': echo '⏳ En attente'; break;
                                                case 'confirmed': echo '✅ Confirmée'; break;
                                                case 'shipped': echo '🚚 Expédiée'; break;
                                                case 'delivered': echo '📦 Livrée'; break;
                                                case 'cancelled': echo '❌ Annulée'; break;
                                            }
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-small">Détails</a>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="5" class="text-center">
                                        <p>Aucune commande pour le moment.</p>
                                        <a href="products.php" class="btn btn-primary">Découvrir nos produits</a>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Fonctions pour la gestion des commandes
        function showOrderDetails(orderId) {
            document.getElementById('orderModal').style.display = 'block';
            
            // Charger les détails via AJAX
            fetch('get_order_details.php?id=' + orderId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('orderDetailsContent').innerHTML = data;
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('orderDetailsContent').innerHTML = 
                        '<div style="text-align: center; padding: 2rem; color: #e74c3c;"><div style="font-size: 2rem; margin-bottom: 1rem;">❌</div><p>Erreur lors du chargement des détails.</p></div>';
                });
        }

        function closeOrderModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        // Fermer le modal en cliquant à l'extérieur
        window.onclick = function(event) {
            const modal = document.getElementById('orderModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function updateOrderStatus(orderId, newStatus) {
            if (confirm('Êtes-vous sûr de vouloir modifier le statut de cette commande ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="update_order_status" value="1">
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto-submit des filtres avec délai
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            if (searchInput) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        if (this.value.length >= 3 || this.value.length === 0) {
                            this.form.submit();
                        }
                    }, 500);
                });
            }
        });
    </script>
    <script src="script.js"></script>
</body>
</html>