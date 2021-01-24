<?php

	function writeNavLinks($role,$context) {
		$SETTINGS = parse_ini_file(__DIR__."/settings.ini");
	  $links = [
			['label'=>'Login','href'=>$SETTINGS['base_url'].'/login.php','req'=>['anonymous']],
			['label'=> 'Your Videos','href'=>$SETTINGS['base_url'].'/personal.php','req'=>['student']],
			['label'=>'Upload Video','href'=>$SETTINGS['base_url'].'/upload/','req'=>['student','staff','admin']],
			['label'=>'Manage Events','href'=>$SETTINGS['base_url'].'/manage/','req'=>['staff','admin']],
			['label'=>'Video Showcase','href'=>$SETTINGS['base_url'].'/player/index.php?sc=1','req'=>[]],
			['label'=>'Video Archive','href'=>$SETTINGS['base_url'].'/archive.php','req'=>['staff','admin']],
			['label'=>'About This Site','href'=>$SETTINGS['base_url'].'/about.php','req'=>[]]
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
			if (empty($link['req']) || in_array($role,$link['req'])) {
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
  function writeConsentForm() {
  	$consentForm = "
	  	<div class='fv_main'>
	  		<h3>
	  			Flagship Video Project Release Form
	  		</h3>
	  		<p>
					I hereby grant permission to the American Councils for International Education and NSEP/DLNSEO to post the video recording of [target language] presentation at the end of the Capstone program, internally within the Flagship community of language programs organization for training purposes with future language students and instructors, US government employees or contracted employees on a need-to-know basis.  I understand that my name and a brief description of my program participation (name of program, year, home institution, host institution and host country) as well as a brief description of the topic of my presentation will be attached to this video. 
	  		</p>
	  	</div>
  	";
  	return $consentForm;
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
		global $user;
		$hasTranscript = '';
		$hasTranslation = '';
		$languageLabel = strtoupper(substr($video['language'],0,2));
		if ($video['transcript_final']) {
			$hasTranscript = "<i class='fas fa-closed-captioning'></i> $languageLabel";
		}
		else if ($video['transcript_raw']) {
			$hasTranscript = "<i class='far fa-closed-captioning'></i> $languageLabel";
		}
		if ($video['translation_final']) {
			$hasTranslation = "<i class='fas fa-closed-captioning'></i> EN";
		}
		else if ($video['translation_raw']) {
			$hasTranslation = "<i class='far fa-closed-captioning'></i> EN";
		}
		$allowDelete = false;
		if (
				 (
				 $user->role == 'admin' || 
				 $user->role == 'staff' || 
				 $user->id == $video['user_id']
				 ) &&
				 (
					$video['translation_final'] != 1 &&
					$video['transcript_final'] != 1
				 ) 
				){
					$allowDelete = true;
				}
		
		if ($allowDelete) {
			$deleteButton = "
											<a href='javascript:deleteVideo(".$video['id'].")'>
												<i class='fas fa-times-circle deleteButton pull-right'></i>
											</a>
			";
		}
		else {
			$deleteButton = "&nbsp;";
		}
		$duration = gmdate("i:s", $video['duration']);
		$row = "
							<div class='videoPanel col-sm-4' id='videoPanel_".$video['id']."' name='videoPanel_".$video['id']."'>
								<table border=0 cellpadding=0 cellspacing=0 width=100%>
									<tr>
										<td colspan=2>
											<p class='studentName'>".$video['first_name']." ".$video['last_name']."</p>
											$deleteButton
										</td>
									</tr>
									<tr>
										<td>
											<div class = 'thumbWrapper' id = 'thumb_".$video['id']."'>
											</div>
										</td>
										<td>
											<div class = 'videoDetails'>
											<p class='details'>".$video['progYrs']."</p>
											<p class='details'>".$video['city'].", ".$video['country']."</p>
											<p class='details'>".$video['type']."</p>
											<p class='details'>".$video['phase']."</p>
											</div>
											<input type=hidden id='videoData_".$video['id']."' name='videoData_".$video['id']."'
											value='".json_encode($video)."'>
										</td>
									</tr>
									<tr>
										<td colspan=2>
											<span class='extras'>
												$hasTranscript
											</span>
											<span class='extras'>
												$hasTranslation
											</span>
											<span class='extras pull-right' style='padding-top:3px;'>
												$duration
											</span>
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