<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\MinkExtension\Context\RawMinkContext;
use PHPUnit\Framework\Assert;

/**
 * Defines application features from the subproduct pages context.
 */
class SubproductContext extends RawMinkContext implements Context
{
    /**
     * @Given the application is configured properly
     */
    public function theApplicationIsConfiguredProperly()
    {
        // Clear any caches that might cause route issues
        exec('php artisan route:clear');
        exec('php artisan config:clear');
        exec('php artisan view:clear');
    }

    /**
     * @Then I should see a link to :text pointing to :url
     */
    public function iShouldSeeALinkToPointingTo($text, $url)
    {
        $page = $this->getSession()->getPage();
        $link = $page->findLink($text);
        
        Assert::assertNotNull($link, "Link with text '$text' not found");
        
        $href = $link->getAttribute('href');
        Assert::assertStringContainsString($url, $href, "Link does not point to expected URL");
    }

    /**
     * @Then I should see the main navigation
     */
    public function iShouldSeeTheMainNavigation()
    {
        $page = $this->getSession()->getPage();
        $nav = $page->find('css', 'nav');
        Assert::assertNotNull($nav, 'Navigation element not found');
        // Check for common navigation elements
        Assert::assertStringContainsString('Features', $page->getText());
        Assert::assertStringContainsString('Platform', $page->getText());
    }

    /**
     * @Then I should see the footer
     */
    public function iShouldSeeTheFooter()
    {
        $page = $this->getSession()->getPage();
        $footer = $page->find('css', 'footer');
        Assert::assertNotNull($footer, 'Footer element not found');
    }

    /**
     * @Then I should see links to all subproducts
     */
    public function iShouldSeeLinksToAllSubproducts()
    {
        $pageText = $this->getSession()->getPage()->getText();
        Assert::assertStringContainsString('FinAegis Exchange', $pageText);
        Assert::assertStringContainsString('FinAegis Lending', $pageText);
        Assert::assertStringContainsString('FinAegis Stablecoins', $pageText);
        Assert::assertStringContainsString('FinAegis Treasury', $pageText);
    }

    /**
     * @Then the :text link should point to :url
     */
    public function theLinkShouldPointTo($text, $url)
    {
        $page = $this->getSession()->getPage();
        
        // Find all links that contain the text
        $links = $page->findAll('xpath', "//a[contains(., '$text')]");
        
        $found = false;
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if (strpos($href, $url) !== false) {
                $found = true;
                break;
            }
        }
        
        Assert::assertTrue($found, "No link containing '$text' points to '$url'");
    }

    /**
     * @Given I am logged in as a user
     */
    public function iAmLoggedInAsAUser()
    {
        // Create a test user if needed
        $user = \App\Models\User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]
        );

        // Visit login page
        $this->getSession()->visit($this->locatePath('/login'));
        $this->getSession()->getPage()->fillField('email', 'test@example.com');
        $this->getSession()->getPage()->fillField('password', 'password');
        $this->getSession()->getPage()->pressButton('Log in');
        
        // Wait for redirect
        $this->getSession()->wait(2000);
    }

    /**
     * @When I click on :text
     */
    public function iClickOn($text)
    {
        $this->getSession()->getPage()->clickLink($text);
    }

}