<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8d58fb635f5f40e71ce2e5323afdd58a
{
    public static $classMap = array (
        'geoPHP' => __DIR__ . '/..' . '/phayes/geophp/geoPHP.inc',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->classMap = ComposerStaticInit8d58fb635f5f40e71ce2e5323afdd58a::$classMap;

        }, null, ClassLoader::class);
    }
}