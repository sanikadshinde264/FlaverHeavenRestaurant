<?php
session_start();
require_once __DIR__ . '/../api/config.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? 'user') !== 'admin') {
  header('Location: login.php');
  exit;
}

$daily = $conn->query("SELECT DATE(created_at) AS period, SUM(total_amount) AS revenue FROM orders WHERE payment_status='paid' GROUP BY DATE(created_at) ORDER BY period DESC LIMIT 7")->fetch_all(MYSQLI_ASSOC);
$weekly = $conn->query("SELECT YEARWEEK(created_at, 1) AS period, SUM(total_amount) AS revenue FROM orders WHERE payment_status='paid' GROUP BY YEARWEEK(created_at, 1) ORDER BY period DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);
$monthly = $conn->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS period, SUM(total_amount) AS revenue FROM orders WHERE payment_status='paid' GROUP BY DATE_FORMAT(created_at, '%Y-%m') ORDER BY period DESC LIMIT 12")->fetch_all(MYSQLI_ASSOC);
$yearly = $conn->query("SELECT YEAR(created_at) AS period, SUM(total_amount) AS revenue FROM orders WHERE payment_status='paid' GROUP BY YEAR(created_at) ORDER BY period DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
$orders = $conn->query("SELECT created_at, items_json FROM orders WHERE payment_status='paid' ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
$topCustomers = $conn->query("SELECT u.username, SUM(o.total_amount) AS revenue FROM orders o LEFT JOIN users u ON u.id = o.user_id GROUP BY u.id ORDER BY revenue DESC LIMIT 8")->fetch_all(MYSQLI_ASSOC);

$seriesByType = [
  'daily' => array_reverse($daily),
  'weekly' => array_reverse($weekly),
  'monthly' => array_reverse($monthly),
  'yearly' => array_reverse($yearly),
];

$colors = ['#d4a359', '#f0c36d', '#b1752b', '#e7a247', '#8d4f1f', '#f7d27e', '#a76f21'];

function formatPeriodLabel($period, $type) {
  if ($type === 'daily') {
    return date('M d', strtotime($period));
  }
  if ($type === 'weekly') {
    return 'Week ' . substr($period, -2);
  }
  if ($type === 'monthly') {
    return date('M Y', strtotime($period . '-01'));
  }
  return (string) $period;
}

function buildPieStyle($series, $colors) {
  $total = 0;
  foreach ($series as $item) {
    $total += (float) ($item['revenue'] ?? 0);
  }

  if ($total <= 0) {
    return 'background: #2b2b2f;';
  }

  $segments = [];
  $start = 0;
  $colorCount = count($colors);
  foreach ($series as $index => $item) {
    $value = (float) ($item['revenue'] ?? 0);
    $pct = $total > 0 ? ($value / $total) * 100 : 0;
    $end = $start + $pct;
    $segments[] = $colors[$index % $colorCount] . ' ' . round($start, 2) . '% ' . round($end, 2) . '%';
    $start = $end;
  }

  return 'background: conic-gradient(' . implode(', ', $segments) . ');';
}

// Builds a pie chart + legend that break revenue down BY DISH for the
// most recent period of the given type (daily/weekly/monthly), instead of
// by period. This is what actually answers "which items are selling" and
// keeps the legend in sync with the wedges (previously the pie showed
// revenue-per-period while the tooltip showed a single dish name, so the
// two never matched and it looked like only one item was ever shown).
function buildDishPieData($foods, $colors) {
  $total = 0;
  foreach ($foods as $stats) {
    $total += (float) ($stats['revenue'] ?? 0);
  }

  if ($total <= 0 || empty($foods)) {
    return ['style' => 'background: #2b2b2f;', 'legend' => []];
  }

  $segments = [];
  $legend = [];
  $start = 0;
  $colorCount = count($colors);
  $index = 0;
  foreach ($foods as $name => $stats) {
    $value = (float) ($stats['revenue'] ?? 0);
    $pct = ($value / $total) * 100;
    $end = $start + $pct;
    $color = $colors[$index % $colorCount];
    $segments[] = $color . ' ' . round($start, 2) . '% ' . round($end, 2) . '%';
    $legend[] = [
      'name' => $name,
      'revenue' => $value,
      'qty' => (int) ($stats['qty'] ?? 0),
      'color' => $color,
      'start' => round($start, 2),
      'end' => round($end, 2)
    ];
    $start = $end;
    $index++;
  }

  return ['style' => 'background: conic-gradient(' . implode(', ', $segments) . ');', 'legend' => $legend];
}

function buildRecommendationData($orders, $type) {
  $groups = [];

  foreach ($orders as $order) {
    $periodKey = '';
    $createdAt = $order['created_at'] ?? '';
    $timestamp = strtotime($createdAt);

    if ($type === 'daily') {
      $periodKey = date('Y-m-d', $timestamp);
    } elseif ($type === 'weekly') {
      $periodKey = date('Y-W', $timestamp);
    } elseif ($type === 'monthly') {
      $periodKey = date('Y-m', $timestamp);
    } else {
      $periodKey = date('Y', $timestamp);
    }

    $items = json_decode($order['items_json'] ?? '[]', true);
    if (!is_array($items)) {
      continue;
    }

    if (!isset($groups[$periodKey])) {
      $groups[$periodKey] = [];
    }

    foreach ($items as $item) {
      $name = trim((string) ($item['name'] ?? $item['item_name'] ?? $item['title'] ?? ''));
      if ($name === '') {
        continue;
      }
      $qty = max(1, (int) ($item['qty'] ?? $item['quantity'] ?? 1));
      $price = (float) ($item['price'] ?? 0);

      if (!isset($groups[$periodKey][$name])) {
        $groups[$periodKey][$name] = ['qty' => 0, 'revenue' => 0];
      }

      $groups[$periodKey][$name]['qty'] += $qty;
      $groups[$periodKey][$name]['revenue'] += $price * $qty;
    }
  }

  if (!$groups) {
    return ['label' => 'No data yet', 'foods' => []];
  }

  $periods = array_keys($groups);
  sort($periods);
  $latestPeriod = end($periods);
  $foods = $groups[$latestPeriod] ?? [];

  uasort($foods, function ($a, $b) {
    if ($b['qty'] !== $a['qty']) {
      return $b['qty'] <=> $a['qty'];
    }
    return $b['revenue'] <=> $a['revenue'];
  });

  return [
    'label' => formatPeriodLabel($latestPeriod, $type),
    'foods' => array_slice($foods, 0, 3, true),
  ];
}

$recommendations = [];
foreach (['daily', 'weekly', 'monthly', 'yearly'] as $periodType) {
  $recommendations[$periodType] = buildRecommendationData($orders, $periodType);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports | Flaver Heaven</title>
  <link rel="stylesheet" href="admin.css" />
</head>
<body>
<div class="admin-shell">
  <?php include_once 'sidebar.php'; ?>
  <main class="main">
    <div class="topbar"><div><h2 class="page-title">Reports</h2><p class="page-subtitle">Sales and customer insights.</p></div></div>
    <div class="cards">
      <div class="card"><h3>Daily Sales</h3><div class="value"><?php echo count($seriesByType['daily']); ?> Records</div></div>
      <div class="card"><h3>Weekly Sales</h3><div class="value"><?php echo count($seriesByType['weekly']); ?> Weeks</div></div>
      <div class="card"><h3>Monthly Sales</h3><div class="value"><?php echo count($seriesByType['monthly']); ?> Months</div></div>
      <div class="card"><h3>Yearly Sales</h3><div class="value"><?php echo count($seriesByType['yearly']); ?> Years</div></div>
    </div>

    <div class="analysis-grid">
      <?php foreach (['daily' => 'Daily Analysis', 'weekly' => 'Weekly Analysis', 'monthly' => 'Monthly Analysis', 'yearly' => 'Yearly Analysis'] as $key => $title): ?>
        <?php $series = $seriesByType[$key] ?? []; ?>
        <?php $foods = $recommendations[$key]['foods'] ?? []; ?>
        <?php $pieData = buildDishPieData($foods, $colors); ?>
        <?php $topDishName = !empty($foods) ? array_key_first($foods) : ''; ?>
        <?php $tooltipText = $topDishName ? 'Top: ' . $topDishName : 'No dish data'; ?>
        <div class="panel analysis-panel">
          <div class="panel-header">
            <h3 class="panel-title"><?php echo htmlspecialchars($title); ?></h3>
            <span class="panel-pill">₹<?php echo number_format(array_sum(array_column($series, 'revenue')), 2); ?></span>
          </div>
          <div class="analysis-layout">
            <div class="pie-chart-wrap" data-tooltip="<?php echo htmlspecialchars($tooltipText); ?>">
              <div class="pie-chart"
                   style="<?php echo htmlspecialchars($pieData['style']); ?>"
                   data-segments="<?php echo htmlspecialchars(json_encode($pieData['legend'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8'); ?>">
              </div>
              <div class="pie-tooltip"><?php echo htmlspecialchars($tooltipText); ?></div>
            </div>
            <div class="legend-list">
              <?php if (empty($pieData['legend'])): ?>
                <div class="empty-state">No dish sales in this period yet.</div>
              <?php else: ?>
                <?php foreach ($pieData['legend'] as $entry): ?>
                  <div class="legend-item">
                    <span class="legend-swatch" style="background:<?php echo htmlspecialchars($entry['color']); ?>"></span>
                    <div>
                      <strong><?php echo htmlspecialchars($entry['name']); ?></strong>
                      <div class="legend-meta"><?php echo (int) $entry['qty']; ?> sold • ₹<?php echo number_format($entry['revenue'], 2); ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="panel">
      <div class="panel-header">
        <h3 class="panel-title">Most Recommended Dishes</h3>
      </div>
      <div class="recommendation-grid">
        <?php foreach ($recommendations as $periodType => $recommendation): ?>
          <div class="recommendation-card">
            <h4><?php echo ucfirst($periodType); ?></h4>
            <p class="recommendation-period"><?php echo htmlspecialchars($recommendation['label']); ?></p>
            <?php if (!empty($recommendation['foods'])): ?>
              <ol class="recommendation-list">
                <?php foreach ($recommendation['foods'] as $foodName => $stats): ?>
                  <li>
                    <strong><?php echo htmlspecialchars($foodName); ?></strong>
                    <span><?php echo (int) ($stats['qty'] ?? 0); ?> orders • ₹<?php echo number_format((float) ($stats['revenue'] ?? 0), 2); ?></span>
                  </li>
                <?php endforeach; ?>
              </ol>
            <?php else: ?>
              <div class="empty-state">No food data yet.</div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header">
        <h3 class="panel-title">Top Customers</h3>
      </div>
      <div class="table-wrap"><table class="table"><thead><tr><th>Customer</th><th>Revenue</th></tr></thead><tbody><?php foreach ($topCustomers as $customer): ?><tr><td><?php echo htmlspecialchars($customer['username'] ?? 'Guest'); ?></td><td>₹<?php echo number_format((float)$customer['revenue'], 2); ?></td></tr><?php endforeach; ?></tbody></table></div>
    </div>
  </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.pie-chart-wrap').forEach((wrap) => {
    const chart = wrap.querySelector('.pie-chart');
    const tooltip = wrap.querySelector('.pie-tooltip');
    if (!tooltip || !chart) return;

    const defaultText = wrap.getAttribute('data-tooltip') || tooltip.textContent;
    const segments = chart.getAttribute('data-segments');
    const parsedSegments = segments ? JSON.parse(segments) : [];

    const getSegmentForPoint = (event) => {
      if (!parsedSegments.length) return null;

      const rect = chart.getBoundingClientRect();
      const centerX = rect.left + rect.width / 2;
      const centerY = rect.top + rect.height / 2;
      const x = event.clientX - centerX;
      const y = event.clientY - centerY;
      const angle = (Math.atan2(y, x) * 180 / Math.PI + 360 + 90) % 360;
      const percent = angle / 360 * 100;

      return parsedSegments.find((segment) => percent >= segment.start && percent <= segment.end) || parsedSegments[0];
    };

    const showTooltip = (event) => {
      const segment = getSegmentForPoint(event);
      const text = segment
        ? `${segment.name} • ${segment.qty} sold • ₹${Number(segment.revenue).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
        : defaultText;
      tooltip.textContent = text;
      tooltip.classList.add('is-visible');
    };

    const hideTooltip = () => tooltip.classList.remove('is-visible');

    wrap.addEventListener('mouseenter', showTooltip);
    wrap.addEventListener('mousemove', showTooltip);
    wrap.addEventListener('mouseleave', hideTooltip);
  });
});
</script>
</body>
</html>