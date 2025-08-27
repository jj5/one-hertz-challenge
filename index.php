<?php

main();

function main() {

  $csv_file = get_csv( __DIR__ . '/dat' );

  if ( ! file_exists( $csv_file ) ) {

    echo "Please copy your CSV into the 'dat' subdirectory.";

    return;

  }

  //echo $csv_file;

  render_head();

  echo "<table>\n";

  $n = 0;

  process_csv( $csv_file, $n );

  echo "</table>\n";

  render_foot();

}

function process_csv( $csv_file, &$n ) {

  $path = $csv_file;

  if (!file_exists($path)) {
      throw new RuntimeException("File not found: $path");
  }

  if (($fh = fopen($path, 'r')) === false) {
      throw new RuntimeException("Unable to open file: $path");
  }

  // If the CSV has a header row:
  $headers = fgetcsv($fh, 0, ',', '"', '\\'); // first line => headers
  if ($headers === false) {
      fclose($fh);
      throw new RuntimeException("Empty file or invalid CSV");
  }

  // optional: clean BOM from first header (UTF-8 BOM)
  $headers[0] = preg_replace('/^\x{FEFF}/u', '', $headers[0]);

  while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
      // skip empty lines
      if ($row === [null] || count(array_filter($row, fn($c) => $c !== null && $c !== '')) === 0) {
          continue;
      }

      // if number of fields matches, map header=>value
      if (count($row) === count($headers)) {
          $assoc = array_combine($headers, $row);
      } else {
          // handle row/column mismatch (you can pad/truncate or skip)
          $assoc = [];
          foreach ($row as $i => $val) {
              $assoc[$headers[$i] ?? "col_$i"] = $val;
          }
      }

      // process the row
      // e.g. normalize and cast fields
      $assoc['id'] = isset($assoc['id']) ? (int)$assoc['id'] : null;
      $assoc['amount'] = isset($assoc['amount']) ? (float)str_replace(',', '', $assoc['amount']) : null;

      // call your processing function

      $n++;

      process_row($assoc, $n);
  }

  fclose($fh);

}

function process_row( array $row, $n ): void {

  $url = henc( $row[ 'URL' ] );
  $project = henc( $row[ 'Project Name' ] );

  echo "<tr>\n";
  echo "<td>$n</td>\n";
  echo "<td><a href=\"$url\">$project</a></td>\n";
  echo "</tr>\n";

}

function henc( $string ) { return htmlspecialchars( $string ); }

function get_csv(string $dir): ?string {
    if (!is_dir($dir)) return null;
    // GLOB_BRACE matches both .csv and .CSV
    $files = glob(rtrim($dir, DIRECTORY_SEPARATOR) . '/*.{csv,CSV}', GLOB_BRACE);
    if (!$files) return null;
    // $files is sorted by filename; return the first match
    return $files[0];
}

function render_head() {
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head>
  <title>One Hertz Challenge</title>
  <meta charset="utf-8">
  <meta name="date" content="Wed, 27 Aug 2025 22:39:28 +1000">
  <meta name="author" content="John Elliot V et al.">
  <!--
  <meta name="referrer" content="no-referrer">
  <meta name="robots" content="noindex, nofollow">
  <meta name="keywords" content="">
  <meta name="description" content="">
  -->
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">

  <!-- 2022-10-13 jj5 - SEE: https://en.wikipedia.org/wiki/Polyglot_markup -->

  <link href="/favicon.ico" rel="icon">

  <link
    rel="stylesheet"
    type="text/css"
    href="https://www.staticmagic.net/global/default.css?v=2025-08-27-223928">

  <script src="https://www.staticmagic.net/global/default.js?v=2025-08-27-223928"></script>

<style>

html {
  min-height: 101%;
}

</style>

<script>

"use strict";

window.addEventListener( 'load', handle_load );

function handle_load() {
  //console.log( 'hi' );
};

</script>

</head>
<body>
  <main>
    <h1>One Hertz Challenge</h1>
    <p>Here are the list of submissions for the One Hertz Challenge:</p>

  </main>
<?php
}

function render_foot() {
?>
</body>
</html>

<?php
}

