<?php
namespace ARNIE_Chat_Bot;

class Test_Utils {
	public static function create_test_bot( $factory ) {
		$bot_id = $factory->post->create( array(
			'post_type' => Bot::POST_TYPE,
		) );

		/**
		 * Hello responses.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['hello_responses']
			. '[0]/' . Bot::$FIELDS['generics']['hello_response']
			. '[0]/' . Bot::$FIELDS['generics']['hello_response_line'],
			'Hello :)'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['hello_responses']
			. '[1]/' . Bot::$FIELDS['generics']['hello_response']
			. '[0]/' . Bot::$FIELDS['generics']['hello_response_line'],
			'Hey there!'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['hello_responses']
			. '[1]/' . Bot::$FIELDS['generics']['hello_response']
			. '[1]/' . Bot::$FIELDS['generics']['hello_response_line'],
			'How can we help you today?'
		);

		/**
		 * Idle responses.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['idle_responses']
			. '[0]/' . Bot::$FIELDS['generics']['idle_response']
			. '[0]/' . Bot::$FIELDS['generics']['idle_response_line'],
			'You still there?'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['idle_responses']
			. '[1]/' . Bot::$FIELDS['generics']['idle_response']
			. '[0]/' . Bot::$FIELDS['generics']['idle_response_line'],
			'Hello?'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['idle_responses']
			. '[2]/' . Bot::$FIELDS['generics']['idle_response']
			. '[0]/' . Bot::$FIELDS['generics']['idle_response_line'],
			'Silence is golden.'
		);

		/**
		 * UDC responses.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['udc_responses']
			. '[0]/' . Bot::$FIELDS['generics']['udc_response']
			. '[0]/' . Bot::$FIELDS['generics']['udc_response_line'],
			'Sorry what?'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['udc_responses']
			. '[1]/' . Bot::$FIELDS['generics']['udc_response']
			. '[0]/' . Bot::$FIELDS['generics']['udc_response_line'],
			"Hey, I really don't understand. Leave your name, number and/or e-mail and my human master will get back to you."
		);

		return Bot::get( $bot_id );
	}
}
