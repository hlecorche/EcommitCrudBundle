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

namespace Ecommit\CrudBundle\Tests\Form\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Ecommit\CrudBundle\Form\Filter\FieldFilterEntityAjax;
use Ecommit\CrudBundle\Form\Type\EntityAjaxType;
use Ecommit\CrudBundle\Tests\DoctrineHelper;
use Ecommit\CrudBundle\Tests\Fixtures\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Fixtures\EntityToManyToOneSearcher;
use Ecommit\CrudBundle\Tests\Fixtures\Tag;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\DoctrineOrmTypeGuesser;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\Routing\Router;

class FieldFilterEntityAjaxTest extends TestCase
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
        DoctrineHelper::loadEntityManyToOneFixtures($this->em);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo('default'))
            ->willReturn($this->em);
        $registry->expects($this->any())
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
    }

    public function testOptionsAreRequired(): void
    {
        $this->expectException(MissingOptionsException::class);
        $filter = new FieldFilterEntityAjax('columnId', 'propertyName');
        $filter->init();
    }

    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder(bool $multiple, $modelData, $expectedViewData, array $expectedIdsFound): void
    {
        $searcher = new EntityToManyToOneSearcher();
        $searcher->propertyName = $modelData;

        $filter = new FieldFilterEntityAjax('columnId', 'propertyName', [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
        ]);
        $filter->init();

        $formBuilder = $this->factory->createBuilder(FormType::class, [
            'propertyName' => $searcher->propertyName,
        ]);
        $filter->addField($formBuilder);
        $view = $formBuilder->getForm()->createView();

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $queryBuilder = $this->em->getRepository(EntityManyToOne::class)->createQueryBuilder('e')
            ->orderBy('e.id', 'asc');
        $filter->changeQuery($queryBuilder, $searcher, 'e.tag');
        $idsFound = [];
        foreach ($queryBuilder->getQuery()->getResult() as $entity) {
            $idsFound[] = $entity->getId();
        }
        $this->assertSame($expectedIdsFound, $idsFound);
    }

    public function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            //No multiple
            [false, null, null, [1, 2, 3, 4, 5]],
            [false, 2, ['2' => 'tag2'], [1]],
            [false, 5, ['5' => 'tag_name'], [4, 5]],
            [false, 9999, null, []], //9999 : Entity not found

            //Multiple
            [true, [], [], [1, 2, 3, 4, 5]],
            [true, [2], ['2' => 'tag2'], [1]],
            [true, [2, 3], ['2' => 'tag2', '3' => '3'], [1, 2]],
            [true, [2, 9999], ['2' => 'tag2'], [1]], //9999 : Entity not found
            [true, [9999], [], []], //9999 : Entity not found
        ];
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit(bool $multiple, $submittedData, $expectedModelData, $expectedViewData): void
    {
        $searcher = new EntityToManyToOneSearcher();
        $searcher->propertyName = ($multiple) ? [] : null;

        $filter = new FieldFilterEntityAjax('columnId', 'propertyName', [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
        ]);
        $filter->init();

        $formBuilder = $this->factory->createBuilder(FormType::class, [
            'propertyName' => $searcher->propertyName,
        ]);
        $filter->addField($formBuilder);

        $form = $formBuilder->getForm();
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData());
    }

    public function getTestSubmitProvider(): array
    {
        return [
            //No multiple
            [false, null, null, null],
            [false, '', null, null],
            [false, '2', '2', ['2' => 'tag2']],

            //Multiple
            [true, [], [], []],
            [true, ['2'], ['2'], ['2' => 'tag2']],
            [true, ['2', '3'], ['2', '3'], ['2' => 'tag2', '3' => '3']],
            [true, ['2', ['1']], ['2'], ['2' => 'tag2']], //Ignore not scalar
        ];
    }

    /**
     * @dataProvider getTestSubmitInvalidProvider
     */
    public function testSubmitInvalid(bool $multiple, $submittedData): void
    {
        $searcher = new EntityToManyToOneSearcher();
        $searcher->propertyName = ($multiple) ? [] : null;

        $filter = new FieldFilterEntityAjax('columnId', 'propertyName', [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
            'max' => 2,
        ]);
        $filter->init();

        $formBuilder = $this->factory->createBuilder(FormType::class, [
            'propertyName' => $searcher->propertyName,
        ]);
        $filter->addField($formBuilder);

        $form = $formBuilder->getForm();
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
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
     * @dataProvider getTestViewWithQueryBuilderProvider
     */
    public function testViewWithQueryBuilder(bool $queryBuilderIsClosure, bool $multiple, $modelData, $expectedViewData, array $expectedIdsFound): void
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

        $searcher = new EntityToManyToOneSearcher();
        $searcher->propertyName = $modelData;

        $filter = new FieldFilterEntityAjax('columnId', 'propertyName', [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
        ], [
            'query_builder' => $queryBuilder,
        ]);
        $filter->init();

        $formBuilder = $this->factory->createBuilder(FormType::class, [
            'propertyName' => $searcher->propertyName,
        ]);
        $filter->addField($formBuilder);
        $view = $formBuilder->getForm()->createView();

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']); //Twig doesn't display invalid list

        $queryBuilderResults = $this->em->getRepository(EntityManyToOne::class)->createQueryBuilder('e')
            ->orderBy('e.id', 'asc');
        $filter->changeQuery($queryBuilderResults, $searcher, 'e.tag');
        $idsFound = [];
        foreach ($queryBuilderResults->getQuery()->getResult() as $entity) {
            $idsFound[] = $entity->getId();
        }
        $this->assertSame($expectedIdsFound, $idsFound);
    }

    public function getTestViewWithQueryBuilderProvider(): array
    {
        return [
            //No multiple - Valid
            [false, false, '4', ['4' => 'tag_name'], [3]],
            [true, false, '4', ['4' => 'tag_name'], [3]],

            //No multiple - Invalid but display results
            [false, false, '2', null, [1]],
            [true, false, '2', null, [1]],

            //Mutiple - valid
            [false, true, ['4'], ['4' => 'tag_name'], [3]],
            [true, true, ['4'], ['4' => 'tag_name'], [3]],

            //Multiple - Invalid but display results
            [false, true, ['2'], [], [1]],
            [true, true, ['2'], [], [1]],
        ];
    }

    /**
     * @dataProvider getTestSubmitWithQueryBuilderProvider
     */
    public function testSubmitWithQueryBuilder(bool $queryBuilderIsClosure, bool $multiple, $submittedData, $expectedValid, $expectedModelData, $expectedViewData): void
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

        $searcher = new EntityToManyToOneSearcher();
        $searcher->propertyName = ($multiple) ? [] : null;

        $filter = new FieldFilterEntityAjax('columnId', 'propertyName', [
            'class' => Tag::class,
            'route_name' => 'route_name',
            'multiple' => $multiple,
        ], [
            'query_builder' => $queryBuilder,
        ]);
        $filter->init();

        $formBuilder = $this->factory->createBuilder(FormType::class, [
            'propertyName' => $searcher->propertyName,
        ]);
        $filter->addField($formBuilder);

        $form = $formBuilder->getForm();
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertSame($expectedValid, $field->isSynchronized());
        $this->assertSame($expectedValid, $field->isValid());
        $this->assertSame($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData()); //Twig doesn't display invalid list
    }

    public function getTestSubmitWithQueryBuilderProvider(): array
    {
        return [
            //No multiple - Valid
            [false, false, '4', true, '4', ['4' => 'tag_name']],
            [true, false, '4', true, '4', ['4' => 'tag_name']],

            //No multiple - Invalid
            [false, false, '2', false, null, '2'],
            [true, false, '2', false, null, '2'],

            //Multiple - Valid
            [false, true, ['4'], true, ['4'], ['4' => 'tag_name']],
            [true, true, ['4'], true, ['4'], ['4' => 'tag_name']],

            //Multiple - Valid
            [false, true, ['2'], false, null, ['2']],
            [true, true, ['2'], false, null, ['2']],
        ];
    }
}
