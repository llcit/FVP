<!DOCTYPE html>
<html lang="en">
<head>
<?php

	include_once("../inc/dump.php");
	include_once("../inc/db.php");
	include_once("../inc/sqlFunctions.php");
	session_start();
	if (!isset($_SESSION['username'])) { 
    header('Location: ../login.php'); 
  } 
	/*  ------------ READ IN POST VALS ------------- */
	$filters = [
		'programs'=>$_POST['programs'],
		'years'=>$_POST['years'],
		'locations'=>$_POST['locations'],
		'institutions'=>$_POST['institutions'],
		'types'=>$_POST['types'],
		'periods'=>$_POST['periods'],
	];
	//vdump($filters);
	/*  ------------ /READ IN POST VALS ------------- */
	/* ---------- MAIN ---------- */
	$videoData = getVideos($filters);
	$videoList =buildVideoList($videoData);
	$filterPulldowns = buildPullDowns($filters);
	/* ---------- /MAIN ---------- */


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
				$videoList .= buildRow($video);
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
	function buildRow($video) {
		$row = "
							<div class='videoPanel col-sm-4' id='videoPanel_".$video['id']."' name='videoPanel_".$video['id']."'>
								<table>
									<tr>
										<td>
											<div class = 'thumbWrapper'>
												<img src = '../assets/thumbs/".$video['id'].".jpg' class='thumb'>
											</div>
										</td>
										<td>
											<div class = 'videoDetails'>
											<p class='studentName'>".$video['first_name']." ".$video['last_name']."</p>
											<p class='details'>".$video['progYrs']."</p>
											<p class='details'>".$video['city'].", ".$video['country']."</p>
											<p class='details'>".$video['type']."</p>
											<p class='details'>".$video['phase']."</p>
											</div>
											<input type=hidden id='videoData_".$video['id']."' name='videoData_".$video['id']."'
											value='".json_encode($video). "'>
										</td>
									</tr>
								</table>
							</div>
					 ";
		return $row;
	}
	function buildPullDowns($filters){
		$fullList = [
			'programs' => getUniqueVals('programs','name'),
			'years' => getUniqueVals('programs','progYrs'),
			'locations'=>getUniqueVals('events','city'),
			'institutions'=>getUniqueVals('institutions','name'),
			'types'=>getUniqueVals('presentations','type'),
			'periods'=>getUniqueVals('presentations','phase')
		];
		$pullDowns = "
			<div class='fv_selects_wrapper'>
				<form id='userControls' method='post'>
				<div class='actionButtons pull-right'>
					<div id='updateFilters' name='updateFilters'>
						<button type='button' class='btn btn-primary fv_filterButton' id='update' name='update' onclick='updateUI()'>Update</button>	
						<br><br><br>				
						<button type='button' class='btn btn-primary fv_filterButton' id='clear' name='clear' onclick='clearFilters()'>Clear Filters</button>
					</div>
				</div>
		";
		$i=0;
		foreach($fullList as $key=>$values) {
			if ($i%3==0) {
				if ($i > 1) {
					$pullDowns .= "</div>";
				}
				$pullDowns .= "<div class='row fv_select_row'>";
			}
			$i++;
			$input = "<select id='".$key."[]' name='".$key."[]' class='selectpicker fv_select_wrapper' multiple>";
			foreach($values as $value) {
				if ($filters[$key]) {
					$selected = (in_array($value,$filters[$key])) ? "selected='selected'" : "";
				}
				$input .= "<option value='$value' $selected>".ucfirst($value)."</option>";
			}
			$input .= "</select>";
			$pullDowns .= "
							<div class='fv_select col-sm-4'>
								<p class='fv_select_label'>".ucfirst($key).":</p>  
								$input
							</div>
		";
		}	
		$pullDowns .= "
						</div>
				</form>
			</div>
		";

		return $pullDowns;
	}

	?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Flagship Video Archive</title>

<!-- Dependencies -->
<script src="../ableplayer/thirdparty/modernizr.custom.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="../ableplayer/thirdparty/js.cookie.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>

<!-- Able Player CSS -->
<link rel="stylesheet" href="./archive.css" type="text/css"/>
<script>
	$( document ).ready(function() {
    $('.videoPanel').each(function() {
    	$(this).click(function(){ 
    		playVideo($(this).attr('id'))
    	});
    })
	$('#playerModal').on('hidden.bs.modal', function (e) {
  	$('#playerFrame').attr('src', '');
		});
	});
	function playVideo(element_id) {
		var id_parts = element_id.match(/(.+)\_(.+)/);
	  var video_id = id_parts[2];
	  var url = './player.php?v=' + video_id;
	  writeDetails($('#videoData_' + video_id).val());
	  
	  $('#playerFrame').attr('src', url);
	  $('#playerModal').modal('show');
	  
	}
	function writeDetails(data_str) {
		var data = $.parseJSON(data_str);
		console.log(data);
		var details = [];
		details.push('<li>' + data.first_name + ' ' + data.last_name + '</li>');
		details.push('<li>Program: ' + data.program + '</li>');
		details.push('<li>Program Year: ' + data.progYrs + '</li>');
		details.push('<li>Program Period: ' + data.phase + '</li>');
		details.push('<li>Domestic Institution: ' + data.institution + '</li>');
		details.push('<li>Overseas Location: ' + data.city + ', ' + data.country + '</li>');
		details.push('<li>Performance Type: ' + data.type + '</li>');
		$('.modal-title').empty();
		$('.modal-title').append(details.join(''));
	}
</script>
</head>

<body>

<!-- ----------- PLAYER MODAL ----------------- -->
<div class="container">
  <div class="modal fade" id="playerModal" role="dialog">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal">&times;</button>
          <ul class="modal-title"></ul>
        </div>
        <div class="modal-body">
          <div id='player'> 
          	<iframe class='playerFrame' id='playerFrame' name='playerFrame' src='./player.php?v=1' allowfullscreen>
						</iframe>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- ----------- /PLAYER MODAL ----------------- -->
<div class="panel panel-default">
	<div class="panel-heading fv_heading">
		<img src='../player/logo_lf.png'>
		&nbsp;&nbsp;&nbsp;Flagship Video Archive 
		<span class='pull-right'>
			<img src='../player/logo_ac.png'>
		</span>
	</div>
	<div class="panel-body">
		<?php echo($filterPulldowns); ?>
		<div class = 'videoListWrapper'>
			<?php echo("$videoList"); ?>
		</div>
	</div>

	<!-- to write transcript to an external div, pass id of an empty div via data-transcript-div -->

	<script language='javascript'>
			function updateUI() {
				$("#userControls").submit();
			};

			function clearFilters() {
				window.location.href='index.php';
			}
	</script>
	<div class="footer">
	  <p> </p>
	</div>
</body>
</html>
