<?php

	function writeNavLinks($role,$context) {
		$SETTINGS = parse_ini_file(__DIR__."/settings.ini");
	  $links = [
			['label'=>'Login','href'=>$SETTINGS['base_url'].'/login.php','req'=>['anonymous']],
			['label'=> 'Your Videos','href'=>$SETTINGS['base_url'].'/personal.php','req'=>['student']],
			['label'=>'Upload Video','href'=>$SETTINGS['base_url'].'/upload/','req'=>['student','staff','admin']],
			['label'=>'Manage Events','href'=>$SETTINGS['base_url'].'/manage/','req'=>['staff','admin']],
			['label'=>'Video Showcase','href'=>$SETTINGS['base_url'].'/player/index.php?sc=1'],
			['label'=>'Video Archive','href'=>$SETTINGS['base_url'].'/archive/','req'=>['staff','admin']],
			['label'=>'About This Site','href'=>$SETTINGS['base_url'].'/about.php']
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
  function buildVideoList($videos) {
		$count = 0;
		$videoList = "<form id='videoData' name='videoData'>";
		if ($videos) {
			foreach ($videos as $video) {
				if ($count%3==0) {
					if ($count > 1) {
						$videoList .= "</div>";
					}
					$videoList .= "<div class='row'>";
				}
				$videoList .= buildVideoRow($video);
				$count++;
			}
			$videoList .= "</div>";
		}
		else {
			$videoList .= "<div class='empty'>There are no videos that meet the search options you have selected.</div>";
		}
		$videoList .= "</form>";
		return $videoList;
	}

	function buildVideoRow($video) {
		$row = "
							<div class='videoPanel col-sm-4' id='videoPanel_".$video['id']."' name='videoPanel_".$video['id']."'>
								<table>
									<tr>
										<td>
											<div class = 'thumbWrapper' id = 'thumb_".$video['id']."'>
											</div>
										</td>
										<td>
											<div class = 'videoDetails'>
											<p class='studentName'>".$video['first_name']." ".$video['last_name']."</p>
											<p class='details'>".$video['progYrs']."</p>
											<p class='details'>".$video['city'].", ".$video['country']."</p>
											<p class='details'>".$video['type']."</p>
											<p class='details'>".$video['phase']."</p>
											</div>
											<input type=hidden id='videoData_".$video['id']."' name='videoData_".$video['id']."'
											value='".json_encode($video)."'>
										</td>
									</tr>
								</table>
							</div>
							<script>
									var thumb = generateFile('thumb','".$video['id']."','jpg','');
							</script>
					 ";
		return $row;
	}