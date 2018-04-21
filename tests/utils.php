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
			. '[0]/' . Bot::$FIELDS['generics']['hello_response'],
			'Hello :)'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['hello_responses']
			. '[1]/' . Bot::$FIELDS['generics']['hello_response'],
			'Hey there! How can we help you today?'
		);

		/**
		 * Idle responses.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['idle_responses']
			. '[0]/' . Bot::$FIELDS['generics']['idle_response'],
			'You still there?'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['idle_responses']
			. '[1]/' . Bot::$FIELDS['generics']['idle_response'],
			'Hello?'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['idle_responses']
			. '[2]/' . Bot::$FIELDS['generics']['idle_response'],
			'Silence is golden.'
		);

		/**
		 * UDC responses.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['udc_responses']
			. '[0]/' . Bot::$FIELDS['generics']['udc_response'],
			'Sorry what?'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['generics']['udc_responses']
			. '[1]/' . Bot::$FIELDS['generics']['udc_response'],
			"Hey, I really don't understand. Leave your name, number and/or e-mail and my human master will get back to you."
		);

		return Bot::get( $bot_id );
	}
}
