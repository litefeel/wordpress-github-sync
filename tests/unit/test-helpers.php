<?php

class Writing_On_GitHub_Helpers_Test extends Writing_On_GitHub_TestCase {

	/**
	 * @var Writing_On_GitHub_Post
	 */
	public $post;

	public function setUp() {
		global $post;
		parent::setUp();
		$post       = $this->factory->post->create_and_get();
		$this->post = new Writing_On_GitHub_Post( $post->ID, $this->api );
		$this->fetch
			->shouldReceive( 'repository' )
			->andReturn( 'owner/repo' );
	}

	public function test_should_return_global_post_view_url() {
		$this->assertEquals( $this->post->github_view_url(), get_the_github_view_url() );
	}

	public function test_should_return_global_post_edit_url() {
		$this->assertEquals( $this->post->github_edit_url(), get_the_github_edit_url() );
	}

	public function test_should_return_global_post_view_link() {
		$this->assertEquals(
			sprintf( '<a href="%s">%s</a>', get_the_github_view_url(), 'View this post on GitHub.' ),
			get_the_github_view_link()
		);
	}

	public function test_should_return_global_post_edit_link() {
		$this->assertEquals(
			sprintf( '<a href="%s">%s</a>', get_the_github_edit_url(), 'Edit this post on GitHub.' ),
			get_the_github_edit_link()
		);
	}
}
