<?php
class RESTTest extends WP_UnitTestCase {
	public function test_register_routes() {
		$routes = rest_get_server()->get_routes();
		$this->assertArrayHasKey( '/arnie/v1/bots/(?P<id>[\d]+)', $routes );
	}

	public function test_start_conversation_no_such_bot() {
		$request = new WP_REST_Request( 'POST', '/arnie/v1/bots/1' );
		$request->add_header( 'content-type', 'application/json' );

		$data = array(
		);

		$request->set_body( wp_json_encode( $data ) );
		$response = rest_get_server()->dispatch( $request );
	}
}
