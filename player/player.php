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

	<!-- Able Player CSS -->
	<link rel="stylesheet" href="../ableplayer/build/ableplayer.css" type="text/css"/>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<!-- Able Player JavaScript -->
	<script src="../ableplayer/build/ableplayer.js"></script>
	<script src='../js/S3FileGen.js'></script>
	<script>
		var filesFromS3 = generateFiles([
				{'type':'video','id': '<?php echo($_GET['v']); ?>','ext': '<?php echo("mp4"); ?>'},
				{'type':'transcript','id': '<?php echo($_GET['v']); ?>','ext': 'vtt'},
				{'type':'translation','id': '<?php echo($_GET['v']); ?>','ext': 'vtt'}
			]
		);
		console.log('files found:',filesFromS3);
	</script>
	<?php
		$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
		$videoId = ($_GET['v']) ? $_GET['v'] : 86;
		$allTracks = 'linguistic,professional,cutural';
		$includeTracks = ($_GET['t']) ? $_GET['t'] : $allTracks;
		$language = $_GET['l'];
		$la = strtolower(substr($language,0,2));
		if ($_GET['cm'] == 'edit') {
			$editCaptions = 'editCaptions';
			echo("<script src='../js/captionEditor.js'></script>");
			echo("<link rel='stylesheet' href='../css/captionEditor.css' type='text/css'/>");
			$transcriptHeight = '1000';
		}
		else {
			$descriptionTracks = "
		    <track kind='descriptions' src='./buildDescriptionTrack.php?t=$includeTracks&v=$videoId' srclang='en'/> 
			";
			$transcriptHeight = '400';
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
	<main role="main">
	  <div id="player">
		  <video id="video1" preload="auto" width="480" height="360" poster="../ableplayer/media/wwa.jpg" data-able-player data-transcript-div="transcript" playsinline <?php echo("$editCaptions"); ?> >
				<source type="video/mp4" id="video">
		    <track kind="captions" src="../assets/transcripts/<?php echo($videoId); ?>.vtt" srclang="<?php echo($la); ?>" label="<?php echo($language); ?>"/>
			  <track kind="captions" src="../assets/translations/<?php echo($videoId); ?>.vtt" srclang="en" label="English"/> -->
			  	<?php //echo($descriptionTracks); ?> 
		  </video>
		</div>
		<div id="transcript"></div>
	</main>
<!-- to write transcript to an external div, pass id of an empty div via data-transcript-div -->

</body>
</html>
