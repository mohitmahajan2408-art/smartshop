<?php
/**
 * ShopSmart India - Shopping Cart
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$userId = currentUserId();

$cartItems = [];
$subtotal = 0;
$totalSavings = 0;

if ($db) {
    if ($userId) {
        // Logged in user: Fetch from database
        $stmt = $db->prepare("
            SELECT ci.id AS cart_item_id, ci.quantity, p.id AS product_id, p.name, p.slug, p.price, p.original_price, p.stock_quantity, p.image_url, c.name AS category_name
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            JOIN categories c ON p.category_id = c.id
            WHERE ci.user_id = ? AND p.is_active = 1
        ");
        $stmt->execute([$userId]);
        $cartItems = $stmt->fetchAll();
    } else {
        // Guest user: Fetch products corresponding to session cart
        if (!empty($_SESSION['guest_cart'])) {
            $ids = array_keys($_SESSION['guest_cart']);
            $placeholders = str_repeat('?,', count($ids) - 1) . '?';
            $stmt = $db->prepare("
                SELECT p.id AS product_id, p.name, p.slug, p.price, p.original_price, p.stock_quantity, p.image_url, c.name AS category_name
                FROM products p
                JOIN categories c ON p.category_id = c.id
                WHERE p.id IN ($placeholders) AND p.is_active = 1
            ");
            $stmt->execute($ids);
            $prods = $stmt->fetchAll();
            foreach ($prods as $p) {
                $p['quantity'] = $_SESSION['guest_cart'][$p['product_id']] ?? 1;
                $p['cart_item_id'] = $p['product_id'];
                $cartItems[] = $p;
            }
        }
    }
}

// Compute authoritative totals
foreach ($cartItems as &$item) {
    // Validate stock cap
    $item['quantity'] = min((int)$item['quantity'], (int)$item['stock_quantity']);
    $itemSubtotal = $item['price'] * $item['quantity'];
    $subtotal += $itemSubtotal;
    
    if ($item['original_price'] > $item['price']) {
        $totalSavings += ($item['original_price'] - $item['price']) * $item['quantity'];
    }
}
unset($item);

$deliveryFee = ($subtotal >= FREE_SHIPPING_THRESHOLD || $subtotal == 0) ? 0 : STANDARD_SHIPPING_FEE;
$grandTotal = $subtotal + $deliveryFee;
?>

<div class="container" style="padding: 32px 16px;">
  <h1 style="font-size:24px;color:var(--primary-navy);margin-bottom:24px;display:flex;align-items:center;gap:10px;">
    <i class="bi bi-cart3" style="color:var(--primary-orange);"></i> <?= e(__('shopping_cart')) ?>
    <span style="font-size:15px;color:#64748b;font-weight:400;">(<?= count($cartItems) ?> distinct items)</span>
  </h1>

  <?php if (!empty($cartItems)): ?>
    <div style="display:grid;grid-template-columns:1fr 360px;gap:32px;align-items:flex-start;">
      <!-- Cart Items List -->
      <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;">
        <div style="display:flex;flex-direction:column;gap:20px;">
          <?php foreach ($cartItems as $item): ?>
            <?php 
              $pId = (int)$item['product_id'];
              $pPrice = (float)$item['price'];
              $pOriginalPrice = (float)$item['original_price'];
              $pQty = (int)$item['quantity'];
              $pStock = (int)$item['stock_quantity'];
              $img = getProductImageUrl($item['image_url'], $item['name']);
            ?>
            <div style="display:flex;gap:20px;padding-bottom:20px;border-bottom:1px solid #f1f5f9;align-items:center;" id="cart-row-<?= $pId ?>">
              <!-- Product Image -->
              <div style="width:100px;height:100px;border:1px solid #e2e8f0;border-radius:10px;padding:8px;background:#f8fafc;flex-shrink:0;">
                <img src="<?= e($img) ?>" alt="<?= e($item['name']) ?>" style="width:100%;height:100%;object-fit:contain;">
              </div>

              <!-- Product Details -->
              <div style="flex:1;">
                <div style="font-size:12px;color:var(--text-muted);text-transform:uppercase;"><?= e($item['category_name']) ?></div>
                <a href="<?= APP_URL ?>/product.php?slug=<?= urlencode($item['slug']) ?>" style="font-size:15px;font-weight:700;color:var(--primary-navy);display:block;margin:2px 0 6px;">
                  <?= e($item['name']) ?>
                </a>

                <div style="font-size:12px;color:var(--accent-green);font-weight:600;margin-bottom:10px;">
                  <i class="bi bi-check-circle-fill"></i> In Stock (<?= $pStock ?> units available)
                </div>

                <!-- Quantity & Actions Row -->
                <div style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
                  <div style="display:flex;align-items:center;border:1px solid #cbd5e1;border-radius:6px;overflow:hidden;">
                    <button type="button" style="padding:4px 10px;background:#f8fafc;font-weight:700;" onclick="updateCartQty(<?= $pId ?>, <?= $pQty - 1 ?>)">-</button>
                    <span style="padding:4px 14px;font-weight:700;font-size:14px;"><?= $pQty ?></span>
                    <button type="button" style="padding:4px 10px;background:#f8fafc;font-weight:700;" onclick="updateCartQty(<?= $pId ?>, <?= $pQty + 1 ?>)" <?= ($pQty >= $pStock) ? 'disabled' : '' ?>>+</button>
                  </div>

                  <button type="button" onclick="removeCartItem(<?= $pId ?>)" style="color:#ef4444;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                    <i class="bi bi-trash"></i> <?= e(__('remove')) ?>
                  </button>

                  <button type="button" onclick="saveForLater(<?= $pId ?>)" style="color:var(--primary-navy);font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                    <i class="bi bi-bookmark"></i> <?= e(__('save_for_later')) ?>
                  </button>
                </div>
              </div>

              <!-- Item Total Price -->
              <div style="text-align:right;">
                <div style="font-size:18px;font-weight:800;color:var(--primary-navy);">
                  <?= formatPriceINR($pPrice * $pQty) ?>
                </div>
                <?php if ($pOriginalPrice > $pPrice): ?>
                  <div style="font-size:12px;color:#94a3b8;text-decoration:line-through;">
                    <?= formatPriceINR($pOriginalPrice * $pQty) ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
          <a href="<?= APP_URL ?>/products.php" style="color:var(--primary-orange);font-size:14px;font-weight:600;display:inline-flex;align-items:center;gap:6px;">
            <i class="bi bi-arrow-left"></i> <?= e(__('continue_shopping')) ?>
          </a>
        </div>
      </div>

      <!-- Order Summary Card -->
      <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:var(--shadow-md);">
        <h3 style="font-size:18px;font-weight:700;color:var(--primary-navy);margin-bottom:16px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          Order Summary
        </h3>

        <!-- Free Delivery Progress Bar -->
        <?php if ($subtotal < FREE_SHIPPING_THRESHOLD): ?>
          <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px;margin-bottom:18px;font-size:12.5px;">
            <div style="color:#9a3412;font-weight:600;">
              Add <?= formatPriceINR(FREE_SHIPPING_THRESHOLD - $subtotal) ?> more for <strong>FREE Delivery</strong>!
            </div>
            <div style="height:6px;background:#ffedd5;border-radius:3px;margin-top:8px;overflow:hidden;">
              <div style="height:100%;background:var(--primary-orange);width:<?= min(100, round(($subtotal / FREE_SHIPPING_THRESHOLD) * 100)) ?>%;"></div>
            </div>
          </div>
        <?php else: ?>
          <div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:8px;padding:10px;margin-bottom:18px;font-size:12.5px;color:#065f46;font-weight:600;">
            ✓ Your order qualifies for <strong>FREE Delivery</strong>!
          </div>
        <?php endif; ?>

        <div style="display:flex;flex-direction:column;gap:12px;font-size:14px;margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;"><?= e(__('subtotal')) ?>:</span>
            <span style="font-weight:600;color:#0f172a;"><?= formatPriceINR($subtotal) ?></span>
          </div>

          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;"><?= e(__('delivery_charges')) ?>:</span>
            <span style="font-weight:600;color:<?= $deliveryFee === 0 ? 'var(--accent-green)' : '#0f172a' ?>;">
              <?= $deliveryFee === 0 ? 'FREE' : formatPriceINR($deliveryFee) ?>
            </span>
          </div>

          <?php if ($totalSavings > 0): ?>
            <div style="display:flex;justify-content:space-between;color:var(--accent-green);">
              <span><?= e(__('you_saved')) ?>:</span>
              <span style="font-weight:700;">-<?= formatPriceINR($totalSavings) ?></span>
            </div>
          <?php endif; ?>

          <hr style="border:none;border-top:1px solid #e2e8f0;margin:6px 0;">

          <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:800;color:var(--primary-navy);">
            <span><?= e(__('total_amount')) ?>:</span>
            <span style="color:var(--primary-orange);"><?= formatPriceINR($grandTotal) ?></span>
          </div>
        </div>

        <a href="<?= APP_URL ?>/checkout.php" class="btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:16px;">
          <?= e(__('proceed_to_checkout')) ?> <i class="bi bi-arrow-right"></i>
        </a>

        <div style="margin-top:16px;text-align:center;font-size:12px;color:#94a3b8;">
          <i class="bi bi-shield-lock-fill"></i> Safe & Secure 256-Bit SSL Encrypted Checkout
        </div>
      </div>
    </div>
  <?php else: ?>
    <!-- Empty Cart State -->
    <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:64px 24px;text-align:center;max-width:600px;margin:auto;">
      <div style="font-size:56px;margin-bottom:16px;">🛒</div>
      <h2 style="font-size:22px;color:var(--primary-navy);margin-bottom:8px;"><?= e(__('cart_empty')) ?></h2>
      <p style="color:#64748b;font-size:14px;margin-bottom:24px;"><?= e(__('cart_empty_sub')) ?></p>
      <a href="<?= APP_URL ?>/products.php" class="btn-primary" style="display:inline-flex;">
        <i class="bi bi-bag"></i> Start Shopping
      </a>
    </div>
  <?php endif; ?>
</div>

<script>
async function updateCartQty(productId, newQty) {
  if (newQty < 1) {
    removeCartItem(productId);
    return;
  }
  const res = await fetch('<?= APP_URL ?>/api/cart.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '<?= csrfToken() ?>'
    },
    body: JSON.stringify({ action: 'update', product_id: productId, quantity: newQty })
  });
  const data = await res.json();
  if (data.success) {
    window.location.reload();
  } else {
    ShopSmart.toast(data.message, 'error');
  }
}

async function removeCartItem(productId) {
  if (!confirm('Remove this item from your cart?')) return;
  const res = await fetch('<?= APP_URL ?>/api/cart.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': '<?= csrfToken() ?>'
    },
    body: JSON.stringify({ action: 'remove', product_id: productId })
  });
  const data = await res.json();
  if (data.success) {
    window.location.reload();
  }
}

function saveForLater(productId) {
  ShopSmart.toggleWishlist(productId, {
    classList: { add: () => {}, remove: () => {} }
  }).then(() => {
    removeCartItem(productId);
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
