<?php
	function sendMail($mailer,$vars) {
		global $SETTINGS;
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
?>