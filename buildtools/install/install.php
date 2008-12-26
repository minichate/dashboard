<?php

require_once('../../core/libs/Smarty.class.php');

define('SITE_ROOT', (dirname(__FILE__) . '/../../'));

$s = new Smarty();
$s->template_dir = dirname(__FILE__) . '/templates';
$s->compile_dir = SITE_ROOT . '/templates_c';

$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 0;

$steps = array(
	'Welcome', 'Checking permissions', 'Database', 'Install Schemas'
);
$s->assign('curstep', $step);

switch ($step) {
	case 0:
		$checks = array(
			'PHP 5.2 or better installed' => version_compare(PHP_VERSION, '5.2', '>') ? 1 : 0,
			'MySQL 5.0 or better installed' => version_compare(mysqli_get_client_info(), '5.0', '>') ? 1 : 0,
		);
		
		$s->assign('checks', $checks);
		$content = $s->fetch('step0.htpl');
		break;
	case 1:
		$checks = array(
			'templates directory is readable' => is_readable(SITE_ROOT . '/templates') ? 1 : 0,
			'templates_c directory is readable' => is_readable(SITE_ROOT . '/templates_c') ? 1 : 0,
			'templates_c directory is writeable' => is_writable(SITE_ROOT . '/templates_c') ? 1 : 0,
			'js/cache directory is readable' => is_readable(SITE_ROOT . '/js/cache') ? 1 : 0,
			'js/cache directory is writeable' => is_writable(SITE_ROOT . '/js/cache') ? 1 : 0
		);
		$s->assign('checks', $checks);
		$content = $s->fetch('step1.htpl');
		break;
	case 2:
		$fields = array(
			'Database Host' => '<input type="text" name="db_host" value="localhost" />',
			'Database Name' => '<input type="text" name="db_name" value="" />',
			'Database Username' => '<input type="text" name="db_user" value="" />',
			'Database Password' => '<input type="text" name="db_pass" value="" />',
		);
		$s->assign('fields', $fields);
		$content = $s->fetch('step2.htpl');
		break;
	case 3:
		//mysqli_close(Database::singleton()->link);
		$link = @mysqli_connect($_REQUEST['db_host'], $_REQUEST['db_user'], $_REQUEST['db_pass']);
		
		if ($link && !mysqli_query($link, 'use ' . $_REQUEST['db_name'])) {
			mysqli_query($link, 'create database ' . $_REQUEST['db_name']);
		}
		
		$checks = array(
			'Can connect to MySQL Database' => $link ? true : false
		);
		
		if ($link) {
			$db_config = fopen(SITE_ROOT . '/include/db-config.php', 'w');
			fwrite($db_config, '<?php 
				$dbhost = "' . $_REQUEST['db_host'] . '";
				$dbuser = "' . $_REQUEST['db_user'] . '";
				$dbpass = "' . $_REQUEST['db_pass'] . '";
				$dbase = "' . $_REQUEST['db_name'] . '";
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
				$checks['Import core schema: ' . $fileName] = Database::singleton()->multi_query($sql) ? true : false;
			}
			
			
			$dataDir  = SITE_ROOT .'/modules/';
		
			$dir  = new DirectoryIterator($dataDir);
			foreach ($dir as $file) {
				$fileName = $file->getFilename();
				if (file_exists($dataDir . $fileName . '/schema.sql')) {
					$sql = file_get_contents($dataDir . $fileName . '/schema.sql');
					$checks['Import module schema: ' . $fileName] = Database::singleton()->multi_query($sql) ? true : false;
				}
			}
		}
		
		$s->assign('checks', $checks);
		$content = $s->fetch('step3.htpl');
		break;
}

$s->assign('content', $content);
$s->assign('steps', $steps);
$s->display('shell.htpl');

?>