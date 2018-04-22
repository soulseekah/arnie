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

		/**
		 * Stopwords.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['stopwords'], " are,your ,you're,I,am" );

		/**
		 * Humans.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['humans'], "test@localhost.lo" );

		/**
		 * Topics.
		 */
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[0]/' . Bot::$FIELDS['topic_id'],
			'location'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[0]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_pattern'],
			'where,locat.*,city,places?'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[0]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_responses']
			. '[0]/' . Bot::$FIELDS['topic_response'],
			'We have various locations around the city :)'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[1]/' . Bot::$FIELDS['topic_id'],
			'vandalism'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[1]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_pattern'],
			'br(eak|oke).*,burn.*,damage.*,vandal.*'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[1]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_confirmation'],
			'Oh, no! One of our locations has been vanalized?'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[1]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_goto'],
			'contact'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[1]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_responses']
			. '[0]/' . Bot::$FIELDS['topic_response'],
			'We will investigate. Please leave your name, number, e-mail.'
		);

		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[2]/' . Bot::$FIELDS['topic_id'],
			'contact'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[2]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_pattern'],
			'\\\\d+,\\\\s+@\\\\s+\\\\.\\\\s+'
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[2]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_alert'],
			true
		);
		carbon_set_post_meta( $bot_id, Bot::$FIELDS['topics']
			. '[2]/' . Bot::$FIELDS['topic_sets']
			. '[0]/' . Bot::$FIELDS['topic_responses']
			. '[0]/' . Bot::$FIELDS['topic_response'],
			'Thanks! One of our humans will get back to you soon!'
		);

		return Bot::get( $bot_id );
	}
}
