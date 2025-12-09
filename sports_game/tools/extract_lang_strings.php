<?php
$root = dirname(__DIR__);
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$strings = [];
foreach ($rii as $file) {
    if ($file->isDir()) {
        continue;
    }
    $path = $file->getPathname();
    if (!preg_match('/\.(php|html)$/i', $path)) {
        continue;
    }
    $content = file_get_contents($path);
    if ($content === false) {
        continue;
    }
    preg_match_all('/[\x{4e00}-\x{9fff}][\x{4e00}-\x{9fff}0-9A-Za-z\s·（）(),，。！？、:：\/\-\.]+/u', $content, $matches);
    foreach ($matches[0] as $match) {
        $text = trim($match);
        $text = preg_replace('/[^\x{4e00}-\x{9fff}0-9A-Za-z·（）(),，。！？、:：\/\-\.\s]+$/u', '', $text);
        if ($text === '' || !preg_match('/[\x{4e00}-\x{9fff}]/u', $text)) {
            continue;
        }
        $strings[$text] = true;
    }
}

ksort($strings, SORT_NATURAL);
file_put_contents(__DIR__ . '/lang_strings.txt', implode(PHP_EOL, array_keys($strings)));
echo "Extracted " . count($strings) . " strings to tools/lang_strings.txt" . PHP_EOL;
