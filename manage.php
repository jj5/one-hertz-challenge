<?php declare(strict_types=1);

require_once __DIR__ . '/common.php';

header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {

  error_log( 'POST received: ' . json_encode($_POST) );

    // Save posted JSON data
    $json = $_POST['data'] ?? '';
    if ($json === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No data provided.']);
        exit;
    }

    // Decode to validate and sanitize
    $decoded = json_decode($json, true);
    if (!is_array($decoded)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON.']);
        exit;
    }

    $clean = [];
    $ids = [];
    foreach ($decoded as $i => $row) {
        // Basic required shape: id, url, project_name, rank, timelords, ridiculous, clockwork, could_have_used_a_555, notes
        $id = isset($row['id']) ? (int)$row['id'] : null;
        if ($id === null || $id < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Row $i: invalid id."]);
            exit;
        }
        if (in_array($id, $ids, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => "Duplicate id $id in row $i."]);
            exit;
        }
        $ids[] = $id;

        $url = isset($row['url']) ? trim((string)$row['url']) : '';
        $project_name = isset($row['project_name']) ? trim((string)$row['project_name']) : '';
        $rank = isset($row['rank']) ? (int)$row['rank'] : 0;
        if ($rank < 0) $rank = 0;
        $timelords = !empty($row['timelords']) ? true : false;
        $ridiculous = !empty($row['ridiculous']) ? true : false;
        $clockwork = !empty($row['clockwork']) ? true : false;
        $could_have_used_a_555 = !empty($row['could_have_used_a_555']) ? true : false;
        $notes = isset($row['notes']) ? trim((string)$row['notes']) : '';

        $clean[] = [
            'id' => $id,
            'url' => $url,
            'project_name' => $project_name,
            'rank' => $rank,
            'timelords' => $timelords,
            'ridiculous' => $ridiculous,
            'clockwork' => $clockwork,
            'could_have_used_a_555' => $could_have_used_a_555,
            'notes' => $notes,
        ];
    }

    // Pretty-print JSON
    $out = json_encode($clean, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($out === false) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to encode JSON.']);
        exit;
    }

    if ( false ) {

      // Atomic write: write to temp then rename
      $tmp = APP_STATE_PATH . '.tmp';
      if (file_put_contents($tmp, $out) === false) {
          http_response_code(500);
          echo json_encode(['success' => false, 'message' => 'Failed to write temp file.']);
          exit;
      }
      if (!rename($tmp, APP_STATE_PATH)) {
          // try to remove tmp
          @unlink($tmp);
          http_response_code(500);
          echo json_encode(['success' => false, 'message' => 'Failed to move temp file to data file.']);
          exit;
      }

    }
    else {

      if (file_put_contents( APP_STATE_PATH, $out) === false) {
          http_response_code(500);
          echo json_encode(['success' => false, 'message' => 'Failed to write JSON file.']);
          exit;
      }

    }

    echo json_encode(['success' => true, 'message' => 'Saved successfully.', 'file' => basename(APP_STATE_PATH)]);
    exit;
}

// Otherwise render the page and load existing data
$rows = [];
if (is_file(APP_STATE_PATH)) {
    $text = file_get_contents(APP_STATE_PATH);
    $maybe = json_decode($text, true);
    if (is_array($maybe)) {
        $rows = $maybe;
    }
}

// helper for output escaping
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Table Manager — JSON persistence</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; margin:20px}
  table{border-collapse:collapse;}
  th,td{border:1px solid #ddd;padding:6px 8px;text-align:left;vertical-align:top}
  th{background:#f3f3f3}
  input[type="text"], select, textarea {width:100%;box-sizing:border-box}
  .controls{margin:12px 0}
  button{margin-right:8px;padding:6px 10px}
  .id-cell{width:70px}
  .rank-cell{width:90px}
  .enabled-cell{width:90px;text-align:center}
  .actions-cell{width:120px}
  .message{margin:10px 0;padding:8px;border-radius:4px;display:none}
  .message.success{background:#e6ffed;border:1px solid #6fd08b}
  .message.error{background:#ffecec;border:1px solid #f08b8b}
  .small{font-size:0.9rem;color:#666}
</style>
</head>
<body>
<h1>Table Manager (saved as JSON)</h1>

<div class="controls">
  <button id="saveBtn">💾 Save to JSON</button>
  <button id="loadBtn">🔄 Load from JSON (refresh)</button>
  <span class="small">Saved file: <strong><?php echo h(basename(APP_STATE_PATH)); ?></strong></span>
  <?php if (!is_writable(dirname(APP_STATE_PATH))): ?>
    <div style="color: #b33; margin-top:6px;">Warning: directory not writable — saving will fail unless server permissions allow writing to this folder.</div>
  <?php endif; ?>
</div>

<div id="msg" class="message"></div>

<div style="margin-bottom:1rem"><a href="export.php">Export to CSV</a></div>


<form id="tableForm" onsubmit="return false;">
<table id="dataTable" aria-describedby="tableDescription">
  <caption id="tableDescription" class="small" style="caption-side:bottom;text-align:left">Edit cells and click Save. IDs are numeric and preserved.</caption>
  <thead>
    <tr>
      <th class="actions-cell" style="width:12rem;">Actions</th>
      <th class="id-cell">ID</th>
      <th>Project</th>
      <th class="rank-cell">Rank (1-10)</th>
      <th class="timelords-cell">Timelords</th>
      <th class="ridiculous-cell">Ridiculous</th>
      <th class="clockwork-cell">Clockwork</th>
      <th class="could-have-used-a-555-cell">Could Have Used a 555</th>
      <th>Notes</th>
    </tr>
  </thead>
  <tbody>
  <?php
    if (count($rows) === 0):

      // 2025-08-30 jj5 - ignore this...

    else:
      foreach ($rows as $row):
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        $url = $row['url'] ?? '';
        $project_name = $row['project_name'] ?? '';
        $rank = isset($row['rank']) ? (int)$row['rank'] : 0;
        $timelords = !empty($row['timelords']);
        $ridiculous = !empty($row['ridiculous']);
        $clockwork = !empty($row['clockwork']);
        $could_have_used_a_555 = !empty($row['could_have_used_a_555']);
        $notes = $row['notes'] ?? '';
  ?>
    <tr>
      <td class="id-cell row-id"><?php echo h($id); ?></td>
      <td class="project-cell row-project"><a href="<?= h( $url ) ?>" target="hackaday" rel="noopener"><?php echo h($project_name); ?></a></td>
      <td class="rank-cell">
        <input type="text" class="row-rank" value="<?php echo h($rank); ?>" />
      </td>
      <td class="timelords-cell"><input type="checkbox" class="row-timelords" <?php if ($timelords) echo 'checked'; ?> /></td>
      <td class="ridiculous-cell"><input type="checkbox" class="row-ridiculous" <?php if ($ridiculous) echo 'checked'; ?> /></td>
      <td class="clockwork-cell"><input type="checkbox" class="row-clockwork" <?php if ($clockwork) echo 'checked'; ?> /></td>
      <td class="could-have-used-a-555-cell"><input type="checkbox" class="row-could-have-used-a-555" <?php if ($could_have_used_a_555) echo 'checked'; ?> /></td>
      <td><textarea class="row-notes" rows="1"><?php echo h($notes); ?></textarea></td>
    </tr>
  <?php
      endforeach;
    endif;
  ?>
  </tbody>
</table>
</form>


<script src="res/table.js"></script>

<script src="res/submit.js"></script>

</body>
</html>