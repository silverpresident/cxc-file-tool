<?php
/**
 * @version 20190415.11
 */
chdir(__DIR__);
define('APP_LOADED',1);
include_once('prep.php');
include_once('user-check.php');
include_once('pre.inc');


$GET = SAP::getInstance('GET');

if ($GET->str('delete_my_files')){
    if ("dmf{$_SESSION['cxc-user-csrf']}" !=$GET->str('delete_my_files')){
        die('Invalid session token');
    }
    $prefix = $GET->str('delete_prefix');
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(DATA_DIR);
    foreach($dirIter as $F){
        if ($F->isFile()){
            unlink($F->getPathname());
            $i++;
        }
    }
    echo "Deleted files: $i ";
} elseif ($GET->str('delete_prefix')){
    $prefix = $GET->str('delete_prefix');
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(SEQ_DIR);
    $plen = strlen($prefix);
    $i = 0;
    foreach($dirIter as $F){
        if (substr($F->getFilename(),0,$plen) == $prefix){
            unlink($F->getPathname());
            $i++;
        }
    }
    echo "Deleted files: $i ";
} else if ($GET->str('delete_mc_prefix')){
    $prefix = $GET->str('delete_mc_prefix');
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(FINAL_DIR);
    $plen = strlen($prefix);
    $i = 0;
    foreach($dirIter as $F){
        $nm = split_name($F->getFilename());
        if ($nm['mc'] == $prefix){
            unlink($F->getPathname());
            $i++;
        }
    }
    echo "Deleted MC files: $i ";
    
} else if ($GET->str('mc_prefix')){
    $prefix = $GET->str('mc_prefix');
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(FINAL_DIR);
    $plen = strlen($prefix);
    $i = 0;
    $zipFileName = tempnam(sys_get_temp_dir(), "zip"); 
    $zip = new ZipArchive();
    $opened = $zip->open( $zipFileName,  ZIPARCHIVE::OVERWRITE );

    foreach($dirIter as $F){
        $nm = split_name($F->getFilename());
        if ($nm['mc'] == $prefix){
            $zip->addFile($F->getPathname(),$prefix .'/'. $F->getFilename());        
            $i++;
        }
    }
    
    $zip->close();
    header("Content-Type: application/zip"); 
    header("Content-Length: " . filesize($zipFileName)); 
    header("Content-Disposition: attachment; filename=\"{$prefix}.zip\""); 
    readfile($zipFileName); 
    
    unlink($zipFileName); 
} else if ($GET->str('prefix')){
    $prefix = $GET->str('prefix');
    $dirIter = ELIX::FileSystem()->getDirectoryIterator(SEQ_DIR);
    $plen = strlen($prefix);
    $i = 0;
    $zipFileName = tempnam(sys_get_temp_dir(), "zip"); 
    $zip = new ZipArchive();
    $opened = $zip->open( $zipFileName,  ZIPARCHIVE::OVERWRITE );

    foreach($dirIter as $F){
        if (substr($F->getFilename(),0,$plen) == $prefix){
            $zip->addFile($F->getPathname(),$prefix .'/'. $F->getFilename());        
            $i++;
        }
    }
    
    $zip->close();
    header("Content-Type: application/zip"); 
    header("Content-Length: " . filesize($zipFileName)); 
    header("Content-Disposition: attachment; filename=\"{$prefix}.zip\""); 
    readfile($zipFileName); 
    
    unlink($zipFileName); 
    
}