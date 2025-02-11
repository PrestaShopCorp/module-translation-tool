<?php

namespace Tests\AppBundle\Extract\Dumper;

use AppBundle\Extract\Dumper\XliffFileDumper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Translation\MessageCatalogue;

class XliffFileDumperTest extends TestCase
{
    private $dumper;
    private $filesystem;
    private $tempDir;

    protected function setUp(): void
    {
        $this->dumper = new XliffFileDumper();
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/xliff_dumper_test';

        $this->filesystem->remove($this->tempDir);
        $this->filesystem->mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->tempDir);
    }

    public function testDumpThrowsExceptionWhenPathIsMissing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The file dumper needs a path option.');

        $messages = new MessageCatalogue('en');
        $this->dumper->dump($messages, []);
    }

    public function testFormatCatalogueReturnsValidXliff()
    {
        $messages = new MessageCatalogue('fr', [
            'messages' => [
                'key1' => 'valeur1',
                'key2' => 'valeur2'
            ]
        ]);

        $options = [
            'default_locale' => 'en'
        ];

        $xmlString = $this->dumper->formatCatalogue($messages, 'messages', $options);
        var_dump($xmlString);
        $this->assertStringContainsString('<xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">', $xmlString);
        $this->assertStringContainsString('<file original="messages" source-language="en" target-language="fr" datatype="plaintext">', $xmlString);
        $this->assertStringContainsString('<source>key1</source>', $xmlString);
        $this->assertStringContainsString('<target>valeur1</target>', $xmlString);
    }

    public function testDumpCreatesFilesCorrectly()
    {
        $domain = 'messages';
        $messages = new MessageCatalogue('fr', [
            $domain => [
                'hello' => 'bonjour',
                'goodbye' => 'au revoir'
            ]
        ]);
        $messages->setMetadata('hello', ['file' => 'src/Example.php', 'line' => 42], $domain);

        $options = [
            'path' => $this->tempDir,
            'default_locale' => 'en'
        ];

        $this->dumper->dump($messages, $options);

        $expectedFile = sprintf('%s/fr/' . $domain . '.en.xlf', $this->tempDir);
        $this->assertFileExists($expectedFile);

        $content = file_get_contents($expectedFile);
        $this->assertStringContainsString(
'<source>hello</source>
        <target>bonjour</target>
        <note>src/Example.php:42</note>',
        $content);
    }
}
