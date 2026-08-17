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

use Ecommit\CrudBundle\Tests\Functional\App\StatelessCsrfKernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Kernel;

class StatelessCsrfTest extends WebTestCase
{
    protected function setUp(): void
    {
        if (Kernel::VERSION_ID < 70200) { // @legacy
            $this->markTestSkipped('The stateless CSRF protection requires Symfony 7.2 or later.');
        }
    }

    protected static function getKernelClass(): string
    {
        return StatelessCsrfKernel::class;
    }

    /**
     * @dataProvider getTestFormsUseStatelessCsrfTokenProvider
     */
    public function testFormsUseStatelessCsrfToken(string $tokenId): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/user');

        $this->assertResponseIsSuccessful();
        // When the token is not stateless, the value is a token stored in the session
        $this->assertSame(StatelessCsrfKernel::COOKIE_NAME, $crawler->filterXPath(\sprintf('//input[@id="%s"]', $tokenId))->attr('value'));
    }

    public static function getTestFormsUseStatelessCsrfTokenProvider(): array
    {
        return [
            ['crud_search_user__token'],
            ['crud_display_settings_user__token'],
        ];
    }
}
