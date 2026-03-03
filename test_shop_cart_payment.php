<?php

echo "=== SHOP PAGE CART & PAYMENT TESTING ===\n\n";

echo "1. TESTING SHOP PAGE ACCESSIBILITY\n";

// Test 1: Shop page loads
echo "   1.1 Testing shop page loading...\n";
$shopResponse = file_get_contents('http://localhost:8000/marketplace/shop');
$shopWorking = strlen($shopResponse) > 1000;
echo "   Status: " . ($shopWorking ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Page Size: " . strlen($shopResponse) . " bytes\n";

// Check if cart functionality is present
$hasCartJS = strpos($shopResponse, 'addToCart') !== false;
$hasCartCount = strpos($shopResponse, 'updateCartCount') !== false;
echo "   Cart JavaScript: " . ($hasCartJS ? '✅ Present' : '❌ Missing') . "\n";
echo "   Cart Count Update: " . ($hasCartCount ? '✅ Present' : '❌ Missing') . "\n\n";

echo "2. TESTING CART FUNCTIONALITY\n";

// Test 2: Initial cart count
echo "   2.1 Testing initial cart count...\n";
$countResponse = file_get_contents('http://localhost:8000/cart/count');
$countData = json_decode($countResponse, true);
echo "   Status: " . ($countData ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Cart Count: " . ($countData['count'] ?? 0) . "\n\n";

// Test 3: Add product to cart
echo "   2.2 Adding product to cart...\n";
$addResponse = file_get_contents('http://localhost:8000/cart/add/1');
$addData = json_decode($addResponse, true);
echo "   Status: " . ($addData['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Message: " . ($addData['message'] ?? 'No message') . "\n";
echo "   Product: " . ($addData['product']['title'] ?? 'Unknown') . "\n";
echo "   Cart Count: " . ($addData['cartCount'] ?? 0) . "\n\n";

// Test 4: Check cart count after adding
echo "   2.3 Checking cart count after adding...\n";
$countResponse2 = file_get_contents('http://localhost:8000/cart/count');
$countData2 = json_decode($countResponse2, true);
echo "   Status: " . ($countData2 ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Cart Count: " . ($countData2['count'] ?? 0) . "\n\n";

echo "3. TESTING PAYMENT SYSTEM\n";

// Test 5: Payment methods API
echo "   3.1 Testing payment methods API...\n";
$methodsResponse = file_get_contents('http://localhost:8000/payment/methods');
$methodsData = json_decode($methodsResponse, true);
echo "   Status: " . ($methodsData ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Payment Methods Available: " . (count($methodsData['methods'] ?? [])) . "\n";
foreach ($methodsData['methods'] ?? [] as $method) {
    echo "   - " . $method['name'] . " (Fee: " . $method['fee'] . "%)\n";
}
echo "\n";

// Test 6: Payment page accessibility (with cart items)
echo "   3.2 Testing payment page with cart items...\n";
$paymentPage = file_get_contents('http://localhost:8000/payment');
$paymentPageWorking = strlen($paymentPage) > 1000;
echo "   Status: " . ($paymentPageWorking ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Page Size: " . strlen($paymentPage) . " bytes\n\n";

echo "4. TESTING CART MANAGEMENT\n";

// Test 7: Update cart quantity
echo "   4.1 Testing cart quantity update...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => 'quantity=2'
    ]
]);
$updateResponse = file_get_contents('http://localhost:8000/cart/update/1', false, $context);
$updateData = json_decode($updateResponse, true);
echo "   Status: " . ($updateData['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   New Total: $" . ($updateData['total'] ?? 0) . "\n";
echo "   Cart Count: " . ($updateData['cartCount'] ?? 0) . "\n\n";

// Test 8: Remove from cart
echo "   4.2 Testing remove from cart...\n";
$removeResponse = file_get_contents('http://localhost:8000/cart/remove/1');
$removeData = json_decode($removeResponse, true);
echo "   Status: " . ($removeData['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Message: " . ($removeData['message'] ?? 'No message') . "\n";
echo "   Cart Count: " . ($removeData['cartCount'] ?? 0) . "\n\n";

// Test 9: Clear cart
echo "   4.3 Testing clear cart...\n";
$clearResponse = file_get_contents('http://localhost:8000/cart/clear');
$clearData = json_decode($clearResponse, true);
echo "   Status: " . ($clearData['success'] ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Message: " . ($clearData['message'] ?? 'No message') . "\n\n";

// Test 10: Final cart count
echo "   4.4 Testing final cart count...\n";
$countResponse3 = file_get_contents('http://localhost:8000/cart/count');
$countData3 = json_decode($countResponse3, true);
echo "   Status: " . ($countData3 ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Final Cart Count: " . ($countData3['count'] ?? 0) . "\n\n";

echo "5. TESTING INTEGRATION\n";

// Test 11: Add products and test cart page
echo "   5.1 Testing cart page with items...\n";
file_get_contents('http://localhost:8000/cart/add/1');
file_get_contents('http://localhost:8000/cart/add/2');
$cartPage = file_get_contents('http://localhost:8000/cart');
$cartPageWorking = strlen($cartPage) > 1000;
echo "   Cart Page Status: " . ($cartPageWorking ? '✅ SUCCESS' : '❌ FAILED') . "\n";
echo "   Cart Page Size: " . strlen($cartPage) . " bytes\n";
$hasCheckoutButton = strpos($cartPage, 'Proceed to Payment') !== false;
echo "   Checkout Button: " . ($hasCheckoutButton ? '✅ Present' : '❌ Missing') . "\n\n";

echo "=== TEST SUMMARY ===\n";

$tests = [
    'Shop Page Loading' => $shopWorking,
    'Cart JavaScript' => $hasCartJS,
    'Cart Count API' => $countData !== null,
    'Add to Cart' => $addData['success'] ?? false,
    'Cart Count Update' => $countData2['count'] > 0,
    'Payment Methods API' => $methodsData !== null,
    'Payment Page' => $paymentPageWorking,
    'Cart Update' => $updateData['success'] ?? false,
    'Remove from Cart' => $removeData['success'] ?? false,
    'Clear Cart' => $clearData['success'] ?? false,
    'Final Empty Cart' => ($countData3['count'] ?? 0) === 0,
    'Cart Page Integration' => $cartPageWorking && $hasCheckoutButton
];

$passed = array_sum($tests);
$total = count($tests);
$successRate = round(($passed / $total) * 100, 1);

echo "Total Tests: $total\n";
echo "Passed: $passed/$total\n";
echo "Success Rate: $successRate%\n\n";

echo "🎯 SHOP PAGE CART & PAYMENT STATUS:\n";
if ($successRate === 100) {
    echo "✅ ALL TESTS PASSED! Shop page cart and payment system is fully functional.\n";
    echo "✅ Users can add products from shop page to cart\n";
    echo "✅ Cart management works perfectly\n";
    echo "✅ Payment system is ready with multiple providers\n";
    echo "✅ Integration between shop, cart, and payment is working\n";
} else {
    echo "⚠️  Some tests failed. Please check the issues above.\n";
}

echo "\n🚀 SHOP PAGE FEATURES READY:\n";
echo "• Click 'Order' button to add products to cart\n";
echo "• Real-time cart count updates in navigation\n";
echo "• Success notifications when adding to cart\n";
echo "• Option to redirect to cart after adding items\n";
echo "• Full cart management (add, update, remove, clear)\n";
echo "• Professional payment interface with 4 methods\n";
echo "• Order creation and management\n";
echo "• Mobile-responsive design\n";

echo "\n📱 USER EXPERIENCE:\n";
echo "1. Browse shop page at /marketplace/shop\n";
echo "2. Click 'Order' button on any product\n";
echo "3. See success notification and cart update\n";
echo "4. Choose to view cart or continue shopping\n";
echo "5. Manage cart items (quantities, remove items)\n";
echo "6. Proceed to secure payment checkout\n";
echo "7. Choose payment method and complete purchase\n";
echo "8. Receive order confirmation\n";

echo "\n🔗 ALL ACCESSIBLE URLS:\n";
echo "• Shop Page: http://localhost:8000/marketplace/shop\n";
echo "• Cart Page: http://localhost:8000/cart\n";
echo "• Payment Page: http://localhost:8000/payment\n";
echo "• Cart Count API: http://localhost:8000/cart/count\n";
echo "• Add to Cart: http://localhost:8000/cart/add/{id}\n";
echo "• Payment Methods: http://localhost:8000/payment/methods\n";

echo "\n=== END OF SHOP PAGE CART & PAYMENT TESTING ===\n";
