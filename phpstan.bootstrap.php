<?php

declare(strict_types=1);

/**
 * Loads the PHPStan autoloader of Magento, which generates factories and extension attributes for the analysis.
 *
 * Factories are generated with the factory generator of Magento itself, as setup:di:compile does, so that create()
 * returns the created class (the stubs of the Magento test framework return nothing, which PHPStan sees as mixed). No
 * generator runs for classes that Composer finds outside of the generated directory: Magento has real classes with names
 * like generated ones (Magento\Shipping\Model\CarrierFactory, ...), whose methods have other signatures.
 *
 * Extension attributes (OrderExtensionInterface, ...) are generated with the getters and setters of the attributes that
 * the etc/extension_attributes.xml files of Magento, the installed modules and this module declare, as at runtime; the
 * generator of the Magento test framework creates them without methods.
 *
 * @author      Andreas Knollmann
 * @copyright   Copyright (c) 2014-2026 Softwareentwicklung Andreas Knollmann
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 */

$magentoRoot = getenv('MAGENTO_ROOT') ?: '/var/www/magento';

$composerLoader = require rtrim($magentoRoot, '/') . '/vendor/autoload.php';

require rtrim($magentoRoot, '/') . '/dev/tests/static/framework/Magento/PhpStan/autoload.php';

/** @var \Magento\Framework\TestFramework\Unit\Autoloader\GeneratedClassesAutoloader $generatedCodeAutoloader */
spl_autoload_unregister([$generatedCodeAutoloader, 'load']);

$factoryIo = new \Magento\Framework\Code\Generator\Io(
    new \Magento\Framework\Filesystem\Driver\File(),
    TESTS_TEMP_DIR . '/phpstan/generated'
);

/**
 * Returns the extension attributes of all etc/extension_attributes.xml files by data interface.
 *
 * @return array<string, mixed>
 */
$extensionAttributes = static function () use ($magentoRoot): array {
    static $attributes = null;

    if ($attributes === null) {
        $attributes = [];
        $converter = new \Magento\Framework\Api\ExtensionAttribute\Config\Converter();
        $root = rtrim($magentoRoot, '/');
        $files = array_merge(
            glob($root . '/vendor/*/*/etc/extension_attributes.xml') ?: [],
            glob($root . '/app/code/*/*/etc/extension_attributes.xml') ?: [],
            glob(__DIR__ . '/src/etc/extension_attributes.xml') ?: []
        );

        foreach ($files as $file) {
            $document = new DOMDocument();

            if (! $document->load($file)) {
                continue;
            }

            // Magento merges the files before converting them, the converter keeps only the last element of a type
            foreach ($document->getElementsByTagName('extension_attributes') as $element) {
                $single = new DOMDocument();
                $single->appendChild($single->createElement('config'))
                    ->appendChild($single->importNode($element, true));

                $attributes = array_replace_recursive($attributes, $converter->convert($single));
            }
        }
    }

    return $attributes;
};

// extension attributes classes that are being generated, see below
$generatingExtensionAttributes = [];

/**
 * Generates the file of the extension attributes class or interface (with the suffix Extension or ExtensionInterface)
 * of a data interface, if it does not exist yet.
 */
$generateExtensionAttributes = static function (string $className) use (
    $extensionAttributes,
    $factoryIo,
    &$generatingExtensionAttributes
): void {
    $isInterface = str_ends_with($className, 'ExtensionInterface');
    $sourceClassName = substr($className, 0, $isInterface ? -18 : -9);
    $resultFile = $factoryIo->generateResultFileName($className);

    if (! $factoryIo->fileExists($resultFile)) {
        $generatingExtensionAttributes[ $className ] = true;

        // the configuration of the extension attributes, read from the files instead of the cache of Magento
        $config = new class ($extensionAttributes()) extends \Magento\Framework\Api\ExtensionAttribute\Config {
            /**
             * @param array<string, mixed> $attributes
             */
            public function __construct(private readonly array $attributes)
            {
            }

            /**
             * @param string|null $path
             * @param mixed $default
             *
             * @return mixed
             */
            public function get($path = null, $default = null)
            {
                return $path === null ? $this->attributes : ($this->attributes[ $path ] ?? $default);
            }
        };

        $generator = $isInterface ?
            new \Magento\Framework\Api\Code\Generator\ExtensionAttributesInterfaceGenerator(
                $config,
                $sourceClassName,
                $className,
                $factoryIo
            ) : new \Magento\Framework\Api\Code\Generator\ExtensionAttributesGenerator(
                $config,
                $sourceClassName,
                $className,
                $factoryIo
            );

        // the generator gets its type processor from the object manager, which does not exist here
        \Closure::bind(
            function (): void {
                $this->typeProcessor = new \Magento\Framework\Reflection\TypeProcessor();
            },
            $generator,
            \Magento\Framework\Api\Code\Generator\ExtensionAttributesGenerator::class
        )();

        try {
            $generator->generate();
        } finally {
            unset($generatingExtensionAttributes[ $className ]);
        }
    }
};

spl_autoload_register(
    static function (string $className) use (
        $composerLoader,
        $generatedCodeAutoloader,
        $factoryIo,
        &$generatingExtensionAttributes
    ): void {
        static $generating = [];

        $file = $composerLoader->findFile($className);

        if ($file !== false && ! str_contains(str_replace('\\', '/', $file), '/generated/code/')) {
            // a real class, which Composer loads itself
            return;
        }

        if (str_ends_with($className, 'Extension') || str_ends_with($className, 'ExtensionInterface')) {
            if (isset($generatingExtensionAttributes[ $className ])) {
                // the generator checks whether the class exists, which must not generate it in another way
                return;
            }

            $resultFile = $factoryIo->generateResultFileName($className);

            if ($factoryIo->fileExists($resultFile)) {
                // generated below with the attributes
                include $resultFile;

                return;
            }
        }

        if (str_ends_with($className, 'Factory') && ! isset($generating[ $className ])) {
            $resultFile = $factoryIo->generateResultFileName($className);

            if (! $factoryIo->fileExists($resultFile)) {
                $sourceClassName = substr($className, 0, -7);

                $generating[ $className ] = true;

                try {
                    if (class_exists($sourceClassName) || interface_exists($sourceClassName)) {
                        (new \Magento\Framework\ObjectManager\Code\Generator\Factory(
                            $sourceClassName,
                            $className,
                            $factoryIo
                        ))->generate();
                    }
                } finally {
                    unset($generating[ $className ]);
                }
            }

            if ($factoryIo->fileExists($resultFile)) {
                include $resultFile;

                return;
            }
        }

        $generatedCodeAutoloader->load($className);
    }
);

// The extension attributes are generated before the analysis: PHPStan calls the autoloaders only to find the file of a
// class, without loading other classes, so the generator cannot run then. Data interfaces without extension attributes
// get the empty ones of the Magento test framework.
foreach (array_keys($extensionAttributes()) as $dataInterface) {
    if (str_ends_with($dataInterface, 'Interface') && interface_exists($dataInterface)) {
        $generateExtensionAttributes(substr($dataInterface, 0, -9) . 'ExtensionInterface');
        $generateExtensionAttributes(substr($dataInterface, 0, -9) . 'Extension');
    }
}
