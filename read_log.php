<?php
$file = 'storage/logs/laravel.log';
$lines = 50;
$handle = fopen($file, "r");
$linecounter = $lines;
$pos = -2;
$beginning = false;
$text = [];
while ($linecounter > 0) {
    if (fseek($handle, $pos, SEEK_END) == -1) {
        $beginning = true; 
        break; 
    }
    $t = fgetc($handle);
    if ($t == "\n") {
        $linecounter--;
    }
    $pos--;
}
if ($beginning) {
    rewind($handle);
}
echo stream_get_contents($handle);
fclose($handle);
