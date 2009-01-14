<?php

require_once('../../core/libs/Smarty.class.php');
function isReadWriteDir($file) {return is_readable($file) && is_writable($file) && is_dir ($file);}

define('SITE_ROOT', (dirname(__FILE__) . '/../../'));
define ('DB_CONFIG', SITE_ROOT . '/include/db-config.php');

$s = new Smarty();
$s->template_dir = dirname(__FILE__) . '/templates';
$s->compile_dir = SITE_ROOT . '/cache/templates';

$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 0;

$steps = array('Welcome', 'Checking permissions', 'Database credentials', 'Create database', 'Install Schemas', 'Done');
$s->assign('curstep', $step);
	
									 
switch ($step) {
	case 0:
		$checks = array(
			'PHP 5.2 or better installed' => version_compare(PHP_VERSION, '5.2', '>'),
			'MySQL 5.0 or better installed' => version_compare(mysqli_get_client_info(), '5.0', '>'),
		);
		$s->assign('checks', $checks);
		$content = $s->fetch('step0.htpl');
		break;
	case 1:
		$checks = array(
			'templates directory is readable' => is_readable(SITE_ROOT . '/templates') && is_dir(SITE_ROOT . '/templates'),
			'cache/templates directory is a read/write directory' => isReadWriteDir(SITE_ROOT . '/cache/templates'),
			'js/cache soft links to cache/js' => is_link(SITE_ROOT . '/js/cache') && readlink(SITE_ROOT . '/js/cache') === '../cache/js',
			'cache/js directory is a read/write directory' => isReadWriteDir(SITE_ROOT . '/cache/js'),
			'cache/images directory is a read/write directory' => isReadWriteDir(SITE_ROOT . '/cache/images'),
			'cache/pages directory is a read/write directory' => isReadWriteDir(SITE_ROOT . '/cache/pages')
		);
		$s->assign('checks', $checks);
		$content = $s->fetch('step1.htpl');
		break;
	case 2:
		if (is_readable(DB_CONFIG)) {
			include_once (DB_CONFIG);
		} else {
			$dbhost = "localhost";
			$dbuser = "";
			$dbpass = "";
			$dbase = "";
		}

		$fields = array(
			'Database Host' => "<input type='text' name='db_host' value='$dbhost' />",
			'Database Name' => "<input type='text' name='db_name' value='$dbase' />",
			'Database Username' => "<input type='text' name='db_user' value='$dbuser' />",
			'Database Password' => "<input type='text' name='db_pass' value='' />",
		);
		$s->assign('fields', $fields);
		$content = $s->fetch('step2.htpl');
		break;
	case 3:
	case 4:
		if (is_readable(DB_CONFIG)) {include_once (DB_CONFIG);}
		if ($step == 3) {
			if ($_REQUEST['db_host']) $dbhost = $_REQUEST['db_host'];
			if ($_REQUEST['db_user']) $dbuser = $_REQUEST['db_user'];
			if ($_REQUEST['db_name']) $dbase = $_REQUEST['db_name'];
			if ($_REQUEST['db_pass']) $dbpass = $_REQUEST['db_pass'];
		}
		$link = @mysqli_connect($dbhost, $dbuser, $dbpass);
		
		if ($link && !mysqli_query($link, 'use ' . $dbase)) {
			mysqli_query($link, 'create database ' . $dbase);
			$db_exists = false;
		} else {
			$db_exists = true;
		}
		$s->assign('db_exists', $db_exists);
		
		$checks = array(
			'Can connect to MySQL Database' => !!$link,
			'DB has data' => $db_exists
		);
		if ($link && $step == 4) {
			$db_config = fopen(DB_CONFIG, 'w');
			fwrite($db_config, '<?php 
				$dbhost = "' . $dbhost . '";
				$dbuser = "' . $dbuser . '";
				$dbpass = "' . $dbpass . '";
				$dbase = "' . $dbase . '";
			?>');
		
		
			require_once(SITE_ROOT . '/core/Database.php');
			
			$sqlDir = dirname(__FILE__).'/../sql/';
			$dir  = new DirectoryIterator($sqlDir);
			foreach ($dir as $file) {
				$fileName = $file->getFilename();
				if ($fileName == '.svn' || $fileName == '.' || $fileName == '..') {
					continue;
				}
				$sql = file_get_contents($sqlDir . $fileName);
				$checks['Import core schema: ' . $fileName] = !!Database::singleton()->multi_query($sql);
			}
			
			
			$dataDir  = SITE_ROOT .'/modules/';
		
			$dir  = new DirectoryIterator($dataDir);
			foreach ($dir as $file) {
				$fileName = $file->getFilename();
				if (file_exists($dataDir . $fileName . '/schema.sql')) {
					$sql = file_get_contents($dataDir . $fileName . '/schema.sql');
					$checks['Import module schema: ' . $fileName] = !!Database::singleton()->multi_query($sql);
				}
			}
		}
		
		$s->assign('checks', $checks);
		$content = $s->fetch("step$step.htpl");
		break;
case 5:
	$s->assign ('complete', true);
	$content = $s->fetch("complete.htpl");
}

$s->assign('content', $content);
$s->assign('steps', $steps);
$s->display('shell.htpl');

?>