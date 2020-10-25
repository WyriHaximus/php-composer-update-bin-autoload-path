<?php declare(strict_types=1);

namespace WyriHaximus\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use function dirname;
use function file_get_contents;
use function file_put_contents;
use function sprintf;
use const DIRECTORY_SEPARATOR;

final class BinAutoloadPathUpdater implements PluginInterface, EventSubscriberInterface
{
    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [ScriptEvents::PRE_AUTOLOAD_DUMP => 'updateBinPaths'];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // does nothing, see getSubscribedEvents() instead.
    }

    /**
     * Called before every dump autoload, generates a fresh PHP class.
     */
    public static function updateBinPaths(Event $event): void
    {
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoloaderPath = $event->getComposer()->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR . 'autoload.php';
        foreach ($event->getcomposer()->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {
            self::updatePackage($package, $vendorDir, $autoloaderPath);
        }
        self::updatePackage($event->getComposer()->getPackage(), $vendorDir, $autoloaderPath);
    }

    private static function updatePackage(PackageInterface $package, string $vendorDir, string $autoloaderPath): void
    {
        if (!array_key_exists('wyrihaximus', $package->getExtra())) {
            return;
        }

        if (!array_key_exists('bin-autoload-path-update', $package->getExtra()['wyrihaximus'])) {
            return;
        }

        foreach ($package->getExtra()['wyrihaximus']['bin-autoload-path-update'] as $binPath) {
            self::updateBinPath(self::getVendorPath($vendorDir, $package) . $binPath, $autoloaderPath);
        }
    }

    private static function updateBinPath(string $binPath, string $autoloaderPath): void
    {
        file_put_contents(
            $binPath,
            sprintf(
                file_get_contents(
                    $binPath . '.source'
                ),
                $autoloaderPath,
            ),
        );
    }

    public static function getVendorPath(string $vendorDir, PackageInterface $package): string
    {
        if ($package instanceof RootPackageInterface) {
            return dirname($vendorDir) . DIRECTORY_SEPARATOR;
        }

        return $vendorDir . DIRECTORY_SEPARATOR . $package->getName() . DIRECTORY_SEPARATOR;
    }
}
