<!DOCTYPE html>
<html lang="en">
<head>
<?php
  include_once("./inc/dump.php");
  include_once("./inc/db_pdo.php");
  include_once("./inc/sqlFunctions.php");
  include_once("./inc/htmlFunctions.php");
  include_once("./inc/htmlFunctions.php");
  $SETTINGS = parse_ini_file(__DIR__."/./inc/settings.ini");
  session_start();
  if ($_POST['deleteVideo'] > 0) {
    include_once("./inc/S3DeleteObject.php");
    deleteObject($_POST['deleteVideo']);
  }
  if (!isset($_SESSION['username'])) { 
    header('Location: ./login.php'); 
  } 
  $user = getUser($_SESSION['username']);
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
        <a href='./index.php'>Retun to Home</a>
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
      <form id='userControls' method='post'>
      <div class='fv_selects_wrapper'>
      <h4 style='display:inline'>Filter Results: </h4>
        <span class='actionButtons clearfix'>
          <span id='updateFilters' name='updateFilters' class='float-right'>
            <button type='button' class='btn btn-primary fv_filterButton' id='clear' name='clear' onclick='clearFilters()'>Clear Filters</button>
            <button type='button' class='btn btn-primary fv_filterButton' id='update' name='update' onclick='updateUI()'>Update</button>        
          </span>
        </span>
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
      $input = "<select id='".$k."[]' name='".$k."[]' class='selectpicker' multiple data-width='100%'>";
      foreach($values as $k2=>$val) {
        foreach($val as $key=>$value) {
          if ($filters[$k]) {
            $selected = (in_array($value,$filters[$k])) ? "selected='selected'" : "";
          }
          $input .= "<option value='$value' $selected>".ucfirst($value)."</option>";
        }
      }
      $input .= "</select>";
      $pullDowns .= "
              <div class='fv_select col-sm-4'>
                <p class='fv_select_label'>".ucfirst($k).":</p>  
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
<link rel="stylesheet" href="./css/main.css" type="text/css"/>
<script>
  var base_url = '<?php echo($SETTINGS['base_url']); ?>';
</script>
<script src='./js/S3FileGen.js'></script>
<script src='./js/main.js'></script>
</head>
<body>
  <div class="panel panel-default">
    <?php 
      $header = writePageHeader($SETTINGS['base_url'],$user,$pageTitle);
      echo($header); 
    ?>
    <div class="panel-body">
      <?php echo($pageContent); ?>
    </div>
    <div class="footer">
      <p> </p>
    </div>
  </div>
  <script language='javascript'>
    function updateUI() {
      $("#userControls").submit();
    };

    function clearFilters() {
      window.location.href='./archive.php';
    }
    $( document ).ready(function() {
      $('.videoPanel').each(function() {
        $(this).click(function(){ 
          playVideo($(this).attr('id'),false);
        });
      });
    });
  </script>
  <form id='deleteForm' name='deleteForm' method='post'>
    <input type='hidden' id='deleteVideo' name='deleteVideo' value='0'>
  </form>
</body>
</html>
