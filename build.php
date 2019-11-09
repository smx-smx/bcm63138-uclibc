<?php
/**
 * Copyright(C) 2019 Stefano Moioli <smxdev4@gmail.com>
 */

define('CROSS', 'arm-buildroot-linux-uclibcgnueabi-');

function getSymbols($lib){
	$h = popen(CROSS.'readelf -D -s ' . escapeshellarg($lib), 'r') or die('readelf failure' . PHP_EOL);
	fgets($h);fgets($h);fgets($h);

	$syms = array();
	while(!feof($h)){
		$line = fgets($h);
		$symbol = trim(strrchr($line, ' '));
		$syms[] = $symbol;
	}
	pclose($h);

	return $syms;
}

function notInternalSymbol($sym){
	return strpos($sym, '__') !== 0;
}

function getLibsSymbols($libs){
	$symbols = array_map(function($lib){
		return getSymbols($lib);
	}, $libs);

	if(count($symbols) < 1)
		return $symbols;

	$symbols = array_merge(...$symbols);
	return array_filter(array_unique($symbols, SORT_STRING), 'notInternalSymbol');
}

switch(@$argv[1]){
	default:
		$cross = CROSS;
		$pwd = getcwd();
		$nproc = rtrim(shell_exec('nproc'));

		$cmd = <<<EOC
		ARCH=arm
		CROSS_COMPILE=$cross
		UCLIBC_EXTRA_CFLAGS="-marm"
		make headers && make -j$nproc && DESTDIR=$pwd/out make install
		EOC;
		passthru($cmd);
		break;
	case 'clean':
		passthru("make clean");
		break;
	case 'install':
		passthru('DESTDIR=$PWD/out make install');
		break;
	case 'check':
		//// Get files names
		$libFilesB = glob('out/lib/*.so');
		$libNamesB = array_map('basename', $libFilesB);

		$libFilesA = glob($argv[2] . '/*.so');
		$libNamesA = array_map('basename', $libFilesA);

		//// Create associative arrays
		$libsB = array_combine($libNamesB, $libFilesB);
		$libsA = array_combine($libNamesA, $libFilesA);

		//// Remove libs of A not in B, from A
		$extraLibs = array_diff_key($libsA, $libsB);
		$libsA = array_diff($libsA, $extraLibs);

		//// We don't need names anymore
		$libsA = array_values($libsA);
		$libsB = array_values($libsB);

		//// Get symbols
		$symsB = getLibsSymbols($libsB);
		$symsA = getLibsSymbols($libsA);


		$common = array_intersect($symsA, $symsB);
		$toRemove = array_diff($symsB, $common);
		$toAdd = array_diff($symsA, $common);

		sort($toAdd, SORT_STRING);
		sort($toRemove, SORT_STRING);

		print("== Extra Symbols ==" . PHP_EOL);
		print(implode(PHP_EOL, $toRemove)) . PHP_EOL . PHP_EOL;

		print("== Missing Symbols ==" . PHP_EOL);
		print(implode(PHP_EOL, $toAdd)) . PHP_EOL . PHP_EOL;
		break;
}
