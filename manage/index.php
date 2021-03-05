<!DOCTYPE html>
<html lang="en">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta charset="utf-8"/>
    <?php
      $context = ($_POST['context']) ? $_POST['context'] : 'event';
      $contextLabel = ucfirst($context);
      $post_id = $_POST['post_id'];
      // highight active pill for context
      $active = [];
      $active[$context] = 'active';
      include_once "../inc/db_pdo.php";
      include_once "../inc/dump.php";
      include_once "../inc/sqlFunctions.php";
      include_once "../inc/htmlFunctions.php";
      include_once "./inc/".$context.".php";
      $SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
			
      if ($context == 'roster') {
        $student_program_id = ($_POST['student_program_id']) ? $_POST['student_program_id'] : $_POST['post_id'];
        if ($_POST['save'] == 1) {
          $subTitle = "Auto-send Emails:";
          $titleText = "We attempted to send the following emails. 
            <a class='btn btn-primary' href='javascript:manageStudents($student_program_id);' style='display:inline;'> 
              <i class='fas fa-check'></i>
              Continue 
            </a>
          ";
        }
        else {
          $subTitle = "Preview Roster";
          $titleText = "The roster listed below is ready to be saved.  Please review the information and click save.  If you want the system to send an invite email automatically, check the box below. ";
        }
      }
      else {
        $subTitle = "Manage $contextLabel"."s";
        $titleText = "You may select an existing $context to edit or add a new $context. ";
      }
			
      if ($context == 'event') {
        $deleteMsg = " You may only delete an event if it  does not have any videos uploaded to it.";
      }
      else if ($context == 'program') {
        $deleteMsg = " You may only delete a program if it does not have any users affiliated with it.  To add/edit students or upload a student roster, click the box showing the number of students in it to the right of the program.";
      } 
      else if ($context == 'student') {
        $deleteMsg = " You may only delete a student if he/she does not have any saved videos.";
        $student_program_id = ($_POST['student_program_id']) ? $_POST['student_program_id'] : $_POST['post_id'];
      } 
      $titleText .= $deleteMsg; 
			session_start();
			if (!isset($_SESSION['username'])) { 
		    exit(header("location:../login.php"));
		  } 
		  else {
        $user = getUser($_SESSION['username']);
        if ($_POST['manage'] == 1) {
          $pageContent = buildManager($_POST['post_id'],$_POST['student_program_id']);
          if ($_POST['post_id']) {
            $subTitle = "Edit Existing $contextLabel";
            $titleText = "You may change any of the details listed below and click the save button to save your changes.";
          }
          else {
            $subTitle = "Add New $contextLabel";
            $titleText = "Please provide all of the $context details listed below and click the save button to save your changes.";
          }
        }
        else {
          // generate roster preview after upload
          if ($context=='roster' && $_POST['save'] == 0) {
            saveTmpRoster();
          }
          if ($_POST['save'] == 1) {
            // prevent double save on refresh -- only save if cookie is true
            if ($_COOKIE['doSave']) {
              $response = save($_POST);
              if ($context!='roster') {
                if ($response == 'success') {
                  $msg = "
                    <div class='msg success'>
                      $contextLabel successfully saved!
                    </div>
                  ";
                }
                else {
                  $msg = "
                    <div class='msg error'>
                      There was a problem saving your $context!
                      <p>Error: $response</p>
                    </div>
                  ";
                }
              }
              else {
                $pageContent  = $response;
              }
              // prevent double save on refresh -- kill cookie after 1st save
              setcookie ("doSave", "", time() - 3600, "/");
              if ($context=='roster') {
                // return to student list for program
                if (!$_POST['auto_send']) {
                  $rosterRedirect = "manageStudents($student_program_id);";
                }
              }
              // return to student list for program
              if ($context=='student') {
               $_POST['post_id'] = $_POST['student_program_id'];
              }
            }
          }
          if ($_POST['remove'] == 1) {
            $response = remove($_POST['post_id']);
            if ($response == 'success') {
              $msg = "
                <div class='msg success'>
                  $contextLabel successfully deleted!
                </div>
              ";
            }
            else {
              $msg = "
                <div class='msg error'>
                  There was a problem deleting your $context!
                  <p>Error: $response</p>
                </div>
              ";
            }
            // return to student list for program
            if ($context=='student') {
             $_POST['post_id'] = $_POST['student_program_id'];
            }
          }
          if ($context!='roster' && ($_POST['send'] == 1 || $_POST['auto_send'])) {
            include_once "../inc/SESMailer.php";
            $emailVars = [
              'user_id' => $_POST['post_id']
            ];
            $msg = sendMail('Welcome',$emailVars);
            // return to student list for program
            if ($context=='student') {
             $_POST['post_id'] = $_POST['student_program_id'];
            }
          }
          // set post_id in student context as parentKey (program_id)
          if($context == 'student') {
            $rosterButtons = " 
              <span>
                <a class='btn btn-primary' href='javascript:downloadTemplate();' style='display:inline;'> 
                  <i class='fas fa-download'></i>
                  Download Template 
                </a>
                <input type='file' id='rosterFile' name='rosterFile' class='rosterFile'  accept='.csv'>
              </span>
              <span>
                <a class='btn btn-primary' style='margin-left:10px;margin-right:10px;display:inline;' href='javascript:importRoster();' > 
                  <i class='fas fa-file-import'></i>
                  Import Roster 
                </a>
              </span>
            ";
          }

          $existing = getExisting($student_program_id);
          $displayList = formatList($existing);
          if ($user->role == 'admin' || $user->role == 'staff') {
            if ($context == 'roster') {
              $contextHeader = "Your Roster";
              $action = 'save';
              $actionLabel = "Save This";
              $icon = "fa-save";
              $disabled = 'disabled';
              $autoSendInput = "
                <span>
                  <label for='auto_send' style='min-width:80px;'>Auto-send Invite Emails to All New Students:</label>
                  <input type='checkbox' class='checkbox' style='margin-right:40px;' id='auto_send' name='auto_send'>
                </span>
              ";
            }
            else{
              if ($context == 'student') {
                $program = getProgram($_POST['post_id']);
                if (!$program) {
                  header('Location: ./index.php');
                  exit;
                }
                $progQualifier = " for " . $program->name;
              }
              $contextHeader = "Existing ".$contextLabel."s" . $progQualifier;
              $action = 'manage';
              $actionLabel = "Add";
              $icon = "fa-plus-circle";
            }
            // do not render content when roster auto send email messages are displayed
            $renderContent = ($context == 'roster' && $_POST['auto_send']) ? false : true;
            if ($renderContent) {
              $pageContent = "
                <div class='panel'>
                  <div style='min-width:100%; border-bottom:solid 1px;padding-bottom:20px;margin-bottom:20px;'>
                    <h4 style='display:inline;'>
                      $contextHeader
                    </h4>
                  </div>
                  <div style='text-align:right;min-width:100%;overflow:none;white-space: nowrap;'>
                    $rosterButtons
                    $autoSendInput
                    <span>
                      <a class='btn btn-primary $disabled' href='javascript:$action();' id='actionButton' name='actionButton' style='display:inline;' 
                        data-toggle='tooltip' data-placement='top' title='$actionLabel $contextLabel'
                      > 
                        <i class='fa $icon' aria-hidden='true'></i>
                        $actionLabel $contextLabel 
                      </a>
                    </span>
                  </div>
                  $displayList
                </div>
              ";
            }
          }
          else {
            $pageContent = "
              <div class = 'msg error'>
                Permission denied! You must be staff or admin to access this page.
              </div>
              <p style='width:100%;text-align:center;margin-top:30px;'>
                <a href='../index.php'>Return to Home</a>
            ";
          }
        }
		  }
    ?>
    <link rel="stylesheet" href="../css/main.css" type="text/css"/>
    <script>
      var context = '<?php echo($context); ?>';
    </script>
  </head>
  <body>
    <div class="panel panel-default">
    <?php 
      $header = writePageHeader($SETTINGS['base_url'],$user,$pageTitle);
      echo($header); 
    ?>
      <form method="post" id='manageForm' action=''>
        <div class="container">
          <?php if($msg) echo("<div style='width:100%;margin-top:30px;'>$msg</div>"); ?>
          <ul class="nav nav-pills fv-nav-container">
            <li class="nav-item">
              <a class="nav-link <?php echo($active['event']); ?>" href="javascript:setContext('event');">Events</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo($active['program'] . $active['student'] . $active['roster']); ?>" href="javascript:setContext('program');">Programs & Students</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo($active['user']); ?>" href="javascript:setContext('user');">Admin Users</a>
            </li>
          </ul>
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
        <input type='hidden' name='post_id' id='post_id' value='<?php echo($post_id); ?>'>
        <input type='hidden' id='context' name='context' value ='<?php echo($context); ?>'>
        <input type='hidden' id='manage' name='manage' value=0>
        <input type='hidden' id='save' name='save' value=0>
        <input type='hidden' id='remove' name='remove' value=0>
        <input type='hidden' id='send' name='send' value=0>
        <input type='hidden' id='uploadRoster' name='uploadRoster' value=0>
        <input type=hidden id='student_program_id' name='student_program_id' value='<?php echo($student_program_id); ?>'>
        
      </form>
      <div class="footer">
        <p> </p>
      </div>
    </div>

    <div class="modal fade" id="confirm-remove" tabindex="-1" role="dialog" aria-labelledby="remove" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    Delete <?php echo($contextLabel); ?>
                </div>
                <div class="modal-body">
                    Are you sure you want to permanently delete this <?php echo($context); ?>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger btn-ok">Delete</a>
                </div>
            </div>
        </div>
    </div>
    <script src='../js/main.js'></script>
    <script src='./js/manage.js'></script>
    <script>
      <?php echo($rosterRedirect); ?>
    </script>
  </body>
</html>
