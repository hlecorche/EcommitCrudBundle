<?php

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
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        //Field "resultsPerPage"
        $builder->add(
            'resultsPerPage',
            ChoiceType::class,
            array(
                'choices' => array_flip($options['resultsPerPageChoices']),
                'label' => 'Number of results per page',
                'choice_translation_domain' => false,
            )
        );

        //Field "displayedColumns"
        $builder->add(
            'displayedColumns',
            ChoiceType::class,
            array(
                'choices' => array_flip($options['columnsChoices']),
                'multiple' => true,
                'expanded' => true,
                'label' => 'Columns to be shown'
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'csrf_protection' => false,
            )
        );

        $resolver->setRequired(
            array(
                'resultsPerPageChoices',
                'columnsChoices',
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return 'crud_display_settings';
    }
}
