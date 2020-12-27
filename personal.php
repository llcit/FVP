<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        include "./inc/db_pdo.php";
        include "./inc/dump.php";
        include "./inc/sqlFunctions.php";
				$pageTitle = "Flagship Video Project";
				$subTitle = "Your Videos";
				$titleText = "";
				session_start();
				if (!isset($_SESSION['username'])) { 
			    exit(header("location:./login.php"));
			  } 
			  else {
			  	$user = getUser($pdo,$_SESSION['username']);
			  	$role =  $user->roles;
			  	$userName = $user->first_name . " " . $user->last_name;
			  }
        if ($user) {
          $welcomeMsg = "
            $userName 
            <a href='./logout.php' class='btn btn-icon btn-danger'>
              <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
            </a>
          ";
        }
       	$pageContent = getUserVideos($user->id);
        dump($pageContent);
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
          <img src='./img/logo_lf.png'>
          <span class='pageTitle'>
          		<?php echo($pageTitle); ?>
          </span>
          <span class='pull-right'>
            <img src='./img/logo_ac.png'>
          </span>
        </div>
        <div class='fv_subHeader'>
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
