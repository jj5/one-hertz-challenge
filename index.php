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

  while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
      // skip empty lines
      if ($row === [null] || count(array_filter($row, fn($c) => $c !== null && $c !== '')) === 0) {
          continue;
      }
      $assoc = array_combine($headers, $row);
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
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
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
    <p>This is for judging the <a href="https://hackaday.io/contest/203248-one-hertz-challenge">One Hertz Challenge</a>.</p>
    <h2>Honorable Mention Categories</h2>
    <p><b>Timelords:</b> How precisely can you get that heartbeat? This category is for those who prefer to see a lot of zeroes after the decimal point.</p>
    <p><b>Ridiculous:</b> This category is for the least likely thing to do once per second. Accuracy is great, but absurdity is king here. Have Rube Goldberg dreams? Now you get to live them out.</p>
    <p><b>Clockwork:</b> It’s hard to mention time without thinking of timepieces. This category is for the clockmakers among you. If your clock ticks at a rate of one hertz, and you’re willing to show us the mechanism, you’re in.</p>
    <p><b>Could Have Used a 555:</b> We knew you were going to say it anyway, so we made it an honorable mention category. If your One Hertz project gets its timing from the venerable triple-five, it belongs here.</p>

    <h2>Submissions</h2>
    <p>Here are the list of submissions for the One Hertz Challenge:</p>

<?php
}

function render_foot() {
?>
  </main>
</body>
</html>

<?php
}

