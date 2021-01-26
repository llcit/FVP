<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
				include "../inc/db_pdo.php";
				include "../inc/dump.php";
				include "../inc/sqlFunctions.php";
				include "../inc/htmlFunctions.php";
				$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
				$pageTitle = "Flagship Video Project";
				$subTitle = "Upload Video";
				$titleText = "Select a video from your computer and press upload. Note that you can either drag and drop the file onto the page or click the 'Upload a File' to use the file selector. ";
				session_start();
				if (!isset($_SESSION['username'])) { 
					exit(header("location:../login.php"));
				} 
				else {
					$user = getUser($pdo,$_SESSION['username']);
					$navLinks = writeNavLinks($user->role,'header');
					$userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
					$welcomeMsg = "
						$userName 
						<a href='".$SETTINGS['base_url']."/logout.php' class='btn btn-xs btn-icon btn-danger'>
						  <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
						</a>
					";
				}
				if (!$_GET['event_id']) {
					$userEvents = getUserEvents($user->id);
					$pageContent = buildPresentatonSelect($userEvents);
				}
				else {
					$event_id = ($_GET['event_id']) ? $_GET['event_id']:0;
					$language = getLanguage($_GET['event_id']);
					$presentation_type = $_GET['presentation_type'];
					// generate a unique code for public asccess
					$salt = $user->id.$event_id.$presentation_type;
					$access_code=md5(uniqid($salt, true));
					$presentationData = getPresentationId($user->id,$event_id,$presentation_type);
					$pid = $presentationData['pid'];
					if (abs($presentationData['grant_internal']) == 1) {
						$grant_internal = $presentationData['grant_internal'];
						$grant_public = $presentationData['grant_public'];
					}
					else if(abs($_GET['grant_internal']) == 1) {
						$grant_internal = $_GET['grant_internal'];
						$grant_public = $_GET['grant_public'];
					}
					if (abs($grant_internal) != 1 || abs($grant_public) !=1 || $editConsent) {
						$titleText = "Before you can upload a file, you must complete the consent form below and indicate whether or not to grant viewing access of your video to the Flagship Program's internal and/or public-facing site.";
						$expectedUserName = $user->first_name . " " . $user->last_name;
						$pageContent = writeConsentForm($expectedUserName,$grant_internal,$grant_public);
						$pageContent .= "
							<input type=hidden name='event_id' id='event_id' value='".$_GET['event_id']."'>
							<input type=hidden name='presentation_type' id='presentation_type' value='".$_GET['presentation_type']."'>
						";
					}
					else {
					  $pageContent = "
					            <div id='fine-uploader-s3'></div>
					  ";
					}
				}
				$videoExistsMsg = '';
				// used for ajax progress and thumb preview
				// if we know the presentation id (i.e. on a reupload), we use it
				// otherwise, use the unique access_code that we generated above
				$findBy = 'access_code';
				if ($pid) {
					$videoExistsMsg = "
						<p class='card-text'>
							You have previously uploaded a video uploaded for this event! If you upload a new video, it will overwrite the existing video!
						</p>
						<p class='card-text'>
							<a href='../player/?v=$pid' target=_blank>
								View saved video
							</a>
						</p>
					";
					$findBy = 'id';
				}
				function buildPresentatonSelect($events) {
					if (count($events)==0) {
						$eventSelect = "
                <div class='msg neutral'>
                  You have not been registered for any events yet.
                </div>
             ";
					}
					else {
						$eventSelect = "
							<div class='eventList'>
								<H4>
									Select the event and presentation type you want to upload a video to.
								</H4>
								<div class='form-group' style='border:solid 1px #000;padding:30px;'>
														";
						$eventSelect .= "
			          <label for='events' style='width:50px;'>Event:</label>
			          <select class='form-control fv_inline_select' id='event_id' name='event_id' style='width:500px;margin-top:30px;margin-bottom:30px;'>
			      ";
						foreach($events as $event) {
			        $eventSelect .= "
			            <option value='".$event['event_id']."'>".
			            $event['progName']." ".$event['progYrs']." (".$event['phase'].")
			            </option>
			        ";
			      }
			      $eventSelect .= "
		          </select>
		          <p><b>Presentation Type:</b></p>
		          <div style='padding-left:30px'>
								<div class='form-check'>
								  <input class='form-check-input' type='radio' name='presentation_type' id='presentation_type' value='Presentation' checked>
								  <label class='form-check-label' for='presentation_type'>
								    Presentation
								  </label>
								</div>
							  <div class='form-check'>
								  <input class='form-check-input' type='radio' name='presentation_type' id='presentation_type' value='Presentation + Q&A'>
								  <label class='form-check-label' for='presentation_type'>
								    Presentation + Q&A
								  </label>
								</div>
								<div class='form-check'>
								  <input class='form-check-input' type='radio' name='presentation_type' id='presentation_type' value='Interview'>
								  <label class='form-check-label' for='presentation_type'>
								    Interview
								  </label>
								 </div>
							</div>
						</div>
						<div style='width:100%;text-align:center;'>
		          <a href='javascript:setUploadVals();' class='btn btn-primary' id='setValsButton'>
		            Continue
		          </a>
		        </div>
						";
					}
				return $eventSelect;
				}
      ?>
			<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
			<link rel="stylesheet" href="../css/main.css" type="text/css"/>
			<link href="<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/fine-uploader-gallery.css" rel="stylesheet">
			<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
			<script src='<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/s3.jquery.fine-uploader.min.js'></script>
	    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
	    
	    <!-- include local js libraries -->
	    <script src='../js/main.js'></script>
	    <script src='../js/S3FileGen.js'></script>

 			<script type="text/template" id="qq-template-s3">
        <div class="qq-uploader-selector qq-uploader qq-gallery" qq-drop-area-text="Drop files here">
            <div class="qq-total-progress-bar-container-selector qq-total-progress-bar-container">
                <div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-total-progress-bar-selector qq-progress-bar qq-total-progress-bar"></div>
            </div>
            <div class="qq-upload-drop-area-selector qq-upload-drop-area" qq-hide-dropzone>
                <span class="qq-upload-drop-area-text-selector"></span>
            </div>
            <div class="qq-upload-button-selector qq-upload-button">
                <div>Upload a file</div>
            </div>
            <span class="qq-drop-processing-selector qq-drop-processing">
                <span>Processing dropped files...</span>
                <span class="qq-drop-processing-spinner-selector qq-drop-processing-spinner"></span>
            </span>
            <ul class="qq-upload-list-selector qq-upload-list" role="region" aria-live="polite" aria-relevant="additions removals">
                <li>
                    <span role="status" class="qq-upload-status-text-selector qq-upload-status-text"></span>
                    <div class="qq-progress-bar-container-selector qq-progress-bar-container">
                    		<div class = "progress_status_wrapper">
                    			<span class= "progress_status_label">
                    				Video upload: 
                    			</span>
                    			<span class= "progress_status_percent">
                    				0%
                    			</span>
                    		</div>
                        <div role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" class="qq-progress-bar-selector qq-progress-bar"></div>
                    </div>
                    <span class="qq-upload-spinner-selector qq-upload-spinner"></span>
                    <div class="qq-thumbnail-wrapper">
                        <a class="preview-link" target="_blank">
                            <img class="qq-thumbnail-selector" qq-max-size="120" qq-server-scale>
                        </a>
                    </div>
                    <button type="button" class="qq-upload-cancel-selector qq-upload-cancel">X</button>
                    <button type="button" class="qq-upload-retry-selector qq-upload-retry">
                        <span class="qq-btn qq-retry-icon" aria-label="Retry"></span>
                        Retry
                    </button>

                    <div class="qq-file-info">
                        <div class="qq-file-name">
                            <span class="qq-upload-file-selector qq-upload-file"></span>
                            <span class="qq-edit-filename-icon-selector qq-edit-filename-icon" aria-label="Edit filename"></span>
                        </div>
                        <input class="qq-edit-filename-selector qq-edit-filename" tabindex="0" type="text">
                        <span class="qq-upload-size-selector qq-upload-size"></span>
                        <button type="button" class="qq-btn qq-upload-delete-selector qq-upload-delete">
                            <span class="qq-btn qq-delete-icon" aria-label="Delete"></span>
                        </button>
                        <button type="button" class="qq-btn qq-upload-pause-selector qq-upload-pause">
                            <span class="qq-btn qq-pause-icon" aria-label="Pause"></span>
                        </button>
                        <button type="button" class="qq-btn qq-upload-continue-selector qq-upload-continue">
                            <span class="qq-btn qq-continue-icon" aria-label="Continue"></span>
                        </button>
                    </div>
                </li>
            </ul>

            <dialog class="qq-alert-dialog-selector">
                <div class="qq-dialog-message-selector"></div>
                <div class="qq-dialog-buttons">
                    <button type="button" class="qq-cancel-button-selector">Close</button>
                </div>
            </dialog>

            <dialog class="qq-confirm-dialog-selector">
                <div class="qq-dialog-message-selector"></div>
                <div class="qq-dialog-buttons">
                    <button type="button" class="qq-cancel-button-selector">No</button>
                    <button type="button" class="qq-ok-button-selector">Yes</button>
                </div>
            </dialog>

            <dialog class="qq-prompt-dialog-selector">
                <div class="qq-dialog-message-selector"></div>
                <input type="text">
                <div class="qq-dialog-buttons">
                    <button type="button" class="qq-cancel-button-selector">Cancel</button>
                    <button type="button" class="qq-ok-button-selector">Ok</button>
                </div>
            </dialog>
        </div>
    	</script>

			<script>
				$(document).ready(function () {
					if (typeof qq !== "undefined") {
						var noFile = false;                                                                 
						qq.isFileOrInput = function(maybeFileOrInput) {
							'use strict';
							if (window.File && Object.prototype.toString.call(maybeFileOrInput) === '[object File]') {
								return true;
							}
							return qq.isInput(maybeFileOrInput);
						};
						$('#fine-uploader-s3').fineUploaderS3({
							template: 'qq-template-s3',
							request: { 
								endpoint: 'https://<?php echo($SETTINGS['S3_BUCKET_NAME']); ?>.s3.amazonaws.com',
								accessKey: '<?php echo($SETTINGS['AWS_SERVER_PRIVATE_KEY']); ?>',  
								params: {
													pid:'<?php echo($pid); ?>',
												 	user_id:'<?php echo($user->id); ?>', 
												 	event_id:'<?php echo($event_id); ?>',
												 	language:'<?php echo($language); ?>',
												 	presentation_type:'<?php echo($presentation_type); ?>',
												 	access_code:'<?php echo($access_code); ?>',
												 	grant_internal:'<?php echo($grant_internal); ?>',
												 	grant_public:'<?php echo($grant_public); ?>'
												 }
							},
							signature: {
								endpoint: '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']."/".$SETTINGS['FINEUPLOADER_BACKEND_SCRIPT']); ?>'
							},
							uploadSuccess: {
								endpoint: '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']."/".$SETTINGS['FINEUPLOADER_BACKEND_SCRIPT']); ?>?success',
								params: {
									isBrowserPreviewCapable: qq.supportedFeatures.imagePreviews
								}
							},
							iframeSupport: {
								localBlankPagePath: '/server/success.html'
							},
							cors: {
								expected: true
							},
							chunking: {
								enabled: true
							},
							resume: {
								enabled: true
							},
							deleteFile: {
								enabled: true,
								method: 'POST',
								endpoint: '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']."/".$SETTINGS['FINEUPLOADER_BACKEND_SCRIPT']); ?>'
							},
							validation: {
								itemLimit: 5,
								sizeLimit: '<?php echo($SETTINGS['S3_MAX_FILE_SIZE']); ?>'
								// FVP TO DO : Ad extesions (mp4, mov, m4a ,etc)
							},
							thumbnails: {
								placeholders: {
									notAvailablePath: '<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/placeholders/not_available-generic.png',
									waitingPath: '<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/placeholders/waiting-generic.png'
								}
							},
							callbacks: {
								onProgress: function(id,name,uploadBytes,totalBytes) {
									var percent = (uploadBytes/totalBytes)*100;
									$('.progress_status_percent').html(Math.round(percent)+'%');
									if (percent == 100) {
										// video is done uploading
										// move progress to ripping audio
										$('.progress_status_label').html('Creating Audio File:');
										var key = '';
										var findBy = '<?php echo($findBy);?>';
										if (findBy == 'id') {
											key = '<?php echo($pid);?>';
										}
										else {
											key = '<?php echo($access_code);?>';
										}
										updateThumb(key,findBy);
										getFFMPEGProgress(key,findBy);
									}
								}
							}

						});
					}
				});
				function getFFMPEGProgress(key,findBy) {
					var url = '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']); ?>/ffmpegProgress.php';
					var request = $.ajax({
					    url: url,
					    type: 'GET',
					    data: { key:key,findBy,findBy} ,
					    contentType: 'application/json; charset=utf-8'
					});
					request.done(function(progress) {
				    console.log("FFMPEG Progress: " + progress);
				    if (progress < 100) {
              $('.qq-progress-bar-container-selector').show();
              $('.qq-progress-bar-selector').css('width',progress+'%');
              $('.progress_status_percent').html(progress+'%');
				    	setTimeout(function() {
				    		getFFMPEGProgress(access_code)
				    	},500);
				    }
				    else {
				    	$('.qq-progress-bar-container-selector').hide();
              $('.qq-progress-bar-selector').css('width','0%');
				    	console.log("DONE!");
				    }
					});
					request.fail(function(jqXHR, textStatus) {
					  console.log('Error in getting audio progress',textStatus);
					});
				}
				function updateThumb(key,findBy) {
					var url = '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']); ?>/generateThumb.php';
					var request = $.ajax({
					    url: url,
					    type: 'GET',
					    data: { key:key,findBy,findBy} ,
					    contentType: 'application/json; charset=utf-8'
					});
					request.done(function(thumb) {
				   $("#qq-thumbnail-wrapper").html(thumb);
					});
					request.fail(function(jqXHR, textStatus) {
					  console.log('Error in getting thumb',textStatus);
					});
				}
				function setUploadVals() {
					$('#uploadForm').submit();
				}
			</script>
    </head>
    <body>
      <div class="panel panel-default">
        <div class="panel-heading fv_heading">
          <img src='../img/logo_lf.png'>
          <span class='pageTitle'>
          		<?php echo($pageTitle); ?>
          </span>
          <span class='pull-right'>
            <img src='../img/logo_ac.png'>
          </span>
        </div>
        <div class='fv_subHeader'>
	        <?php echo($navLinks); ?>
	        <?php echo($welcomeMsg); ?>
      	</div>
        <form method="get" action="" id='uploadForm' name='uploadForm'>
          <div class="container">
             <div class="row fv_main">
                <div class="card fv_card">
                    <div class="card-body fv_card_body" style='border-bottom:solid 1px gray;'>
                       <h2 class="card-title"><?php echo($subTitle); ?></h2>
                       <p class="card-text"><?php echo($titleText); ?></p>
                       <?php echo($videoExistsMsg); ?>
                    </div>
                    <?php echo($pageContent); ?>
                </div>

              </div>
          </div>
        </form>
        <div class="footer">
          <p> </p>
        </div>
      </div>
    </body>
</html>

