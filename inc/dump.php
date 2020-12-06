<?php
set_exception_handler(function(Throwable $e) {
	$text = sprintf("
		<h1>Uncaught exception</h1>
		<hr />
		<pre>%s</pre>
		<hr />
		<pre>%s</pre>",
		$e->getMessage(),
		$e->getTraceAsString()
	);

	die($text);
});

function dump($data) {
	throw new E(prettyPrintJson(json_encode($data)));
}
function vdump($data,$label=null) {
  $output = prettyPrintJson(json_encode($data));
  $output = preg_replace("/\}/","<br>}<br>",$output);
  $output = preg_replace("/\{/","{<br>",$output);
  if ($label) $displayLabel = " for \$$label";
  print "<hr>VALUES $displayLabel:<br>$output<hr>";
}

function prettyPrintJson( $json )
{
  $result = '';
  $level = 0;
  $in_quotes = false;
  $in_escape = false;
  $ends_line_level = NULL;
  $json_length = strlen( $json );

  for( $i = 0; $i < $json_length; $i++ ) {
    $char = $json[$i];
    $new_line_level = NULL;
    $post = "";
    if( $ends_line_level !== NULL ) {
      $new_line_level = $ends_line_level;
      $ends_line_level = NULL;
    }
    if ( $in_escape ) {
      $in_escape = false;
    } else if( $char === '"' ) {
      $in_quotes = !$in_quotes;
    } else if( ! $in_quotes ) {
      switch( $char ) {
        case '}': case ']':
          $level--;
          $ends_line_level = NULL;
          $new_line_level = $level;
          break;

        case '{': case '[':
          $level++;
        case ',':
          $ends_line_level = $level;
          break;

        case ':':
          $post = " ";
          break;

        case " ": case "\t": case "\n": case "\r":
          $char = "";
          $ends_line_level = $new_line_level;
          $new_line_level = NULL;
          break;
      }
    } else if ( $char === '\\' ) {
      $in_escape = true;
    }
    if( $new_line_level !== NULL ) {
      $result .= "\n".str_repeat( "  ", $new_line_level );
    }
    $result .= $char.$post;
  }
  return $result;
}

class E extends Exception
{
	public function __construct($msg) {
		parent::__construct(var_export($msg, true));
	}
}
?>