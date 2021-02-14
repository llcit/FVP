<?php
	function writePageHeader($base_url,$user,$pageTitle) {
		$navLinks = writeNavLinks($user->role,'header');
		$pageTitle = "Flagship Video Project";
		$userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
		$welcomeMsg = "
          $userName 
          <a href='".$base_url."/logout.php' class='btn btn-xs btn-icon btn-danger'>
            <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
          </a>
        ";
		  $cdnDependencies = "
        <link rel='stylesheet' href='https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css'>
        <script src='https://code.jquery.com/jquery-3.5.1.min.js'></script>
        <script src='https://code.jquery.com/ui/1.12.1/jquery-ui.js'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/moment.min.js'></script>
        <link rel='stylesheet' href='https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css' integrity='sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm' crossorigin='anonymous'>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js' integrity='sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q' crossorigin='anonymous'></script>
        <script src='https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js' integrity='sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl' crossorigin='anonymous'></script>
        <script src='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/js/bootstrap-select.min.js' integrity='sha512-yDlE7vpGDP7o2eftkCiPZ+yuUyEcaBwoJoIhdXv71KZWugFqEphIS3PU60lEkFaz8RxaVsMpSvQxMBaKVwA5xg==' crossorigin='anonymous'></script>
        <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.13.18/css/bootstrap-select.min.css' integrity='sha512-ARJR74swou2y0Q2V9k0GbzQ/5vJ2RBSoCWokg4zkfM29Fb3vZEQyv0iWBMW/yvKgyHSR/7D64pFMmU8nYmbRkg=='' crossorigin='anonymous' />
        <link rel='stylesheet' href='https://use.fontawesome.com/releases/v5.1.0/css/all.css' integrity='sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt' crossorigin='anonymous'>
      ";
      $html = "
	      <div class='panel-heading fv_heading' style='overflow:none;'>
	        <div class='row flex-nowrap'>
	          <div class='col-3'>
	            <img src='".$base_url."/img/logo_lf.png' class='logo-img-fluid'>
	          </div>
	          <div class='pageTitle col-6'>
	          		$pageTitle
	          </div>
	          <div class='col-3'>
	            <img src='".$base_url."/img/logo_ac.png' class='logo-img-fluid float-right'>
	          </div>
	        </div>
	      </div>
	      <div class='fv_subHeader'>
	        $navLinks
	        $welcomeMsg
	      </div>
      ";
      return $cdnDependencies . $html;
	}
	function writeNavLinks($role,$context) {
		$SETTINGS = parse_ini_file(__DIR__."/settings.ini");
	  $links = [
			['label'=>'Login','href'=>$SETTINGS['base_url'].'/login.php','req'=>['anonymous']],
			['label'=> 'Your Videos','href'=>$SETTINGS['base_url'].'/personal.php','req'=>['student']],
			['label'=>'Upload Video','href'=>$SETTINGS['base_url'].'/upload/','req'=>['student']],
			['label'=>'Manage','href'=>$SETTINGS['base_url'].'/manage/','req'=>['admin']],
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
  function buildVideoList($videos,$displayPublicLink=null) {
		$count = 0;
		$videoList = "<form id='videoData' name='videoData'>";
		if ($videos) {
			foreach ($videos as $video) {
				if ($count%3==0) {
					if ($count > 1) {
						$videoList .= "</div>";
					}
					$videoList .= "<div class='row fv_video_table_wrapper'>";
				}
				$videoList .= buildVideoRow($video,$displayPublicLink);
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

	function buildVideoRow($video,$displayPublicLink=null) {
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
												<i class='fas fa-times-circle deleteButton float-right'></i>
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
		if ($displayPublicLink) {
			$row .= "
					<div class='fv_linkWrapper'>
						<span class='extras'>
							<b>Public Link:</b> http://video.thelanguageflagship.tech/player/index.php?v=".$video['id']."&ac=".$video['access_code']."
						</span>
					</div>
			";
		}
		return $row;
	}