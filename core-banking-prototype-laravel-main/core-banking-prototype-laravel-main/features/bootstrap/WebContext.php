<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkContext;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

/**
 * Web feature context for browser-based testing
 */
class WebContext extends MinkContext implements Context
{

    /**
     * @When I visit :path
     */
    public function iVisit($path)
    {
        $this->visit($path);
    }

    /**
     * @Then I should see the following requirements:
     */
    public function iShouldSeeTheFollowingRequirements(TableNode $table)
    {
        $page = $this->getSession()->getPage();
        
        foreach ($table->getHash() as $row) {
            $requirementType = $row['requirement_type'];
            $itemsCount = (int) $row['items_count'];
            
            // Find the section with this requirement type
            $section = $page->find('xpath', "//h3[contains(text(), '{$requirementType} Requirements')]");
            Assert::assertNotNull($section, "Could not find {$requirementType} Requirements section");
            
            // Count the list items in this section
            $parentDiv = $section->getParent();
            $listItems = $parentDiv->findAll('css', 'li');
            Assert::assertCount($itemsCount, $listItems, "{$requirementType} Requirements should have {$itemsCount} items");
        }
    }

}