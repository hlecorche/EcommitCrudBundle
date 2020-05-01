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

namespace Ecommit\CrudBundle\Tests\Twig;

use Ecommit\CrudBundle\Twig\CrudExtension;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Twig\Environment;

class CrudExtensionTest extends KernelTestCase
{
    /**
     * @var CrudExtension
     */
    protected $crudExtension;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Environment
     */
    protected $environment;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->crudExtension = self::$container->get(CrudExtension::class);
        $this->formFactory = self::$container->get(FormFactoryInterface::class);
        $this->environment = self::$container->get(Environment::class);
    }

    public function testFormStartAjax(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView());

        $xpath = '//form[@method="post" and @action="/url" and contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithCustomAutoClass(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'auto_class' => 'custom-class',
        ]);

        $xpath = '//form[not(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and contains(concat(" ", normalize-space(@class), " "), " custom-class ")]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithClassAttr(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'attr' => [
                'class' => 'my-class',
            ],
        ]);

        $xpath = '//form[(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and contains(concat(" ", normalize-space(@class), " "), " my-class ")]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithAjaxOptions(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'ajax_options' => [
                'on_success' => 'a=b',
            ],
        ]);

        $xpath = '//form[(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and @data-ec-crud-ajax-on-success="a=b"]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithAttrAndAjaxOptions(): void
    {
        $html = $this->crudExtension->formStartAjax($this->createFormView(), [
            'attr' => [
                'data-custom' => 'custom',
            ],
            'ajax_options' => [
                'on_success' => 'a=b',
            ],
        ]);

        $xpath = '//form[(contains(concat(" ", normalize-space(@class), " "), " ec-crud-ajax-form-auto ")) and @data-ec-crud-ajax-on-success="a=b" and @data-custom="custom"]';
        $this->assertMatchesXpath($html, $xpath);
    }

    public function testFormStartAjaxWithBadAjaxOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->crudExtension->formStartAjax($this->createFormView(), [
            'ajax_options' => [
                'badOption' => 'value',
            ],
        ]);
    }

    /**
     * @dataProvider getTestAjaxAttributesProvider
     */
    public function testAjaxAttributes(array $ajaxOptions, string $expected): void
    {
        $result = $this->crudExtension->ajaxAttributes(
            $this->environment,
            $ajaxOptions
        );

        $this->assertSame($expected, $result);
    }

    public function getTestAjaxAttributesProvider(): array
    {
        return [
            [[], ''],
            [['url' => '/url?id=a'], ' data-ec-crud-ajax-url="/url?id=a"'],
            [['url' => '/url?id=a', 'method' => 'GET'], ' data-ec-crud-ajax-url="/url?id=a" data-ec-crud-ajax-method="GET"'],
            [['data' => '<script>ValueToEscape</script>'], ' data-ec-crud-ajax-data="&lt;script&gt;ValueToEscape&lt;/script&gt;"'],
            [['cache' => true], ' data-ec-crud-ajax-cache="true"'],
            [['cache' => false], ' data-ec-crud-ajax-cache="false"'],
            [['data' => ['var1' => 'value1']], ' data-ec-crud-ajax-data="{&quot;var1&quot;:&quot;value1&quot;}"'],
        ];
    }

    public function testAjaxAttributesWithBadAjaxOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $this->crudExtension->ajaxAttributes(
            $this->environment,
            ['badOption' => 'value']
        );
    }

    protected function createFormView(): FormView
    {
        $builder = $this->formFactory->createBuilder(FormType::class, null, [
            'action' => '/url',
            'method' => 'POST',
        ]);

        return $builder->getForm()->createView();
    }

    protected function assertMatchesXpath(?string $html, string $expression, int $count = 1): void
    {
        $crawler = new Crawler($html);
        $this->assertCount($count, $crawler->filterXPath($expression));
    }
}
