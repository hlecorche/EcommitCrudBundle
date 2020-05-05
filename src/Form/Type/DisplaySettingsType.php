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

namespace Ecommit\CrudBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisplaySettingsType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        //Field "resultsPerPage"
        $builder->add(
            'resultsPerPage',
            ChoiceType::class,
            [
                'choices' => array_flip($options['resultsPerPageChoices']),
                'label' => 'Number of results per page',
                'choice_translation_domain' => false,
            ]
        );

        //Field "displayedColumns"
        $builder->add(
            'displayedColumns',
            ChoiceType::class,
            [
                'choices' => array_flip($options['columnsChoices']),
                'multiple' => true,
                'expanded' => true,
                'label' => 'Columns to be shown',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(
            [
                'csrf_protection' => false,
            ]
        );

        $resolver->setRequired(
            [
                'resultsPerPageChoices',
                'columnsChoices',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'crud_display_settings';
    }
}
