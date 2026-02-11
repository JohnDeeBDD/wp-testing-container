<?php

namespace Tests\acceptance;

use Tests\Support\AcceptanceTester;

class HelloWorldCest
{
    public function helloWorldTest(AcceptanceTester $I): void
    {
        $I->wantTo('see Hello World on the WordPress homepage');
        $I->amOnPage('/');
        $I->see('Test Site');
    }
}
