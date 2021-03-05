<!DOCTYPE html>
<html lang="en">
    <head>

      <?php
        include_once("./inc/db_pdo.php");
        include_once("./inc/dump.php");
        include_once("./inc/sqlFunctions.php");
        include_once("./inc/htmlFunctions.php");
        $SETTINGS = parse_ini_file(__DIR__."/inc/settings.ini");
      ?>
      <script>
        // set for S3FileGen
        var base_url = '<?php echo($SETTINGS['base_url']); ?>';
      </script>
      <script src='./js/S3FileGen.js'></script>
      <?php
        $pageTitle = "Flagship Video Project";
        $subTitle = "Your Videos";
        $titleText = "Click a video from the list below to play it or edit captions.  You may share the public link with anyone you would like to see the video.";
        if ($_POST['deleteVideo'] > 0) {
          include_once("./inc/S3DeleteObject.php");
          deleteObject($_POST['deleteVideo']);
        }
        session_start();
        if (!isset($_SESSION['username'])) { 
          exit(header("location:./login.php"));
        } 
        else {
          if ($_GET['uname']) {
            $userName = $_GET['uname'];
          }
          else {
            $userName = $_SESSION['username']; 
            $displayUserName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
            $navLinks = writeNavLinks($user->role,'header');
          }
          $user = getUser($userName);
        }
        
        if ($displayUserName) {
          $welcomeMsg = "
            $displayUserName 
            <a href='".$SETTINGS['base_url']."/logout.php' class='btn btn-xs btn-icon btn-danger'>
              <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
            </a>
          ";
        }
        $userVideos = getVideos($user->id,'user_id');
        if(!$userVideos) {
          $pageContent = "
                <div class='msg neutral'>
                  You do not have any videos saved yet.
                  <div style='width:100%; text-align:center;margin-top:30px;'>
                    <a href='./upload/'> 
                      Upload a video now.
                    </a>
                  </div>
                </div>
          ";
        }
        else {
           $pageContent = buildVideoList($userVideos,true);
        }
      ?>
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>
    </head>
    <body>
      <?php
        $header = writePageHeader($SETTINGS['base_url'],$user,$pageTitle);
        echo($header); 
      ?>
        <form method="post" action="">
          <div class="container">
             <div class="row fv_main">
                <div class="card fv_card">
                    <div class="card-body fv_card_body" style='border-bottom:solid 1px gray;'>
                       <h2 class="card-title"><?php echo($subTitle); ?></h2>
                       <p class="card-text"><?php echo($titleText); ?></p>
                    </div>
                    <div class='fv_pageContent'>
                      <?php echo($pageContent); ?>
                    </div>
                </div>

              </div>
          </div>
        </form>
        <div class="footer">
          <p> </p>
        </div>
      </div>
      <form id='deleteForm' name='deleteForm' method='post'>
        <input type='hidden' id='deleteVideo' name='deleteVideo' value='0'>
      </form>
      <script>
        $( document ).ready(function() {
          $('.videoPanel').each(function() {
            $(this).click(function(){ 
              playVideo($(this).attr('id'),false)
            });
          });
        });
      </script>
      <script src='./js/main.js'></script>
    </body>
</html>
