<?php
namespace ARNIE_Chat_Bot;

class Bot_Test extends \WP_UnitTestCase {
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
		$bot = Test_Utils::create_test_bot( self::factory() );

		$uid = $bot->get_CUID();
		$this->assertNotEmpty( $uid );

		$this->assertEquals( $uid, $bot->get_CUID() );
		$this->assertNotEquals( $uid, $bot->reset()->get_CUID() );
	}

	public function test_save_load() {
		$bot = Test_Utils::create_test_bot( self::factory() );

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
		$bot = Test_Utils::create_test_bot( self::factory() );

		$this->expectException( \Exception::class );

		$bot->load_state( array(
			'bid'  => $bot->ID + 1,
			'cuid' => 'one-two-three',
		) );
	}

	public function test_hello() {
		$bot = Test_Utils::create_test_bot( self::factory() );

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

	public function test_idle() {
		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$state = $bot->dump_state();
		$state['last'] = time() - 30;
		$bot->load_state( $state );

		$this->assertEmpty( $bot->handle( '' ) );

		$state = $bot->dump_state();
		$state['last'] = time() - 70;
		$bot->load_state( $state );

		$response = $bot->handle( '' );

		$this->assertContains( $response,
			array(
				array( 'You still there?' ),
				array( 'Hello?' ),
				array( 'Silence is golden.' ),
			)
		);

		$state = $bot->dump_state();
		$this->assertTrue( $state['idle'] );

		$state['last'] = time() - 170;
		$bot->load_state( $state );

		$response = $bot->handle( '' );
		
		$this->assertEmpty( $response );
	}

	public function test_udc() {
		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$response = $bot->handle( '私はロボットです' );

		$this->assertContains( $response,
			array(
				array( 'Sorry what?' ),
				array( "Hey, I really don't understand. Leave your name, number and/or e-mail and my human master will get back to you." ),
			)
		);
	}
}
