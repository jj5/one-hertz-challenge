<?php declare(strict_types=1);

require_once __DIR__ . '/common.php';

main();

function main() {

  foreach ( [ APP_PATH_ETC, APP_PATH_DAT ] as $dir ) {

    if ( is_dir( $dir ) ) { continue; }

    mkdir( $dir, 0755, true ) or die( "Failed to create directory: $dir\n" );

  }

  $csv_file = get_csv( APP_PATH_DAT );

  if ( ! file_exists( $csv_file ) ) {

    die( "Please copy your CSV into the 'dat' subdirectory.\n" );

  }

  $http_verb = $_SERVER[ 'REQUEST_METHOD' ];

  if ( $http_verb === 'POST' ) {

    var_dump( $_POST );

    exit;

  }

  $data = get_json_data();

  //var_dump( $data ); exit;

  set_json_data( $data );

  render_head();

  render_json_data( $data );

  render_foot();

}

function render_json_data( array $data ): void {

  if ( count( $data ) === 0 ) {

    echo "<p>No data.</p>\n";

    return;

  }

  $first_row = $data[ 0 ];

  echo "<form method=\"post\" action=\"\">\n";

    echo "<table id='table' class='nice-table sortable'>\n";

      echo "<thead>\n";

        echo '<tr>';
          echo "<th><input type=\"submit\" value=\"Save Changes\" /></th>\n";
          echo "<th>ID</th>";
          echo "<th>Project</th>";
          echo '<th>' . APP_FIELD_RANK . '</th>';
          echo '<th>' . APP_FIELD_TIMELORDS . '</th>';
          echo '<th>' . APP_FIELD_RIDICULOUS . '</th>';
          echo '<th>' . APP_FIELD_CLOCKWORK . '</th>';
          echo '<th>' . APP_FIELD_COULD_HAVE_USED_A_555 . '</th>';
          echo '<th>' . APP_FIELD_NOTES . '</th>';

        echo '</tr>';

      echo "</thead>\n";

      echo "<tbody>\n";

        foreach ( $data as $row ) {

          render_json_data_row( $row );

        }

      echo "</tbody>\n";

    echo "</table>\n";

  echo "</form>\n";

}

function render_json_data_row( array $row ): void {

  $idx = intval( $row[ APP_FIELD_ID ] - 1 );

  $id = henc( $row[ APP_FIELD_ID ] );
  $url = henc( $row[ APP_FIELD_URL ] );
  $project = henc( $row[ APP_FIELD_PROJECT_NAME ] );
  $rank = henc( $row[ APP_FIELD_RANK ] );
  $notes = henc( $row[ APP_FIELD_NOTES ] );
  $timelords = henc( $row[ APP_FIELD_TIMELORDS ] );
  $ridiculous = henc( $row[ APP_FIELD_RIDICULOUS ] );
  $clockwork = henc( $row[ APP_FIELD_CLOCKWORK ] );
  $could_have_used_a_555 = henc( $row[ APP_FIELD_COULD_HAVE_USED_A_555 ] );

  echo '<tr>';
    echo "<td>$id";
      render_input_hidden( $idx, APP_FIELD_ID, $id );
      render_input_hidden( $idx, APP_FIELD_URL, $url );
      render_input_hidden( $idx, APP_FIELD_PROJECT_NAME, $project );
    echo "</td>";
    echo "<td><a href=\"$url\" target=\"hackaday\">$project</a></td>";
    echo "<td>";
      render_input_text_readonly( $idx, 'rank', $rank );
    echo "</td>";
    echo "<td class='center'>";
      render_radio_input( $idx, 'timelords', $timelords, APP_FIELD_TIMELORDS );
    echo "</td>";
    echo "<td class='center'>";
      render_radio_input( $idx, 'ridiculous', $ridiculous, APP_FIELD_RIDICULOUS );
    echo "</td>";
    echo "<td class='center'>";
      render_radio_input( $idx, 'clockwork', $clockwork, APP_FIELD_CLOCKWORK );
    echo "</td>";
    echo "<td class='center'>";
      render_radio_input( $idx, 'could_have_used_a_555', $could_have_used_a_555, APP_FIELD_COULD_HAVE_USED_A_555 );
    echo "</td>";
    echo "<td>";
      render_input_text( $idx, 'notes', $notes );
    echo "</td>";
  echo '</tr>';

}

function render_radio_input( int $idx, string $name, string $setting, $text = '' ): void {

  $html = henc( $text );

  $checked = ( $setting === 'X' ) ? true : false;

  $yes_checked = ( $setting === 'X' ) ? true : false;
  $no_checked = ! $yes_checked;

  $yes_attr = $yes_checked ? 'checked' : '';
  $no_attr = $no_checked ? 'checked' : '';

  echo '<label>';
  echo "<input type=\"radio\" name=\"{$name}[$idx]\" value=\"yes\" $yes_attr /> Yes";
  echo '</label>';

  echo '<label>';
  echo "<input type=\"radio\" name=\"{$name}[$idx]\" value=\"no\" $no_attr /> No";
  echo '</label>';

}

function render_checkbox_input( int $idx, string $name, string $setting, $text = '' ): void {

  $html = henc( $text );

  $checked = ( $setting === 'X' ) ? true : false;

  echo '<label>';
  echo "<input type=\"checkbox\" name=\"{$name}[$idx]\" value=\"on\" " . ($checked ? 'checked' : '') . " /> $html";
  echo '</label>';

}

function render_input_hidden( int $idx, string $name, string $value ): void {

  echo "<input type=\"hidden\" name=\"{$name}[$idx]\" value=\"" . henc( $value ) . "\" />\n";

}

function render_input_text( int $idx, string $name, string $value ): void {

  echo "<input type=\"text\" name=\"{$name}[$idx]\" value=\"" . henc( $value ) . "\" />\n";

}

function render_input_text_readonly( int $idx, string $name, string $value ): void {

  echo "<input type=\"text\" name=\"{$name}[$idx]\" value=\"" . henc( $value ) . "\" readonly />\n";

}

function render_textarea( int $idx, string $name, string $value ): void {

  echo "<textarea name=\"{$name}[$idx]\" rows=\"4\" cols=\"50\">" . henc( $value ) . "</textarea>\n";

}

function process_csv( $csv_file, &$n ) {

  $path = $csv_file;

  if (!file_exists($path)) {
      app_fail("File not found: $path");
  }

  if (($fh = fopen($path, 'r')) === false) {
      app_fail("Unable to open file: $path");
  }

  $headers = fgetcsv($fh, 0, ',', '"', '\\'); // first line => headers
  if ($headers === false) {
    app_fail("Empty file or invalid CSV: $path", $fh  );
  }

  while (($row = fgetcsv($fh, 0, ',', '"', '\\')) !== false) {
    $assoc = array_combine($headers, $row);
    $n++;
    process_row($assoc, $n);
  }

  fclose($fh);

}

function app_fail( string $message, $handle = null ): void {

  if ( $handle ) { fclose( $handle ); }

  die( $message . "\n" );

}

function process_row( array $row, $n ): void {

  $url = henc( $row[ 'URL' ] );
  $project = henc( $row[ 'Project Name' ] );

  echo "<tr>\n";
  echo "<td class='right'>$n</td>\n";
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
  max-width: unset;
}
thead th {
  text-align: center;
}

body { font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; padding: 24px; }
table { border-collapse: collapse; width: 100%; margin-top: 12px; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th.actions, td.actions { width: 110px; text-align: center; }
.move-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .35rem;
  min-width: 34px;
  height: 28px;
  padding: 0 6px;
  border-radius: 6px;
  border: 1px solid #bbb;
  background: #f8f8f8;
  cursor: pointer;
  font-size: 13px;
  line-height: 1;
}
.move-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.controls { display:flex; gap:6px; align-items:center; justify-content:center; }
caption { caption-side: top; font-weight: 600; margin-bottom: 6px; }

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
    <p>Reload this page <a href="">here</a>.</p>

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
<script src="res/table.js"></script>
</body>
</html>

<?php
}

