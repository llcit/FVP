<?php
/*
rip from xls

1. Clean initial 0s:		\r(0)?\: 			 -> 	\r00:
2. Clean hyphens:			(\s*)?\-(\s*+\-*)?  --> 	 -
3. Clean medial 0s			([^\d])(\d)([^\d])  -->  	\1 \0\2\3
4. Remove initial space		\r 0				-->		\r0
5.         (\d*)\:(\d*)\-(\d*)\:(\d*)   --->  \r00:\1:\2.000 --> 00:\3:\4.999\r
*/
	// list of requested tracks from post 
	$tracks = explode(',',$_GET['t']);
	$videoId = $_GET['v'];
	// all lines from all files
	$parsedLines = [];
	
	// merge all lines from specified tracks into one big array
	foreach ($tracks as $track) {
		$track = strtolower($track);
		$filename = "../assets/annotations/".$track."/".$videoId.".vtt";
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		$lines = preg_split("/\\n/", $contents);
		foreach ($lines as $line) {
			if ($line == "" || $line == 'WEBVTT') {
				$parsedLine = [];
			}
			else {
				// time codes
				if ($line[0] == '0') {
					// get the start time
					preg_match("/(\d{2}\:\d{2}\:\d{2}).*/",$line, $matches);
					// reduce time signatures to integers for sorting
					$start = preg_replace("/\:/", "",$matches[1]);
					// add start for sorting
					$parsedLine['start'] = $start;
					// add time reference
					$parsedLine['timeCodes'] = $line;
					// we need this to theme categories in the player descriptions
					$parsedLine['category'] = $track;
				}
				// transcriptions
				else {
					// add the trancsribed utterance
					$parsedLine['text'] = $line;
					// we need this to theme categories in the player descriptions
					$parsedLine['category'] = $track;
					array_push($parsedLines,$parsedLine);
				}
			}
		}
	}
	
	// sort by start time-- important.  This is the only way to keep the correct time-sequenced display!
	usort($parsedLines, 'sortByStart');
	
	// build the text of the merged and sorted vtt entries
	$mergedTrack = "WEBVTT\r\n\r\n";
	foreach($parsedLines as $parsedLine) {
		$mergedTrack .= $parsedLine['timeCodes'] . "\r\n[" . $parsedLine['category']. "] " . $parsedLine['text']. "\r\n\r\n";
	}
	
	// print out the merged VTT track
	header("Content-Type:text/vtt;charset=utf-8");
	print($mergedTrack);
	
	function sortByStart($a, $b) {
	    return $a['start'] - $b['start'];
	}
?>