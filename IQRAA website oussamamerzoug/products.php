<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - Library</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo"><a href="index.php">🖊IQRAA</a></h1>
                <ul class="nav-menu">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="products.php" class="active">Produits</a></li>
                    <?php if (isLoggedIn()): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <?php if (isAcheteur()): ?>
                            <li><a href="cart.php">Panier <?php $count = getCartCount(); echo $count > 0 ? "($count)" : ''; ?></a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Déconnexion</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Connexion</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </nav>
    </header>

    <main class="products-main">
        <div class="container">
            <div class="products-header">
                <h2>Nos Produits</h2>
                
                <!-- Filtres -->
                <div class="filters">
                    <form method="GET" action="" class="filter-form">
                        <div class="filter-group">
                            <select name="category" onchange="this.form.submit()">
                                <option value="">Toutes les catégories</option>
                                <?php
                                $categories_query = "SELECT * FROM categories ORDER BY name";
                                $categories_result = $conn->query($categories_query);
                                while ($category = $categories_result->fetch_assoc()):
                                ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo (isset($_GET['category']) && $_GET['category'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo sanitizeInput($category['name']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <input type="text" name="search" placeholder="Rechercher un produit..." 
                                   value="<?php echo isset($_GET['search']) ? sanitizeInput($_GET['search']) : ''; ?>">
                        </div>
                        
                        <div class="filter-group">
                            <select name="sort" onchange="this.form.submit()">
                                <option value="">Trier par</option>
                                <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>Prix croissant</option>
                                <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>Prix décroissant</option>
                                <option value="date_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_desc') ? 'selected' : ''; ?>>Plus récents</option>
                                <option value="date_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'date_asc') ? 'selected' : ''; ?>>Plus anciens</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-secondary">Filtrer</button>
                        <?php if (!empty($_GET)): ?>
                            <a href="products.php" class="btn btn-outline">Réinitialiser</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="products-grid">
                <?php
                // Construction de la requête avec filtres
                $where_conditions = ["p.status = 'available'", "p.stock > 0"];
                $params = [];
                $types = "";
                
                if (isset($_GET['category']) && !empty($_GET['category'])) {
                    $where_conditions[] = "p.category_id = ?";
                    $params[] = $_GET['category'];
                    $types .= "i";
                }
                
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $where_conditions[] = "(p.title LIKE ? OR p.description LIKE ?)";
                    $search_term = "%" . $_GET['search'] . "%";
                    $params[] = $search_term;
                    $params[] = $search_term;
                    $types .= "ss";
                }
                
                $where_clause = implode(" AND ", $where_conditions);
                
                // Tri
                $order_clause = "ORDER BY p.created_at DESC";
                if (isset($_GET['sort'])) {
                    switch ($_GET['sort']) {
                        case 'price_asc':
                            $order_clause = "ORDER BY p.price ASC";
                            break;
                        case 'price_desc':
                            $order_clause = "ORDER BY p.price DESC";
                            break;
                        case 'date_desc':
                            $order_clause = "ORDER BY p.created_at DESC";
                            break;
                        case 'date_asc':
                            $order_clause = "ORDER BY p.created_at ASC";
                            break;
                    }
                }
                
                $query = "SELECT p.*, c.name as category_name, u.username as bibliothecaire_name,
                         (SELECT AVG(rating) FROM reviews WHERE product_id = p.id) as avg_rating,
                         (SELECT COUNT(*) FROM reviews WHERE product_id = p.id) as review_count
                         FROM products p 
                         JOIN categories c ON p.category_id = c.id 
                         JOIN users u ON p.bibliothecaire_id = u.id 
                         WHERE $where_clause 
                         $order_clause";
                
                if (!empty($params)) {
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();
                    $result = $stmt->get_result();
                } else {
                    $result = $conn->query($query);
                }
                
                if ($result && $result->num_rows > 0):
                    while ($product = $result->fetch_assoc()):
                ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo sanitizeInput($product['image_url']); ?>" 
                                     alt="<?php echo sanitizeInput($product['title']); ?>"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="product-placeholder" style="display: none;">📦</div>
                            <?php else: ?>
                                <div class="product-placeholder">📦</div>
                            <?php endif; ?>
                            
                            <?php if ($product['stock'] <= 5): ?>
                                <div class="stock-badge">Stock limité</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-info">
                            <h3><?php echo sanitizeInput($product['title']); ?></h3>
                            <p class="product-category"><?php echo sanitizeInput($product['category_name']); ?></p>
                            <p class="product-description">
                                <?php echo sanitizeInput(substr($product['description'], 0, 100)); ?>
                                <?php echo strlen($product['description']) > 100 ? '...' : ''; ?>
                            </p>
                            <p class="product-seller">Par: <?php echo sanitizeInput($product['bibliothecaire_name']); ?></p>
                            
                            <?php if ($product['review_count'] > 0): ?>
                                <div class="product-rating">
                                    <div class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <span class="star <?php echo $i <= round($product['avg_rating']) ? 'filled' : ''; ?>">⭐</span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="rating-text">(<?php echo $product['review_count']; ?> avis)</span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="product-footer">
                                <div class="price-stock">
                                    <span class="product-price"><?php echo number_format($product['price'], 2); ?> <?php echo CURRENCY; ?></span>
                                    <span class="stock-info">Stock: <?php echo $product['stock']; ?></span>
                                </div>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Voir détails</a>
                            </div>
                        </div>
                    </div>
                <?php 
                    endwhile;
                else:
                ?>
                    <div class="no-products">
                        <div class="no-products-icon">🔍</div>
                        <h3>Aucun produit trouvé</h3>
                        <p>Aucun produit ne correspond à vos critères de recherche.</p>
                        <a href="products.php" class="btn btn-secondary">Voir tous les produits</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="products-info">
                    <p><?php echo $result->num_rows; ?> produit(s) trouvé(s)</p>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script src="script.js"></script>
</body>
</html>
