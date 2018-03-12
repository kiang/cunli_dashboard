<?php
$path = dirname(__DIR__);

$fh = fopen(__DIR__ . '/villages.csv', 'r');
fgetcsv($fh, 2048);
$ref = array();
while($line = fgetcsv($fh, 2048)) {
  $parts = explode('-', $line[1]);
  $ref[$line[1]] = $line[4] . $line[3] . $line[2];
}

/*
[households] => 475
[population] => 2403
[male] => 1331
[female] => 1072
[under15] => 329
[be1564] => 1865
[up65] => 209
[up20] => 1867
[be1819] => 78
*/
$result = array(
  'households' => array(),
  'population' => array(),
  'under15' => array(),
  'be1564' => array(),
  'up65' => array(),
);
$households = $populations = $under15 = $be1564 = $up65 = array();

foreach(glob($path . '/data/*.json') AS $jsonFile) {
  $json = json_decode(file_get_contents($jsonFile), true);
  if(isset($json['population']['2017-12']) && isset($json['population']['2015-01'])) {
    $listLine = array($ref[$json['code']], 'https://kiang.github.io/cunli_dashboard/#/cunli/' . $json['code']);
    foreach($result AS $k => $v) {
      $target = $json['population']['2017-12'][$k] - $json['population']['2015-01'][$k];
      if(!isset($result[$k][$target])) {
        $result[$k][$target] = array();
      }
      $result[$k][$target][] = $listLine;
    }
  }
}

foreach($result AS $k => $v) {
  $fh = fopen(__DIR__ . '/reports/' . $k . '.csv', 'w');
  ksort($v);
  foreach($v AS $value => $lines) {
    foreach($lines AS $line) {
      fputcsv($fh, array_merge(array($value), $line));
    }
  }
}
