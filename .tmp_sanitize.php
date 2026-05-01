<?php
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/app/Http/Controllers'));
$changed=[];
foreach ($it as $f) {
  if (!$f->isFile() || strtolower($f->getExtension()) !== 'php') continue;
  $p = $f->getPathname();
  $c = file_get_contents($p);
  $o = $c;

  $patterns = [
    "/'message'\s*=>\s*\$e->getMessage\(\)/" => "'message' => 'Internal server error'",
    '/"message"\s*=>\s*\$e->getMessage\(\)/' => '"message" => "Internal server error"',
    "/'message'\s*=>\s*'[^']*'\s*\.\s*\$e->getMessage\(\)/" => "'message' => 'Internal server error'",
    '/"message"\s*=>\s*"[^"]*"\s*\.\s*\$e->getMessage\(\)/' => '"message" => "Internal server error"',
    "/\$response\[['\"]error['\"]\]\s*=\s*\$e->getMessage\(\)\s*;/" => "\$response['error'] = 'Internal server error';",
    "/'error'\s*=>\s*\$e->getMessage\(\)/" => "'error' => 'Internal server error'",
    '/"error"\s*=>\s*\$e->getMessage\(\)/' => '"error" => "Internal server error"',
    "/'debug'\s*=>\s*\$e->getMessage\(\)/" => "'debug' => 'Internal server error'",
    "/'error'\s*=>\s*\$e\b/" => "'error' => 'Internal server error'",
  ];

  foreach ($patterns as $rx=>$rp) $c = preg_replace($rx, $rp, $c);

  if ($c !== $o) {
    file_put_contents($p, $c);
    $changed[] = $p;
  }
}
foreach ($changed as $p) echo $p, PHP_EOL;
echo 'TOTAL_CHANGED=' . count($changed) . PHP_EOL;
?>
