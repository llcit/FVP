<?php
	$progress = file_get_contents('./progress/'.$_POST['pid']);
	echo json_encode($progress);
?>