<?php
namespace Apie\Tests\ServiceProviderGenerator;

use Apie\ServiceProviderGenerator\ServiceProviderGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Finder\Finder;

class ServiceProviderGeneratorTest extends TestCase
{
    #[Test]
    #[DataProvider('generatedCodeProvider')]
    public function it_generates_code(string $expectedOutputFile, string $inputFile)
    {
        $testItem = new ServiceProviderGenerator();
        $actualCode = $testItem->generateClass('Example\\OfSomeClass\\Test', $inputFile);
        file_put_contents($expectedOutputFile, $actualCode);
        $this->assertEquals(
            file_get_contents($expectedOutputFile),
            $actualCode
        );
    }

    public static function generatedCodeProvider()
    {
        $files = Finder::create()->in(__DIR__ . '/fixtures')->files()->name('*.yaml');
        foreach ($files as $inputFile) {
            yield [str_replace('.yaml', '.phpinc', (string) $inputFile), (string) $inputFile];
        }
    }
}
