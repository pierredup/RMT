<?php

namespace Liip\RMT\Compiler;

use Symfony\Component\Finder\Finder;

class Compile
{
    protected $path;

    public function compile($path, $filename = 'RMT.phar')
    {
        if (file_exists($filename)) {
            unlink($filename);
        }

        $this->path = $path;

        $phar = new \Phar($filename, 0, 'RMT.phar');
        $phar->setSignatureAlgorithm(\Phar::SHA1);

        $phar->startBuffering();

        $finder = new Finder();
        $finder->files()
            ->ignoreVCS(true)
            ->name('*.php')
            ->notName('Compiler.php')
            //->notName('ClassLoader.php')
            ->exclude('Tests')
            ->in($this->path.'/src')
            ->in($this->path.'/vendor/composer')
            ->in($this->path.'/vendor/symfony')
        ;

        foreach ($finder as $file) {
            $this->addFile($phar, $file);
        }

        $this->addFile($phar, new \SplFileInfo($this->path.'/vendor/autoload.php'));

        $content = file_get_contents($this->path.'/bin/RMT');
        $content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
        $phar->addFromString('bin/RMT', $content);

        $phar->setStub($this->getStub());

        $phar->stopBuffering();
    }

    private function addFile(\Phar $phar, \SplFileInfo $file)
    {
        $path = str_replace($this->path.DIRECTORY_SEPARATOR, '', $file->getRealPath());

        $content = file_get_contents($file);
        /*if ($strip) {
            $content = $this->stripWhitespace($content);
        }*/

        $phar->addFromString($path, $content);
    }

    private function getStub()
    {
        return <<<'EOF'
#!/usr/bin/env php
<?php

Phar::mapPhar('RMT.phar');

require 'phar://RMT.phar/bin/RMT';

__HALT_COMPILER();
EOF;
    }
}