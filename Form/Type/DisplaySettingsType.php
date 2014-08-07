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
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DisplaySettingsType extends AbstractType
{
    /**
     * {@inheritDoc}
     */
    function buildForm(FormBuilderInterface $builder, array $options)
    {
        //Field "resultsPerPage"
        $builder->add(
            'resultsPerPage',
            'choice',
            array(
                'choices' => $options['resultsPerPageChoices'],
                'label' => 'Number of results per page',
            )
        );

        //Field "ddisplayedColumns"
        $builder->add(
            'displayedColumns',
            'choice',
            array(
                'choices' => $options['columnsChoices'],
                'multiple' => true,
                'expanded' => true,
                'label' => 'Columns to be shown'
            )
        );
    }

    /**
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
    public function getName()
    {
        return 'crud_display_settings';
    }
}
