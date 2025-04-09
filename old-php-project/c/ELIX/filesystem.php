<?php
/**
 * @author Edwards
 * @copyright 2015
 * 
 * this class provides tools for file management
 */
namespace ELIX;

class FileSystem {
    public static function getFileObject($file=null)
    {
    	if(func_num_args()>1){
			$file = implode(DIRECTORY_SEPARATOR,func_get_args());
		}
        return new file_object($file);
    }
    public static function getDirectoryIterator($path)
    {
        return new DirectoryIterator($path);
    }
    public static function getETagMaker($file=''){
        return new FileETag($file);
    }
    public static function getFileWriter($file)
    {
    	if(func_num_args()>1){
			$file = implode(DIRECTORY_SEPARATOR,func_get_args());
		}
        return new file_writer($file);
    }
    public static function getDirectory($path)
    {
        return new directory($path);
    }
    public static function getExistingDirectory($path)
    {
        return new existingDirectory($path);
    }
    public static function getExtensionMimeType($filename){
        if($mime = getMimeTypeCommon($filename))
            return $mime;
        if($mime = getMimeTypeExtended($filename))
            return $mime;
        if($mime = getContentMimeType($filename))
            return $mime;
        return 'application/octet-stream';
    }
    
    public static function getMediaType($mime)
    {
        return new mediatype($mime);
    }
    public static function mkdir($path) {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        if(empty($path)){
            return false;
        }
        if(is_dir($path)){
            return true;
        }
        
        $path = rtrim($path,DIRECTORY_SEPARATOR);
        $x = explode(DIRECTORY_SEPARATOR, $path);
        $r = '';
        foreach($x as $f){
            $r .= $f. DIRECTORY_SEPARATOR;
            if(!is_dir($r)) mkdir($r);
        }
        return true;
    }
    
    public static function rmdir($abspath) {
        if(!$abspath){
            return  false;
        }
        if(!is_dir($abspath)){
            return false;
        }
        if(is_link($abspath)){
            return unlink($abspath);
        }
        $files = array_diff(scandir($abspath), array('.','..')); 
        foreach ($files as $file) {
          (is_dir("$abspath/$file") && !is_link("$abspath/$file")) ? self::rmdir("$abspath/$file") : unlink("$abspath/$file"); 
        }
        return rmdir($abspath); 
    }
}


/**
 * Designed to be compatible with the PHP Directory Class returned by the dir() function.
 */
class DirectoryIterator extends \DirectoryIterator
{
    private $is_invalid = false;
    public function __construct($path){
        if (is_dir($path)){
            parent::__construct($path);
        } else {
            $this->is_invalid = true;
        }
    }
    public function getExtension()
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }
    public function read()
    {
        if ($this->is_invalid){
            return false;
        }
        if (!$this->valid()){
            return false;
        }
        $file = $this->getFilename();
        $this->next();
        return $file;
    }
    public function close()
    {
        
    }
}
class directory
{
    protected $path =null;
    protected $debug =array();
    public function __construct($path=''){
        $path = rtrim($path,DIRECTORY_SEPARATOR);
        $this->path = $path . DIRECTORY_SEPARATOR;
    }
    
    public function __get($name){
        $name =strtolower($name);
        if(in_array($name,array('exists','path','mime'))){
            return $this->$name();
        }
        return null;
    }
    public function mime(){
        if(!$this->path) return '';
        return 'directory';
    }
    public function path() {
        return $this->path;
    }
    public function mkdir($path='') {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        $path = rtrim($path,DIRECTORY_SEPARATOR);
        if($path) $path .=DIRECTORY_SEPARATOR;
        $path = $this->path . $path;
        $x = explode(DIRECTORY_SEPARATOR, $path);
        $r = '';
        foreach($x as $f){
            $r .= $f. DIRECTORY_SEPARATOR;
            if(!is_dir($r)) mkdir($r);
        }
    }
    public function rmdir($path='') {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        $path = rtrim($path,DIRECTORY_SEPARATOR);
        if($path) $path .=DIRECTORY_SEPARATOR;
        $path = $this->path . $path;
        
        if(!$path){
            return  false;
        }
        return FileSystem::rmdir($path); 
    }
    public function unlink($path='') {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        
        $file = $this->path . $path;
        if(!is_dir($file)){
            return unlink($file);
        }
        return $this->rmdir($path);
    }
    public function exists($path='') {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        $path = rtrim($path,DIRECTORY_SEPARATOR);
        if($path){
            return file_exists($this->path . $path);
        }else{
            return is_dir($this->path);
        }
    }
    public function getDirectoryIterator($path='')
    {
        if (func_num_args()>1){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        $path = ltrim($path,DIRECTORY_SEPARATOR);
        return new DirectoryIterator($this->path .  $path);
    }
    function getDirectory($path)
    {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        $path = ltrim($path,DIRECTORY_SEPARATOR);
        return new directory($this->path .  $path);
    }
    public function getExistingDirectory($path)
    {
        if($path){
            $path = implode(DIRECTORY_SEPARATOR, func_get_args());
        }
        $path = ltrim($path,DIRECTORY_SEPARATOR);
        return new existingDirectory($this->path .  $path);
    }
    public function getBasename()
    {
        return basename($this->path);
    }
    public function getPathname()
    {
        if (func_num_args()){
            $file = implode(DIRECTORY_SEPARATOR,func_get_args());
            $file = ltrim($file,DIRECTORY_SEPARATOR);
        } else {
            $file = '';
        }
        
        if (strpos($file,DIRECTORY_SEPARATOR)){
            $r = $this->path;
            $x = explode(DIRECTORY_SEPARATOR, $file);
            array_pop($x);
            foreach ($x as $f){
                $r .= $f. DIRECTORY_SEPARATOR;
                if (!is_dir($r)) mkdir($r);
            }
        }
        return $this->path . $file;
    }
      
    public function getFilepath($file='')
    {
        if (func_num_args()>1){
            $file = implode(DIRECTORY_SEPARATOR,func_get_args());
        }
        return $this->getPathname($file);
    }
    public function getFileObject($file)
    {
    	if(func_num_args()>1){
			$file = implode(DIRECTORY_SEPARATOR,func_get_args());
		}
        $file = ltrim($file,DIRECTORY_SEPARATOR);
        if(strpos($file,DIRECTORY_SEPARATOR)){
            $r = $this->path;
            $x = explode(DIRECTORY_SEPARATOR, $file);
            array_pop($x);
            foreach($x as $f){
                $r .= $f. DIRECTORY_SEPARATOR;
                if(!is_dir($r)) mkdir($r);
            }
        }
        if(!$file){
            $file .= uniqid();
        }
        
        return new file_object($this->path . $file);
    }
    public function getFreeFileObject($ext='ch',$prefix='')
    {
        $ext = trim(ltrim($ext,'.'));   
        
        if($ext){
            $i=0;
            do{
                $tmpfname = tempnam($this->path, $prefix);
                $tmpename = $tmpfname .'.'.$ext;
                @unlink($tmpfname);
                if($i++ > 15) break;
            }while(file_exists($tmpename));
            file_put_contents($tmpename,'');
        }else{
            $tmpename = tempnam($this->path, $prefix);
        }
        return new file_object($tmpename);
    }
    static function getDirectorySize(){
        $dir = $this->path;
        if(!is_dir($dir)) return false;
        
        $totalSize = 0;
        $os        = strtoupper(substr(PHP_OS, 0, 3));
        // If on a Unix Host (Linux, Mac OS)
        if ($os !== 'WIN') {
            $io = popen('/usr/bin/du -sb ' . $dir, 'r');
            if ($io !== false) {
                $totalSize = intval(fgets($io, 80));
                pclose($io);
                return $totalSize;
            }
        }
        // If on a Windows Host (WIN32, WINNT, Windows)
        if ($os === 'WIN' && extension_loaded('com_dotnet')) {
            $obj = new \COM('scripting.filesystemobject');
            if (is_object($obj)) {
                $ref       = $obj->getfolder($dir);
                $totalSize = $ref->size;
                $obj       = null;
                return $totalSize;
            }
        }
        // If System calls did't work, use slower PHP 5
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));
        foreach ($files as $file) {
            $totalSize += $file->getSize();
        }
        return $totalSize;
    }
}

class existingDirectory extends directory
{
    public function __construct($path='') {
        $path = str_replace("\\",DIRECTORY_SEPARATOR,$path);
        $path = get_absolute_path($path);
        $path = trim($path,DIRECTORY_SEPARATOR);
        if($path){
            $r =DIRECTORY_SEPARATOR;
            $x = explode(DIRECTORY_SEPARATOR, $path);
            foreach($x as $f){
                $r .= $f. DIRECTORY_SEPARATOR;
                if(!is_dir($r)) mkdir($r);
            }
            $path =$r;
        }
        $this->path = $path;
    }
}
class file_object
{
    protected $path =null;
    public function __construct($path='') {
        if($path){
            $path = rtrim($path,DIRECTORY_SEPARATOR);
            $this->path = $path ;
        }
    }
    public function __get($name) {
        $name =strtolower($name);
        if(method_exists($this,$name)){
            return $this->$name();
        }
        return null;
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        if($name =='get_contents'){
            return $this->getContents();
        }
        /*if($name =='include' || $name=='include_once'){
            if(!file_exists($this->path)) return '';
            ob_start();
            $r = $name($this->path);
            $d = ob_get_clean();
             
            if($r && ($r !== true)&& ($r !== 1)){
                return $d . $r;
            }
            return $d;
        }*/
    }
    public function path() {
        return $this->path;
    }
    public function basename() {
        return $this->getBasename();
    }
    public function getBasename() {
        if(!$this->path) return '';
        return @basename($this->path );
    }
    public function extension(){
        if (!$this->path) return null;
        $x = explode(".", $this->path);
        return end($x); 
    }
    public function exists() {
        if(!$this->path) return false;
        return @file_exists($this->path );
    }
    public function is_dir() {
        if(!$this->path) return false;
        return @is_dir($this->path );
    }
    public function size() {
        if(!$this->path) return false;
        if(! @file_exists($this->path)) return false;
        return @filesize($this->path );
    }
    public function mtime() {
        if(!$this->path) return false;
        if(! @file_exists($this->path)) return false;
        return @filemtime($this->path );
    }
    public function ctime() {
        if(!$this->path) return false;
        if(! @file_exists($this->path)) return false;
        return @filectime($this->path );
    }
    protected $data = null;
    public function free() {
        $this->data = null;
    }
    public function getContents(){
        if(!$this->path) return null;
         if(@file_exists($this->path)){
            if($this->data === null){
                $this->data = @file_get_contents($this->path);
            }
            return $this->data;
         }
         return null;
    }
    protected $mime=null;
    public function mime(){
        if(!$this->path) return '';
        if($this->mime ===null){
            $this->mime = FileSystem::getExtensionMimeType($this->path);
        }
        return $this->mime;
    }
    public function getContentMimeType(){
        if(!$this->path) return '';
        $mime = '';
        if(@file_exists($this->path)){
            $mime = getContentMimeType($this->path);
            if(!$mime){
                $mime = getMimeTypeCommon($this->path);
            }
            if(!$mime){
                $mime = getMimeTypeExtended($this->path);
            }
        }
        return $mime;
    }
    public function getMediaType(){
        return FileSystem::getMediaType($this->mime());
    }
    
    public function getETagMaker(){
        return new FileETag($this->path);
    }
}

function getContentMimeType($filename){
    $mime = '';
    if (@is_dir($filename)) {
        $mime = 'directory';
    }elseif(@file_exists($filename)){
        if(function_exists('mime_content_type'))
        {
            $mime = mime_content_type($this->path);
        }elseif(function_exists('finfo_open'))
        {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $this->path);
            finfo_close($finfo);
        }
    }
    return $mime;
}
function getMimeTypeCommon($filename){
    //by extension
    static $mime_types = array(
        'txt' => 'text/plain',
        'csv' => 'text/csv',
        'htm' => 'text/html',
        'html' => 'text/html',
        'css' => 'text/css',
        'ics' => 'text/calendar',
        
        'js' => 'application/javascript',
        'json' => 'application/json',
        'xml' => 'application/xml',
        'swf' => 'application/x-shockwave-flash',
        'epub' => 'application/epub+zip',

        // images
        'png' => 'image/png',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'gif' => 'image/gif',
        'bmp' => 'image/bmp',
        'ico' => 'image/vnd.microsoft.icon',
        'tiff' => 'image/tiff',
        'tif' => 'image/tiff',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',

        // audio/video
        'mp3' => 'audio/mpeg',
        'wav' => 'audio/wav',
        'mp4' => 'video/mp4',
        'qt' => 'video/quicktime',
        'mov' => 'video/quicktime',
        'flv' => 'video/x-flv',
        'avi' =>'video/x-msvideo',
        'wmv' =>'video/x-ms-wmv',
        
        // archives
        'zip' => 'application/zip',
        'tar' => 'application/x-tar',
        'rar' => 'application/x-rar-compressed',
        'cab' => 'application/vnd.ms-cab-compressed',
        'exe' => 'application/x-msdownload',
        'msi' => 'application/x-msdownload',

        // adobe
        'pdf' => 'application/pdf',
        'psd' => 'image/vnd.adobe.photoshop',
        'ps' => 'application/postscript',
        
        // ms office
        'dot' => 'application/msword',
        'doc' => 'application/msword',
        'docx' => 'application/msword',
        'rtf' => 'application/rtf',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.ms-excel',
        'xlt' => 'application/vnd.ms-excel',
        'pps' => 'application/vnd.ms-powerpoint',
        'ppt' => 'application/vnd.ms-powerpoint',
        'ppsx' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.ms-powerpoint',
        'pub' =>'application/x-mspublisher',

        // open office
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        
        //other
        'woff' => 'application/font-woff',
        'ttf' => 'application/font-sfnt', //"application/x-font-truetype"
        'otf' => 'application/font-sfnt',
        'eot' => 'application/vnd.ms-fontobject',
        'torrent' => 'application/x-bittorrent'
            
    );
    $x =explode('.',$filename);
    $ext = strtolower(array_pop($x));
    
    if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
    }elseif(@is_dir($filename)){
        return 'directory';
    }
    return '';
}
function getMimeTypeExtended($filename){
    $mime_types = array(	
        'ai' =>'application/postscript',
	'aif' =>'audio/x-aiff',
	'aifc' =>'audio/x-aiff',
	'aiff' =>'audio/x-aiff',
	'asc' =>'text/plain',
	'atom' =>'application/atom+xml',
	'avi' =>'video/x-msvideo',
	'bcpio' =>'application/x-bcpio',
	'bmp' =>'image/bmp',
	'cdf' =>'application/x-netcdf',
	'cgm' =>'image/cgm',
	'cpio' =>'application/x-cpio',
	'cpt' =>'application/mac-compactpro',
	'crl' =>'application/x-pkcs7-crl',
	'crt' =>'application/x-x509-ca-cert',
	'csh' =>'application/x-csh',
	'css' =>'text/css',
	'dcr' =>'application/x-director',
	'dir' =>'application/x-director',
	'djv' =>'image/vnd.djvu',
	'djvu' =>'image/vnd.djvu',
	'doc' =>'application/msword',
	'dtd' =>'application/xml-dtd',
	'dvi' =>'application/x-dvi',
	'dxr' =>'application/x-director',
	'eps' =>'application/postscript',
	'etx' =>'text/x-setext',
	'ez' =>'application/andrew-inset',
	'gif' =>'image/gif',
	'gram' =>'application/srgs',
	'grxml' =>'application/srgs+xml',
	'gtar' =>'application/x-gtar',
	'hdf' =>'application/x-hdf',
	'hqx' =>'application/mac-binhex40',
	'html' =>'text/html',
	'html' =>'text/html',
	'ice' =>'x-conference/x-cooltalk',
	'ico' =>'image/x-icon',
	'ics' =>'text/calendar',
	'ief' =>'image/ief',
	'ifb' =>'text/calendar',
	'iges' =>'model/iges',
	'igs' =>'model/iges',
	'jpe' =>'image/jpeg',
	'jpeg' =>'image/jpeg',
	'jpg' =>'image/jpeg',
	'js' =>'application/x-javascript',
	'kar' =>'audio/midi',
	'latex' =>'application/x-latex',
	'm3u' =>'audio/x-mpegurl',
	'man' =>'application/x-troff-man',
	'mathml' =>'application/mathml+xml',
	'me' =>'application/x-troff-me',
	'mesh' =>'model/mesh',
	'mid' =>'audio/midi',
	'midi' =>'audio/midi',
	'mif' =>'application/vnd.mif',
	'mov' =>'video/quicktime',
	'movie' =>'video/x-sgi-movie',
	'mp2' =>'audio/mpeg',
	'mp3' =>'audio/mpeg',
	'mpe' =>'video/mpeg',
	'mpeg' =>'video/mpeg',
	'mpg' =>'video/mpeg',
	'mpga' =>'audio/mpeg',
	'ms' =>'application/x-troff-ms',
	'msh' =>'model/mesh',
	'mxu' =>'video/vnd.mpegurl',
    'm4u' =>'video/vnd.mpegurl',
	'nc' =>'application/x-netcdf',
	'oda' =>'application/oda',
    
        'odc' => 'application/vnd.oasis.opendocument.chart',
        'odb' => 'application/vnd.oasis.opendocument.database',
        'odi' => 'application/vnd.oasis.opendocument.image',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'ott' => 'application/vnd.oasis.opendocument.text-template',
        'oth' => 'application/vnd.oasis.opendocument.text-web',
	'ogg' =>'application/ogg',
	'pbm' =>'image/x-portable-bitmap',
	'pdb' =>'chemical/x-pdb',
	'pdf' =>'application/pdf',
	'pgm' =>'image/x-portable-graymap',
	'pgn' =>'application/x-chess-pgn',
	'php' =>'application/x-httpd-php',
    'php5' =>'application/x-httpd-php',
	'php4' =>'application/x-httpd-php',
	'php3' =>'application/x-httpd-php',
	'phtml' =>'application/x-httpd-php',
	'phps' =>'application/x-httpd-php-source',
	'png' =>'image/png',
	'pnm' =>'image/x-portable-anymap',
	'ppm' =>'image/x-portable-pixmap',
	'pps' =>'application/vnd.ms-powerpoint',
    'ppsx' =>'application/vnd.ms-powerpoint',
	'ppt' =>'application/vnd.ms-powerpoint',
    'pptx' =>'application/vnd.ms-powerpoint',
	'ps' =>'application/postscript',
    'pub' =>'application/x-mspublisher',
	'qt' =>'video/quicktime',
	'ra' =>'audio/x-pn-realaudio',
	'ram' =>'audio/x-pn-realaudio',
	'ras' =>'image/x-cmu-raster',
	'rdf' =>'application/rdf+xml',
	'rgb' =>'image/x-rgb',
	'rm' =>'application/vnd.rn-realmedia',
	'roff' =>'application/x-troff',
	'rtf' =>'text/rtf',
	'rtx' =>'text/richtext',
	'sgm' =>'text/sgml',
	'sgml' =>'text/sgml',
	'sh' =>'application/x-sh',
	'shar' =>'application/x-shar',
	'shtml' =>'text/html',
	'silo' =>'model/mesh',
	'sit' =>'application/x-stuffit',
	'skd' =>'application/x-koan',
	'skm' =>'application/x-koan',
	'skp' =>'application/x-koan',
	'skt' =>'application/x-koan',
	'smi' =>'application/smil',
	'smil' =>'application/smil',
	'snd' =>'audio/basic',
	'spl' =>'application/x-futuresplash',
	'src' =>'application/x-wais-source',
	'sv4cpio' =>'application/x-sv4cpio',
	'sv4crc' =>'application/x-sv4crc',
	'svg' =>'image/svg+xml',
	'swf' =>'application/x-shockwave-flash',
	't' =>'application/x-troff',
	'tar' =>'application/x-tar',
	'tcl' =>'application/x-tcl',
	'tex' =>'application/x-tex',
	'texi' =>'application/x-texinfo',
	'texinfo' =>'application/x-texinfo',
	'tgz' =>'application/x-tar',
	'tif' =>'image/tiff',
	'tiff' =>'image/tiff',
	'tr' =>'application/x-troff',
	'tsv' =>'text/tab-separated-values',
	'txt' =>'text/plain',
	'ustar' =>'application/x-ustar',
	'vcd' =>'application/x-cdlink',
	'vrml' =>'model/vrml',
	'vxml' =>'application/voicexml+xml',
	'wav' =>'audio/x-wav',
	'wbmp' =>'image/vnd.wap.wbmp',
	'wbxml' =>'application/vnd.wap.wbxml',
	'wml' =>'text/vnd.wap.wml',
	'wmlc' =>'application/vnd.wap.wmlc',
	'wmlc' =>'application/vnd.wap.wmlc',
	'wmls' =>'text/vnd.wap.wmlscript',
	'wmlsc' =>'application/vnd.wap.wmlscriptc',
	'wmlsc' =>'application/vnd.wap.wmlscriptc',
	'wmv' =>'video/x-ms-wmv',
    'woff' => 'application/font-woff',
    'woff2' => 'application/font-woff',
    'wrl' =>'model/vrml',
	'xbm' =>'image/x-xbitmap',
	'xht' =>'application/xhtml+xml',
	'xhtml' =>'application/xhtml+xml',
	'xls' =>'application/vnd.ms-excel',
    'xlsx' =>'application/vnd.ms-excel',
    'xlt' =>'application/vnd.ms-excel',
    'xltx' =>'application/vnd.ms-excel',
	'xml' =>'application/xml',
    'xsl' =>'application/xml',
	'xpm' =>'image/x-xpixmap',
	'xslt' =>'application/xslt+xml',
	'xul' =>'application/vnd.mozilla.xul+xml',
	'xwd' =>'image/x-xwindowdump',
	'xyz' =>'chemical/x-xyz',
	'zip' =>'application/zip',
    
    'sql' =>'application/sql',
	);
    
    $x =explode('.',$filename);
    $ext = strtolower(array_pop($x));
    
    if (array_key_exists($ext, $mime_types)) {
        return $mime_types[$ext];
    }elseif(@is_dir($filename)){
        return 'directory';
    }
    return '';
    
}

function get_absolute_path($path) {
    $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
    $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
    $absolutes = array(DIRECTORY_SEPARATOR);
    foreach ($parts as $part) {
        if ('.' == $part) continue;
        if ('..' == $part) {
            array_pop($absolutes);
        } else {
            $absolutes[] = $part;
        }
    }
    return implode(DIRECTORY_SEPARATOR, $absolutes);
}

class file_writer extends file_object{
    
    public function touch() {
        return @touch($this->path );
    }
    public function unlink() {
        if(! @file_exists($this->path)) return false;
        return @unlink($this->path );
    }
    public function delete() {
        if(! @file_exists($this->path)) return false;
        return @unlink($this->path );
    }
    public function append($data)
    {
        if(!$this->path) return false;
        if($this->data === null){
            $this->data ='';
        }
        $this->data .= $data;
        return @file_put_contents($this->path,$data, FILE_APPEND | LOCK_EX);
    }
    public function putContents($data)
    {
        $this->data = $data;
        return @file_put_contents($this->path,$data);
    }
    public function write($data)
    {
        $this->data = $data;
        return @file_put_contents($this->path,$data);
    }
}
class mediatype{
    private $mime = '';
    private $data = array();
    public function __construct($mime='') {
        $this->mime = $mime;
        $this->data['mime'] = strtolower($mime);
        if($mime){
            $x = explode(';',$mime);
            $this->data['mime'] = strtolower(trim($x[0]));
            if(isset($x[1]))
                $this->data['parameters'] = trim($x[1]);
            
            $x = explode('/',$this->data['mime']);
            $this->data['type'] = $x[0];
            if(isset($x[1]))
                $this->data['subtype'] = trim($x[1]);
            
            if(isset($this->data['subtype'])){
                $x = explode('+',$this->data['subtype']);
                $this->data['subtype'] = $x[0];
                if(isset($x[1]))
                    $this->data['suffix'] = trim($x[1]);
            }
            
        }
    }
    public function __get($name) {
        $name = strtolower($name);
        if(isset($this->data[$name]))
            return $this->data[$name];
        if(method_exists($this,$name))
            return $this->$name();
        return '';
    }
    public function __call($name, $arguments) {
        $name =strtolower($name);
        if(isset($this->data[$name])) return $this->data[$name];
        return '';
    }
    
    public function __toString(){
        return $this->mime;
    }
    public function toString(){
        return $this->mime;
    }
    public function type(){
        if(isset($this->data['type'])) return $this->data['type'];
        return '';
    }
    public function subtype(){
        if(isset($this->data['subtype'])) return $this->data['subtype'];
        return '';
    }
    public function suffix(){
        if(isset($this->data['suffix'])) return $this->data['suffix'];
        return '';
    }
    public function parameters(){
        if(isset($this->data['parameters'])) return $this->data['parameters'];
        return '';
    }
    public function tree(){
        if(strpos($this->subtype(),'.')){
            $x = explode('.',$this->subtype());
            return $x[0];
        }
        return '';
    }
    public function isRegistered(){
        return in_array($ths->type(), array('application', 'audio', 'example', 'image', 'message', 'model', 'multipart', 'text', 'video'));
    }
    public function isVendor(){
        return strtolower($this->tree()) =='vnd';
    }
    public function description(){
        if($this->mime=='') return '';
        
        switch($this->mime){
        case 'directory': return 'Directory';
        case 'text/plain': return 'Plain text file';
        case 'text/html': return 'Web page';
        case 'video/mpeg': return 'MPEG video';
        case 'audio/mpeg': return 'MPEG audio';
        case 'multipart/mixed': return 'Mixed message';
        case 'multipart/form-data': return 'Form data';
        }
        
        switch($this->subtype()){
        case 'pdf': return 'PDF document';
        case 'xml': return 'XML document';
        case 'htm': 
        case 'html': return 'Web page';
        case 'json': return 'Javascript object notation';
        case 'css': return 'Stylesheet';
        case 'zip': return 'ZIP file';
        case 'rtf': return 'Rich text file';
        case 'javascript': return 'Javascript file';
        case 'gif': return 'GIF image';
        case 'jpeg': return 'JPEG image';
        case 'png': return 'PNG image';
        case 'svg+xml': return 'SVG image (vector image)';
        case 'csv': return 'Comma-separated values';
        case 'calendar': return 'iCalendar format';
        case 'java-archive': return 'Java archive';
        case 'mp4': return 'MPEG video';
        case 'sql': return 'SQL file';
        case 'rfc822': return 'Mail message file';
        case 'x-rar-compressed': return 'RAR compressed archive';
        case 'x-7z-compressed': return '7zip compressed archive';
        case 'x-tar': return 'Tape archive';
        }
        
        switch($this->type){
        case 'image': return 'Image';
        case 'text': return 'Text file';
        case 'audio': return 'Audio file';
        case 'video': return 'Video file';
        case 'font': return 'Font file';
        case 'multipart': return 'Multipart file';
        }
        
        $x = $this->subtype();
        if(strpos($x,'font')!==false) return 'Font file';
        if(strpos($x,'msword')!== false) return 'Word document';
        if(strpos($x,'wordprocessing')!== false) return 'Word-processing document';
        if(strpos($x,'powerpoint')) return 'PowerPoint file';
        if(strpos($x,'presentation')) return 'Presentation file';
        if(strpos($x,'ms-excel')) return 'Excel document';
        if(strpos($x,'spreadsheet')) return 'Spreadsheet';
        if(strpos($x,'mspublisher')) return 'MS Publisher document';
        if(strpos($x,'officedocument')) return 'Office document';
        if(strpos($x,'bittorrent')) return 'BitTorrent';
        

        if($this->type ==' application') return 'Application file';
        return $this->mime;
    }
    
}
class FileETag{
    private $e = array();
    public function __get($name) {
        if(method_exists($this,$name))
            return $this->$name();
        return '';
    }
    public function __construct($file='') {
        if($file){
            $this->reset($file);
        }
    }
    public function reset($file='') {
        $this->e=array();
        if($file && @file_exists($file)){
            if(!@is_dir($file)){
                $sz = @filesize($file);
                if($sz > 3000000 ){
                    $this->e[] = $sz . md5($file);
                }else{
                    $this->e[] = md5_file($file);
                }
            }
        }
    }
    public function etag() {
        if(count($this->e) == 0) return '';
        return md5(implode('|',$this->e) );        
    }
    public function addEntity($value) {
        $this->e[] = (string)$value;
        return $this;
    }
}
