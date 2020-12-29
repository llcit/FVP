<?php
  include "../../inc/db_pdo.php";
  include "../../inc/sqlFunctions.php";
	$pres = getPid($_GET['uid'],$_GET['eid'],$_GET['presentation_type']);
	$pid = $pres->id;
	if (file_exists('./progress/'.$pid.'.txt')) {
		$progress = file_get_contents('./progress/'.$_GET['pid'].'.txt');
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