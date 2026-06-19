<?php
// PHP CLI Integration and System Test Suite for Mascotiendas
require_once __DIR__ . '/../config/db.php';

$baseUrl = 'http://localhost/mascotiendas/';
$cookieJar = 'C:/Users/obal_/.gemini/antigravity/scratch/cookies.txt';

// Ensure cookie jar is empty at the start
if (file_exists($cookieJar)) {
    unlink($cookieJar);
}

$assertionsCount = 0;
$passedCount = 0;
$failedCount = 0;
$failures = [];

function assertEqual($actual, $expected, $message = '') {
    global $assertionsCount, $passedCount, $failedCount, $failures;
    $assertionsCount++;
    if ($actual === $expected) {
        $passedCount++;
        echo "  [PASS] $message\n";
    } else {
        $failedCount++;
        $failMsg = "  [FAIL] $message (Expected: " . print_r($expected, true) . ", Got: " . print_r($actual, true) . ")";
        echo "$failMsg\n";
        $failures[] = $failMsg;
    }
}

function assertNotEmpty($value, $message = '') {
    global $assertionsCount, $passedCount, $failedCount, $failures;
    $assertionsCount++;
    if (!empty($value)) {
        $passedCount++;
        echo "  [PASS] $message\n";
    } else {
        $failedCount++;
        $failMsg = "  [FAIL] $message (Value is empty)";
        echo "$failMsg\n";
        $failures[] = $failMsg;
    }
}

function makeRequest($endpoint, $method = 'GET', $data = null, $asJson = false, $csrfOverride = true) {
    global $baseUrl, $cookieJar;
    static $csrfToken = null;

    if ($method !== 'GET') {
        if ($csrfToken === null) {
            $csrfCh = curl_init();
            curl_setopt($csrfCh, CURLOPT_URL, $baseUrl . 'index.php');
            curl_setopt($csrfCh, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($csrfCh, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($csrfCh, CURLOPT_COOKIEJAR, $cookieJar);
            curl_setopt($csrfCh, CURLOPT_COOKIEFILE, $cookieJar);
            curl_setopt($csrfCh, CURLOPT_TIMEOUT, 10);
            $csrfRes = curl_exec($csrfCh);
            curl_close($csrfCh);
            if (preg_match('/window\.MT_CSRF_TOKEN\s*=\s*"([^"]*)"/', $csrfRes, $matches)) {
                $csrfToken = $matches[1];
            }
        }
    }

    $url = $baseUrl . $endpoint;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $headers = [];
    if ($method !== 'GET') {
        if ($csrfOverride === true) {
            if ($csrfToken !== null) {
                $headers[] = 'X-CSRF-Token: ' . $csrfToken;
            }
        } elseif (is_string($csrfOverride)) {
            $headers[] = 'X-CSRF-Token: ' . $csrfOverride;
        }
    }

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data !== null) {
            if ($asJson) {
                $payload = json_encode($data);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                $headers[] = 'Content-Type: application/json';
                $headers[] = 'Content-Length: ' . strlen($payload);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
    } else {
        if ($data !== null) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($data);
            curl_setopt($ch, CURLOPT_URL, $url);
        }
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // If logout, invalidate the cached token so it will be refetched in subsequent requests
    if ($method === 'POST' && strpos($endpoint, 'auth.php') !== false) {
        $action = '';
        if ($asJson && is_array($data)) {
            $action = $data['action'] ?? '';
        } elseif (!$asJson) {
            if (is_array($data)) {
                $action = $data['action'] ?? '';
            } else {
                parse_str($data, $parsedData);
                $action = $parsedData['action'] ?? '';
            }
        }
        if ($action === 'logout') {
            $csrfToken = null;
        }
    }

    return [
        'code' => $httpCode,
        'body' => $response,
        'json' => json_decode($response, true)
    ];
}

function makeUploadRequest($endpoint, $filePath, $mimeType, $fileName, $productId) {
    global $baseUrl, $cookieJar;
    static $csrfToken = null;

    if ($csrfToken === null) {
        $csrfCh = curl_init();
        curl_setopt($csrfCh, CURLOPT_URL, $baseUrl . 'index.php');
        curl_setopt($csrfCh, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($csrfCh, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($csrfCh, CURLOPT_COOKIEJAR, $cookieJar);
        curl_setopt($csrfCh, CURLOPT_COOKIEFILE, $cookieJar);
        curl_setopt($csrfCh, CURLOPT_TIMEOUT, 10);
        $csrfRes = curl_exec($csrfCh);
        curl_close($csrfCh);
        if (preg_match('/window\.MT_CSRF_TOKEN\s*=\s*"([^"]*)"/', $csrfRes, $matches)) {
            $csrfToken = $matches[1];
        }
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieJar);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieJar);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_POST, true);

    $fields = [
        'action' => 'imagen_upload',
        'producto_id' => $productId,
        'imagen' => new CURLFile($filePath, $mimeType, $fileName)
    ];
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

    $headers = [];
    if ($csrfToken !== null) {
        $headers[] = 'X-CSRF-Token: ' . $csrfToken;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $response,
        'json' => json_decode($response, true)
    ];
}


echo "===============================================\n";
echo "   STARTING MASCOTIENDAS MASSIVE SYSTEM TESTS\n";
echo "===============================================\n\n";

$timestamp = time();
$testUserEmail = "test_runner_{$timestamp}@mascotiendas.cl";
$testUserPassword = "Password123!";
$testUserName = "RunnerName";
$testUserApellido = "RunnerLastName";
$testUserPhone = "+56999999999";
$testNewsletterEmail = "test_news_{$timestamp}@example.com";

// STEP 1: PUBLIC CATALOG APIS
echo "--- Step 1: Testing Public Catalog APIs ---\n";

// 1.1 Categories
$res = makeRequest('api/productos.php?action=categorias');
assertEqual($res['code'], 200, "Categories endpoint response is HTTP 200");
assertNotEmpty($res['json'], "Categories response is not empty");
assertEqual(is_array($res['json']), true, "Categories list is an array");

// 1.1b Community Content
$res = makeRequest('api/contenido.php?action=comunidad');
assertEqual($res['code'], 200, "Community content response is HTTP 200");
assertEqual(is_array($res['json']), true, "Community content is an array");
assertNotEmpty($res['json'], "Community content has seeded items");

// 1.2 Featured Products
$res = makeRequest('api/productos.php?action=destacados');
assertEqual($res['code'], 200, "Featured products response is HTTP 200");
assertEqual(is_array($res['json']), true, "Featured products list is an array");

// 1.3 List Products
$res = makeRequest('api/productos.php?action=list');
assertEqual($res['code'], 200, "List products response is HTTP 200");
assertNotEmpty($res['json']['productos'], "Product list has products");
$productId = null;
$productName = '';
foreach ($res['json']['productos'] as $prod) {
    if (!empty($prod['en_stock'])) {
        $productId = $prod['id'];
        $productName = $prod['nombre'];
        break;
    }
}
if (!$productId) {
    $firstProduct = $res['json']['productos'][0];
    $productId = $firstProduct['id'];
    $productName = $firstProduct['nombre'];
}
echo "Selected Product ID $productId ($productName) for further tests\n";

// 1.4 Product Details
$res = makeRequest("api/productos.php?action=detalle&id=$productId");
assertEqual($res['code'], 200, "Product details response is HTTP 200");
assertEqual($res['json']['id'], $productId, "Fetched details match selected product ID");

// STEP 2: NEWSLETTER API
echo "\n--- Step 2: Testing Newsletter Subscription ---\n";
$res = makeRequest('api/newsletter.php', 'POST', [
    'action' => 'subscribe',
    'email' => $testNewsletterEmail,
    'nombre' => 'Test News User'
]);
assertEqual($res['code'], 200, "Newsletter subscription response is HTTP 200");
assertEqual($res['json']['success'], true, "Subscription was successful");

// Attempt duplicate subscription
$res = makeRequest('api/newsletter.php', 'POST', [
    'action' => 'subscribe',
    'email' => $testNewsletterEmail,
    'nombre' => 'Test News User'
]);
assertEqual($res['json']['success'], true, "Duplicate subscription updates existing subscription successfully");

// Invalid email subscription
$res = makeRequest('api/newsletter.php', 'POST', [
    'action' => 'subscribe',
    'email' => 'invalid-email-format',
    'nombre' => 'Test'
]);
assertEqual($res['json']['success'], false, "Invalid email subscription returns success=false");

// STEP 3: USER AUTH FLOW
echo "\n--- Step 3: Testing User Auth Flow (Register, Login, Profile) ---\n";

// 3.1 Register
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'registro',
    'nombre' => $testUserName,
    'apellido' => $testUserApellido,
    'email' => $testUserEmail,
    'password' => $testUserPassword,
    'telefono' => $testUserPhone
]);
assertEqual($res['code'], 200, "Register endpoint returns HTTP 200");
assertEqual($res['json']['ok'], true, "Registration ok flag is true");
assertEqual($res['json']['nombre'], $testUserName, "Registration returns registered name");

// 3.2 Duplicate Registration
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'registro',
    'nombre' => $testUserName,
    'apellido' => $testUserApellido,
    'email' => $testUserEmail,
    'password' => $testUserPassword,
    'telefono' => $testUserPhone
]);
assertEqual($res['code'], 409, "Duplicate registration returns HTTP 409 Conflict");

// 3.3 Fetch Profile (Session Check)
$res = makeRequest('api/auth.php?action=perfil');
assertEqual($res['code'], 200, "Profile endpoint returns HTTP 200");
assertEqual($res['json']['logueado'], true, "Profile states user is logged in");
assertEqual($res['json']['usuario']['email'], $testUserEmail, "Profile email matches registered email");

// STEP 4: ADDRESS MANAGEMENT
echo "\n--- Step 4: Testing Address Management ---\n";

// 4.1 Save First Address
$res = makeRequest('api/direcciones.php', 'POST', [
    'action' => 'guardar',
    'alias' => 'Casa',
    'calle' => 'Avenida Siempreviva',
    'numero' => '742',
    'depto' => '',
    'ciudad' => 'Springfield',
    'region' => 'Metropolitana',
    'predeterminada' => '1'
]);
assertEqual($res['code'], 200, "Save first address returns HTTP 200");
assertEqual($res['json']['ok'], true, "Save address returns ok=true");

// Get address ID from profile
$res = makeRequest('api/auth.php?action=perfil');
$addresses = $res['json']['direcciones'];
assertEqual(count($addresses), 1, "User has exactly 1 address");
$firstAddressId = $addresses[0]['id'];

// 4.2 Save Second Address
$res = makeRequest('api/direcciones.php', 'POST', [
    'action' => 'guardar',
    'alias' => 'Trabajo',
    'calle' => 'Planta Nuclear',
    'numero' => '100',
    'depto' => 'Sector 7-G',
    'ciudad' => 'Springfield',
    'region' => 'Metropolitana',
    'predeterminada' => '0'
]);
assertEqual($res['code'], 200, "Save second address returns HTTP 200");

// Get second address ID
$res = makeRequest('api/auth.php?action=perfil');
$addresses = $res['json']['direcciones'];
assertEqual(count($addresses), 2, "User has exactly 2 addresses");
$secondAddressId = ($addresses[0]['id'] == $firstAddressId) ? $addresses[1]['id'] : $addresses[0]['id'];

// 4.3 Set Second Address as Predeterminada
$res = makeRequest('api/direcciones.php', 'POST', [
    'action' => 'predeterminada',
    'id' => $secondAddressId
]);
assertEqual($res['code'], 200, "Set default address returns HTTP 200");

// Verify default is changed
$res = makeRequest('api/auth.php?action=perfil');
foreach ($res['json']['direcciones'] as $dir) {
    if ($dir['id'] == $secondAddressId) {
        assertEqual($dir['predeterminada'], 1, "Second address is now default");
    } else {
        assertEqual($dir['predeterminada'], 0, "First address is not default anymore");
    }
}

// 4.4 Delete First Address
$res = makeRequest('api/direcciones.php', 'POST', [
    'action' => 'eliminar',
    'id' => $firstAddressId
]);
assertEqual($res['code'], 200, "Delete address returns HTTP 200");

// Verify deletion
$res = makeRequest('api/auth.php?action=perfil');
assertEqual(count($res['json']['direcciones']), 1, "User has exactly 1 address after deletion");
assertEqual($res['json']['direcciones'][0]['id'], $secondAddressId, "Remaining address is the second address");

// STEP 5: PRODUCT REVIEWS
echo "\n--- Step 5: Testing Reviews (Submission & Moderation) ---\n";
$commentText = "¡Un producto fabuloso! A mi perro le encantó. Probado en el test {$timestamp}.";
$res = makeRequest('api/reviews.php', 'POST', [
    'action' => 'crear',
    'producto_id' => $productId,
    'nombre' => $testUserName,
    'email' => $testUserEmail,
    'estrellas' => '5',
    'comentario' => $commentText
]);
assertEqual($res['code'], 200, "Submit review returns HTTP 200");
assertEqual($res['json']['ok'], true, "Submit review response is ok=true");

// Verify review is NOT visible publicly yet (needs admin approval)
$res = makeRequest("api/reviews.php?action=del_producto&producto_id={$productId}");
$publicReviews = $res['json']['reviews'] ?? [];
$reviewFoundPublicly = false;
foreach ($publicReviews as $rev) {
    if ($rev['comentario'] === $commentText) {
        $reviewFoundPublicly = true;
    }
}
assertEqual($reviewFoundPublicly, false, "Review is NOT visible publicly before approval");

// STEP 6: FAVORITES
echo "\n--- Step 6: Testing Favorites Toggle ---\n";

// 6.1 Toggle On
$res = makeRequest('api/favoritos.php', 'POST', [
    'action' => 'toggle',
    'producto_id' => $productId
]);
assertEqual($res['code'], 200, "Toggle favorite response is HTTP 200");
assertEqual($res['json']['favorito'], true, "Product is now in favorites");

// 6.2 Get Favorites List
$res = makeRequest('api/favoritos.php?action=lista');
assertEqual($res['code'], 200, "List favorites response is HTTP 200");
assertEqual(is_array($res['json']), true, "Favorites list is an array");
$favFound = false;
foreach ($res['json'] as $fav) {
    if ($fav['id'] == $productId) {
        $favFound = true;
    }
}
assertEqual($favFound, true, "Product is found in the favorites list");

// STEP 7: CART AND ORDER CREATION
echo "\n--- Step 7: Testing Cart & Order Creation ---\n";
$orderData = [
    'nombre' => $testUserName . ' ' . $testUserApellido,
    'email' => $testUserEmail,
    'telefono' => $testUserPhone,
    'direccion' => 'Planta Nuclear 100',
    'ciudad' => 'Springfield',
    'metodo_entrega' => 'delivery',
    'metodo_pago' => 'transferencia',
    'items' => [
        [
            'id' => $productId,
            'cantidad' => 2
        ]
    ]
];
$res = makeRequest('api/pedidos.php?action=crear', 'POST', $orderData, true);
assertEqual($res['code'], 200, "Create order returns HTTP 200");
assertEqual($res['json']['ok'], true, "Order creation was successful");
$orderId = $res['json']['pedido_id'];
$orderTotal = $res['json']['total'];
echo "Created Order ID $orderId with total $orderTotal\n";

// Fetch client's orders
$res = makeRequest('api/pedidos.php?action=mis');
assertEqual($res['code'], 200, "Fetch client's orders returns HTTP 200");
$orderFound = false;
foreach ($res['json'] as $ord) {
    if ($ord['id'] == $orderId) {
        $orderFound = true;
        assertEqual($ord['total'], $orderTotal, "Order total matches created total");
    }
}
assertEqual($orderFound, true, "Created order is found in client's orders list");

// Logout client
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'logout'
]);
assertEqual($res['code'], 200, "Client logout response is HTTP 200");

// Verify profile shows logged out
$res = makeRequest('api/auth.php?action=perfil');
assertEqual($res['json']['logueado'], false, "Profile confirms logged out status");

// STEP 8: ADMIN OPERATIONS
echo "\n--- Step 8: Testing Admin Operations ---\n";

// 8.1 Login as Admin
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'login',
    'email' => 'admin@mascotiendas.cl',
    'password' => 'Admin1234!'
]);
assertEqual($res['code'], 200, "Admin login response is HTTP 200");
assertEqual($res['json']['rol'], 'admin', "Logged in user role is admin");

// 8.2 Get Admin Stats
$res = makeRequest('api/admin.php?action=stats');
assertEqual($res['code'], 200, "Fetch stats response is HTTP 200");
assertNotEmpty($res['json']['ventas'], "Stats have ventas data");

// 8.3 Get Newsletter Subscribers
$res = makeRequest('api/admin.php?action=newsletter_list');
assertEqual($res['code'], 200, "Fetch newsletter list response is HTTP 200");
$newsFound = false;
foreach ($res['json'] as $sub) {
    if ($sub['email'] === $testNewsletterEmail) {
        $newsFound = true;
    }
}
assertEqual($newsFound, true, "Subscribed newsletter email is present in admin newsletter list");

// 8.4 Fetch Orders List
$res = makeRequest('api/admin.php?action=pedidos_list');
assertEqual($res['code'], 200, "Fetch admin orders list returns HTTP 200");
$adminOrderFound = false;
foreach ($res['json']['pedidos'] as $ord) {
    if ($ord['id'] == $orderId) {
        $adminOrderFound = true;
    }
}
assertEqual($adminOrderFound, true, "Created order is present in admin order list");

// 8.5 Find and Approve the Test Review
$res = makeRequest('api/admin.php?action=reviews_list&aprobado=0');
assertEqual($res['code'], 200, "Fetch pending reviews returns HTTP 200");
$pendingReviewId = null;
foreach ($res['json'] as $rev) {
    if ($rev['comentario'] === $commentText) {
        $pendingReviewId = $rev['id'];
        break;
    }
}
assertNotEmpty($pendingReviewId, "Found our pending review ID: $pendingReviewId");

if ($pendingReviewId) {
    $res = makeRequest('api/admin.php', 'POST', [
        'action' => 'review_aprobar',
        'id' => $pendingReviewId,
        'aprobado' => '1'
    ]);
    assertEqual($res['code'], 200, "Approve review response is HTTP 200");
    assertEqual($res['json']['ok'], true, "Approve review returns ok=true");
}

// 8.6 Admin Community CRUD testing
$res = makeRequest('api/admin.php?action=comunidad_list');
assertEqual($res['code'], 200, "Admin community list response is HTTP 200");
assertEqual(is_array($res['json']), true, "Admin community list is an array");

// Create new community content
$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'comunidad_save',
    'titulo' => 'Corrida de Prueba 2026',
    'descripcion' => 'Corrida de prueba para test automatizados.',
    'cta_texto' => 'Inscribirse',
    'enlace_url' => 'https://wa.me/56953793135',
    'activo' => '1'
]);
assertEqual($res['code'], 200, "Create community content response is HTTP 200");
assertEqual($res['json']['ok'], true, "Create community response returns ok=true");
$testComId = $res['json']['id'];

// Edit community content
$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'comunidad_save',
    'id' => $testComId,
    'titulo' => 'Corrida de Prueba 2026 - Actualizada',
    'descripcion' => 'Corrida de prueba para test automatizados.',
    'cta_texto' => 'Inscribirse',
    'enlace_url' => 'https://wa.me/56953793135',
    'activo' => '1'
]);
assertEqual($res['code'], 200, "Edit community content response is HTTP 200");
assertEqual($res['json']['ok'], true, "Edit community response returns ok=true");

// Delete community content
$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'comunidad_delete',
    'id' => $testComId
]);
assertEqual($res['code'], 200, "Delete community content response is HTTP 200");
assertEqual($res['json']['ok'], true, "Delete community response returns ok=true");

// Logout Admin
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'logout'
]);
assertEqual($res['code'], 200, "Admin logout response is HTTP 200");

// STEP 9: PUBLIC REVIEW VERIFICATION
echo "\n--- Step 9: Verifying Approved Review Publicly ---\n";
$res = makeRequest("api/reviews.php?action=del_producto&producto_id={$productId}");
$publicReviews = $res['json']['reviews'] ?? [];
$reviewFoundPublicly = false;
foreach ($publicReviews as $rev) {
    if ($rev['comentario'] === $commentText) {
        $reviewFoundPublicly = true;
        assertEqual($rev['estrellas'], 5, "Approved review stars match");
        assertEqual($rev['nombre'], $testUserName, "Approved review reviewer name matches");
    }
}
assertEqual($reviewFoundPublicly, true, "Review is now publicly visible after admin approval");

// STEP 10: CSRF BLOCKING TESTS (NEGATIVE SECURITY VERIFICATION)
echo "\n--- Step 10: Testing Security Hardening (CSRF Negative Tests) ---\n";

// 10.1 Post request without X-CSRF-Token header
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'registro',
    'nombre' => 'HackerNoToken',
    'apellido' => 'Hack',
    'email' => 'hacker_notoken@mascotiendas.cl',
    'password' => 'Password123',
    'telefono' => '+56999999999'
], false, false); // csrfOverride = false suppresses the token
assertEqual($res['code'], 403, "POST request without X-CSRF-Token is blocked with HTTP 403");
assertEqual($res['json']['error'] ?? '', 'Acción no autorizada (CSRF Token inválido)', "Correct CSRF error message returned for missing token");

// 10.2 Post request with invalid CSRF token header
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'registro',
    'nombre' => 'HackerBadToken',
    'apellido' => 'Hack',
    'email' => 'hacker_badtoken@mascotiendas.cl',
    'password' => 'Password123',
    'telefono' => '+56999999999'
], false, 'bad_token_value_12345'); // csrfOverride = 'bad_token_value_12345'
assertEqual($res['code'], 403, "POST request with invalid X-CSRF-Token is blocked with HTTP 403");
assertEqual($res['json']['error'] ?? '', 'Acción no autorizada (CSRF Token inválido)', "Correct CSRF error message returned for bad token");


// STEP 11: PERFORMANCE TUNING VERIFICATION (GZIP & CACHE CONTROL HEADERS)
echo "\n--- Step 11: Checking Performance Tuning (Gzip & Cache) ---\n";

function getHeadersOfUrl($url, $sendGzip = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    if ($sendGzip) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Encoding: gzip']);
    }
    $response = curl_exec($ch);
    curl_close($ch);

    $headers = [];
    foreach (explode("\r\n", $response) as $line) {
        if (strpos($line, ':') !== false) {
            list($key, $val) = explode(':', $line, 2);
            $headers[strtolower(trim($key))] = trim($val);
        }
    }
    return $headers;
}

// 11.1 Check HTML Gzip compression (Optional warning if local Apache doesn't have mod_deflate loaded)
$headersHtml = getHeadersOfUrl($baseUrl . 'index.php', true);
if (isset($headersHtml['content-encoding']) && strpos($headersHtml['content-encoding'], 'gzip') !== false) {
    echo "  [INFO] Gzip compression is active on index.php (content-encoding: gzip)\n";
} else {
    echo "  [WARNING] Gzip compression is not active on the local Apache server. (Verify that mod_deflate is enabled in Apache's httpd.conf)\n";
}

// 11.2 Check Asset Cache-Control/Expires headers
$headersAsset = getHeadersOfUrl($baseUrl . 'assets/app.js');
if (isset($headersAsset['expires']) || (isset($headersAsset['cache-control']) && strpos($headersAsset['cache-control'], 'max-age') !== false)) {
    echo "  [INFO] Cache-Control/Expires headers are active on assets/app.js\n";
} else {
    echo "  [WARNING] Cache-Control/Expires headers are not active on the local Apache server. (Verify that mod_expires is enabled in Apache's httpd.conf)\n";
}

// STEP 12: E-COMMERCE AUTOMATION & OPT-OUT BLACKLIST VERIFICATION
echo "\n--- Step 12: Testing E-commerce Marketing Automation & Blacklist ---\n";

// 12.1 Log in as Admin first to fetch the initial blacklist
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'login',
    'email' => 'admin@mascotiendas.cl',
    'password' => 'Admin1234!'
]);
assertEqual($res['code'], 200, "Admin login for marketing automation tests is HTTP 200");

// Fetch empty blacklist
$res = makeRequest('api/admin.php?action=blacklist_list');
assertEqual($res['code'], 200, "Fetch blacklist list is HTTP 200");
assertEqual(is_array($res['json']), true, "Blacklist is an array");

// 12.2 Simulate a guest clicking the unsubscribe (opt-out) link in their email
$optoutEmail = "test_optout_{$timestamp}@mascotiendas.cl";
$optoutToken = md5($optoutEmail . 'mascotiendas_marketing_salt_987');
$res = makeRequest("api/marketing.php?action=optout&email=" . urlencode($optoutEmail) . "&token=" . $optoutToken);
assertEqual($res['code'], 200, "Opt-out GET request succeeds with HTTP 200");
assertNotEmpty($res['body'], "Opt-out returns HTML confirmation body");

// 12.3 Fetch blacklist again and verify the email was added
$res = makeRequest('api/admin.php?action=blacklist_list');
$foundInBlacklist = false;
$blacklistItemId = null;
foreach ($res['json'] as $item) {
    if ($item['email'] === $optoutEmail) {
        $foundInBlacklist = true;
        $blacklistItemId = $item['id'];
        break;
    }
}
assertEqual($foundInBlacklist, true, "Unsubscribed email is now present in the admin blacklist");

// 12.4 Verify that trying to send a manual reminder to a blacklisted email is blocked
// Create a fake cart session for this email
$db = getPDO();
$db->prepare("INSERT INTO carritos_sesiones (token_sesion, email_invitado, datos_carrito, estado) VALUES (?, ?, ?, ?)")
   ->execute(["test_session_token_{$timestamp}", $optoutEmail, '{}', 'abandonado']);
$cartId = $db->lastInsertId();

$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'carrito_enviar_recordatorio',
    'id' => $cartId,
    'tipo' => 1
]);
assertEqual($res['code'], 400, "Sending manual reminder to blacklisted email returns HTTP 400");
assertEqual($res['json']['error'] ?? '', "Este cliente ha solicitado no recibir correos de marketing.", "Blacklisted manual reminder block returns correct error message");

// Clean up: Delete the test cart session
$db->prepare("DELETE FROM carritos_sesiones WHERE id = ?")->execute([$cartId]);

// 12.5 Remove from blacklist and verify it is empty again
$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'blacklist_delete',
    'id' => $blacklistItemId
]);
assertEqual($res['code'], 200, "Remove email from blacklist returns HTTP 200");

$res = makeRequest('api/admin.php?action=blacklist_list');
$foundInBlacklistAfterDelete = false;
foreach ($res['json'] as $item) {
    if ($item['email'] === $optoutEmail) {
        $foundInBlacklistAfterDelete = true;
        break;
    }
}
assertEqual($foundInBlacklistAfterDelete, false, "Email was successfully removed from the blacklist");

// 12.6 Test configuration toggle marketing_automatizacion_activo in cron
// Turn off marketing automation
$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'config_save',
    'clave' => 'marketing_automatizacion_activo',
    'valor' => '0'
]);
assertEqual($res['code'], 200, "Deactivating marketing_automatizacion_activo returns HTTP 200");

// Trigger cron job, it should exit with message indicating it is inactive
$res = makeRequest('api/cron_carritos.php');
assertEqual($res['code'], 200, "Cron execution is HTTP 200");
assertEqual($res['json']['success'] ?? null, false, "Cron reports success=false");
assertEqual($res['json']['message'] ?? '', "La automatización de marketing está desactivada globalmente.", "Cron returns correct inactive warning message");

// Turn marketing automation back ON
$res = makeRequest('api/admin.php', 'POST', [
    'action' => 'config_save',
    'clave' => 'marketing_automatizacion_activo',
    'valor' => '1'
]);
assertEqual($res['code'], 200, "Re-activating marketing_automatizacion_activo returns HTTP 200");

// Logout Admin
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'logout'
]);
assertEqual($res['code'], 200, "Admin logout at end of marketing automation tests returns HTTP 200");

// --- Step 13: Testing Security Hardening & Local Assets (Points 2, 3, 4) ---
echo "\n--- Step 13: Testing Security Hardening & Local Assets (Points 2, 3, 4) ---\n";

// 13.1 Local Assets Check (Point 4)
$indexHtml = file_get_contents(__DIR__ . '/../index.php');
$hasLocalTailwind = (strpos($indexHtml, 'assets/vendor/tailwindcss.js') !== false);
$hasLocalAlpine = (strpos($indexHtml, 'assets/vendor/alpine.js') !== false);
$hasCdnTailwind = (strpos($indexHtml, 'cdn.tailwindcss.com') !== false);
$hasCdnAlpine = (strpos($indexHtml, 'jsdelivr.net/npm/alpinejs') !== false);

assertEqual($hasLocalTailwind && $hasLocalAlpine, true, "index.php has local vendor scripts for Tailwind and Alpine");
assertEqual($hasCdnTailwind || $hasCdnAlpine, false, "index.php has no CDN references to Tailwind or Alpine");

$adminIndexHtml = file_get_contents(__DIR__ . '/../admin/index.php');
$adminHasLocalTailwind = (strpos($adminIndexHtml, 'assets/vendor/tailwindcss.js') !== false);
$adminHasLocalAlpine = (strpos($adminIndexHtml, 'assets/vendor/alpine.js') !== false);
assertEqual($adminHasLocalTailwind && $adminHasLocalAlpine, true, "admin/index.php has local vendor scripts");

// 13.2 Review Sanitization, Limits and XSS Prevention (Point 3)
$xssName = "TestUser<script>alert('xss')</script>" . str_repeat("A", 120);
$xssComment = "Cool review! <img src=x onerror=alert(1)> " . str_repeat("B", 1100);
$xssEmail = "xss_test_{$timestamp}@example.com";

$res = makeRequest('api/reviews.php', 'POST', [
    'action' => 'crear',
    'producto_id' => $productId,
    'nombre' => $xssName,
    'email' => $xssEmail,
    'estrellas' => '4',
    'comentario' => $xssComment
]);
assertEqual($res['code'], 200, "Submit long review with XSS returns HTTP 200");

// Fetch the review from DB directly to verify limits and sanitization
$db = getPDO();
$stmt = $db->prepare("SELECT * FROM reviews WHERE email = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$xssEmail]);
$createdReview = $stmt->fetch();

assertNotEmpty($createdReview, "Review with long/XSS content was saved in DB");
if ($createdReview) {
    assertEqual(strlen($createdReview['nombre']) <= 100, true, "Reviewer name truncated to max 100 characters");
    assertEqual(strlen($createdReview['comentario']) <= 1000, true, "Review comment truncated to max 1000 characters");
    assertEqual(strpos($createdReview['nombre'], '<script>'), false, "Reviewer name does not contain raw script tags");
    assertEqual(strpos($createdReview['comentario'], '<img'), false, "Review comment does not contain raw img tags");
    
    // Clean up
    $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$createdReview['id']]);
}

// 13.3 Image Upload Hardening (Point 2)
// Log in as admin
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'login',
    'email' => 'admin@mascotiendas.cl',
    'password' => 'Admin1234!'
]);
assertEqual($res['code'], 200, "Admin login for upload tests is HTTP 200");

// 13.3.1 Negative test: Upload an invalid image (text file) with a .jpg extension
$dummyTextFile = __DIR__ . '/../scratch/fake_image.jpg';
if (!file_exists(dirname($dummyTextFile))) {
    mkdir(dirname($dummyTextFile), 0755, true);
}
file_put_contents($dummyTextFile, "<?php echo 'Not an image!'; ?>");

$res = makeUploadRequest('api/admin.php', $dummyTextFile, 'image/jpeg', 'fake_image.jpg', $productId);
assertEqual($res['code'], 400, "Uploading a text file disguised as JPG is blocked");
assertEqual($res['json']['error'] ?? '', "Tipo de archivo no permitido (MIME-type inválido)", "Blocked with correct MIME error message");
if (file_exists($dummyTextFile)) {
    unlink($dummyTextFile);
}

// 13.3.2 Positive test: Upload a real PNG image (using base64 bytes)
$realImageFile = __DIR__ . '/../scratch/real_image.png';
file_put_contents($realImageFile, base64_decode("iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII="));

$res = makeUploadRequest('api/admin.php', $realImageFile, 'image/png', 'real_image.png', $productId);
assertEqual($res['code'], 200, "Uploading a valid 1x1 PNG image succeeds");
assertEqual($res['json']['ok'] ?? false, true, "Upload response reports ok=true");
$uploadedImgId = $res['json']['id'] ?? null;
$uploadedImgUrl = $res['json']['url'] ?? null;

if ($uploadedImgUrl) {
    $physicalPath = __DIR__ . '/../' . ltrim($uploadedImgUrl, '/');
    assertEqual(file_exists($physicalPath), true, "Uploaded image file exists on the filesystem");
    
    // Clean up
    if ($uploadedImgId) {
        $delRes = makeRequest('api/admin.php', 'POST', [
            'action' => 'imagen_delete',
            'imagen_id' => $uploadedImgId
        ]);
        assertEqual($delRes['code'], 200, "Delete uploaded image returns HTTP 200");
        assertEqual(file_exists($physicalPath), false, "Uploaded image file is deleted from filesystem");
    }
}
if (file_exists($realImageFile)) {
    unlink($realImageFile);
}

// Logout Admin
$res = makeRequest('api/auth.php', 'POST', [
    'action' => 'logout'
]);
assertEqual($res['code'], 200, "Admin logout at end of Step 13 returns HTTP 200");


echo "\n===============================================\n";
echo "            TEST EXECUTION SUMMARY\n";
echo "===============================================\n";
echo "Total Assertions: $assertionsCount\n";
echo "Passed: $passedCount\n";
echo "Failed: $failedCount\n";

if ($failedCount > 0) {
    echo "\nFailures encountered:\n";
    foreach ($failures as $f) {
        echo "$f\n";
    }
    exit(1);
} else {
    echo "\nAll tests passed successfully!\n";
    exit(0);
}
