<?php
  include "../../inc/db_pdo.php";
  include "../../inc/sqlFunctions.php";
  if ($_GET['findBy'] == 'id') {
  	$pid = $_GET['key'];
  }
  else {
		$pres = getPid($_GET['key'],$_GET['findBy']);
		$pid = $pres->id;
  }
	if (file_exists('./progress/'.$pid.'.txt')) {
		$progress = file_get_contents('./progress/'.$pid.'.txt');
		if ($progress == 100) {
			unlink('./progress/'.$pid.'.txt');
			echo ('100');
		}
		else {
			echo ($progress);
		}
	}
	else {
		echo ('0');
	}
?>