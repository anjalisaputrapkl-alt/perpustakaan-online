<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../src/db.php';

// Get filter parameters
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';
$category = $_GET['category'] ?? '';

// Validate and sanitize dates
if ($start_date) {
  try {
    new DateTime($start_date);
  } catch (Exception $e) {
    $start_date = '';
  }
}

if ($end_date) {
  try {
    new DateTime($end_date);
  } catch (Exception $e) {
    $end_date = '';
  }
}

// Sanitize category
$category = htmlspecialchars(trim($category), ENT_QUOTES, 'UTF-8');

try {
  // Check if category column exists
  $hasCategory = false;
  try {
    $result = $pdo->query("SELECT 1 FROM books LIMIT 1");
    $columns = $result->fetch(PDO::FETCH_ASSOC);
    if ($columns && isset($columns['category'])) {
      $hasCategory = true;
    }
  } catch (Exception $e) {
    $hasCategory = false;
  }

  // ============ TREND (Last 30 days with filter) ============
  $trend_labels = [];
  $trend_data = [];
  
  $sql = "SELECT DATE(borrowed_at) as d, COUNT(*) as c FROM borrows WHERE 1=1";
  if ($start_date) {
    $sql .= " AND DATE(borrowed_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $sql .= " AND DATE(borrowed_at) <= DATE('" . addslashes($end_date) . "')";
  }
  if (!$start_date && !$end_date) {
    $sql .= " AND borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY)";
  }
  $sql .= " GROUP BY DATE(borrowed_at) ORDER BY d ASC";

  $trend = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  
  // Generate date range
  if ($start_date && $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('+1 day');
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
  } else {
    $start = new DateTime('-29 days');
    $end = new DateTime('+1 day');
    $period = new DatePeriod($start, new DateInterval('P1D'), $end);
  }
  
  $map = [];
  foreach ($trend as $t) {
    $map[$t['d']] = (int) $t['c'];
  }
  
  foreach ($period as $day) {
    $k = $day->format('Y-m-d');
    $trend_labels[] = $k;
    $trend_data[] = $map[$k] ?? 0;
  }

  // ============ CATEGORY (Most borrowed) ============
  $category_labels = [];
  $category_data = [];
  
  if ($hasCategory) {
    $sql = "SELECT b.category, COUNT(*) as c FROM borrows br JOIN books b ON br.book_id = b.id WHERE 1=1";
    if ($start_date) {
      $sql .= " AND DATE(br.borrowed_at) >= DATE('" . addslashes($start_date) . "')";
    }
    if ($end_date) {
      $sql .= " AND DATE(br.borrowed_at) <= DATE('" . addslashes($end_date) . "')";
    }
    if (!$start_date && !$end_date) {
      $sql .= " AND br.borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY)";
    }
    $sql .= " GROUP BY b.category ORDER BY c DESC LIMIT 10";
    
    $cats = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cats as $r) {
      $category_labels[] = $r['category'] ?: 'Uncategorized';
      $category_data[] = (int) $r['c'];
    }
  }

  // ============ MEMBERS per month ============
  $mem_labels = [];
  $mem_data = [];
  
  $sql = "SELECT DATE_FORMAT(created_at,'%Y-%m') month, COUNT(*) c FROM members WHERE 1=1";
  if ($start_date) {
    $sql .= " AND DATE(created_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $sql .= " AND DATE(created_at) <= DATE('" . addslashes($end_date) . "')";
  }
  if (!$start_date && !$end_date) {
    $sql .= " AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 11 MONTH)";
  }
  $sql .= " GROUP BY month ORDER BY month ASC";
  
  $mem = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
  
  // Generate month range
  if ($start_date && $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $end->modify('first day of next month');
    $period = new DatePeriod($start, new DateInterval('P1M'), $end);
  } else {
    $start = new DateTime('first day of -11 months');
    $end = new DateTime('first day of next month');
    $period = new DatePeriod($start, new DateInterval('P1M'), $end);
  }
  
  $map = [];
  foreach ($mem as $m) {
    $map[$m['month']] = (int) $m['c'];
  }
  
  foreach ($period as $d) {
    $k = $d->format('Y-m');
    $mem_labels[] = $k;
    $mem_data[] = $map[$k] ?? 0;
  }

  // ============ HEATMAP (Borrows per hour) ============
  $sql = "SELECT HOUR(borrowed_at) h, COUNT(*) c FROM borrows WHERE 1=1";
  if ($start_date) {
    $sql .= " AND DATE(borrowed_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $sql .= " AND DATE(borrowed_at) <= DATE('" . addslashes($end_date) . "')";
  }
  if (!$start_date && !$end_date) {
    $sql .= " AND borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 29 DAY)";
  }
  $sql .= " GROUP BY h";
  
  $hours = array_fill(0, 24, 0);
  $maxHour = 1;
  foreach ($pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $hours[(int) $r['h']] = (int) $r['c'];
    $maxHour = max($maxHour, (int) $r['c']);
  }
  
  $heatmap_data = [];
  for ($h = 0; $h < 24; $h++) {
    $v = $hours[$h];
    $intensity = min(1, $v / max(1, $maxHour));
    $heatmap_data[] = [
      'hour' => sprintf('%02d:00', $h),
      'value' => $v,
      'intensity' => 0.12 + ($intensity * 0.6)
    ];
  }

  // ============ BORROWS TABLE ============
  $sql = "SELECT br.id, br.borrowed_at, b.title as book_title, m.name as member_name, br.status, br.due_at, br.returned_at 
          FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id WHERE 1=1";
  if ($start_date) {
    $sql .= " AND DATE(br.borrowed_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $sql .= " AND DATE(br.borrowed_at) <= DATE('" . addslashes($end_date) . "')";
  }
  if ($category && $hasCategory) {
    $sql .= " AND b.category = '" . addslashes($category) . "'";
  }
  $sql .= " ORDER BY br.borrowed_at DESC LIMIT 500";

  $borrowTable = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ============ RETURNS TABLE ============
  $sql = "SELECT br.id, br.borrowed_at, br.returned_at, DATEDIFF(br.returned_at, br.due_at) as days_late, b.title as book_title, m.name as member_name 
          FROM borrows br JOIN books b ON br.book_id=b.id JOIN members m ON br.member_id=m.id WHERE br.returned_at IS NOT NULL";
  if ($start_date) {
    $sql .= " AND DATE(br.returned_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $sql .= " AND DATE(br.returned_at) <= DATE('" . addslashes($end_date) . "')";
  }
  if ($category && $hasCategory) {
    $sql .= " AND b.category = '" . addslashes($category) . "'";
  }
  $sql .= " ORDER BY br.returned_at DESC LIMIT 500";

  $returnsTable = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ============ BOOKS TABLE ============
  $sql = "SELECT id, title, author, copies, created_at FROM books WHERE 1=1";
  if ($start_date) {
    $sql .= " AND DATE(created_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $sql .= " AND DATE(created_at) <= DATE('" . addslashes($end_date) . "')";
  }
  if ($category && $hasCategory) {
    $sql .= " AND category = '" . addslashes($category) . "'";
  }
  $sql .= " ORDER BY title LIMIT 1000";

  $booksTable = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

  // ============ STATS ============
  // Total books
  $tot_books = (int) $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();

  // Borrows this month (within filter range or current month)
  if ($start_date && $end_date) {
    $borrows_month_sql = "SELECT COUNT(*) FROM borrows WHERE DATE(borrowed_at) >= DATE('" . addslashes($start_date) . "') AND DATE(borrowed_at) <= DATE('" . addslashes($end_date) . "')";
    if ($category && $hasCategory) {
      $borrows_month_sql = "SELECT COUNT(*) FROM borrows br JOIN books b ON br.book_id = b.id WHERE DATE(br.borrowed_at) >= DATE('" . addslashes($start_date) . "') AND DATE(br.borrowed_at) <= DATE('" . addslashes($end_date) . "') AND b.category = '" . addslashes($category) . "'";
    }
  } else {
    $borrows_month_sql = "SELECT COUNT(*) FROM borrows WHERE MONTH(borrowed_at) = MONTH(CURRENT_DATE()) AND YEAR(borrowed_at)=YEAR(CURRENT_DATE())";
  }
  $tot_borrows_month = (int) $pdo->query($borrows_month_sql)->fetchColumn();

  // Returns this month
  if ($start_date && $end_date) {
    $returns_month_sql = "SELECT COUNT(*) FROM borrows WHERE returned_at IS NOT NULL AND DATE(returned_at) >= DATE('" . addslashes($start_date) . "') AND DATE(returned_at) <= DATE('" . addslashes($end_date) . "')";
    if ($category && $hasCategory) {
      $returns_month_sql = "SELECT COUNT(*) FROM borrows br JOIN books b ON br.book_id = b.id WHERE returned_at IS NOT NULL AND DATE(br.returned_at) >= DATE('" . addslashes($start_date) . "') AND DATE(br.returned_at) <= DATE('" . addslashes($end_date) . "') AND b.category = '" . addslashes($category) . "'";
    }
  } else {
    $returns_month_sql = "SELECT COUNT(*) FROM borrows WHERE returned_at IS NOT NULL AND MONTH(returned_at)=MONTH(CURRENT_DATE()) AND YEAR(returned_at)=YEAR(CURRENT_DATE())";
  }
  $tot_returns_month = (int) $pdo->query($returns_month_sql)->fetchColumn();

  // Active members (90 days or within filter range)
  if ($start_date && $end_date) {
    $active_sql = "SELECT COUNT(DISTINCT br.member_id) FROM borrows br WHERE DATE(br.borrowed_at) >= DATE('" . addslashes($start_date) . "') AND DATE(br.borrowed_at) <= DATE('" . addslashes($end_date) . "')";
    if ($category && $hasCategory) {
      $active_sql = "SELECT COUNT(DISTINCT br.member_id) FROM borrows br JOIN books b ON br.book_id = b.id WHERE DATE(br.borrowed_at) >= DATE('" . addslashes($start_date) . "') AND DATE(br.borrowed_at) <= DATE('" . addslashes($end_date) . "') AND b.category = '" . addslashes($category) . "'";
    }
  } else {
    $active_sql = "SELECT COUNT(DISTINCT member_id) FROM borrows WHERE borrowed_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 90 DAY)";
  }
  $active_members = (int) $pdo->query($active_sql)->fetchColumn();

  // Fines (1000 Rp per day late)
  $per_day = 1000;
  $fines = 0;
  $fines_sql = "SELECT due_at, returned_at FROM borrows WHERE due_at IS NOT NULL AND (returned_at IS NOT NULL OR CURRENT_DATE() > due_at)";
  if ($start_date) {
    $fines_sql .= " AND DATE(due_at) >= DATE('" . addslashes($start_date) . "')";
  }
  if ($end_date) {
    $fines_sql .= " AND DATE(due_at) <= DATE('" . addslashes($end_date) . "')";
  }
  
  $fines_rows = $pdo->query($fines_sql)->fetchAll(PDO::FETCH_ASSOC);
  foreach ($fines_rows as $r) {
    try {
      $due = new DateTime($r['due_at']);
      $returned = $r['returned_at'] ? new DateTime($r['returned_at']) : new DateTime();
      $diff = (int) $due->diff($returned)->format('%r%a');
      if ($diff > 0) $fines += $diff * $per_day;
    } catch (Exception $e) {}
  }

  // New members (30 days within filter range or last 30 days)
  if ($start_date && $end_date) {
    $new_mem_sql = "SELECT COUNT(*) FROM members WHERE DATE(created_at) >= DATE('" . addslashes($start_date) . "') AND DATE(created_at) <= DATE('" . addslashes($end_date) . "')";
  } else {
    $new_mem_sql = "SELECT COUNT(*) FROM members WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
  }
  $new_members_30 = (int) $pdo->query($new_mem_sql)->fetchColumn();

  // New books (30 days within filter range or last 30 days)
  if ($start_date && $end_date) {
    $new_books_sql = "SELECT COUNT(*) FROM books WHERE DATE(created_at) >= DATE('" . addslashes($start_date) . "') AND DATE(created_at) <= DATE('" . addslashes($end_date) . "')";
    if ($category && $hasCategory) {
      $new_books_sql .= " AND category = '" . addslashes($category) . "'";
    }
  } else {
    $new_books_sql = "SELECT COUNT(*) FROM books WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)";
    if ($category && $hasCategory) {
      $new_books_sql .= " AND category = '" . addslashes($category) . "'";
    }
  }
  $new_books_30 = (int) $pdo->query($new_books_sql)->fetchColumn();

  // Return JSON response
  echo json_encode([
    'trend' => [
      'labels' => $trend_labels,
      'data' => $trend_data
    ],
    'category' => [
      'labels' => $category_labels,
      'data' => $category_data
    ],
    'members' => [
      'labels' => $mem_labels,
      'data' => $mem_data
    ],
    'heatmap' => $heatmap_data,
    'borrows_table' => $borrowTable,
    'returns_table' => $returnsTable,
    'books_table' => $booksTable,
    'stats' => [
      'tot_books' => $tot_books,
      'borrows_month' => $tot_borrows_month,
      'returns_month' => $tot_returns_month,
      'active_members' => $active_members,
      'fines' => $fines,
      'new_members_30' => $new_members_30,
      'new_books_30' => $new_books_30
    ],
    'hasCategory' => $hasCategory
  ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

} catch (Exception $e) {
  http_response_code(400);
  echo json_encode([
    'error' => $e->getMessage(),
    'file' => $e->getFile(),
    'line' => $e->getLine()
  ], JSON_PRETTY_PRINT);
  exit;
}
