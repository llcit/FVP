<!doctype html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <title>Reset Password In PHP MySQL</title>
      <!-- CSS -->
      <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" rel="stylesheet">
      <link rel="stylesheet" href="./css/main.css" type="text/css"/>
   </head>
   <body>
      <?php
         include "./inc/db.php";
         $userMsg = '';
         if (isset($_POST['password']) || $_POST['reset_link_token'] || $_POST['email']) {
            $emailId  = $_POST['email'];
            $token    = $_POST['reset_link_token'];
            $password=password_hash($_POST['password'], PASSWORD_DEFAULT);
            $query    = mysqli_query($dbcnx, "SELECT * FROM `users` WHERE `reset_link_token`='" . $token . "' and `email`='" . $emailId . "'");

            $row = mysqli_num_rows($query);
            if ($row) {
              mysqli_query($dbcnx, "UPDATE users set  password='" . $password . "', reset_link_token='" . NULL . "' ,exp_date='" . NULL . "' WHERE email='" . $emailId . "'");
              $userMsg = '<p>Congratulations! Your password has been updated successfully.</p>';
              $msgClass = " loginMsg_success";
              $returnToLogin = "<a class ='pull-right loginLink' href='login.php'>Return to Login</a>";
            } 
            else {
              $userMsg =  "<p>Something has gone wrong. Please try again</p>";
              $msgClass = " loginMsg_error";
            }
         }
         else if($_GET['key'] && $_GET['token']) {
            $email = $_GET['key'];
            $token = $_GET['token'];
            $query = mysqli_query($dbcnx,
            "SELECT * FROM `FVP`.`users` WHERE `reset_link_token`='".$token."' and `email`='".$email."';"
            );
            $curDate = date("Y-m-d H:i:s");
            if (mysqli_num_rows($query) > 0) {
               $row= mysqli_fetch_array($query);
               if($row['exp_date'] >= $curDate){ 
                  $pageContent = "
                     <form action='' method='post'>
                        <input type='hidden' name='email' value='$email'>
                        <input type='hidden' name='reset_link_token' value='$token'>
                        <div class='form-group'>
                           <label>Password</label>
                           <input type='password' name='password' id='password' class='form-control'>
                        </div>
                        <div class='rating'></div>
                        <div class='form-group'>
                           <label>Confirm Password</label>
                           <input type='password' name='cpassword' id='cpassword' class='form-control'>
                        </div>
                        <input type='submit' name='new-password' id='new-password' class='btn btn-primary' disabled>
                     </form>
                  ";
               } 
               else{
                  $userMsg = "This password link has expired";
                  $msgClass = " loginMsg_error";
               }
            } 
            else {
               $userMsg = "Email <i>$email</i> not found.";
               $msgClass = " loginMsg_error";
            }  
         }
         else {
            $userMsg = "Invalid request. This page requires an email address and a valid token. You may want to try copying and pasting the entire link from the email you received.";
            $msgClass = " loginMsg_error";
         }

        if ($userMsg != '') {
          $userMsgPanel = "
                                <div class='loginMsg $msgClass' style='margin-top:25px;'>
                                    $userMsg
                                  </div>
          ";
         }
      ?>
      <div class="panel panel-default">
         <div class="panel-heading fv_heading">
            <img src='./img/logo_lf.png'>
            &nbsp;&nbsp;&nbsp;Flagship Video Password Reset 
            <span class='pull-right'>
               <img src='./img/logo_ac.png'>
            </span>
         </div>
         <div class="panel-body">
           <div class="container">
               <div class="container" style="max-width: 1200px;">
                  <div class="row div_login">
                       <div class="col-md-12 mb-5">
                           <div class="card soloCard">
                               <div class="card-body">
                                 <h2 class="card-title">Set New Password</h2>
                                 <?php echo($userMsgPanel); ?>
                                 <?php echo($pageContent); ?>
                              </div>
                           </div>
                        </div>
                        <?php echo($returnToLogin); ?>
                     </div>
                  </div>
               </div>
            </div>           
         </div>
         <div class="footer">
           <p> </p>
         </div>
      </div>
      <script src="./js/passwordMeter.js"></script>
   </body>
</html>