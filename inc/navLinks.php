<?php
	function writeNavLinks($role,$context) {
		$SETTINGS = parse_ini_file(__DIR__."/settings.ini");
	  $links = [
	  	['label'=>'Login','href'=>$SETTINGS['base_url'].'/login.php','req'=>['anonymous']],
	  	['label'=> 'Your Videos','href'=>$SETTINGS['base_url'].'/personal.php','req'=>['student']],
      ['label'=>'Upload Video','href'=>$SETTINGS['base_url'].'/upload/','req'=>['student','staff','admin']],
      ['label'=>'Manage Events','href'=>$SETTINGS['base_url'].'/manage/','req'=>['staff','admin']],
	  	['label'=>'Video Showcase','href'=>$SETTINGS['base_url'].'/player/'].'?sc=1',
	  	['label'=>'Video Archive','href'=>$SETTINGS['base_url'].'/archive/','req'=>['staff','admin']],
	  	['label'=>'About This Site','href'=>$SETTINGS['base_url'].'/about.php'],
	  ];
	  if ($context == 'header') {
	  	$class = 'linkList_header';
	  }
	  else {
	  	$class = 'linkList';
	  }
	  $linkList = "
	  	<ul class='$class'>
	  ";
	  foreach($links as $link) {
	  	if (!$link['req'] || in_array($role,$link['req'])) {
	  		$linkList .= "
	  			<li><a href='".$link['href']."'>".$link['label']."</a></li>
	  		";
	  	}
	  }
	  $linkList .= "
	  	</ul>
	  ";
   	return $linkList;
  }
?>