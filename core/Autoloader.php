<?php
namespace Core;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            $prefixes = [
                'Core\\' => __DIR__ . '/',
                'Commands\\' => __DIR__ . '/../commands/'
            ];
            foreach ($prefixes as $prefix => $baseDir) {
                if (strpos($class, $prefix) === 0) {
                    $relativeClass = substr($class, strlen($prefix));
                    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                    if (file_exists($file)) {
                        require $file;
                    }
                }
            }
        });
    }
}
