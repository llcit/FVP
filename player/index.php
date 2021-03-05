<!DOCTYPE html>
<html lang="en">
<head>
<?php
	include_once("../inc/dump.php");
	include_once("../inc/db_pdo.php");
	include_once("../inc/sqlFunctions.php");
	include_once("../inc/htmlFunctions.php");
	$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
	session_start();
	$videoId = ($_GET['v']) ? $_GET['v'] : 187;
	if (!isset($_SESSION['username'])) { 
    $role = 'anonymous'; 
  } 
  else {
  	$user = getUser($_SESSION['username']);
  	$role =  $user->role;
  }
	if ($_GET['sc']) {
		$filters = [
			'is_showcase'=>['1']
		];
		$showcaseVideos = getVideos(null,null,$filters);
		$userSelect = buildUserSelect($showcaseVideos,$videoId);
		$allTracks = ['linguistic','professional','cultural'];
		$includeTracks = ($_GET['t']) ? $_GET['t'] : $allTracks;
		$trackSelect = buildTrackSelect($allTracks,$includeTracks);
		for ($i=0;$i<count($showcaseVideos);$i++) {
			if ($showcaseVideos[$i]['id'] == $_GET['v']) {
				$audioDescription = "
					<tr>
						<td colspan=5 align=left>
							<div class='audioDescription'>
								<h3>About this Speaker:</h3>
								".$showcaseVideos[$i]['description']."
							</div>
						</td>
					</tr>
				";
			}
		}
		$userControls = "
					<table class='controlsTable'>
						<tr>
							<td>
								Presentation: 
							</td>
							<td>
								$userSelect
							</td>
							<td>
								Description Tracks: 
							</td>
							<td>
								$trackSelect
							</td>
							<td>
								<button type='button' class='btn btn-primary' id='update' name='update' onclick='updateUI()'>Update</button>
							</td>
						</tr>
						$audioDescription
					</table>
					<input type = hidden id='sc' name='sc' value ='".$_GET['sc']."'> 
		";
	}
	else {
		$includeTracks = [];
	}
	function buildUserSelect($showcaseVideos,$videoId) {
		global $audioDescription;
		$isSelected[$videoId] = " SELECTED";
		$userSelect = "<select id='v' name='v' class='selectpicker fv_block fv_showcase_select' onChange='updateUI();'>";
		foreach($showcaseVideos as $video) {
			$progYrs = preg_replace("/(A|C)Y\ /","",$video['progYrs']);
			$userSelect .= "<option value='".$video['id']."'". $isSelected[$video['id']].">".$video['last_name']." (".$video['language'].", " .$progYrs. ")</option>";
		}
		$userSelect .= "</select>";
		return $userSelect;
	}
	function buildTrackSelect($allTracks,$includeTracks) {	
		$trackSelect = "<select id='t[]' name='t[]' class='selectpicker fv_block fv_showcase_select' multiple>";
		foreach($allTracks as $track) {
			$selected = (in_array($track,$includeTracks)) ? "selected='selected'" : "";
			$trackSelect .= "<option value='$track' $selected>".ucfirst($track)."</option>";
		}
		$trackSelect .= "</select>";
		return $trackSelect;
	}
	
	?>
<meta charset="UTF-8">
<title>Flagship Video Project</title>
<?php 
	$captionMode = $_GET['cm'];
	if ($captionMode == 'edit'){
		$playerClass = 'playerFrame_tall';
	}
	else {
		$playerClass = 'playerFrame_short';
	}
?>
<link rel="stylesheet" href="../css/main.css" type="text/css"/>

</head>

<body>
  <div class="panel panel-default">
    <?php 
      $header = writePageHeader($SETTINGS['base_url'],$user,$pageTitle);
      echo($header); 
    ?>
  	<div class="panel-body">
		<div class="controlWrapper">
			<form id='userControls'>
				<?php echo($userControls); ?>
			</form>
		</div>
		<iframe class='<?php echo($playerClass); ?>' src='./player.php?v=<?php echo($videoId); ?>&t=<?php echo(implode(',',$includeTracks)); ?>&cm=<?php echo($captionMode);?>&language=<?php echo($_GET['language']); ?>&ac=<?php echo($_GET['ac']); ?>' allowfullscreen>
		</iframe>
  	</div>

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
