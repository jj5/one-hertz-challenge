<?php

define( 'APP_PATH_BASE', realpath( __DIR__ . '/' ) );
define( 'APP_PATH_ETC', APP_PATH_BASE . '/etc' );
define( 'APP_PATH_DAT', APP_PATH_BASE . '/dat' );
define( 'APP_STATE_PATH', APP_PATH_ETC . '/state.json' );
define( 'DATA_FILE', __DIR__ . '/etc/state.json' );

define( 'APP_FIELD_ID', 'ID' );
define( 'APP_FIELD_URL', 'URL' );
define( 'APP_FIELD_PROJECT_NAME', 'Project Name' );
define( 'APP_FIELD_RANK', 'Rank top 10 (1 is best)' );
define( 'APP_FIELD_NOTES', 'Notes' );
define( 'APP_FIELD_TIMELORDS', 'Timelords' );
define( 'APP_FIELD_RIDICULOUS', 'Ridiculous' );
define( 'APP_FIELD_CLOCKWORK', 'Clockwork' );
define( 'APP_FIELD_COULD_HAVE_USED_A_555', 'Could Have Used a 555' );

define( 'APP_INPUT_ID', 'id' );
define( 'APP_INPUT_URL', 'url' );
define( 'APP_INPUT_PROJECT_NAME', 'project_name' );
define( 'APP_INPUT_RANK', 'rank' );
define( 'APP_INPUT_TIMELORDS', 'timelords' );
define( 'APP_INPUT_RIDICULOUS', 'ridiculous' );
define( 'APP_INPUT_CLOCKWORK', 'clockwork' );
define( 'APP_INPUT_COULD_HAVE_USED_A_555', 'could_have_used_a_555' );
define( 'APP_INPUT_NOTES', 'notes' );

$input_map = [
  APP_INPUT_ID => APP_FIELD_ID,
  APP_INPUT_URL => APP_FIELD_URL,
  APP_INPUT_PROJECT_NAME => APP_FIELD_PROJECT_NAME,
  APP_INPUT_RANK => APP_FIELD_RANK,
  APP_INPUT_TIMELORDS => APP_FIELD_TIMELORDS,
  APP_INPUT_RIDICULOUS => APP_FIELD_RIDICULOUS,
  APP_INPUT_CLOCKWORK => APP_FIELD_CLOCKWORK,
  APP_INPUT_COULD_HAVE_USED_A_555 => APP_FIELD_COULD_HAVE_USED_A_555,
  APP_INPUT_NOTES => APP_FIELD_NOTES,
];


function get_json_data() {

  if ( file_exists( APP_STATE_PATH ) ) {

    $json = file_get_contents( APP_STATE_PATH );

    return json_decode( $json, true ) ?? [];

  }

  return get_csv_data();

}

function set_json_data( array $data ): void {

  $json = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

  file_put_contents( APP_STATE_PATH, $json );

}

function get_csv_data() {

  $path = get_csv( APP_PATH_DAT );

  if ( ! file_exists( $path ) ) { app_fail( "File not found: $path" ); }

  if ( ( $handle = fopen( $path, 'r' ) ) === false ) { app_fail( "Unable to open file: $path" ); }

  // 2025-08-30 jj5 - NOTE: first line contains headers...
  //

  $headers = fgetcsv( $handle, 0, ',', '"', '\\' );

  if ( $headers === false ) { app_fail( "Empty file or invalid CSV: $path", $handle ); }

  $result = [];
  $id = 0;

  while (($row = fgetcsv($handle, 0, ',', '"', '\\')) !== false) {
    $assoc = array_combine($headers, $row);
    $id++;

    $row = [
      APP_INPUT_ID => $id,
      APP_INPUT_URL => $assoc[ APP_FIELD_URL ],
      APP_INPUT_PROJECT_NAME => $assoc[ APP_FIELD_PROJECT_NAME ],
      APP_INPUT_RANK => intval( $assoc[ APP_FIELD_RANK ] ?? 0 ),
      APP_INPUT_NOTES => $assoc[ APP_FIELD_NOTES ] ?? '',
      APP_INPUT_TIMELORDS => $assoc[ APP_FIELD_TIMELORDS ] ?? '',
      APP_INPUT_RIDICULOUS => $assoc[ APP_FIELD_RIDICULOUS ] ?? '',
      APP_INPUT_CLOCKWORK => $assoc[ APP_FIELD_CLOCKWORK ] ?? '',
      APP_INPUT_COULD_HAVE_USED_A_555 => $assoc[ APP_FIELD_COULD_HAVE_USED_A_555 ] ?? '',
    ];

    $result[] = $row;

  }

  fclose( $handle );

  return $result;

}
