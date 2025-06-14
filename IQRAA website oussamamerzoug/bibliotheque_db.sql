-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 13 juin 2025 à 10:50
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bibliotheque_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `cart_items`
--

CREATE TABLE `cart_items` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `cart_items`
--

INSERT INTO `cart_items` (`id`, `user_id`, `product_id`, `quantity`, `added_at`) VALUES
(0, 8, 22, 1, '2025-06-12 20:05:57');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'SCOLAIRE', 'Stylos,couleurs,crayons,Règles,gommes et taille-crayons'),
(2, 'LIVRES', 'Livres académiques et littéraires'),
(3, ' PAPIERS', 'Fiches, cartons et papiers blancs'),
(4, ' IMPRIMANTES', 'Imprimantes et accessoires dimpression'),
(5, 'MOSHAFS QURAN', 'Qurans (Moushafs) avec les récitations Hafs et Warch , Rub Yassin Chapelet de glorification ....'),
(6, 'CAHIERS', 'Cahiers de coloriage, cahiers pour enfants, cahiers de dessin, cahiers multi-usages..');

-- --------------------------------------------------------

--
-- Structure de la table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(5) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_type` enum('domicile','bureau') NOT NULL,
  `delivery_address` text NOT NULL,
  `delivery_phone` varchar(13) NOT NULL,
  `delivery_wilaya` varchar(50) NOT NULL,
  `delivery_commune` varchar(50) NOT NULL,
  `ZR_tracking` varchar(50) DEFAULT NULL,
  `status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash_on_delivery') DEFAULT 'cash_on_delivery',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `bibliothecaire_id` int(11) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `stock` int(11) DEFAULT 1,
  `status` enum('available','sold') DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `products`
--

INSERT INTO `products` (`id`, `title`, `description`, `price`, `category_id`, `bibliothecaire_id`, `image_url`, `stock`, `status`, `created_at`) VALUES
(5, 'اقلام هايلايتر طقم 4 الوان', 'طقم اقلام هايلايتر مظهر للكتابة والتوضيح 4 الوان', 290.00, 1, 4, 'https://media.zid.store/18bdc83c-a79a-4f53-be44-a9013fc6f13b/0cdba9ea-a98e-4272-8502-c5f4e13bf01e.jpg', 4, 'available', '2025-06-10 13:04:33'),
(6, 'moshaf wrach almadina', 'Dimensions (L × l × H) : 10 × 14,5 × 1,8 cm\r\nNombre de pages : 640 pages\r\nÉpaisseur du papier : 20 g/m²\r\nPoids du livre : 300 g', 660.00, 5, 4, 'https://media.zid.store/thumbs/cdea19f6-b0f8-453a-91ed-a293ac37de30/a7c71767-acf4-4614-ba11-9c4c09fb18a0-thumbnail-1000x1000-70.jpeg', 0, 'available', '2025-06-10 13:37:04'),
(7, 'Imprimante Epson EcoTank L3211 ????️', 'Technologie : Jet d’encre EcoTank (réservoirs rechargeables)\r\n\r\nFonctions : Impression, copie, numérisation *(3-en-1)*\r\n\r\nVitesse d’impression :\r\n\r\nNoir : ~10 ppm | Couleur : ~5 ppm (pages par minute)\r\n\r\nRésolution : 5760 × 1440 dpi (qualité photo optimale)\r\n\r\nCapacité des réservoirs :\r\n\r\nNoir : ~70 ml | Couleurs : ~70 ml × 3 (cyan, magenta, jaune)\r\n\r\nConnectivité : USB 2.0 (sans Wi-Fi)\r\n\r\nPoids : 3,9 kg', 34000.00, 4, 4, 'https://media.zid.store/cdea19f6-b0f8-453a-91ed-a293ac37de30/5e3a5d5e-1437-476a-940a-064ea83a4f49.jpeg', 2, 'available', '2025-06-10 13:39:13'),
(8, 'Gomme blanche grande taille', 'Grande gomme blanche (standard)\r\n\r\nGomme blanche format maxi (pour un style plus commercial)\r\n\r\nGomme blanche extra-large (version plus descriptive)', 25.00, 1, 4, 'https://media.zid.store/thumbs/18bdc83c-a79a-4f53-be44-a9013fc6f13b/a4bbb708-454f-4567-87ea-033fabc78d08-thumbnail-1000x1000-70.jpg', 19, 'available', '2025-06-10 13:41:12'),
(9, 'TROUSSE', 'TROUSSE TRIPLE POCHES BLEU &quot;TECHNO&quot;', 480.00, 1, 4, 'https://technostationery.com/media/catalog/product/cache/0357bde545ff49ff327f5b2f4e2532a3/t/r/trousse-triple-poches-bleu-techno-ref-6868.jpg', 5, 'available', '2025-06-10 14:04:18'),
(10, 'ALBUM DESSIN', 'ALBUM PAPIER NOIR 30F 120g &quot;TECHNO&quot;', 450.00, 3, 4, 'https://technostationery.com/media/catalog/product/cache/0357bde545ff49ff327f5b2f4e2532a3/a/l/album-papier-noir-30f-120g-techno-0.jpg', 14, 'available', '2025-06-10 17:13:27'),
(11, 'PAPIER ONDULE', 'PAPIER ONDULE PAILETTE A3 PAQUET 10 COULEURS &quot;TECHNO&quot; REF: 5189', 185.00, 3, 4, 'https://technostationery.com/media/catalog/product/cache/0357bde545ff49ff327f5b2f4e2532a3/p/a/papier-ondule-pailette-a3-paquet-10-couleurs-techno-ref-5189-0.jpg', 11, 'available', '2025-06-10 17:14:51'),
(13, 'CAHIER TRAVAUX PRATIQUE 96p', 'Le cahier de travaux pratiques fait alterner des pages séyès à grands carreaux pour écrire les cours ou les leçons, et des pages blanches unies, de type papier canson, destinées aux exercices d’application et autres dessins illustratifs', 180.00, 6, 4, 'https://elhillal.com/wp-content/uploads/2020/08/1-7.jpg', 24, 'available', '2025-06-12 12:57:06'),
(14, 'CAHIER DE CLASSE', 'Le Cahier de classe pour les préparatoires et primaires de la première à la cinquième année\r\nsuit le programme scolaire', 380.00, 6, 4, 'https://elhillal.com/wp-content/uploads/2022/12/Classe-0.png', 20, 'available', '2025-06-12 12:58:14'),
(15, 'CAHIER PIQUE 96p', 'La piqûre à cheval est la méthode de reliure la plus simple.', 50.00, 6, 4, 'https://elhillal.com/wp-content/uploads/2020/08/4.png', 28, 'available', '2025-06-12 12:59:55'),
(16, 'CAHIER PIQUE 48p', 'La piqûre à cheval est la méthode de reliure la plus simple', 40.00, 6, 4, 'https://elhillal.com/wp-content/uploads/2020/08/9.png', 48, 'available', '2025-06-12 13:01:34'),
(17, 'CAHIER PIQUE 64p', 'La piqûre à cheval est la méthode de reliure la plus simple', 50.00, 6, 4, 'https://elhillal.com/wp-content/uploads/2020/08/10.png', 26, 'available', '2025-06-12 13:02:49'),
(18, 'RAM PAPIER A4 MULTICOPY 80g', 'It is Multifunction paper', 650.00, 3, 4, 'https://elhillal.com/wp-content/uploads/2020/08/2-1.jpg', 15, 'available', '2025-06-12 13:04:00'),
(19, 'PAPIER RAM Premium+ A4 80g', 'Le Papier ramette qui sublime vos impressions couleurs!\r\n\r\nHaute résolution Blancheur accrue pour un meilleur rendu des couleurs', 700.00, 3, 4, 'https://elhillal.com/wp-content/uploads/2020/08/Premium.jpg', 16, 'available', '2025-06-12 13:05:24'),
(20, 'RAM PAPIER A4 a-Plus DoubleCOPY 90g', 'A high-end paper from color laser paper copiers, Inkjet printers', 600.00, 3, 4, 'https://elhillal.com/wp-content/uploads/2020/08/2-2.jpg', 16, 'available', '2025-06-12 13:07:31'),
(21, 'PATE A MODELER 8 COULEURS GLITTER SOUS BLISTER LICENCE PAW PATROL FILLE', 'PATE A MODELER 8 COULEURS GLITTER SOUS BLISTER LICENCE PAW PATROL FILLE', 250.00, 1, 4, 'https://technostationery.com/media/catalog/product/cache/e6148d99ae2c39ce16283ee794dfce11/p/a/pate_a_modeler_8_couleurs_glitter_sous_blister_licence_paw_patrol_fille_techno_ref_7217.jpg', 26, 'available', '2025-06-12 13:09:34'),
(22, 'CRAYONS DE COULEUR', 'CRAYONS DE COULEUR WOW FLUO BOITE DE 12 COULEURS', 430.00, 1, 4, 'https://technostationery.com/media/catalog/product/cache/0357bde545ff49ff327f5b2f4e2532a3/c/r/crayons-de-couleur-wow-fluo-boite-de-12-couleurs-techno-ref-6567.jpg', 43, 'available', '2025-06-12 13:11:34'),
(23, 'stylo unomax gold', 'STYLUS GOLD\r\nMetal Ball Point Pens\r\nTip Size 0.7 mm\r\nInk Colours\r\nblue', 70.00, 1, 4, 'https://www.unomaxpens.com/public/images/products/STYLUS%20GOLD.png', 44, 'available', '2025-06-12 13:14:46'),
(24, 'كتاب التوحيد للكاتب شيخ الإسلام محمد بن عبدالوهاب باللغة الإنجليزية', 'لمؤلف\r\nمحمد بن عبد الوهاب\r\n\r\n الشيخ محمد بن عبد الوهاب، الفقيه والداعية الإسلامي البارز الذي عاش في القرن الثامن عشر\r\n\r\nحالة الكتاب\r\nجديد', 1500.00, 2, 4, 'https://books.ay7aaga.com/wp-content/uploads/2024/09/%D9%A2%D9%A0%D9%A2%D9%A4%D9%A0%D9%A9%D9%A1%D9%A2_%D9%A1%D9%A2%D9%A5%D9%A1%D9%A2%D9%A2-scaled.jpg.webp', 1, 'available', '2025-06-12 14:09:39');

-- --------------------------------------------------------

--
-- Structure de la table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `purchase_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `quantity` int(11) DEFAULT 1,
  `total_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `reviews`
--

CREATE TABLE `reviews` (
  `id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `buyer_id` int(11) DEFAULT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` >= 1 and `rating` <= 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('bibliothecaire','acheteur') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(4, 'oussama', 'oussama.mer@univ-annab.dz', '$2y$10$G33I5pyfa5zxRehuUxP5FuiEhpEzu/UE/bF6IxfAfWvrJ8MbYozwu', 'bibliothecaire', '2025-06-10 13:00:02');

-- --------------------------------------------------------

--
-- Structure de la table `wilayas`
--

CREATE TABLE `wilayas` (
  `id` int(11) NOT NULL,
  `code` varchar(2) NOT NULL,
  `name` varchar(50) NOT NULL,
  `delivery_price` decimal(10,2) DEFAULT 500.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Déchargement des données de la table `wilayas`
--

INSERT INTO `wilayas` (`id`, `code`, `name`, `delivery_price`) VALUES
(1, '01', 'Adrar', 600.00),
(2, '02', 'Chlef', 600.00),
(3, '03', 'Laghouat', 700.00),
(4, '04', 'Oum El Bouaghi', 500.00),
(5, '05', 'Batna', 600.00),
(6, '06', 'Béjaïa', 500.00),
(7, '07', 'Biskra', 650.00),
(8, '08', 'Béchar', 900.00),
(9, '09', 'Blida', 400.00),
(10, '10', 'Bouira', 500.00),
(11, '11', 'Tamanrasset', 1200.00),
(12, '12', 'Tébessa', 700.00),
(13, '13', 'Tlemcen', 600.00),
(14, '14', 'Tiaret', 600.00),
(15, '15', 'Tizi Ouzou', 500.00),
(16, '16', 'Alger', 300.00),
(17, '17', 'Djelfa', 650.00),
(18, '18', 'Jijel', 550.00),
(19, '19', 'Sétif', 550.00),
(20, '20', 'Saïda', 650.00),
(21, '21', 'Skikda', 3030.00),
(22, '22', 'Sidi Bel Abbès', 600.00),
(23, '23', 'Annaba', 0.00),
(24, '24', 'Guelma', 300.00),
(25, '25', 'Constantine', 550.00),
(26, '26', 'Médéa', 500.00),
(27, '27', 'Mostaganem', 550.00),
(28, '28', 'M\'Sila', 600.00),
(29, '29', 'Mascara', 600.00),
(30, '30', 'Ouargla', 800.00),
(31, '31', 'Oran', 500.00),
(32, '32', 'El Bayadh', 700.00),
(33, '33', 'Illizi', 1000.00),
(34, '34', 'Bordj Bou Arréridj', 550.00),
(35, '35', 'Boumerdès', 400.00),
(36, '36', 'El Tarf', 650.00),
(37, '37', 'Tindouf', 1100.00),
(38, '38', 'Tissemsilt', 600.00),
(39, '39', 'El Oued', 750.00),
(40, '40', 'Khenchela', 650.00),
(41, '41', 'Souk Ahras', 650.00),
(42, '42', 'Tipaza', 400.00),
(43, '43', 'Mila', 600.00),
(44, '44', 'Aïn Defla', 500.00),
(45, '45', 'Naâma', 750.00),
(46, '46', 'Aïn Témouchent', 550.00),
(47, '47', 'Ghardaïa', 750.00),
(48, '48', 'Relizane', 600.00);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `cart_items`
--
ALTER TABLE `cart_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_product` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `bibliothecaire_id` (`bibliothecaire_id`);

--
-- Index pour la table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Index pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `buyer_id` (`buyer_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `wilayas`
--
ALTER TABLE `wilayas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT pour la table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `wilayas`
--
ALTER TABLE `wilayas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Contraintes pour la table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`bibliothecaire_id`) REFERENCES `users` (`id`);

--
-- Contraintes pour la table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
