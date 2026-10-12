<?php
/**
 * ShopSmart India - Secure 3-Step Checkout & Order Placement
 * BCA Semester V Field Project
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/header.php';

$db = Database::getConnection();
$userId = currentUserId();

// If user is not logged in, prompt to sign in or allow quick account creation during checkout
if (!$userId) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: ' . APP_URL . '/login.php?info=checkout');
    exit;
}

// Fetch user's cart items from DB
$cartStmt = $db->prepare("
    SELECT ci.quantity, p.id AS product_id, p.name, p.price, p.original_price, p.stock_quantity, p.image_url
    FROM cart_items ci
    JOIN products p ON ci.product_id = p.id
    WHERE ci.user_id = ? AND p.is_active = 1
");
$cartStmt->execute([$userId]);
$cartItems = $cartStmt->fetchAll();

if (empty($cartItems)) {
    header('Location: ' . APP_URL . '/cart.php');
    exit;
}

// Compute authoritative subtotal
$subtotal = 0;
$totalSavings = 0;
foreach ($cartItems as $item) {
    $qty = min((int)$item['quantity'], (int)$item['stock_quantity']);
    $subtotal += $item['price'] * $qty;
    if ($item['original_price'] > $item['price']) {
        $totalSavings += ($item['original_price'] - $item['price']) * $qty;
    }
}

$deliveryFee = ($subtotal >= FREE_SHIPPING_THRESHOLD) ? 0 : STANDARD_SHIPPING_FEE;
$grandTotal = $subtotal + $deliveryFee;

// Fetch saved addresses
$addrStmt = $db->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$userId]);
$savedAddresses = $addrStmt->fetchAll();

$errorMsg = '';

// Handle Order Placement POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!verifyCsrf()) {
        $errorMsg = 'Security validation failed. Please refresh and try again.';
    } else {
        // Collect & validate address
        $useSaved = !empty($_POST['saved_address_id']);
        $addressData = [];

        if ($useSaved) {
            $addrId = (int)$_POST['saved_address_id'];
            $chkAddr = $db->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
            $chkAddr->execute([$addrId, $userId]);
            $selAddr = $chkAddr->fetch();
            if (!$selAddr) {
                $errorMsg = 'Selected delivery address not found.';
            } else {
                $addressData = [
                    'full_name' => $selAddr['full_name'],
                    'phone' => $selAddr['phone'],
                    'flat_house' => $selAddr['flat_house'],
                    'street_area' => $selAddr['street_area'],
                    'landmark' => $selAddr['landmark'],
                    'city' => $selAddr['city'],
                    'state' => $selAddr['state'],
                    'pincode' => $selAddr['pincode'],
                ];
            }
        } else {
            $fullName = trim($_POST['full_name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $flat = trim($_POST['flat_house'] ?? '');
            $street = trim($_POST['street_area'] ?? '');
            $landmark = trim($_POST['landmark'] ?? '');
            $city = trim($_POST['city'] ?? '');
            $state = trim($_POST['state'] ?? '');
            $pincode = trim($_POST['pincode'] ?? '');

            if (empty($fullName) || empty($phone) || empty($flat) || empty($street) || empty($city) || empty($state) || empty($pincode)) {
                $errorMsg = 'Please complete all required address fields.';
            } elseif (!preg_match('/^[6-9]\d{9}$/', $phone)) {
                $errorMsg = 'Please enter a valid 10-digit Indian mobile number.';
            } elseif (!preg_match('/^\d{6}$/', $pincode)) {
                $errorMsg = 'Please enter a valid 6-digit Indian PIN code.';
            } else {
                $addressData = [
                    'full_name' => $fullName,
                    'phone' => $phone,
                    'flat_house' => $flat,
                    'street_area' => $street,
                    'landmark' => $landmark,
                    'city' => $city,
                    'state' => $state,
                    'pincode' => $pincode,
                ];

                // Optionally save address to user's address book
                if (!empty($_POST['save_address'])) {
                    $insAddr = $db->prepare("
                        INSERT INTO addresses (user_id, full_name, phone, flat_house, street_area, landmark, city, state, pincode, is_default)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
                    ");
                    $insAddr->execute([$userId, $fullName, $phone, $flat, $street, $landmark, $city, $state, $pincode]);
                }
            }
        }

        $paymentMethod = $_POST['payment_method'] ?? 'upi';
        if (!in_array($paymentMethod, ['upi', 'card', 'netbanking', 'cod'], true)) {
            $paymentMethod = 'upi';
        }

        // Process Transactional Order Creation
        if (empty($errorMsg)) {
            try {
                $db->beginTransaction();

                // 1. Re-validate inventory with FOR UPDATE
                foreach ($cartItems as $item) {
                    $stockCheck = $db->prepare("SELECT stock_quantity, price FROM products WHERE id = ? FOR UPDATE");
                    $stockCheck->execute([$item['product_id']]);
                    $row = $stockCheck->fetch();

                    if (!$row || $row['stock_quantity'] < $item['quantity']) {
                        throw new Exception("Product '{$item['name']}' has insufficient stock. Please adjust quantity.");
                    }
                }

                // 2. Generate unique order number
                $orderNumber = generateOrderNumber();
                $paymentStatus = ($paymentMethod === 'cod') ? 'pending' : 'completed';
                $trackingNum = 'EXP-' . strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $addressData['city']), 0, 3)) . '-' . rand(10000, 99999);
                $estimatedDelivery = date('Y-m-d', strtotime('+3 days'));

                // 3. Insert order record
                $orderStmt = $db->prepare("
                    INSERT INTO orders (order_number, user_id, subtotal, delivery_fee, discount_amount, total_amount, payment_method, payment_status, order_status, delivery_address_json, tracking_number, courier_name, estimated_delivery, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?, ?, 'ShopSmart Express', ?, NOW())
                ");
                $orderStmt->execute([
                    $orderNumber,
                    $userId,
                    $subtotal,
                    $deliveryFee,
                    $totalSavings,
                    $grandTotal,
                    $paymentMethod,
                    $paymentStatus,
                    json_encode($addressData),
                    $trackingNum,
                    $estimatedDelivery
                ]);
                $orderId = (int)$db->lastInsertId();

                // 4. Insert order items & reduce stock
                $itemStmt = $db->prepare("
                    INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total_price)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stockUpdate = $db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $invStmt = $db->prepare("INSERT INTO inventory_movements (product_id, change_qty, reason, reference_id, created_at) VALUES (?, ?, 'order_sale', ?, NOW())");

                foreach ($cartItems as $ci) {
                    $itemTotal = $ci['price'] * $ci['quantity'];
                    $itemStmt->execute([$orderId, $ci['product_id'], $ci['name'], $ci['price'], $ci['quantity'], $itemTotal]);
                    $stockUpdate->execute([$ci['quantity'], $ci['product_id']]);
                    $invStmt->execute([$ci['product_id'], -$ci['quantity'], $orderNumber]);
                }

                // 5. Status history
                $histStmt = $db->prepare("INSERT INTO order_status_history (order_id, status, notes, created_at) VALUES (?, 'Confirmed', ?, NOW())");
                $histStmt->execute([$orderId, "Order placed successfully via " . strtoupper($paymentMethod) . " (Demo Payment)"]);

                // 6. Clear cart
                $clearStmt = $db->prepare("DELETE FROM cart_items WHERE user_id = ?");
                $clearStmt->execute([$userId]);

                $db->commit();

                // Redirect to success confirmation
                header("Location: " . APP_URL . "/order_success.php?id=" . $orderId);
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                $errorMsg = "Order could not be processed: " . $e->getMessage();
            }
        }
    }
}

// Indian States and UTs list for address form
$indianStates = [
    'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat', 
    'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh', 
    'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab', 
    'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh', 'Uttarakhand', 'West Bengal',
    'Delhi NCR', 'Chandigarh', 'Jammu and Kashmir', 'Ladakh', 'Puducherry'
];
?>

<div class="container" style="padding: 32px 16px;">
  <h1 style="font-size:24px;color:var(--primary-navy);margin-bottom:24px;">
    <i class="bi bi-shield-lock-fill" style="color:var(--accent-green);"></i> <?= e(__('checkout_title')) ?>
  </h1>

  <?php if ($errorMsg): ?>
    <div style="background:#fef2f2;border-left:4px solid #ef4444;color:#991b1b;padding:14px;border-radius:6px;margin-bottom:24px;font-weight:600;">
      ✕ <?= e($errorMsg) ?>
    </div>
  <?php endif; ?>

  <form action="<?= APP_URL ?>/checkout.php" method="POST" id="checkout-form">
    <?= csrfField() ?>
    <input type="hidden" name="action" value="place_order">

    <div style="display:grid;grid-template-columns:1fr 380px;gap:32px;align-items:flex-start;">
      <!-- Steps 1 & 2 -->
      <div style="display:flex;flex-direction:column;gap:24px;">
        
        <!-- Step 1: Delivery Address -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;">
          <h2 style="font-size:18px;color:var(--primary-navy);margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <span style="background:var(--primary-orange);color:#fff;width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">1</span>
            <?= e(__('step_address')) ?>
          </h2>

          <?php if (!empty($savedAddresses)): ?>
            <div style="margin-bottom:20px;">
              <div style="font-size:13px;font-weight:700;color:var(--text-secondary);margin-bottom:10px;">Select a saved delivery address:</div>
              <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:12px;">
                <?php foreach ($savedAddresses as $addr): ?>
                  <label style="border:2px solid #e2e8f0;border-radius:10px;padding:14px;display:flex;gap:12px;cursor:pointer;background:#f8fafc;" class="addr-card">
                    <input type="radio" name="saved_address_id" value="<?= $addr['id'] ?>" <?= $addr['is_default'] ? 'checked' : '' ?> onchange="toggleNewAddressForm(false)">
                    <div style="font-size:13px;line-height:1.4;">
                      <strong><?= e($addr['full_name']) ?></strong> (<?= e($addr['phone']) ?>)<br>
                      <?= e($addr['flat_house']) ?>, <?= e($addr['street_area']) ?><br>
                      <?= e($addr['city']) ?>, <?= e($addr['state']) ?> — <strong><?= e($addr['pincode']) ?></strong>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
              <div style="margin-top:12px;">
                <label style="font-size:13px;font-weight:600;color:var(--primary-orange);cursor:pointer;display:inline-flex;align-items:center;gap:6px;">
                  <input type="radio" name="saved_address_id" value="" onchange="toggleNewAddressForm(true)">
                  + Deliver to a different or new address
                </label>
              </div>
            </div>
          <?php endif; ?>

          <!-- New Address Form Fields -->
          <div id="new-address-fields" style="<?= !empty($savedAddresses) ? 'display:none;' : '' ?>">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
              <div class="form-group">
                <label class="form-label"><?= e(__('full_name')) ?> *</label>
                <input type="text" name="full_name" class="form-control" value="<?= e(currentUser()['name'] ?? '') ?>" placeholder="e.g. Rahul Verma">
              </div>
              <div class="form-group">
                <label class="form-label"><?= e(__('mobile_number')) ?> *</label>
                <input type="text" name="phone" class="form-control" value="<?= e(currentUser()['phone'] ?? '') ?>" placeholder="10-digit mobile" maxlength="10">
              </div>
            </div>

            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label"><?= e(__('flat_house')) ?> *</label>
              <input type="text" name="flat_house" class="form-control" placeholder="House no, Flat, Apartment, Building">
            </div>

            <div class="form-group" style="margin-bottom:14px;">
              <label class="form-label"><?= e(__('street_area')) ?> *</label>
              <input type="text" name="street_area" class="form-control" placeholder="Area, Colony, Street, Sector, Village">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-bottom:14px;">
              <div class="form-group">
                <label class="form-label"><?= e(__('city')) ?> *</label>
                <input type="text" name="city" class="form-control" placeholder="e.g. Pune / Mumbai">
              </div>
              <div class="form-group">
                <label class="form-label"><?= e(__('state')) ?> *</label>
                <select name="state" class="form-control">
                  <?php foreach ($indianStates as $st): ?>
                    <option value="<?= e($st) ?>" <?= $st === 'Maharashtra' ? 'selected' : '' ?>><?= e($st) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label"><?= e(__('pincode')) ?> *</label>
                <input type="text" name="pincode" class="form-control" placeholder="6-digit PIN" maxlength="6">
              </div>
            </div>

            <label style="font-size:13px;display:flex;align-items:center;gap:8px;cursor:pointer;">
              <input type="checkbox" name="save_address" value="1" checked> <?= e(__('save_address')) ?>
            </label>
          </div>
        </div>

        <!-- Step 2: Payment Method -->
        <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;">
          <h2 style="font-size:18px;color:var(--primary-navy);margin-bottom:8px;display:flex;align-items:center;gap:8px;">
            <span style="background:var(--primary-orange);color:#fff;width:26px;height:26px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;">2</span>
            <?= e(__('step_payment')) ?>
          </h2>
          <div style="font-size:12.5px;color:#9a3412;background:#fff7ed;padding:8px 12px;border-radius:6px;margin-bottom:16px;border:1px solid #fed7aa;">
            ℹ️ <strong>Demonstration Mode:</strong> No real funds are deducted. Real bank UPI or Card credentials are never requested or stored.
          </div>

          <div style="display:flex;flex-direction:column;gap:14px;">
            <!-- UPI -->
            <label style="border:1.5px solid #cbd5e1;border-radius:10px;padding:16px;display:flex;gap:14px;cursor:pointer;" class="pay-option">
              <input type="radio" name="payment_method" value="upi" checked style="margin-top:4px;">
              <div style="flex:1;">
                <div style="font-weight:700;font-size:14.5px;color:var(--primary-navy);"><?= e(__('pay_upi')) ?></div>
                <div style="font-size:12.5px;color:#64748b;margin-top:2px;">Google Pay, PhonePe, Paytm, BHIM or any Indian UPI app</div>
                <div style="margin-top:10px;display:flex;gap:10px;align-items:center;">
                  <input type="text" placeholder="example@okhdfcbank" class="form-control" style="max-width:240px;padding:6px 10px;font-size:13px;">
                  <button type="button" class="btn-secondary" style="padding:6px 12px;font-size:12px;" onclick="ShopSmart.toast('UPI ID verified successfully! ✓', 'success')">Verify UPI ID</button>
                </div>
              </div>
            </label>

            <!-- Cards -->
            <label style="border:1.5px solid #cbd5e1;border-radius:10px;padding:16px;display:flex;gap:14px;cursor:pointer;" class="pay-option">
              <input type="radio" name="payment_method" value="card" style="margin-top:4px;">
              <div>
                <div style="font-weight:700;font-size:14.5px;color:var(--primary-navy);"><?= e(__('pay_card')) ?></div>
                <div style="font-size:12.5px;color:#64748b;margin-top:2px;">RuPay, Visa, Mastercard, Maestro domestic Indian cards</div>
              </div>
            </label>

            <!-- Net Banking -->
            <label style="border:1.5px solid #cbd5e1;border-radius:10px;padding:16px;display:flex;gap:14px;cursor:pointer;" class="pay-option">
              <input type="radio" name="payment_method" value="netbanking" style="margin-top:4px;">
              <div>
                <div style="font-weight:700;font-size:14.5px;color:var(--primary-navy);"><?= e(__('pay_netbanking')) ?></div>
                <div style="font-size:12.5px;color:#64748b;margin-top:2px;">SBI, HDFC Bank, ICICI Bank, Axis Bank, Kotak, PNB</div>
              </div>
            </label>

            <!-- Cash on Delivery -->
            <label style="border:1.5px solid #cbd5e1;border-radius:10px;padding:16px;display:flex;gap:14px;cursor:pointer;" class="pay-option">
              <input type="radio" name="payment_method" value="cod" style="margin-top:4px;">
              <div>
                <div style="font-weight:700;font-size:14.5px;color:var(--primary-navy);"><?= e(__('pay_cod')) ?></div>
                <div style="font-size:12.5px;color:#64748b;margin-top:2px;">Pay with cash or UPI QR code at your doorstep upon delivery</div>
              </div>
            </label>
          </div>
        </div>
      </div>

      <!-- Order Summary Column -->
      <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:var(--shadow-md);position:sticky;top:100px;">
        <h3 style="font-size:18px;font-weight:700;color:var(--primary-navy);margin-bottom:16px;border-bottom:1px solid #f1f5f9;padding-bottom:12px;">
          <?= e(__('step_review')) ?> (<?= count($cartItems) ?> items)
        </h3>

        <!-- Items preview list -->
        <div style="max-height:220px;overflow-y:auto;display:flex;flex-direction:column;gap:10px;margin-bottom:16px;padding-right:4px;">
          <?php foreach ($cartItems as $ci): ?>
            <div style="display:flex;justify-content:space-between;font-size:13px;align-items:center;">
              <span style="color:#0f172a;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:200px;">
                <?= (int)$ci['quantity'] ?>x <?= e($ci['name']) ?>
              </span>
              <span style="font-weight:700;color:var(--primary-navy);">
                <?= formatPriceINR($ci['price'] * $ci['quantity']) ?>
              </span>
            </div>
          <?php endforeach; ?>
        </div>

        <div style="display:flex;flex-direction:column;gap:10px;font-size:14px;border-top:1px solid #f1f5f9;padding-top:14px;margin-bottom:20px;">
          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;"><?= e(__('subtotal')) ?>:</span>
            <span style="font-weight:600;"><?= formatPriceINR($subtotal) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;">
            <span style="color:#64748b;"><?= e(__('delivery_charges')) ?>:</span>
            <span style="font-weight:600;color:<?= $deliveryFee === 0 ? 'var(--accent-green)' : '#0f172a' ?>;">
              <?= $deliveryFee === 0 ? 'FREE' : formatPriceINR($deliveryFee) ?>
            </span>
          </div>
          <?php if ($totalSavings > 0): ?>
            <div style="display:flex;justify-content:space-between;color:var(--accent-green);font-weight:600;">
              <span><?= e(__('you_saved')) ?>:</span>
              <span>-<?= formatPriceINR($totalSavings) ?></span>
            </div>
          <?php endif; ?>

          <hr style="border:none;border-top:1px solid #e2e8f0;margin:4px 0;">

          <div style="display:flex;justify-content:space-between;font-size:18px;font-weight:800;color:var(--primary-navy);">
            <span><?= e(__('total_amount')) ?>:</span>
            <span style="color:var(--primary-orange);"><?= formatPriceINR($grandTotal) ?></span>
          </div>
        </div>

        <button type="submit" class="btn-primary" style="width:100%;justify-content:center;padding:14px;font-size:16px;">
          <?= e(__('place_order')) ?>
        </button>

        <div style="margin-top:14px;font-size:11.5px;color:#94a3b8;text-align:center;line-height:1.4;">
          By placing this order, you agree to ShopSmart India Terms of Use and Privacy Policy.
        </div>
      </div>
    </div>
  </form>
</div>

<script>
function toggleNewAddressForm(show) {
  const fields = document.getElementById('new-address-fields');
  if (fields) fields.style.display = show ? 'block' : 'none';
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
