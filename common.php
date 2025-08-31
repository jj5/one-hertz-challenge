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
define( 'APP_FIELD_VIEWS', 'Views' );
define( 'APP_FIELD_COMMENTS', 'Comments' );
define( 'APP_FIELD_FOLLOWERS', 'Followers' );
define( 'APP_FIELD_LIKES', 'Likes' );
define( 'APP_FIELD_HAS_SCHEMATICS', 'Has Schematics?' );

define( 'APP_INPUT_ID', 'id' );
define( 'APP_INPUT_URL', 'url' );
define( 'APP_INPUT_PROJECT_NAME', 'project_name' );
define( 'APP_INPUT_RANK', 'rank' );
define( 'APP_INPUT_TIMELORDS', 'timelords' );
define( 'APP_INPUT_RIDICULOUS', 'ridiculous' );
define( 'APP_INPUT_CLOCKWORK', 'clockwork' );
define( 'APP_INPUT_COULD_HAVE_USED_A_555', 'could_have_used_a_555' );
define( 'APP_INPUT_NOTES', 'notes' );
define( 'APP_INPUT_VIEWS', 'views' );
define( 'APP_INPUT_COMMENTS', 'comments' );
define( 'APP_INPUT_FOLLOWERS', 'followers' );
define( 'APP_INPUT_LIKES', 'likes' );
define( 'APP_INPUT_HAS_SCHEMATICS', 'has_schematics' );
define( 'APP_INPUT_HAS_CODE', 'has_code' );

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

/** 2025-08-31 jj5 - SEE: https://chatgpt.com/share/68b3d54b-98c4-8006-b1bc-f21d139c4737
 *
 * Parse shorthand numbers like "3.7k", "1.2M", "2b" into an integer.
 *
 * @param string $s           Input string (e.g. "3.7k", "1,234", "5M")
 * @param bool   $binary      If true, use binary multipliers (k=1024). Default false (k=1000).
 * @param bool   $returnFloat If true return float instead of int. Default false.
 * @return int|float|null     Parsed number, or null if can't parse.
 */
function parse_shorthand_number($s, $binary = false, $returnFloat = false) {
    if (!is_string($s)) return '';
    $s = trim($s);
    if ($s === '') return '';

    // normalize: remove commas/spaces, lowercase
    $clean = strtolower(str_replace([',', ' '], ['', ''], $s));

    // match optional sign, integer or decimal, optional suffix (k,m,g,t,b)
    if (!preg_match('/^([+-]?\d+(\.\d+)?)([kmg t b])?$/i', $clean)) {
        // attempt with only letters stripped at end (e.g. "3.7k views")
        if (preg_match('/^([+-]?\d+(\.\d+)?)([a-z]+).*$/i', $clean, $m2)) {
            $clean = $m2[1] . $m2[3]; // fall through to parse
        } else {
            // final fallback: if it's plain numeric
            if (is_numeric($clean)) {
                return $returnFloat ? (float)$clean : (int) round((float)$clean);
            }
            return null;
        }
    }

    // extract again in a robust way
    if (preg_match('/^([+-]?\d+(\.\d+)?)([kmgtb])?$/i', $clean, $m)) {
        $num = (float)$m[1];
        $suffix = isset($m[3]) ? strtolower($m[3]) : '';

        $mapDecimal = [
            'k' => 1_000,
            'm' => 1_000_000,
            'g' => 1_000_000_000,
            'b' => 1_000_000_000, // "b" as billion
            't' => 1_000_000_000_000,
        ];
        $mapBinary = [
            'k' => 1024,
            'm' => 1024 * 1024,
            'g' => 1024 * 1024 * 1024,
            'b' => 1024 * 1024 * 1024, // treat b ~ G in binary mode
            't' => 1024 * 1024 * 1024 * 1024,
        ];

        $mult = 1;
        if ($suffix !== '') {
            $mult = $binary ? ($mapBinary[$suffix] ?? 1) : ($mapDecimal[$suffix] ?? 1);
        }

        $value = $num * $mult;
        return $returnFloat ? $value : (int) round($value);
    }

    return null;
}

function get_hackaday_io_stats( $url ) {

  $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0';

  // Build stream context with custom header
  $opts = [
      'http' => [
          'method'  => "GET",
          'header'  => "User-Agent: $userAgent\r\n" .
                      "Accept: text/html,application/xhtml+xml,application/xml;q=0.9\r\n",
          'timeout' => 10, // seconds
          'ignore_errors' => true // fetch content even on 4xx/5xx to read response body
      ],
      'ssl' => [
          'verify_peer' => true,
          'verify_peer_name' => true
      ]
  ];

  $context = stream_context_create($opts);

  $html = @file_get_contents($url, false, $context);

  if ($html === false) {
    // get last warning (if any)
    $err = error_get_last();
    die( "Error fetching page: " . ($err['message'] ?? 'unknown') . PHP_EOL );
  }

  preg_match( '|<span title="View Count">(.*)</span>|', $html, $matches );

  $views = $matches[1] ?? '';

  preg_match( '|<span title="Comments">(.*)</span>|', $html, $matches );

  $comments = $matches[1] ?? '';

  preg_match( '|<span title="Followers">(.*)</span>|', $html, $matches );

  $followers = $matches[1] ?? '';

  preg_match( '|<span title="Likes">(.*)</span>|', $html, $matches );

  $likes = $matches[1] ?? '';

  return [
      'views' => parse_shorthand_number( $views ),
      'comments' => parse_shorthand_number( $comments ),
      'followers' => parse_shorthand_number( $followers ),
      'likes' => parse_shorthand_number( $likes ),
  ];

}

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
