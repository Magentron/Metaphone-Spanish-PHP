<?php
namespace Magentron\MetaphoneSpanish\Tests;

class MetaphoneSpanishCliTest extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $script = realpath(__DIR__ . '/../src/cli/metaphone_es_cli.php');
        if (!in_array($script, get_included_files())) {
            // create temporary file for input
            $filename = tempnam(sys_get_temp_dir(), 'MSC');
            file_put_contents($filename, "X\n\nY\n");

            // load and run script
            $_SERVER['argv'] = array(
                realpath($script),
                $filename
            );

            ob_start();
            require_once $script;
            $result = ob_get_clean();
            $this->assertEquals("X\nY\n", $result);

            // remove the temporary file
            unlink($filename);
        }
    }

    /**
     * @test
     * @dataProvider words
     */
    public function metaphoneCli($word, $expected)
    {
        ob_start();
        process(1, array($word));
        $result = trim(ob_get_clean());

        $this->assertEquals($expected, $result);
    }
}
