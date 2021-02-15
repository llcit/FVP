<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        include "./inc/db_pdo.php";
        include "./inc/dump.php";
        include "./inc/sqlFunctions.php";
        include "./inc/htmlFunctions.php";
        $SETTINGS = parse_ini_file(__DIR__."/inc/settings.ini");
				$pageTitle = "Flagship Video Home";
				$subTitle = "Main Menu";
				$titleText = "Select one of the links below.";
				session_start();
				if (!isset($_SESSION['username'])) { 
			    $role = 'anonymous'; 
			  } 
			  else {
			  	$user = getUser($_SESSION['username']);
			  	$role =  $user->role;
			  }
        $pageContent = writeNavLinks($role,'body');
      ?>
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>
    </head>
    <body>
      <div class="panel panel-default">
        <?php 
          $header = writePageHeader($SETTINGS['base_url'],$user,$pageTitle);
          echo($header); 
        ?>
        <div class="panel-body" style='margin-top:30px;'>
          <div class="container" >
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
        </div>
        <div class="footer">
          <p> </p>
        </div>
      </div>
    </body>
</html>
