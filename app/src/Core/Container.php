<?php

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use ReflectionException;

class Container
{
    private array $bindings = [];

    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * @throws ReflectionException
     */
    public function make(string $class)
    {
        if (isset($this->bindings[$class])) {
            $class = $this->bindings[$class];
        }

        if (interface_exists($class)) {
            $class = $this->discoverConcreteImplementation($class);
        }

        if (!class_exists($class)) {
            throw new \RuntimeException("Class '$class' not found.");
        }

        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Class '$class' is not instantiable.");
        }

        if (!$constructor = $reflection->getConstructor()) {
            return new $class();
        }

        $dependencies = array_map(function (ReflectionParameter $param) {
            $type = $param->getType();
            if (!$type) {
                throw new \RuntimeException("Cannot resolve untyped parameter: \${$param->getName()}");
            }
            return $this->make($type->getName());
        }, $constructor->getParameters());

        return $reflection->newInstanceArgs($dependencies);
    }

    public function loadFromConfig(string $path): void
    {
        $services = require $path;

        foreach ($services as $namespace => $settings) {
            $resourcePath = rtrim($settings['resource'], '/');
            $excludePaths = array_map('realpath', $settings['exclude'] ?? []);

            $rii = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($resourcePath)
            );

            foreach ($rii as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }

                $realPath = realpath($file->getPathname());

                foreach ($excludePaths as $excluded) {
                    if (str_starts_with($realPath, $excluded)) {
                        continue 2;
                    }
                }

                $basePath = realpath($resourcePath);
                $relativePath = ltrim(str_replace($basePath, '', $realPath), DIRECTORY_SEPARATOR);
                $classPath = str_replace(['/', '\\', '.php'], ['\\', '\\', ''], $relativePath);
                $class = rtrim($namespace, '\\') . '\\' . $classPath;

                if (!class_exists($class)) {
                    continue;
                }

                $reflection = new \ReflectionClass($class);

                if ($reflection->isAbstract() || $reflection->isInterface()) {
                    continue;
                }

                foreach ($reflection->getInterfaceNames() as $interface) {
                    if (!isset($this->bindings[$interface])) {
                        $this->bind($interface, $class);
                    }
                }
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function discoverConcreteImplementation(string $interface): string
    {
        $declared = get_declared_classes();
        $candidates = [];

        foreach ($declared as $class) {
            $reflection = new ReflectionClass($class);
            if ($reflection->implementsInterface($interface) && $reflection->isInstantiable()) {
                $candidates[] = $class;
            }
        }

        if (count($candidates) === 0) {
            throw new \RuntimeException("No implementation found for interface '$interface'");
        }

        if (count($candidates) > 1) {
            throw new \RuntimeException("Multiple implementations found for '$interface'. Please bind manually.");
        }

        return $candidates[0];
    }
}
