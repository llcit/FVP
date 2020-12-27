<?php
	if (file_exists('./progress/'.$_GET['pid'].'.txt')) {
		$progress = file_get_contents('./progress/'.$_GET['pid'].'.txt');
		if ($progress == 100) {
			unlink('./progress/'.$_GET['pid'].'.txt');
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