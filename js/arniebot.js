(
	function ( $ ) {
		var arnieBot = {
			currentBotId: null,
			botWrapperClass: '.arniebot',
			userMessageClass: '.client-board__message',
			chatClass: '.arniebot__chat',
			interval: 15000, // 15 sec
			state: null,
			soundPath: null,
			init: function () {
				this.currentBotId = $( this.botWrapperClass ).data( 'id' );
				this.soundPath = $( this.botWrapperClass ).data( 'sound-path' );
				this.events();
				this.send( this.currentBotId, '' );
				this.runPolling();
			},
			events: function () {
				self = this;
				$( document ).on( 'click', '.client-board__message__send', function ( e ) {
					e.preventDefault();
					self.send( self.currentBotId, self.getUserMessage() );
					$( self.userMessageClass ).val('');
				} );

				$(document).on('keydown', function(e) {
					if (e.which == 13) {
						e.preventDefault();
						self.send( self.currentBotId, self.getUserMessage() );
						$( self.userMessageClass ).val('');
					}
				});
			},
			send: function ( botId, message ) { // send message to bot
				message = this.filterString(message);
				if(message !== ''){
					$( this.chatClass ).append( '<div class="arniebot__chat__message arniebot__chat__message--user">' + message + '</div>' );
				}
				var data = {'message': message, 'state': this.state},
					//if first request - POST, otherwise - PUT
					requestMethod = (
						this.state !== null
					) ? 'PUT' : 'POST';
				self = this;
				$.ajax( {
					type: requestMethod,
					url: '/wp-json/arnie/v1/bots/' + botId,
					dataType: 'json',
					data: data,
					success: function ( data ) {
						self.updateState( data.state );
						if ( data.response.length != 0 ) {
							$( self.chatClass ).append( '<div class="arniebot__chat__message arniebot__chat__message--bot">' + data.response + '</div>' );
							var audio = new Audio(self.soundPath);
							audio.play();
						}
					},
					error: function ( er ) {
						console.log( er );
					}
				} );
			},
			runPolling: function () {
				self = this;
				pollInterval = setInterval( function () {
					self.poll();
				}, self.interval );
			},
			getUserMessage: function () {
				return $( this.userMessageClass ).val();
			},
			poll: function () {
				this.send( this.currentBotId, '' );
			},
			updateState: function ( state ) {
				this.state = state;
			},
			filterString: function ( str ) {
				return str.replace(/<.*?>/g, "");
			}
		};

		arnieBot.init();
	}( jQuery )
);
