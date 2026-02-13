<?php

namespace Tests\wpunit;

use Codeception\Test\Unit;

class HelloShortcodeTest extends Unit
{
    public function testHelloShortcodeIsRegistered(): void
    {
        $this->assertTrue(
            shortcode_exists( 'HELLO' ),
            'The [HELLO] shortcode should be registered.'
        );
    }

    public function testHelloShortcodeReturnsHelloWorld(): void
    {
        $result = my_plugin_hello_shortcode();
        $this->assertSame( 'HELLO WORLD', $result );
    }

    public function testHelloShortcodeRendersViaDoShortcode(): void
    {
        $output = do_shortcode( '[HELLO]' );
        $this->assertSame( 'HELLO WORLD', $output );
    }
}
