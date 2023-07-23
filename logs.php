<?php

require('functions.php');

const TMP_FOLDER    = 'tmp/';
$logFiles = scandir(TMP_FOLDER, SCANDIR_SORT_DESCENDING);

$logFiles = array_filter($logFiles, function ($logFile) {
	$length = strlen($logFile);
	return substr($logFile, $length - 4, $length) === '.txt';
});

if (! count($logFiles)) die("There's no log files");

foreach ($logFiles as $logFile) {
	echo "<a href='/logs.php?log={$logFile}'>{$logFile}</a><br>";
}

$logFileName = TMP_FOLDER . ($_GET['log'] ?? $logFiles[count($logFiles) - 1]);

$logFile = file_get_contents($logFileName);
$logs = explode('[] []', $logFile);

$logs = array_map(function ($log) {
	$start = strpos($log, '<?xml');

	$log = rtrim($log);
	
	return substr($log, $start, strlen($log));
}, $logs);

array_pop($logs);

foreach ($logs as $log) {
	$xml = simplexml_load_string($log);
	$statusValue = (int) $xml->xpath('//wss:runResponse/runReturn/status')[0];
	$resultXml = (string) $xml->xpath('//wss:runResponse/runReturn/resultXml')[0];
	$multiRefs = $xml->xpath('//multiRef');

	echo "Status Value: $statusValue\n<br>";

	echo "Messages:\n<br>";
	foreach ($multiRefs as $multiRef) {
		$id = (string) $multiRef['id'];
		$type = (int) $multiRef->type;
		$message = (string) $multiRef->message;

		echo "*ID: $id, Type: $type, Message: $message\n<br>";
	}

	if ($statusValue) {
		echo '<pre>';
		var_dump(json_decode($resultXml, JSON_UNESCAPED_SLASHES));
		echo '</pre>';
	}

	echo "<br>";
	echo "<br>";
}