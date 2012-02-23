<?php

namespace SimpleEntityExtension;

use Symfony\Component\Form\FormBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Form\AbstractType;

use SimpleEntityExtension\OneEntityToIdTransformer;

class EntityIdType extends AbstractType
{
    protected $em;

    public function __construct(RegistryInterface $registry)
    {
        $this->em = $registry->getEntityManager();
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        $em = $options['em'] ?: $this->em;

        $builder->prependClientTransformer(new OneEntityToIdTransformer($em, $options['class'], $options['query_builder']));
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'required'          => true,
            'em'                => null,
            'class'             => null,
            'query_builder'     => null,
            'hidden'            => true
        );

        $options = array_replace($defaultOptions, $options);

        return $defaultOptions;
    }

    public function getParent(array $options)
    {
        return $options['hidden'] ? 'hidden' : 'field';
    }

    public function getName()
    {
        return 'entity_id';
    }
}
