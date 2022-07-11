<?php 

namespace FusionExport;

class Helpers
{
    public static function startsWith($string, $query)
    {
        return substr($string, 0, strlen($query)) === $query;
    }

    public static function endsWith($string, $query)
    {
        return substr($string, strlen($query) * -1) === $query;
    }

    public static function findCommonPath($paths) 
    {
        sort($paths, SORT_STRING);
        if (count($paths) === 0) return '';
        $paths = array_map(function ($pa) {
            return explode(DIRECTORY_SEPARATOR, $pa);
        }, $paths);
        $p1 = $paths[0];
        $p2 = $paths[count($paths) - 1];
        $l = count($p1);
        $i = 0;
        while ($i < $l && @$p1[$i] === @$p2[$i]) $i += 1;
        return implode(DIRECTORY_SEPARATOR, array_slice($p1, 0, $i));
    }
      
    public static function removeCommonPath($path, $base) 
    {
        $pathSpl = explode(DIRECTORY_SEPARATOR, $path);
        $baseSpl = explode(DIRECTORY_SEPARATOR, $base);
        $l = count($pathSpl);
        $i = 0;
        while ($i < $l && @$pathSpl[$i] === @$baseSpl[$i]) $i += 1;
        return implode(DIRECTORY_SEPARATOR, array_slice($pathSpl, $i));
    }

    public static function isChildPath($path, $base)
    {
        $path = explode(DIRECTORY_SEPARATOR, $path);
        $base = explode(DIRECTORY_SEPARATOR, $base);
        $l = count($base);
        $i = 0;
        while ($i < $l && @$path[$i] === @$base[$i]) $i += 1;
        if ($i === $l) return true;
        return false;
    }

    public static function resolvePaths($paths, $base)
    {
        if (count($paths) === 0) return [];

        $cwd = getcwd();
        $basePath = realpath($base);
        
        chdir($basePath);

        $resolvedPaths = array_map(function ($p) {
            if (!isset($p)) return false;
            return realpath($p);
        }, $paths);

        chdir($cwd);

        return $resolvedPaths;
    }

    public static function globResolve(&$outListResourcePaths, &$outBaseDirectoryPath, $resources)
    {
        $baseDirectoryPath = null;
		$listResourcePaths = array();
		$listResourceIncludePaths = array();
		$listResourceExcludePaths = array();
		$resourceFilePath = realpath($resource);
		$resourceDirectoryPath = dirname($resourceFilePath);
		$reources = json_decode(file_get_contents($resourceFilePath),true);
		if($resources.resolvePaths != null){
			$resourceDirectoryPath = realpath($resources.resolvePaths);
		}
		chdir($resourceDirectoryPath);
		$includes = $resources['include'];
		foreach(includes as $include){
			array_push($listResourceIncludePaths,glob($include));
		}
		foreach($resources.exclude as $exclude){
			array_push($listResourceExcludePaths,glob($exclude));
		}
		$outListResourcePaths = array_diff(listResourceIncludePaths,listResourceExcludePaths);
		$outBaseDirectoryPath = $resources.basePath;
		
    }

    public static function convertFilePathToBase64($val)
    {
        return base64_encode(file_get_contents($val));
    }
}