<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library - Accueil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav class="navbar">
            <div class="nav-container">
                <h1 class="logo"><a href="index.php">🖊IQRAA</a></h1>
                <ul class="nav-menu">
                    <li><a href="index.php" class="active">Accueil</a></li>
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

    <main>
        <section class="hero">
            <div class="hero-content">
                <h2>Bienvenue dans notre Library</h2>
                <p>Découvrez notre collection de livres, stylos, papeterie et bien plus</p>
                <a href="products.php" class="btn btn-primary">Voir les produits</a>
            </div>
        </section>

        <section class="categories">
            <div class="container">
                <h3>Nos Catégories</h3>
                <div class="category-grid">
                    <?php
                    $categories_query = "SELECT * FROM categories LIMIT 6";
                    $categories_result = $conn->query($categories_query);
                    
                    $category_icons = [
                        'LIVRES' => '📖',
                        'SCOLAIRE' => '🎒',
                        ' PAPIERS' => '📄',
                        ' IMPRIMANTES' => '🖨️',
                        'MOSHAFS QURAN' => '📕',
                        'CAHIERS' => '📒'
                    ];
                    
                    while ($category = $categories_result->fetch_assoc()):
                        $icon = $category_icons[$category['name']] ?? '📦';
                    ?>
                        <div class="category-card">
                            <h4><?php echo $icon; ?> <?php echo sanitizeInput($category['name']); ?></h4>
                            <p><?php echo sanitizeInput($category['description']); ?></p>
                            <a href="products.php?category=<?php echo $category['id']; ?>" class="btn btn-secondary">Voir produits</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </section>

        <section class="featured-products">
            <div class="container">
                <h3>Nos produits ✨</h3>
                <div class="products-grid">
                    <?php
                    $query = "SELECT p.*, c.name as category_name FROM products p 
                             JOIN categories c ON p.category_id = c.id 
                             WHERE p.status = 'available' AND p.stock > 0
                             ORDER BY p.created_at DESC LIMIT 6";
                    $result = $conn->query($query);
                    
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
                            </div>
                            <div class="product-info">
                                <h4><?php echo sanitizeInput($product['title']); ?></h4>
                                <p class="category"><?php echo sanitizeInput($product['category_name']); ?></p>
                                <p class="price"><?php echo number_format($product['price'], 2); ?> <?php echo CURRENCY; ?></p>
                                <a href="product_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">Voir détails</a>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <div class="no-products">
                            <p>Aucun produit disponible pour le moment.</p>
                            <?php if (isBibliothecaire()): ?>
                                <a href="dashboard.php" class="btn btn-primary">Ajouter des produits</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <section class="stats">
            <div class="container">
                <div class="stats-grid">
                    <?php
                    // Statistiques générales
                    $stats_query = "SELECT 
                        (SELECT COUNT(*) FROM products WHERE status = 'available') as total_products,
                        (SELECT COUNT(*) FROM users WHERE role = 'bibliothecaire') as total_sellers,
                        (SELECT COUNT(*) FROM orders) as total_orders,
                        (SELECT COUNT(*) FROM categories) as total_categories";
                    $stats_result = $conn->query($stats_query);
                    $stats = $stats_result->fetch_assoc();
                    ?>
                    <div class="stat-card">
                        <h3><?php echo $stats['total_products']; ?></h3>
                        <p>Produits disponibles</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['total_sellers']; ?></h3>
                        <p>Vendeurs</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['total_orders']; ?></h3>
                        <p>Commandes</p>
                    </div>
                    <div class="stat-card">
                        <h3><?php echo $stats['total_categories']; ?></h3>
                        <p>Catégories</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Contact</h4>
                    <p>📧 iqraastore@gmail.com</p>
                    <p>📞 +213 657967356</p>
                    <p>📍 EL hadjar,annaba, Algérie</p>
                </div>
                <div class="footer-section">
                    <h4>Horaires</h4>
                    <p> Dimanche  - judi : 7h - 20h</p>
                    <p>Samedi: 9h - 20h</p>
                    <p>Vendredi: Fermé</p>
                </div>
                <div class="footer-section">
                    <h4>Liens utiles</h4>
                    <ul>
                        <li><a href="products.php">Nos produits</a></li>
                        <li><a href="signup.php">Créer un compte</a></li>
                        <li><a href="login.php">Se connecter</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Livraison</h4>
                    <p>🚚 Livraison par ZR express</p>
                    <p>📦 58 willaya</p>
                    <p>⚡ Livraison rapide 24h-48h </p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 IQRAA store Library..</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
