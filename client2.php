<?php
	//Get below values from $_GET
	if(isset($_GET['user_id']) AND $_GET['user_id'] != ''){
		$user_id = $_GET['user_id'];
	}
	else{
		exit();
	}
	
?>
<html>
<head>
</head>
<body>
	<input type="hidden" name="hidden_user_id" id="hidden_user_id" value="<?php echo $user_id; ?>" />
	<div id="accept_buttons">
		<button type="button" id ="accept_call" name="Accept_CAll" onclick="acceptCallInvite()" >Join Session</button>
	</div>
	<div id='videos'>
		<video id='localVideo' autoplay muted></video>
                <video id='remoteVideo' autoplay style="width: 100%,max-height: 400px;"></video>
	</div>
	<div id='textareas'>
		<textarea id="dataChannelSend" disabled placeholder="Press Start, enter some text, then press Send."></textarea>
		<textarea id="dataChannelReceive" disabled></textarea>
	</div>
	
	<button id="sendButton" disabled>Send</button>
	
	

<script src="https://cdn.socket.io/socket.io-1.2.0.js"></script>
<script src="https://code.jquery.com/jquery-1.11.1.js"></script>
<script src='js/lib/adapter.js'></script>

<script>
	var user_id = document.getElementById('hidden_user_id').value;
	 var socket = io.connect('https://efagcollege.co.uk:3000');
	//var socket = io.connect();
	var pc;
	var remoteStream;
	var localStream;
	
	var sendChannel;
var sendButton = document.getElementById("sendButton");
var sendTextarea = document.getElementById("dataChannelSend");
var receiveTextarea = document.getElementById("dataChannelReceive");

sendButton.onclick = sendData;
	
	var localVideo = document.querySelector('#localVideo');
	var remoteVideo = document.querySelector('#remoteVideo');
	function logError(err) {
		console.log(err.toString(), err);
	}
	
	var config = webrtcDetectedBrowser === 'firefox' ?
	  {'iceServers':[{'url':'stun:23.21.150.121'}]} : // number IP
	  {'iceServers': [{'url': 'stun:stun.l.google.com:19302'}]};
  
	function getMediaErrorCallback(error){
		console.log("getUserMedia error:", error);
	}

	//var video = document.getElementsByTagName('video')[0];
	
	function handleUserMediaError(error){
	  console.log('getUserMedia error: ', error);
	}
  
	function getMediaSuccessCallback(stream) {
		var streamURL = window.URL.createObjectURL(stream);
		console.log('getUserMedia video stream URL:', streamURL);
		window.stream = stream; // stream available to console

		video.src = streamURL;
	}
	
	function randomToken() {
		return Math.floor((1 + Math.random()) * 1e16).toString(16).substring(1);
	}
	
	
	

	$( "#call_btn" ).click(function() {
		
		var random_string = randomToken();
		
		// getUserMedia({video: true}, getMediaSuccessCallback, getMediaErrorCallback);
		// $.ajax({
			// url: 'http://localhost:3000/call_user/'+user_id+'/2/'+random_string,
			// type: 'get',
			// success: function(data) {
				// alert(data);
			// },
			// error: function(jqXHR, textStatus, errorThrown) {
				// alert('error ' + textStatus + " " + errorThrown);
			// }
		// });
  
		//return false;
		
		// On click the client2 initiates call to user 1 i.e client1
		socket.emit('call_initiate_channel',user_id,1);
	});
	
	
	function sendData() {
  var data = sendTextarea.value;
  sendChannel.send(data);
  trace('Sent data: ' + data);
}
  
  
	socket.on(user_id+'_notf_channel', function(msg,sender){
	
	
		if(msg == 'call_invite'){
			//$('#accept_buttons').html('<button type="button" id ="accept_call" name="Accept_CAll" onclick="acceptCallInvite()" >Accept Call</button>');
		
		
		}
		
		if(msg == 'call_acceptance'){
			console.log("Call acceptance aknowledgement Recieved");
			initiateCall();
		
		}
		
		if(msg == 'candidate_accepted'){
			console.log("Candidate aknowledgement Recieved");
			initiateSDP();
		
		}
		
	});
        
        socket.on(user_id+'_wait_notf', function(msg,sender){
            alert(msg);
	});
	
    socket.on(user_id+'candidate_acceptance_channel', function(message){
		console.log("accepting the ice candidate");
		var candidate = new RTCIceCandidate({sdpMLineIndex:message.label,
								candidate:message.candidate});
		pc.addIceCandidate(candidate);
		
		//socket.emit('candidate_acceptance_confirm',user_id,1);
		
	
	});
	
	


	
    socket.on(user_id+'_offer_acceptance', function(msg){
        
		console.log("Offer Request Recieved");
		
		//Creating RTC Peer Connection
		var pc_config = webrtcDetectedBrowser === 'firefox' ?
		  {'iceServers':[{'url':'stun:23.21.150.121'}]} : // number IP
		  {'iceServers': [{'url': 'stun:stun.l.google.com:19302'}]};

		var pc_constraints = {
		  'optional': [
			{'DtlsSrtpKeyAgreement': true},
			{'RtpDataChannels': true}
		  ]};

			// pc = new RTCPeerConnection(pc_config, pc_constraints);
		
		
			// pc.onicecandidate = handleIceCandidate;
			
			try {
				pc = new RTCPeerConnection(pc_config, pc_constraints);
				pc.onicecandidate = handleIceCandidate;
				console.log('Created RTCPeerConnnection with:\n' +
				  '  config: \'' + JSON.stringify(pc_config) + '\';\n' +
				  '  constraints: \'' + JSON.stringify(pc_constraints) + '\'.');
			  } catch (e) {
				console.log('Failed to create PeerConnection, exception: ' + e.message);
				alert('Cannot create RTCPeerConnection object.');
				  return;
			  }
  
			pc.onaddstream = handleRemoteStreamAdded;
			pc.onremovestream = handleRemoteStreamRemoved;
			 pc.ondatachannel = gotReceiveChannel;
			pc.addStream(localStream);
		
			pc.setRemoteDescription(new RTCSessionDescription(msg));
		
			console.log('Sending answer to peer.');
		
			var sdpConstraints = {'mandatory': {
				'OfferToReceiveAudio':true,
					'OfferToReceiveVideo':true }};
			pc.createAnswer(createAnswerCallBack, null, sdpConstraints);
	
	});
        
	
	function createAnswerCallBack(sessionDescription) {
	  // Set Opus as the preferred codec in SDP if Opus is present.
		sessionDescription.sdp = preferOpus(sessionDescription.sdp);
		pc.setLocalDescription(sessionDescription);
		
		socket.emit('answer_for_Offer_channel',sessionDescription,user_id,1);  
		
	}
  
  	function handleUserMedia(stream) {
	
	  localStream = stream;
	  attachMediaStream(localVideo, stream);
	  console.log('Adding local stream.');
	  socket.emit('call_acceptance_channel',user_id,1);
	}
	
	function handleUserMediaError(error){
	  console.log('getUserMedia error: ', error);
	}
	
  
function acceptCallInvite() {
  
	alert('You clicked accept call button')
	
	var constraints = {video: true,audio:true};
	getUserMedia(constraints, handleUserMedia, handleUserMediaError);
	

}
  
function gotReceiveChannel(event) {
  trace('Receive Channel Callback');
  sendChannel = event.channel;
  sendChannel.onmessage = handleMessage;
  sendChannel.onopen = handleReceiveChannelStateChange;
  sendChannel.onclose = handleReceiveChannelStateChange;
}

  
function handleMessage(event) {
  trace('Received message: ' + event.data);
  receiveTextarea.value = event.data;
}

function handleSendChannelStateChange() {
  var readyState = sendChannel.readyState;
  trace('Send channel state is: ' + readyState);
  enableMessageInterface(readyState == "open");
}

function handleReceiveChannelStateChange() {
  var readyState = sendChannel.readyState;
  trace('Receive channel state is: ' + readyState);
  enableMessageInterface(readyState == "open");
}

function enableMessageInterface(shouldEnable) {
    if (shouldEnable) {
    dataChannelSend.disabled = false;
    dataChannelSend.focus();
    dataChannelSend.placeholder = "";
    sendButton.disabled = false;
  } else {
    dataChannelSend.disabled = true;
    sendButton.disabled = true;
  }
}

function handleIceCandidate(event) {
  console.log('handleIceCandidate event: ', event);
  if (event.candidate) {
  var candidateInfo = {
      type: 'candidate',
      label: event.candidate.sdpMLineIndex,
      id: event.candidate.sdpMid,
      candidate: event.candidate.candidate};
  socket.emit('candidate_registry_channel_request',candidateInfo,user_id,1);
  } else {
    console.log('End of candidates.');
  }
}

function handleRemoteStreamAdded(event) {
  console.log('Remote stream added.');
 // reattachMediaStream(miniVideo, localVideo);
  attachMediaStream(remoteVideo, event.stream);
  remoteStream = event.stream;
//  waitForRemoteVideo();
}
function handleRemoteStreamRemoved(event) {
  console.log('Remote stream removed. Event: ', event);
}
  
  // Set Opus as the default audio codec if it's present.
	function preferOpus(sdp) {
	  var sdpLines = sdp.split('\r\n');
	  var mLineIndex;
	  // Search for m line.
	  for (var i = 0; i < sdpLines.length; i++) {
		  if (sdpLines[i].search('m=audio') !== -1) {
			mLineIndex = i;
			break;
		  }
	  }
	  if (mLineIndex === null) {
		return sdp;
	  }

	  // If Opus is available, set it as the default in m line.
	  for (i = 0; i < sdpLines.length; i++) {
		if (sdpLines[i].search('opus/48000') !== -1) {
		  var opusPayload = extractSdp(sdpLines[i], /:(\d+) opus\/48000/i);
		  if (opusPayload) {
			sdpLines[mLineIndex] = setDefaultCodec(sdpLines[mLineIndex], opusPayload);
		  }
		  break;
		}
	  }

	  // Remove CN in m line and sdp.
	  sdpLines = removeCN(sdpLines, mLineIndex);

	  sdp = sdpLines.join('\r\n');
	  return sdp;
	}
	
function extractSdp(sdpLine, pattern) {
  var result = sdpLine.match(pattern);
  return result && result.length === 2 ? result[1] : null;
}

// Set the selected codec to the first in m line.
function setDefaultCodec(mLine, payload) {
  var elements = mLine.split(' ');
  var newLine = [];
  var index = 0;
  for (var i = 0; i < elements.length; i++) {
    if (index === 3) { // Format of media starts from the fourth.
      newLine[index++] = payload; // Put target payload to the first.
    }
    if (elements[i] !== payload) {
      newLine[index++] = elements[i];
    }
  }
  return newLine.join(' ');
}

// Strip CN from sdp before CN constraints is ready.
function removeCN(sdpLines, mLineIndex) {
  var mLineElements = sdpLines[mLineIndex].split(' ');
  // Scan from end for the convenience of removing an item.
  for (var i = sdpLines.length-1; i >= 0; i--) {
    var payload = extractSdp(sdpLines[i], /a=rtpmap:(\d+) CN\/\d+/i);
    if (payload) {
      var cnPos = mLineElements.indexOf(payload);
      if (cnPos !== -1) {
        // Remove CN payload from m line.
        mLineElements.splice(cnPos, 1);
      }
      // Remove CN line in sdp
      sdpLines.splice(i, 1);
    }
  }

  sdpLines[mLineIndex] = mLineElements.join(' ');
  return sdpLines;
}
  
  
</script>
</body>
</html>