<?php

namespace Tests\acceptance;

use Tests\Support\AcceptanceTester;

class HelloShortcodeCest
{
    public function shortcodeRendersOnPage(AcceptanceTester $I): void
    {
        $I->wantTo('verify the [HELLO] shortcode renders HELLO WORLD on a page');
        $I->amOnPage('/?pagename=shortcode-test');
        $I->see('HELLO WORLD');
    }
}
