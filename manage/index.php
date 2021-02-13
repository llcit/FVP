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
      include "../inc/db_pdo.php";
      include "../inc/dump.php";
      include "../inc/sqlFunctions.php";
      include "../inc/htmlFunctions.php";
      include "./inc/".$context.".php";
      $SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
      require '../vendor/autoload.php';
      use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;
			$pageTitle = "Flagship Video Project";
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
        $navLinks = writeNavLinks($user->role,'header');
        $userName = "<h5 style='display:inline'>" . $user->first_name . " " . $user->last_name . "</h5>";
        $welcomeMsg = "
          $userName 
          <a href='".$SETTINGS['base_url']."/logout.php' class='btn btn-xs btn-icon btn-danger'>
            <i class='fa fa-sign-out-alt' aria-hidden='true'></i>
          </a>
        ";
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
          if ($_POST['send'] == 1) {
            $mailer = new PHPMailer(true);
            include "../inc/SESMailer.php";
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
              $userMsg =  "Email sent.";
              $msgClass = "success";
            }
            else {
              $userMsg =  "There was a problem sending the email to " . 
                          $emailUser->email . ": <p>" . $response ."</p>";
              $msgClass = "error";
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
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.15.1/moment.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
    <link rel="stylesheet" href="../css/main.css" type="text/css"/>
    <script src='../js/main.js'></script>
    
    <script>
      function setContext(context) {
        $('#context').val(context);
        $('#manageForm').submit();       
      }
      function manage(id) {
        $('#manage').val(1);
        $('#post_id').val(id);
        $('#manageForm').submit();
      }
      function save() {
        $('#save').val(1);
        $('#manageForm').submit();
      }
      function remove(id) {
        $('#remove').val(1);
        $('#post_id').val(id);
        $('#manageForm').submit();        
      }
      function cancel() {
        $('#manage').val(0);
        $('#save').val(0);
        $('#remove').val(0);
        $('#post_id').val(0);
        $('#manageForm').submit();        
      }
      function sendInvite(user_id) {
        $('#send').val(1);
        $('#post_id').val(user_id);
        $('#manageForm').submit(); 
      }
      $( document ).ready(function() {
        $( function() {
          $( "#start_date" ).datepicker();
          $( "#end_date" ).datepicker();
        } );
        $('input').keypress(function() {
          enableSave();
        });
        $('input').change(function() {
          enableSave();
        });
        $('select').change(function() {
          enableSave();
        });
        $('#confirm-remove').on('show.bs.modal', function(e) {
          $(this).find('.btn-ok').attr('href', $(e.relatedTarget).data('href'));
        });
        // activate tooltip
        $(function () {
          $('[data-toggle="tooltip"]').tooltip()
        })
      });
      function enableSave() {
        var enable = false;
        var context = '<?php echo($context); ?>';
        var dateString = new RegExp('^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$');
        switch(context) {
          case 'event':
            if(
                $("#program_id option:selected").val() > 0 && 
                $("#phase option:selected").val() != '' &&
                dateString.test($("#start_date").val()) &&
                dateString.test($("#end_date").val()) &&
                $("#city option:selected").val() != '' &&
                $("#country option:selected").val() != ''
              ) {
              enable = true;
            }
            break;
          case 'program':
            if(
                $("#language option:selected").val() != '' && 
                $("#timespan option:selected").val() != '' &&
                dateString.test($("#start_date").val()) &&
                dateString.test($("#end_date").val())
              ) {
              enable = true;
            }
            break;
          case 'user':
            if(
                $("#first_name").val() != '' && 
                $("#last_name").val() != '' && 
                isEmail($("#email").val()) &&
                $("#role option:selected").val() != '' 
              ) {
              enable = true;
            }
            break;
        }
        if(enable) {
          $('#saveButton').removeClass('disabled');
        }
      }
    </script>
  </head>
  <body>
    <div class="panel panel-default">
      <div class="panel-heading fv_heading" style='overflow:none;'>
        <div class='row flex-nowrap'>
          <div class='col-3'>
            <img src='../img/logo_lf.png' class='logo-img-fluid'>
          </div>
          <div class='pageTitle col-6'>
          		<?php echo($pageTitle); ?>
          </div>
          <div class='col-3'>
            <img src='../img/logo_ac.png' class='logo-img-fluid float-right'>
          </div>
        </div>
      </div>
      <div class='fv_subHeader'>
        <?php echo($navLinks); ?>
        <?php echo($welcomeMsg); ?>
      </div>
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
        <input type='hidden' id='save' name='save' value =0>
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
  </body>
</html>
