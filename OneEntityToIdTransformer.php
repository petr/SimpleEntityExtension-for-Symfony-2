<?php

namespace SimpleEntityExtension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NoResultException;

class OneEntityToIdTransformer implements DataTransformerInterface
{
    private $em;
    private $class;
    private $queryBuilder;

    public function __construct(EntityManager $em, $class, $queryBuilder)
    {
        if (!(null === $queryBuilder || $queryBuilder instanceof \Closure)) {
            throw new UnexpectedTypeException($queryBuilder, 'Doctrine\ORM\QueryBuilder or \Closure');
        } 

        if (null == $class)
            throw new UnexpectedTypeException($class, 'string');

        $this->em = $em;
        $this->class = $class;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Fetch the id of the entity to populate the form
     */
    public function transform($data)
    {
        if (null === $data)
            return null;

        $meta = $this->em->getClassMetadata($this->class);

        if (!$meta->getReflectionClass()->isInstance($data))
            throw new TransformationFailedException('Invalid data, must be an instance of '.$this->class);

        $identifierField = $meta->getSingleIdentifierFieldName();
        $id = $meta->getReflectionProperty($identifierField)->getValue($data);

        return $id;
    }

    /**
     * Try to fetch the entity from its id in the database
     */
    public function reverseTransform($data)
    {
        if (!$data) {
            return null;
        }

        $em = $this->em;
        $class = $this->class;
        $repository = $em->getRepository($class);

        if ($qb = $this->queryBuilder) {
            // If a closure was passed, call id with the repository and the id
            if ($qb instanceof \Closure) {
                $qb = $qb($repository, $data);
            }

            try {
                $result = $qb->getQuery()->getSingleResult();
            } catch (NoResultException $e) {
                $result = null;
            }
        } else {
            // Defaults to find()
            $result = $repository->find($data);
        }

        if (!$result)
            throw new TransformationFailedException('Can not find entity');

        return $result;
    }
}

