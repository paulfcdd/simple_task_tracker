<?php

declare(strict_types=1);

namespace App\Factory;

use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class SerializerFactory
{
    public static function createSerializer(iterable $normalizers, iterable $encoders): SerializerInterface
    {
        $normalizersArray = $normalizers instanceof \Traversable ? iterator_to_array($normalizers) : (array) $normalizers;
        $encodersArray = $encoders instanceof \Traversable ? iterator_to_array($encoders) : (array) $encoders;

        return new Serializer($normalizersArray, $encodersArray);
    }
}
