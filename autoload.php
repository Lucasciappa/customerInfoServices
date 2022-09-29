<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
spl_autoload_register(['infoServicesAutoload', 'find']);

/**
 * Nueva clase totalmente estatica para gestionar el autoload y poder organizar los scripts por servicio.  
 * Los archivos se crean segiendo las indicaciones que se detallan va a poder ser auto-cargados por esta clase.  
 * Cada vez que se agrega un nuevo servicio, se le crea una carpeta con el nombre del servico
 *  y un archivo para cada clase que se le va a generar. Cada conjunto de clase-archivo debe compartir el mismo nombre
 *  y a la vez este debe comenzar con el nombre de la carpeta.
 * 
 * Ejemplo de la estrctura que se asume:  
 * nosis/  
 *   └ nosisService.php  
 *   └ nosisAddressParser.php  
 *   └ nosisCustomerInfo.php  
 * 
 * @author Jasu - Junio/2021
 */
abstract class infoServicesAutoload
{
    static $class_paths;
    static $include_folders = array('models');
    static $exclude_folders = array('use');
    static $exclude_files = array('config.php', 'autoload.php');
    static $scanned = false;

    static function find(string $class): void
    {
        $class_paths = self::getClassPaths();
        if (isset($class_paths[$class])) {
            include_once $class_paths[$class];
        }
    }

    static function getClassPaths(): array
    {
        if (!self::$scanned) {
            self::$class_paths = self::scan();
            self::$scanned = true;
        }

        return self::$class_paths;
    }

    static function scan(string $dir = __DIR__, string $include = null): array
    {
        $class_paths = array();

        if ($include && $include[0] <> DIRECTORY_SEPARATOR && strpos($include, __DIR__) === false) {
            $include = __DIR__ . DIRECTORY_SEPARATOR . $include;
        }

        foreach (new DirectoryIterator($dir) as $file) {
            if ($file->getExtension() == 'php') {
                if (
                    !in_array($file->getFilename(), self::$exclude_files) &&
                    (!$include || fnmatch($include, $file->getPathname(), FNM_CASEFOLD))
                ) {
                    $class_paths[$file->getBasename('.php')] = $file->getPathname();
                }
            } elseif (
                $file->isDir() && !$file->isDot() &&
                !in_array($file->getFilename(), self::$exclude_folders)
            ) {
                $sub_include = (
                    in_array($file->getFilename(), self::$include_folders) ? null
                    : $file->getFilename() . DIRECTORY_SEPARATOR . $file->getFilename() . '*'
                );

                $sub_dir = self::scan($file->getPathname(), $sub_include);
                $class_paths = array_merge($class_paths, $sub_dir);
            }
        }

        return $class_paths;
    }
}

?>