<?php

namespace Tests\acceptance;

use Tests\Support\AcceptanceTester;

class PluginFrontendCest
{
    public function homepageLoads(AcceptanceTester $I): void
    {
        $I->wantTo('verify the WordPress homepage loads');
        $I->amOnPage('/');
        $I->see('Test Site');
    }

    public function adminLoginWorks(AcceptanceTester $I): void
    {
        $I->wantTo('log in to wp-admin');
        $I->loginAsAdmin();
        $I->see('Dashboard');
    }

    public function pluginAppearsOnPluginsPage(AcceptanceTester $I): void
    {
        $I->wantTo('verify the plugin is listed and active');
        $I->loginAsAdmin();
        $I->amOnPage('/wp-admin/plugins.php');
        $I->see('My Plugin');
    }

    public function bookPostTypeVisibleInAdmin(AcceptanceTester $I): void
    {
        $I->wantTo('verify the Book CPT menu appears in wp-admin');
        $I->loginAsAdmin();
        $I->amOnPage('/wp-admin/edit.php?post_type=book');
        $I->see('Books');
    }
}
