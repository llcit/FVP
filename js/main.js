	function playVideo(element_id,embedded) {
		var id_parts = element_id.match(/(.+)\_(.+)/);
	  var video_id = id_parts[2];
	  var backTick = (embedded) ? '..' : '.';
		window.location = backTick + "/player/index.php?v="+video_id ;
	}
	function writeDetails(data_str) {
		var data = $.parseJSON(data_str);
		console.log(data);
		var details = [];
		details.push('<li>' + data.first_name + ' ' + data.last_name + '</li>');
		details.push('<li>Program: ' + data.program + '</li>');
		details.push('<li>Program Year: ' + data.progYrs + '</li>');
		details.push('<li>Program Period: ' + data.phase + '</li>');
		details.push('<li>Domestic Institution: ' + data.institution + '</li>');
		details.push('<li>Overseas Location: ' + data.city + ', ' + data.country + '</li>');
		details.push('<li>Performance Type: ' + data.type + '</li>');
		$('.modal-title').empty();
		$('.modal-title').append(details.join(''));
	}