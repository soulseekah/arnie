<?php
namespace ARNIE_Chat_Bot;

class BotTest extends \WP_UnitTestCase {
	public static function create_test_bot() {
		$bot_id = self::factory()->post->create( array(
			'post_type' => Bot::POST_TYPE,
		) );

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

		return Bot::get( $bot_id );
	}

	public function test_get() {
		$post_id = self::factory()->post->create( array() );

		$this->assertNull( Bot::get( $post_id ) );
		$this->assertNull( Bot::get( $post_id + 1  ) );
		
		$bot_id = self::factory()->post->create( array(
			'post_type' => Bot::POST_TYPE,
		) );

		$this->assertNotNull( $bot = Bot::get( $bot_id ) );
		$this->assertEquals( $bot_id, $bot->ID );
	}

	public function test_cuuid() {
		$bot = self::create_test_bot();

		$uid = $bot->get_CUID();
		$this->assertNotEmpty( $uid );

		$this->assertEquals( $uid, $bot->get_CUID() );
		$this->assertNotEquals( $uid, $bot->reset()->get_CUID() );
	}

	public function test_save_load() {
		$bot = self::create_test_bot();

		$state = $bot->dump_state();

		$this->assertEquals( $bot->ID, $state['bid'] );
		$this->assertEquals( $bot->get_CUID(), $state['cuid'] );

		$bot = Bot::get( $bot->ID );
		$bot->load_state( array(
			'bid'  => $bot->ID,
			'cuid' => 'one-two-three',
		) );

		$this->assertEquals( $bot->get_CUID(), 'one-two-three' );
	}

	public function test_load_invalid() {
		$bot = self::create_test_bot();

		$this->expectException( \Exception::class );

		$bot->load_state( array(
			'bid'  => $bot->ID + 1,
			'cuid' => 'one-two-three',
		) );
	}

	public function test_hello() {
		$bot = self::create_test_bot();

		$response = $bot->handle( '' );

		$this->assertContains( $response,
			array(
				array( 'Hello :)' ),
				array( 'Hey there!', 'How can we help you today?' ),
			)
		);

		$response = $bot->handle( '' );

		$this->assertEmpty( $bot->handle( '' ) );
	}

}
