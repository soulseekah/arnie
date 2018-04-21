<?php
namespace ARNIE_Chat_Bot;

class BotTest extends \WP_UnitTestCase {
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
		$bot_id = self::factory()->post->create( array(
			'post_type' => Bot::POST_TYPE,
		) );

		$bot = Bot::get( $bot_id );

		$uid = $bot->get_CUID();
		$this->assertNotEmpty( $uid );

		$this->assertEquals( $uid, $bot->get_CUID() );
		$this->assertNotEquals( $uid, $bot->reset()->get_CUID() );
	}

	public function test_save_load() {
		$bot_id = self::factory()->post->create( array(
			'post_type' => Bot::POST_TYPE,
		) );

		$bot = Bot::get( $bot_id );

		$state = $bot->dump_state();

		$this->assertEquals( $bot_id, $state['bid'] );
		$this->assertEquals( $bot->get_CUID(), $state['cuid'] );

		$bot = Bot::get( $bot_id );
		$bot->load_state( array(
			'bid'  => $bot->ID,
			'cuid' => 'one-two-three',
		) );

		$this->assertEquals( $bot->get_CUID(), 'one-two-three' );
	}

	public function test_load_invalid() {
		$bot_id = self::factory()->post->create( array(
			'post_type' => Bot::POST_TYPE,
		) );
		$bot = Bot::get( $bot_id );

		$this->expectException( \Exception::class );

		$bot->load_state( array(
			'bid'  => $bot_id + 1,
			'cuid' => 'one-two-three',
		) );
	}
}
