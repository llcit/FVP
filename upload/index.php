<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        include "../inc/db_pdo.php";
        include "../inc/dump.php";
        include "../inc/sqlFunctions.php";
				$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
				$pageTitle = "Flagship Video Upload";
				$subTitle = "Upload Video";
				$titleText = "Select one or more videos and press upload.";
				session_start();
				if (!isset($_SESSION['username'])) { 
					$role = 'anonymous'; 
				} 
				else {
					$user = getUser($pdo,$_SESSION['username']);
					$role =  $user->roles;
					$userName = $user->first_name . " " . $user->last_name;
				}
			  $pageContent = "
			            <div id='fine-uploader-s3'></div>
				";
				$user_id = $_GET['user_id']; // FVP TO DO: switch to $_POST after testing
				$event_id = $_GET['event_id'];
				$pid = getPresentationId($user_id,$event_id);
				$videoExists = '';
				if ($pid) {
					$videoExists = "<p> You already have a video uploaded for this event! [LINK TO VID IN NEW WINDOW]";

				}
				function getPresentationId($user_id,$event_id) {
					global $pdo;
					$sql ="SELECT id FROM presentations WHERE (user_id=? AND event_id=?)";
	        $stmt = $pdo->prepare($sql);
	        $stmt->execute([$user_id,$event_id]); 
	        if($stmt->rowCount() > 0) {
	            // presentation exists-- overwrite
	            $result = $stmt->fetch(PDO::FETCH_OBJ);
	            $pid = $result->id;
	        } else {  
	            $pid = null;
	        }
	        return $pid;
				}
      ?>
			<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
			<link rel="stylesheet" href="../css/main.css" type="text/css"/>
			<link href="<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/fine-uploader-gallery.css" rel="stylesheet">
			<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
			<script src='<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/s3.jquery.fine-uploader.min.js'></script>

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
											 	user_id:'<?php echo($user_id); ?>', 
											 	event_id: '<?php echo($event_id); ?>'
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
								console.log('prog:', percent);
								if (percent == 100) {
									console.log('GO TO FFMPEG');
									getFFMPEGProgress(<?php echo($pid) ;?>);
								}
							}
						}
					});
				});
				function getFFMPEGProgress(pid) {
					console.log("FFMPEG Progress for " + pid);
					var url = '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']); ?>/ffmpegProgress.php';
					$.post(url, {pid: pid}, 
					  function(response) {
					    console.log("FFMPEG Progress: " + response.progress);
					    if (response.progress < 100) {
					    	getFFMPEGProgress(pid);
					    }
					  }
					).fail(function(error){
						      console.log(error);
						}
					);
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
        <form method="post" action="">
          <div class="container">
             <div class="row fv_main">
                <div class="card fv_card">
                    <div class="card-body fv_card_body" style='border-bottom:solid 1px gray;'>
                       <h2 class="card-title"><?php echo($subTitle); ?></h2>
                       <p class="card-text"><?php echo($titleText); ?></p>
                       <?php echo($videoExists); ?>
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

