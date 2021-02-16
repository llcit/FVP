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
      include_once "../inc/SESMailer.php";
      require '../vendor/autoload.php';
      use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;
			$subTitle = "Manage $contextLabel"."s";
			$titleText = "You may select an existing $context to edit or add a new $context. ";
      if ($context == 'event') {
        $deleteMsg = " You may only delete an event if it  does not have any videos uploaded to it.";
      }
      else if ($context == 'program') {
        $deleteMsg = " You may only delete a program if it does not have any users affiliated with it.";
      } 
      $titleText .= $deleteMsg; 
			session_start();
			if (!isset($_SESSION['username'])) { 
		    exit(header("location:../login.php"));
		  } 
		  else {
        $user = getUser($_SESSION['username']);
        if ($_POST['manage'] == 1) {
          $pageContent = buildManager($_POST['post_id']);
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
          if ($_POST['save'] == 1) {
            $response = save($_POST);
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
          }
          if ($_POST['send'] == 1 || $_POST['auto_send'] == 1) {
            $mailer = new PHPMailer(true);
            $emailUser = getSavedUser($_POST['post_id']);
            $url = $SETTINGS['base_url']."/passwordSetup.php?email=".$emailUser->email;
            $link = "<a href='$url'>$url</a>";
            $emailVars = [
              'recipient' => $emailUser->email,
              'subject' => "Welcome to the Flagship Video Project",
              'bodyText' => "You have been added to the system with $role privileges. 
                             To set up your password, click the following link : $url",
              'bodyHtml' => "<p>You have been added to the system with $role privileges. 
                             To set up your password, click the following link :</p><p>$link</p>"
            ];
            $response = sendMail($mailer,$emailVars);
            if ($response == 'success') {
              $msg = "
                <div class='msg success'>
                  Email sent to " . 
                  $emailUser->email . "
                </div>
              ";
            }
            else {
              $msg = "
                <div class='msg error'>
                  There was a problem sending the email to " . 
                  $emailUser->email . ": <p>" . $response ."</p>
                </div>
              ";
            }
          }
          $existing = getExisting();
          $displayList = formatList($existing);
          if ($user->role == 'admin' || $user->role == 'staff') {
            $pageContent = "
              <div class='panel'>
                <h4 style='display:inline;'>
                  Existing ".$contextLabel."s
                </h4>
                <a class='btn btn-primary float-right' href='javascript:manage();'> 
                <i class='fa fa-plus-circle' aria-hidden='true'></i>
                Add $contextLabel 
                </a>
                $displayList
              </div>
            ";
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
              <a class="nav-link <?php echo($active['program']); ?>" href="javascript:setContext('program');">Programs</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?php echo($active['user']); ?>" href="javascript:setContext('user');">Users</a>
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
        <input type='hidden' id='manage' name='manage' value =0>
        <input type='hidden' id='save' name='save' value =0>
        <input type='hidden' id='remove' name='remove' value =0>
        <input type='hidden' id='send' name='send' value =0>
      </form>
      <div class="footer">
        <p> </p>
      </div>
    </div>

    <div class="modal fade" id="confirm-remove" tabindex="-1" role="dialog" aria-labelledby="remove" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    Delete Event
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
  </body>
</html>
