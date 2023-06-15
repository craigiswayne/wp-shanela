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
    protected $wpCoreDirectory = 'wordpress';
    
    public function activate(Composer $composer, IOInterface $io) {
        $this->composer = $composer;
        $this->io = $io;
        $this->wpCoreDirectory  = $this->getWPCoreInstallDirectory();
    }
    
    public function deactivate(Composer $composer, IOInterface $io){
        return;
    }
    
    public function uninstall(Composer $composer, IOInterface $io){
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
    
    private function getOption(string $optionName, bool $defaultValue): bool {
        $extra = $this->composer->getPackage()->getExtra();
        if(!isset($extra['wp-shanela']) || !isset($extra['wp-shanela'][$optionName])){
            return $defaultValue;
        }
        return $extra['wp-shanela'][$optionName];
    }

    /**
     * Gets the config used for the WordPress core install directory,
     * if none is found, uses the default value, i.e. wordpress
     * @return string
     */
    private function getWPCoreInstallDirectory(){
        
        $extra = $this->composer->getPackage()->getExtra();
        $this->wpCoreDirectory = isset( $extra['wordpress-install-dir'] ) ? $extra['wordpress-install-dir'] : $this->wpCoreDirectory;
        
        self::debug( 'WordPress Core directory found at: '. $this->wpCoreDirectory );
        
        return $this->wpCoreDirectory;
    }
    
    /**
     * Move files from one directory to another
     * @param $sourceDir
     * @param $targetDir
     *
     * @return void
     */
    private function moveFiles($sourceDir, $targetDir) {
        self::debug("Moving files from $sourceDir -> $targetDir");
        $filesFoundInDirectory = scandir($sourceDir);
        
        $filesToExclude = [
            '.',
            '..',
            'license.txt',
            'readme.html',
            'composer.json'
        ];
        
        foreach($filesFoundInDirectory as $fileName) {
            $sourceFile = $sourceDir.DIRECTORY_SEPARATOR.$fileName;
            
            if (in_array($fileName, $filesToExclude)) {
                if (!is_dir($sourceFile)) {
                    self::debug("Deleting excluded file: $sourceFile");
                    unlink($sourceFile);
                }
                continue;
            }
            
            if(is_dir($sourceFile)){
                $fileNameDirectories  = explode(DIRECTORY_SEPARATOR, $sourceFile);
                array_shift($fileNameDirectories);
                $newDestination = join(DIRECTORY_SEPARATOR, $fileNameDirectories);
                $this->moveFiles($sourceFile, $newDestination.DIRECTORY_SEPARATOR); // todo: async this beesh
                continue;
            }
            
            $destinationFile = $targetDir.$fileName;
            self::debug("Moving file from $sourceFile -> $destinationFile");
            
            $this->maybeMakeDirectories($destinationFile);
            rename($sourceFile, $destinationFile);
        }
        
        if($this->dirIsEmpty($sourceDir)){
            self::debug("Deleting empty folder: $sourceDir");
            rmdir($sourceDir);
        }
    }
    
    /**
     * Makes the folder structure for the proposed filename
     * @param $proposedFileName
     *
     * @return void
     */
    private function maybeMakeDirectories($proposedFileName): void {
        if(is_dir(dirname($proposedFileName))){
            return;
        }
        
        self::debug("Making directory: ". dirname($proposedFileName));
        mkdir(dirname($proposedFileName), 0777, true);
    }
    
    private function dirIsEmpty($dir): bool {
        return (count(scandir($dir)) == 2);
    }
    
    private function findAllFilesInDirectory($directory) {
        $filesFound = [];
        $excludedFiles = ['.', '..'];
        foreach(scandir($directory) as $filename){
            
            if(in_array($filename, $excludedFiles)){
                continue;
            }
            
            $fullFileName = "$directory".DIRECTORY_SEPARATOR.$filename;
            
            if(!is_dir($fullFileName)){
                $filesFound[] = $fullFileName;
                continue;
            }
            
            $nestedFilesFound = $this->findAllFilesInDirectory($fullFileName);
            $filesFound = array_merge($filesFound, $nestedFilesFound);
        }
        return $filesFound;
    }
    
    private function destroyDirectory($directory): void {
        if(!is_dir($directory)){
            return;
        }
        
        $excludedFiles = ['.', '..'];
        foreach(scandir($directory) as $filename){
            
            if(in_array($filename, $excludedFiles)){
                continue;
            }
            
            $fullFileName = "$directory".DIRECTORY_SEPARATOR.$filename;
            
            if(!is_dir($fullFileName)){
                unlink($fullFileName);
                continue;
            }
            
            $this->destroyDirectory($fullFileName);
        }
        
        if($this->dirIsEmpty($directory)){
            rmdir($directory);
        }
    }
    
    private function removeDefaultPlugins(){
        self::log('Removing Plugin: akismet');
        $this->destroyDirectory($this->wpCoreDirectory.DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR."akismet");
        self::log('Removing Plugin: hello.php');
        unlink($this->wpCoreDirectory.DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR."plugins".DIRECTORY_SEPARATOR."hello.php");
    }
    
    private function removeDefaultThemes(){
        $themes = ['twentytwentyone', 'twentytwentytwo', 'twentytwentythree'];
        foreach($themes as $theme){
            self::log("Removing Theme: $theme");
            $this->destroyDirectory($this->wpCoreDirectory.DIRECTORY_SEPARATOR."wp-content".DIRECTORY_SEPARATOR."themes".DIRECTORY_SEPARATOR.$theme);
        }
    }

    public function postAutoloadDump(Event $event){
        // TODO: check if wordpress directory exists
        if(!is_dir($this->wpCoreDirectory)){
            self::log("Not moving anything since the wordpress directory does not exist");
            return;
        }

        if($this->getOption('removeDefaultThemes', true)){
            self::removeDefaultThemes();
        }
        if($this->getOption('removeDefaultPlugins', true)) {
            self::removeDefaultPlugins();
        }
        self::log("Moving files from $this->wpCoreDirectory -> ./");
        $this->moveFiles($this->wpCoreDirectory, './');
    }
}
