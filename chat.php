<?php
    //Get below values from $_GET
    if(isset($_GET['user_id']) AND $_GET['user_id'] != ''){
        $user_id = $_GET['user_id'];
    }
    else{
        exit();
    }
?>
<!doctype html>
<html>
  <head>
    <title>Chat Example</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font: 13px Helvetica, Arial; }
        form { background: #000; padding: 3px; position: fixed; bottom: 0; width: 50%; }
        form input { border: 0; padding: 10px; width: 90%; margin-right: .5%; }
        form button { width: 9%;  background: rgb(206, 35, 12); border: none; padding: 10px; }
        #messages { list-style-type: none; margin: 0; padding: 0; }
        #messages li { padding: 5px 10px; }
        .my_messages{
            background: #BBE892;
            float: right;
            border-radius: 5px;
        }
        
        .user_messages{
            background: gainsboro;
            float: left;
            border-radius: 5px;
        }
    </style>
  </head>
  <body>
        <div style="width: 50%;">
            <input type="hidden" name="hidden_user_id" id="hidden_user_id" value="<?php echo $user_id; ?>" />
            <ul id="messages" style=""></ul>
            <form action="">
              <input id="text_message" autocomplete="off" placeholder="Type here" /><button>Send</button>
            </form>
        </div>
    <script src="https://cdn.socket.io/socket.io-1.2.0.js"></script>
    <script src="http://code.jquery.com/jquery-1.11.1.js"></script>
    <script>
        var user_id = document.getElementById('hidden_user_id').value;
        if(user_id == 1){
            var recipient_user_id = 2;
        }else{
            var recipient_user_id = 1;
        }
        var socket = io.connect('http://192.168.1.12:3000');
        $('form').submit(function(){
            socket.emit('chat_message', $('#text_message').val(),user_id,recipient_user_id);
            $('#messages').append($('<li class="my_messages">').text($('#text_message').val()));
            $('#messages').append($('<br/><br/><br/>'));
            $('#text_message').val('');
            return false;
        });
        socket.on(user_id+'_chat_message_'+recipient_user_id, function(msg,sender){
            $('#messages').append($('<li class="user_messages">').text(msg));
            $('#messages').append($('<br/><br/><br/>'));
        });
    </script>
  </body>
</html>
