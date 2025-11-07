<?php
/**
 * Inventory Sync (LIVE)
 * - VentoryOne: set STK – Insgesamt to CSV total (cartons=0, loose=CSV)
 * - Billbee: apply safety deduction before update
 *
 * Run: php inventory_sync.php
 */

if (php_sapi_name() !== 'cli') die('Access denied.');
date_default_timezone_set('Europe/Berlin');
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
define('LOG_DIR', __DIR__ . '/logs');

$logEchoOverride = getenv('SYNC_LOG_ECHO');
if ($logEchoOverride !== false) {
  $value = strtolower(trim($logEchoOverride));
  $logEcho = in_array($value, ['1', 'true', 'yes', 'on'], true);
} else {
  $logEcho = false;
  if (defined('STDOUT')) {
    if (function_exists('stream_isatty') && @stream_isatty(STDOUT)) {
      $logEcho = true;
    } elseif (function_exists('posix_isatty') && @posix_isatty(STDOUT)) {
      $logEcho = true;
    }
  }
  if (!$logEcho) {
    $term = getenv('TERM');
    if (PHP_SAPI === 'cli' && $term && strtolower($term) !== 'dumb') {
      $logEcho = true;
    }
  }
}
define('LOG_ECHO_ENABLED', $logEcho); // quiet for cron, override with SYNC_LOG_ECHO=1

if (!is_dir(LOG_DIR) && !mkdir(LOG_DIR, 0777, true) && !is_dir(LOG_DIR)) {
  fwrite(STDERR, "Cannot create log directory: " . LOG_DIR . PHP_EOL);
  exit(1);
}
define('LOG_FILE', LOG_DIR . '/sync_' . date('Ymd_His') . '.log');

// VentoryOne (2116 = cafol warehouse)
define('VO_BASE', 'https://app.ventory.one');
define('VO_TOKEN', '2d94eb4a8c3c2cef8ad628e3619591069b7156ed');
define('VO_WAREHOUSE_ID', 2116);

// Billbee
define('BILLBEE_API_URL', 'https://app.billbee.io/api/v1/');
define('BILLBEE_USER', 'info@feela.de');
define('BILLBEE_API_PASSWORD', 'LuPWibuTa7Ngn8m');
define('BILLBEE_API_KEY', '2FAA5DE0-18EA-4C4E-88EB-F0394109CF2E');

// Retry & timeouts
define('MAX_RETRIES', 3);
define('RETRY_BASE_MS', 600);
define('CONNECT_TIMEOUT', 15);
define('REQUEST_TIMEOUT', 45);

/* ================ LOGGING ================= */
function logMsg($msg) {
  $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
  if (LOG_ECHO_ENABLED) {
    echo $line;
  }
  file_put_contents(LOG_FILE, $line, FILE_APPEND);
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
function adjustStockForBillbee($stock) {
  // Simple tiered safety stock:
  if ($stock > 50) return max(0, $stock - 20);  // fast
  if ($stock > 20) return max(0, $stock - 10);  // medium
  if ($stock > 5)  return max(0, $stock - 3);   // slow
  return max(0, $stock - 1);                    // very low
}

/* ============ Helpers ============ */
function normalizeVoSku($sku) {
  // VentoryOne uses the base SKU (no "-FBM")
  return preg_replace('/-FBM$/', '', $sku);
}

function extractIntFromMixed($value) {
  if (is_int($value)) return $value;
  if (is_float($value)) return (int)round($value);
  if (is_string($value) && preg_match('/^-?\d+$/', trim($value))) {
    return (int)trim($value);
  }
  return null;
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
      }

      if (!is_array($rows)) {
        $note = 'Unexpected payload';
        break;
      }

      foreach ($rows as $row) {
        if (!is_array($row)) continue;

        $matchedCandidate = null;
        $skuMatches = false;
        foreach (extractSkuCandidates($row) as $candidate) {
          if (strcasecmp($candidate, $skuBase) === 0 || strcasecmp(normalizeVoSku($candidate), $skuBase) === 0) {
            $matchedCandidate = $candidate;
            $skuMatches = true;
            break;
          }
        }

        if (!$skuMatches) continue;

        $warehouseIds = extractWarehouseIds($row);
        if ($warehouseIds && !in_array(VO_WAREHOUSE_ID, $warehouseIds, true)) {
          continue;
        }

        $row['_matched_candidate'] = $matchedCandidate ?: $skuBase;
        $row['_warehouse_ids'] = $warehouseIds;

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

  return null;
}

function voExtractSkuId(array $row) {
  foreach (['sku_id', 'skuId', 'id'] as $field) {
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

function voPostAndCheck($path, array $payload) {
  $url = rtrim(VO_BASE, '/') . '/' . ltrim($path, '/');
  $headers = [
    'Authorization: Bearer ' . VO_TOKEN,
    'Content-Type: application/json',
    'Accept: application/json'
  ];

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
function voSetCartonsZero(array $ident) {
  $entry = ['carton_qty' => 0];

  if (!empty($ident['sku_id'])) {
    $entry['sku_id'] = (int)$ident['sku_id'];
  } else {
    $entry['sku'] = $ident['sku'];
  }

  $payload = [
    'warehouse_id' => VO_WAREHOUSE_ID,
    'sku_qty_list' => [$entry]
  ];

  if (!empty($ident['organization_id'])) {
    $payload['organization_id'] = (int)$ident['organization_id'];
  }

  return voPostAndCheck('/api/update_plain_carton_line_item_qty/', $payload);
}

function voSetLooseToTotal(array $ident, $total) {
  $entry = [
    'pcs_in_loose_stock' => (int)$total
  ];

  if (!empty($ident['sku_id'])) {
    $entry['sku_id'] = (int)$ident['sku_id'];
  } else {
    $entry['sku'] = $ident['sku'];
  }

  $payload = [
    'warehouse_id' => VO_WAREHOUSE_ID,
    'sku_qty_list' => [$entry]
  ];

  if (!empty($ident['organization_id'])) {
    $payload['organization_id'] = (int)$ident['organization_id'];
  }

  return voPostAndCheck('/api/update_loose_stock/', $payload);
}

function voVerifyLooseEquals($skuBase, $expect) {
  $note = null;
  $row = voFetchStockEntry($skuBase, $note);
  if (!$row) {
    return [false, $note];
  }

  [$metric, $fieldUsed] = voExtractMetricDetails($row);
  if ($metric === null) {
    $debugKeys = implode(',', array_keys($row));
    logMsg("ℹ️ VO metric scan failed for $skuBase | keys=$debugKeys");
    return [false, 'stock-metric-missing'];
  }

  $parts = [];
  $parts[] = $fieldUsed . '=' . $metric;
  if (isset($row['_matched_candidate']) && strcasecmp($row['_matched_candidate'], $skuBase) !== 0) {
    $parts[] = '(matched ' . $row['_matched_candidate'] . ')';
  }
  if (!empty($row['_warehouse_ids'])) {
    $parts[] = 'warehouses=' . implode(',', $row['_warehouse_ids']);
  }

  return [$metric === (int)$expect, implode(' ', $parts)];
}

function updateVentoryTotal($csvSku, $total) {
  $skuBase = normalizeVoSku($csvSku);

  $lookupNote = null;
  $lookupRow = voFetchStockEntry($skuBase, $lookupNote);
  $ident = [
    'sku' => $skuBase,
    'sku_id' => $lookupRow ? voExtractSkuId($lookupRow) : null,
    'organization_id' => $lookupRow ? voExtractOrganizationId($lookupRow) : null,
  ];

  if (!$lookupRow && $lookupNote) {
    logMsg("ℹ️ VO lookup $skuBase before update: $lookupNote");
  }

  [$okC, $cartonNote] = voSetCartonsZero($ident);
  if (!$okC) {
    logMsg("⚠️ VO carton reset $skuBase failed: $cartonNote");
  }

  [$okL, $looseNote] = voSetLooseToTotal($ident, $total);
  if (!$okL) {
    logMsg("⚠️ VO loose set $skuBase failed: $looseNote");
  }

  if ($okC && $okL) {
    // verify (after async processing it may take a moment; still try once)
    [$okV, $note] = voVerifyLooseEquals($skuBase, $total);
    if ($okV) {
      logMsg("✅ VO OK $skuBase → STK–Insgesamt=$total (cartons=0, loose=$total)");
    } else {
      logMsg("✅ VO submitted $skuBase total=$total (verify pending: $note)");
    }
    return true;
  }

  logMsg("❌ VO FAIL $skuBase → total=$total (cartonsZero=" . ($okC?'OK':'FAIL') . ", looseSet=" . ($okL?'OK':'FAIL') . ")");
  return false;
}

/* ============== Billbee ============== */
function updateBillbee($sku, $qty) {
  $url = BILLBEE_API_URL . 'products/updatestock';
  $auth = base64_encode(BILLBEE_USER . ':' . BILLBEE_API_PASSWORD);
  $headers = [
    'Authorization: Basic ' . $auth,
    'X-Billbee-Api-Key: ' . BILLBEE_API_KEY,
    'Content-Type: application/json',
    'Accept: application/json'
  ];
  $payload = [
    'Sku'         => $sku,
    'NewQuantity' => (int)$qty,
    'Reason'      => 'Automated sync via Feela'
  ];
  [$code, $resp] = httpJsonWithRetry($url, 'POST', $headers, $payload);

  if ($code >= 200 && $code < 300) {
    logMsg("✅ Billbee OK $sku → $qty");
    return true;
  }
  $preview = $resp ? substr($resp, 0, 240) : 'no-body';
  logMsg("❌ Billbee FAIL $sku HTTP $code | Resp: $preview");
  return false;
}

/* ================= MAIN =================== */
logMsg("========== INVENTORY SYNC (LIVE) ==========");
$csv  = downloadLatestCSV();
$rows = parseCSV($csv);

$count = count($rows);
logMsg("📦 Found $count SKUs in stock file");

$okVO = 0; $failVO = 0; $okBB = 0; $failBB = 0;

foreach ($rows as $r) {
  $csvSku = $r['sku'];
  $stock  = (int)$r['stock'];

  // --- VentoryOne: STK – Insgesamt = CSV total (cartons=0, loose=stock) ---
  if (updateVentoryTotal($csvSku, $stock)) $okVO++; else $failVO++;

  // --- Billbee: safety stock logic ---
  $bbQty = adjustStockForBillbee($stock);
  updateBillbee($csvSku, $bbQty) ? $okBB++ : $failBB++;
}

logMsg("✅ Done. VO OK: $okVO/$count | VO Fail: $failVO/$count | Billbee OK: $okBB/$count | Billbee Fail: $failBB/$count");
