<!DOCTYPE html>
<html lang="en">
    <head>
        <?php
            include "./inc/db_pdo.php";
            include "./inc/dump.php";
            session_start();
            if(isset($_POST["login"])) {
                $username=$_POST['username'];
                $password=password_hash($_POST['password'], PASSWORD_DEFAULT);
                $sql ="SELECT username, password FROM users WHERE (username=:username)";
                $query= $pdo->prepare($sql);
                $query->bindParam(':username', $username, PDO::PARAM_STR);
                //$query->bindParam(':password', $password, PDO::PARAM_STR);
                $query->execute();

                if($query->rowCount() > 0) {
                    $result = $query->fetch(PDO::FETCH_OBJ);
                    if (password_verify($_POST["password"], $result->password)) {
                        $_SESSION["username"] = $_POST["username"];
                        exit(header("location:archive/index.php"));
                    } else {
                        echo "'Invalid Details'";
                    } 
                } else {  
                    echo "'Invalid Details'";
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
        <div class="panel panel-default">
            <div class="panel-heading fv_heading">
                <img src='./img/logo_lf.png'>
                &nbsp;&nbsp;&nbsp;Flagship Video Login 
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
                                           <h2 class="card-title">Login</h2>
                                           <p class="card-text">Please enter your email password then click submit.</p>
                                        </div>
                                        <div class="card-footer">
                                            <div class="form-group">
                                              <div>
                                                 <label for="username">Username:</label>
                                                 <input type="text" class="textbox" id="username" name="username" placeholder="Username" />
                                              </div>
                                              <div>
                                                 <label for="password">Password:</label>
                                                 <input type="password" class="textbox" id="password" name="password" placeholder="Password"/>
                                              </div>
                                              <div>
                                                 <input type="submit" class='btn btn-primary fv_button'value="Submit" name="login" id="login" />
                                              </div>
                                           </div>
                                        </div>
                                    </div>
                                </div>
                                   <a class ="pull-right loginLink" href='passwordSetup.php'>Set/reset your password</a>
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

