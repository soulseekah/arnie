<?php
namespace ARNIE_Chat_Bot;

class REST_Test extends \WP_UnitTestCase {
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/arnie/v1/bots/(?P<id>[\d]+)', $routes );
	}

	public function test_start_conversation_no_such_bot() {
		$request = new \WP_REST_Request( 'POST', '/arnie/v1/bots/1' );
		$request->add_header( 'content-type', 'application/json' );

		$data = array(
		);

		$request->set_body( wp_json_encode( $data ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		$data = $response->get_data();
		$this->assertEquals( 'not_found', $data['code'] );
	}

	public function test_start_conversation_invalid_state() {
		$bot = Test_Utils::create_test_bot( self::factory() );

		$request = new \WP_REST_Request( 'PUT', '/arnie/v1/bots/' . $bot->ID );
		$request->add_header( 'content-type', 'application/json' );

		$data = array(
			'state' => base64_encode( json_encode( array(
				'bid' => '2'
			) ) ),
		);

		$request->set_body( wp_json_encode( $data ) );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 500, $response->status );

		$data = $response->get_data();
		$this->assertEquals( 'invalid_state', $data['code'] );
	}

	public function test_start_conversation() {
		$bot = Test_Utils::create_test_bot( self::factory() );

		$request = new \WP_REST_Request( 'POST', '/arnie/v1/bots/' . $bot->ID );
		$request->add_header( 'content-type', 'application/json' );
		$response = rest_get_server()->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$data = $response->get_data();
		$state = json_decode( base64_decode( $data['state'] ), true );

		$this->assertEquals( array( 'bid', 'cuid', 'topic', 'last', 'log' ), array_keys( $state ) );
		$this->assertEquals( $bot->ID, $state['bid'] );
		$this->assertLessThan( 3, abs( $state['last'] - time() ) );
		$this->assertNotEmpty( $state['log'] );
		$this->assertNotEmpty( $state['cuid'] );
	}
}
