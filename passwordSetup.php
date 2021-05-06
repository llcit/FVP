<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        $SETTINGS = parse_ini_file(__DIR__."/inc/settings.ini");
        require 'vendor/autoload.php';
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        include "./inc/dump.php";
        include "./inc/SESMailer.php";
        include "./inc/db_pdo.php";
        include "./inc/sqlFunctions.php";
        $userMsg = '';
        if (isset($_POST['password-reset']) && $_POST['email']) {
          $emailId = $_POST['email'];
          $userExists = getExistingUser($emailId,null);
          if($userExists){
             $token = md5($emailId).rand(10,9999);
             $expFormat = mktime(
             date("H"), date("i"), date("s"), date("m") ,date("d")+1, date("Y")
             );
            $expDate = date("Y-m-d H:i:s",$expFormat);
            $success = updatePassword($password,$emailId,$token,$expDate);
            if($success) {
              $emailVars = [
                'email' => $emailId,
                'token' => $token
              ];
              $userMsg = sendMail("Password_Reset", $emailVars); 
            }
            else {
              $userMsg =  "Unable to generate a reset token!"; 
              $msgClass = " error";              
            }
          }
          else {
                $userMsg =  "We are unable to locate that email address in the system.  Please use the address to which your invitation was sent."; 
                $msgClass = " error";
          }
        }
        if ($userMsg != '') {
          $userMsgPanel = "
                                <div class='msg $msgClass'>
                                    $userMsg
                                  </div>
          ";
        }
        $disabled = ($_GET['email']) ? "" : "disabled";
      ?>
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
      <!-- Able Player CSS -->
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>
      <script src='./js/main.js'></script>

      <script>
        function enableSend() {
          var email = document.getElementById('email').value;
          if (isEmail(email)) {
            document.getElementById("password-reset").disabled = false;
          }

        }
      </script>
    </head>
    <body>
      <div class="panel panel-default">
        <div class="panel-heading fv_heading">
          <img src='./img/logo_lf.png'>
          &nbsp;&nbsp;&nbsp;Flagship Video Password Setup
          
        </div>
        <div class="panel-body">
          <form method="post" action="">
              <div class="container">
                  <div class="container" style="max-width: 1200px;">
                     <div class="row fv_main">
                          <div class="col-md-12 mb-5">
                              <div class="card soloCard">
                                  <div class="card-body">
                                     <h2 class="card-title">Password Setup</h2>
                                     <p class="card-text">Enter your email address below to receive a message with a password setup link.</p>
                                  </div>
                                  <?php echo($userMsgPanel); ?>
                                  
                                  <div class="card-footer">
                                      <div class="form-group">
                                        <div>
                                           <label for="username">Email:</label>
                                           <input type="text" class="textbox fv_text_box" id="email" name="email" value='<?php echo($_GET['email']); ?>' placeholder="Email" / onkeyup="enableSend();">
                                        </div>

                                        <div>
                                           <input type="submit" value="Send Link" name="password-reset" id="password-reset" class='btn btn-primary fv_button' <?php echo($disabled); ?>/>
                                        </div>
                                     </div>
                                  </div>
                              </div>
                          </div>
                          <a class ="pull-right loginLink" href='login.php'>Return to Login</a>
                      </div>
                  </div>
              </div>
          </form>
        </div>
        <div class="footer">
          <p> </p>
        </div>
      </div>
    </body>
</html>
