<?php

namespace Tests\wpunit;

use Codeception\Test\Unit;

class PluginActivationTest extends Unit
{
    public function testPluginIsActive(): void
    {
        $this->assertTrue(
            is_plugin_active( 'my-plugin/my-plugin.php' ),
            'The my-plugin plugin should be active.'
        );
    }

    public function testGreetReturnsExpectedString(): void
    {
        $this->assertSame( 'Hello, World!', my_plugin_greet( 'World' ) );
    }

    public function testGreetWithEmptyName(): void
    {
        $this->assertSame( 'Hello, !', my_plugin_greet( '' ) );
    }

    public function testBookPostTypeIsRegistered(): void
    {
        $this->assertTrue(
            post_type_exists( 'book' ),
            'The "book" custom post type should be registered.'
        );
    }

    public function testCanInsertBookPost(): void
    {
        $post_id = wp_insert_post( [
            'post_type'   => 'book',
            'post_title'  => 'Test Book',
            'post_status' => 'publish',
        ] );

        $this->assertIsInt( $post_id );
        $this->assertGreaterThan( 0, $post_id );

        $post = get_post( $post_id );
        $this->assertSame( 'Test Book', $post->post_title );
        $this->assertSame( 'book', $post->post_type );

        // Clean up
        wp_delete_post( $post_id, true );
    }
}
