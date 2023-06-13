<?php

namespace Splinter\Composer\WordPress;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;
use Composer\Script\ScriptEvents as ScriptEvents;

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
    // TODO: streamline this fuckn thing
    protected $wpCoreDirectory = 'wordpress'; //this is the default wp core install directory

    public function activate(Composer $composer, IOInterface $io)
    {
        $this->composer = $composer;
        $this->io = $io;
        $this->wpCoreDirectory  = $this->getWPCoreInstallDirectory();
    }

    public function deactivate(Composer $composer, IOInterface $io){
        //$this->$this->log('TODO: Deactivate');
        return;
    }

    public function uninstall(Composer $composer, IOInterface $io){
        //$this->$this->log('TODO: Uninstall');
        return;
    }

    /**
     * Prints out a blue message to the console only if the composer is run with debug on, i.e. -vv
     * @param $message
     */
    private function debug( $message ){
        if( !$this->io->isVerbose() ){
            return;
        }
        echo PHP_EOL.self::COLOR_LIGHT_BLUE.$message.self::COLOR_WHITE.PHP_EOL;
    }

    /**
     * Prints out a message to the console
     * @param $message
     */
    private function log( $message ){
        self::debug( debug_backtrace()[1]['class'].'\\'.debug_backtrace()[1]['function'] );
        echo $message.PHP_EOL;
    }

    /**
     * @see https://getcomposer.org/doc/articles/plugins.md
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            ScriptEvents::POST_AUTOLOAD_DUMP => array(
                array( 'postAutoloadDump', 1 )
            )
        );
    }

    /**
     * Gets the config used for the wordpress core install directory,
     * if none is found, uses the default value, i.e. wordpress
     * @return string
     */
    private function getWPCoreInstallDirectory(){

        $extra = $this->composer->getPackage()->getExtra();
        $this->wpCoreDirectory = isset( $extra['wordpress-install-dir'] ) ? $extra['wordpress-install-dir'] : $this->wpCoreDirectory;

        self::debug( 'WordPress Core directory found at: '. $this->wpCoreDirectory );

        return $this->wpCoreDirectory;
    }


    private function rsyncWPCoreToProjectRoot(){
        self::log("rsync'ing the WordPress Core files to the Project Root...");
        exec("if [ -d ".$this->wpCoreDirectory." ]; then rsync -rtlpP ".$this->wpCoreDirectory."/* ./ --exclude='composer.json' --exclude='vendor'; fi" );
    }


    private function removeWPCoreInstallationDirectory(){
        self::log("Removing the WordPress Core installation Directory...");
        exec("if [ -d ".$this->wpCoreDirectory." ]; then rm -rf ".$this->wpCoreDirectory."; fi" );
    }

    /**
     * Removes the hello and akismet plugin
     */
    private function removeDefaultPlugins(){
//        self::log("Removing hello.php plugin...");
//        exec("if [ -f wp-content/plugins/hello.php ]; then rm wp-content/plugins/hello.php; fi" );
//        self::log("Removing akismet plugin...");
//        exec("if [ -d wp-content/plugins/akismet ]; then rm -rf wp-content/plugins/akismet; fi" );
    }


    private function removeStandardThemes(){
        self::log("Removing standard WordPress Themes...");
        exec('rm -rf wp-content/themes/twenty*');
    }

    private function copyFilesFrom($sourceDir,$targetDir) {
        $files = scandir($sourceDir);
        $oldfolder = "$sourceDir/";
        $filesToExclude = [
            '.',
            '..',
            'license.txt',
            'readme.html',
            'wp-config-sample.php',
            'composer.json'
        ];

        foreach($files as $fname) {
            $sourceFile = $oldfolder.$fname;

            print_r("\n\n");
            print_r("file: $fname\n");

            if (in_array($fname, $filesToExclude)) {
                print_r("excluded: $sourceFile\n");
                if (is_dir($sourceFile)) {
                    print_r("but its a directory\n");
                } else {
                    print_r("removing \n");
                    unlink($sourceFile);
                }
                continue;
            }

            $destinationFile = $targetDir.$fname;

            if(is_dir($destinationFile)){
                print_r("its a directory, skipping\n");
                continue;
            }

            print_r("copying file: $fname\n");
            rename($sourceFile, $destinationFile);
        }
    }

    /**
     * called via postAutoloadDump
     *
     * @see postAutoloadDump
     */
    private function cleanup(){

        $this->copyFilesFrom($this->wpCoreDirectory, './');

//        $files = scandir($this->wpCoreDirectory);
//        $oldfolder = "$this->wpCoreDirectory/";
//        $newfolder = "./";
//        $filesToExclude = [
//            '.',
//            '..',
//            'license.txt',
//            'readme.html',
//            'wp-config-sample.php',
//            'composer.json'
//        ];
//
//        foreach($files as $fname) {
//            print_r("\n\n");
//            print_r("file: $fname\n");
//
//            if(in_array($fname, $filesToExclude)){
//                print_r("exluded file: $fname\n");
//                if(is_dir($oldfolder.$fname)){
//                    print_r("NOT REMOVING: $fname \n");
//                } else {
//                    print_r("removing: $fname \n");
//                    unlink($oldfolder.$fname);
//                }
//                continue;
//            }
//
////            if(is_dir($fname) ){
////                print_r("is_dir: skipping this file $fname \n");
////                continue;
////            }
//
//
////            print_r("applying to $fname \n");
//            // TODO: merge wp-content folder
//            if($fname == 'wp-content' || $fname == 'wp-admin' || $fname == 'wp-includes') {
//                continue;
//            }
//
//            rename($oldfolder.$fname, $newfolder.$fname);
//        }

        // Delete the wordpress folder
//        rmdir($this->wpCoreDirectory);

//		self::rsyncWPCoreToProjectRoot();
//		self::removeWPCoreInstallationDirectory();
//		self::removeDefaultPlugins();
//		self::removeStandardThemes();
    }


    public function postAutoloadDump( Event $event){
        self::cleanup();
    }
}
