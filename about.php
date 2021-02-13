<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        include "./inc/db_pdo.php";
        include "./inc/dump.php";
        include "./inc/sqlFunctions.php";
        include "./inc/htmlFunctions.php";
        $SETTINGS = parse_ini_file(__DIR__."/inc/settings.ini");
				$pageTitle = "Flagship Video Project";
				$subTitle = "About the Flagship Video Project";
				session_start();
				if (!isset($_SESSION['username'])) { 
			    exit(header("location:./login.php"));
			  } 
			  else {
			  	$user = getUser($_SESSION['username']);
          $navLinks = writeNavLinks($user->role,'header');
			  	$userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
			  }

       	$pageContent = "
          <p>
            Launched in 2018, the video project is now an integrated component in the Arabic, Chinese, Portuguese and Russian Overseas Capstone Programs.   Over the course of the second semester, capstone students research and prepare a 20-minute oral presentation on a topic of professional or academic interest for delivery in a conference setting (usually the focus is the internship).   In preparation for the project, Capstone students enroll in a professional writing and/or public speaking course, select a topic, develop an outline, a workplan for collecting data/information for their presentation, prepare successive  drafts, and, of course, rehearse their presentation with the help and supervision of their instructors and language partners.  Host country videographers follow Flagship-specified guidelines for video and audio capture and basic editing of these projects.  All presentations are evaluated by capstone faculty and count for a portion of the student’s final grade for the 2nd semester.
          </p>
          <p>
            Apart from providing a momento of their Flagship experience (some students have compared the video presentation to a senior recital recording), the videos provide a rich documentation of the range of each student’s linguistic, cultural, intercultural, professional and regional/area competencies, captured in a public setting in real time, and in a form which can be shared by Flagship alums with prospective employers, reviewed for curricular and faculty development purposes, analyzed by second language researchers, and made available to potential donors and policy makers.    
          </p>
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
