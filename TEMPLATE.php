<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        include "./inc/db_pdo.php";
        include "./inc/dump.php";
        include "./inc/sqlFunctions.php";
				$pageTitle = "Flagship Video Home";
				$subTitle = "Main Menu";
				$titleText = "Select one of the links below...";
				session_start();
				if (!isset($_SESSION['username'])) { 
			    $role = 'anonymous'; 
			  } 
			  else {
			  	$user = getUser($pdo,$_SESSION['username']);
			  	$role =  $user->roles;
			  	$userName = $user->first_name . " " . $user->last_name;
			  }

       	$pageContent = "
       		<div class='fv_pageContent'>
       			Welcome $userName!
       		</div>";
      ?>
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
      <!-- Able Player CSS -->
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>

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
        <form method="post" action="">
          <div class="container">
             <div class="row fv_main">
                <div class="card fv_card">
                    <div class="card-body fv_card_body" style='border-bottom:solid 1px gray;'>
                       <h2 class="card-title"><?php echo($subTitle); ?></h2>
                       <p class="card-text"><?php echo($titleText); ?></p>
                    </div>
                    <?php echo($pageContent); ?>
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
