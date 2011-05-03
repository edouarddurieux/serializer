<?php

namespace JMS\SerializerBundle\Tests;

use JMS\SerializerBundle\Tests\Fixtures\Comment;

use JMS\SerializerBundle\Tests\Fixtures\Author;

use JMS\SerializerBundle\Tests\Fixtures\BlogPost;
use Annotations\Reader;
use JMS\SerializerBundle\Serializer\Exclusion\NoneExclusionStrategy;
use JMS\SerializerBundle\Serializer\Exclusion\AllExclusionStrategy;
use JMS\SerializerBundle\Serializer\Exclusion\ExclusionStrategyFactory;
use JMS\SerializerBundle\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\SerializerBundle\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\SerializerBundle\Serializer\Normalizer\ArrayCollectionNormalizer;
use JMS\SerializerBundle\Serializer\Normalizer\NativePhpTypeNormalizer;
use JMS\SerializerBundle\Serializer\Normalizer\PropertyBasedNormalizer;
use JMS\SerializerBundle\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

class PerformanceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @group performance
     */
    public function testNormalizationPerformance()
    {
        $serializer = $this->getSerializer();
        $testData   = $this->getTestCollection();

        $time = microtime(true);
        for ($i=0,$c=10; $i<$c; $i++) {
            $serializer->normalize($testData);
        }
        $time = microtime(true) - $time;

        $this->printResults('normalization', $time, $c);
    }

    private function getTestCollection()
    {
        $collection = array();
        for ($i=0; $i<50; $i++) {
            $collection[] = $this->getTestObject();
        }

        return $collection;
    }

    private function getTestObject()
    {
        $post = new BlogPost('FooooooooooooooooooooooBAR', new Author('Foo'));
        for ($i=0; $i<10; $i++) {
            $post->addComment(new Comment(new Author('foo'), 'foobar'));
        }

        return $post;
    }

    private function getSerializer()
    {
        $reader = new Reader();

        $propertyNamingStrategy = new SerializedNameAnnotationStrategy(
            $reader,
            new CamelCaseNamingStrategy()
        );

        $encoders = array(
            'xml'  => new XmlEncoder(),
            'json' => new JsonEncoder(),
        );

        $customNormalizers = array(
            new ArrayCollectionNormalizer(),
        );

        $exclusionStrategyFactory = new ExclusionStrategyFactory(array(
            'ALL'  => new AllExclusionStrategy($reader),
            'NONE' => new NoneExclusionStrategy($reader),
        ));

        return new Serializer(
            new NativePhpTypeNormalizer(),
            new PropertyBasedNormalizer($reader, $propertyNamingStrategy, $exclusionStrategyFactory),
            $customNormalizers,
            $encoders
        );
    }

    private function printResults($test, $time, $iterations)
    {
        if (0 == $iterations) {
            throw new \InvalidArgumentException('$iterations cannot be zero.');
        }

        $title = $test." results:\n";
        $iterationsText = sprintf("Iterations:         %d\n", $iterations);
        $totalTime      = sprintf("Total Time:         %.3f s\n", $time);
        $iterationTime  = sprintf("Time per iteration: %.3f ms\n", $time/$iterations * 1000);

        $max = max(strlen($title), strlen($iterationTime)) - 1;

        echo "\n".str_repeat('-', $max)."\n";
        echo $title;
        echo str_repeat('=', $max)."\n";
        echo $iterationsText;
        echo $totalTime;
        echo $iterationTime;
        echo str_repeat('-', $max)."\n";
    }
}