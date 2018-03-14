<?php
$path = dirname(__DIR__);

$fh = fopen(__DIR__ . '/villages.csv', 'r');
fgetcsv($fh, 2048);
$ref = array();
while($line = fgetcsv($fh, 2048)) {
  $parts = explode('-', $line[1]);
  $key = $line[4] . $line[3];
  if(!isset($ref[$parts[0]])) {
    $ref[$key] = $parts[0];
  }
}
fclose($fh);

$fh = fopen('/home/kiang/public_html/salary/data/cunli_code.csv', 'r');
fgetcsv($fh, 2048);
$codeMap = array();
$paris = array(
  '台' => '臺',
);
while($line = fgetcsv($fh, 2048)) {
  $line[1] = strtr($line[1], $paris);
  switch($line[3]) {
    case '員林市':
    $line[3] = '員林鎮';
    break;
  }
  $key = $line[1] . $line[3];
  if(!isset($codeMap[$line[2]])) {
    $codeMap[$line[2]] = $ref[$key];
  }
}

$path = dirname(__DIR__);
$fia = json_decode(file_get_contents('/home/kiang/public_html/salary/map/fia_data.json'), true);
foreach($fia AS $code => $data) {
  $parts = explode('-', $code);
  $targetCode = $codeMap[$parts[0]] . '-' . $parts[1];
  $targetFile = $path . '/data/' . $targetCode . '.json';
  $base = json_decode(file_get_contents($targetFile), true);
  $base['income'] = $data;
  file_put_contents($targetFile, json_encode($base));
}
