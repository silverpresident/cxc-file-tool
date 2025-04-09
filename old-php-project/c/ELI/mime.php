<?php
/**
 * @author Edwards
 * @copyright  2011
 */
class ELI_MIME
{
    static function getMimeType($filename){
        if($mime = self::getCommonMimeType($filename))
            return $mime;
        if($mime = self::getExtendedMimeType($filename))
            return $mime;
        
        $mime = self::getMimeShell($filename);
        if($mime) return $mime;
        return  'application/octet-stream';;
    }
    static function getMimeShell($filename){
        return trim ( exec ('file -bi ' . escapeshellarg ( $filename ) ) ) ;
    }
    static function getMimeLive($filename){
        $mime_types = self::generateUpToDateMimeArray();
        if (array_key_exists($ext, $mime_types)) 
            return $mime_types[$ext];
        
        
        return  'application/octet-stream';;
    }
    static function generateUpToDateMimeArray($url=''){
        static $mime_types = null;
        if(is_array($mime_types)) return $mime_types;
        if(empty($url)) $url = 'http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types';
        $mime_types=array();
        foreach(@explode("\n",@file_get_contents($url))as $x){
            if(isset($x[0])&&$x[0]!=='#'&&preg_match_all('#([^\s]+)#',$x,$out)&&isset($out[1])&&($c=count($out[1]))>1)
                for($i=1;$i<$c;$i++){
                    $mime_types[strtolower(trim($out[1][$i]))]= $out[1][0];
                }
        }
        return $mime_types;
    }
    static function getCommonMimeType($filename){
        //by extension
        static $mime_types = array(
            'txt' => 'text/plain',
            'csv' => 'text/csv',
            'htm' => 'text/html',
            'html' => 'text/html',
            'css' => 'text/css',
            
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
            'eot' => 'application/vnd.ms-fontobject'
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
    static function getExtendedMimeType($filename){
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
    	'zip' =>'application/zip'
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
} 