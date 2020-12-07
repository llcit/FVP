<!DOCTYPE html>
<html lang="en">
<head>
	<?php
		$videoId = ($_GET['v']) ? $_GET['v'] : 86;
	?>
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
<link rel="stylesheet" href="../ableplayer/build/ableplayer_archive.css" type="text/css"/>

<!-- Able Player JavaScript -->
<script src="../ableplayer/build/ableplayer.js"></script>

<!-- Style for this example only -->
<style>
  main {
    position: relative;
    width: 100%;
    margin: 0;
  }
  #player {
    width: 95%;
    text-align:center;
    -border:solid 2px;
    margin-left: auto;
    margin-right: auto;
  }
</style>
</head>

<body>

	<main role="main">
	  <div id="player">
		  <video id="video1" preload="auto" width=720 height="360" poster="../ableplayer/media/wwa.jpg" data-able-player data-transcript-div="transcript" playsinline>
				<!--                           
        <source type="video/mp4" src="../assets/videos/<?php echo($videoId); ?>.mp4"> 
        -->
        <source type="video/mp4" src="https://flagship-video-project.s3.amazonaws.com/187.mp4">
		  </video>
		</div>
	</main>
<!-- to write transcript to an external div, pass id of an empty div via data-transcript-div -->

</body>
</html>
