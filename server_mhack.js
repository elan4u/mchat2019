var os = require('os');
//var util = require('util');
var static = require('node-static');
var http = require('http');
var fs =    require('fs'); 
var url = require('url');
var fileServer = new(static.Server)();

var socketIO = require('socket.io');
var options = {
    key:    fs.readFileSync('/etc/ssl/certs/marlabsqbteam/marlabsqbteam.com.key'),
    ca:   fs.readFileSync('/etc/ssl/certs/marlabsqbteam/marlabsqbteam.com.crt'),
    cert:     fs.readFileSync('/etc/ssl/certs/marlabsqbteam/marlabsqbteam.com.crt')
};

var app = http.createServer(function (req, res) {
    res.writeHead(200, {"Content-Type": "text/plain"});
  res.end("Hello World\n");
  fileServer.serve(req, res);
}).listen(3001);

var io = socketIO.listen(app);
var faculty_array = [];
io.sockets.on('connection', function (socket){
    
        function inArray(needle, haystack) {
            var length = haystack.length;
            for(var i = 0; i < length; i++) {
                if(haystack[i] == needle) return true;
            }
            return false;
        }
        
        console.log("Connected");
	
	

	socket.on('send_message',function(msg,user_id){
		console.log(msg);
		io.emit('receive_message', msg,user_id);

	});
        
	
	
	socket.on('msg_channel', function (user_id,msg) {
		console.log(msg);
		io.emit('receive_msg', msg);
                
	});
	
	socket.on('call_initiate_channel', function (sender,recipient) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		console.log("call initiated_"+"_sender="+sender+"=====_reciever_"+recipient);
		var msg = 'call_invite';
		io.emit(recipient+'_notf_channel', msg,sender);
	});
        
	socket.on('faculty_joined', function (recipient,room) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		console.log("Faculty Joined");
		//console.log(io.sockets.clients('faculty_1').length);
                faculty_array.push('faculty_1');
                var msg = 'Session started, Click join button to join the session';
                io.emit(recipient+'_wait_notf', msg,1);
                
	});
	
	
	socket.on('call_acceptance_channel', function (sender,recipient) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		console.log("call_acceptance_channel"+"_sender="+sender+"=====_reciever_"+recipient);
                
                if (!inArray('faculty_1',faculty_array)){
                    console.log("Not in array");
                    var msg = 'Please wait, session is not started';
                    io.emit(sender+'_wait_notf', msg,sender);
                }else{
                    console.log("In array");
                    var msg = 'call_acceptance';
                    io.emit(recipient+'_notf_channel', msg,sender);
                }
	});
	
	
	socket.on('candidate_registry_channel_request', function (candidate_info,sender,recipient) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		//console.log("call initiated_"+"_sender="+sender+"=====_reciever_"+recipient);
		var msg = candidate_info;
		io.emit(recipient+'candidate_acceptance_channel', msg,sender);
	});
	
	socket.on('candidate_acceptance_confirm', function (sender,recipient) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		//console.log("call initiated_"+"_sender="+sender+"=====_reciever_"+recipient);
		var msg = "candidate_accepted";
		io.emit(recipient+'_notf_channel', msg,sender);
	});
	
	socket.on('offer_request', function (session_description,sender,recipient) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		//console.log("call initiated_"+"_sender="+sender+"=====_reciever_"+recipient);
		var msg = session_description;
		io.emit(recipient+'_offer_acceptance', msg);
	});
	
	socket.on('answer_for_Offer_channel', function (session_description,sender,recipient) {
		//console.log('Got message: ', userid);
		//socket.broadcast.emit('message_'+room, message); // should be room only
		//console.log("call initiated_"+"_sender="+sender+"=====_reciever_"+recipient);
		var msg = session_description;
		io.emit(recipient+'_answer_acceptance', msg,sender);
	});
	
	
	
});