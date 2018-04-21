( function ($) {
    var arnieBot = {
        currentBotId: null,
        botWrapperClass: '.arniebot',
        userMessageClass: '.client-board__message',
        chatClass: '.arniebot__chat',
        state: null,
        init: function() {
            this.currentBotId = $(this.botWrapperClass).data('bot-id');
            this.events();
        },
        events: function () {
            self = this;
            $(document).on('click', '.client-board__message__send', function(e){
                e.preventDefault();
                self.send(self.currentBotId, $(self.userMessageClass).val());
            });
        },
        send: function (botId, message) { // send message to bot
            $(this.chatClass).append('<div class="arniebot__chat__message arniebot__chat__message--user">' + message  + '</div>');
            var data = {'data': message, 'state':this.state},
                //if first request - POST, otherwise - PUT
            requestMethod = (this.state !== null)?'PUT':'POST';
            self = this;
            $.ajax({
                type: requestMethod,
                url: '/wp-json/arnie/v1/bots/'+ botId,
                dataType: 'json',
                data: data,
                success: function(data){
                    self.state = data.state;
                    $(self.chatClass).append('<div class="arniebot__chat__message arniebot__chat__message--bot">' + data.response  + '</div>');
                },
                error: function (er) {
                    console.log(er);
                }
            } );
        }
    };

    arnieBot.init();
}(jQuery) );
