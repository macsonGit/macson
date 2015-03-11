<?php

namespace Custom\ProjectBundle\Twig;

use Symfony\Component\HttpKernel\KernelInterface;

class AssetExistsExtension extends \Twig_Extension
{

    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function getFunctions()
    {
        return array(
                'asset_exists' => new \Twig_Function_Method($this, 'asset_exists'),
        );
    }

    public function asset_exists($path)
    {
        $webRoot = realpath($this->kernel->getRootDir() . '/../web/');
        $toCheck = realpath($webRoot .'/'. $path);

        // check if the file exists
        if (!is_file($toCheck))
        {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)

        return true;
    }

    public function getName()
    {
        return 'asset_exists';
    }

}
