<?php
    //Get user id 
    if (isset($_GET['user_id']) AND $_GET['user_id'] != '') {
        $user_id = $_GET['user_id'];
    } else {
        exit();
    }
?>
<html>
    <head>
    </head>
    <body>
        <input type="button" value="Start Session" id="call_btn" />
        <input type="hidden" name="hidden_user_id" id="hidden_user_id" value="<?php echo $user_id; ?>" />

        <?php
        $sudentsArray = array();
        $sudentsArray = [2, 3, 4, 5, 6, 7, 8, 9, 10, 11];

        if (sizeof($sudentsArray) > 0) {
            $jsonString = json_encode($sudentsArray);
            //var_dump($jsonString);
            $jsonString = htmlspecialchars($jsonString);
            echo '<input type="hidden" id="hidden_students_data" name="hidden_students_data" value="' . $jsonString . '" />';
        }
        ?>

        <div id='videos'>
            <video id='localVideo' autoplay muted ></video>
            <?php
            foreach ($sudentsArray as $student_id) {
                echo "<video id='remoteVideo" . $student_id . "' autoplay><track>Monthly savings</track></video>";
            }
            ?>
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
            //Current user id
            var user_id = document.getElementById('hidden_user_id').value;
            
            //List of users for the chat sesssion
            var details = document.getElementById('hidden_students_data').value;
            var studentsDataArray = JSON.parse(details);
            var connectionArray = new Array();
            
            //Local video of the user
            var localVideo = document.querySelector('#localVideo');
            
            //To generate different global variables for different peer connection  
            for (var i = 0; i < studentsDataArray.length; i++) {
                
                //Global declaration for peer names
                var name = 'pc';
                var peer_name = peer_name + studentsDataArray[i];
                
                
                //Global declaration for remote videos
                var remote_vid_name = 'remoteVideo';
                var remoteVIdeoName = remote_vid_name + studentsDataArray[i];
                remoteVIdeoName = document.querySelector('#remoteVideo' + studentsDataArray[i]);
                
                //Global declaration for remote streams
                var remote_stream_name = 'remoteStream';
                var remoteStreamName = remote_stream_name + studentsDataArray[i];

                connectionArray[studentsDataArray[i]] = new Array();


                connectionArray[studentsDataArray[i]]['name'] = peer_name;
                connectionArray[studentsDataArray[i]]['remote_video'] = remoteVIdeoName;
                connectionArray[studentsDataArray[i]]['remote_stream'] = remoteStreamName;

            }

            //Socket connection
            // var socket = io.connect('http://localhost:3000');
//			var socket = io.connect('https://efagcollege.co.uk:3000');
            var socket = io.connect('https://efagcollege.co.uk:3000');
            
            var localStream;
            var sendChannel;
            var sendButton = document.getElementById("sendButton");
            var sendTextarea = document.getElementById("dataChannelSend");
            var receiveTextarea = document.getElementById("dataChannelReceive");
            
            //Sending textarea message
            sendButton.onclick = sendData;

            //After conncetion of the students 'isStarted' will be set to true
            isStarted = false;

            function logError(err) {
                console.log(err.toString(), err);
            }
            
            //COnfiguration of signalling Servers
            var config = webrtcDetectedBrowser === 'firefox' ?
                    {'iceServers': [{'url': 'stun:23.21.150.121'}]} : // number IP
                    {'iceServers': [{'url': 'stun:stun.l.google.com:19302'}]};

            
            
            //Getusermedia success callback function
            function handleUserMedia(stream) {
                //Get the stream from webcam & attach to the video element
                localStream = stream;
                attachMediaStream(localVideo, stream);
                var room = "room1";
                for (var i = 0; i < studentsDataArray.length; i++) {
                    socket.emit('faculty_joined', user_id,studentsDataArray[i]);
                }
                console.log('Adding local stream.');

            }
            
            //Getusermedia error callback function
            function handleUserMediaError(error) {
                console.log('getUserMedia error: ', error);
            }
            
            
            //Initiating the call, Sending notification to session users(calling to users)
            $("#call_btn").click(function () {
                
                //Constraints for getusermedia (video,audio)
                var constraints = {video: true, audio: true};
                
                //To get access for user's webcam and microphone
                getUserMedia(constraints, handleUserMedia, handleUserMediaError);
                
                //Sending notification to all the users of a session
                for (var i = 0; i < studentsDataArray.length; i++) {
                    socket.emit('call_initiate_channel', user_id, studentsDataArray[i]);
                }

            });



            //Receiving notification Channel
            socket.on(user_id + '_notf_channel', function (msg, student_id) {
                
                //If the user(callee) accepts the call
                if (msg == 'call_acceptance') {
                    
                    console.log("Call acceptance aknowledgement Recieved");
                    
                    //Initilization of the call
                    initiateCall(student_id);

                }
                
                //If the user(callee) receives the candidate
                if (msg == 'candidate_accepted') {
                    console.log("Candidate aknowledgement Recieved");
                }

            });

            
            //Session description from other users
            socket.on(user_id + '_answer_acceptance', function (sessionDescription, student_id) {
                //Set remote description
                connectionArray[studentsDataArray[student_id]]['name'].setRemoteDescription(new RTCSessionDescription(sessionDescription));

            });

            //ICE candidates from other users
            socket.on(user_id + 'candidate_acceptance_channel', function (message, student_id) {
                console.log("accepting the ice candidate");
                var candidate = new RTCIceCandidate({sdpMLineIndex: message.label,
                    candidate: message.candidate});
                connectionArray[studentsDataArray[student_id]]['name'].addIceCandidate(candidate);

            });


            function sendData() {
                var data = sendTextarea.value;
                sendChannel.send(data);
                trace('Sent data: ' + data);
            }

            function initiateCall(student_id) {
                
                //Configurations for peer connections
                var pc_config = webrtcDetectedBrowser === 'firefox' ?
                    {'iceServers': [{'url': 'stun:23.21.150.121'}]} : // number IP
                    {'iceServers': [{'url': 'stun:stun.l.google.com:19302'}]};
                
                //Contraints for peer connections
                var pc_constraints = {
                    'optional': [
                        {'DtlsSrtpKeyAgreement': true},
                        {'RtpDataChannels': true}
                    ]};


                try {
                    // Creating RTC Peer Connection
                    connectionArray[studentsDataArray[student_id]]['name'] = new RTCPeerConnection(pc_config, pc_constraints);
                    isStarted = true;
                    
                    //onicecandidate event
                    connectionArray[studentsDataArray[student_id]]['name'].onicecandidate = function (event) {
                        console.log('handleIceCandidate event: ', event);
                        if (event.candidate) {
                            
                            var candidateInfo = {
                                type: 'candidate',
                                label: event.candidate.sdpMLineIndex,
                                id: event.candidate.sdpMid,
                                candidate: event.candidate.candidate};
                            
                            socket.emit('candidate_registry_channel_request', candidateInfo, user_id, student_id);
                            
                        } else {
                            console.log('End of candidates.');
                        }
                    };

                } catch (e) {

                    alert('Cannot create RTCPeerConnection object.' + e.message);
                    return;

                }
                
                //onaddstream event
                connectionArray[studentsDataArray[student_id]]['name'].onaddstream = function (event) {
                    console.log('Remote stream added.');

                    attachMediaStream(connectionArray[studentsDataArray[student_id]]['remote_video'], event.stream);
                    connectionArray[studentsDataArray[student_id]]['remote_stream'] = event.stream;
                };
                
                //onremovestream event
                connectionArray[studentsDataArray[student_id]]['name'].onremovestream = function (event) {
                    console.log('Remote stream removed. Event: ', event);
                };
                
                //Creating Data channel for data transfer
                try {
                    // Reliable Data Channels not yet supported in Chrome
                    sendChannel = connectionArray[studentsDataArray[student_id]]['name'].createDataChannel("sendDataChannel",
                            {reliable: false});
                    sendChannel.onmessage = handleMessage;
                    trace('Created send data channel');
                } catch (e) {
                    alert('Failed to create data channel. ' +
                            'You need Chrome M25 or later with RtpDataChannel enabled');
                    trace('createDataChannel() failed with exception: ' + e.message);
                }
                sendChannel.onopen = handleSendChannelStateChange;
                sendChannel.onclose = handleSendChannelStateChange;
                
                //Adding local stream to the peer connection
                connectionArray[studentsDataArray[student_id]]['name'].addStream(localStream);
                
                //After peer and data channel creation, we have to create offer for the callee
                initiateSDP(student_id);

            }
            
            
            //Creating offer for the callee(Sending session description)
            function initiateSDP(student_id) {
                
                var constraints = {'optional': [], 'mandatory': {'MozDontOfferDataChannel': true}};
                // temporary measure to remove Moz* constraints in Chrome
                if (webrtcDetectedBrowser === 'chrome') {
                    for (var prop in constraints.mandatory) {
                        if (prop.indexOf('Moz') !== -1) {
                            delete constraints.mandatory[prop];
                        }
                    }
                }

                var sdpConstraints = {'mandatory':
                            {
                                'OfferToReceiveAudio': true,
                                'OfferToReceiveVideo': true
                            }
                };

                constraints = mergeConstraints(constraints, sdpConstraints);
                
                //create offer event
                connectionArray[studentsDataArray[student_id]]['name'].createOffer(function (sessionDescription) {
                    
                    connectionArray[studentsDataArray[student_id]]['name'].setLocalDescription(sessionDescription, function () {
                        
                        // Set Opus as the preferred codec in SDP if Opus is present.
                        sessionDescription.sdp = preferOpus(sessionDescription.sdp);
                        connectionArray[studentsDataArray[student_id]]['name'].setLocalDescription(sessionDescription);
                        socket.emit('offer_request', sessionDescription, user_id, student_id);
                        
                    });
                    
                }, null, constraints);


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
            
            //<-- Video quality and other options related to video quality -->//
            
            function mergeConstraints(cons1, cons2) {
                var merged = cons1;
                for (var name in cons2.mandatory) {
                    merged.mandatory[name] = cons2.mandatory[name];
                }
                merged.optional.concat(cons2.optional);
                return merged;
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
                for (var i = sdpLines.length - 1; i >= 0; i--) {
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