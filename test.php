<?php

$result = explode(';', 'MFGFCY=123123;sndfjsn=23234');

$values = [];

foreach ($result as $value) {
    $item = explode('=', $value);
    $values[$item[0]] = $item[1];
}

echo '<pre>';
var_dump($values);
echo '</pre>';