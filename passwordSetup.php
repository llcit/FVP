<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        require 'vendor/autoload.php';
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        include "./inc/dump.php";
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
            $SETTINGS = parse_ini_file(__DIR__."/inc/settings.ini");
            $expDate = date("Y-m-d H:i:s",$expFormat);
            $success = updatePassword($password,$emailId,$token,$expDate); 
            $link = "<p>Click or copy & paste the link below to set your password.</p> <a href='".$SETTINGS['password_reset_base_url']."/passwordSet.php?email=".$emailId."&token=".$token."'>".$SETTINGS['password_reset_base_url']."/passwordSet.php?email=".$emailId."&token=".$token."</a>";


            $mailer = new PHPMailer();
            $recipient = $_POST['email'];
            $subject = 'Flagship Video: Reset Password';
            $bodyText =  "Click On This Link to Reset Password '.$link.'";
            $bodyHtml = '<h1>Password Reset for the Flagship Video Project</h1>
                <p>Click On This Link to Reset Password '.$link.'</p>';
            $mail = new PHPMailer(true);
            try {
              $mail->CharSet =  "utf-8";
              $mail->isSMTP();
              $mail->setFrom($SETTINGS['sender'], $SETTINGS['senderName']);
              $mail->Username   = $SETTINGS['usernameSmtp'];
              $mail->Password   = $SETTINGS['passwordSmtp'];
              $mail->Host       = $SETTINGS['hostSmtp'];
              $mail->Port       = $SETTINGS['portSmtp'];
              $mail->SMTPAuth   = true;
              $mail->SMTPSecure = 'tls';
              $mail->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);

              // Specify the message recipients.
              $mail->addAddress($recipient);
              // You can also add CC, BCC, and additional To recipients here.

              // Specify the content of the message.
              $mail->isHTML(true);
              $mail->Subject    = $subject;
              $mail->Body       = $bodyHtml;
              $mail->AltBody    = $bodyText;
              $mail->Send();
              $userMsg =  "Check your email and click on the link to set your new password.";
              $msgClass = "success";
            } catch (phpmailerException $e) {
                $userMsg =  "An error occurred. {$e->errorMessage()}"; //Catch errors from PHPMailer.
                $msgClass = "error";
            } catch (Exception $e) {
                $userMsg =  "Email not sent. {$mail->ErrorInfo}"; //Catch errors from Amazon SES.
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
      ?>
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
      <!-- Able Player CSS -->
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>

      <script>
        function enableSend() {
          var email = document.getElementById('email').value;
          if (isEmail(email)) {
            document.getElementById("password-reset").disabled = false;
          }

        }
        function isEmail(email) {
          var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
          return regex.test(email);
        }
      </script>
    </head>
    <body>
      <div class="panel panel-default">
        <div class="panel-heading fv_heading">
          <img src='./img/logo_lf.png'>
          &nbsp;&nbsp;&nbsp;Flagship Video Password Setup
          <span class='pull-right'>
            <img src='./img/logo_ac.png'>
          </span>
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
                                           <input type="text" class="textbox" id="email" name="email" placeholder="Email" / onkeyup="enableSend();">
                                        </div>

                                        <div>
                                           <input type="submit" value="Send Link" name="password-reset" id="password-reset" class='btn btn-primary fv_button' disabled/>
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
