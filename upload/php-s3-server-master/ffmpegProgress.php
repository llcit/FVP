<?php
	$progress = file_get_contents('./progress/'.$_GET['key']);
	echo json_encode($progress);
?>