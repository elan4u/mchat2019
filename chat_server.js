var os = require('os');
//var util = require('util');
var mysql = require('mysql');
var static = require('node-static');
var http = require('http');
var fs = require('fs');
var url = require('url');
var fileServer = new (static.Server)();

var socketIO = require('socket.io');

var pool = mysql.createPool({
    connectionLimit: 100,
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'messages',
    debug: false
});

var app = http.createServer(function (req, res) {
    res.writeHead(200, {"Content-Type": "text/plain"});
    res.end("Hello World\n");
    fileServer.serve(req, res);
}).listen(3000);

var io = socketIO.listen(app);


io.sockets.on('connection', function (socket) {

    socket.on('chat_message', function (msg, sender, recipient) {
        console.log(msg);
        handle_database(msg, sender, recipient);
        io.emit(recipient + '_chat_message_' + sender, msg, sender);
    });

    function handle_database(msg, sender, recipient) {

        pool.getConnection(function (err, connection) {
            if (err) {
                connection.release();
                console.log("Error in connection database");
            }

            console.log('connected as id ' + connection.threadId);
            var post  = {sender_id: sender, recipient_id: recipient, message: msg };
            var query = connection.query("INSERT INTO tbl_messages SET ?", post, function (err, rows) {
                connection.release();
            });
            console.log(query.sql);

            connection.on('error', function (err) {
                console.log("Error in connection database");
            });
        });
    }

});
