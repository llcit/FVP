<?php

	function writeNavLinks($role,$context) {
		$SETTINGS = parse_ini_file(__DIR__."/settings.ini");
	  $links = [
			['label'=>'Login','href'=>$SETTINGS['base_url'].'/login.php','req'=>['anonymous']],
			['label'=> 'Your Videos','href'=>$SETTINGS['base_url'].'/personal.php','req'=>['student']],
			['label'=>'Upload Video','href'=>$SETTINGS['base_url'].'/upload/','req'=>['student']],
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
  function writeConsentForm($userName,$grant_internal=null,$grant_public=null) {
  	if (!isset($grant_internal)) $grant_internal = 1;
  	if (!isset($grant_public)) $grant_public = 1;
  	$isChecked_internal = [];
  	$isChecked_public = [];
  	$isChecked_internal[$grant_internal] = "CHECKED";
  	$isChecked_public[$grant_public] = "CHECKED";
  	$consentForm = "
			<div class='card_body fv_card_body'>
				<h3>
					Flagship Video Project Release Form
				</h3>
				<p class='card_text fv_pageContent'>
					I hereby grant permission to the American Councils for International Education and NSEP/DLNSEO to post the video recording of [target language] presentation at the end of the Capstone program, internally within the Flagship community of language programs organization for training purposes with future language students and instructors, US government employees or contracted employees on a need-to-know basis.  I understand that my name and a brief description of my program participation (name of program, year, home institution, host institution and host country) as well as a brief description of the topic of my presentation will be attached to this video. 
				</p>
				<span class='form-check card_text fv_pageContent' style='white-space: nowrap;'>
					<input class='form-check-input' type='radio' name='grant_internal' id='grant_internal' value='1' ".$isChecked_internal[1].">
					<label class='form-check-label' for='grant_internal'>
						I grant permission to post this video on the internal website for the Flagship community.
					</label>
				</span>
				<span class='form-check card_text fv_pageContent' style='white-space: nowrap;'>
					<input class='form-check-input' type='radio' name='grant_internal' id='grant_internal' value='-1' ".$isChecked_internal[-1].">
					<label class='form-check-label' for='grant_internal'>
						I do grant not permission to post this video on the internal website for the Flagship community.
					</label>
				</span>	 		
				<p class='card_text fv_pageContent'>
					I hereby grant permission to American Councils for International Education and NSEP/DLNSEO to post the video of my presentation on a public-facing portion of websites that are only accessible to the general public to showcase participants’ language proficiency and professional performance in the target language.  I understand that my name and a brief description of my program participation (name of program, year, home institution, host institution and host country) as well as a brief description of the topic of my presentation will be attached to this video. 
				</p>
				<span class='form-check card_text fv_pageContent' style='white-space: nowrap;'>
					<input class='form-check-input' type='radio' name='grant_public' id='grant_public' value='1' ".$isChecked_public[1].">
					<label class='form-check-label' for='grant_public'>
						I grant permission to post this video  
						on the public website for the Flagship community.
					</label>
				</span>
				<span class='form-check card_text fv_pageContent' style='white-space: nowrap;'>
					<input class='form-check-input' type='radio' name='grant_public' id='grant_public' value='-1' ".$isChecked_public[-1].">
					<label class='form-check-label' for='grant_public'>
						I do grant not permission to post this video 
						on the public website for the Flagship community.
					</label>
				</span>
				<p class='card_text fv_pageContent'>
					In granting permission to post the video of my presentation, I am not forfeiting any copyright or other legal right I may have in this work for any other purpose. I ACKNOWLEDGE that I am 18 years of age or older and have read and understood the terms of this release. 
				</p>  
				<p class='card_text fv_pageContent'>
					Please type in your name, exactly as it appears in the top right corner of this page and click 'Submit'. 
				</p>  
				<div>
					<label for='username'>Your Name:</label>
					<input type='text' class='textbox fv_text_box' id='userName' name='userName' placeholder='Type name here' autocomplete='off' style='width:200px;' onKeyUp='enableSubmit()' >
				</div>	
				<div>
					<input type='button' class='btn btn-primary fv_button' value='Submit' name='submitConsent' id='submitConsent' onClick=\"grantConsent('$userName')\" disabled>
				</div>
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
		if (abs($video['grant_public']) == 1)  {
			$consent_pub_color = ($video['grant_public'] == 1) ? '#2EAE32' : '#BA3D40';
		}
		else {
			$consent_pub_color = '#ccc';
		}
		if (abs($video['grant_internal']) == 1)  {
			$consent_int_color = ($video['grant_internal'] == 1) ? '#2EAE32' : '#BA3D40';
		}
		else {
			$consent_int_color = '#ccc';
		}
		$privacy = "  <span style='color:$consent_pub_color'>Public</span> - 
									<span style='color:$consent_int_color'>Internal</span>
							 ";
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
											<span class='extras' style='padding-left:15px;'>
												$privacy
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