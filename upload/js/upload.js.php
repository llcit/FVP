	<?php
		$SETTINGS = parse_ini_file(__DIR__."/../../inc/settings.ini");
		$data = json_decode($_GET['data'],true);
	?>
	$(document).ready(function () {
		$('.fv_total-progress-container').hide();
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
										pid:'<?php echo($data['pid']); ?>',
									 	user_id:'<?php echo($data['user_id']); ?>', 
									 	event_id:'<?php echo($data['event_id']); ?>',
									 	language:'<?php echo($data['language']); ?>',
									 	presentation_type:'<?php echo($data['presentation_type']); ?>',
									 	access_code:'<?php echo($data['access_code']); ?>',
									 	grant_internal:'<?php echo($data['grant_internal']); ?>',
									 	grant_public:'<?php echo($data['grant_public']); ?>'
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
					itemLimit: 1,
					sizeLimit: '<?php echo($SETTINGS['S3_MAX_FILE_SIZE']); ?>',
					allowedExtensions: ['mp4']
				},
				thumbnails: {
					placeholders: {
						notAvailablePath: '<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/placeholders/not_available-generic.png',
						waitingPath: '<?php echo($SETTINGS['FINEUPLOADER_FRONTEND_PATH']); ?>/placeholders/waiting-generic.png'
					}
				},
				callbacks: {
					onProgress: function(id,name,uploadBytes,totalBytes) {
						$('.qq-upload-button').hide();
						updateGlobalProgress('upload','active');
						var base_url = '<?php echo($SETTINGS['base_url']); ?>';
						if ($('.qq-thumbnail-selector').attr('src') != base_url + '/img/thumb_placeholder.gif') {
							$('.qq-thumbnail-selector').attr('src',base_url + '/img/thumb_placeholder.gif');
							$('.qq-thumbnail-selector').attr('src',base_url + '/img/thumb_placeholder.gif');
						}

						var percent = (uploadBytes/totalBytes)*100;
						$('.progress_status_percent').html(Math.round(percent)+'%');
						if (percent == 100) {
							// video is done uploading
							// move progress to ripping audio
							updateGlobalProgress('upload','success')
							$('.progress_status_label').html('Creating Audio File:');
							var key = '';
							var findBy = '<?php echo($findBy);?>';
							if (findBy == 'id') {
								key = '<?php echo($pid);?>';
							}
							else {
								key = '<?php echo($access_code);?>';
							}
							timerID=setTimeout(function() {
								updateThumb(key,findBy)
							},3000);
							getFFMPEGProgress(key,findBy);
						}
					}
				}
			});
		}
	});
	function updateGlobalProgress(stage,state) {
		var stages = [
			'upload',
			'audio',
			'transcribe',
			'cleanup',
			'finished'
		];
		$("#ps_finished").hide();
		$('.fv_total-progress-container').show();
		var progressStageId = "#ps_" + stage;
		var progressStateId = "progress_" + state;
		$(progressStageId).addClass(progressStateId);
		var currentIndex = stages.indexOf(stage);
		if (currentIndex < stages.length-1) {
			if (state != 'active') {
				$(progressStageId).removeClass("progress_active");
				// penultimate stage prompts finish
				if(currentIndex == stages.length-2) {
					var regex = /([0-9]*)\.jpg/;
					var match = $('.qq-thumbnail-selector').prop('src').match(regex);
					var vid = match[1];
					console.log("vid: ",vid);
					$("#ps_finished").attr("href", '../player/index.php?v=' + vid);
					$("#ps_finished").show();
				}
				else {
					var nextIndex = currentIndex + 1;
					var nextStage = stages[nextIndex];
					$("#ps_" + nextStage).addClass("progress_active");
				}
			}
		}

	}
	function getFFMPEGProgress(key,findBy) {
		if (typeof startTime == 'undefined') var startTime = new Date();
		var url = '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']); ?>/ffmpegProgress.php';
		var request = $.ajax({
				url: url,
				type: 'GET',
				data: { key:key,findBy:findBy} ,
				contentType: 'application/json; charset=utf-8'
		});
		request.done(function(progress) {
			console.log("FFMPEG Progress: " + progress);
			if (progress < 100) {
				$('.qq-progress-bar-container-selector').show();
				$('.qq-progress-bar-selector').css('width',progress+'%');
				$('.progress_status_percent').html(progress+'%');
				setTimeout(function() {
					getFFMPEGProgress(key,findBy)
				},500);
			}
			else {
				updateGlobalProgress('audio','success');
				$('.qq-progress-bar-selector').css('width','0%');
				var endTime = new Date();
				var ffmpeg_exec_time = endTime - startTime;
				transcribeProgress(ffmpeg_exec_time);
			}
		});
		request.fail(function(jqXHR, textStatus) {
			console.log('Error in getting audio progress',textStatus);
		});
	}
	function updateThumb(key,findBy) {
		console.log('Getting thumb...');
		var url = '<?php echo($SETTINGS['FINEUPLOADER_BACKEND_PATH']); ?>/generateThumb.php';
		var request = $.ajax({
				url: url,
				type: 'GET',
				data: { key:key,findBy:findBy} ,
				contentType: 'application/json; charset=utf-8'
		});
		request.done(function(thumb) {
		 $('.qq-thumbnail-selector').attr('src',thumb);
		});
		request.fail(function(jqXHR, textStatus) {
			console.log('Error in getting thumb',textStatus);
		});
	}
	var transcibeProgress = 0;
	var secondsToTranscribe = 0;
	function transcribeProgress(ffmpeg_exec_time) {
		var execOffset = '<?php echo($execOffset); ?>';
		console.log("ffmpeg_exec_time: ",ffmpeg_exec_time);
		console.log("execOffset: ",execOffset);
		var estimatedSeconds = Math.ceil((ffmpeg_exec_time*execOffset));
		console.log("estimatedSeconds: ",estimatedSeconds);
		$('.progress_status_label').html('Generating transcript:');
		$('.qq-progress-bar-container-selector').show();
		transcibeProgress = Math.ceil((secondsToTranscribe/estimatedSeconds) * 100);
		if (transcibeProgress < 100) {
			$('.qq-progress-bar-selector').css('width',transcibeProgress+'%');
			$('.progress_status_percent').html(transcibeProgress+'%');
			secondsToTranscribe++;
			setTimeout(function() {
				transcribeProgress(ffmpeg_exec_time);
			},1000);
		}
		else {
			updateGlobalProgress('transcribe','success');
			$('.qq-progress-bar-selector').css('width','0%');
			$('.qq-progress-bar-container-selector').hide();
			setTimeout(function() {
				updateGlobalProgress('cleanup','success')
			},1000);
		}
	}
	function setUploadVals() {
		$('#uploadForm').submit();
	}