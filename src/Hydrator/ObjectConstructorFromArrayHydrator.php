<?php

namespace JamesHalsall\Hydrator;

use Stringy\Stringy;

/**
 * Object Constructor From Array Hydrator.
 *
 * Hydrates objects using their constructor parameters.
 *
 * @package JamesHalsall\Hydrator
 * @author  James Halsall <james.t.halsall@googlemail.com>
 */
class ObjectConstructorFromArrayHydrator implements HydratorInterface
{
    /**
     * Hydrates an object with raw data
     *
     * @param mixed $className The object to hydrate
     * @param array $rawData   The raw data to hydrate the data with
     *
     * @return mixed
     */
    public function hydrate($className, array $rawData)
    {
        $reflectionClass = new \ReflectionClass($className);
        $constructorParameters = $reflectionClass->getConstructor()->getParameters();
        $callParameters = array();

        /** @var \ReflectionParameter $parameter */
        foreach ($constructorParameters as $parameter) {
            foreach ($this->getPossibleParameterKeys($parameter) as $possibleKey) {
                if (true === isset($rawData[$possibleKey])) {
                    $callParameters[] = $rawData[$possibleKey];
                    break;
                }
            }
        }

        if (count($callParameters) !== count($constructorParameters)) {
            return new $className();
        }

        return $reflectionClass->newInstanceArgs($callParameters);
    }

    /**
     * Hydrates collection of models.
     *
     * Hydrates an array of models from an array containing multiple arrays
     * of raw data.
     *
     * @param string $className         The class name of the model to hydrate for each
     * @param array  $rawDataCollection The raw data collection
     *
     * @return mixed
     */
    public function hydrateCollection($className, array $rawDataCollection)
    {
        $hydratedObjects = array();
        foreach ($rawDataCollection as $rawData) {
            $hydratedObjects[] = $this->hydrate($className, $rawData);
        }

        return $hydratedObjects;
    }

    /**
     * Guesses possible raw data keys from a constructor parameter name.
     *
     * @param \ReflectionParameter $parameter The parameter to guess keys for
     *
     * @return array
     */
    private function getPossibleParameterKeys(\ReflectionParameter $parameter)
    {
        $parameterString = new Stringy($parameter->getName());

        return array(
            (string) $parameterString->underscored(),
            // we need to snake case any parameter that has a number in as well (e.g. "alpha2"
            // could be "alpha_2" in the raw data array)
            (string) $parameterString->regexReplace('([a-z]+)([0-9]+)', '\1_\2'),
            $parameter->getName()
        );
    }
} 