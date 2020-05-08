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

use Ecommit\CrudBundle\Crud\Crud;
use Ecommit\CrudBundle\Paginator\ArrayPaginator;
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

    public function testPaginatorLinksWithNoResult(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData([]);
        $paginator->init();

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list');

        $this->assertEmpty($result);
    }

    public function testPaginatorLinksWithOnePage(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(['val']);
        $paginator->init();

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list');

        $this->assertEmpty($result);
    }

    /**
     * @dataProvider getTestPaginatorWithDefaultOptionsProvider
     */
    public function testPaginatorWithDefaultOptions(int $page, string $expected): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage($page);
        $paginator->init();

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list');

        $this->assertSame($expected, $result);
    }

    public function getTestPaginatorWithDefaultOptionsProvider(): array
    {
        return [
            [1, '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1">1</a></li><li><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li><li><a href="/user?page=1">1</a></li><li class="current"><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li><a href="/user?page=5">5</a></li><li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li><li><a href="/user?page=7">7</a></li><li><a href="/user?page=8">8</a></li><li><a href="/user?page=9">9</a></li><li class="current"><a href="/user?page=10">10</a></li><li><a href="/user?page=11">11</a></li><li><a href="/user?page=12">12</a></li><li><a href="/user?page=13">13</a></li><li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li class="current"><a href="/user?page=19">19</a></li><li><a href="/user?page=20">20</a></li><li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li><a href="/user?page=19">19</a></li><li class="current"><a href="/user?page=20">20</a></li></ul></nav>'],
        ];
    }

    /**
     * @dataProvider getTestPaginatorWithMaxPagesOptionsProvider
     */
    public function testPaginatorWithMaxPagesOptions(int $page, string $expected): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage($page);
        $paginator->init();

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'max_pages_before' => 4,
            'max_pages_after' => 2,
        ]);

        $this->assertSame($expected, $result);
    }

    public function getTestPaginatorWithMaxPagesOptionsProvider(): array
    {
        return [
            [1, '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1">1</a></li><li><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li><li><a href="/user?page=1">1</a></li><li class="current"><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li><li><a href="/user?page=6">6</a></li><li><a href="/user?page=7">7</a></li><li><a href="/user?page=8">8</a></li><li><a href="/user?page=9">9</a></li><li class="current"><a href="/user?page=10">10</a></li><li><a href="/user?page=11">11</a></li><li><a href="/user?page=12">12</a></li><li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li><li><a href="/user?page=15">15</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li class="current"><a href="/user?page=19">19</a></li><li><a href="/user?page=20">20</a></li><li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li><a href="/user?page=19">19</a></li><li class="current"><a href="/user?page=20">20</a></li></ul></nav>'],
        ];
    }

    /**
     * @dataProvider getTestPaginatorWithTypeOptionProvider
     */
    public function testPaginatorWithTypeOption(int $page, string $type, string $expected): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage($page);
        $paginator->init();

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'type' => $type,
        ]);

        $this->assertSame($expected, $result);
    }

    public function getTestPaginatorWithTypeOptionProvider(): array
    {
        $full = function (int $currentPage) {
            $result = '';
            for ($i = 1; $i <= 20; ++$i) {
                $class = ($i === $currentPage) ? ' class="current"' : '';
                $result .= sprintf('<li%s><a href="/user?page=%s">%s</a></li>', $class, $i, $i);
            }

            return $result;
        };

        return [
            [1, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1">1</a></li><li><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li><li><a href="/user?page=1">1</a></li><li class="current"><a href="/user?page=2">2</a></li><li><a href="/user?page=3">3</a></li><li><a href="/user?page=4">4</a></li><li><a href="/user?page=5">5</a></li><li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li><li><a href="/user?page=7">7</a></li><li><a href="/user?page=8">8</a></li><li><a href="/user?page=9">9</a></li><li class="current"><a href="/user?page=10">10</a></li><li><a href="/user?page=11">11</a></li><li><a href="/user?page=12">12</a></li><li><a href="/user?page=13">13</a></li><li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li><li><a href="/user?page=16">16</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li class="current"><a href="/user?page=19">19</a></li><li><a href="/user?page=20">20</a></li><li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, 'sliding', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li><li><a href="/user?page=17">17</a></li><li><a href="/user?page=18">18</a></li><li><a href="/user?page=19">19</a></li><li class="current"><a href="/user?page=20">20</a></li></ul></nav>'],

            [1, 'elastic', '<nav><ul class="ec-crud-pagination">'.$full(1).'<li class="next"><a href="/user?page=2">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [2, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=1">‹</a></li>'.$full(2).'<li class="next"><a href="/user?page=3">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [10, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=9">‹</a></li>'.$full(10).'<li class="next"><a href="/user?page=11">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [19, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=18">‹</a></li>'.$full(19).'<li class="next"><a href="/user?page=20">›</a></li><li class="last"><a href="/user?page=20">»</a></li></ul></nav>'],
            [20, 'elastic', '<nav><ul class="ec-crud-pagination"><li class="first"><a href="/user?page=1">«</a></li><li class="previous"><a href="/user?page=19">‹</a></li>'.$full(20).'</ul></nav>'],
        ];
    }

    public function testPaginatorWithEmptyArrayAjaxOption(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(1);
        $paginator->init();

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1" class="ec-crud-ajax-link-auto">1</a></li><li><a href="/user?page=2" class="ec-crud-ajax-link-auto">2</a></li><li class="next"><a href="/user?page=2" class="ec-crud-ajax-link-auto">›</a></li><li class="last"><a href="/user?page=20" class="ec-crud-ajax-link-auto">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'ajax_options' => [],
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithAjaxOption(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(1);
        $paginator->init();

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">1</a></li><li><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">2</a></li><li class="next"><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">›</a></li><li class="last"><a href="/user?page=20" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'ajax_options' => [
                'update' => '#myId',
            ],
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithAttributePageOption(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(1);
        $paginator->init();

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?p=1">1</a></li><li><a href="/user?p=2">2</a></li><li class="next"><a href="/user?p=2">›</a></li><li class="last"><a href="/user?p=20">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'attribute_page' => 'p',
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithAttrOptions(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(10);
        $paginator->init();

        $expected = '<nav class="navattr" data-nav="val"><ul class="ulattr ec-crud-pagination" data-ul="val"><li class="lifirstpageattr first" data-li-first="val"><a href="/user?page=1" class="afirstpageattr" data-a-first="val">«</a></li><li class="lipreviouspageattr previous"><a href="/user?page=9" class="apreviouspageattr">‹</a></li><li class="liotherpageattr"><a href="/user?page=9" class="aotherpageattr">9</a></li><li class="liccurentpageattr current"><a href="/user?page=10" class="accurentpageattr">10</a></li><li class="liotherpageattr"><a href="/user?page=11" class="aotherpageattr">11</a></li><li class="linextpageattr next"><a href="/user?page=11" class="anextpageattr">›</a></li><li class="lilastpageattr last"><a href="/user?page=20" class="alastpageattr">»</a></li></ul></nav>';
        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'nav_attr' => ['class' => 'navattr', 'data-nav' => 'val'],
            'ul_attr' => ['class' => 'ulattr', 'data-ul' => 'val'],
            'li_attr' => [
                'first_page' => ['class' => 'lifirstpageattr', 'data-li-first' => 'val'],
                'previous_page' => ['class' => 'lipreviouspageattr'],
                'current_page' => ['class' => 'liccurentpageattr'],
                'next_page' => ['class' => 'linextpageattr'],
                'last_page' => ['class' => 'lilastpageattr'],
                'other_page' => ['class' => 'liotherpageattr'],
            ],
            'a_attr' => [
                'first_page' => ['class' => 'afirstpageattr', 'data-a-first' => 'val'],
                'previous_page' => ['class' => 'apreviouspageattr'],
                'current_page' => ['class' => 'accurentpageattr'],
                'next_page' => ['class' => 'anextpageattr'],
                'last_page' => ['class' => 'alastpageattr'],
                'other_page' => ['class' => 'aotherpageattr'],
            ],
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
    }

    public function testPaginatorWithRenderOption(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(1);
        $paginator->init();

        $result = $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'render' => 'render.html.twig',
        ]);

        $this->assertRegExp('/OK/', $result);
    }

    public function testPaginatorWithBadOptions(): void
    {
        $this->expectException(UndefinedOptionsException::class);
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(1);
        $paginator->init();

        $this->crudExtension->paginatorLinks($this->environment, $paginator, 'user_list', [], [
            'bad_option' => 'bad',
        ]);
    }

    public function testCrudPaginatorLinks(): void
    {
        $paginator = new ArrayPaginator(5);
        $paginator->setData(range(1, 100));
        $paginator->setPage(1);
        $paginator->init();

        $crud = $this->getMockBuilder(Crud::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getDivIdList', 'getPaginator', 'getRouteName', 'getRouteParams'])
            ->getMock();
        $crud->expects($this->once())->method('getDivIdList')->willReturn('myId');
        $crud->expects($this->once())->method('getPaginator')->willReturn($paginator);
        $crud->expects($this->once())->method('getRouteName')->willReturn('user_list');
        $crud->expects($this->once())->method('getRouteParams')->willReturn([]);

        $expected = '<nav><ul class="ec-crud-pagination"><li class="current"><a href="/user?page=1" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">1</a></li><li><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">2</a></li><li class="next"><a href="/user?page=2" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">›</a></li><li class="last"><a href="/user?page=20" class="ec-crud-ajax-link-auto" data-ec-crud-ajax-update="#myId">»</a></li></ul></nav>';
        $result = $this->crudExtension->crudPaginatorLinks($this->environment, $crud, [
            'max_pages_before' => 1,
            'max_pages_after' => 1,
        ]);

        $this->assertSame($expected, $result);
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
