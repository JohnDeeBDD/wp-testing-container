<?php

declare(strict_types=1);

namespace Tests\Support;

/**
 * Inherited Methods
 * @method void wantTo($text)
 * @method void wantToTest($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause($vars = [])
 *
 * @SuppressWarnings(PHPMD)
*/
class AcceptanceTester extends \Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * Log in to wp-admin.
     */
    public function loginAsAdmin(): void
    {
        $I = $this;
        $I->amOnPage('/wp-login.php');
        $I->fillField('#user_login', 'admin');
        $I->fillField('#user_pass', 'admin');
        $I->click('#wp-submit');
        $I->waitForElement('#wpadminbar', 10);
    }
}
