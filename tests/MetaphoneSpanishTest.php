<?php
namespace Magentron\MetaphoneSpanish\Tests;

use Magentron\MetaphoneSpanish\MetaphoneSpanish;

class MetaphoneSpanishTest extends BaseTestCase
{
    /**
     * @test
     * @dataProvider words
     */
    public function metaphone($word, $expected)
    {
        $pa     = new MetaphoneSpanish();
        $result = $pa->metaphone($word);
        $this->assertEquals($expected, $result);
    }

    /** @test */
    public function pythonOutputCheck()
    {
        $exitcode = null;
        exec('python vendor/amsqr/spanish-metaphone/phonetic_algorithms_es.py', $output, $exitcode);
        $this->assertEquals(0, $exitcode, 'exitcode of python script should return 0, got: ' . var_export($exitcode));
        $this->assertNotCount(0, $output);

        $count = 0;
        foreach ($output as $line) {
            $parts = preg_split('/\s*->\s*/', $line);
            $this->metaphone($parts[0], $parts[1]);
            ++$count;
        }

        $this->assertEquals(67, $count);
    }
}
