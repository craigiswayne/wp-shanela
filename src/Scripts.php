<?php

namespace Splinter\Composer\WordPress;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;

/**
 * Class Scripts
 *
 * @see     For colors: https://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 *
 * @package Splinter\Composer\WordPress
 */
class Scripts implements PluginInterface, EventSubscriberInterface
{
	const COLOR_LIGHT_BLUE = "\033[34m";
	const COLOR_LIGHT_GREEN = "\033[32m";
	const COLOR_RED = "\033[31m";
	const COLOR_WHITE = "\033[0m";
	protected $composer;
	protected $io;
	protected $isVerbose = false;
	
	public function activate(Composer $composer, IOInterface $io)
	{
		$this->composer = $composer;
		$this->io = $io;
		$this->isVerbose = $this->io->isVerbose();
		
	}
	
	/**
	 * @param $message
	 */
	private function log( $message ){
		
		if( $this->isVerbose ){
			echo PHP_EOL.self::COLOR_LIGHT_BLUE.debug_backtrace()[1]['class'].'\\'.debug_backtrace()[1]['function'].self::COLOR_WHITE.PHP_EOL;
		}
		
		echo $message.PHP_EOL;
	}
	
	/**
	 * @see https://getcomposer.org/doc/articles/plugins.md
	 * @return array
	 */
	public static function getSubscribedEvents()
	{
		return array(
			'post-autoload-dump' => array(
				array( 'postAutoloadDump', 1 )
			)
		);
	}
	
	private function rsyncWPCoreToProjectRoot(){
		self::log("rsync'ing the WordPress Core files to the Project Root...");
		exec("if [ -d wordpress-core ]; then rsync -rtlpP wordpress-core/* ./ --exclude='composer.json' --exclude='vendor'; fi" );
	}
	
	private function removeWPCoreInstallationDirectory(){
		self::log("Removing the WordPress Core installation Directory...");
		exec("if [ -d wordpress-core ]; then rm -rf wordpress-core; fi" );
	}
	
	private function removeHelloPlugin(){
		self::log("Removing hello.php plugin...");
		exec("if [ -f wp-content/plugins/hello.php ]; then rm wp-content/plugins/hello.php; fi" );
	}
	
	private function removeStandardThemes(){
		self::log("Removing standard WordPress Themes...");
		exec('rm -rf wp-content/themes/twenty*');
	}
	
	/**
	 * called via postAutoloadDump
	 *
	 * @see postAutoloadDump
	 */
	private function cleanup(){
		self::rsyncWPCoreToProjectRoot();
		self::removeWPCoreInstallationDirectory();
		self::removeHelloPlugin();
		self::removeStandardThemes();
	}
	
	public function postAutoloadDump( Event $event){
		self::cleanup();
	}
}