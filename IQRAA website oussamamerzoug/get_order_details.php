<?php
include 'config.php';

if (!isLoggedIn() || !isBibliothecaire()) {
    http_response_code(403);
    exit('<div style="text-align: center; padding: 2rem; color: #e74c3c;"><div style="font-size: 2rem; margin-bottom: 1rem;">🚫</div><p>Accès non autorisé</p></div>');
}

$order_id = intval($_GET['id']);

// Vérifier que la commande contient des produits du bibliothécaire
$check_query = "SELECT DISTINCT o.id FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE o.id = ? AND p.bibliothecaire_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows == 0) {
    echo '<div style="text-align: center; padding: 2rem; color: #e74c3c;"><div style="font-size: 2rem; margin-bottom: 1rem;">❌</div><p>Commande non trouvée ou non autorisée.</p></div>';
    exit;
}

// Récupérer les détails de la commande
$order_query = "SELECT o.*, u.username, u.email, w.name as wilaya_name
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                LEFT JOIN wilayas w ON o.delivery_wilaya = w.code
                WHERE o.id = ?";
$order_stmt = $conn->prepare($order_query);
$order_stmt->bind_param("i", $order_id);
$order_stmt->execute();
$order = $order_stmt->get_result()->fetch_assoc();

// Récupérer les articles de la commande (seulement ceux du bibliothécaire)
$items_query = "SELECT oi.*, p.title, p.image_url, c.name as category_name
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN categories c ON p.category_id = c.id
                WHERE oi.order_id = ? AND p.bibliothecaire_id = ?";
$items_stmt = $conn->prepare($items_query);
$items_stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<h2 style="color: #2c3e50; margin-bottom: 1rem;">📋 Détails de la commande #<?php echo sanitizeInput($order['order_number']); ?></h2>
<p style="color: #7f8c8d; margin-bottom: 2rem;">
    Commande passée le <?php echo date('d/m/Y à H:i', strtotime($order['created_at'])); ?>
    <?php if ($order['updated_at'] != $order['created_at']): ?>
        • Dernière modification le <?php echo date('d/m/Y à H:i', strtotime($order['updated_at'])); ?>
    <?php endif; ?>
</p>

<div class="order-details-grid">
    <!-- Informations client -->
    <div class="detail-card">
        <h4>👤 Informations Client</h4>
        <div class="detail-item">
            <span class="detail-label">Nom d'utilisateur:</span>
            <span class="detail-value"><strong><?php echo sanitizeInput($order['username']); ?></strong></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Email:</span>
            <span class="detail-value"><?php echo sanitizeInput($order['email']); ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Téléphone:</span>
            <span class="detail-value"><strong><?php echo sanitizeInput($order['delivery_phone']); ?></strong></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Client depuis:</span>
            <span class="detail-value">
                <?php
                $client_since_query = "SELECT created_at FROM users WHERE id = ?";
                $client_since_stmt = $conn->prepare($client_since_query);
                $client_since_stmt->bind_param("i", $order['user_id']);
                $client_since_stmt->execute();
                $client_since = $client_since_stmt->get_result()->fetch_assoc();
                echo date('d/m/Y', strtotime($client_since['created_at']));
                ?>
            </span>
        </div>
    </div>

    <!-- Informations de livraison -->
    <div class="detail-card">
        <h4>📦 Informations de Livraison</h4>
        <div class="detail-item">
            <span class="detail-label">Type:</span>
            <span class="detail-value">
                <strong style="color: <?php echo $order['delivery_type'] == 'domicile' ? '#3498db' : '#9b59b6'; ?>;">
                    <?php echo $order['delivery_type'] == 'domicile' ? '🏠 Domicile' : '🏢 Bureau'; ?>
                </strong>
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Wilaya:</span>
            <span class="detail-value"><strong><?php echo sanitizeInput($order['wilaya_name'] ?? $order['delivery_wilaya']); ?></strong></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Commune:</span>
            <span class="detail-value"><?php echo sanitizeInput($order['delivery_commune']); ?></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Adresse complète:</span>
            <span class="detail-value"><?php echo sanitizeInput($order['delivery_address']); ?></span>
        </div>
        <?php if (!empty($order['yalidine_tracking'])): ?>
        <div class="detail-item">
            <span class="detail-label">Suivi Yalidine:</span>
            <span class="detail-value"><strong><?php echo sanitizeInput($order['yalidine_tracking']); ?></strong></span>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Articles commandés -->
<div class="detail-card">
    <h4>🛒 Vos Produits dans cette Commande</h4>
    <table class="order-items-table">
        <thead>
            <tr>
                <th>Produit</th>
                <th>Catégorie</th>
                <th style="text-align: center;">Quantité</th>
                <th style="text-align: right;">Prix unitaire</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $total_bibliothecaire = 0;
            while ($item = $items_result->fetch_assoc()): 
                $total_bibliothecaire += $item['total_price'];
            ?>
            <tr>
                <td>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if ($item['image_url']): ?>
                            <img src="<?php echo sanitizeInput($item['image_url']); ?>" 
                                 alt="<?php echo sanitizeInput($item['title']); ?>"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #e9ecef;">
                        <?php else: ?>
                            <div style="width: 60px; height: 60px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center; border: 1px solid #e9ecef; font-size: 1.5rem;">
                                📦
                            </div>
                        <?php endif; ?>
                        <div>
                            <strong style="color: #2c3e50;"><?php echo sanitizeInput($item['title']); ?></strong>
                        </div>
                    </div>
                </td>
                <td><?php echo sanitizeInput($item['category_name']); ?></td>
                <td style="text-align: center;"><strong><?php echo $item['quantity']; ?></strong></td>
                <td style="text-align: right;"><?php echo number_format($item['unit_price'], 2); ?> DA</td>
                <td style="text-align: right;"><strong style="color: #27ae60;"><?php echo number_format($item['total_price'], 2); ?> DA</strong></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
        <tfoot>
            <tr style="border-top: 2px solid #e9ecef;">
                <td colspan="4"><strong>Total de vos produits:</strong></td>
                <td style="text-align: right;"><strong style="color: #27ae60; font-size: 1.1rem;"><?php echo number_format($total_bibliothecaire, 2); ?> DA</strong></td>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Informations de paiement et statut -->
<div class="order-details-grid">
    <div class="detail-card">
        <h4>💳 Informations de Paiement</h4>
        <div class="detail-item">
            <span class="detail-label">Mode de paiement:</span>
            <span class="detail-value">
                <strong><?php echo $order['payment_method'] == 'cash_on_delivery' ? '💵 Paiement à la livraison' : '🏦 Virement bancaire'; ?></strong>
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Montant total commande:</span>
            <span class="detail-value"><strong style="color: #2c3e50; font-size: 1.1rem;"><?php echo number_format($order['total_amount'], 2); ?> DA</strong></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Vos revenus:</span>
            <span class="detail-value"><strong style="color: #27ae60; font-size: 1.1rem;"><?php echo number_format($total_bibliothecaire, 2); ?> DA</strong></span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Pourcentage:</span>
            <span class="detail-value">
                <strong style="color: #3498db;">
                    <?php echo number_format(($total_bibliothecaire / $order['total_amount']) * 100, 1); ?>%
                </strong>
            </span>
        </div>
    </div>

    <div class="detail-card">
        <h4>📊 Statut de la Commande</h4>
        <div class="detail-item">
            <span class="detail-label">Statut actuel:</span>
            <span class="detail-value">
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
            </span>
        </div>
        <div class="detail-item">
            <span class="detail-label">Dernière mise à jour:</span>
            <span class="detail-value"><?php echo date('d/m/Y à H:i', strtotime($order['updated_at'])); ?></span>
        </div>
        
        <!-- Formulaire de mise à jour du statut -->
        <?php if ($order['status'] != 'delivered' && $order['status'] != 'cancelled'): ?>
        <div class="status-update-form">
            <h5>🔄 Modifier le statut:</h5>
            <form method="POST" action="dashboard.php" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="update_order_status" value="1">
                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                <select name="new_status" required style="padding: 0.5rem; border: 2px solid #e9ecef; border-radius: 6px; font-size: 1rem;">
                    <option value="">Choisir un statut</option>
                    <?php if ($order['status'] == 'pending'): ?>
                        <option value="confirmed">✅ Confirmer</option>
                        <option value="cancelled">❌ Annuler</option>
                    <?php elseif ($order['status'] == 'confirmed'): ?>
                        <option value="shipped">🚚 Expédier</option>
                        <option value="cancelled">❌ Annuler</option>
                    <?php elseif ($order['status'] == 'shipped'): ?>
                        <option value="delivered">📦 Marquer comme livrée</option>
                    <?php endif; ?>
                </select>
                <button type="submit" class="btn btn-small btn-primary">🔄 Mettre à jour</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($order['notes'])): ?>
<div class="detail-card">
    <h4>📝 Notes de la commande</h4>
    <div style="background: white; padding: 1rem; border-radius: 6px; border: 1px solid #e9ecef;">
        <p style="margin: 0; line-height: 1.6; color: #555;"><?php echo nl2br(sanitizeInput($order['notes'])); ?></p>
    </div>
</div>
<?php endif; ?>

<!-- Historique des commandes de ce client -->
<div class="detail-card">
    <h4>📈 Historique du client</h4>
    <?php
    $client_history_query = "SELECT COUNT(DISTINCT o.id) as total_orders, 
                            SUM(oi.total_price) as total_spent_with_me,
                            MAX(o.created_at) as last_order_date
                            FROM orders o 
                            JOIN order_items oi ON o.id = oi.order_id 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE o.user_id = ? AND p.bibliothecaire_id = ? AND o.status != 'cancelled'";
    $client_history_stmt = $conn->prepare($client_history_query);
    $client_history_stmt->bind_param("ii", $order['user_id'], $_SESSION['user_id']);
    $client_history_stmt->execute();
    $client_history = $client_history_stmt->get_result()->fetch_assoc();
    ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
        <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #3498db;"><?php echo $client_history['total_orders']; ?></div>
            <div style="font-size: 0.9rem; color: #7f8c8d;">Commandes totales</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #27ae60;"><?php echo number_format($client_history['total_spent_with_me'], 2); ?> DA</div>
            <div style="font-size: 0.9rem; color: #7f8c8d;">Total dépensé</div>
        </div>
        <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 6px;">
            <div style="font-size: 1.5rem; font-weight: bold; color: #9b59b6;">
                <?php 
                if ($client_history['last_order_date']) {
                    $days_ago = floor((time() - strtotime($client_history['last_order_date'])) / (60 * 60 * 24));
                    echo $days_ago == 0 ? "Aujourd'hui" : $days_ago . " jours";
                } else {
                    echo "N/A";
                }
                ?>
            </div>
            <div style="font-size: 0.9rem; color: #7f8c8d;">Dernière commande</div>
        </div>
    </div>
</div>

<div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef;">
    <button onclick="closeOrderModal()" class="btn btn-secondary">✖️ Fermer</button>
    <button onclick="window.print()" class="btn btn-outline" style="margin-left: 1rem;">🖨️ Imprimer</button>
</div>