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

namespace Ecommit\CrudBundle\Tests\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Ecommit\CrudBundle\Form\Type\EntityAjaxType;
use Ecommit\CrudBundle\Tests\DoctrineHelper;
use Ecommit\CrudBundle\Tests\Fixtures\Tag;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Exception\RuntimeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Routing\Router;

class EntityAjaxTypeTest extends TestCase
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    protected function setUp(): void
    {
        $this->em = DoctrineHelper::createEntityManager();
        DoctrineHelper::loadTagsFixtures($this->em);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo('default'))
            ->willReturn($this->em);
        $registry
            ->expects($this->any())
            ->method('getManagerForClass')
            ->willReturnCallback(function ($class) {
                if (Tag::class === $class) {
                    return $this->em;
                }

                return null;
            });

        $router = $this->createMock(Router::class);
        $router->expects($this->any())
            ->method('generate')
            ->willReturnCallback(function (string $routeName, array $routeParams) {
                if (\count($routeParams) > 0) {
                    return '/'.$routeName.'?'.http_build_query($routeParams);
                }

                return '/'.$routeName;
            });

        $this->factory = Forms::createFormFactoryBuilder()
            ->addType(new EntityAjaxType($registry, $router))
            ->addTypeGuesser(new DoctrineOrmTypeGuesser($registry))
            ->getFormFactory();
    }

    protected function tearDown(): void
    {
        $this->em = null;
        $this->factory = null;
    }

    public function testOptionsAreRequired(): void
    {
        $this->expectException(MissingOptionsException::class);
        $this->factory->createNamed('name', EntityAjaxType::class);
    }

    public function testInvalidClassOption(): void
    {
        $this->expectException(RuntimeException::class);
        $this->factory->createNamed('name', EntityAjaxType::class, null, [
            'class' => 'bad',
            'route_name' => 'route_name',
        ]);
    }

    /**
     * @dataProvider getTestViewProvider
     */
    public function testView(bool $multiple, $modelData, $dataBuilderName, $expectedViewValue): void
    {
        $modelData = $this->buildData($this->em, $modelData, $dataBuilderName);

        $field = $this->factory->create(EntityAjaxType::class, $modelData, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
            'data_class' => null,
        ]);
        $view = $field->createView();

        $this->assertSame($expectedViewValue, $view->vars['value']);
        $this->assertSame('/route_name', $view->vars['url']);
        $this->assertSame($multiple, $view->vars['multiple']);
    }

    public function getTestViewProvider(): array
    {
        return [
            //No multiple
            [false, null, 'no-transformation', null],
            [false, 2, 'entity', ['2' => 'tag2']],

            //Multiple
            [true, [], 'collection', []],
            [true, [2], 'collection', ['2' => 'tag2']],
            [true, [2, 3], 'collection', ['2' => 'tag2', '3' => '3']],
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit(bool $multiple, $submittedData, $expectedModelData, $expectedDataBuilderName, $expectedViewData): void
    {
        $field = $this->factory->create(EntityAjaxType::class, null, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
        ]);

        $field->submit($submittedData);

        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $expectedModelData = $this->buildData($this->em, $expectedModelData, $expectedDataBuilderName);
        $this->assertEquals($expectedModelData, $field->getData());
        $this->assertEquals($expectedViewData, $field->getViewData());
    }

    public function getTestSubmitProvider(): array
    {
        return [
            //No multiple
            [false, null, null, 'no-transformation', null],
            [false, '', null, 'no-transformation', null],
            [false, '2', 2, 'entity', ['2' => 'tag2']],

            //Multiple
            [true, [], [], 'collection', []],
            [true, ['2'], [2], 'collection', ['2' => 'tag2']],
            [true, ['2', '3'], [2, 3], 'collection', ['2' => 'tag2', '3' => '3']],
            [true, ['2', ['1']], [2], 'collection', ['2' => 'tag2']], //Ignore not scalar
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidProvider
     */
    public function testSubmitInvalid(bool $multiple, $submittedData): void
    {
        $field = $this->factory->create(EntityAjaxType::class, null, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
            'max_elements' => 2,
        ]);

        $field->submit($submittedData);

        $this->assertFalse($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertNull($field->getData());
        $this->assertSame($submittedData, $field->getViewData()); //Twig doesn't display invalid list
    }

    public function getTestSubmitInvalidProvider(): array
    {
        return [
            //No multiple
            [false, []],
            [false, '99999'],

            //Multiple
            [true, '1'],
            [true, ['99999']],
            [true, ['1', '99999']],
            [true, ['1', '2', '3']], //max elements
        ];
    }

    /**
     * @dataProvider getTestSubmitWithQueryBuilderProvider
     */
    public function testSubmitWithQueryBuilder(bool $queryBuilderIsClosure, bool $multiple, $submittedData, $expectedValid, $expectedModelData, $expectedDataBuilderName, $expectedViewData): void
    {
        if ($queryBuilderIsClosure) {
            $queryBuilder = function (EntityRepository $entityRepository) {
                return $entityRepository->createQueryBuilder('t')
                    ->select('t')
                    ->andWhere('t.id > 2');
            };
        } else {
            $queryBuilder = $this->em->getRepository(Tag::class)->createQueryBuilder('t')
                ->select('t')
                ->andWhere('t.id > 2');
        }

        $field = $this->factory->create(EntityAjaxType::class, null, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
            'query_builder' => $queryBuilder,
        ]);

        $field->submit($submittedData);

        $this->assertSame($expectedValid, $field->isSynchronized());
        $this->assertSame($expectedValid, $field->isValid());
        $expectedModelData = $this->buildData($this->em, $expectedModelData, $expectedDataBuilderName);
        $this->assertEquals($expectedModelData, $field->getData());
        $this->assertEquals($expectedViewData, $field->getViewData()); //Twig doesn't display invalid list
    }

    public function getTestSubmitWithQueryBuilderProvider(): array
    {
        return [
            //No multiple - Valid
            [false, false, '4', true, 4, 'entity', ['4' => 'tag_name']],
            [true, false, '4', true, 4, 'entity', ['4' => 'tag_name']],

            //No multiple - Invalid
            [false, false, '2', false, null, 'no-transformation', '2'],
            [true, false, '2', false, null, 'no-transformation', '2'],

            //Multiple - Valid
            [false, true, ['4', '5'], true, [4, 5], 'collection', ['4' => 'tag_name', '5' => 'tag_name']],
            [true, true, ['4', '5'], true, [4, 5], 'collection', ['4' => 'tag_name', '5' => 'tag_name']],

            //Multiple - Invalid
            [false, true, ['2', '5'], false, null, 'no-transformation', ['2', '5']],
            [true, true, ['2', '5'], false, null, 'no-transformation', ['2', '5']],
        ];
    }

    /**
     * @dataProvider getTestViewWithChoiceLabelProvider
     */
    public function testViewWithChoiceLabel($choiceLabel, $expectedViewValue): void
    {
        $tag = $this->em->getRepository(Tag::class)->find(2);

        $field = $this->factory->create(EntityAjaxType::class, $tag, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'data_class' => null,
            'choice_label' => $choiceLabel,
        ]);
        $view = $field->createView();

        $this->assertSame($expectedViewValue, $view->vars['value']);
    }

    public function getTestViewWithChoiceLabelProvider(): array
    {
        $closure = function (Tag $tag) {
            return sprintf('name: %s', $tag->getName());
        };

        return [
            ['name', ['2' => 'tag2']],
            [$closure, ['2' => 'name: tag2']],
        ];
    }

    public function testViewWithRouteParams(): void
    {
        $tag = $this->em->getRepository(Tag::class)->find(2);

        $field = $this->factory->create(EntityAjaxType::class, $tag, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'route_params' => ['param1' => 'value1'],
            'data_class' => null,
        ]);
        $view = $field->createView();

        $this->assertSame('/route_name?param1=value1', $view->vars['url']);
    }

    public function testViewWithEmParams(): void
    {
        $tag = $this->em->getRepository(Tag::class)->find(2);

        $field = $this->factory->create(EntityAjaxType::class, $tag, [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'em' => 'default',
            'data_class' => null,
        ]);
        $view = $field->createView();

        $this->assertSame(['2' => 'tag2'], $view->vars['value']);
    }

    protected function buildData(EntityManager $entityManager, $data, string $builderName)
    {
        if ('no-transformation' === $builderName) {
            return $data;
        } elseif ('entity' === $builderName) {
            return $entityManager->find(Tag::class, $data);
        } elseif ('collection' === $builderName) {
            $collection = new ArrayCollection();
            foreach ($data as $identifier) {
                $collection->add($entityManager->find(Tag::class, $identifier));
            }

            return $collection;
        }

        throw new \Exception('Bad builder');
    }
}
