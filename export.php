<?php declare(strict_types=1);

require_once __DIR__ . '/common.php';

main();

function main() {

  $rows = get_json_data();

  $filename = '2025_One_Hertz_Challenge_Scoring-John_Elliot-' . date('Y-m-d_Hi') . '.csv';

  if ( false ) {
    header('Content-Type: text/plain; charset=UTF-8');
  }
  else {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
  }

  header('Pragma: no-cache');
  header('Expires: 0');

  // 2025-08-30 jj5 - add UTF-8 BOM so Excel recognises UTF-8
  echo "\xEF\xBB\xBF";

  $handle = fopen('php://output', 'w');

  // 2025-08-30 jj5 - these are the header row
  //
  $columns = [
    //APP_FIELD_ID,
    APP_FIELD_PROJECT_NAME,
    APP_FIELD_URL,
    APP_FIELD_RANK,
    APP_FIELD_NOTES,
    APP_FIELD_TIMELORDS,
    APP_FIELD_RIDICULOUS,
    APP_FIELD_CLOCKWORK,
    APP_FIELD_COULD_HAVE_USED_A_555
  ];

  fputcsv( $handle, $columns );

  usort( $rows, fn( $a, $b ) => $a[ APP_INPUT_ID ] <=> $b[ APP_INPUT_ID ] );

  foreach ( $rows as $row ) {

    $line = [
      //$row[APP_INPUT_ID],
      $row[ APP_INPUT_PROJECT_NAME ],
      $row[ APP_INPUT_URL ],
      $row[ APP_INPUT_RANK ] <= 10 ? $row[ APP_INPUT_RANK ] : '',
      get_notes( $row ),
      $row[ APP_INPUT_TIMELORDS ] ? 'X' : '',
      $row[ APP_INPUT_RIDICULOUS ] ? 'X' : '',
      $row[ APP_INPUT_CLOCKWORK ] ? 'X' : '',
      $row[ APP_INPUT_COULD_HAVE_USED_A_555 ] ? 'X' : ''
    ];

    fputcsv( $handle, $line );

  }

  fclose( $handle );

}

function get_notes( array $row ): string {

  $result = [];

  if ( $row[ APP_INPUT_HAS_SCHEMATICS ] ) {
    $result[] = 'has schematics';
  }

  if ( $row[ APP_INPUT_HAS_CODE ] ) {
    $result[] = 'has code';
  }

  if ( $row[ APP_INPUT_NOTES ] ) {
    $result[] = $row[ APP_INPUT_NOTES ];
  }

  return implode( '; ', $result );

}
