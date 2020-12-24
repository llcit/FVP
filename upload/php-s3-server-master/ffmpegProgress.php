<?php
	$progress = file_get_contents('./progress/'.$_GET['pid'].'.txt');
	echo ($progress);
?>