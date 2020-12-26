	let currentCaption = false;
	let currentResizerType = false;
	let currentDirection = false;
	let currentY = 0;
	// top at beginning of resize 
	let original_top = 0;
	// height at beginning of resize
	let original_height = 0;
	const pixelsPerSecond = 30;
	function makeResizableDiv() {
		$('.resizable').each(function(){
			let currentCaption = $( this );				
			let parentWrapper = currentCaption.parent();
			let scrollWrapper = parentWrapper.parent();
			let prevCaption = currentCaption.prev('.resizable');
			let nextCaption = currentCaption.next('.resizable');
			const minimum_size = 60;
			let before_height = 0;
			// top before each resize 
			let before_top = 0;
			let before_mouse_y = 0;
			let before_height_prev = 0;
			let before_height_next = 0;
			// prevent multiple firing of updateTime
			let currentBreakPoint = 0;
			// ...but, signal that sibling time should be updated
			let updateSiblingTime = false;

			$( this ).find(".resizer").each(function(){
				const currentResizer = $( this );
				currentResizer.mousedown(function(e) {
					e.preventDefault();
					before_height = parseFloat(currentCaption.css('height').replace('px', ''));
					before_top = Math.round(currentCaption.position().top);
					if (original_top == 0) original_top = before_top;
					if (original_height == 0) original_height = before_height;
					before_mouse_y = e.pageY;
					window.addEventListener('mousemove', resize);
					window.addEventListener('mouseup', stopResize);
				}); // end mousedown					
				function resize(e) {
					let scrollTop = $(scrollWrapper).scrollTop();
					if (currentY > 0 && currentY != e.pageY) {
						let prevDirection = currentDirection;
						currentDirection = (e.pageY > currentY) ? 'down' : 'up';
						if (currentDirection != prevDirection) {
							currentBreakPoint = 0;
						}
					}
					currentY = e.pageY;
					if (currentResizer.hasClass('bottom')) {
						currentResizerType = 'bottom';
						let currentElement = currentCaption;
						const height = before_height + (e.pageY - before_mouse_y);
						if (height >= minimum_size) {
							currentElement.css('height',height + 'px');
							let global_offset = Math.round(height - original_height);
							if ((Math.abs(global_offset)%(pixelsPerSecond)==pixelsPerSecond/2) && global_offset != currentBreakPoint) { 
								//console.log('global_offset:',global_offset);
								//console.log('height:',height);
								//console.log('original_height:',original_height);
								//console.log('currentBreakPoint:',currentBreakPoint);

								let currentEndTime = currentElement.find(".endTime");
								updateTime(currentEndTime,currentDirection);
								currentBreakPoint = global_offset;
								updateSiblingTime = true;
							}

							var currHeight = parseFloat(currentElement.css('height').replace('px', ''));
							var currTop = parseFloat(currentElement.position().top);						
							if (typeof nextCaption.css('height') !== 'undefined')  {
								const next_height_orig = parseFloat(nextCaption.css('height').replace('px', ''));	
								const next_top = Math.round(nextCaption.position().top);
								const bottom = Math.round(currTop + currHeight);
								if (bottom > next_top) {
									//console.log("Next top: ", next_top);
									const height_offset = Math.round(bottom-next_top);
									//console.log("Height Offset: ", height_offset);
									const next_height_adj = next_height_orig - height_offset;
									if (next_height_adj >= minimum_size) {
										nextCaption.css('height',next_height_adj + 'px');
										nextCaption.css('top', parseFloat(next_top + height_offset)+scrollTop + 'px');
										if (updateSiblingTime) { 
											let nextStartTime = nextCaption.find(".startTime");
											updateTime(nextStartTime,currentDirection);
											updateSiblingTime = false;
										}
									}
									else if(currentDirection == 'down') {
										stopResize();
									}
								} // end move down
							}
							else {
								updateSiblingTime = false;
							}
						}
					} 
					if (currentResizer.hasClass('top')) {
						currentResizerType = 'top';
						let offset = e.pageY - before_mouse_y;
						let global_offset = Math.round(currentCaption.position().top - original_top);
						const height = before_height - offset;
						if (height >= minimum_size) {
							currentCaption.css('height',height + 'px');
							let top_adj = (before_top + offset) + scrollTop;
							if ((Math.abs(global_offset)%(pixelsPerSecond)==pixelsPerSecond/2) && global_offset != currentBreakPoint) { 
								//console.log('global_offset:',global_offset);
								//console.log('e.pageY:',e.pageY);
								//console.log('original_top:',original_top);
								//console.log('currentBreakPoint:',currentBreakPoint);
								let currentStartTime = $(currentCaption).find(".startTime");
								updateTime(currentStartTime,currentDirection);
								currentBreakPoint = global_offset;
								updateSiblingTime = true;
							}
							currentCaption.css('top', top_adj + 'px');
						}
						if (typeof prevCaption.css('height') !== 'undefined')  {
							const prev_height_orig = parseFloat(prevCaption.css('height').replace('px', ''));
							const prev_top = parseFloat(prevCaption.css('top').replace('px', ''));
							const prev_bottom = parseFloat(prev_top + prev_height_orig);
							const current_top = parseFloat(currentCaption.css('top').replace('px', ''));
							if (current_top <= prev_bottom) {
								const height_offset = parseFloat(current_top-prev_bottom);
								const prev_height_adj = parseFloat(prev_height_orig + height_offset);
								if (prev_height_adj >= minimum_size) {
									prevCaption.css('height',prev_height_adj + 'px');
									if (updateSiblingTime) { 
										let prevEndTime = prevCaption.find(".endTime");
										updateTime(prevEndTime,currentDirection);
										updateSiblingTime = false;
									}
								}
								else if (currentDirection == 'up') {
									stopResize();
								}
							}
						}
						else {
							updateSiblingTime = false;
						}
					} 
				} // END RESIZE
				function stopResize() {
					// reset top for timer
					original_top = 0;
					let parentWrapper = currentCaption.parent();
					let scrollWrapper = parentWrapper.parent();
					let scrollTop = Math.round($(scrollWrapper).scrollTop());
					//console.log('scrollTop--> ', scrollTop);
					let curr_height = Math.round(parseFloat(currentCaption.css('height').replace('px', '')));
					let curr_top = Math.round(currentCaption.position().top);
					let snapOffset = Math.round(curr_height%pixelsPerSecond);
					let snapBy = (snapOffset >= pixelsPerSecond/2) ? pixelsPerSecond - snapOffset : snapOffset*-1;
					
					window.removeEventListener('mousemove', resize);
					if (currentResizerType == 'bottom') {
						let height_adj = Math.round(curr_height + snapBy);	
						console.log('curr_height:',height_adj);
						currentCaption.css('height',height_adj + 'px');
						let nextCaption = currentCaption.next('.resizable');
						if (typeof nextCaption.css('height') !== 'undefined')  {
							let next_top = Math.round(nextCaption.position().top);
							let next_height = parseFloat(nextCaption.css('height').replace('px', ''));						
							let nextSnapOffset = Math.round(next_top%pixelsPerSecond);
							//console.log('next_top:',next_top);
							//console.log('nextSnapOffset:',nextSnapOffset);
							let nextSnapBy_top;
							let nextSnapBy_height;
							if (nextSnapOffset >= pixelsPerSecond/2) {
								nextSnapBy_top = Math.round(pixelsPerSecond - nextSnapOffset);
								nextSnapBy_height = Math.round((pixelsPerSecond - nextSnapOffset)*-1);
							}
							else {
								nextSnapBy_top = nextSnapOffset*-1;
								nextSnapBy_height = nextSnapOffset;
							}
							let next_height_adj = next_height + nextSnapBy_height;
							let next_top_adj = next_top + nextSnapBy_top + scrollTop;
							//console.log('next_top:',next_top);
							//console.log('nextSnapBy_top:',nextSnapBy_top);
							//console.log('next_top_adj:',next_top_adj);
							//console.log('next_height:',next_height);
							console.log('nextSnapBy_height:',nextSnapBy_height);
							console.log('next_height_adj:',next_height_adj);
							nextCaption.css('height',next_height_adj + 'px');
							nextCaption.css('top', next_top_adj + 'px');
						}
					}
					else {	
						let current_top = Math.round(currentCaption.position().top);
						let height_adj = curr_height + snapBy;
						let top_adj = current_top - snapBy + scrollTop;	
						//console.log('current_top:',current_top);
						console.log('height_adj:',height_adj);
						console.log('top_adj:',top_adj);
						currentCaption.css('height',height_adj + 'px');	
						currentCaption.css('top', top_adj + 'px');
						let prevCaption = currentCaption.prev('.resizable');
						if (typeof prevCaption.css('height') !== 'undefined')  {
							let prev_height = parseFloat(prevCaption.css('height').replace('px', ''));
							let prevSnapOffset = prev_height%pixelsPerSecond;
							let prevSnapBy_height;
							if (prevSnapOffset >= pixelsPerSecond/2) {
								prevSnapBy_height = (pixelsPerSecond - prevSnapOffset)*-1;
							}
							else {
								prevSnapBy_height = prevSnapOffset;
							}
							let prev_height_adj = Math.round(prev_height + prevSnapBy_height);
							console.log('prev_height_adj:',prev_height_adj);
							prevCaption.css('height',prev_height_adj + 'px');
						}
					}
					currentBreakPoint = 0;
				} // end stopResize
			}); // end each resizer
		});// end each currentCaption
	}	// end makeResizableDiv	
	function updateTime(element,direction){
		let stm = element.html().match(/(\d{2})\:(\d{2})$/);
		let currMin = parseFloat(stm[1]);
		let currSec = parseFloat(stm[2]);
		let bool = (direction =='up') ? -1 : 1;
		let startMin = pad((currMin), 2);
		let startSec=pad((currSec+bool), 2);
		let whichTime = (element.hasClass('startTime')) ? 'Start' : 'End';
		element.html(whichTime + " time: " + startMin+":"+startSec);
		//console.log('direction: ', direction);
	}
	function compareTime() {

	}

	var addEditableCaption = function(div, cap) {
    var $capSpan = $('<span>',{
      'class': 'able-transcript-seekpoint able-transcript-caption'
    });
    var $captionWrapper = $('<span>',{
      'class': 'resizable able-transcript-seekpoint able-transcript-caption'
      //able-transcript-seekpoint able-transcript-caption able-block-temp able-highlight
    });
    var $resizerWrapper = $('<div>',{
      'class': 'resizers'
    });
    var $resizerTop = $('<div>',{
      'class': 'resizer top'
    });

    var $resizerTopIcon = $('<i>',{
      'class': 'fas fa-sort-down'
    });
    var $startTime = $('<div>',{
      'class': 'startTime'
    });
    var $resizerBottom = $('<div>',{
      'class': 'resizer bottom'
    });
    var $resizerBottomIcon = $('<i>',{
      'class': 'fas fa-sort-up'
    });
    var $endTime = $('<div>',{
      'class': 'endTime'
    });
    var $capInput =  $('<textarea>',{
      'class': 'captionEditInput',
      'wrap': 'soft'
    });

    var flattenComponentForCaption = function(comp) {
      var result = [];

      var flattenString = function (str) {
        var result = [];
        if (str === '') {
          return result;
        }
        var openBracket = str.indexOf('[');
        var closeBracket = str.indexOf(']');
        var openParen = str.indexOf('(');
        var closeParen = str.indexOf(')');

        var hasBrackets = openBracket !== -1 && closeBracket !== -1;
        var hasParens = openParen !== -1 && closeParen !== -1;

        if ((hasParens && hasBrackets && openBracket < openParen) || hasBrackets) {
          result = result.concat(flattenString(str.substring(0, openBracket)));
          var $silentSpan = $('<span>',{
            'class': 'able-unspoken'
          });
          $silentSpan.text(str.substring(openBracket, closeBracket + 1));
          result.push($silentSpan);
          result = result.concat(flattenString(str.substring(openParen, closeParen + 1)));
        }
        else if (hasParens) {
          result = result.concat(flattenString(str.substring(0, openParen)));
          var $silentSpan = $('<span>',{
            'class': 'able-unspoken'
          });
          $silentSpan.text(str.substring(openBracket, closeBracket + 1));
          result.push($silentSpan);
          result = result.concat(flattenString(str.substring(closeParen + 1)));
        }
        else {
          result.push(str);
        }
        return result;
      };

      if (comp.type === 'string') {
        result = result.concat(flattenString(comp.value));
      }
      return result;
    };
    for (var ii = 0; ii < cap.components.children.length; ii++) {
      var results = flattenComponentForCaption(cap.components.children[ii]);
      for (var jj = 0; jj < results.length; jj++) {
      	let startMin = pad(Math.floor(cap.start/60), 2);
      	let startSec=pad(Math.round(cap.start%60), 2);
      	let endMin = pad(Math.floor(cap.end/60), 2);
      	let endSec=pad(Math.round(cap.end%60), 2);
        $capInput.val(results[jj]);
        $resizerTop.append($resizerTopIcon);
        $resizerWrapper.append($resizerTop);
        $startTime.html("Start time: " + startMin+":"+startSec);
        $resizerWrapper.append($startTime);
        $resizerBottom.append($resizerBottomIcon);
        $resizerWrapper.append($resizerBottom);
        $endTime.html("End time: " + endMin + ":" + endSec);
        $resizerWrapper.append($endTime);
        $resizerWrapper.append($capInput);
        $captionWrapper.append($resizerWrapper);
      }
    }
    $captionWrapper.attr('data-start', cap.start.toString());
    $captionWrapper.attr('data-end', cap.end.toString());
    let start = Math.round($captionWrapper.attr('data-start'));
		let end = Math.round($captionWrapper.attr('data-end'));
		let numSeconds = end-start;
		let height = numSeconds * pixelsPerSecond;;
		$captionWrapper.css('height',height + 'px');
		$('.able-transcript').css('height','1500px');
		$('.able-transcript-area').css('height','1500px');
    div.append($captionWrapper);
    div.append(' \n');
  };
  function pad (str, max) {
	  str = str.toString();
	  return str.length < max ? pad("0" + str, max) : str;
	}

	var captionCount = 0;
	function resize_init(){
		makeResizableDiv();
		var i=0;
		var relOffsets=[];
		$('.resizable').each(function(){	
			$(this).prop('id','rs_' + captionCount);
			$(this).find(".startTime").prop('id','st_' + captionCount);
			$(this).find(".endTime").prop('id','et_' + captionCount);
			$(this).find(".captionEditInput").prop('id','cip_' + captionCount);
			/*  add space between non-concurrent captions */
			let prevCaption = $(this).prev('.resizable');
			// number of vertical pixels to add between non-concurrent captions
			// (number of seconds * pixelsPerSecond) 
			let whiteSpace = 0;
			if (typeof prevCaption.css('height') !== 'undefined') {
				// get current start in total number of seconds
				let currStartTime = $(this).find(".startTime");
				let stm_curr = currStartTime.html().match(/(\d{2})\:(\d{2})$/);
				let currStartMin = parseFloat(stm_curr[1]);
				let currStartSec = parseFloat(stm_curr[2]);
				let currStartSec_total = (currStartMin*60) + currStartSec;
				// get previous end in total number of seconds
				let prevEndTime = prevCaption.find(".endTime");;
				let stm_prev = prevEndTime.html().match(/(\d{2})\:(\d{2})$/);
				let prevEndMin = parseFloat(stm_prev[1]);
				let prevEndSec = parseFloat(stm_prev[2]);
				let prevEndSec_total = (prevEndMin*60) + prevEndSec;
				// calulate the vertical space to add between captions
				whiteSpace = (currStartSec_total - prevEndSec_total) * pixelsPerSecond;
			}
			/*  /add space between non-concurrent captions */
			var currTop = $( this ).position().top + whiteSpace;
			var currLeft = $( this ).position().left;
			relOffsets[captionCount] = {'top':currTop,'left':currLeft};
			captionCount++;
		});
		////console.log('relOffsets: ', relOffsets);
		for (let j = 0;j < relOffsets.length; j++) {
			$( '#rs_' + j ).css({position:'absolute',top:relOffsets[j].top});
		}
	}
	$(document).ready(function() { 
		timerID=setTimeout("resize_init()",1000);
  });

