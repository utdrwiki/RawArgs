<?php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = [
	...$cfg['directory_list'],
	'../../extensions/Scribunto',
];

$cfg['exclude_analysis_directory_list'] = [
	...$cfg['exclude_analysis_directory_list'],
	'../../extensions/Scribunto',
];

return $cfg;
