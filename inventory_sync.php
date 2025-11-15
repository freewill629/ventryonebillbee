<?php
// coded by vishnu
/**
 * Inventory Sync (LIVE / DRY-RUN)
 * - VentoryOne: set STK – Insgesamt to CSV total (cartons=0, loose=CSV)
 * - Billbee: apply safety deduction before update
 *
 * Run: php inventory_sync.php
 */

if (php_sapi_name() !== 'cli') die('Access denied.');
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Toggle default behaviour here: set to 'dry' to simulate or 'live' to push updates.
define('DEFAULT_RUN_MODE', 'dry');

// Determine whether log output should also be echoed to STDOUT.
$argv = $argv ?? [];
$forceEcho  = false;
$forceQuiet = false;
$forcedMode = null;
$showHelp  = false;
foreach (array_slice($argv, 1) as $arg) {
  if ($arg === '--verbose' || $arg === '-v') {
    $forceEcho = true;
  } elseif ($arg === '--quiet' || $arg === '-q') {
    $forceQuiet = true;
  } elseif ($arg === '--help' || $arg === '-h') {
    $showHelp = true;
  } elseif ($arg === '--dry-run' || $arg === '--dry') {
    $forcedMode = 'dry';
  } elseif ($arg === '--live') {
    $forcedMode = 'live';
  }
}

$runMode = $forcedMode ?? DEFAULT_RUN_MODE;
$runModeSource = $forcedMode !== null ? 'CLI override' : 'DEFAULT_RUN_MODE constant';
if (!in_array($runMode, ['dry', 'live'], true)) {
  fwrite(STDERR, "Invalid run mode: $runMode. Use 'dry' or 'live'." . PHP_EOL);
  exit(1);
}
define('SYNC_DRY_RUN', $runMode !== 'live');

$defaultEcho = true;
$envEcho = getenv('INVENTORY_SYNC_STDOUT');
if ($envEcho !== false) {
  $envEcho = strtolower(trim($envEcho));
  if (in_array($envEcho, ['0', 'false', 'no', 'off'], true)) {
    $defaultEcho = false;
  } else {
    $defaultEcho = true;
  }
}

$logEchoEnabled = $forceQuiet ? false : ($forceEcho ? true : $defaultEcho);

/* ================= CONFIG ================= */
define('FTP_HOST', '80.151.37.192');
define('FTP_PORT', 45801);
define('FTP_USER', 'feela');
define('FTP_PASS', '14022023#');
define('FTP_DIR',  'bestand'); // CSV directory on FTP

// CSV headers (from stock_quantity.csv)
define('CSV_SKU_COL', 'Variant SKU');
define('CSV_QTY_COL', 'Inventory Available: Cafol DE');

define('LOCAL_CSV_DIR', __DIR__ . '/csv_files');
// Each execution records a timestamped log file alongside this script for later review.
define('LOG_FILE', __DIR__ . '/sync_' . date('Ymd_His') . '.log');
define('LOG_ECHO_ENABLED', $logEchoEnabled);

// VentoryOne (2116 = cafol warehouse)
define('VO_BASE', 'https://app.ventory.one');
define('VO_TOKEN', '2d94eb4a8c3c2cef8ad628e3619591069b7156ed');
define('VO_WAREHOUSE_ID', 2116);

// Billbee
define('BILLBEE_API_URL', 'https://app.billbee.io/api/v1/');
define('BILLBEE_USER', 'info@feela.de');
define('BILLBEE_API_PASSWORD', 'LuPWibuTa7Ngn8m');
define('BILLBEE_API_KEY', '2FAA5DE0-18EA-4C4E-88EB-F0394109CF2E');
define('BILLBEE_VELOCITY_LOOKBACK_DAYS', 30);
define('BILLBEE_VELOCITY_FAST_THRESHOLD', 5.0);  // avg items sold per day → fast seller
define('BILLBEE_VELOCITY_MEDIUM_THRESHOLD', 1.0); // avg items sold per day → medium seller
define('BILLBEE_BUFFER_FAST', 20);
define('BILLBEE_BUFFER_MEDIUM', 10);
define('BILLBEE_BUFFER_SLOW', 3);
define('BILLBEE_DEFAULT_CATEGORY', 'slow');
define('BILLBEE_PAGE_SIZE', 250);
define('BILLBEE_MAX_PAGES', 40);

// Retry & timeouts
define('MAX_RETRIES', 3);
define('RETRY_BASE_MS', 600);
define('CONNECT_TIMEOUT', 15);
define('REQUEST_TIMEOUT', 45);

// Simple caches populated during runtime to avoid repeated VO lookups per SKU.
$VO_STOCK_CACHE = [];

/* ================ LOGGING ================= */
function logMsg($msg) {
  $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  if (LOG_ECHO_ENABLED) {
    echo $line;
  }
  file_put_contents(LOG_FILE, $line, FILE_APPEND);
}

function logMsgFileOnly($msg) {
  $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  file_put_contents(LOG_FILE, $line, FILE_APPEND);
}

function runModeLabel() {
  return SYNC_DRY_RUN ? 'DRY-RUN' : 'LIVE';
}

function isDryRun() {
  return SYNC_DRY_RUN;
}

function logSection($title) {
  $bar = str_repeat('-', max(10, strlen($title) + 6));
  logMsg($bar);
  logMsg('>>> ' . $title);
  logMsg($bar);
}

/* ============== HTTP with retry ============== */
function httpJsonWithRetry($url, $method, $headers, $jsonBody) {
  $body = $jsonBody === null ? null : json_encode($jsonBody, JSON_UNESCAPED_SLASHES);
  $last = [0, null, null, ''];
  for ($a=1; $a<=MAX_RETRIES; $a++) {
    $ch = curl_init($url);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt_array($ch, [
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_CUSTOMREQUEST  => $method,
      CURLOPT_HTTPHEADER     => array_merge($headers, ['User-Agent: FeelaSync/1.0']),
      CURLOPT_CONNECTTIMEOUT => CONNECT_TIMEOUT,
      CURLOPT_TIMEOUT        => REQUEST_TIMEOUT,
      CURLOPT_POSTFIELDS     => $body,
      CURLOPT_SSL_VERIFYPEER => true,
      CURLOPT_SSL_VERIFYHOST => 2,
      CURLOPT_ENCODING       => '',
      CURLOPT_VERBOSE        => true,
      CURLOPT_STDERR         => $verbose,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    rewind($verbose);
    $vout = stream_get_contents($verbose);
    fclose($verbose);

    $last = [$code, $resp, $err, $vout];

    if ($code >= 200 && $code < 300) return $last;
    if ($code == 429 || ($code >= 500 && $code <= 599) || $code == 0) {
      usleep((int)((RETRY_BASE_MS * (2 ** ($a-1))) * 1000));
      continue;
    }
    break; // non-retryable
  }
  return $last;
}

/* ================ FTP ===================== */
function downloadLatestCSV() {
  if (!is_dir(LOCAL_CSV_DIR)) mkdir(LOCAL_CSV_DIR, 0777, true);

  $conn = ftp_connect(FTP_HOST, FTP_PORT);
  if (!$conn) { logMsg("❌ FTP connect failed"); exit(1); }
  if (!ftp_login($conn, FTP_USER, FTP_PASS)) { logMsg("❌ FTP login failed"); exit(1); }
  ftp_pasv($conn, true);
  if (!ftp_chdir($conn, FTP_DIR)) { logMsg("❌ FTP chdir failed: " . FTP_DIR); exit(1); }

  $files = ftp_nlist($conn, ".");
  if (!$files) { logMsg("❌ No files in FTP dir"); exit(1); }
  $csvs = array_filter($files, fn($f) => preg_match('/\.csv$/i', $f));
  if (!$csvs) { logMsg("❌ No CSV files found"); exit(1); }
  sort($csvs, SORT_NATURAL);
  $latest = end($csvs);

  $local  = LOCAL_CSV_DIR . '/' . basename($latest);
  if (!ftp_get($conn, $local, $latest, FTP_BINARY)) { logMsg("❌ FTP download failed: $latest"); exit(1); }
  ftp_close($conn);

  logMsg("✅ Downloaded: $latest → $local");
  return $local;
}

/* ================ CSV PARSER ============== */
function detectDelimiter($line) {
  $c = [';', ',', "\t", '|']; $best=';'; $cnt=0;
  foreach ($c as $d) { $n=substr_count($line,$d); if ($n>$cnt){$cnt=$n;$best=$d;} }
  return $best;
}
function parseCSV($path) {
  $raw = file_get_contents($path);
  if ($raw === false) { logMsg("❌ Cannot read CSV"); exit(1); }
  $raw = preg_replace('/^\xEF\xBB\xBF/','',$raw);
  $raw = str_replace(["\r\n","\r"],"\n",$raw);
  $lines = explode("\n", trim($raw));
  if (count($lines) < 2) { logMsg("❌ CSV seems empty"); exit(1); }

  $delim  = detectDelimiter($lines[0]);
  $header = str_getcsv($lines[0], $delim);

  $rows = [];
  for ($i=1; $i<count($lines); $i++) {
    $line = trim($lines[$i]);
    if ($line==='') continue;
    $data = str_getcsv($line, $delim);
    if (!$data) continue;
    $row = array_combine($header, array_pad($data, count($header), null));
    $sku = trim($row[CSV_SKU_COL] ?? '');
    $qty = trim($row[CSV_QTY_COL] ?? '');
    $qty = (int)round((float)str_replace(',', '.', $qty));
    if ($sku !== '' && $qty >= 0) $rows[] = ['sku'=>$sku, 'stock'=>$qty];
  }

  logMsg("🔍 Parsed " . count($rows) . " SKUs from stock file");
  foreach (array_slice($rows, 0, 5) as $p) logMsg("   Sample → SKU: {$p['sku']} | Stock: {$p['stock']}");
  return $rows;
}

/* ===== SAFETY BUFFER for Billbee ===== */
function canonicalSku($sku) {
  if (!is_string($sku)) return '';
  return strtoupper(trim($sku));
}

function billbeeBufferForCategory($category) {
  switch (strtolower((string)$category)) {
    case 'fast':
      return BILLBEE_BUFFER_FAST;
    case 'medium':
      return BILLBEE_BUFFER_MEDIUM;
    case 'slow':
    default:
      return BILLBEE_BUFFER_SLOW;
  }
}

function billbeeClassifyDailyRate($avgPerDay) {
  if (!is_numeric($avgPerDay)) return BILLBEE_DEFAULT_CATEGORY;
  if ($avgPerDay >= BILLBEE_VELOCITY_FAST_THRESHOLD) {
    return 'fast';
  }
  if ($avgPerDay >= BILLBEE_VELOCITY_MEDIUM_THRESHOLD) {
    return 'medium';
  }
  return 'slow';
}

function adjustStockForBillbee($stock, $category) {
  $buffer = billbeeBufferForCategory($category);
  $buffer = min($buffer, max(0, (int)$stock));
  return max(0, (int)$stock - $buffer);
}

function billbeeBaseHeaders() {
  $auth = base64_encode(BILLBEE_USER . ':' . BILLBEE_API_PASSWORD);
  return [
    'Authorization: Basic ' . $auth,
    'X-Billbee-Api-Key: ' . BILLBEE_API_KEY,
    'Accept: application/json'
  ];
}

function billbeeApiRequest($path, array $query = []) {
  $url = rtrim(BILLBEE_API_URL, '/') . '/' . ltrim($path, '/');
  if ($query) {
    $url .= '?' . http_build_query($query);
  }
  $headers = billbeeBaseHeaders();

  [$code, $resp, $curlErr, $verbose] = httpJsonWithRetry($url, 'GET', $headers, null);
  if (!($code >= 200 && $code < 300)) {
    $extra = $curlErr ?: trim($verbose);
    return [null, 'HTTP ' . $code . ($extra ? ' | ' . $extra : '')];
  }

  $decoded = billbeeDecodeJson($resp);
  if ($decoded === null) {
    return [null, 'invalid-json'];
  }

  return [$decoded, null];
}

function billbeeDecodeJson($payload) {
  if ($payload === null || $payload === '') {
    return [];
  }

  if (!is_string($payload)) {
    return null;
  }

  $candidates = [];

  $candidates[] = $payload;
  $candidates[] = preg_replace('/^\xEF\xBB\xBF/', '', $payload);

  $trimmed = trim($payload);
  if ($trimmed !== $payload) {
    $candidates[] = $trimmed;
  }

  $sanitized = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $payload);
  if ($sanitized !== $payload) {
    $candidates[] = $sanitized;
    $candidates[] = trim($sanitized);
  }

  foreach ($candidates as $candidate) {
    if (!is_string($candidate)) {
      continue;
    }
    $json = json_decode($candidate, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
      return $json;
    }
  }

  return null;
}

function billbeeExtractOrderItems(array $order) {
  $candidateKeys = ['OrderItems', 'orderItems', 'Items', 'items', 'Positions', 'positions', 'Products', 'products'];
  foreach ($candidateKeys as $key) {
    if (!array_key_exists($key, $order)) continue;
    $items = $order[$key];
    if (is_array($items)) {
      $filtered = array_values(array_filter($items, 'is_array'));
      if ($filtered) {
        return $filtered;
      }
    }
  }

  foreach ($order as $value) {
    if (!is_array($value)) continue;
    foreach ($value as $sub) {
      if (!is_array($sub)) continue;
      $filtered = array_values(array_filter($sub, 'is_array'));
      foreach ($filtered as $item) {
        if (is_array($item) && (array_key_exists('Quantity', $item) || array_key_exists('quantity', $item))) {
          return $filtered;
        }
      }
    }
  }

  return [];
}

function billbeeExtractOrderItemQuantity(array $item) {
  foreach (['Quantity', 'quantity', 'Amount', 'amount'] as $field) {
    if (!array_key_exists($field, $item)) continue;
    $value = normalizeIntValue($item[$field]);
    if ($value !== null) return $value;
  }
  return null;
}

function billbeeExtractOrderItemSku(array $item) {
  foreach (extractSkuCandidates($item) as $candidate) {
    if ($candidate !== '') {
      return $candidate;
    }
  }
  if (isset($item['Product']) && is_array($item['Product'])) {
    foreach (extractSkuCandidates($item['Product']) as $candidate) {
      if ($candidate !== '') {
        return $candidate;
      }
    }
  }
  return null;
}

function billbeeFetchSalesVelocities($lookbackDays) {
  $days = max(1, (int)$lookbackDays);
  $since = (new \DateTimeImmutable('now'))->modify('-' . $days . ' days')->setTime(0, 0, 0)->format('c');

  $page = 1;
  $pagesFetched = 0;
  $ordersProcessed = 0;
  $itemsProcessed = 0;
  $quantityBySku = [];

  while ($page <= BILLBEE_MAX_PAGES) {
    $query = [
      'page' => $page,
      'pageSize' => BILLBEE_PAGE_SIZE,
      'minOrderDate' => $since,
      'expand' => 'orderitems'
    ];

    [$payload, $err] = billbeeApiRequest('orders', $query);
    if ($err !== null) {
      return [
        'ok' => false,
        'info' => [],
        'counts' => ['fast' => 0, 'medium' => 0, 'slow' => 0],
        'orders' => $ordersProcessed,
        'items' => $itemsProcessed,
        'window_days' => $days,
        'pages' => $pagesFetched,
        'error' => $err
      ];
    }

    $orders = [];
    if (isset($payload['data']) && is_array($payload['data'])) {
      $orders = $payload['data'];
    } elseif (isset($payload['Data']) && is_array($payload['Data'])) {
      $orders = $payload['Data'];
    } elseif (isset($payload['results']) && is_array($payload['results'])) {
      $orders = $payload['results'];
    } elseif (isset($payload['Results']) && is_array($payload['Results'])) {
      $orders = $payload['Results'];
    } elseif (isset($payload['orders']) && is_array($payload['orders'])) {
      $orders = $payload['orders'];
    } elseif (isset($payload['Orders']) && is_array($payload['Orders'])) {
      $orders = $payload['Orders'];
    } elseif (array_values($payload) === $payload) {
      $orders = $payload;
    }

    if (!$orders) {
      break;
    }

    foreach ($orders as $order) {
      if (!is_array($order)) continue;
      $ordersProcessed++;
      $items = billbeeExtractOrderItems($order);
      foreach ($items as $item) {
        if (!is_array($item)) continue;
        $sku = billbeeExtractOrderItemSku($item);
        $qty = billbeeExtractOrderItemQuantity($item);
        if ($sku === null || $sku === '' || $qty === null || $qty <= 0) continue;
        foreach (billbeeSkuTargets($sku) as $candidate) {
          $canon = canonicalSku($candidate);
          if ($canon === '') continue;
          if (!array_key_exists($canon, $quantityBySku)) {
            $quantityBySku[$canon] = 0;
          }
          $quantityBySku[$canon] += $qty;
        }
        $itemsProcessed++;
      }
    }

    $pagesFetched++;
    if (count($orders) < BILLBEE_PAGE_SIZE) {
      break;
    }
    $page++;
  }

  if ($page > BILLBEE_MAX_PAGES) {
    logMsg('⚠️ Billbee velocity reached max page limit (' . BILLBEE_MAX_PAGES . ')');
  }

  $info = [];
  $counts = ['fast' => 0, 'medium' => 0, 'slow' => 0];
  foreach ($quantityBySku as $canon => $qty) {
    $avgPerDay = $qty / $days;
    $category = billbeeClassifyDailyRate($avgPerDay);
    if (!isset($counts[$category])) {
      $counts[$category] = 0;
    }
    $counts[$category]++;
    $info[$canon] = [
      'total_quantity' => $qty,
      'daily_rate' => $avgPerDay,
      'category' => $category
    ];
  }

  return [
    'ok' => true,
    'info' => $info,
    'counts' => $counts,
    'orders' => $ordersProcessed,
    'items' => $itemsProcessed,
    'window_days' => $days,
    'pages' => $pagesFetched
  ];
}

function billbeeResolveVelocityForSku($sku, array $velocityInfo) {
  $candidates = array_merge([$sku], billbeeSkuTargets($sku));
  $seen = [];
  foreach ($candidates as $candidate) {
    $canon = canonicalSku($candidate);
    if ($canon === '' || isset($seen[$canon])) continue;
    $seen[$canon] = true;
    if (array_key_exists($canon, $velocityInfo)) {
      return $velocityInfo[$canon];
    }
  }
  return null;
}

/* ============ Helpers ============ */
function isFbmSku($sku) {
  if (!is_string($sku)) {
    return false;
  }

  return preg_match('/-FBM$/i', trim($sku)) === 1;
}

function normalizeVoSku($sku) {
  // VentoryOne uses the base SKU (no "-FBM")
  return preg_replace('/-FBM$/i', '', $sku);
}

function billbeeSkuTargets($sku) {
  $targets = [$sku];

  // Billbee previously stored some SKUs without their fulfillment suffix (e.g. "-FBM" or "-FBA").
  // Only add the suffix-less variant for non-FBM SKUs – FBM stock must remain isolated.
  if (preg_match('/-(FB[AM])$/i', $sku, $m) && strcasecmp($m[1], 'FBM') !== 0) {
    $base = substr($sku, 0, -strlen($m[0]));
    if ($base !== '') {
      $targets[] = $base;
    }
  }

  $targets = array_values(array_unique($targets));

  return $targets;
}

function collectStrings($value, array &$out) {
  if (is_string($value)) {
    $trimmed = trim($value);
    if ($trimmed !== '') $out[] = $trimmed;
    return;
  }

  if (is_array($value)) {
    foreach ($value as $item) collectStrings($item, $out);
    return;
  }

  if (is_object($value)) {
    foreach (get_object_vars($value) as $item) collectStrings($item, $out);
  }
}

function collectWarehouseIds($value, array &$out) {
  if ($value === null) return;

  if (is_int($value)) {
    $out[] = $value;
    return;
  }

  if (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
    $out[] = (int)trim($value);
    return;
  }

  if (is_float($value)) {
    $out[] = (int)$value;
    return;
  }

  if (is_array($value)) {
    foreach ($value as $key => $item) {
      if (is_string($key)) {
        $lower = strtolower($key);
        if (strpos($lower, 'warehouse') !== false || strpos($lower, 'id') !== false) {
          collectWarehouseIds($item, $out);
        }
      } else {
        collectWarehouseIds($item, $out);
      }
    }
    return;
  }

  if (is_object($value)) {
    collectWarehouseIds(get_object_vars($value), $out);
  }
}

function extractIntFromMixed($value) {
  if (is_int($value)) {
    return $value;
  }

  if (is_float($value)) {
    return (int)round($value);
  }

  if (is_string($value)) {
    $trimmed = trim($value);
    if ($trimmed === '') return null;
    if (!preg_match('/^-?\d+(?:[\.,]\d+)?$/', $trimmed)) return null;
    $normalized = str_replace(',', '.', $trimmed);
    return (int)round((float)$normalized);
  }

  return null;
}

function extractSkuCandidates(array $row) {
  $preferredKeys = [
    'sku', 'sku_code', 'skuCode', 'sku_name', 'skuName', 'sku_full_name',
    'skuFullName', 'sku_display_name', 'skuDisplayName', 'name', 'skuValue',
    'sku_value', 'sku_number', 'skuNumber'
  ];

  $candidates = [];

  foreach ($preferredKeys as $key) {
    if (!array_key_exists($key, $row)) continue;
    collectStrings($row[$key], $candidates);
  }

  if (!$candidates) {
    // Fallback: scan nested structures for matching strings.
    collectStrings($row, $candidates);
  }

  $candidates = array_values(array_unique(array_filter($candidates, fn($s) => $s !== '')));

  return $candidates;
}

function extractWarehouseIds(array $row) {
  $ids = [];

  foreach (['warehouse_id', 'warehouse', 'warehouse_info'] as $key) {
    if (!array_key_exists($key, $row)) continue;
    collectWarehouseIds($row[$key], $ids);
  }

  $ids = array_values(array_unique(array_map('intval', $ids)));

  return $ids;
}

function stockKeywordScore($text) {
  if (!is_string($text) || $text === '') return 0;

  $lower = strtolower($text);
  $keywords = [
    'stk' => 8,
    'insgesamt' => 7,
    'gesamt' => 6,
    'total' => 6,
    'sum' => 4,
    'loose' => 5,
    'stock' => 5,
    'inventory' => 4,
    'qty' => 4,
    'quantity' => 4,
    'available' => 3,
    'pcs' => 2,
  ];

  $score = 0;
  foreach ($keywords as $kw => $weight) {
    if (strpos($lower, $kw) !== false) {
      $score += $weight;
    }
  }

  return $score;
}

function normalizeIntValue($value) {
  if (is_int($value)) {
    return $value;
  }

  if (is_float($value)) {
    return (int)round($value);
  }

  if (is_string($value)) {
    $trimmed = trim($value);
    if ($trimmed === '') return null;
    if (!preg_match('/^-?\d+(?:[\.,]\d+)?$/', $trimmed)) return null;
    $normalized = str_replace(',', '.', $trimmed);
    return (int)round((float)$normalized);
  }

  return null;
}

function normalizeFloatValue($value) {
  if (is_int($value) || is_float($value)) {
    return (float)$value;
  }

  if (is_string($value)) {
    $trimmed = trim($value);
    if ($trimmed === '') return null;
    if (!preg_match('/^-?\d+(?:[\.,]\d+)?$/', $trimmed)) return null;
    $normalized = str_replace(',', '.', $trimmed);
    return (float)$normalized;
  }

  return null;
}

function formatNumberForLog($number, $decimals = 2) {
  $formatted = number_format((float)$number, $decimals, '.', '');
  return rtrim(rtrim($formatted, '0'), '.');
}

function addStockMetricCandidate(array &$candidates, $label, $value, $score) {
  $intValue = normalizeIntValue($value);
  if ($intValue === null) return;
  if ($score <= 0) return;

  $key = strtolower($label);
  if (!isset($candidates[$key]) || $score > $candidates[$key]['score']) {
    $candidates[$key] = [
      'label' => $label,
      'value' => $intValue,
      'score' => $score,
    ];
  }
}

function collectStockMetricCandidates($value, array $path, array &$candidates) {
  if (is_array($value)) {
    $assoc = $value;
  } elseif (is_object($value)) {
    $assoc = get_object_vars($value);
  } else {
    $labelParts = array_filter($path, 'is_string');
    if (!$labelParts) return;

    $label = implode('.', $labelParts);
    $score = stockKeywordScore($label);
    addStockMetricCandidate($candidates, $label, $value, $score);
    return;
  }

  // detect metric structures like {"name": "STK – Insgesamt", "value": 123}
  $textKeys = ['name', 'label', 'title', 'metric', 'description', 'display_name', 'displayName'];
  $valueKeys = ['value', 'qty', 'quantity', 'amount', 'total', 'count', 'stock', 'stock_qty', 'stockQty', 'quantity_total', 'quantityTotal'];

  $textValue = null;
  $textKeyUsed = null;
  foreach ($textKeys as $textKey) {
    if (array_key_exists($textKey, $assoc) && is_string($assoc[$textKey]) && trim($assoc[$textKey]) !== '') {
      $textValue = trim($assoc[$textKey]);
      $textKeyUsed = $textKey;
      break;
    }
  }

  if ($textValue !== null) {
    foreach ($valueKeys as $valueKey) {
      if (!array_key_exists($valueKey, $assoc)) continue;
      $score = stockKeywordScore($valueKey) + stockKeywordScore($textValue);
      $labelParts = array_merge(array_filter($path, 'is_string'), [$valueKey]);
      $label = implode('.', $labelParts);
      if ($textKeyUsed) {
        $label .= ' [' . $textKeyUsed . '=' . $textValue . ']';
      }
      addStockMetricCandidate($candidates, $label, $assoc[$valueKey], $score);
    }
  }

  foreach ($assoc as $key => $item) {
    $nextPath = $path;
    if (is_string($key) && $key !== '') {
      $nextPath[] = $key;
    } elseif (!empty($path) && is_int($key)) {
      $nextPath[] = $key;
    }
    collectStockMetricCandidates($item, $nextPath, $candidates);
  }
}

function extractStockMetric(array $row) {
  $candidates = [];
  collectStockMetricCandidates($row, [], $candidates);
  if (!$candidates) return [null, null];

  $sorted = array_values($candidates);
  usort($sorted, function ($a, $b) {
    if ($a['score'] === $b['score']) {
      return $b['value'] <=> $a['value'];
    }
    return $b['score'] <=> $a['score'];
  });

  $best = $sorted[0];
  return [$best['value'], $best['label']];
}

function voExtractMetricDetails(array $row) {
  $metric = null;
  $fieldUsed = null;

  foreach ([
    'stk_insgesamt',
    'stkInsGesamt',
    'stk-gesamt',
    'stk_total',
    'qty_total_stock',
    'total_qty',
    'total_stock',
    'qty_loose_stock',
    'loose_qty',
    'qty',
  ] as $field) {
    if (!array_key_exists($field, $row)) continue;
    $candidate = normalizeIntValue($row[$field]);
    if ($candidate !== null) {
      $metric = $candidate;
      $fieldUsed = $field;
      break;
    }
  }

  if ($metric === null) {
    [$metricCandidate, $metricLabel] = extractStockMetric($row);
    if ($metricCandidate !== null) {
      $metric = $metricCandidate;
      $fieldUsed = $metricLabel ?: 'metric';
    }
  }

  return [$metric, $fieldUsed];
}

function voFormatPath(array $path) {
  if (!$path) {
    return '';
  }

  $segments = [];
  foreach ($path as $segment) {
    if (is_int($segment)) {
      $segments[] = '[' . $segment . ']';
    } elseif (is_string($segment) && $segment !== '') {
      $segments[] = $segment;
    }
  }

  if (!$segments) {
    return '';
  }

  $formatted = array_shift($segments);
  foreach ($segments as $segment) {
    if ($segment !== '' && $segment[0] === '[') {
      $formatted .= $segment;
    } else {
      $formatted .= '.' . $segment;
    }
  }

  return $formatted;
}

function voFindField($data, $targetKey) {
  $target = strtolower((string)$targetKey);
  if ($target === '') {
    return null;
  }

  $stack = [[
    'value' => $data,
    'path'  => []
  ]];
  $visitedObjects = [];

  while ($stack) {
    $current = array_pop($stack);
    $value = $current['value'];
    $path  = $current['path'];

    if (is_array($value)) {
      foreach ($value as $key => $child) {
        $newPath = $path;
        if (is_string($key) || is_int($key)) {
          $newPath[] = $key;
        }
        if (is_string($key) && strtolower($key) === $target) {
          return ['value' => $child, 'path' => $newPath];
        }
        if (is_array($child) || is_object($child)) {
          $stack[] = ['value' => $child, 'path' => $newPath];
        }
      }
      continue;
    }

    if (is_object($value)) {
      $objectId = spl_object_id($value);
      if (isset($visitedObjects[$objectId])) {
        continue;
      }
      $visitedObjects[$objectId] = true;

      foreach (get_object_vars($value) as $key => $child) {
        $newPath = $path;
        $newPath[] = $key;
        if (is_string($key) && strtolower($key) === $target) {
          return ['value' => $child, 'path' => $newPath];
        }
        if (is_array($child) || is_object($child)) {
          $stack[] = ['value' => $child, 'path' => $newPath];
        }
      }
    }
  }

  return null;
}

function voFetchStockEntry($skuBase, &$note = null) {
  $headers = [
    'Authorization: Bearer ' . VO_TOKEN,
    'Accept: application/json'
  ];

  $attempts = [
    VO_BASE . '/api/current_stock/All/?search=' . urlencode($skuBase),
    VO_BASE . '/api/current_stock/All/'
  ];

  $note = 'SKU not found in current_stock';
  $fallbackNote = null;

  foreach ($attempts as $baseUrl) {
    if (!$baseUrl) continue;
    $url = $baseUrl;
    while ($url) {
      [$code, $resp, $curlErr] = httpJsonWithRetry($url, 'GET', $headers, null);
      if (!($code >= 200 && $code < 300) || !$resp) {
        $note = $curlErr ? ('HTTP ' . $code . ' | ' . $curlErr) : ('HTTP ' . $code);
        break;
      }

      $json = json_decode($resp, true);
      if (!is_array($json)) {
        $note = 'Bad JSON';
        break;
      }

      $rows = $json;
      if (isset($json['results']) && is_array($json['results'])) {
        $rows = $json['results'];
      } elseif (isset($json['data']) && is_array($json['data'])) {
        $rows = $json['data'];
      }

      if (!is_array($rows)) {
        $note = 'Unexpected payload';
        break;
      }

      foreach ($rows as $row) {
        if (!is_array($row)) continue;

        $skuDisplay = null;
        $skuMatches = false;
        foreach (extractSkuCandidates($row) as $candidate) {
          if (strcasecmp($candidate, $skuBase) === 0 || strcasecmp(normalizeVoSku($candidate), $skuBase) === 0) {
            $skuDisplay = $candidate;
            $skuMatches = true;
            break;
          }
        }

        if (!$skuMatches) continue;

        $warehouseIds = extractWarehouseIds($row);

        $metric = null;
        $fieldUsed = null;
        foreach ([
          'stk_insgesamt',
          'qty_total_stock',
          'total_qty',
          'total_stock',
          'qty_loose_stock',
          'loose_qty',
          'qty'
        ] as $field) {
          if (!array_key_exists($field, $row)) continue;
          $value = normalizeIntValue($row[$field]);
          if ($value === null) continue;
          $metric = $value;
          $fieldUsed = $field;
          break;
        }

        if ($metric === null) {
          $note = 'stock-metric-missing';
          continue;
        }

        $note = $fieldUsed . '=' . $metric;
        if ($skuDisplay !== null && strcasecmp($skuDisplay, $skuBase) !== 0) {
          $note .= ' (matched ' . $skuDisplay . ')';
          $row['_matched_candidate'] = $skuDisplay;
        }

        if (!empty($warehouseIds)) {
          $row['_warehouse_ids'] = $warehouseIds;
        }

        $row['_metric_field'] = $fieldUsed;
        $row['_metric_value'] = $metric;

        if (!empty($warehouseIds) && !in_array(VO_WAREHOUSE_ID, $warehouseIds, true)) {
          if ($fallbackNote === null) {
            $fallbackNote = 'warehouse mismatch (found ' . implode(',', $warehouseIds) . ')';
          }
          continue;
        }

        return $row;
      }

      $next = $json['next'] ?? null;
      if (!$next) {
        break;
      }

      if (is_string($next)) {
        if (preg_match('#^https?://#', $next)) {
          $url = $next;
        } else {
          $url = rtrim(VO_BASE, '/') . '/' . ltrim($next, '/');
        }
      } else {
        $url = null;
      }
    }
  }

  if ($fallbackNote !== null) {
    $note = $fallbackNote;
  }

  return null;
}

function voExtractSkuId(array $row) {
  foreach ([
    'sku_id', 'skuId', 'id', 'obj_id', 'objId', 'sku_obj_id', 'skuObjId'
  ] as $field) {
    if (!array_key_exists($field, $row)) continue;
    $value = extractIntFromMixed($row[$field]);
    if ($value !== null) return $value;
  }

  if (isset($row['sku_obj'])) {
    $obj = $row['sku_obj'];
    if (is_array($obj)) {
      foreach (['id', 'pk'] as $field) {
        if (array_key_exists($field, $obj)) {
          $value = extractIntFromMixed($obj[$field]);
          if ($value !== null) return $value;
        }
      }
    } elseif (is_object($obj)) {
      foreach (['id', 'pk'] as $field) {
        if (property_exists($obj, $field)) {
          $value = extractIntFromMixed($obj->$field);
          if ($value !== null) return $value;
        }
      }
    }
  }

  return null;
}

function voExtractOrganizationId(array $row) {
  foreach (['organization_id', 'organizationId', 'organization'] as $field) {
    if (!array_key_exists($field, $row)) continue;
    $value = extractIntFromMixed($row[$field]);
    if ($value !== null) return $value;
  }

  if (isset($row['warehouse'])) {
    $warehouse = $row['warehouse'];
    if (is_array($warehouse) && array_key_exists('organization', $warehouse)) {
      $value = extractIntFromMixed($warehouse['organization']);
      if ($value !== null) return $value;
    }
    if (is_object($warehouse) && property_exists($warehouse, 'organization')) {
      $value = extractIntFromMixed($warehouse->organization);
      if ($value !== null) return $value;
    }
  }

  return null;
}

function voResolveSkuForUpdate(?array $row = null, $skuBase = '', $csvSku = '') {
  $preferred = [];
  $fallback  = [];

  if (is_array($row)) {
    $primaryFields = [
      'sku', 'sku_code', 'skuCode', 'sku_name', 'skuName',
      'sku_display_name', 'skuDisplayName', 'sku_full_name', 'skuFullName',
      'sku_value', 'skuValue'
    ];
    foreach ($primaryFields as $field) {
      if (!array_key_exists($field, $row)) continue;
      $value = $row[$field];
      if (!is_string($value)) continue;
      $trimmed = trim($value);
      if ($trimmed === '') continue;
      $preferred[] = $trimmed;
    }

    $fallbackFields = [
      '_matched_candidate', 'variation_name', 'variationName', 'name'
    ];
    foreach ($fallbackFields as $field) {
      if (!array_key_exists($field, $row)) continue;
      $value = $row[$field];
      if (!is_string($value)) continue;
      $trimmed = trim($value);
      if ($trimmed === '') continue;
      $fallback[] = $trimmed;
    }
  }

  if (is_string($skuBase) && $skuBase !== '') {
    array_unshift($preferred, $skuBase);
  }

  if (is_string($csvSku) && $csvSku !== '') {
    if ($csvSku !== $skuBase) {
      $preferred[] = $csvSku;
    }
  }

  $seen = [];
  $ordered = array_merge($preferred, $fallback);
  foreach ($ordered as $candidate) {
    if ($candidate === '') {
      continue;
    }
    $canon = canonicalSku($candidate);
    if ($canon === '' || isset($seen[$canon])) {
      continue;
    }
    $seen[$canon] = true;
    return $candidate;
  }

  return $skuBase ?: $csvSku;
}

function voPostAndCheck($path, array $payload) {
  $url = rtrim(VO_BASE, '/') . '/' . ltrim($path, '/');
  $headers = [
    'Authorization: Bearer ' . VO_TOKEN,
    'Content-Type: application/json',
    'Accept: application/json'
  ];

  if (isDryRun()) {
    $skuList = [];
    if (isset($payload['sku_qty_list']) && is_array($payload['sku_qty_list'])) {
      foreach ($payload['sku_qty_list'] as $entry) {
        if (is_array($entry)) {
          $skuList[] = $entry['sku'] ?? ('#' . ($entry['sku_id'] ?? '?'));
        }
      }
    }
    $summary = $skuList ? implode(', ', $skuList) : 'n/a';
    logMsg('🧪 DRY-RUN: VentoryOne ' . $path . ' would update [' . $summary . ']');
    return [true, 'dry-run'];
  }

  [$code, $resp, $curlErr, $verbose] = httpJsonWithRetry($url, 'POST', $headers, $payload);

  if (!($code >= 200 && $code < 300)) {
    $extra = $curlErr ?: trim($verbose);
    return [false, 'HTTP ' . $code . ($extra ? ' | ' . $extra : '')];
  }

  if ($resp === null || $resp === '') {
    return [true, ''];
  }

  $json = json_decode($resp, true);
  if (!is_array($json)) {
    return [true, trim($resp)];
  }

  if (isset($json['errors']) && !empty($json['errors'])) {
    return [false, 'errors=' . json_encode($json['errors'])];
  }

  if (isset($json['success'])) {
    $success = filter_var($json['success'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    if ($success === false) {
      return [false, 'success flag false'];
    }
  }

  $message = '';
  if (isset($json['message']) && is_string($json['message'])) {
    $message = trim($json['message']);
    if (preg_match('/(not found|invalid|error|failed)/i', $message)) {
      return [false, 'message=' . $message];
    }
  }

  return [true, $message];
}

/* ============ VentoryOne ============ */
function voDescribeIdent(array $ident) {
  $parts = [];
  if (!empty($ident['sku'])) {
    $parts[] = 'sku=' . $ident['sku'];
  }
  if (!empty($ident['sku_id'])) {
    $parts[] = 'sku_id=' . $ident['sku_id'];
  }
  if (!empty($ident['organization_id'])) {
    $parts[] = 'org=' . $ident['organization_id'];
  }
  $parts[] = 'warehouse=' . VO_WAREHOUSE_ID;
  return implode(', ', $parts);
}

function voSummarizeObserved(array $observed) {
  $parts = [];
  foreach (['metric', 'total', 'loose'] as $key) {
    if (array_key_exists($key, $observed) && $observed[$key] !== null) {
      $parts[] = $key . '=' . $observed[$key];
    }
  }
  return implode(', ', $parts);
}

function voSetCartonsZero(array $ident) {
  $entry = ['carton_qty' => 0];

  if (!empty($ident['sku_id'])) {
    $entry['sku_id'] = (int)$ident['sku_id'];
  }
  if (!empty($ident['sku'])) {
    $entry['sku'] = $ident['sku'];
  }

  $payload = [
    'warehouse_id' => VO_WAREHOUSE_ID,
    'sku_qty_list' => [$entry]
  ];

  if (!empty($ident['organization_id'])) {
    $payload['organization_id'] = (int)$ident['organization_id'];
  }

  if (isDryRun()) {
    return [true, 'dry-run'];
  }

  return voPostAndCheck('/api/update_plain_carton_line_item_qty/', $payload);
}

function voSetLooseToTotal(array $ident, $total, ?array $referenceRow = null) {
  $totalInt = (int)$total;

  $baseEntry = [
    'pcs_in_loose_stock' => $totalInt,
  ];

  if (!empty($ident['sku_id'])) {
    $baseEntry['sku_id'] = (int)$ident['sku_id'];
  }
  if (!empty($ident['sku'])) {
    $baseEntry['sku'] = $ident['sku'];
  }

  $basePayload = [
    'warehouse_id' => VO_WAREHOUSE_ID,
  ];

  if (!empty($ident['organization_id'])) {
    $basePayload['organization_id'] = (int)$ident['organization_id'];
  }

  if (isDryRun()) {
    logMsg('🧪 DRY-RUN: skip VentoryOne loose stock set to ' . $totalInt . ' for ' . voDescribeIdent($ident));
    return [true, 'dry-run'];
  }

  $candidateFields = [
    'qty_loose_stock',
    'pcs_in_total_stock', 'pcs_total_stock', 'pcs_in_stock',
    'qty_total_stock', 'total_qty', 'total_stock',
    'stk_insgesamt', 'stkInsGesamt', 'stk-gesamt', 'stk_total',
    'qty_available_stock', 'available_qty', 'available_stock',
    'qty_available', 'stock_available', 'qty_in_stock',
    'in_stock_qty', 'stock_qty', 'stock_quantity',
    'quantity', 'qty', 'available'
  ];

  if ($referenceRow !== null) {
    if (!empty($referenceRow['_metric_field']) && is_string($referenceRow['_metric_field'])) {
      $candidateFields[] = $referenceRow['_metric_field'];
    }
    foreach ($referenceRow as $key => $value) {
      if (!is_string($key)) {
        continue;
      }
      $trimmed = trim($key);
      if ($trimmed === '') {
        continue;
      }
      $lower = strtolower($trimmed);
      if (strpos($lower, 'total') === false && strpos($lower, 'stk') === false && strpos($lower, 'loose') === false) {
        continue;
      }
      if (in_array($trimmed, ['sku', 'sku_id', 'warehouse_id', 'organization_id'], true)) {
        continue;
      }
      $candidateFields[] = $trimmed;
    }
  }

  $variants = [];
  $variants[] = $baseEntry; // minimal payload first

  $seenFields = [];
  foreach ($candidateFields as $field) {
    if (!is_string($field) || $field === '') {
      continue;
    }
    if (isset($seenFields[$field])) {
      continue;
    }
    $seenFields[$field] = true;
    $variant = $baseEntry;
    $variant[$field] = $totalInt;
    $variants[] = $variant;
  }

  $lastResult = [false, 'no-variant-succeeded'];
  foreach ($variants as $entry) {
    $payload = $basePayload;
    $payload['sku_qty_list'] = [$entry];
    [$ok, $note] = voPostAndCheck('/api/update_loose_stock/', $payload);
    if ($ok) {
      return [$ok, $note];
    }
    $lastResult = [$ok, $note];
  }

  return $lastResult;
}

function voVerifyLooseEquals($skuBase, $expect) {
  if (isDryRun()) {
    return [true, 'dry-run', ['metric' => null, 'total' => null, 'loose' => null]];
  }

  $note = null;
  $row = voFetchStockEntry($skuBase, $note);
  if (!$row) {
    return [false, $note, ['metric' => null, 'total' => null, 'loose' => null]];
  }

  $expected = (int)$expect;

  $pickValue = function (array $source, array $fields) {
    foreach ($fields as $field) {
      if (!array_key_exists($field, $source)) {
        continue;
      }
      $value = normalizeIntValue($source[$field]);
      if ($value === null) {
        continue;
      }
      return [$value, $field];
    }
    return null;
  };

  $totalInfo = $pickValue(
    $row,
    [
      'stk_insgesamt', 'stkInsGesamt', 'stk-gesamt', 'stk_total',
      'qty_total_stock', 'total_qty', 'total_stock',
      'pcs_in_total_stock', 'pcs_in_stock', 'pcs_total_stock',
      'qty_available_stock', 'available_qty', 'available_stock',
      'qty_available', 'stock_available', 'qty_in_stock',
      'in_stock_qty', 'stock_qty', 'stock_quantity',
      'quantity', 'qty', 'available'
    ]
  );
  $looseInfo = $pickValue($row, ['qty_loose_stock', 'loose_qty', 'pcs_in_loose_stock']);
  $cartonInfo = $pickValue($row, ['carton_qty', 'qty_cartons', 'cartons_left_cached', 'cartons']);
  [$metricValue, $metricField] = voExtractMetricDetails($row);

  if ($totalInfo === null && $looseInfo === null && $metricValue === null) {
    $debugKeys = implode(',', array_keys($row));
    return [false, 'stock-metric-missing', ['metric' => null, 'total' => null, 'loose' => null]];
  }

  $parts = [];
  $ok = true;

  if ($metricValue !== null) {
    if ($metricValue !== $expected) {
      $ok = false;
    }
    $parts[] = 'metric(' . $metricField . ')=' . $metricValue;
  }

  if ($totalInfo !== null) {
    [$value, $field] = $totalInfo;
    if ($value !== $expected) {
      $ok = false;
    }
    $parts[] = 'total(' . $field . ')=' . $value;
  }

  if ($looseInfo !== null) {
    [$value, $field] = $looseInfo;
    if ($value !== $expected) {
      $ok = false;
    }
    $parts[] = 'loose(' . $field . ')=' . $value;
  }

  if ($cartonInfo !== null) {
    [$value, $field] = $cartonInfo;
    $parts[] = 'cartons(' . $field . ')=' . $value;
  }

  if (isset($row['_matched_candidate']) && strcasecmp($row['_matched_candidate'], $skuBase) !== 0) {
    $parts[] = 'matched ' . $row['_matched_candidate'];
  }

  if (!empty($row['_warehouse_ids'])) {
    $parts[] = 'warehouses=' . implode(',', $row['_warehouse_ids']);
  }

  if (!$parts) {
    return [false, 'verification-missing', ['metric' => null, 'total' => null, 'loose' => null]];
  }

  $observed = [
    'metric' => $metricValue,
    'total'  => $totalInfo[0] ?? null,
    'loose'  => $looseInfo[0] ?? null,
  ];

  return [$ok, implode(' | ', $parts), $observed];
}

function updateVentoryTotal($csvSku, $total) {
  $skuBase = normalizeVoSku($csvSku);

  $maxAttempts = isDryRun() ? 1 : 5;
  $retryDelays = isDryRun() ? [] : [0.75, 1.5, 3.0, 4.5];
  $lastVerifyNote = null;
  $lastVerifyObserved = ['metric' => null, 'total' => null, 'loose' => null];

  for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
    $lookupNote = null;
    $lookupRow = voFetchStockEntry($skuBase, $lookupNote);
    $preferredSku = voResolveSkuForUpdate($lookupRow ?? null, $skuBase, $csvSku);
    $ident = [
      'sku' => $preferredSku,
      'sku_id' => $lookupRow ? voExtractSkuId($lookupRow) : null,
      'organization_id' => $lookupRow ? voExtractOrganizationId($lookupRow) : null,
    ];

    if (!$lookupRow && $lookupNote) {
      logMsg("ℹ️ VO lookup $skuBase before update: $lookupNote");
    } elseif ($lookupRow && !array_key_exists('_metric_value', $lookupRow)) {
      logMsg("ℹ️ VO lookup $skuBase before update: stock-metric-missing");
    }

    [$okC, $cartonNote] = voSetCartonsZero($ident);
    if (!$okC) {
      logMsg("⚠️ VO carton reset $skuBase failed: $cartonNote");
    }

    [$okL, $looseNote] = voSetLooseToTotal($ident, $total, $lookupRow);
    if (!$okL) {
      logMsg("⚠️ VO loose set $skuBase failed: $looseNote");
    }

    if (!$okC || !$okL) {
      $detailParts = [];
      $detailParts[] = 'cartonsZero=' . ($okC ? 'OK' : 'FAIL' . ($cartonNote !== null && $cartonNote !== '' ? '[' . $cartonNote . ']' : ''));
      $detailParts[] = 'looseSet=' . ($okL ? 'OK' : 'FAIL' . ($looseNote !== null && $looseNote !== '' ? '[' . $looseNote . ']' : ''));
      $detail = implode('; ', $detailParts);
      logMsg("❌ VO FAIL $skuBase → total=$total ($detail)");
      return [false, $detail];
    }

    if (isDryRun()) {
      return [true, 'dry-run'];
    }

    [$okV, $note, $observed] = voVerifyLooseEquals($skuBase, $total);
    if ($okV) {
      logMsg("✅ VO OK $skuBase → target=$total | $note");
      return [true, $note];
    }

    $lastVerifyNote = $note;
    $lastVerifyObserved = $observed;

    if ($attempt < $maxAttempts) {
      $observedSummary = voSummarizeObserved($observed);
      $retrySuffix = $observedSummary !== '' ? ' | observed ' . $observedSummary : '';
      logMsg('ℹ️ VO verify mismatch for ' . $skuBase . ' (attempt ' . $attempt . '/' . $maxAttempts . '): ' . $note . $retrySuffix . ' → retrying');
      $delay = $retryDelays[min($attempt - 1, count($retryDelays) - 1)] ?? 0.75;
      usleep((int)($delay * 1000000));
      continue;
    }
  }

  $pendingNote = $lastVerifyNote !== null ? $lastVerifyNote : 'verification-missing';
  $observedSummary = voSummarizeObserved($lastVerifyObserved);
  if ($observedSummary !== '') {
    $pendingNote .= ' | observed ' . $observedSummary;
  }
  logMsg("❌ VO FAIL $skuBase → total=$total after retries (last verify: $pendingNote)");
  return [false, $pendingNote];
}

/* ============== Billbee ============== */
function updateBillbee($sku, $qty) {
  $url = BILLBEE_API_URL . 'products/updatestock';
  $headers = array_merge(billbeeBaseHeaders(), ['Content-Type: application/json']);
  $payload = [
    'Sku'         => $sku,
    'NewQuantity' => (int)$qty,
    'Reason'      => 'Automated sync via Feela'
  ];

  if (isDryRun()) {
    return [true, 'dry-run'];
  }

  [$code, $resp, $curlErr, $verbose] = httpJsonWithRetry($url, 'POST', $headers, $payload);

  if ($code >= 200 && $code < 300) {
    return [true, null];
  }
  $preview = $resp ? substr($resp, 0, 240) : 'no-body';
  $extra = $curlErr ?: trim($verbose);
  $note = 'HTTP ' . $code . ' | Resp: ' . $preview;
  if ($extra !== '') {
    $note .= ' | ' . $extra;
  }
  return [false, $note];
}

/* ================= MAIN =================== */
$heading = '========== INVENTORY SYNC (' . runModeLabel() . ') ==========';
logMsg($heading);
logMsg('⚙️ Mode selection: ' . $runModeSource . ' (default toggle via DEFAULT_RUN_MODE)');
if (isDryRun()) {
  logMsg('📋 Mode: DRY-RUN → external services will NOT be updated.');
} else {
  logMsg('📋 Mode: LIVE → external services WILL be updated.');
}
logMsg('ℹ️ Switch modes by editing DEFAULT_RUN_MODE or using --dry-run / --live when running php ' . $scriptName);
logMsg('🏬 VentoryOne warehouse target: ' . VO_WAREHOUSE_ID . ' (CAFOL)');
logMsg('🎯 SKU filter: only items ending with -FBM are processed');
logMsg('⏱️ Billbee velocity lookback: ' . BILLBEE_VELOCITY_LOOKBACK_DAYS . ' days');

logSection('CSV IMPORT');
$csv  = downloadLatestCSV();
$rows = parseCSV($csv);

$count = count($rows);
logMsg("📦 Found $count SKUs in stock file");

$fbmCount = count(array_filter($rows, function ($row) {
  return isset($row['sku']) && isFbmSku($row['sku']);
}));
logMsg('📦 FBM-qualified SKUs detected: ' . $fbmCount);

logSection('BILLBEE SALES VELOCITY');
$billbeeVelocityData = billbeeFetchSalesVelocities(BILLBEE_VELOCITY_LOOKBACK_DAYS);
$billbeeVelocityInfo = $billbeeVelocityData['info'] ?? [];
$billbeeVelocityWindow = $billbeeVelocityData['window_days'] ?? BILLBEE_VELOCITY_LOOKBACK_DAYS;
$billbeeVelocityOk = !empty($billbeeVelocityData['ok']);
$billbeeVelocityError = null;
if ($billbeeVelocityOk) {
  $counts = $billbeeVelocityData['counts'] ?? ['fast' => 0, 'medium' => 0, 'slow' => 0];
  $summary = sprintf(
    '📈 Billbee sales velocity %dd → orders=%d, items=%d, tracked SKUs=%d | fast=%d, medium=%d, slow=%d',
    $billbeeVelocityWindow,
    $billbeeVelocityData['orders'] ?? 0,
    $billbeeVelocityData['items'] ?? 0,
    count($billbeeVelocityInfo),
    $counts['fast'] ?? 0,
    $counts['medium'] ?? 0,
    $counts['slow'] ?? 0
  );
  logMsg($summary);
  if (($billbeeVelocityData['pages'] ?? 0) >= BILLBEE_MAX_PAGES) {
    logMsg('⚠️ Billbee velocity may be truncated due to page limit');
  }
} else {
  $billbeeVelocityError = $billbeeVelocityData['error'] ?? 'unknown error';
  logMsgFileOnly('ℹ️ Billbee velocity metrics unavailable; default safety buffers will be used.');
  logMsgFileOnly('⚠️ Billbee velocity data unavailable: ' . $billbeeVelocityError);
  $billbeeVelocityInfo = [];
}

$billbeeCategoryCounters = ['fast' => 0, 'medium' => 0, 'slow' => 0];
$billbeeSourceCounters = ['billbee' => 0, 'default' => 0];

$okVO = 0; $failVO = 0; $okBB = 0; $failBB = 0;
$voProcessedCount = 0;
$billbeeProcessedCount = 0;
$skippedNonFbm = 0;

logSection('SYNCHRONIZATION RUN');
foreach ($rows as $r) {
  $csvSku = $r['sku'];
  $stock  = (int)$r['stock'];

  $voProcessedCount++;

  // --- VentoryOne: STK – Insgesamt = CSV total (cartons=0, loose=stock) ---
  [$voOk, $voNote] = updateVentoryTotal($csvSku, $stock);
  if ($voOk) {
    $okVO++;
    if ($voNote === 'dry-run') {
      logMsg('🧪 DRY-RUN: VentoryOne would set ' . $csvSku . ' → ' . $stock . ' pcs');
    } else {
      logMsg('✅ VentoryOne ' . $csvSku . ' → ' . $stock . ' pcs');
    }
  } else {
    $failVO++;
    $detail = $voNote !== null && $voNote !== '' ? ' (' . $voNote . ')' : '';
    logMsg('❌ VentoryOne ' . $csvSku . ' failed (target ' . $stock . ' pcs)' . $detail);
  }

  if (!isFbmSku($csvSku)) {
    $skippedNonFbm++;
    logMsg('ℹ️ Billbee skip non-FBM SKU ' . $csvSku . ' (VentoryOne updated only)');
    continue;
  }

  $billbeeProcessedCount++;

  // --- Billbee: safety stock logic ---
  $category = BILLBEE_DEFAULT_CATEGORY;
  $source = 'default';
  $velocityLogContext = ['type' => 'default'];

  $velocityInfo = billbeeResolveVelocityForSku($csvSku, $billbeeVelocityInfo);
  if ($velocityInfo !== null) {
    $category = $velocityInfo['category'];
    $source = 'billbee';
    $velocityLogContext = [
      'type'   => 'billbee',
      'daily'  => formatNumberForLog($velocityInfo['daily_rate'] ?? 0),
      'total'  => $velocityInfo['total_quantity'] ?? null,
      'window' => $billbeeVelocityWindow,
    ];
  } else {
    $reason = $billbeeVelocityOk
      ? 'no Billbee velocity match in last ' . $billbeeVelocityWindow . 'd'
      : 'Billbee velocity data unavailable (default buffers applied)';
    $velocityLogContext = [
      'type'   => 'default',
      'reason' => $reason,
    ];
    if (!$billbeeVelocityOk && $billbeeVelocityError !== null) {
      $velocityLogContext['detail'] = $billbeeVelocityError;
    }
  }

  $keep = billbeeBufferForCategory($category);
  $bbQty = adjustStockForBillbee($stock, $category);

  switch ($velocityLogContext['type']) {
    case 'billbee':
      $total = $velocityLogContext['total'];
      $window = (int)$velocityLogContext['window'];
      if ($total !== null) {
        $detail = 'sold=' . $total . ' over ' . $window . 'd';
      } else {
        $detail = 'window=' . $window . 'd';
      }
      logMsg("ℹ️ Billbee velocity $csvSku → $category (avg {$velocityLogContext['daily']}/day, $detail, keep $keep)");
      break;
    default:
      $reason = $velocityLogContext['reason'] ?? 'no Billbee velocity data';
      logMsg('ℹ️ Billbee velocity fallback for ' . $csvSku . ' → category=' . $category . ' (keep ' . $keep . ', ' . $reason . ')');
      if (isset($velocityLogContext['detail'])) {
        logMsgFileOnly('ℹ️ Billbee velocity fallback detail for ' . $csvSku . ': ' . $velocityLogContext['detail']);
      }
      break;
  }

  logMsg("ℹ️ Billbee allocation $csvSku → stock=$stock, keep=$keep, update=$bbQty, source=$source");

  if (!isset($billbeeCategoryCounters[$category])) {
    $billbeeCategoryCounters[$category] = 0;
  }
  $billbeeCategoryCounters[$category]++;
  if (!isset($billbeeSourceCounters[$source])) {
    $billbeeSourceCounters[$source] = 0;
  }
  $billbeeSourceCounters[$source]++;

  $bbAllOk = true;
  $bbErrors = [];
  foreach (billbeeSkuTargets($csvSku) as $bbSku) {
    [$bbOk, $bbNote] = updateBillbee($bbSku, $bbQty);
    if (!$bbOk) {
      $bbAllOk = false;
      $bbErrors[] = $bbSku . ': ' . $bbNote;
    }
  }
  if ($bbAllOk) {
    $okBB++;
    if (isDryRun()) {
      logMsg('🧪 DRY-RUN: Billbee would set ' . $csvSku . ' → stock=' . $stock . ', keep=' . $keep . ', update=' . $bbQty);
    } else {
      $suffix = $source === 'billbee'
        ? ' (Billbee velocity ' . ($velocityLogContext['daily'] ?? 'n/a') . '/day)'
        : '';
      logMsg('✅ Billbee ' . $csvSku . ' → stock=' . $stock . ', keep=' . $keep . ', update=' . $bbQty . $suffix);
    }
  } else {
    $failBB++;
    $detail = $bbErrors ? ' (' . implode('; ', $bbErrors) . ')' : '';
    logMsg('❌ Billbee ' . $csvSku . ' failed → stock=' . $stock . ', keep=' . $keep . ', update=' . $bbQty . $detail);
  }
}

$usedSummary = sprintf(
  '📊 Billbee categories used this run → fast=%d, medium=%d, slow=%d',
  $billbeeCategoryCounters['fast'] ?? 0,
  $billbeeCategoryCounters['medium'] ?? 0,
  $billbeeCategoryCounters['slow'] ?? 0
);
$sourceSummary = sprintf(
  '📊 Velocity sources → Billbee=%d, default=%d',
  $billbeeSourceCounters['billbee'] ?? 0,
  $billbeeSourceCounters['default'] ?? 0
);
logSection('RUN SUMMARY');
logMsg($usedSummary);
logMsg($sourceSummary);

logMsg("✅ Done. VO OK: $okVO/$voProcessedCount | VO Fail: $failVO/$voProcessedCount | Billbee OK: $okBB/$billbeeProcessedCount | Billbee Fail: $failBB/$billbeeProcessedCount");
if ($skippedNonFbm > 0) {
  logMsg('ℹ️ Billbee skipped non-FBM SKUs: ' . $skippedNonFbm);
}

/* ================= NOTIFICATION (SMTP over STARTTLS, only on failures) =================== */
if ($failVO > 0 || $failBB > 0) {
  // ---- Mail settings (from your message) ----
  $mailer_from_name  = 'Marco Winter';
  $mailer_from_email = 'info@feela.de';
  $mailer_transport  = 'smtp'; // informational
  $mailer_host       = 'w017b812.kasserver.com';
  $mailer_port       = 587; // STARTTLS
  $mailer_user       = 'news@feela.de';
  $mailer_password   = 'zfv3myg.QEA5ruj0vhq';

  $to_email = 'info@feela.de'; // change/add more recipients if needed

  $subject = "⚠️ Inventory Sync Failures on " . date('Y-m-d H:i');
  $htmlBody = "<h2>Inventory Sync Report</h2>"
            . "<p><b>Date:</b> " . date('Y-m-d H:i:s') . "</p>"
            . "<p><b>VentoryOne:</b> $okVO OK / $failVO Fail</p>"
            . "<p><b>Billbee:</b> $okBB OK / $failBB Fail</p>"
            . "<p>See attached log for details.</p>";

  $attachmentPath = LOG_FILE;

  // ---- Helper: read full SMTP response (supports multi-line 250- style) ----
  $smtp_read = function($socket) {
    $data = '';
    while (($line = fgets($socket, 515)) !== false) {
      $data .= $line;
      // If the fourth char is space, it's the last line (e.g., "250 OK")
      if (strlen($line) >= 4 && $line[3] === ' ') break;
    }
    return $data;
  };

  // ---- Build MIME message with optional attachment ----
  $boundary = 'bnd_' . bin2hex(random_bytes(8));
  $headers  = "From: " . addslashes($mailer_from_name) . " <{$mailer_from_email}>\r\n";
  $headers .= "MIME-Version: 1.0\r\n";
  $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

  $message  = "This is a MIME encoded message.\r\n\r\n";
  $message .= "--{$boundary}\r\n";
  $message .= "Content-Type: text/html; charset=\"UTF-8\"\r\n";
  $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
  $message .= $htmlBody . "\r\n\r\n";

  if (is_file($attachmentPath) && is_readable($attachmentPath)) {
    $fileData  = chunk_split(base64_encode(file_get_contents($attachmentPath)));
    $filename  = basename($attachmentPath);
    $message .= "--{$boundary}\r\n";
    $message .= "Content-Type: text/plain; name=\"{$filename}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $message .= $fileData . "\r\n\r\n";
  }
  $message .= "--{$boundary}--\r\n";

  // ---- SMTP send with STARTTLS ----
  $socket = fsockopen($mailer_host, $mailer_port, $errno, $errstr, 20);
  if (!$socket) {
    logMsg("⚠️ SMTP connect failed: $errstr ($errno)");
  } else {
    $resp = $smtp_read($socket); // server greeting
    fputs($socket, "EHLO all-inkl\r\n");
    $resp = $smtp_read($socket);

    // STARTTLS
    fputs($socket, "STARTTLS\r\n");
    $resp = $smtp_read($socket);
    if (stripos($resp, '220') !== 0) {
      // server may already require TLS or not support STARTTLS; try to continue
      logMsg("⚠️ STARTTLS not accepted: " . trim($resp));
    } else {
      // enable TLS encryption
      $cryptoOk = stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
      if (!$cryptoOk) {
        logMsg("⚠️ Failed to enable TLS crypto");
      } else {
        // EHLO again after STARTTLS
        fputs($socket, "EHLO all-inkl\r\n");
        $resp = $smtp_read($socket);
      }
    }

    // AUTH LOGIN
    fputs($socket, "AUTH LOGIN\r\n");
    $resp = $smtp_read($socket);
    fputs($socket, base64_encode($mailer_user) . "\r\n");
    $resp = $smtp_read($socket);
    fputs($socket, base64_encode($mailer_password) . "\r\n");
    $resp = $smtp_read($socket);

    // MAIL FROM / RCPT TO / DATA
    fputs($socket, "MAIL FROM:<{$mailer_from_email}>\r\n");
    $resp = $smtp_read($socket);

    // Support multiple recipients (comma-separated)
    $recipients = array_map('trim', explode(',', $to_email));
    foreach ($recipients as $rcpt) {
      if ($rcpt === '') continue;
      fputs($socket, "RCPT TO:<{$rcpt}>\r\n");
      $resp = $smtp_read($socket);
    }

    fputs($socket, "DATA\r\n");
    $resp = $smtp_read($socket);

    // Headers (must include To + Subject inside DATA)
    $toHeader = 'To: ' . implode(', ', $recipients) . "\r\n";
    $subjectHeader = 'Subject: ' . $subject . "\r\n";

    fputs($socket, $toHeader . $subjectHeader . $headers . "\r\n" . $message . "\r\n.\r\n");
    $resp = $smtp_read($socket);

    fputs($socket, "QUIT\r\n");
    $smtp_read($socket);
    fclose($socket);

    logMsg("📧 Failure notification email sent to: " . implode(', ', $recipients));
  }
}

?>
