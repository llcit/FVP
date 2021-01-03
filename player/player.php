<!DOCTYPE html>
<html lang="en">
<head>
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
	<meta charset="UTF-8">
	<title>Flagship Video Demo</title>
	<!-- Dependencies -->

	<script src="../ableplayer/thirdparty/modernizr.custom.js"></script>
	<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
	<script src="../ableplayer/thirdparty/js.cookie.js"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
	<link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
	<!-- Able Player JavaScript -->
	<script src="../ableplayer/build/ableplayer.js"></script>
	<script src='../js/S3FileGen.js'></script>
	<!-- Able Player CSS -->
	<link rel="stylesheet" href="../ableplayer/build/ableplayer.css" type="text/css"/>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>

	<?php
	  include "../inc/db_pdo.php";
    include "../inc/dump.php";
    include "../inc/sqlFunctions.php";
		$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
		session_start();
		$user = getUser($pdo,$_SESSION['username']);
		$videoId = ($_GET['v']) ? $_GET['v'] : 86;
		if ($_POST['saveCaptions']) {
			include "./saveCaptionEdits.php";
			$data = json_decode($_POST['captionData']);
			writeVTTFile($videoId,$data,$_POST['captionLanguage']);
			if ($_POST['transcript_final']) {
				updatePresentationStatus($videoId,'transcript_final');
			}
			if ($_POST['translation_final']) {
				updatePresentationStatus($videoId,'translation_final');
			}
		}
		if ($_POST['translateCaptions']) {
			include "../upload/php-s3-server-master/translateCaptions.php";
			translateVTTFile($videoId,);
			updatePresentationStatus($videoId,'translation_raw');
		}
		$presentationData = getVideos($videoId,'id');
		$allTracks = 'linguistic,professional,cutural';
		$includeTracks = ($_GET['t']) ? $_GET['t'] : $allTracks;
		$editCaptions = ($_GET['cm'] == 'edit' && $_POST['saveCaptions'] != 1)? true : false;
		if ($editCaptions) {
			$editCaptionTag = 'editCaptions';
			echo("<script src='../js/captionEditor.js'></script>");
			echo("<link rel='stylesheet' href='../css/captionEditor.css' type='text/css'/>");
			$transcriptHeight = '1000';
		}
		else {
			$descriptionTracks = "
		    <track id='descriptionTrack' kind='descriptions' src='./buildDescriptionTrack.php?a=".$presentationData[0]['annotations']."&t=$includeTracks&v=$videoId' srclang='en'/> 
			";
			$transcriptHeight = '400';
		}
		$isOwner = ($user->id == $presentationData[0]['user_id']) ? true : false;
		$isShowcase = ($presentationData[0]['is_showcase']==1) ? true : false;
		if($isOwner && !$isShowcase) {
			if ($editCaptions) {
				$editControls = "
					<div id = 'edit_controls' class = 'edit_controls'>
						<div class='form-check' style='display:inline;'>
						  <input type='checkbox' class='transcript_final' id='transcript_final' name='transcript_final'>
						  <label class='form-check-label' for='transcript_final'>Save as Final</label>
						</div>
						<a style='margin-left:20px;' class='btn btn-primary' href=\"javascript:saveCaptions();\">Save Captions</a>
					</div>
				";
			}
			else {
				if ($presentationData[0]['transcript_final'] == 1) {
					$translateButton = "
						<a style='display:inline;' class='btn btn-primary' href=\"javascript:translate();\">Generate Translation</a>
					";
				}
				$editControls = "
					<div id = 'edit_controls' class = 'edit_controls'>
						$translateButton
						<a class='btn btn-primary' href=\"javascript:editCaptions();\">Edit Captions</a>
					</div>
				";
			}
		}
	?>
	<!-- Style for this example only -->
	<style>
	  main {
	    position: relative;
	    width: 100%;
	    margin: 0;
	  }
	  #player {
	    float: left;
	    width: 480px;
	    margin: 0;
	  }
	  #transcript {
	    height:<?php echo($transcriptHeight); ?>.px
	    margin: 0;
	    padding-left: 500px;
	  }
	  #transcript div.able-desc {
	    width: 90%;
	  }

	</style>
</head>

	<body>
		<form method='post' id='saveCaptionForm' name='saveCaptionForm'>
		<main role="main">
			<?php echo($editControls); ?>
		  <div id="player">
			  <video id="video1" preload="auto" width="480" height="360" poster="../ableplayer/media/wwa.jpg" data-able-player data-transcript-div="transcript" playsinline <?php echo("$editCaptionTag"); ?> >
					<?php
						// put empty placeholder in for ableplayer onready function
						if ($presentationData[0]['transcript_raw'] || $presentationData[0]['translation_raw']) {
							echo("<track kind='captions' src='' srclang='' label=''/>");
						}
						if ($presentationData[0]['annotations'] != '') {
							echo("$descriptionTracks");
						}
					?>
			  </video>
			</div>
			<div id="transcript"></div>
		</main>
		<script>
			var GLOBAL_LANGUAGE;
	  	var hasTranscript = '<?php echo($presentationData[0]['transcript_raw']); ?>';
	  	var hasTranslation = '<?php echo($presentationData[0]['translation_raw']); ?>';
	  	var annotations = '<?php echo($presentationData[0]['annotations']); ?>';
			var videoFile = generateFile('video','<?php echo($_GET['v']); ?>','<?php echo($presentationData[0]['extension']); ?>','');
			var showTranscriptArea = false;
			if (hasTranscript) {
				var transcriptFile = generateFile('transcript','<?php echo($_GET['v']); ?>','vtt','<?php echo($presentationData[0]['language']); ?>');
				showTranscriptArea = true;
			}
			if (hasTranslation) {
				var translationFile = generateFile('translation','<?php echo($_GET['v']); ?>','vtt','<?php echo($presentationData[0]['language']); ?>');
				showTranscriptArea = true;
			}
			// center the player
			if (!showTranscriptArea) {
				$('#player').css('float','none');
				$('#player').css('margin','auto');
			}	
			function editCaptions() {
				window.top.location.href = "./index.php?v=<?php echo($_GET['v']); ?>&cm=edit";  // reference parent
			}	
			function saveCaptions() {
				var i=0;
				var data = [];
				$('.captionEditInput').each(function() {
					var text = $(this).val();
					var startTimeMatch = $('#st_'+i).html().match(/(\d{2}\:\d{2})$/);
					var startTime = startTimeMatch[1];
					var endTimeMatch = $('#et_'+i).html().match(/(\d{2}\:\d{2})$/);
					var endTime = endTimeMatch[1];
					data.push({
		        start: startTime,
		        end: endTime,
		        text:text
		      });
					i++;
				});
				$('#captionData').val(JSON.stringify(data));
				$('#captionLanguage').val(GLOBAL_LANGUAGE);
				$('#saveCaptions').val(1);
				$('#saveCaptionForm').submit();
			}		
			function translate() {
				$('#translateCaptions').val(1);
				$('#saveCaptionForm').submit();
			}
		</script>
			<input type='hidden' id='saveCaptions' name='saveCaptions' value = 0> 
			<input type='hidden' id='captionData' name='captionData' value = ''>
			<input type='hidden' id='captionLanguage' name='captionLanguage' value = ''>
			<input type='hidden' id='translateCaptions' name='translateCaptions' value = 0> 
		</form>
	</body>
</html>
