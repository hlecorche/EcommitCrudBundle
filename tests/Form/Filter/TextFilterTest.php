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

use Ecommit\CrudBundle\Form\Filter\TextFilter;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\EntityManyToOne;
use Ecommit\CrudBundle\Tests\Functional\App\Entity\Tag;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class TextFilterTest extends AbstractFilterTestCase
{
    /**
     * @dataProvider getTestViewAndQueryBuilderProvider
     */
    public function testViewAndQueryBuilder($modelData, bool $mustBegin, bool $mustEnd, $expectedViewData, ?string $whereExpected, array $parametersExpected): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', $modelData));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class, [
            'must_begin' => $mustBegin,
            'must_end' => $mustEnd,
        ]);
        $view = $this->createFormAndGetFormView($crud);

        $this->assertSame($expectedViewData, $view->children['propertyName']->vars['value']);

        $this->checkQueryBuilder($crud, $whereExpected, $parametersExpected);
    }

    public static function getTestViewAndQueryBuilderProvider(): array
    {
        return [
            // Null value
            [null, false, false, '', null, []],
            [null, false, true, '', null, []],
            [null, true, false, '', null, []],
            [null, true, true, '', null, []],

            // Empty value
            ['', false, false, '', null, []],
            ['', false, true, '', null, []],
            ['', true, false, '', null, []],
            ['', true, true, '', null, []],

            // Not empty value
            ['val', false, false, 'val', "e.name LIKE :value_text_propertyName ESCAPE '!'", ['value_text_propertyName' => '%val%']],
            ['val', false, true, 'val', "e.name LIKE :value_text_propertyName ESCAPE '!'", ['value_text_propertyName' => '%val']],
            ['val', true, false, 'val', "e.name LIKE :value_text_propertyName ESCAPE '!'", ['value_text_propertyName' => 'val%']],
            ['val', true, true, 'val', 'e.name = :value_text_propertyName', ['value_text_propertyName' => 'val']],

            // Escape
            ['v%a_l', false, false, 'v%a_l', "e.name LIKE :value_text_propertyName ESCAPE '!'", ['value_text_propertyName' => '%v!%a!_l%']],
            ['v!a\l', false, false, 'v!a\l', "e.name LIKE :value_text_propertyName ESCAPE '!'", ['value_text_propertyName' => '%v!!a\l%']],
            ['val\\', false, false, 'val\\', "e.name LIKE :value_text_propertyName ESCAPE '!'", ['value_text_propertyName' => '%val\\%']],
            ['v%a_l', true, true, 'v%a_l', 'e.name = :value_text_propertyName', ['value_text_propertyName' => 'v%a_l']],
        ];
    }

    public function testInvalidUtf8(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', "val\xff"));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class);

        $this->checkQueryBuilder($crud, null, []);
    }

    /**
     * @dataProvider getTestLikeResultsProvider
     */
    public function testLikeResults(string $value, bool $mustBegin, bool $mustEnd, array $expectedNames): void
    {
        $this->insertLikeEntities();

        $crud = $this->createCrud($this->createCrudConfig('e.name', $value));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class, [
            'must_begin' => $mustBegin,
            'must_end' => $mustEnd,
        ]);
        $crud->build();

        $names = $crud->getQueryBuilder()->select('e.name')->getQuery()->getSingleColumnResult();
        sort($names);
        $this->assertSame($expectedNames, $names);
    }

    /**
     * @dataProvider getTestLikeResultsProvider
     */
    public function testLikeResultsWithDbalQueryBuilder(string $value, bool $mustBegin, bool $mustEnd, array $expectedNames): void
    {
        $this->insertLikeEntities();

        $queryBuilder = $this->em->getConnection()->createQueryBuilder()
            ->select('e.name')
            ->from('entity_many_to_one', 'e');
        (new TextFilter())->updateQueryBuilder($queryBuilder, 'propertyName', $value, [
            'must_begin' => $mustBegin,
            'must_end' => $mustEnd,
            'alias_search' => 'e.name',
        ]);

        $names = $queryBuilder->executeQuery()->fetchFirstColumn();
        sort($names);
        $this->assertSame($expectedNames, $names);
    }

    public static function getTestLikeResultsProvider(): array
    {
        return [
            ['like_a_b', false, false, ['like_a_b']],
            ['like_50%', false, false, ['like_50%']],
            ['like_a!b', false, false, ['like_a!b']],
            ['like_c:\\', false, false, ['like_c:\x']],
            ['like_c:\\', true, false, ['like_c:\x']],
            ['like_', true, false, ['like_50%', 'like_500', 'like_a!b', 'like_a_b', 'like_axb', 'like_c:\x']],
            ['%', false, false, ['like_50%']],
            ['_', false, false, ['like_50%', 'like_500', 'like_a!b', 'like_a_b', 'like_axb', 'like_c:\x']],
            ['a_b', false, true, ['like_a_b']],
        ];
    }

    protected function insertLikeEntities(): void
    {
        $this->em->getConnection()->beginTransaction();

        $tag = $this->em->getRepository(Tag::class)->find(2);
        foreach (['like_a_b', 'like_axb', 'like_50%', 'like_500', 'like_a!b', 'like_c:\x'] as $i => $name) {
            $this->em->persist(new EntityManyToOne(1000 + $i, $name, $tag));
        }
        $this->em->flush();
    }

    protected function tearDown(): void
    {
        if ($this->em?->getConnection()->isTransactionActive()) {
            $this->em->getConnection()->rollBack();
        }

        parent::tearDown();
    }

    public function testInvalidInput(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name', ['not-scalar']));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class);

        $this->checkQueryBuilder($crud, null, []);
    }

    /**
     * @dataProvider getTestSubmitProvider
     */
    public function testSubmit($submittedData, $expectedModelData, $expectedViewData): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => $submittedData,
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame($expectedModelData, $field->getData());
        $this->assertSame($expectedViewData, $field->getViewData());
    }

    public static function getTestSubmitProvider(): array
    {
        return [
            [null, null, ''],
            ['', null, ''],
            ['val', 'val', 'val'],
        ];
    }

    public function testSubmitInvalidFormat(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => ['not-scalar'],
        ]);

        $field = $form->get('propertyName');
        $this->assertFalse($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertNull($field->getData());
    }

    public function testSubmitInvalidMinLength(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class, [
            'min_length' => 5,
        ]);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => 'val',
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertSame('val', $field->getData());
    }

    public function testSubmitInvalidMaxLength(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class, [
            'max_length' => 2,
        ]);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => 'val',
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertFalse($field->isValid());
        $this->assertSame('val', $field->getData());
    }

    public function testSubmitWithoutValidation(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class, [
            'max_length' => 2,
            'autovalidate' => false,
        ]);

        $form = $this->createAndGetForm($crud);
        $form->submit([
            'propertyName' => 'val',
        ]);

        $field = $form->get('propertyName');
        $this->assertTrue($field->isSynchronized());
        $this->assertTrue($field->isValid());
        $this->assertSame('val', $field->getData());
        $this->assertSame('val', $field->getViewData());
    }

    public function testWithType(): void
    {
        $crud = $this->createCrud($this->createCrudConfig('e.name'));
        $crud->getSearchFormBuilder()->addFilter('propertyName', TextFilter::class, [
            'type' => TextareaType::class,
        ]);

        $view = $this->createFormAndGetFormView($crud);

        $field = $view['propertyName'];
        $this->assertContains('textarea', $field->vars['block_prefixes']);
    }
}
