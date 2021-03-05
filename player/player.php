<!DOCTYPE html>
<html lang="en">
<head>
  <?php
    include "../inc/db_pdo.php";
    include "../inc/dump.php";
    include "../inc/sqlFunctions.php";
    include "../inc/htmlFunctions.php";
    $SETTINGS = parse_ini_file(__DIR__."/../inc/settings.ini");
  ?>

  <meta charset="UTF-8">
  <title>Flagship Video Player</title>
  <!-- Dependencies -->
  <?php
    $cdnDependencies = writeCDNDepandencies();
    echo($cdnDependencies);
  ?>
  <script>
    // set for S3FileGen
    var base_url = '<?php echo($SETTINGS['base_url']); ?>';
  </script>
  <?php
    session_start();
    $user = getUser($_SESSION['username']);
    $videoId = ($_GET['v']) ? $_GET['v'] : 86;
    if ($_POST['saveCaptions']) {
      include "./saveCaptionEdits.php";
      $data = json_decode($_POST['captionData']);
      writeVTTFile($videoId,$data,$_POST['captionLanguage']);
      if ($_POST['transcript_final']) {
        updatePresentationStatus($videoId,'transcript_final');
      }
      if ($_POST['translation_final']) {
        updatePresentationStatus($videoId,'translation_final');
      }
      // plant as hidden for multiple edit/saves
      $captionLanguage = $_POST['captionLanguage'];
      $captionMode = '';
    }
    else {
      $captionLanguage = $_GET['language'];
      $captionMode = $_GET['cm'];
    }
    if ($_POST['translateCaptions']) {
      include "../upload/php-s3-server-master/translateCaptions.php";
      translateVTTFile($videoId);
      updatePresentationStatus($videoId,'translation_raw');

    }
    $presentationData = getVideos($videoId,'id');
    $allTracks = 'linguistic,professional,cutural';
    $includeTracks = ($_GET['t']) ? $_GET['t'] : $allTracks;
    $editCaptions = ($_GET['cm'] == 'edit' && $_POST['saveCaptions'] != 1 && $_POST['translateCaptions'] != 1)? true : false;
    if ($editCaptions) {
      $editCaptionTag = 'editCaptions';
      echo("<script src='../js/captionEditor.js'></script>");
      echo("<link rel='stylesheet' href='../css/captionEditor.css' type='text/css'/>");
      $transcriptHeight = '1000';
    }
    else {
      $descriptionTracks = "
        <track id='descriptionTrack' kind='descriptions' src='".$SETTINGS['base_url']."/player/buildDescriptionTrack.php?a=".$presentationData[0]['annotations']."&t=$includeTracks&v=$videoId' srclang='en'/> 
      ";
      $transcriptHeight = '400';
    }
    $isOwner = ($user->id == $presentationData[0]['user_id']) ? true : false;
    $isShowcase = ($presentationData[0]['is_showcase']==1) ? true : false;
    $displayVideo;
    if (
        !$isOwner && !$isShowcase && $user->role != 'admin' && 
        $user->role != 'staff' && (
          $presentationData[0]['grant_public'] != 1 &&
          $_GET['ac'] != $presentationData[0]['access_code']
        )
      ) {
      $displayVideo = false;
    }
    else {
      $displayVideo = true;
    }
    if($isOwner && !$isShowcase) {
      if ($editCaptions) {
      	$final = ($_GET['language'] == 'en') ? 'translation_final':'transcript_final' ;
        $editControls = "
          <div id = 'edit_controls' class = 'edit_controls'>
            <a style='margin-left:20px;' class='btn btn-secondary' href=\"javascript:cancelEdit();\">Cancel</a>
            <div class='form-check' style='display:inline;'>
              <input type='checkbox' class='transcript_final' id='$final' name='$final'>
              <label class='form-check-label' for='transcript_final'>Save as Final</label>
            </div>
            <a style='margin-left:20px;' class='btn btn-primary' href=\"javascript:saveCaptions();\">Save Captions</a>
          </div>
        ";
      }
      else {
        if ($presentationData[0]['transcript_final'] == 1 && $presentationData[0]['translation_final'] != 1) {
          $label = ($presentationData[0]['translation_raw'] == 1) ? 'Regenerate' : 'Generate';
          $translateButton = "
            <a class='btn btn-primary' href=\"javascript:translate();\">$label Translation</a>
          ";
        }
        $editControls = "
          <div id = 'edit_controls' class = 'edit_controls'>
            $translateButton
            <a class='btn btn-primary' href=\"javascript:editCaptions();\">Edit Captions</a>
          </div>
        ";
      }
    }
    $languages = [
        'Arabic' => 'ar',
        'Chinese' => 'zh',
        'English' => 'en',
        'Korean' => 'ko',
        'Portuguese' => 'pt',
        'Russian' => 'ru'
    ];
    $hasTrancript = '';
    $hasTranlation = '';
    // caption language for editing
    if ($_GET['language'] && $editCaptions) {
      // editing translation
      if ($_GET['language'] == 'en') {
        $hasTranslation = 1;
        $hasTranscript = 0;
      }
      // editing transcript
      else {
        $hasTranslation = 0;
        $hasTranscript = 1;        
      }
    }
    else {
      $hasTranscript = $presentationData[0]['transcript_raw'];
      $hasTranslation = $presentationData[0]['translation_raw'];    
    }
    if ($displayVideo) {
      $pageContent = "
        <div id='player'>
          <video id='video1' preload='auto' width='480' height='360' poster='../ableplayer/media/wwa.jpg' data-able-player data-transcript-div='transcript' playsinline $editCaptionTag>
      ";
      if ($hasTranscript) {
        $pageContent .= "<track kind='captions' src='".$SETTINGS['base_url']. "/inc/S3LinkGen.php?type=transcript&id=".$_GET['v']."&ext=vtt' srclang='".$languages[$presentationData[0]['language']]."' label='".$presentationData[0]['language']."'/>";
      } 
      if ($hasTranslation) {
        $pageContent .= "<track kind='captions' src='".$SETTINGS['base_url']. "/inc/S3LinkGen.php?type=translation&id=".$_GET['v']."&ext=vtt' srclang='en' label='English'/>";
      }
      if ($presentationData[0]['annotations'] != '') {
        $pageContent .= "$descriptionTracks";
      }
      $pageContent .= "
          </video>
        </div>
        <div id='transcript'></div>
      ";
    }
    else {
      $pageContent = "
        <div class='container'>
           <div class='row fv_main'>
              <div class='card fv_card'>
                  <div class='card-body fv_card_body' style='border-bottom:solid 1px gray;max-height:65px;'>
                     <h2 class='card-title'>Access Denied</h2>
                     <p class='card-text'></p>
                  </div>
                  <div class='fv_pageContent'>
                    <p>
                      You do not have sufficient permissions to view this video.  
                      Because it is not marked as public, you would need to obtain a 
                      public link from the owner of the video.
                    </p>
                   </div>
              </div>
            </div>
        </div>
       ";
    }
  ?>
  <!-- Style for this example only -->
  <style>
    main {
      position: relative;
      width: 100%;
      margin: 0;
    }
    #player {
      float: left;
      width: 480px;
      margin: 0;
    }
    #transcript {
      height:<?php echo($transcriptHeight); ?>.px
      margin: 0;
      padding-left: 500px;
    }
    #transcript div.able-desc {
      width: 90%;
    }

  </style>
  <script src='../js/main.js'></script>
  <script src='../js/S3FileGen.js'></script>
  <script>
    var captionMode = '<?php echo($captionMode);?>';
    var hasTranscript = '<?php echo($hasTranscript);?>';
    var hasTranslation = '<?php echo($hasTranslation);?>';
    var annotations = '<?php echo($presentationData[0]['annotations']); ?>';
    var DEFAULT_LANGUAGE = '<?php echo($languages[$presentationData[0]['language']]); ?>';
    var SELECTED_LANGUAGE = '<?php echo($_GET['language']); ?>';
    // after save, keep selected language
    if (captionMode != 'edit' && SELECTED_LANGUAGE != '') {
      timerID=setTimeout(function() {
        $("#transcript-language-select").val(SELECTED_LANGUAGE);
        $("#transcript-language-select").change();
      },500);
    }
    var videoFile = generateFile('video','<?php echo($_GET['v']); ?>','<?php echo($presentationData[0]['extension']); ?>','');
    var showTranscriptArea = (hasTranscript == 1 || hasTranslation == 1) ? true : false;
    // center the player
    if (!showTranscriptArea) {
      $('#player').css('float','none');
      $('#player').css('margin','auto');
    }  
    function editCaptions() {
      if (typeof $("#transcript-language-select") !== "undefined" && $("#transcript-language-select > option").length>0) {
        language = $("#transcript-language-select option:selected").val();
      }
      else {
        language = DEFAULT_LANGUAGE;
      }
      if (language == 'ch') language = 'zh';
      window.top.location.href = "./index.php?v=<?php echo($_GET['v']); ?>&cm=edit&language="+language;  // reference parent
    }  
    function cancelEdit() {
      window.top.location.href = "./index.php?v=<?php echo($_GET['v']); ?>";  // reference parent
    }  

    function saveCaptions() {
      var i=0;
      var data = [];
      $('.captionEditInput').each(function() {
        var text = $(this).val();
        var startTimeMatch = $('#st_'+i).html().match(/(\d{2}\:\d{2})$/);
        var startTime = startTimeMatch[1];
        var endTimeMatch = $('#et_'+i).html().match(/(\d{2}\:\d{2})$/);
        var endTime = endTimeMatch[1];
        data.push({
          start: startTime,
          end: endTime,
          text:text
        });
        i++;
      });
      $('#captionData').val(JSON.stringify(data));
      $('#saveCaptions').val(1);
      $('#saveCaptionForm').submit();
    }    
    function translate() {
      displayMessage('Generating translation... please wait.');
      $('#translateCaptions').val(1);
      $('#saveCaptionForm').submit();
    }
  </script>
  <!-- Able Player JavaScript -->
  <script src="../ableplayer/build/ableplayer.js"></script>
  <script src="../ableplayer/thirdparty/modernizr.custom.js"></script>
  <script src="../ableplayer/thirdparty/js.cookie.js"></script>

  <!-- Able Player CSS -->
  <link rel="stylesheet" href="../ableplayer/build/ableplayer.css" type="text/css"/>
  <link rel="stylesheet" href="../css/main.css" type="text/css"/>
</head>
  <body>
    <span id='userMsg' name='userMsg' class='msg' style='display:none;float:left;max-height:50px;'></span>
    <form method='post' id='saveCaptionForm' name='saveCaptionForm'>
      <input type='hidden' id='saveCaptions' name='saveCaptions' value = 0> 
      <input type='hidden' id='captionData' name='captionData' value = ''>
      <input type='hidden' id='captionLanguage' name='captionLanguage' value = '<?php echo($captionLanguage); ?>'>
      <input type='hidden' id='translateCaptions' name='translateCaptions' value = 0> 
      <?php echo($editControls); ?>
    </form>
    <main role="main">
      <?php echo($pageContent); ?>
    </main>
  </body>
</html>
