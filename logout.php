<?php
  session_start();
  unset($_SESSION["username"]);
  exit(header("location:./index.php"));
?>