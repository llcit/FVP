<!DOCTYPE html>
<html lang="en">
<head>
<?php
	include_once("../inc/dump.php");
	include_once("../inc/db.php");
	include_once("../inc/sqlFunctions.php");
	$videoId = ($_GET['v']) ? $_GET['v'] : 204;
	$showcaseVideos = getShowcaseVideos();		  
	$language = "";				  
	$userSelect = buildUserSelect($showcaseVideos,$videoId);
	$allTracks = ['linguistic','professional','cultural'];
	$includeTracks = ($_GET['t']) ? $_GET['t'] : $allTracks;
	$trackSelect = buildTrackSelect($allTracks,$includeTracks);
	if ($audioDescription != '') {
		$audioDescription = "
			<tr>
				<td colspan=5 align=left>
					<div class='audioDescription'>
						<h3>About this Speaker:</h3>
						$audioDescription
					</div>
				</td>
			</tr>
		";
	}
	function buildUserSelect($showcaseVideos,$videoId) {
		global $language,$audioDescription;
		$isSelected[$videoId] = " SELECTED";
		$userSelect = "<select id='v' name='v' class='selectpicker fv_block' onChange='updateUI();'>";
		$userSelect .= "<option value='204'". $isSelected['204'].">About the Flagship Video Project</option>";
		foreach($showcaseVideos as $video) {
			$progYrs = preg_replace("/(A|C)Y\ /","",$video['progYrs']);
			$userSelect .= "<option value='".$video['id']."'". $isSelected[$video['id']].">".$video['last_name']." (".$video['language'].", " .$progYrs. ")</option>";
			if ($video['id'] == $videoId) {
				$language = $video['language'];	
				$audioDescription = $video['description'];
			}
		}
		$userSelect .= "</select>";
		return $userSelect;
	}
	function buildTrackSelect($allTracks,$includeTracks) {	
		$trackSelect = "<select id='t[]' name='t[]' class='selectpicker fv_block' multiple>";
		foreach($allTracks as $track) {
			$selected = (in_array($track,$includeTracks)) ? "selected='selected'" : "";
			$trackSelect .= "<option value='$track' $selected>".ucfirst($track)."</option>";
		}
		$trackSelect .= "</select>";
		return $trackSelect;
	}
	
	?>
<meta charset="UTF-8">
<title>Flagship Video Demo</title>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<?php 
	$captionMode = $_GET['cm'];
	if ($captionMode == 'edit'){
		$playerHeight = '1800px';
	}
	else {
		$playerHeight = '800px';
	}
?>
<!-- Style for this example only -->
<style>
	.footer {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100%;
    background-color: #181b26;
    color: white;
    text-align: center;
	}
  .controlWrapper {
	  width:100%;
  }
  .playerFrame {
	  width:100%;
	  height:<?php echo($playerHeight); ?>;
	  
	  margin-top:20px;
	  border:none;
	
  }
  .fv_block {
	  display:block !important;
	  width:250px !important;
  }
  .controlsTable td {
  padding:10px;	
  font-size:14px;
  font-weight:bold;
  }
  .fv_heading {
	  background-color: #181b26 !important;
	  color:#dfd8c9 !important;
	  font-size:28px !important;
	  margin:0px !important;
	  padding:5px 10px !important; 
  }
</style>

<!-- Dependencies -->
<script src="../ableplayer/thirdparty/modernizr.custom.js"></script>
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

</head>

<body>

    <div class="panel panel-default">
    	<div class="panel-heading fv_heading">
    		<img src='../img/logo_lf.png'>
    		&nbsp;&nbsp;&nbsp;Flagship Video Showcase 
    		<span class='pull-right'>
  				<img src='../img/logo_ac.png'>
  			</span>
    	</div>
    	<div class="panel-body">
			<div class="controlWrapper">
				<form id='userControls'>
					<table class='controlsTable'>
						<tr>
							<td>
								Select Video Presentation: 
							</td>
							<td>
								<?php
								echo($userSelect);
								?>
							</td>
							<td>
								Select Description Tracks: 
							</td>
							<td>
								<?php
								echo($trackSelect);
								?>
							</td>
							<td>
								<button type="button" class="btn btn-primary" id="update" name="update" onclick="updateUI()">Update</button>
							</td>
						</tr>

						<?php echo($audioDescription);?>
					</table>
			</div>
			<iframe class='playerFrame' src='./player.php?v=<?php echo($_GET['v']); ?>&t=<?php echo(implode(',',$includeTracks)); ?>&l=<?php echo($language);?>&cm=<?php echo($captionMode);?>' allowfullscreen>
			</iframe>
    	</div>

<!-- to write transcript to an external div, pass id of an empty div via data-transcript-div -->

<script language='javascript'>
		function updateUI() {
			$("#userControls").submit();
		}
</script>
		<div class="footer">
		  <p> </p>
		</div>
</body>
</html>
