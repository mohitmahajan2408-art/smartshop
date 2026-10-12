<?php
/**
 * ShopSmart India - Product Image Seeder
 * Populates authentic, high-resolution product images for all 39 products
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

$pdo = Database::getConnection();
if (!$pdo) {
    die("Database connection failed\n");
}

$images = [
    1 => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600&auto=format&fit=crop&q=80', // boAt headphones
    2 => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=600&auto=format&fit=crop&q=80', // Samsung Galaxy M14
    3 => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600&auto=format&fit=crop&q=80', // Noise smartwatch
    4 => 'https://images.unsplash.com/photo-1609592426867-6e44b9b0075c?w=600&auto=format&fit=crop&q=80', // Philips Power Bank
    5 => 'https://images.unsplash.com/photo-1558002038-1055907df827?w=600&auto=format&fit=crop&q=80', // Wipro Smart Plug
    6 => 'https://images.unsplash.com/photo-1602810318383-e386cc2a3ccf?w=600&auto=format&fit=crop&q=80', // Raymond shirt
    7 => 'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=600&auto=format&fit=crop&q=80', // FabIndia kurta
    8 => 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=600&auto=format&fit=crop&q=80', // Bata derby shoes
    9 => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?w=600&auto=format&fit=crop&q=80', // Fastrack men watch
    10 => 'https://images.unsplash.com/photo-1610030469983-98e550d6193c?w=600&auto=format&fit=crop&q=80', // FabIndia silk kurta
    11 => 'https://images.unsplash.com/photo-1543163521-1bf539c55dd2?w=600&auto=format&fit=crop&q=80', // Bata women sandals
    12 => 'https://images.unsplash.com/photo-1508615039623-a25605d2b022?w=600&auto=format&fit=crop&q=80', // Titan Raga rose gold watch
    13 => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=600&auto=format&fit=crop&q=80', // Wildcraft crossbody bag
    14 => 'https://images.unsplash.com/photo-1622290291468-a28f7a7dc6a8?w=600&auto=format&fit=crop&q=80', // FabIndia kids kurta
    15 => 'https://images.unsplash.com/photo-1514989940723-e8e51635b782?w=600&auto=format&fit=crop&q=80', // Bata kids sneakers
    16 => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600&auto=format&fit=crop&q=80', // Wildcraft school backpack
    17 => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&auto=format&fit=crop&q=80', // Aashirvaad wheat flour
    18 => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=600&auto=format&fit=crop&q=80', // Tata Sampann Toor dal
    19 => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&auto=format&fit=crop&q=80', // Tata Tea Premium
    20 => 'https://images.unsplash.com/photo-1631451095765-2c91616fc9e6?w=600&auto=format&fit=crop&q=80', // Amul Cow Ghee
    21 => 'https://images.unsplash.com/photo-1584990347449-a292671c6674?w=600&auto=format&fit=crop&q=80', // Prestige Pressure Cooker
    22 => 'https://images.unsplash.com/photo-1570222094114-d054a817e56b?w=600&auto=format&fit=crop&q=80', // Philips Mixer Grinder
    23 => 'https://images.unsplash.com/photo-1582139329536-e7284fece509?w=600&auto=format&fit=crop&q=80', // Godrej safe locker
    24 => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=600&auto=format&fit=crop&q=80', // Himalaya neem face wash
    25 => 'https://images.unsplash.com/photo-1608248597359-00e9559c381c?w=600&auto=format&fit=crop&q=80', // Dabur Vatika hair oil
    26 => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?w=600&auto=format&fit=crop&q=80', // Wings of Fire book
    27 => 'https://images.unsplash.com/photo-1531346878377-a5be20888e57?w=600&auto=format&fit=crop&q=80', // Classmate notebook
    28 => 'https://images.unsplash.com/photo-1596461404969-9ae70f2830c1?w=600&auto=format&fit=crop&q=80', // Funskool activity walker
    29 => 'https://images.unsplash.com/photo-1515488042361-ee00e0ddd4e4?w=600&auto=format&fit=crop&q=80', // Himalaya baby care basket
    30 => 'https://images.unsplash.com/photo-1511886929837-354d827aae26?w=600&auto=format&fit=crop&q=80', // Nivia football
    31 => 'https://images.unsplash.com/photo-1598971639058-fab3c3109a00?w=600&auto=format&fit=crop&q=80', // Nivia skipping rope
    32 => 'https://images.unsplash.com/photo-1489824904134-891ab64532f1?w=600&auto=format&fit=crop&q=80', // Philips car LED bulb
    33 => 'https://images.unsplash.com/photo-1558981403-c5f9899a28bc?w=600&auto=format&fit=crop&q=80', // Fastrack bike phone holder
    34 => 'https://images.unsplash.com/photo-1589924691995-400dc9ecc119?w=600&auto=format&fit=crop&q=80', // Pedigree dog food
    35 => 'https://images.unsplash.com/photo-1535294435445-d7249524ef2e?w=600&auto=format&fit=crop&q=80', // Pedigree dog treats
    36 => 'https://images.unsplash.com/photo-1416879595882-3373a0480b5b?w=600&auto=format&fit=crop&q=80', // TrustBasket potting soil
    37 => 'https://images.unsplash.com/photo-1585320806297-9794b3e4eeae?w=600&auto=format&fit=crop&q=80', // TrustBasket watering can
    38 => 'https://images.unsplash.com/photo-1615485290382-441e4d049cb5?w=600&auto=format&fit=crop&q=80', // Dabur Chyawanprash
    39 => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&auto=format&fit=crop&q=80', // Omron BP monitor
];

// Secondary gallery images
$gallery = [
    1 => [
        'https://images.unsplash.com/photo-1546435770-a3e426bf472b?w=600&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1583394838336-acd977736f90?w=600&auto=format&fit=crop&q=80'
    ],
    2 => [
        'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600&auto=format&fit=crop&q=80',
        'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=600&auto=format&fit=crop&q=80'
    ],
    3 => [
        'https://images.unsplash.com/photo-1508685096489-7aacd43bd3b1?w=600&auto=format&fit=crop&q=80'
    ],
    6 => [
        'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?w=600&auto=format&fit=crop&q=80'
    ],
    10 => [
        'https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?w=600&auto=format&fit=crop&q=80'
    ],
    17 => [
        'https://images.unsplash.com/photo-1574323347407-f5e1ad6d020b?w=600&auto=format&fit=crop&q=80'
    ],
    21 => [
        'https://images.unsplash.com/photo-1556911220-e15b29be8c8f?w=600&auto=format&fit=crop&q=80'
    ]
];

$updateStmt = $pdo->prepare("UPDATE products SET image_url = ? WHERE id = ?");
$delGallery = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
$insGallery = $pdo->prepare("INSERT INTO product_images (product_id, image_url, is_primary, display_order) VALUES (?, ?, ?, ?)");

$updatedCount = 0;
foreach ($images as $id => $url) {
    $updateStmt->execute([$url, $id]);
    $updatedCount++;
    
    // Primary gallery item
    $delGallery->execute([$id]);
    $insGallery->execute([$id, $url, 1, 0]);
    
    // Additional gallery items if available
    if (isset($gallery[$id])) {
        $order = 1;
        foreach ($gallery[$id] as $gUrl) {
            $insGallery->execute([$id, $gUrl, 0, $order++]);
        }
    }
}

echo "Successfully updated {$updatedCount} products with real images and gallery photos.\n";
