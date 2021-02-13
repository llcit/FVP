<?php
  require 'vendor/autoload.php';
  use PHPMailer\PHPMailer\PHPMailer;
  use PHPMailer\PHPMailer\Exception;
	function sendMail($vars) {
		global $SETTINGS;
		$mailer = new PHPMailer();
		$mail = new PHPMailer(true);
		$recipient = $vars['recipient'];
		$subject = $vars['subject'];
		$bodyText = $vars['bodyText'];
		$bodyHtml = $vars['bodyHtml']; 
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
	    $mail->addAddress($recipient);
	    $mail->isHTML(true);
	    $mail->Subject    = $subject;
	    $mail->Body       = $bodyHtml;
	    $mail->AltBody    = $bodyText;
	    $mail->Send();
	    $userMsg = "success";
	  } catch (phpmailerException $e) {
	      $userMsg =  "An error occurred. {$e->errorMessage()}"; //Catch errors from PHPMailer.
	  } catch (Exception $e) {
	      $userMsg =  "Email not sent. {$mail->ErrorInfo}"; //Catch errors from Amazon SES.
	  }
	  return $userMsg;
	}
?>