<?php
	$progress = file_get_contents('./progress/'.$_GET['pid'].'.txt');
	echo json_encode(['progress'=>$progress]);
?>