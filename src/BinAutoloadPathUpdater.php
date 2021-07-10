<?php

declare(strict_types=1);

namespace WyriHaximus\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;

use function array_key_exists;
use function array_pad;
use function array_shift;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_dir;
use function rtrim;
use function sprintf;
use function str_replace;

use const DIRECTORY_SEPARATOR;

final class BinAutoloadPathUpdater implements PluginInterface, EventSubscriberInterface
{
    private const MINUS_ONE = -1;
    private const ZERO      = 0;
    private const ONE       = 1;

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
        $vendorDir      = $event->getComposer()->getConfig()->get('vendor-dir');
        $autoloaderPath = $vendorDir . DIRECTORY_SEPARATOR . 'autoload.php';

        foreach ($event->getComposer()->getRepositoryManager()->getLocalRepository()->getCanonicalPackages() as $package) {
            self::updatePackage($package, $vendorDir, $autoloaderPath);
        }

        self::updatePackage($event->getComposer()->getPackage(), $vendorDir, $autoloaderPath);
    }

    private static function updatePackage(PackageInterface $package, string $vendorDir, string $autoloaderPath): void
    {
        if (! array_key_exists('wyrihaximus', $package->getExtra())) {
            return;
        }

        if (! array_key_exists('bin-autoload-path-update', $package->getExtra()['wyrihaximus'])) {
            return;
        }

        foreach ($package->getExtra()['wyrihaximus']['bin-autoload-path-update'] as $binPath) {
            $vendorPath             = self::getVendorPath($vendorDir, $package);
            $relativeAutoloaderPath = self::getRelativePath(dirname($vendorPath . $binPath), $autoloaderPath);
            self::updateBinPath($vendorPath . $binPath, $relativeAutoloaderPath);
        }
    }

    private static function updateBinPath(string $binPath, string $autoloaderPath): void
    {
        file_put_contents(
            $binPath,
            sprintf(
                /** @phpstan-ignore-next-line */
                file_get_contents(
                    $binPath . '.source'
                ),
                $autoloaderPath,
            ),
        );
    }

    private static function getVendorPath(string $vendorDir, PackageInterface $package): string
    {
        if ($package instanceof RootPackageInterface) {
            return dirname($vendorDir) . DIRECTORY_SEPARATOR;
        }

        return $vendorDir . DIRECTORY_SEPARATOR . $package->getName() . DIRECTORY_SEPARATOR;
    }

    private static function getRelativePath(string $from, string $to): string
    {
        // some compatibility fixes for Windows paths
        $from = is_dir($from) ? rtrim($from, '\/') . '/' : $from;
        $to   = is_dir($to)   ? rtrim($to, '\/') . '/'   : $to;
        $from = str_replace('\\', '/', $from);
        $to   = str_replace('\\', '/', $to);

        $from    = explode('/', $from);
        $to      = explode('/', $to);
        $relPath = $to;

        foreach ($from as $depth => $dir) {
            // find first non-matching dir
            if ($dir === $to[$depth]) {
                // ignore this directory
                array_shift($relPath);
            } else {
                // get number of remaining dirs to $from
                $remaining = count($from) - $depth;
                if ($remaining >= self::ZERO) {
                    // add traversals up to first matching dir
                    $padLength = (count($relPath) + $remaining - self::ONE) * self::MINUS_ONE;
                    $relPath   = array_pad($relPath, $padLength, '..');
                    break;
                }

                $relPath[self::ZERO] = './' . $relPath[self::ZERO];
            }
        }

        return implode('/', $relPath);
    }
}
