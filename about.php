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
				if (isset($_SESSION['username'])) { 
			    $user = getUser($_SESSION['username']);
			  } 
       	$pageContent = "
          <p>
          Launched in 2018, the video project is now an integrated component of the Overseas Capstone Programs. Over the course of the second semester, Capstone students research and prepare 
          a 20-minute oral presentation on a topic of professional or academic interest for delivery in a conference setting (usually the focus is the internship). In preparation for the project, Capstone students enroll in a professional writing and/or public speaking course, select a topic, develop an outline, a work plan for collecting data/information for their presentation, prepare successive drafts, and, of course, rehearse their presentation with the help and supervision of their instructors and language partners.
          </p>
          <p>
          The videos provide a rich documentation of the range of each student’s linguistic, cultural, intercultural, professional and regional/area competencies, captured in a public setting in real time, and in a form which can be shared by Flagship alums with prospective employers, reviewed for curricular and faculty development purposes, analyzed by second language researchers, and made available to demonstrate the skills acquired by Flagship students during The Flagship program.
          </p>
          <p>
          The Language Flagship Video Project is sponsored by the Defense Language and National Security Education Office (DLNSEO) of the Department of Defense and administered by the Institute of International Education (IIE).
          </p>
       	";
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
        </div>
        <div class="footer">
          <p> </p>
        </div>
      </div>
    </body>
</html>
