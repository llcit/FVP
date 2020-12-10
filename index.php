<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        include "./inc/db_pdo.php";
        include "./inc/dump.php";
        include "./inc/sqlFunctions.php";
				$pageTitle = "Flagship Video Home";
				$subTitle = "Main Menu";
				$titleText = "Select one of the links below.";
				session_start();
				if (!isset($_SESSION['username'])) { 
			    $role = 'anonymous'; 
			  } 
			  else {
			  	$user = getUser($pdo,$_SESSION['username']);
			  	$role =  $user->roles;
			  	$userName = $user->first_name . " " . $user->last_name;
			  }
			  $links = [
			  	['label'=>'Login','href'=>'./login.php'],
			  	['label'=> $userName.'\'s Videos','href'=>'./player/','req'=>['student']],
          ['label'=>'Upload Video','href'=>'./upload/','req'=>['student','staff','admin']],
          ['label'=>'Manage Users and Events','href'=>'./manage/','req'=>['staff','admin']],
			  	['label'=>'Video Showcase','href'=>'./player/'],
			  	['label'=>'Video Archive','href'=>'./archive/','req'=>['staff','admin']],
			  	['label'=>'About This Site','href'=>'./about.php'],

			  	
			  ];
			  $linkList = "
			  	<ul class='linkList'>
			  ";
			  foreach($links as $link) {
			  	if (!$link['req'] || in_array($role,$link['req'])) {
			  		$linkList .= "
			  			<li><a href='".$link['href']."'>".$link['label']."</a></li>
			  		";
			  	}
			  }
       	$pageContent = "
       		<div class='fv_pageContent'>
       			$linkList
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
        <div class='fv_subHeader'>
          Welcome <?php echo($userName); ?>!
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
