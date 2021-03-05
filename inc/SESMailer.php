<?php
  require $SETTINGS['base_path'] . '/vendor/autoload.php';
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
	function send($vars) {
		global $SETTINGS;
		$mailer = new PHPMailer(true);
		$recipient = $vars['recipient'];
		$subject = $vars['subject'];
		$bodyText = $vars['bodyText'];
		$bodyHtml = $vars['bodyHtml']; 
	  try {
	    $mailer->CharSet =  "utf-8";
	    $mailer->isSMTP();
	    $mailer->setFrom($SETTINGS['sender'], $SETTINGS['senderName']);
	    $mailer->Username   = $SETTINGS['usernameSmtp'];
	    $mailer->Password   = $SETTINGS['passwordSmtp'];
	    $mailer->Host       = $SETTINGS['hostSmtp'];
	    $mailer->Port       = $SETTINGS['portSmtp'];
	    $mailer->SMTPAuth   = true;
	    $mailer->SMTPSecure = 'tls';
	    $mailer->addCustomHeader('X-SES-CONFIGURATION-SET', $configurationSet);
	    $mailer->addAddress($recipient);
	    $mailer->isHTML(true);
	    $mailer->Subject    = $subject;
	    $mailer->Body       = $bodyHtml;
	    $mailer->AltBody    = $bodyText;
	    $mailer->Send();
	    $userMsg = "success";
	  } catch (phpmailerException $e) {
	      $userMsg =  "An error occurred. {$e->errorMessage()}"; //Catch errors from PHPMailer.
	  } catch (Exception $e) {
	      $userMsg =  "Email not sent. {$mailer->ErrorInfo}"; //Catch errors from Amazon SES.
	  }
	  return $userMsg;
	}
	function sendMail($message,$vars) {
		global $SETTINGS;
		// for password reset link, email is typed in by the user
		// otherwise, user id is passed by admin interaction for 
		// manual and auto-send of Welcome message
		if ($vars['email']) {
			// call global function to look up by username
			$emailUser = getUser($vars['email']);
		}
		else {
			// get user by id using functions in /manage/inc/
			$emailUser = getSavedUser($vars['user_id']);
		}
    $email = $emailUser->email;
    $role = $emailUser->role;
		switch($message) {
    	case 'Welcome':
	      $url = $SETTINGS['base_url']."/passwordSetup.php?email=".$email;
	      $link = "<a href='$url'>$url</a>";
	      $emailVars = [
	        'recipient' => $email,
	        'subject' => "Welcome to the Flagship Video Project",
	        'bodyText' => "You have been added to the system with $role privileges. 
	                       To set up your password, click the following link : $url",
	        'bodyHtml' => "<p>You have been added to the Flagship Video Project system with " . 
	                       $role . " privileges. To set up your password, click the following link :</p>
	                       <p>$link</p>"
	      ];
	      break;
	    case 'Password_Reset' :
	      $url = $SETTINGS['base_url']."/passwordSet.php?email=".$vars['email']."&token=".$vars['token'];
	      $link = "<a href='$url'>$url</a>";
	      $emailVars = [
	        'recipient' => $email,
	        'subject' => "Flagship Video Project: Setup New Password",
	        'bodyText' => "Copy/paste the link below to set or reset your password: ".$url,
          'bodyHtml' => "
                         <p>
                          Click or copy/paste the link below to set or reset your password: 
                         </p>
                         <p>
                          ".$link."
                         </p>
                         "
	      ];

    }
    $response = send($emailVars);
    if ($response == 'success') {
      $msg = "
        <div class='msg success'>
          Email sent to " . 
          $email . "
        </div>
      ";
    }
    else {
      $msg = "
        <div class='msg error'>
          There was a problem sending the email to " . 
          $email . ": <p>" . $response ."</p>
        </div>
      ";
    }
    return $msg;
	}

?>