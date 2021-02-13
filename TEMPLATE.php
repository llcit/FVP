<!DOCTYPE html>
<html lang="en">
    <head>
      <style>
#im {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-image: url("path/to/img");
    background-repeat: no-repeat;
    background-size: contain;
}
      </style>
      <?php
        include "./inc/db_pdo.php";
        include "./inc/dump.php";
        include "./inc/sqlFunctions.php";
        include "./inc/htmlFunctions.php";
        $SETTINGS = parse_ini_file(__DIR__."/inc/settings.ini");
				$pageTitle = "Flagship Video Project";
				$subTitle = "[SUBTITLE]";
				$titleText = "[SOME WORDS]";
				session_start();
				if (!isset($_SESSION['username'])) { 
			    exit(header("location:./login.php"));
			  } 
			  else {
			  	$user = getUser($_SESSION['username']);
          $navLinks = writeNavLinks($user->role,'header');
			  	$userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
			  }
        if ($user) {
          $welcomeMsg = "
            $userName 
            <a href='".$SETTINGS['base_url']."/logout.php' class='btn btn-xs btn-icon btn-danger'>
              <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
            </a>
          ";
        }

       	$pageContent = "
       		[PAGE CONTENT]
       	";
      ?>
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
      <!-- Able Player CSS -->
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
      <script>

      </script>
    </head>
    <body>
      <div class="panel panel-default">
        <div class="panel-heading fv_heading">
          <img src='./img/logo_lf.png' class='logo'>
          <span class='pageTitle'>
          		<?php echo($pageTitle); ?>
          </span>
          <span class='pull-right'>
            <img src='./img/logo_ac.png' class='logo'>
          </span>
        </div>
        <div class='fv_subHeader'>
          <?php echo($navLinks); ?>
          <?php echo($welcomeMsg); ?>
        </div>
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
    </body>
</html>
