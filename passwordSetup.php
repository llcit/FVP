<!DOCTYPE html>
<html lang="en">
    <head>
      <?php
        use PHPMailer\PHPMailer\PHPMailer;
        use PHPMailer\PHPMailer\Exception;
        if (isset($_POST['password-reset']) && $_POST['email']) {
          include "./inc/db.php";
          include "./inc/ses_settings.php";
          $emailId = $_POST['email'];
          $result = mysqli_query($dbcnx,"SELECT * FROM users WHERE email='" . $emailId . "'");
          $row= mysqli_fetch_array($result);
          if($row){
             $token = md5($emailId).rand(10,9999);
             $expFormat = mktime(
             date("H"), date("i"), date("s"), date("m") ,date("d")+1, date("Y")
             );
            $expDate = date("Y-m-d H:i:s",$expFormat);
            $update = mysqli_query($dbcnx,"UPDATE users set  password='" . $password . "', reset_link_token='" . $token . "' ,exp_date='" . $expDate . "' WHERE email='" . $emailId . "'");

            $link = "<a href='".$SES_settings['password_reset_base_url']."/passwordSet.php?key=".$email."&token=".$token."'>Click To Reset password</a>";

            require 'vendor/autoload.php';

            $mailer = new PHPMailer();
            $sender = $SES_settings['sender'];
            $senderName = $SES_settings['senderName'];
            $recipient = $_POST['email'];
            $usernameSmtp = $SES_settings['usernameSmtp'];
            $passwordSmtp = $SES_settings['passwordSmtp'];
            $host = $SES_settings['host'];
            $port = 587;
            $subject = 'Flagship Video: Reset Password';
            $bodyText =  "Click On This Link to Reset Password '.$link.'";
            $bodyHtml = '<h1>Password Reset for the Flagship Video Project</h1>
                <p>Click On This Link to Reset Password '.$link.'</p>';
            $mail = new PHPMailer(true);
            try {
                $mail->CharSet =  "utf-8";
                $mail->isSMTP();
                $mail->setFrom($sender, $senderName);
                $mail->Username   = $usernameSmtp;
                $mail->Password   = $passwordSmtp;
                $mail->Host       = $host;
                $mail->Port       = $port;
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
                echo "Check Your Email and Click on the link sent to your email." , PHP_EOL;
            } catch (phpmailerException $e) {
                echo "An error occurred. {$e->errorMessage()}", PHP_EOL; //Catch errors from PHPMailer.
            } catch (Exception $e) {
                echo "Email not sent. {$mail->ErrorInfo}", PHP_EOL; //Catch errors from Amazon SES.
            }
          }
        }
      ?>
      <script src="//ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
      <script src="../ableplayer/thirdparty/js.cookie.js"></script>
      <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.bundle.min.js"></script>
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.1.0/css/all.css" integrity="sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt" crossorigin="anonymous">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/css/bootstrap-select.min.css">
      <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.4/js/bootstrap-select.min.js"></script>

      <!-- Able Player CSS -->
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>
    </head>
    <body>
        <form method="post" action="">
            <div class="container">
                <div class="container" style="max-width: 1200px;">
                   <div class="row div_login">
                        <div class="col-md-12 mb-5">
                            <div class="card soloCard">
                                <div class="card-body">
                                   <h2 class="card-title">Password Setup</h2>
                                   <p class="card-text">Enter your email address below to receive a message with a password setup link.</p>
                                </div>
                                <div class="card-footer">
                                    <div class="form-group">
                                      <div>
                                         <label for="username">Email:</label>
                                         <input type="text" class="textbox" id="email" name="email" placeholder="Email" />
                                      </div>

                                      <div>
                                         <input type="submit" value="Send Link" name="password-reset" id="password-reset" />
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
    </body>
</html>
