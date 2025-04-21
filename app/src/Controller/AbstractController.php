<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractController
{
    protected SerializerInterface $serializer;
    protected ValidatorInterface $validator;

    public function __construct()
    {
        $this->serializer = new Serializer(
            [
                new BackedEnumNormalizer(),
                new ObjectNormalizer(
                    classMetadataFactory: new ClassMetadataFactory(new AttributeLoader()),
                    propertyTypeExtractor: new ReflectionExtractor()
                )
            ],
            [new JsonEncoder()]
        );
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
