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

		$state = $bot->dump_state();
		$state['bid']   = $bot->ID;
		$state['cuid']  = 'one-two-three';

		$bot->load_state( $bot->sign_state( $state ) );

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

		$this->assertContains( $response[0],
			array( '<p>Hello 🙂</p>', '<p>Hey there! How can we help you today?</p>' )
		);

		$response = $bot->handle( '' );

		$this->assertEmpty( $bot->handle( '' ) );
	}

	public function test_idle() {
		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$state = $bot->dump_state();
		$state['last'] = time() - 30;
		$bot->load_state( $bot->sign_state( $state ) );

		$this->assertEmpty( $bot->handle( '' ) );

		$state = $bot->dump_state();
		$state['last'] = time() - 70;
		$bot->load_state( $bot->sign_state( $state ) );

		$response = $bot->handle( '' );

		$this->assertContains( $response,
			array(
				array( '<p>You still there?</p>' ),
				array( '<p>Hello?</p>' ),
				array( '<p>Silence is golden.</p>' ),
			)
		);

		$state = $bot->dump_state();
		$this->assertTrue( $state['idle'] );

		$state['last'] = time() - 170;
		$bot->load_state( $bot->sign_state( $state ) );

		$response = $bot->handle( '' );
		
		$this->assertEmpty( $response );
	}

	public function test_udc() {
		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$response = $bot->handle( '私はロボットです' );

		$this->assertContains( $response,
			array(
				array( '<p>Sorry what?</p>' ),
				array( "<p>Hey, I really don&#8217;t understand. Leave your name, number and/or e-mail and my human master will get back to you.</p>" ),
			)
		);

		$response = $bot->handle( '私はロボットです' );

		$this->assertContains( $response,
			array(
				array( '<p>Sorry what?</p>' ),
				array( "<p>Hey, I really don&#8217;t understand. Leave your name, number and/or e-mail and my human master will get back to you.</p>" ),
			)
		);
	}

	public function test_hello_udc() {
		$bot = Test_Utils::create_test_bot( self::factory() );

		$response = $bot->handle( '私はロボットです' );

		$this->assertContains( $response[0],
			array( '<p>Hello 🙂</p>', '<p>Hey there! How can we help you today?</p>' )
		);

		$this->assertContains( $response[1],
			array( '<p>Sorry what?</p>', "<p>Hey, I really don&#8217;t understand. Leave your name, number and/or e-mail and my human master will get back to you.</p>" )
		);
	}

	public function test_topic() {
		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$response = $bot->handle( 'Where are your   stores located  ?' );
		$this->assertEquals( '<p>We have various locations around the city 🙂</p>', $response[0] );
	}

	public function test_alert() {
		reset_phpmailer_instance();

		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$response = $bot->handle( 'Can you call me on +7 123 345 644, please?' );
		$this->assertEquals( '<p>Thanks! One of our humans will get back to you soon!</p>', $response[0] );

		$mailer = tests_retrieve_phpmailer_instance();
		$this->assertContains( 'call me', $mailer->get_sent()->body );
	}

	public function test_confirmation_and_goto() {
		$bot = Test_Utils::create_test_bot( self::factory() );
		$response = $bot->handle( '' );

		$response = $bot->handle( 'Help! One of your is being vandalized!' );
		$this->assertEquals( '<p>Oh, no! One of our locations has been vanalized?</p>', $response[0] );

		$state = $bot->dump_state();
		$this->assertNotEmpty( $state['confirm'] );

		$this->assertEmpty( $bot->handle( '' ) );
		$this->assertEmpty( $bot->handle( '' ) );

		$this->assertEmpty( $bot->handle( 'lol' ) );

		$this->assertEmpty( $bot->handle( 'no' ) );

		$response = $bot->handle( 'yes' );
		$this->assertEquals( '<p>We will investigate. Please leave your name, number, e-mail.</p>', $response[0] );

		$state = $bot->dump_state();
		$this->assertEquals( 'contact', $state['topic'] );
	}
}
