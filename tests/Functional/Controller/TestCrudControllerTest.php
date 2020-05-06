<?php

declare(strict_types=1);

/*
 * This file is part of the EcommitCrudBundle package.
 *
 * (c) E-commit <contact@e-commit.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ecommit\CrudBundle\Tests\Functional\Controller;

use Ecommit\CrudBundle\Crud\Crud;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;
use Symfony\Component\Panther\PantherTestCase;

class TestCrudControllerTest extends PantherTestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$defaultOptions['browser'] = static::FIREFOX;
    }

    public function testList(): Client
    {
        $client = static::createPantherClient();
        $client->request('GET', '/user/');

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));
        $this->assertSame('AudeJavel', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testList
     */
    public function testChangeSortSense(Client $client): Client
    {
        $link = $client->getCrawler()->filterXPath('//table[@class="result"]/thead/tr/th/a[text()="first_name"]');
        $link->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('YvonEmbavé', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testChangeSortSense
     */
    public function testChangeSortColumn(Client $client): Client
    {
        $link = $client->getCrawler()->filterXPath('//table[@class="result"]/thead/tr/th/a[text()="last_name"]');
        $link->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testChangeSortColumn
     */
    public function testChangeDisplayedColumns(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(.,"Display Settings")]');
        $button->first()->click();

        $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_user"]/descendant::input[@value="username"]')->click();
        $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_user"]/descendant::button[@type="submit"]')->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testChangeDisplayedColumns
     */
    public function testChangePerPage(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(., "Display Settings")]');
        $button->first()->click();

        $form = $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_user"]/descendant::button[@type="submit"]')->form();
        $form['crud_display_settings_user[resultsPerPage]'] = 10;
        $client->submit($form);
        $this->waitForAjax($client);

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testChangePerPage
     */
    public function testPersistentValuesAfterChangeSortAndSettings(Client $client): Client
    {
        $client->request('GET', '/user/');

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testPersistentValuesAfterChangeSortAndSettings
     */
    public function testChangePage(Client $client): Client
    {
        $page = $client->getCrawler()->filterXPath('//ul[@class="pagination"]/li/a[text()="2"]');
        $page->first()->click();
        $this->waitForAjax($client);

        $this->assertSame([1, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([2, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('JudieCieux', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testChangePage
     */
    public function testPersistentValuesAfterChangePage(Client $client): Client
    {
        $client->request('GET', '/user/');

        $this->assertSame([1, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([2, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('JudieCieux', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testPersistentValuesAfterChangePage
     */
    public function testSearch(Client $client): Client
    {
        $form = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[@type="submit" and contains(text(), "Search")]')->form();
        $form['crud_search_user[firstName]'] = 'Henri';
        $client->submit($form);
        $this->waitForAjax($client);

        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 1], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('HenriPoste', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testSearch
     */
    public function testPersistentValuesAfterSearch(Client $client): Client
    {
        $client->request('GET', '/user/');

        $this->assertSame([2, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 1], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('HenriPoste', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testPersistentValuesAfterSearch
     */
    public function testResetSearch(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//div[@id="crud_search"]/descendant::button[contains(text(), "Reset")]');
        $button->first()->click();
        $this->waitForAjax($client);

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testResetSearch
     */
    public function testPersistentValuesAfterResetSearch(Client $client): Client
    {
        $client->request('GET', '/user/');

        $this->assertSame([10, 3], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 2], $this->getPagination($client->getCrawler()));
        $this->assertSame(['last_name', Crud::DESC], $this->getSort($client->getCrawler()));
        $this->assertSame('ClémentTine', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testPersistentValuesAfterResetSearch
     */
    public function testResetSettings(Client $client): Client
    {
        $button = $client->getCrawler()->filterXPath('//button[contains(., "Display Settings")]');
        $button->first()->click();

        $button = $client->getCrawler()->filterXPath('//form[@name="crud_display_settings_user"]/descendant::button[contains(., "Reset display settings")]');
        $button->first()->click();
        $this->waitForAjax($client);

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));
        $this->assertSame('AudeJavel', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    /**
     * @depends testResetSettings
     */
    public function testPersistentValuesAfterResetSettings(Client $client): Client
    {
        $client->request('GET', '/user/');

        $this->assertSame([5, 2], $this->countRowsAndColumns($client->getCrawler()));
        $this->assertSame([1, 3], $this->getPagination($client->getCrawler()));
        $this->assertSame(['first_name', Crud::ASC], $this->getSort($client->getCrawler()));
        $this->assertSame('AudeJavel', $this->getFirstUsername($client->getCrawler()));

        return $client;
    }

    protected function countRowsAndColumns(Crawler $crawler): array
    {
        $rows = $crawler->filterXPath('//table[@class="result"]/tbody/tr');
        $countRows = \count($rows);
        $columns = $rows->first()->filterXPath('td');
        $countColumns = \count($columns);

        return [$countRows, $countColumns];
    }

    protected function getPagination(Crawler $crawler): array
    {
        $infos = $crawler->filterXPath('//div[@class="info-pagination"]')->text();

        preg_match('/^Results \d+\-\d+ \- Page (\d+)\/(\d+)$/', $infos, $groups);

        $page = (int) $groups[1];
        $countPages = (int) $groups[2];

        return [$page, $countPages];
    }

    protected function getSort(Crawler $crawler): array
    {
        $imageSort = $crawler->filterXPath('//table[@class="result"]/thead/tr/th/img');
        if (0 === \count($imageSort)) {
            return [];
        }
        $imageSort = $imageSort->first();

        switch ($imageSort->getAttribute('alt')) {
            case '^':
                $sense = Crud::ASC;
                break;
            case 'V':
                $sense = Crud::DESC;
                break;
            default:
                throw new \Exception('Bad sense');
        }

        $column = $imageSort->filterXPath('ancestor::th')->last();

        return [$column->text(), $sense];
    }

    protected function getFirstUsername(Crawler $crawler): ?string
    {
        $rows = $crawler->filterXPath('//table[@class="result"]/tbody/tr');
        if (0 === \count($rows)) {
            return null;
        }

        return $rows->first()->getAttribute('data-username');
    }

    protected function waitForAjax(Client $client, int $timeout = 5): void
    {
        $driver = $client->getWebDriver();

        $driver->wait($timeout, 500)->until(static function ($driver) {
            return !$driver->executeScript('return (typeof jQuery !== "undefined" && jQuery.active);');
        });
    }
}
