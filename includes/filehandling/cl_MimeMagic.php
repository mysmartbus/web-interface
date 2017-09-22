<?php
/**
 * Helper functions for detecting and dealing with mime types.
 *
 * This file comes from MediaWiki 1.22.1 with some modifications for use with Kraven
 *
 * Things modified:
 *   Removed use of $ext in the functions in which it was marked for removal
 *   Converted wfDebug() lines to PHP code comments or removed them
 *   Deleted the following functions
 *      public function getIEMimeTypes()::Line 992
 *      protected function getIEContentAnalyzer()::Line 1002
 *
 * Last modified for Kraven on 2014-11-11
**/

/**
 * Defines a set of well known mime types
 * This is used as a fallback to mime.types files.
 * An extensive list of well known mime types is provided by
 * the file "mime.types" in the includes directory.
 *
 * This list concatenated with mime.types is used to create a mime <-> ext
 * map. Each line contains a mime type followed by a space separated list of
 * extensions. If multiple extensions for a single mime type exist or if
 * multiple mime types exist for a single extension then in most cases
 * Krave assumes that the first extension following the mime type is the
 * canonical extension, and the first time a mime type appears for a certain
 * extension is considered the canonical mime type.
 *
 * (Note that appending $mimetypesfile to the end of MM_WELL_KNOWN_MIME_TYPES
 * sucks because you can't redefine canonical types. This could be fixed by
 * appending MM_WELL_KNOWN_MIME_TYPES behind $mimetypesfile, but who knows
 * what will break? In practice this probably isn't a problem anyway -- Bryan@MediaWiki)
**/
define( 'MM_WELL_KNOWN_MIME_TYPES', <<<END_STRING
application/ogg ogx ogg ogm ogv oga spx
application/pdf pdf
application/vnd.oasis.opendocument.chart odc
application/vnd.oasis.opendocument.chart-template otc
application/vnd.oasis.opendocument.database odb
application/vnd.oasis.opendocument.formula odf
application/vnd.oasis.opendocument.formula-template otf
application/vnd.oasis.opendocument.graphics odg
application/vnd.oasis.opendocument.graphics-template otg
application/vnd.oasis.opendocument.image odi
application/vnd.oasis.opendocument.image-template oti
application/vnd.oasis.opendocument.presentation odp
application/vnd.oasis.opendocument.presentation-template otp
application/vnd.oasis.opendocument.spreadsheet ods
application/vnd.oasis.opendocument.spreadsheet-template ots
application/vnd.oasis.opendocument.text odt
application/vnd.oasis.opendocument.text-master otm
application/vnd.oasis.opendocument.text-template ott
application/vnd.oasis.opendocument.text-web oth
application/x-javascript js
application/x-shockwave-flash swf
audio/midi mid midi kar
audio/mpeg mpga mpa mp2 mp3
audio/x-aiff aif aiff aifc
audio/x-wav wav
audio/ogg oga spx ogg
image/x-bmp bmp
image/gif gif
image/jpeg jpeg jpg jpe
image/png png
image/svg+xml svg
image/svg svg
image/tiff tiff tif
image/vnd.djvu djvu
image/x.djvu djvu
image/x-djvu djvu
image/x-portable-pixmap ppm
image/x-xcf xcf
text/plain txt
text/html html htm
video/ogg ogv ogm ogg
video/mpeg mpg mpeg
END_STRING
);

/**
 * Defines a set of well known mime info entries
 * This is used as a fallback to mime.info files.
 * An extensive list of well known mime types is provided by
 * the file "mime.info" in the includes directory.
 */
define( 'MM_WELL_KNOWN_MIME_INFO', <<<END_STRING
application/pdf [OFFICE]
application/vnd.oasis.opendocument.chart [OFFICE]
application/vnd.oasis.opendocument.chart-template [OFFICE]
application/vnd.oasis.opendocument.database [OFFICE]
application/vnd.oasis.opendocument.formula [OFFICE]
application/vnd.oasis.opendocument.formula-template [OFFICE]
application/vnd.oasis.opendocument.graphics [OFFICE]
application/vnd.oasis.opendocument.graphics-template [OFFICE]
application/vnd.oasis.opendocument.image [OFFICE]
application/vnd.oasis.opendocument.image-template [OFFICE]
application/vnd.oasis.opendocument.presentation [OFFICE]
application/vnd.oasis.opendocument.presentation-template [OFFICE]
application/vnd.oasis.opendocument.spreadsheet [OFFICE]
application/vnd.oasis.opendocument.spreadsheet-template [OFFICE]
application/vnd.oasis.opendocument.text [OFFICE]
application/vnd.oasis.opendocument.text-template [OFFICE]
application/vnd.oasis.opendocument.text-master [OFFICE]
application/vnd.oasis.opendocument.text-web [OFFICE]
text/javascript application/x-javascript [EXECUTABLE]
application/x-shockwave-flash [MULTIMEDIA]
audio/midi [AUDIO]
audio/x-aiff [AUDIO]
audio/x-wav [AUDIO]
audio/mp3 audio/mpeg [AUDIO]
application/ogg audio/ogg video/ogg [MULTIMEDIA]
image/x-bmp image/x-ms-bmp image/bmp [BITMAP]
image/gif [BITMAP]
image/jpeg [BITMAP]
image/png [BITMAP]
image/svg+xml [DRAWING]
image/tiff [BITMAP]
image/vnd.djvu [BITMAP]
image/x-xcf [BITMAP]
image/x-portable-pixmap [BITMAP]
text/plain [TEXT]
text/html [TEXT]
video/ogg [VIDEO]
video/mpeg [VIDEO]
unknown/unknown application/octet-stream application/x-empty [UNKNOWN]
END_STRING
);

/**
 * Implements functions related to mime types such as detection and mapping to
 * file extension.
 *
 * Instances of this class are stateless, there only needs to be one global instance
 * of MimeMagic. Please use MimeMagic::singleton() to get that instance.
 */
class MimeMagic {

    /**
     * Mapping of media types to arrays of mime types.
     * This is used by findMediaType and getMediaType, respectively
     */
    var $mMediaTypes = null;

    /** Map of mime type aliases
     */
    var $mMimeTypeAliases = null;

    /** map of mime types to file extensions (as a space separated list)
     */
    var $mMimeToExt = null;

    /** map of file extensions types to mime types (as a space separated list)
     */
    var $mExtToMime = null;

    /** IEContentAnalyzer instance
     */
    var $mIEAnalyzer;

    /** The singleton instance
     */
    private static $instance;

    /** Initializes the MimeMagic object. This is called by MimeMagic::singleton().
     *
     * This constructor parses the mime.types and mime.info files and build internal mappings.
     */
    function __construct() {
        /**
         *   --- load mime.types ---
         */

        global $IP;

        $types = MM_WELL_KNOWN_MIME_TYPES;

        $mimetypesfile = $IP.'/includes/filehandling/mime.types';

        if (is_file($mimetypesfile) && is_readable($mimetypesfile)) {
            $types .= "\n";
            $types .= file_get_contents( $mimetypesfile );
        }

        // These replacements make it easier to work with $types later on
        $types = str_replace( array( "\r\n", "\n\r", "\n\n", "\r\r", "\r" ), "\n", $types );
        $types = str_replace( "\t", " ", $types );

        $this->mMimeToExt = array();
        $this->mToMime = array();

        // Fills two arrays, $this->mMimeToExt[] and $this->mExtToMime[] with the info in $types
        $lines = explode( "\n", $types );
        foreach ( $lines as $s ) {
            $s = trim( $s );
            if ( empty( $s ) ) {
                continue;
            }
            if ( strpos( $s, '#' ) === 0 ) {
                continue;
            }

            $s = strtolower( $s );
            $i = strpos( $s, ' ' );

            if ( $i === false ) {
                continue;
            }

            $mime = substr( $s, 0, $i );
            $ext = trim( substr( $s, $i + 1 ) );

            if ( empty( $ext ) ) {
                continue;
            }

            if ( !empty( $this->mMimeToExt[$mime] ) ) {
                $this->mMimeToExt[$mime] .= ' ' . $ext;
            } else {
                $this->mMimeToExt[$mime] = $ext;
            }

            $extensions = explode( ' ', $ext );

            foreach ( $extensions as $e ) {
                $e = trim( $e );
                if ( empty( $e ) ) {
                    continue;
                }

                if ( !empty( $this->mExtToMime[$e] ) ) {
                    $this->mExtToMime[$e] .= ' ' . $mime;
                } else {
                    $this->mExtToMime[$e] = $mime;
                }
            }
        }

        /**
         *   --- load mime.info ---
         */

        $info = MM_WELL_KNOWN_MIME_INFO;

        $mimeinfofile = $IP.'/includes/filehandling/mime.info';

        if (is_file($mimeinfofile) && is_readable($mimeinfofile)) {
            $info .= "\n";
            $info .= file_get_contents($mimeinfofile);
        }

        $info = str_replace( array( "\r\n", "\n\r", "\n\n", "\r\r", "\r" ), "\n", $info );
        $info = str_replace( "\t", " ", $info );

        $this->mMimeTypeAliases = array();
        $this->mMediaTypes = array();

        $lines = explode( "\n", $info );
        foreach ( $lines as $s ) {
            $s = trim( $s );
            if ( empty( $s ) ) {
                continue;
            }
            if ( strpos( $s, '#' ) === 0 ) {
                continue;
            }

            $s = strtolower( $s );
            $i = strpos( $s, ' ' );

            if ( $i === false ) {
                continue;
            }

            $match = array();
            if ( preg_match( '!\[\s*(\w+)\s*\]!', $s, $match ) ) {
                $s = preg_replace( '!\[\s*(\w+)\s*\]!', '', $s );
                $mtype = trim( strtoupper( $match[1] ) );
            } else {
                $mtype = MEDIATYPE_UNKNOWN;
            }

            $m = explode( ' ', $s );

            if ( !isset( $this->mMediaTypes[$mtype] ) ) {
                $this->mMediaTypes[$mtype] = array();
            }

            foreach ( $m as $mime ) {
                $mime = trim( $mime );
                if ( empty( $mime ) ) {
                    continue;
                }

                $this->mMediaTypes[$mtype][] = $mime;
            }

            if ( count($m) > 1 ) {
                $main = $m[0];
                for ( $i = 1; $i < count($m); $i += 1 ) {
                    $mime = $m[$i];
                    $this->mMimeTypeAliases[$mime] = $main;
                }
            }
        }

    }

    /**
     * Get an instance of this class
     * @return MimeMagic
     */
    public static function &singleton() {
        if ( self::$instance === null ) {
            self::$instance = new MimeMagic;
        }
        return self::$instance;
    }

    /**
     * Returns a list of file extensions for a given mime type as a space
     * separated string or null if the mime type was unrecognized. Resolves
     * mime type aliases.
     *
     * @param $mime string
     * @return string|null
     */
    public function getExtensionsForType( $mime ) {
        $mime = strtolower( $mime );

        // Check the mime-to-ext map
        if ( isset( $this->mMimeToExt[$mime] ) ) {
            return $this->mMimeToExt[$mime];
        }

        // Resolve the mime type to the canonical type
        if ( isset( $this->mMimeTypeAliases[$mime] ) ) {
            $mime = $this->mMimeTypeAliases[$mime];
            if ( isset( $this->mMimeToExt[$mime] ) ) {
                return $this->mMimeToExt[$mime];
            }
        }

        return null;
    }

    /**
     * Returns a list of mime types for a given file extension as a space
     * separated string or null if the extension was unrecognized.
     *
     * @param Required string $ext Do not include leading dot (.)
     * @return string|null
     */
    public function getTypesForExtension( $ext ) {
        $ext = strtolower( $ext );

        $r = isset( $this->mExtToMime[$ext] ) ? $this->mExtToMime[$ext] : null;
        return $r;
    }

    /**
     * Returns a single mime type for a given file extension or null if unknown.
     * This is always the first type from the list returned by getTypesForExtension($ext).
     *
     * @param $ext string
     * @return string|null
     */
    public function guessTypesForExtension( $ext ) {
        $m = $this->getTypesForExtension( $ext );
        if ( is_null($m) ) {
            return null;
        }

        // TODO: Check if this is needed; strtok( $m, ' ' ) should be sufficient
        $m = trim($m);
        $m = preg_replace( '/\s.*$/', '', $m );

        return $m;
    }

    /**
     * Tests if the extension matches the given mime type. Returns true if a
     * match was found, null if the mime type is unknown, and false if the
     * mime type is known but no matches where found.
     *
     * @param $extension string
     * @param $mime string
     * @return bool|null
     */
    public function isMatchingExtension( $extension, $mime ) {
        $ext = $this->getExtensionsForType( $mime );

        if ( !$ext ) {
            return null; // Unknown mime type
        }

        $ext = explode( ' ', $ext );

        $extension = strtolower( $extension );
        return in_array( $extension, $ext );
    }

    /**
     * Returns true if the mime type is known to represent an image format
     * supported by the PHP GD library.
     *
     * @param $mime string
     *
     * @return bool
     */
    public function isPHPImageType( $mime ) {
        // As defined by imagegetsize and image_type_to_mime
        static $types = array(
            'image/gif',
            'image/jpeg',
            'image/png',
            'image/x-bmp',
            'image/xbm',
            'image/tiff',
            'image/jp2',            
            'image/jpeg2000',
            'image/iff',
            'image/xbm',
            'image/x-xbitmap',
            'image/vnd.wap.wbmp',
            'image/vnd.xiff',
            'image/x-photoshop',
            'application/x-shockwave-flash'
        );

        return in_array( $mime, $types );
    }

    /**
     * Returns true if the extension represents a type which can
     * be reliably detected from its content. Use this to determine
     * whether strict content checks should be applied to reject
     * invalid uploads; if we can't identify the type we won't
     * be able to say if it's invalid.
     *
     * @todo Be more accurate when using fancy mime detector plugins;
     *       right now this is the bare minimum getimagesize() list.
     * @return bool
     */
    function isRecognizableExtension( $extension ) {
        static $types = array(
            // Types recognized by getimagesize()
            'gif', 'jpeg', 'jpg', 'png', 'swf', 'psd',
            'bmp', 'tiff', 'tif', 'jpc', 'jp2',
            'jpx', 'jb2', 'swc', 'iff', 'wbmp',
            'xbm',

            // Formats we recognize magic numbers for
            'djvu', 'ogx', 'ogg', 'ogv', 'oga', 'spx',
            'mid', 'pdf', 'wmf', 'xcf', 'webm', 'mkv', 'mka',
            'webp',

            // XML formats we sure hope we recognize reliably
            'svg',
        );
        return in_array( strtolower( $extension ), $types );
    }

    /**
     * Improves a mime type using the file extension. Some file formats are very generic,
     * so their mime type is not very meaningful. A more useful mime type can be derived
     * by looking at the file extension. Typically, this method would be called on the
     * result of guessMimeType().
     *
     * Currently, this method does the following:
     *
     * If $mime is "unknown/unknown" and isRecognizableExtension( $ext ) returns false,
     * return the result of guessTypesForExtension($ext).
     *
     * If $mime is "application/x-opc+zip" and isMatchingExtension( $ext, $mime )
     * gives true, return the result of guessTypesForExtension($ext).
     *
     * @param string $mime the mime type, typically guessed from a file's content.
     * @param string $ext the file extension, as taken from the file name
     *
     * @return string the mime type
     */
    public function improveTypeFromExtension( $mime, $ext ) {
        if ( $mime === 'unknown/unknown' ) {
            if ($this->isRecognizableExtension($ext) === false ) {
                // Not something we can detect, so simply
                // trust the file extension
                $mime = $this->guessTypesForExtension( $ext );
            }
        } elseif ( $mime === 'application/x-opc+zip' ) {
            if ( $this->isMatchingExtension( $ext, $mime ) ) {
                // A known file extension for an OPC file,
                // find the proper mime type for that file extension
                $mime = $this->guessTypesForExtension( $ext );
            } else {
                // Refusing to guess better type for $mime file. $ext is not a known OPC extension
                $mime = 'application/zip';
            }
        }

        if ( isset( $this->mMimeTypeAliases[$mime] ) ) {
            $mime = $this->mMimeTypeAliases[$mime];
        }

        return $mime;
    }

    /**
     * Mime type detection. This uses detectMimeType to detect the mime type
     * of the file, but applies additional checks to determine some well known
     * file formats that may be missed or misinterpreted by the default mime
     * detection (namely XML based formats like XHTML or SVG, as well as ZIP
     * based formats like OPC/ODF files).
     *
     * @param string $file the file to check
     *
     * @return string the mime type of $file
     */
    public function guessMimeType($file) {

        $mime = $this->doGuessMimeType($file);

        if ( !$mime ) {
            // Internal type detection failed for $file...
            $mime = $this->detectMimeType($file);
        }

        if ( isset( $this->mMimeTypeAliases[$mime] ) ) {
            $mime = $this->mMimeTypeAliases[$mime];
        }

        // Guessed mime type of $file as $mime
        return $mime;
    }

    /**
     * Guess the mime type from the file contents.
     *
     * @param string $file
     * @return bool|string
     */
    private function doGuessMimeType($file) {
        // Read a chunk of the file
        // @todo FIXME: Shouldn't this be rb?
        $f = fopen( $file, 'rt' );

        if ( !$f ) {
            return 'unknown/unknown';
        }
        $head = fread( $f, 1024 );
        fseek( $f, -65558, SEEK_END );
        $tail = fread( $f, 65558 ); // 65558 = maximum size of a zip EOCDR
        fclose( $f );

        // Hardcode a few magic number checks...
        $headers = array(
            // Multimedia...
            'MThd'             => 'audio/midi',
            'OggS'             => 'application/ogg',

            // Image formats...
            // Note that WMF may have a bare header, no magic number.
            "\x01\x00\x09\x00" => 'application/x-msmetafile', // Possibly prone to false positives?
            "\xd7\xcd\xc6\x9a" => 'application/x-msmetafile',
            '%PDF'             => 'application/pdf',
            'gimp xcf'         => 'image/x-xcf',

            // Some forbidden fruit...
            'MZ'               => 'application/octet-stream', // DOS/Windows executable
            "\xca\xfe\xba\xbe" => 'application/octet-stream', // Mach-O binary
            "\x7fELF"          => 'application/octet-stream', // ELF binary
        );

        foreach ( $headers as $magic => $candidate ) {
            if ( strncmp( $head, $magic, strlen( $magic ) ) == 0 ) {
                // magic header in $file recognized as $candidate
                return $candidate;
            }
        }

        /* Look for WebM and Matroska files */
        if ( strncmp( $head, pack( "C4", 0x1a, 0x45, 0xdf, 0xa3 ), 4 ) == 0 ) {
            $doctype = strpos( $head, "\x42\x82" );
            if ( $doctype ) {
                // Next byte is datasize, then data (sizes larger than 1 byte are very stupid muxers)
                $data = substr( $head, $doctype + 3, 8 );
                if ( strncmp( $data, "matroska", 8 ) == 0 ) {
                    //Recognized file as video/x-matroska
                    return "video/x-matroska";
                } elseif ( strncmp( $data, "webm", 4 ) == 0 ) {
                    // Recognized file as video/webm
                    return "video/webm";
                }
            }

            // unknown EBML file
            return "unknown/unknown";
        }

        /* Look for WebP */
        if ( strncmp( $head, "RIFF", 4 ) == 0 && strncmp( substr( $head, 8, 8 ), "WEBPVP8 ", 8 ) == 0 ) {
            // Recognized file as image/webp
            return "image/webp";
        }

        /**
         * Look for PHP.  Check for this before HTML/XML...  Warning: this is a
         * heuristic, and won't match a file with a lot of non-PHP before.  It
         * will also match text files which could be PHP. :)
         *
         * @todo FIXME: For this reason, the check is probably useless -- an attacker
         * could almost certainly just pad the file with a lot of nonsense to
         * circumvent the check in any case where it would be a security
         * problem.  On the other hand, it causes harmful false positives (bug
         * 16583).  The heuristic has been cut down to exclude three-character
         * strings like "<? ", but should it be axed completely?
         */
        if ( ( strpos( $head, '<?php' ) !== false ) ||
            ( strpos( $head, "<\x00?\x00p\x00h\x00p" ) !== false ) ||
            ( strpos( $head, "<\x00?\x00 " ) !== false ) ||
            ( strpos( $head, "<\x00?\x00\n" ) !== false ) ||
            ( strpos( $head, "<\x00?\x00\t" ) !== false ) ||
            ( strpos( $head, "<\x00?\x00=" ) !== false ) ) {

            // Recognized $file as application/x-php
            return 'application/x-php';
        }

        /**
         * look for XML formats (XHTML and SVG)
         */
        $xml = new XmlTypeCheck($file);
        if ( $xml->wellFormed ) {
            /**
             * Additional XML types we can allow via mime-detection.
             * array = ( 'rootElement' => 'associatedMimeType' )
            **/
            $XMLMimeTypes = array(
                'http://www.w3.org/2000/svg:svg' => 'image/svg+xml',
                'svg' => 'image/svg+xml',
                'http://www.lysator.liu.se/~alla/dia/:diagram' => 'application/x-dia-diagram',
                'http://www.w3.org/1999/xhtml:html' => 'text/html', // application/xhtml+xml?
                'html' => 'text/html', // application/xhtml+xml?
            );
            if ( isset( $XMLMimeTypes[$xml->getRootElement()] ) ) {
                return $XMLMimeTypes[$xml->getRootElement()];
            } else {
                return 'application/xml';
            }
        }

        /**
         * look for shell scripts
         */
        $script_type = null;

        # detect by shebang
        if ( substr( $head, 0, 2 ) == "#!" ) {
            $script_type = "ASCII";
        } elseif ( substr( $head, 0, 5 ) == "\xef\xbb\xbf#!" ) {
            $script_type = "UTF-8";
        } elseif ( substr( $head, 0, 7 ) == "\xfe\xff\x00#\x00!" ) {
            $script_type = "UTF-16BE";
        } elseif ( substr( $head, 0, 7 ) == "\xff\xfe#\x00!" ) {
            $script_type = "UTF-16LE";
        }

        if ( $script_type ) {
            if ( $script_type !== "UTF-8" && $script_type !== "ASCII" ) {
                // Quick and dirty fold down to ASCII!
                $pack = array( 'UTF-16BE' => 'n*', 'UTF-16LE' => 'v*' );
                $chars = unpack( $pack[$script_type], substr( $head, 2 ) );
                $head = '';
                foreach ( $chars as $codepoint ) {
                    if ( $codepoint < 128 ) {
                        $head .= chr( $codepoint );
                    } else {
                        $head .= '?';
                    }
                }
            }

            $match = array();

            if ( preg_match( '%/?([^\s]+/)(\w+)%', $head, $match ) ) {
                $mime = "application/x-{$match[2]}";
                // Shell script recognized as $mime
                return $mime;
            }
        }

        // Check for ZIP variants (before getimagesize)
        if ( strpos( $tail, "PK\x05\x06" ) !== false ) {
            // ZIP header present in $file
            return $this->detectZipType($head, $tail);
        }

        $gis = getimagesize($file);

        if ( $gis && isset( $gis['mime'] ) ) {
            $mime = $gis['mime'];
            // getimagesize detected $file as $mime
            return $mime;
        }

        // Also test DjVu
        $deja = new DjVuImage($file);
        if ( $deja->isValid() ) {
            // Detected $file as image/vnd.djvu
            return 'image/vnd.djvu';
        }

        return false;
    }

    /**
     * Detect application-specific file type of a given ZIP file from its
     * header data.  Currently works for OpenDocument and OpenXML types...
     * If can't tell, returns 'application/zip'.
     *
     * @param string $header some reasonably-sized chunk of file header
     * @param $tail   String: the tail of the file
     *
     * @return string
     */
    function detectZipType( $header, $tail = null) {

        $mime = 'application/zip';
        $opendocTypes = array(
            'chart-template',
            'chart',
            'formula-template',
            'formula',
            'graphics-template',
            'graphics',
            'image-template',
            'image',
            'presentation-template',
            'presentation',
            'spreadsheet-template',
            'spreadsheet',
            'text-template',
            'text-master',
            'text-web',
            'text' );

        // http://lists.oasis-open.org/archives/office/200505/msg00006.html
        $types = '(?:' . implode( '|', $opendocTypes ) . ')';
        $opendocRegex = "/^mimetype(application\/vnd\.oasis\.opendocument\.$types)/";

        $openxmlRegex = "/^\[Content_Types\].xml/";

        if ( preg_match( $opendocRegex, substr( $header, 30 ), $matches ) ) {
            // Detected $mime from ZIP archive
            $mime = $matches[1];
        } elseif ( preg_match( $openxmlRegex, substr( $header, 30 ) ) ) {
            // Detected an Open Packaging Conventions archive
            $mime = "application/x-opc+zip";
        } elseif ( substr( $header, 0, 8 ) == "\xd0\xcf\x11\xe0\xa1\xb1\x1a\xe1" &&
                ( $headerpos = strpos( $tail, "PK\x03\x04" ) ) !== false &&
                preg_match( $openxmlRegex, substr( $tail, $headerpos + 30 ) ) ) {
            // Detected a MS Office document with OPC trailer
            if ( substr( $header, 512, 4 ) == "\xEC\xA5\xC1\x00" ) {
                $mime = "application/msword";
            }
            switch ( substr( $header, 512, 6 ) ) {
                case "\xEC\xA5\xC1\x00\x0E\x00":
                case "\xEC\xA5\xC1\x00\x1C\x00":
                case "\xEC\xA5\xC1\x00\x43\x00":
                    $mime = "application/vnd.ms-powerpoint";
                    break;
                case "\xFD\xFF\xFF\xFF\x10\x00":
                case "\xFD\xFF\xFF\xFF\x1F\x00":
                case "\xFD\xFF\xFF\xFF\x22\x00":
                case "\xFD\xFF\xFF\xFF\x23\x00":
                case "\xFD\xFF\xFF\xFF\x28\x00":
                case "\xFD\xFF\xFF\xFF\x29\x00":
                case "\xFD\xFF\xFF\xFF\x10\x02":
                case "\xFD\xFF\xFF\xFF\x1F\x02":
                case "\xFD\xFF\xFF\xFF\x22\x02":
                case "\xFD\xFF\xFF\xFF\x23\x02":
                case "\xFD\xFF\xFF\xFF\x28\x02":
                case "\xFD\xFF\xFF\xFF\x29\x02":
                    $mime = "application/vnd.msexcel";
                    break;
            }


        }/* else {
            // Unable to identify type of ZIP archive
        } */
        return $mime;
    }

    /**
     * Internal mime type detection. Detection is done using the fileinfo
     * extension and mime_content_type (in this order), if they are available.
     *
     * If no mime type can be determined, this function returns 'unknown/unknown'.
     *
     * @param string $file the file to check
     * @return string the mime type of $file
     */
    private function detectMimeType($file) {

        $m = null;
        if ( function_exists( "finfo_open" ) && function_exists( "finfo_file" ) ) {

            # This requires the fileinfo extension by PECL,
            # see http://pecl.php.net/package/fileinfo
            # This must be compiled into PHP
            #
            # finfo is the official replacement for the deprecated
            # mime_content_type function, see below.
            #
            # If you may need to load the fileinfo extension at runtime, set
            # $wgLoadFileinfoExtension in LocalSettings.php

            $mime_magic_resource = finfo_open( FILEINFO_MIME ); /* return mime type ala mimetype extension */

            if ( $mime_magic_resource ) {
                $m = finfo_file( $mime_magic_resource, $file );
                finfo_close( $mime_magic_resource );
            }
        } elseif ( function_exists( "mime_content_type" ) ) {

            # NOTE: this function is available since PHP 4.3.0, but only if
            # PHP was compiled with --with-mime-magic or, before 4.3.2, with --enable-mime-magic.
            #
            # On Windows, you must set mime_magic.magicfile in php.ini to point to the mime.magic file bundled with PHP;
            # sometimes, this may even be needed under linus/unix.
            #
            # Also note that this has been DEPRECATED in favor of the fileinfo extension by PECL, see above.
            # see http://www.php.net/manual/en/ref.mime-magic.php for details.

            $m = mime_content_type($file);
        }/* else {
            // No magic mime detector found
        } */

        if ($m) {
            # normalize
            $m = preg_replace( '![;, ].*$!', '', $m ); #strip charset, etc
            $m = trim($m);
            $m = strtolower($m);

            if ( strpos( $m, 'unknown' ) !== false ) {
                $m = null;
            } else {
                // Magic mime type of $file is $m
                return $m;
            }
        }

        // Unknown type
        // Failed to guess mime type for $file
        return 'unknown/unknown';
    }

    /**
     * Determine the media type code for a file, using its mime type, name and
     * possibly its contents.
     *
     * This function relies on the findMediaType(), mapping extensions and mime
     * types to media types.
     *
     * @todo analyse file if need be
     * @todo look at multiple extension, separately and together.
     *
     * @param string $path full path to the image file, in case we have to look at the contents
     *        (if null, only the mime type is used to determine the media type code).
     * @param string $mime mime type. If null it will be guessed using guessMimeType.
     *
     * @return (int?string?) a value to be used with the MEDIATYPE_xxx constants.
     */
    function getMediaType( $path = null, $mime = null ) {
        if ( !$mime && !$path ) {
            return MEDIATYPE_UNKNOWN;
        }

        // If mime type is unknown, guess it
        if ( !$mime ) {
            $mime = $this->guessMimeType( $path, false );
        }

        // Special code for ogg - detect if it's video (theora),
        // else label it as sound.
        if ( $mime == 'application/ogg' && file_exists( $path ) ) {

            // Read a chunk of the file
            $f = fopen( $path, "rt" );
            if ( !$f ) {
                return MEDIATYPE_UNKNOWN;
            }
            $head = fread( $f, 256 );
            fclose( $f );

            $head = strtolower( $head );

            // This is an UGLY HACK, file should be parsed correctly
            if ( strpos( $head, 'theora' ) !== false ) {
                return MEDIATYPE_VIDEO;
            } elseif ( strpos( $head, 'vorbis' ) !== false ) {
                return MEDIATYPE_AUDIO;
            } elseif ( strpos( $head, 'flac' ) !== false ) {
                return MEDIATYPE_AUDIO;
            } elseif ( strpos( $head, 'speex' ) !== false ) {
                return MEDIATYPE_AUDIO;
            } else {
                return MEDIATYPE_MULTIMEDIA;
            }
        }

        // Check for entry for full mime type
        if ( $mime ) {
            $type = $this->findMediaType( $mime );
            if ( $type !== MEDIATYPE_UNKNOWN ) {
                return $type;
            }
        }

        // Check for entry for file extension
        if ( $path ) {
            $i = strrpos( $path, '.' );
            $e = strtolower( $i ? substr( $path, $i + 1 ) : '' );

            // TODO: look at multi-extension if this fails, parse from full path
            $type = $this->findMediaType( '.' . $e );
            if ( $type !== MEDIATYPE_UNKNOWN ) {
                return $type;
            }
        }

        // Check major mime type
        if ( $mime ) {
            $i = strpos( $mime, '/' );
            if ( $i !== false ) {
                $major = substr( $mime, 0, $i );
                $type = $this->findMediaType( $major );
                if ( $type !== MEDIATYPE_UNKNOWN ) {
                    return $type;
                }
            }
        }

        if ( !$type ) {
            $type = MEDIATYPE_UNKNOWN;
        }

        return $type;
    }

    /**
     * Returns a media code matching the given mime type or file extension.
     * File extensions are represented by a string starting with a dot (.) to
     * distinguish them from mime types.
     *
     * This function relies on the mapping defined by $this->mMediaTypes
     * @access private
     * @return int|string
     */
    function findMediaType( $extMime ) {
        if ( strpos( $extMime, '.' ) === 0 ) {
            // If it's an extension, look up the mime types
            $m = $this->getTypesForExtension( substr( $extMime, 1 ) );
            if ( !$m ) {
                return MEDIATYPE_UNKNOWN;
            }

            $m = explode( ' ', $m );
        } else {
            // Normalize mime type
            if ( isset( $this->mMimeTypeAliases[$extMime] ) ) {
                $extMime = $this->mMimeTypeAliases[$extMime];
            }

            $m = array( $extMime );
        }

        foreach ( $m as $mime ) {
            foreach ( $this->mMediaTypes as $type => $codes ) {
                if ( in_array( $mime, $codes, true ) ) {
                    return $type;
                }
            }
        }

        return MEDIATYPE_UNKNOWN;
    }
}
