<!DOCTYPE html>
<html lang="en">
<head>
<?php
	include_once("../inc/dump.php");
	include_once("../inc/db_pdo.php");
	include_once("../inc/sqlFunctions.php");
	include_once("../inc/htmlFunctions.php");
	include_once("../inc/htmlFunctions.php");
	$SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
	session_start();
	if ($_POST['deleteVideo'] > 0) {
		include_once("../inc/S3DeleteObject.php");
		deleteObject($_POST['deleteVideo']);
	}
	if (!isset($_SESSION['username'])) { 
    header('Location: ../login.php'); 
  } 
  $user = getUser($pdo,$_SESSION['username']);
  $navLinks = writeNavLinks($user->role,'header');
  $userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
  $welcomeMsg = "
    $userName 
    <a href='".$SETTINGS['base_url']."/logout.php' class='btn btn-xs btn-icon btn-danger'>
      <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
    </a>
  ";
    if ($user->role == 'admin' || $user->role == 'staff') {
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
		$videoData = getVideos(null,null,$filters);
		$videoList =buildVideoList($videoData);
		$filterPulldowns = buildPullDowns($filters);
		$pageContent = "
			$filterPulldowns
			<div class = 'videoListWrapper'>
				$videoList
			</div>
			";

		/* ---------- /MAIN ---------- */
	}
	else {
    $pageContent = "
      <div class = 'msg error' style='margin-top:30px;'>
        Permission denied! You must be staff or admin to access this page.
      </div>
      <p style='width:100%;text-align:center;margin-top:30px;'>
        <a href='../index.php'>Retun to Home</a>
    ";
  }
	function buildPullDowns($filters){
		$fullList = [
			'programs' => getUniqueVals('programs','name'),
			'years' => getUniqueVals('programs','progYrs'),
			'locations'=>getUniqueVals('events','city'),
			'institutions'=>getUniqueVals('institutions','name'),
			'types'=>getUniqueVals('presentations','type'),
			'periods'=>getUniqueVals('events','phase')
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
		foreach($fullList as $k=>$values) {
			if ($i%3==0) {
				if ($i > 1) {
					$pullDowns .= "</div>";
				}
				$pullDowns .= "<div class='row fv_select_row'>";
			}
			$i++;
			$input = "<select id='".$k."[]' name='".$k."[]' class='selectpicker fv_select_wrapper' multiple>";
			foreach($values as $k2=>$val) {
				foreach($val as $key=>$value) {
					if ($filters[$key]) {
						$selected = (in_array($value,$filters[$key])) ? "selected='selected'" : "";
					}
					$input .= "<option value='$value' $selected>".ucfirst($value)."</option>";
				}
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
<link rel="stylesheet" href="../css/main.css" type="text/css"/>
<script>
	$( document ).ready(function() {
    $('.videoPanel').each(function() {
    	$(this).click(function(){ 
    		if (!deleteVideo) {
    			playVideo($(this).attr('id'),true);
    		}
    	});
    });
    // set for S3FileGen
  });
  var base_url = '<?php echo($SETTINGS['base_url']); ?>';
</script>
<script src='../js/S3FileGen.js'></script>
<script src='../js/main.js'></script>
</head>

<body>

<div class="panel panel-default">
	<div class="panel-heading fv_heading">
		<img src='../img/logo_lf.png'>
		&nbsp;&nbsp;&nbsp;Flagship Video Archive 
		<span class='pull-right'>
			<img src='../img/logo_ac.png'>
		</span>
	</div>
	<div class='fv_subHeader'>
		<?php echo($navLinks); ?>
    <?php echo($welcomeMsg); ?>
  </div>
	<div class="panel-body">
		<?php echo($pageContent); ?>
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
	<form id='deleteForm' name='deleteForm' method='post'>
		<input type='hidden' id='deleteVideo' name='deleteVideo' value='0'>
	</form>
</body>
</html>
