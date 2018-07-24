<?php
namespace Splinter\Composer\WordPress;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;

class Plugin implements PluginInterface {
	/**
	 * Apply plugin modifications to composer
	 *
	 * @param Composer    $composer
	 * @param IOInterface $io
	 */
	public function activate( Composer $composer, IOInterface $io ) {
		$installer = new Splinter\Composer\WordPress\Installer( $io, $composer );
		$composer->getInstallationManager()->addInstaller( $installer );
	}
}