<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8fad106cefe92d933c0ae009a86d1428
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'SpotifyWebAPI\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'SpotifyWebAPI\\' => 
        array (
            0 => __DIR__ . '/..' . '/jwilsson/spotify-web-api-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit8fad106cefe92d933c0ae009a86d1428::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit8fad106cefe92d933c0ae009a86d1428::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit8fad106cefe92d933c0ae009a86d1428::$classMap;

        }, null, ClassLoader::class);
    }
}
