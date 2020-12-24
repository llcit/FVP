<?php
	$progress = file_get_contents('./progress/'.$_POST['pid'].'.txt');
	echo json_encode(['progress'=>$progress]);
?>