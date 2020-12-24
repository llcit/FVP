<?php
	$progress = file_get_contents('./progress/'.$_GET['pid'].'.txt');
	if ($progress == 100) {
		unlink('./progress/'.$_GET['pid'].'.txt');
	}
	echo ($progress);
?>