<?php
/**
 * Extraction and validation of image metadata.
 *
 * This file contains classes and functions from MediaWiki 1.22.1
 *
 * Each class/function lists which file and line number they came from.
 *
 * Added to Kraven on: 2014-10-05 by Nathan Weiler (ncweiler2@hotmail.com)
 * Last modified for Kraven on: 2014-11-11 by Nathan Weiler (ncweiler2@hotmail.com)
**/

/**
 *
 * @ingroup Media
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason, 2009 Brent Garber
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @see http://exif.org/Exif2-2.PDF The Exif 2.2 specification
 * @file
**/

/**
 * Class to extract and validate Exif data from jpeg (and possibly tiff) files.
 *
 * class Exif() is from '/includes/media/Exif.php'
**/
class Exif {

	const BYTE = 1; //!< An 8-bit (1-byte) unsigned integer.
	const ASCII = 2; //!< An 8-bit byte containing one 7-bit ASCII code. The final byte is terminated with NULL.
	const SHORT = 3; //!< A 16-bit (2-byte) unsigned integer.
	const LONG = 4; //!< A 32-bit (4-byte) unsigned integer.
	const RATIONAL = 5; //!< Two LONGs. The first LONG is the numerator and the second LONG expresses the denominator
	const SHORT_OR_LONG = 6; //!< A 16-bit (2-byte) or 32-bit (4-byte) unsigned integer.
	const UNDEFINED = 7; //!< An 8-bit byte that can take any value depending on the field definition
	const SLONG = 9; //!< A 32-bit (4-byte) signed integer (2's complement notation),
	const SRATIONAL = 10; //!< Two SLONGs. The first SLONG is the numerator and the second SLONG is the denominator.
	const IGNORE = -1; // A fake value for things we don't want or don't support.

	//@{
	/* @var array
	 * @private
	 */

	/**
	 * Exif tags grouped by category, the tagname itself is the key and the type
	 * is the value, in the case of more than one possible value type they are
	 * separated by commas.
	 */
	var $mExifTags;

	/**
	 * The raw Exif data returned by PHP's exif_read_data()
	 */
	var $mRawExifData;

	/**
	 * A Filtered version of $mRawExifData that has been pruned of invalid
	 * tags and tags that contain content they shouldn't contain according
	 * to the Exif specification
	 */
	var $mFilteredExifData;

	/**
	 * Filtered and formatted Exif data from getFormattedData()
	 */
	var $mFormattedExifData;

	//@}

	//@{
	/* @var string
	 * @private
	 */

	/**
	 * The file being processed
	 */
	var $file;

	/**
	 * The basename of the file being processed
	 */
	var $basename;

	/**
	 * If true, print debug messages as HTML comments
	 */
	var $debug = false;

	/**
	 * The byte order of the file. Needed because php's
	 * extension doesn't fully process some obscure props.
	 */
	private $byteOrder;
	//@}

	function __construct($file = '', $byteOrder = '') {
        if ($file != '') {
            $this->extractData($file,$byteorder);
        }
	}

	/**
	 * The main function
	 *
	 * @param string $file filename.
	 * @param string $byteOrder Type of byte ordering either 'BE' (Big Endian) or 'LE' (Little Endian). Default ''.
	 * @returns string message if unable to locate PHP's exif_read_data() function
     *          array $this->mFilteredExifData if EXIF data extracted
	 * @todo FIXME: The following are broke:
	 * SubjectArea. Need to test the more obscure tags.
	 *
	 * DigitalZoomRatio = 0/0 is rejected. need to determine if that's valid.
	 * possibly should treat 0/0 = 0. need to read exif spec on that.
	 */
    public function extractData($file, $byteOrder = '') {
		/**
		 * Page numbers here refer to pages in the Exif 2.2 standard
		 *
		 * Note, Exif::UNDEFINED is treated as a string, not as an array of bytes
		 * so don't put a count parameter for any UNDEFINED values.
		 *
		 * @link http://exif.org/Exif2-2.PDF The Exif 2.2 specification
		 */
		$this->mExifTags = array(
			# TIFF Rev. 6.0 Attribute Information (p22)
			'IFD0' => array(
				# Tags relating to image structure
				'ImageWidth' => Exif::SHORT_OR_LONG,		# Image width
				'ImageLength' => Exif::SHORT_OR_LONG,		# Image height
				'BitsPerSample' => array( Exif::SHORT, 3 ),		# Number of bits per component
				# "When a primary image is JPEG compressed, this designation is not"
				# "necessary and is omitted." (p23)
				'Compression' => Exif::SHORT,				# Compression scheme #p23
				'PhotometricInterpretation' => Exif::SHORT,		# Pixel composition #p23
				'Orientation' => Exif::SHORT,				# Orientation of image #p24
				'SamplesPerPixel' => Exif::SHORT,			# Number of components
				'PlanarConfiguration' => Exif::SHORT,			# Image data arrangement #p24
				'YCbCrSubSampling' => array( Exif::SHORT, 2 ),		# Subsampling ratio of Y to C #p24
				'YCbCrPositioning' => Exif::SHORT,			# Y and C positioning #p24-25
				'XResolution' => Exif::RATIONAL,			# Image resolution in width direction
				'YResolution' => Exif::RATIONAL,			# Image resolution in height direction
				'ResolutionUnit' => Exif::SHORT,			# Unit of X and Y resolution #(p26)

				# Tags relating to recording offset
				'StripOffsets' => Exif::SHORT_OR_LONG,			# Image data location
				'RowsPerStrip' => Exif::SHORT_OR_LONG,			# Number of rows per strip
				'StripByteCounts' => Exif::SHORT_OR_LONG,		# Bytes per compressed strip
				'JPEGInterchangeFormat' => Exif::SHORT_OR_LONG,		# Offset to JPEG SOI
				'JPEGInterchangeFormatLength' => Exif::SHORT_OR_LONG,	# Bytes of JPEG data

				# Tags relating to image data characteristics
				'TransferFunction' => Exif::IGNORE,			# Transfer function
				'WhitePoint' => array( Exif::RATIONAL, 2 ),		# White point chromaticity
				'PrimaryChromaticities' => array( Exif::RATIONAL, 6 ),	# Chromaticities of primarities
				'YCbCrCoefficients' => array( Exif::RATIONAL, 3 ),	# Color space transformation matrix coefficients #p27
				'ReferenceBlackWhite' => array( Exif::RATIONAL, 6 ),	# Pair of black and white reference values

				# Other tags
				'DateTime' => Exif::ASCII,				# File change date and time
				'ImageDescription' => Exif::ASCII,			# Image title
				'Make' => Exif::ASCII,					# Image input equipment manufacturer
				'Model' => Exif::ASCII,					# Image input equipment model
				'Software' => Exif::ASCII,				# Software used
				'Artist' => Exif::ASCII,				# Person who created the image
				'Copyright' => Exif::ASCII,				# Copyright holder
			),

			# Exif IFD Attribute Information (p30-31)
			'EXIF' => array(
				# TODO: NOTE: Nonexistence of this field is taken to mean nonconformance
				# to the Exif 2.1 AND 2.2 standards
				'ExifVersion' => Exif::UNDEFINED,			# Exif version
				'FlashPixVersion' => Exif::UNDEFINED,			# Supported Flashpix version #p32

				# Tags relating to Image Data Characteristics
				'ColorSpace' => Exif::SHORT,				# Color space information #p32

				# Tags relating to image configuration
				'ComponentsConfiguration' => Exif::UNDEFINED,			# Meaning of each component #p33
				'CompressedBitsPerPixel' => Exif::RATIONAL,			# Image compression mode
				'PixelYDimension' => Exif::SHORT_OR_LONG,		# Valid image width
				'PixelXDimension' => Exif::SHORT_OR_LONG,		# Valid image height

				# Tags relating to related user information
				'MakerNote' => Exif::IGNORE,				# Manufacturer notes
				'UserComment' => Exif::UNDEFINED,			# User comments #p34

				# Tags relating to related file information
				'RelatedSoundFile' => Exif::ASCII,			# Related audio file

				# Tags relating to date and time
				'DateTimeOriginal' => Exif::ASCII,			# Date and time of original data generation #p36
				'DateTimeDigitized' => Exif::ASCII,			# Date and time of original data generation
				'SubSecTime' => Exif::ASCII,				# DateTime subseconds
				'SubSecTimeOriginal' => Exif::ASCII,			# DateTimeOriginal subseconds
				'SubSecTimeDigitized' => Exif::ASCII,			# DateTimeDigitized subseconds

				# Tags relating to picture-taking conditions (p31)
				'ExposureTime' => Exif::RATIONAL,			# Exposure time
				'FNumber' => Exif::RATIONAL,				# F Number
				'ExposureProgram' => Exif::SHORT,			# Exposure Program #p38
				'SpectralSensitivity' => Exif::ASCII,			# Spectral sensitivity
				'ISOSpeedRatings' => Exif::SHORT,			# ISO speed rating
				'OECF' => Exif::IGNORE,
				# Optoelectronic conversion factor. Note: We don't have support for this atm.
				'ShutterSpeedValue' => Exif::SRATIONAL,			# Shutter speed
				'ApertureValue' => Exif::RATIONAL,			# Aperture
				'BrightnessValue' => Exif::SRATIONAL,			# Brightness
				'ExposureBiasValue' => Exif::SRATIONAL,			# Exposure bias
				'MaxApertureValue' => Exif::RATIONAL,			# Maximum land aperture
				'SubjectDistance' => Exif::RATIONAL,			# Subject distance
				'MeteringMode' => Exif::SHORT,				# Metering mode #p40
				'LightSource' => Exif::SHORT,				# Light source #p40-41
				'Flash' => Exif::SHORT,					# Flash #p41-42
				'FocalLength' => Exif::RATIONAL,			# Lens focal length
				'SubjectArea' => array( Exif::SHORT, 4 ),		# Subject area
				'FlashEnergy' => Exif::RATIONAL,			# Flash energy
				'SpatialFrequencyResponse' => Exif::IGNORE,		# Spatial frequency response. Not supported atm.
				'FocalPlaneXResolution' => Exif::RATIONAL,		# Focal plane X resolution
				'FocalPlaneYResolution' => Exif::RATIONAL,		# Focal plane Y resolution
				'FocalPlaneResolutionUnit' => Exif::SHORT,		# Focal plane resolution unit #p46
				'SubjectLocation' => array( Exif::SHORT, 2 ),		# Subject location
				'ExposureIndex' => Exif::RATIONAL,			# Exposure index
				'SensingMethod' => Exif::SHORT,				# Sensing method #p46
				'FileSource' => Exif::UNDEFINED,			# File source #p47
				'SceneType' => Exif::UNDEFINED,				# Scene type #p47
				'CFAPattern' => Exif::IGNORE,				# CFA pattern. not supported atm.
				'CustomRendered' => Exif::SHORT,			# Custom image processing #p48
				'ExposureMode' => Exif::SHORT,				# Exposure mode #p48
				'WhiteBalance' => Exif::SHORT,				# White Balance #p49
				'DigitalZoomRatio' => Exif::RATIONAL,			# Digital zoom ration
				'FocalLengthIn35mmFilm' => Exif::SHORT,			# Focal length in 35 mm film
				'SceneCaptureType' => Exif::SHORT,			# Scene capture type #p49
				'GainControl' => Exif::SHORT,				# Scene control #p49-50
				'Contrast' => Exif::SHORT,				# Contrast #p50
				'Saturation' => Exif::SHORT,				# Saturation #p50
				'Sharpness' => Exif::SHORT,				# Sharpness #p50
				'DeviceSettingDescription' => Exif::IGNORE,
				# Device settings description. This could maybe be supported. Need to find an
				# example file that uses this to see if it has stuff of interest in it.
				'SubjectDistanceRange' => Exif::SHORT,			# Subject distance range #p51

				'ImageUniqueID' => Exif::ASCII,				# Unique image ID
			),

			# GPS Attribute Information (p52)
			'GPS' => array(
				'GPSVersion' => Exif::UNDEFINED,
				# Should be an array of 4 Exif::BYTE's. However php treats it as an undefined
				# Note exif standard calls this GPSVersionID, but php doesn't like the id suffix
				'GPSLatitudeRef' => Exif::ASCII,			# North or South Latitude #p52-53
				'GPSLatitude' => array( Exif::RATIONAL, 3 ),		# Latitude
				'GPSLongitudeRef' => Exif::ASCII,			# East or West Longitude #p53
				'GPSLongitude' => array( Exif::RATIONAL, 3 ),		# Longitude
				'GPSAltitudeRef' => Exif::UNDEFINED,
				# Altitude reference. Note, the exif standard says this should be an EXIF::Byte,
				# but php seems to disagree.
				'GPSAltitude' => Exif::RATIONAL,			# Altitude
				'GPSTimeStamp' => array( Exif::RATIONAL, 3 ),		# GPS time (atomic clock)
				'GPSSatellites' => Exif::ASCII,				# Satellites used for measurement
				'GPSStatus' => Exif::ASCII,				# Receiver status #p54
				'GPSMeasureMode' => Exif::ASCII,			# Measurement mode #p54-55
				'GPSDOP' => Exif::RATIONAL,				# Measurement precision
				'GPSSpeedRef' => Exif::ASCII,				# Speed unit #p55
				'GPSSpeed' => Exif::RATIONAL,				# Speed of GPS receiver
				'GPSTrackRef' => Exif::ASCII,				# Reference for direction of movement #p55
				'GPSTrack' => Exif::RATIONAL,				# Direction of movement
				'GPSImgDirectionRef' => Exif::ASCII,			# Reference for direction of image #p56
				'GPSImgDirection' => Exif::RATIONAL,			# Direction of image
				'GPSMapDatum' => Exif::ASCII,				# Geodetic survey data used
				'GPSDestLatitudeRef' => Exif::ASCII,			# Reference for latitude of destination #p56
				'GPSDestLatitude' => array( Exif::RATIONAL, 3 ),	# Latitude destination
				'GPSDestLongitudeRef' => Exif::ASCII,			# Reference for longitude of destination #p57
				'GPSDestLongitude' => array( Exif::RATIONAL, 3 ),	# Longitude of destination
				'GPSDestBearingRef' => Exif::ASCII,			# Reference for bearing of destination #p57
				'GPSDestBearing' => Exif::RATIONAL,			# Bearing of destination
				'GPSDestDistanceRef' => Exif::ASCII,			# Reference for distance to destination #p57-58
				'GPSDestDistance' => Exif::RATIONAL,			# Distance to destination
				'GPSProcessingMethod' => Exif::UNDEFINED,		# Name of GPS processing method
				'GPSAreaInformation' => Exif::UNDEFINED,		# Name of GPS area
				'GPSDateStamp' => Exif::ASCII,				# GPS date
				'GPSDifferential' => Exif::SHORT,			# GPS differential correction
			),
		);

		$this->file = $file;
		$this->basename = basename($this->file);
		if ( $byteOrder === 'BE' || $byteOrder === 'LE' ) {
			$this->byteOrder = $byteOrder;
		} else {
			$this->byteOrder = 'BE'; // BE seems about twice as popular as LE in jpg's.
		}

		$this->debugFile( $this->basename, __FUNCTION__, true );
		if ( function_exists( 'exif_read_data' ) ) {
			$data = exif_read_data($this->file, 0, true);
		} else {
			echo 'Unable to find PHP\'s exif_read_data() function.';
            return;
		}
		/**
		 * exif_read_data() will return false on invalid input, such as
		 * when somebody uploads a file called something.jpeg
		 * containing random gibberish.
		 */
		$this->mRawExifData = $data ? $data : array();
		$this->makeFilteredData();
		$this->collapseData();
		$this->debugFile( __FUNCTION__, false );
    }
    // END public function extractData()

	/**
	 * Make $this->mFilteredExifData
	 */
	private function makeFilteredData() {
		$this->mFilteredExifData = Array();

		foreach ( array_keys( $this->mRawExifData ) as $section ) {
			if ( !in_array( $section, array_keys( $this->mExifTags ) ) ) {
				$this->debug( $section, __FUNCTION__, "'$section' is not a valid Exif section" );
				continue;
			}

			foreach ( array_keys( $this->mRawExifData[$section] ) as $tag ) {
				if ( !in_array( $tag, array_keys( $this->mExifTags[$section] ) ) ) {
					$this->debug( $tag, __FUNCTION__, "'$tag' is not a valid tag in '$section'" );
					continue;
				}

				$this->mFilteredExifData[$tag] = $this->mRawExifData[$section][$tag];
				// This is ok, as the tags in the different sections do not conflict.
				// except in computed and thumbnail section, which we don't use.

				$value = $this->mRawExifData[$section][$tag];
				if ( !$this->validate( $section, $tag, $value ) ) {
					$this->debug( $value, __FUNCTION__, "'$tag' contained invalid data" );
					unset( $this->mFilteredExifData[$tag] );
				}
			}
		}
	}

	/**
	 * Collapse some fields together.
	 * This converts some fields from exif form, to a more friendly form.
	 * For example GPS latitude to a single number.
	 *
	 * The rationale behind this is that we're storing data, not presenting to the user
	 * For example a longitude is a single number describing how far away you are from
	 * the prime meridian. Well it might be nice to split it up into minutes and seconds
	 * for the user, it doesn't really make sense to split a single number into 4 parts
	 * for storage. (degrees, minutes, second, direction vs single floating point number).
	 *
	 * Other things this might do (not really sure if they make sense or not):
	 * Dates -> mediawiki date format.
	 * convert values that can be in different units to be in one standardized unit.
	 *
	 * As an alternative approach, some of this could be done in the validate phase
	 * if we make up our own types like Exif::DATE.
	 */
	private function collapseData() {

		$this->exifGPStoNumber( 'GPSLatitude' );
		$this->exifGPStoNumber( 'GPSDestLatitude' );
		$this->exifGPStoNumber( 'GPSLongitude' );
		$this->exifGPStoNumber( 'GPSDestLongitude' );

		if ( isset( $this->mFilteredExifData['GPSAltitude'] ) && isset( $this->mFilteredExifData['GPSAltitudeRef'] ) ) {

			// We know altitude data is a <num>/<denom> from the validation functions ran earlier.
			// But multiplying such a string by -1 doesn't work well, so convert.
			list( $num, $denom ) = explode( '/', $this->mFilteredExifData['GPSAltitude'] );
			$this->mFilteredExifData['GPSAltitude'] = $num / $denom;

			if ( $this->mFilteredExifData['GPSAltitudeRef'] === "\1" ) {
				$this->mFilteredExifData['GPSAltitude'] *= - 1;
			}
			unset( $this->mFilteredExifData['GPSAltitudeRef'] );
		}

		$this->exifPropToOrd( 'FileSource' );
		$this->exifPropToOrd( 'SceneType' );

		$this->charCodeString( 'UserComment' );
		$this->charCodeString( 'GPSProcessingMethod' );
		$this->charCodeString( 'GPSAreaInformation' );

		//ComponentsConfiguration should really be an array instead of a string...
		//This turns a string of binary numbers into an array of numbers.

		if ( isset( $this->mFilteredExifData['ComponentsConfiguration'] ) ) {
			$val = $this->mFilteredExifData['ComponentsConfiguration'];
			$ccVals = array();
			for ( $i = 0; $i < strlen( $val ); $i++ ) {
				$ccVals[$i] = ord( substr( $val, $i, 1 ) );
			}
			$ccVals['_type'] = 'ol'; //this is for formatting later.
			$this->mFilteredExifData['ComponentsConfiguration'] = $ccVals;
		}

		//GPSVersion(ID) is treated as the wrong type by php exif support.
		//Go through each byte turning it into a version string.
		//For example: "\x02\x02\x00\x00" -> "2.2.0.0"

		//Also change exif tag name from GPSVersion (what php exif thinks it is)
		//to GPSVersionID (what the exif standard thinks it is).

		if ( isset( $this->mFilteredExifData['GPSVersion'] ) ) {
			$val = $this->mFilteredExifData['GPSVersion'];
			$newVal = '';
			for ( $i = 0; $i < strlen( $val ); $i++ ) {
				if ( $i !== 0 ) {
					$newVal .= '.';
				}
				$newVal .= ord( substr( $val, $i, 1 ) );
			}
			if ( $this->byteOrder === 'LE' ) {
				// Need to reverse the string
				$newVal2 = '';
				for ( $i = strlen( $newVal ) - 1; $i >= 0; $i-- ) {
					$newVal2 .= substr( $newVal, $i, 1 );
				}
				$this->mFilteredExifData['GPSVersionID'] = $newVal2;
			} else {
				$this->mFilteredExifData['GPSVersionID'] = $newVal;
			}
			unset( $this->mFilteredExifData['GPSVersion'] );
		}

	}
	/**
	 * Do userComment tags and similar. See pg. 34 of exif standard.
	 * basically first 8 bytes is charset, rest is value.
	 * This has not been tested on any shift-JIS strings.
	 * @param string $prop prop name.
	**/
	private function charCodeString( $prop ) {
		if ( isset( $this->mFilteredExifData[$prop] ) ) {

			if ( strlen( $this->mFilteredExifData[$prop] ) <= 8 ) {
				//invalid. Must be at least 9 bytes long.

				$this->debug( $this->mFilteredExifData[$prop], __FUNCTION__, false );
				unset( $this->mFilteredExifData[$prop] );
				return;
			}
			$charCode = substr( $this->mFilteredExifData[$prop], 0, 8 );
			$val = substr( $this->mFilteredExifData[$prop], 8 );

			switch ( $charCode ) {
				case "\x4A\x49\x53\x00\x00\x00\x00\x00":
					//JIS
					$charset = "Shift-JIS";
					break;
				case "UNICODE\x00":
					$charset = "UTF-16" . $this->byteOrder;
					break;
				default: //ascii or undefined.
					$charset = "";
					break;
			}
			// This could possibly check to see if iconv is really installed
			// or if we're using the compatibility wrapper in globalFunctions.php
			if ( $charset ) {
				$val = iconv( $charset, 'UTF-8//IGNORE', $val );
			} else {
				// if valid utf-8, assume that, otherwise assume windows-1252
				$valCopy = $val;
				UtfNormal::quickIsNFCVerify( $valCopy ); //validates $valCopy.
				if ( $valCopy !== $val ) {
					$val = iconv( 'Windows-1252', 'UTF-8//IGNORE', $val );
				}
			}

			//trim and check to make sure not only whitespace.
			$val = trim( $val );
			if ( strlen( $val ) === 0 ) {
				//only whitespace.
				$this->debug( $this->mFilteredExifData[$prop], __FUNCTION__, "$prop: Is only whitespace" );
				unset( $this->mFilteredExifData[$prop] );
				return;
			}

			//all's good.
			$this->mFilteredExifData[$prop] = $val;
		}
	}
	/**
	 * Convert an Exif::UNDEFINED from a raw binary string
	 * to its value. This is sometimes needed depending on
	 * the type of UNDEFINED field
	 * @param string $prop name of property
	 */
	private function exifPropToOrd( $prop ) {
		if ( isset( $this->mFilteredExifData[$prop] ) ) {
			$this->mFilteredExifData[$prop] = ord( $this->mFilteredExifData[$prop] );
		}
	}
	/**
	 * Convert gps in exif form to a single floating point number
	 * for example 10 degress 20`40`` S -> -10.34444
	 * @param string $prop a gps coordinate exif tag name (like GPSLongitude)
	 */
	private function exifGPStoNumber( $prop ) {
		$loc =& $this->mFilteredExifData[$prop];
		$dir =& $this->mFilteredExifData[$prop . 'Ref'];
		$res = false;

		if ( isset( $loc ) && isset( $dir ) && ( $dir === 'N' || $dir === 'S' || $dir === 'E' || $dir === 'W' ) ) {
			list( $num, $denom ) = explode( '/', $loc[0] );
			$res = $num / $denom;
			list( $num, $denom ) = explode( '/', $loc[1] );
			$res += ( $num / $denom ) * ( 1 / 60 );
			list( $num, $denom ) = explode( '/', $loc[2] );
			$res += ( $num / $denom ) * ( 1 / 3600 );

			if ( $dir === 'S' || $dir === 'W' ) {
				$res *= - 1; // make negative
			}
		}

		// update the exif records.

		if ( $res !== false ) { // using !== as $res could potentially be 0
			$this->mFilteredExifData[$prop] = $res;
			unset( $this->mFilteredExifData[$prop . 'Ref'] );
		} else { // if invalid
			unset( $this->mFilteredExifData[$prop] );
			unset( $this->mFilteredExifData[$prop . 'Ref'] );
		}
	}

	/**#@+
	 * @return array
	 */
	/**
	 * Get $this->mRawExifData
	 * @return array
	 */
	public function getRawData() {
		return $this->mRawExifData;
	}

	/**
	 * Get $this->mFilteredExifData
	 */
	public function getFilteredData() {
		return $this->mFilteredExifData;
	}

	/**
	 * Get $this->mRawExifData['FILE']
	 */
	public function getFileData() {
        if (is_array($this->mRawExifData['FILE']) !== true ||
            (is_array($this->mRawExifData['FILE']) === true && empty($this->mRawExifData['FILE']) === true)) {
            // $mRawExifData['FILE'] is not an array or is an empty array
            $this->mRawExifData['FILE'] = array(
                'FileName' => $this->basename
            );
        }

        if (is_array($this->mRawExifData['COMPUTED']) !== true ||
            (is_array($this->mRawExifData['COMPUTED']) === true && empty($this->mRawExifData['COMPUTED']) === true)) {
            // $mRawExifData['COMPUTED'] is not an array or is an empty array
            $this->mRawExifData['COMPUTED'] = array();
        }

		return array_merge($this->mRawExifData['FILE'],$this->mRawExifData['COMPUTED']);
	}

	/**
	 * The version of the output format
	 *
	 * Before the actual metadata information is saved in the database we
	 * strip some of it since we don't want to save things like thumbnails
	 * which usually accompany Exif data. This value gets saved in the
	 * database along with the actual Exif data, and if the version in the
	 * database doesn't equal the value returned by this function the Exif
	 * data is regenerated.
	 *
	 * @return int
	 */
	public static function version() {
		return 2; // We don't need no bloddy constants!
	}

	/**#@+
	 * Validates if a tag value is of the type it should be according to the Exif spec
	 *
	 * @private
	 *
	 * @param $in Mixed: the input value to check
	 * @return bool
	 */
	private function isByte( $in ) {
		if ( !is_array( $in ) && sprintf( '%d', $in ) == $in && $in >= 0 && $in <= 255 ) {
			$this->debug( $in, __FUNCTION__, true );
			return true;
		} else {
			$this->debug( $in, __FUNCTION__, false );
			return false;
		}
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isASCII( $in ) {
		if ( is_array( $in ) ) {
			return false;
		}

		if ( preg_match( "/[^\x0a\x20-\x7e]/", $in ) ) {
			$this->debug( $in, __FUNCTION__, 'found a character not in our whitelist' );
			return false;
		}

		if ( preg_match( '/^\s*$/', $in ) ) {
			$this->debug( $in, __FUNCTION__, 'input consisted solely of whitespace' );
			return false;
		}

		return true;
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isShort( $in ) {
		if ( !is_array( $in ) && sprintf( '%d', $in ) == $in && $in >= 0 && $in <= 65536 ) {
			$this->debug( $in, __FUNCTION__, true );
			return true;
		} else {
			$this->debug( $in, __FUNCTION__, false );
			return false;
		}
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isLong( $in ) {
		if ( !is_array( $in ) && sprintf( '%d', $in ) == $in && $in >= 0 && $in <= 4294967296 ) {
			$this->debug( $in, __FUNCTION__, true );
			return true;
		} else {
			$this->debug( $in, __FUNCTION__, false );
			return false;
		}
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isRational( $in ) {
		$m = array();
		if ( !is_array( $in ) && preg_match( '/^(\d+)\/(\d+[1-9]|[1-9]\d*)$/', $in, $m ) ) { # Avoid division by zero
			return $this->isLong( $m[1] ) && $this->isLong( $m[2] );
		} else {
			$this->debug( $in, __FUNCTION__, 'fed a non-fraction value' );
			return false;
		}
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isUndefined( $in ) {
		$this->debug( $in, __FUNCTION__, true );
		return true;
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isSlong( $in ) {
		if ( $this->isLong( abs( $in ) ) ) {
			$this->debug( $in, __FUNCTION__, true );
			return true;
		} else {
			$this->debug( $in, __FUNCTION__, false );
			return false;
		}
	}

	/**
	 * @param $in
	 * @return bool
	 */
	private function isSrational( $in ) {
		$m = array();
		if ( !is_array( $in ) && preg_match( '/^(-?\d+)\/(\d+[1-9]|[1-9]\d*)$/', $in, $m ) ) { # Avoid division by zero
			return $this->isSlong( $m[0] ) && $this->isSlong( $m[1] );
		} else {
			$this->debug( $in, __FUNCTION__, 'fed a non-fraction value' );
			return false;
		}
	}
	/**#@-*/

	/**
	 * Validates if a tag has a legal value according to the Exif spec
	 *
	 * @private
	 * @param string $section section where tag is located.
	 * @param string $tag the tag to check.
	 * @param $val Mixed: the value of the tag.
	 * @param $recursive Boolean: true if called recursively for array types.
	 * @return bool
	 */
	private function validate( $section, $tag, $val, $recursive = false ) {
		$debug = "tag is '$tag'";
		$etype = $this->mExifTags[$section][$tag];
		$ecount = 1;
		if ( is_array( $etype ) ) {
			list( $etype, $ecount ) = $etype;
			if ( $recursive ) {
				$ecount = 1; // checking individual elements
			}
		}
		$count = count( $val );
		if ( $ecount != $count ) {
			$this->debug( $val, __FUNCTION__, "Expected $ecount elements for $tag but got $count" );
			return false;
		}
		if ( $count > 1 ) {
			foreach ( $val as $v ) {
				if ( !$this->validate( $section, $tag, $v, true ) ) {
					return false;
				}
			}
			return true;
		}
		// Does not work if not typecast
		switch ( (string)$etype ) {
			case (string)Exif::BYTE:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isByte( $val );
			case (string)Exif::ASCII:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isASCII( $val );
			case (string)Exif::SHORT:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isShort( $val );
			case (string)Exif::LONG:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isLong( $val );
			case (string)Exif::RATIONAL:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isRational( $val );
			case (string)Exif::SHORT_OR_LONG:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isShort( $val ) || $this->isLong( $val );
			case (string)Exif::UNDEFINED:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isUndefined( $val );
			case (string)Exif::SLONG:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isSlong( $val );
			case (string)Exif::SRATIONAL:
				$this->debug( $val, __FUNCTION__, $debug );
				return $this->isSrational( $val );
			case (string)Exif::IGNORE:
				$this->debug( $val, __FUNCTION__, $debug );
				return false;
			default:
				$this->debug( $val, __FUNCTION__, "The tag '$tag' is unknown" );
				return false;
		}
	}

	/**
	 * Convenience function for debugging output
     *
     * This function simply echos to screen whatever it receives since I currently
     * have no need to log the info it receives.
	 *
	 * @private
	 *
	 * @param $in Mixed:
	 * @param $fname String:
	 * @param $action Mixed: , default NULL.
	**/
	private function debug( $in, $fname, $action = null ) {
        if ($this->debug === false) {
            return;
        }

        echo '<!-- $in = '.$in.' $fname = '.$fname.' $action = ';
        if ($action === null) {
            echo 'null';
        } else {
            echo $action;
        }
        echo "-->\n";
	}

	/**
	 * Convenience function for debugging output
     *
     * This function simply echos to screen whatever it receives since I currently
     * have no need to log the info it receives.
	 *
	 * @private
	 *
	 * @param string $fname the name of the function calling this function
	 * @param $io Boolean: Specify whether we're beginning or ending
	 */
	private function debugFile( $fname, $io ) {
        if ($this->debug === false) {
            return;
        }
        echo '<!-- $fname = '.$fname.' $io = '.$io.'-->'."\n";
	}
}

/**
 * Format Image metadata values into a human readable form.
 *
 * class FormatMetadata() is from '/includes/media/FromatMetadata.php'
 *
 * Note lots of these messages use the prefix 'exif' even though
 * they may not be exif properties. For example 'exif-ImageDescription'
 * can be the Exif ImageDescription, or it could be the iptc-iim caption
 * property, or it could be the xmp dc:description property. This
 * is because these messages should be independent of how the data is
 * stored, sine the user doesn't care if the description is stored in xmp,
 * exif, etc only that its a description. (Additionally many of these properties
 * are merged together following the MWG standard, such that for example,
 * exif properties override XMP properties that mean the same thing if
 * there is a conflict).
 *
 * It should perhaps use a prefix like 'metadata' instead, but there
 * is already a large number of messages using the 'exif' prefix.
 *
 * @ingroup Media
 * @author Ævar Arnfjörð Bjarmason <avarab@gmail.com>
 * @copyright Copyright © 2005, Ævar Arnfjörð Bjarmason, 2009 Brent Garber, 2010 Brian Wolff
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @see http://exif.org/Exif2-2.PDF The Exif 2.2 specification
 * @file
**/
class FormatMetadata {

	/**
	 * Numbers given by Exif user agents are often magical, that is they
	 * should be replaced by a detailed explanation depending on their
	 * value which most of the time are plain integers. This function
	 * formats Exif (and other metadata) values into human readable form.
	 *
	 * @param array $tags the Exif data to format ( as returned by
	 *                    Exif::getFilteredData() or BitmapMetadataHandler )
	 * @return array
	 */
	public static function getFormattedData( $tags ) {
		global $wgLang;

		$resolutionunit = !isset( $tags['ResolutionUnit'] ) || $tags['ResolutionUnit'] == 2 ? 2 : 3;
		unset( $tags['ResolutionUnit'] );

		foreach ( $tags as $tag => &$vals ) {

			// This seems ugly to wrap non-array's in an array just to unwrap again,
			// especially when most of the time it is not an array
			if ( !is_array( $tags[$tag] ) ) {
				$vals = Array( $vals );
			}

			// _type is a special value to say what array type
			if ( isset( $tags[$tag]['_type'] ) ) {
				$type = $tags[$tag]['_type'];
				unset( $vals['_type'] );
			} else {
				$type = 'ul'; // default unordered list.
			}

			//This is done differently as the tag is an array.
			if ( $tag == 'GPSTimeStamp' && count( $vals ) === 3 ) {
				//hour min sec array

				$h = explode( '/', $vals[0] );
				$m = explode( '/', $vals[1] );
				$s = explode( '/', $vals[2] );

				// this should already be validated
				// when loaded from file, but it could
				// come from a foreign repo, so be
				// paranoid.
				if ( !isset( $h[1] )
					|| !isset( $m[1] )
					|| !isset( $s[1] )
					|| $h[1] == 0
					|| $m[1] == 0
					|| $s[1] == 0
				) {
					continue;
				}
				$tags[$tag] = str_pad( intval( $h[0] / $h[1] ), 2, '0', STR_PAD_LEFT )
					. ':' . str_pad( intval( $m[0] / $m[1] ), 2, '0', STR_PAD_LEFT )
					. ':' . str_pad( intval( $s[0] / $s[1] ), 2, '0', STR_PAD_LEFT );

				try {
					$time = wfTimestamp( TS_MW, '1971:01:01 ' . $tags[$tag] );
					// the 1971:01:01 is just a placeholder, and not shown to user.
					if ( $time && intval( $time ) > 0 ) {
						$tags[$tag] = $wgLang->time( $time );
					}
				} catch ( TimestampException $e ) {
					// This shouldn't happen, but we've seen bad formats
					// such as 4-digit seconds in the wild.
					// leave $tags[$tag] as-is
				}
				continue;
			}

			// The contact info is a multi-valued field
			// instead of the other props which are single
			// valued (mostly) so handle as a special case.
			if ( $tag === 'Contact' ) {
				$vals = self::collapseContactInfo( $vals );
				continue;
			}

			foreach ( $vals as &$val ) {

				switch ( $tag ) {
				case 'Compression':
					switch ( $val ) {
					case 1: case 2: case 3: case 4:
					case 5: case 6: case 7: case 8:
					case 32773: case 32946: case 34712:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'PhotometricInterpretation':
					switch ( $val ) {
					case 2: case 6:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'Orientation':
					switch ( $val ) {
					case 1: case 2: case 3: case 4: case 5: case 6: case 7: case 8:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'PlanarConfiguration':
					switch ( $val ) {
					case 1: case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				// TODO: YCbCrSubSampling
				case 'YCbCrPositioning':
					switch ( $val ) {
					case 1:
					case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'XResolution':
				case 'YResolution':
					switch ( $resolutionunit ) {
						case 2:
							$val = self::msg( 'XYResolution', 'i', self::formatNum( $val ) );
							break;
						case 3:
							$val = self::msg( 'XYResolution', 'c', self::formatNum( $val ) );
							break;
						default:
							/* If not recognized, display as is. */
							break;
					}
					break;

				// TODO: YCbCrCoefficients  #p27 (see annex E)
				case 'ExifVersion': case 'FlashpixVersion':
					$val = "$val" / 100;
					break;

				case 'ColorSpace':
					switch ( $val ) {
					case 1: case 65535:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'ComponentsConfiguration':
					switch ( $val ) {
					case 0: case 1: case 2: case 3: case 4: case 5: case 6:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'DateTime':
				case 'DateTimeOriginal':
				case 'DateTimeDigitized':
				case 'DateTimeReleased':
				case 'DateTimeExpires':
				case 'GPSDateStamp':
				case 'dc-date':
				case 'DateTimeMetadata':
					if ( $val == '0000:00:00 00:00:00' || $val == '    :  :     :  :  ' ) {
						$val = wfMessage( 'exif-unknowndate' )->text();
					} elseif ( preg_match( '/^(?:\d{4}):(?:\d\d):(?:\d\d) (?:\d\d):(?:\d\d):(?:\d\d)$/D', $val ) ) {
						// Full date.
						$time = wfTimestamp( TS_MW, $val );
						if ( $time && intval( $time ) > 0 ) {
							$val = $wgLang->timeanddate( $time );
						}
					} elseif ( preg_match( '/^(?:\d{4}):(?:\d\d):(?:\d\d) (?:\d\d):(?:\d\d)$/D', $val ) ) {
						// No second field. Still format the same
						// since timeanddate doesn't include seconds anyways,
						// but second still available in api
						$time = wfTimestamp( TS_MW, $val . ':00' );
						if ( $time && intval( $time ) > 0 ) {
							$val = $wgLang->timeanddate( $time );
						}
					} elseif ( preg_match( '/^(?:\d{4}):(?:\d\d):(?:\d\d)$/D', $val ) ) {
						// If only the date but not the time is filled in.
						$time = wfTimestamp( TS_MW, substr( $val, 0, 4 )
							. substr( $val, 5, 2 )
							. substr( $val, 8, 2 )
							. '000000' );
						if ( $time && intval( $time ) > 0 ) {
							$val = $wgLang->date( $time );
						}
					}
					// else it will just output $val without formatting it.
					break;

				case 'ExposureProgram':
					switch ( $val ) {
					case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7: case 8:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'SubjectDistance':
					$val = self::msg( $tag, '', self::formatNum( $val ) );
					break;

				case 'MeteringMode':
					switch ( $val ) {
					case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7: case 255:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'LightSource':
					switch ( $val ) {
					case 0: case 1: case 2: case 3: case 4: case 9: case 10: case 11:
					case 12: case 13: case 14: case 15: case 17: case 18: case 19: case 20:
					case 21: case 22: case 23: case 24: case 255:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'Flash':
					$flashDecode = array(
						'fired' => $val & bindec( '00000001' ),
						'return' => ( $val & bindec( '00000110' ) ) >> 1,
						'mode' => ( $val & bindec( '00011000' ) ) >> 3,
						'function' => ( $val & bindec( '00100000' ) ) >> 5,
						'redeye' => ( $val & bindec( '01000000' ) ) >> 6,
//						'reserved' => ($val & bindec( '10000000' )) >> 7,
					);
					$flashMsgs = array();
					# We do not need to handle unknown values since all are used.
					foreach ( $flashDecode as $subTag => $subValue ) {
						# We do not need any message for zeroed values.
						if ( $subTag != 'fired' && $subValue == 0 ) {
							continue;
						}
						$fullTag = $tag . '-' . $subTag;
						$flashMsgs[] = self::msg( $fullTag, $subValue );
					}
					$val = $wgLang->commaList( $flashMsgs );
					break;

				case 'FocalPlaneResolutionUnit':
					switch ( $val ) {
					case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'SensingMethod':
					switch ( $val ) {
					case 1: case 2: case 3: case 4: case 5: case 7: case 8:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'FileSource':
					switch ( $val ) {
					case 3:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'SceneType':
					switch ( $val ) {
					case 1:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'CustomRendered':
					switch ( $val ) {
					case 0: case 1:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'ExposureMode':
					switch ( $val ) {
					case 0: case 1: case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'WhiteBalance':
					switch ( $val ) {
					case 0: case 1:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'SceneCaptureType':
					switch ( $val ) {
					case 0: case 1: case 2: case 3:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GainControl':
					switch ( $val ) {
					case 0: case 1: case 2: case 3: case 4:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'Contrast':
					switch ( $val ) {
					case 0: case 1: case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'Saturation':
					switch ( $val ) {
					case 0: case 1: case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'Sharpness':
					switch ( $val ) {
					case 0: case 1: case 2:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'SubjectDistanceRange':
					switch ( $val ) {
					case 0: case 1: case 2: case 3:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				//The GPS...Ref values are kept for compatibility, probably won't be reached.
				case 'GPSLatitudeRef':
				case 'GPSDestLatitudeRef':
					switch ( $val ) {
					case 'N': case 'S':
						$val = self::msg( 'GPSLatitude', $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSLongitudeRef':
				case 'GPSDestLongitudeRef':
					switch ( $val ) {
					case 'E': case 'W':
						$val = self::msg( 'GPSLongitude', $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSAltitude':
					if ( $val < 0 ) {
						$val = self::msg( 'GPSAltitude', 'below-sealevel', self::formatNum( -$val, 3 ) );
					} else {
						$val = self::msg( 'GPSAltitude', 'above-sealevel', self::formatNum( $val, 3 ) );
					}
					break;

				case 'GPSStatus':
					switch ( $val ) {
					case 'A': case 'V':
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSMeasureMode':
					switch ( $val ) {
					case 2: case 3:
						$val = self::msg( $tag, $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSTrackRef':
				case 'GPSImgDirectionRef':
				case 'GPSDestBearingRef':
					switch ( $val ) {
					case 'T': case 'M':
						$val = self::msg( 'GPSDirection', $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSLatitude':
				case 'GPSDestLatitude':
					$val = self::formatCoords( $val, 'latitude' );
					break;
				case 'GPSLongitude':
				case 'GPSDestLongitude':
					$val = self::formatCoords( $val, 'longitude' );
					break;

				case 'GPSSpeedRef':
					switch ( $val ) {
					case 'K': case 'M': case 'N':
						$val = self::msg( 'GPSSpeed', $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSDestDistanceRef':
					switch ( $val ) {
					case 'K': case 'M': case 'N':
						$val = self::msg( 'GPSDestDistance', $val );
						break;
					default:
						/* If not recognized, display as is. */
						break;
					}
					break;

				case 'GPSDOP':
					// See http://en.wikipedia.org/wiki/Dilution_of_precision_(GPS)
					if ( $val <= 2 ) {
						$val = self::msg( $tag, 'excellent', self::formatNum( $val ) );
					} elseif ( $val <= 5 ) {
						$val = self::msg( $tag, 'good', self::formatNum( $val ) );
					} elseif ( $val <= 10 ) {
						$val = self::msg( $tag, 'moderate', self::formatNum( $val ) );
					} elseif ( $val <= 20 ) {
						$val = self::msg( $tag, 'fair', self::formatNum( $val ) );
					} else {
						$val = self::msg( $tag, 'poor', self::formatNum( $val ) );
					}
					break;

				// This is not in the Exif standard, just a special
				// case for our purposes which enables wikis to wikify
				// the make, model and software name to link to their articles.
				case 'Make':
				case 'Model':
					$val = self::msg( $tag, '', $val );
					break;

				case 'Software':
					if ( is_array( $val ) ) {
						//if its a software, version array.
						$val = wfMessage( 'exif-software-version-value', $val[0], $val[1] )->text();
					} else {
						$val = self::msg( $tag, '', $val );
					}
					break;

				case 'ExposureTime':
					// Show the pretty fraction as well as decimal version
					$val = wfMessage( 'exif-exposuretime-format',
						self::formatFraction( $val ), self::formatNum( $val ) )->text();
					break;
				case 'ISOSpeedRatings':
					// If its = 65535 that means its at the
					// limit of the size of Exif::short and
					// is really higher.
					if ( $val == '65535' ) {
						$val = self::msg( $tag, 'overflow' );
					} else {
						$val = self::formatNum( $val );
					}
					break;
				case 'FNumber':
					$val = wfMessage( 'exif-fnumber-format',
						self::formatNum( $val ) )->text();
					break;

				case 'FocalLength': case 'FocalLengthIn35mmFilm':
					$val = wfMessage( 'exif-focallength-format',
						self::formatNum( $val ) )->text();
					break;

				case 'MaxApertureValue':
					if ( strpos( $val, '/' ) !== false ) {
						// need to expand this earlier to calculate fNumber
						list( $n, $d ) = explode( '/', $val );
						if ( is_numeric( $n ) && is_numeric( $d ) ) {
							$val = $n / $d;
						}
					}
					if ( is_numeric( $val ) ) {
						$fNumber = pow( 2, $val / 2 );
						if ( $fNumber !== false ) {
							$val = wfMessage( 'exif-maxaperturevalue-value',
								self::formatNum( $val ),
								self::formatNum( $fNumber, 2 )
							)->text();
						}
					}
					break;

				case 'iimCategory':
					switch ( strtolower( $val ) ) {
						// See pg 29 of IPTC photo
						// metadata standard.
						case 'ace': case 'clj':
						case 'dis': case 'fin':
						case 'edu': case 'evn':
						case 'hth': case 'hum':
						case 'lab': case 'lif':
						case 'pol': case 'rel':
						case 'sci': case 'soi':
						case 'spo': case 'war':
						case 'wea':
							$val = self::msg(
								'iimcategory',
								$val
							);
					}
					break;
				case 'SubjectNewsCode':
					// Essentially like iimCategory.
					// 8 (numeric) digit hierarchical
					// classification. We decode the
					// first 2 digits, which provide
					// a broad category.
					$val = self::convertNewsCode( $val );
					break;
				case 'Urgency':
					// 1-8 with 1 being highest, 5 normal
					// 0 is reserved, and 9 is 'user-defined'.
					$urgency = '';
					if ( $val == 0 || $val == 9 ) {
						$urgency = 'other';
					} elseif ( $val < 5 && $val > 1 ) {
						$urgency = 'high';
					} elseif ( $val == 5 ) {
						$urgency = 'normal';
					} elseif ( $val <= 8 && $val > 5 ) {
						$urgency = 'low';
					}

					if ( $urgency !== '' ) {
						$val = self::msg( 'urgency',
							$urgency, $val
						);
					}
					break;

				// Things that have a unit of pixels.
				case 'OriginalImageHeight':
				case 'OriginalImageWidth':
				case 'PixelXDimension':
				case 'PixelYDimension':
				case 'ImageWidth':
				case 'ImageLength':
					$val = self::formatNum( $val ) . ' ' . wfMessage( 'unit-pixel' )->text();
					break;

				// Do not transform fields with pure text.
				// For some languages the formatNum()
				// conversion results to wrong output like
				// foo,bar@example,com or foo٫bar@example٫com.
				// Also some 'numeric' things like Scene codes
				// are included here as we really don't want
				// commas inserted.
				case 'ImageDescription':
				case 'Artist':
				case 'Copyright':
				case 'RelatedSoundFile':
				case 'ImageUniqueID':
				case 'SpectralSensitivity':
				case 'GPSSatellites':
				case 'GPSVersionID':
				case 'GPSMapDatum':
				case 'Keywords':
				case 'WorldRegionDest':
				case 'CountryDest':
				case 'CountryCodeDest':
				case 'ProvinceOrStateDest':
				case 'CityDest':
				case 'SublocationDest':
				case 'WorldRegionCreated':
				case 'CountryCreated':
				case 'CountryCodeCreated':
				case 'ProvinceOrStateCreated':
				case 'CityCreated':
				case 'SublocationCreated':
				case 'ObjectName':
				case 'SpecialInstructions':
				case 'Headline':
				case 'Credit':
				case 'Source':
				case 'EditStatus':
				case 'FixtureIdentifier':
				case 'LocationDest':
				case 'LocationDestCode':
				case 'Writer':
				case 'JPEGFileComment':
				case 'iimSupplementalCategory':
				case 'OriginalTransmissionRef':
				case 'Identifier':
				case 'dc-contributor':
				case 'dc-coverage':
				case 'dc-publisher':
				case 'dc-relation':
				case 'dc-rights':
				case 'dc-source':
				case 'dc-type':
				case 'Lens':
				case 'SerialNumber':
				case 'CameraOwnerName':
				case 'Label':
				case 'Nickname':
				case 'RightsCertificate':
				case 'CopyrightOwner':
				case 'UsageTerms':
				case 'WebStatement':
				case 'OriginalDocumentID':
				case 'LicenseUrl':
				case 'MorePermissionsUrl':
				case 'AttributionUrl':
				case 'PreferredAttributionName':
				case 'PNGFileComment':
				case 'Disclaimer':
				case 'ContentWarning':
				case 'GIFFileComment':
				case 'SceneCode':
				case 'IntellectualGenre':
				case 'Event':
				case 'OrginisationInImage':
				case 'PersonInImage':

					$val = htmlspecialchars( $val );
					break;

				case 'ObjectCycle':
					switch ( $val ) {
					case 'a': case 'p': case 'b':
						$val = self::msg( $tag, $val );
						break;
					default:
						$val = htmlspecialchars( $val );
						break;
					}
					break;
				case 'Copyrighted':
					switch ( $val ) {
					case 'True': case 'False':
						$val = self::msg( $tag, $val );
						break;
					}
					break;
				case 'Rating':
					if ( $val == '-1' ) {
						$val = self::msg( $tag, 'rejected' );
					} else {
						$val = self::formatNum( $val );
					}
					break;

				case 'LanguageCode':
					$lang = Language::fetchLanguageName( strtolower( $val ), $wgLang->getCode() );
					if ( $lang ) {
						$val = htmlspecialchars( $lang );
					} else {
						$val = htmlspecialchars( $val );
					}
					break;

				default:
					$val = self::formatNum( $val );
					break;
				}
			}
			// End formatting values, start flattening arrays.
			$vals = self::flattenArray( $vals, $type );

		}
		return $tags;
	}

	/**
	 * A function to collapse multivalued tags into a single value.
	 * This turns an array of (for example) authors into a bulleted list.
	 *
	 * This is public on the basis it might be useful outside of this class.
	 *
	 * @param array $vals array of values
	 * @param string $type Type of array (either lang, ul, ol).
	 * lang = language assoc array with keys being the lang code
	 * ul = unordered list, ol = ordered list
	 * type can also come from the '_type' member of $vals.
	 * @param $noHtml Boolean If to avoid returning anything resembling
	 * html. (Ugly hack for backwards compatibility with old mediawiki).
	 * @return String single value (in wiki-syntax).
	 */
	public static function flattenArray( $vals, $type = 'ul', $noHtml = false ) {
		if ( isset( $vals['_type'] ) ) {
			$type = $vals['_type'];
			unset( $vals['_type'] );
		}

		if ( !is_array( $vals ) ) {
			return $vals; // do nothing if not an array;
		}
		elseif ( count( $vals ) === 1 && $type !== 'lang' ) {
			return $vals[0];
		}
		elseif ( count( $vals ) === 0 ) {
			wfDebug( __METHOD__ . " metadata array with 0 elements!\n" );
			return ""; // paranoia. This should never happen
		}
		/* @todo FIXME: This should hide some of the list entries if there are
		 * say more than four. Especially if a field is translated into 20
		 * languages, we don't want to show them all by default
		 */
		else {
			global $wgContLang;
			switch ( $type ) {
			case 'lang':
				// Display default, followed by ContLang,
				// followed by the rest in no particular
				// order.

				// Todo: hide some items if really long list.

				$content = '';

				$cLang = $wgContLang->getCode();
				$defaultItem = false;
				$defaultLang = false;

				// If default is set, save it for later,
				// as we don't know if it's equal to
				// one of the lang codes. (In xmp
				// you specify the language for a
				// default property by having both
				// a default prop, and one in the language
				// that are identical)
				if ( isset( $vals['x-default'] ) ) {
					$defaultItem = $vals['x-default'];
					unset( $vals['x-default'] );
				}
				// Do contentLanguage.
				if ( isset( $vals[$cLang] ) ) {
					$isDefault = false;
					if ( $vals[$cLang] === $defaultItem ) {
						$defaultItem = false;
						$isDefault = true;
					}
					$content .= self::langItem(
						$vals[$cLang], $cLang,
						$isDefault, $noHtml );

					unset( $vals[$cLang] );
				}

				// Now do the rest.
				foreach ( $vals as $lang => $item ) {
					if ( $item === $defaultItem ) {
						$defaultLang = $lang;
						continue;
					}
					$content .= self::langItem( $item,
						$lang, false, $noHtml );
				}
				if ( $defaultItem !== false ) {
					$content = self::langItem( $defaultItem,
						$defaultLang, true, $noHtml ) .
						$content;
				}
				if ( $noHtml ) {
					return $content;
				}
				return '<ul class="metadata-langlist">' .
					$content .
					'</ul>';
			case 'ol':
				if ( $noHtml ) {
					return "\n#" . implode( "\n#", $vals );
				}
				return "<ol><li>" . implode( "</li>\n<li>", $vals ) . '</li></ol>';
			case 'ul':
			default:
				if ( $noHtml ) {
					return "\n*" . implode( "\n*", $vals );
				}
				return "<ul><li>" . implode( "</li>\n<li>", $vals ) . '</li></ul>';
			}
		}
	}

	/** Helper function for creating lists of translations.
	 *
	 * @param string $value value (this is not escaped)
	 * @param string $lang lang code of item or false
	 * @param $default Boolean if it is default value.
	 * @param $noHtml Boolean If to avoid html (for back-compat)
	 * @throws MWException
	 * @return string language item (Note: despite how this looks,
	 * this is treated as wikitext not html).
	 */
	private static function langItem( $value, $lang, $default = false, $noHtml = false ) {
		if ( $lang === false && $default === false ) {
			throw new MWException( '$lang and $default cannot both '
				. 'be false.' );
		}

		if ( $noHtml ) {
			$wrappedValue = $value;
		} else {
			$wrappedValue = '<span class="mw-metadata-lang-value">'
				. $value . '</span>';
		}

		if ( $lang === false ) {
			if ( $noHtml ) {
				return wfMessage( 'metadata-langitem-default',
					$wrappedValue )->text() . "\n\n";
			} /* else */
			return '<li class="mw-metadata-lang-default">'
				. wfMessage( 'metadata-langitem-default',
					$wrappedValue )->text()
				. "</li>\n";
		}

		$lowLang = strtolower( $lang );
		$langName = Language::fetchLanguageName( $lowLang );
		if ( $langName === '' ) {
			//try just the base language name. (aka en-US -> en ).
			list( $langPrefix ) = explode( '-', $lowLang, 2 );
			$langName = Language::fetchLanguageName( $langPrefix );
			if ( $langName === '' ) {
				// give up.
				$langName = $lang;
			}
		}
		// else we have a language specified

		if ( $noHtml ) {
			return '*' . wfMessage( 'metadata-langitem',
				$wrappedValue, $langName, $lang )->text();
		} /* else: */

		$item = '<li class="mw-metadata-lang-code-'
			. $lang;
		if ( $default ) {
			$item .= ' mw-metadata-lang-default';
		}
		$item .= '" lang="' . $lang . '">';
		$item .= wfMessage( 'metadata-langitem',
			$wrappedValue, $langName, $lang )->text();
		$item .= "</li>\n";
		return $item;
	}

	/**
	 * Convenience function for getFormattedData()
	 *
	 * @private
	 *
	 * @param string $tag the tag name to pass on
	 * @param string $val the value of the tag
	 * @param string $arg an argument to pass ($2)
	 * @param string $arg2 a 2nd argument to pass ($2)
	 * @return string A wfMessage of "exif-$tag-$val" in lower case
	 */
	static function msg( $tag, $val, $arg = null, $arg2 = null ) {
		global $wgContLang;

		if ( $val === '' ) {
			$val = 'value';
		}
		return wfMessage( $wgContLang->lc( "exif-$tag-$val" ), $arg, $arg2 )->text();
	}

	/**
	 * Format a number, convert numbers from fractions into floating point
	 * numbers, joins arrays of numbers with commas.
	 *
	 * @param $num Mixed: the value to format
	 * @param $round float|int|bool digits to round to or false.
	 * @return mixed A floating point number or whatever we were fed
	 */
	static function formatNum( $num, $round = false ) {
		global $wgLang;
		$m = array();
		if ( is_array( $num ) ) {
			$out = array();
			foreach ( $num as $number ) {
				$out[] = self::formatNum( $number );
			}
			return $wgLang->commaList( $out );
		}
		if ( preg_match( '/^(-?\d+)\/(\d+)$/', $num, $m ) ) {
			if ( $m[2] != 0 ) {
				$newNum = $m[1] / $m[2];
				if ( $round !== false ) {
					$newNum = round( $newNum, $round );
				}
			} else {
				$newNum = $num;
			}

			return $wgLang->formatNum( $newNum );
		} else {
			if ( is_numeric( $num ) && $round !== false ) {
				$num = round( $num, $round );
			}
			return $wgLang->formatNum( $num );
		}
	}

	/**
	 * Format a rational number, reducing fractions
	 *
	 * @private
	 *
	 * @param $num Mixed: the value to format
	 * @return mixed A floating point number or whatever we were fed
	 */
	static function formatFraction( $num ) {
		$m = array();
		if ( preg_match( '/^(-?\d+)\/(\d+)$/', $num, $m ) ) {
			$numerator = intval( $m[1] );
			$denominator = intval( $m[2] );
			$gcd = self::gcd( abs( $numerator ), $denominator );
			if ( $gcd != 0 ) {
				// 0 shouldn't happen! ;)
				return self::formatNum( $numerator / $gcd ) . '/' . self::formatNum( $denominator / $gcd );
			}
		}
		return self::formatNum( $num );
	}

	/**
	 * Calculate the greatest common divisor of two integers.
	 *
	 * @param $a Integer: Numerator
	 * @param $b Integer: Denominator
	 * @return int
	 * @private
	 */
	static function gcd( $a, $b ) {
		/*
			// http://en.wikipedia.org/wiki/Euclidean_algorithm
			// Recursive form would be:
			if( $b == 0 )
				return $a;
			else
				return gcd( $b, $a % $b );
		*/
		while ( $b != 0 ) {
			$remainder = $a % $b;

			// tail recursion...
			$a = $b;
			$b = $remainder;
		}
		return $a;
	}

	/**
	 * Fetch the human readable version of a news code.
	 * A news code is an 8 digit code. The first two
	 * digits are a general classification, so we just
	 * translate that.
	 *
	 * Note, leading 0's are significant, so this is
	 * a string, not an int.
	 *
	 * @param string $val The 8 digit news code.
	 * @return string The human readable form
	 */
	private static function convertNewsCode( $val ) {
		if ( !preg_match( '/^\d{8}$/D', $val ) ) {
			// Not a valid news code.
			return $val;
		}
		$cat = '';
		switch ( substr( $val, 0, 2 ) ) {
			case '01':
				$cat = 'ace';
				break;
			case '02':
				$cat = 'clj';
				break;
			case '03':
				$cat = 'dis';
				break;
			case '04':
				$cat = 'fin';
				break;
			case '05':
				$cat = 'edu';
				break;
			case '06':
				$cat = 'evn';
				break;
			case '07':
				$cat = 'hth';
				break;
			case '08':
				$cat = 'hum';
				break;
			case '09':
				$cat = 'lab';
				break;
			case '10':
				$cat = 'lif';
				break;
			case '11':
				$cat = 'pol';
				break;
			case '12':
				$cat = 'rel';
				break;
			case '13':
				$cat = 'sci';
				break;
			case '14':
				$cat = 'soi';
				break;
			case '15':
				$cat = 'spo';
				break;
			case '16':
				$cat = 'war';
				break;
			case '17':
				$cat = 'wea';
				break;
		}
		return $val;
	}

	/**
	 * Format a coordinate value, convert numbers from floating point
	 * into degree minute second representation.
	 *
	 * @param int $coord degrees, minutes and seconds
	 * @param string $type latitude or longitude (for if its a NWS or E)
	 * @return mixed A floating point number or whatever we were fed
	 */
	static function formatCoords( $coord, $type ) {
		$ref = '';
		if ( $coord < 0 ) {
			$nCoord = -$coord;
			if ( $type === 'latitude' ) {
				$ref = 'S';
			} elseif ( $type === 'longitude' ) {
				$ref = 'W';
			}
		} else {
			$nCoord = $coord;
			if ( $type === 'latitude' ) {
				$ref = 'N';
			} elseif ( $type === 'longitude' ) {
				$ref = 'E';
			}
		}

		$deg = floor( $nCoord );
		$min = floor( ( $nCoord - $deg ) * 60.0 );
		$sec = round( ( ( $nCoord - $deg ) - $min / 60 ) * 3600, 2 );

		$deg = self::formatNum( $deg );
		$min = self::formatNum( $min );
		$sec = self::formatNum( $sec );

		return wfMessage( 'exif-coordinate-format', $deg, $min, $sec, $ref, $coord )->text();
	}

	/**
	 * Format the contact info field into a single value.
	 *
	 * @param array $vals array with fields of the ContactInfo
	 *    struct defined in the IPTC4XMP spec. Or potentially
	 *    an array with one element that is a free form text
	 *    value from the older iptc iim 1:118 prop.
	 *
	 * This function might be called from
	 * JpegHandler::convertMetadataVersion which is why it is
	 * public.
	 *
	 * @return String of html-ish looking wikitext
	 */
	public static function collapseContactInfo( $vals ) {
		if ( !( isset( $vals['CiAdrExtadr'] )
			|| isset( $vals['CiAdrCity'] )
			|| isset( $vals['CiAdrCtry'] )
			|| isset( $vals['CiEmailWork'] )
			|| isset( $vals['CiTelWork'] )
			|| isset( $vals['CiAdrPcode'] )
			|| isset( $vals['CiAdrRegion'] )
			|| isset( $vals['CiUrlWork'] )
		) ) {
			// We don't have any sub-properties
			// This could happen if its using old
			// iptc that just had this as a free-form
			// text value.
			// Note: We run this through htmlspecialchars
			// partially to be consistent, and partially
			// because people often insert >, etc into
			// the metadata which should not be interpreted
			// but we still want to auto-link urls.
			foreach ( $vals as &$val ) {
				$val = htmlspecialchars( $val );
			}
			return self::flattenArray( $vals );
		} else {
			// We have a real ContactInfo field.
			// Its unclear if all these fields have to be
			// set, so assume they do not.
			$url = $tel = $street = $city = $country = '';
			$email = $postal = $region = '';

			// Also note, some of the class names this uses
			// are similar to those used by hCard. This is
			// mostly because they're sensible names. This
			// does not (and does not attempt to) output
			// stuff in the hCard microformat. However it
			// might output in the adr microformat.

			if ( isset( $vals['CiAdrExtadr'] ) ) {
				// Todo: This can potentially be multi-line.
				// Need to check how that works in XMP.
				$street = '<span class="extended-address">'
					. htmlspecialchars(
						$vals['CiAdrExtadr'] )
					. '</span>';
			}
			if ( isset( $vals['CiAdrCity'] ) ) {
				$city = '<span class="locality">'
					. htmlspecialchars( $vals['CiAdrCity'] )
					. '</span>';
			}
			if ( isset( $vals['CiAdrCtry'] ) ) {
				$country = '<span class="country-name">'
					. htmlspecialchars( $vals['CiAdrCtry'] )
					. '</span>';
			}
			if ( isset( $vals['CiEmailWork'] ) ) {
				$emails = array();
				// Have to split multiple emails at commas/new lines.
				$splitEmails = explode( "\n", $vals['CiEmailWork'] );
				foreach ( $splitEmails as $e1 ) {
					// Also split on comma
					foreach ( explode( ',', $e1 ) as $e2 ) {
						$finalEmail = trim( $e2 );
						if ( $finalEmail == ',' || $finalEmail == '' ) {
							continue;
						}
						if ( strpos( $finalEmail, '<' ) !== false ) {
							// Don't do fancy formatting to
							// "My name" <foo@bar.com> style stuff
							$emails[] = $finalEmail;
						} else {
							$emails[] = '[mailto:'
							. $finalEmail
							. ' <span class="email">'
							. $finalEmail
							. '</span>]';
						}
					}
				}
				$email = implode( ', ', $emails );
			}
			if ( isset( $vals['CiTelWork'] ) ) {
				$tel = '<span class="tel">'
					. htmlspecialchars( $vals['CiTelWork'] )
					. '</span>';
			}
			if ( isset( $vals['CiAdrPcode'] ) ) {
				$postal = '<span class="postal-code">'
					. htmlspecialchars(
						$vals['CiAdrPcode'] )
					. '</span>';
			}
			if ( isset( $vals['CiAdrRegion'] ) ) {
				// Note this is province/state.
				$region = '<span class="region">'
					. htmlspecialchars(
						$vals['CiAdrRegion'] )
					. '</span>';
			}
			if ( isset( $vals['CiUrlWork'] ) ) {
				$url = '<span class="url">'
					. htmlspecialchars( $vals['CiUrlWork'] )
					. '</span>';
			}
			return wfMessage( 'exif-contact-value', $email, $url,
				$street, $city, $region, $postal, $country,
				$tel )->text();
		}
	}
}

class UtfNormal {

	/**
	 * Load the basic composition data if necessary
	 * @private
	 */
	static function loadData() {
		if( !isset( self::$utfCombiningClass ) ) {
			require_once __DIR__ . '/UtfNormalData.inc';
		}
	}
	/**
	 * Returns true if the string is _definitely_ in NFC.
	 * Returns false if not or uncertain.
	 * @param string $string a UTF-8 string, altered on output to be valid UTF-8 safe for XML.
	 * @return bool
	 */
	static function quickIsNFCVerify( &$string ) {
		# Screen out some characters that eg won't be allowed in XML
		$string = preg_replace( '/[\x00-\x08\x0b\x0c\x0e-\x1f]/', UTF8_REPLACEMENT, $string );

		# ASCII is always valid NFC!
		# If we're only ever given plain ASCII, we can avoid the overhead
		# of initializing the decomposition tables by skipping out early.
		if( !preg_match( '/[\x80-\xff]/', $string ) ) return true;

		static $checkit = null, $tailBytes = null, $utfCheckOrCombining = null;
		if( !isset( $checkit ) ) {
			# Load/build some scary lookup tables...
			UtfNormal::loadData();

			$utfCheckOrCombining = array_merge( self::$utfCheckNFC, self::$utfCombiningClass );

			# Head bytes for sequences which we should do further validity checks
			$checkit = array_flip( array_map( 'chr',
					array( 0xc0, 0xc1, 0xe0, 0xed, 0xef,
						   0xf0, 0xf1, 0xf2, 0xf3, 0xf4, 0xf5, 0xf6, 0xf7,
						   0xf8, 0xf9, 0xfa, 0xfb, 0xfc, 0xfd, 0xfe, 0xff ) ) );

			# Each UTF-8 head byte is followed by a certain
			# number of tail bytes.
			$tailBytes = array();
			for( $n = 0; $n < 256; $n++ ) {
				if( $n < 0xc0 ) {
					$remaining = 0;
				} elseif( $n < 0xe0 ) {
					$remaining = 1;
				} elseif( $n < 0xf0 ) {
					$remaining = 2;
				} elseif( $n < 0xf8 ) {
					$remaining = 3;
				} elseif( $n < 0xfc ) {
					$remaining = 4;
				} elseif( $n < 0xfe ) {
					$remaining = 5;
				} else {
					$remaining = 0;
				}
				$tailBytes[chr($n)] = $remaining;
			}
		}

		# Chop the text into pure-ASCII and non-ASCII areas;
		# large ASCII parts can be handled much more quickly.
		# Don't chop up Unicode areas for punctuation, though,
		# that wastes energy.
		$matches = array();
		preg_match_all(
			'/([\x00-\x7f]+|[\x80-\xff][\x00-\x40\x5b-\x5f\x7b-\xff]*)/',
			$string, $matches );

		$looksNormal = true;
		$base = 0;
		$replace = array();
		foreach( $matches[1] as $str ) {
			$chunk = strlen( $str );

			if( $str[0] < "\x80" ) {
				# ASCII chunk: guaranteed to be valid UTF-8
				# and in normal form C, so skip over it.
				$base += $chunk;
				continue;
			}

			# We'll have to examine the chunk byte by byte to ensure
			# that it consists of valid UTF-8 sequences, and to see
			# if any of them might not be normalized.
			#
			# Since PHP is not the fastest language on earth, some of
			# this code is a little ugly with inner loop optimizations.

			$head = '';
			$len = $chunk + 1; # Counting down is faster. I'm *so* sorry.

			for( $i = -1; --$len; ) {
				$remaining = $tailBytes[$c = $str[++$i]];
				if( $remaining ) {
					# UTF-8 head byte!
					$sequence = $head = $c;
					do {
						# Look for the defined number of tail bytes...
						if( --$len && ( $c = $str[++$i] ) >= "\x80" && $c < "\xc0" ) {
							# Legal tail bytes are nice.
							$sequence .= $c;
						} else {
							if( 0 == $len ) {
								# Premature end of string!
								# Drop a replacement character into output to
								# represent the invalid UTF-8 sequence.
								$replace[] = array( UTF8_REPLACEMENT,
													$base + $i + 1 - strlen( $sequence ),
													strlen( $sequence ) );
								break 2;
							} else {
								# Illegal tail byte; abandon the sequence.
								$replace[] = array( UTF8_REPLACEMENT,
													$base + $i - strlen( $sequence ),
													strlen( $sequence ) );
								# Back up and reprocess this byte; it may itself
								# be a legal ASCII or UTF-8 sequence head.
								--$i;
								++$len;
								continue 2;
							}
						}
					} while( --$remaining );

					if( isset( $checkit[$head] ) ) {
						# Do some more detailed validity checks, for
						# invalid characters and illegal sequences.
						if( $head == "\xed" ) {
							# 0xed is relatively frequent in Korean, which
							# abuts the surrogate area, so we're doing
							# this check separately to speed things up.

							if( $sequence >= UTF8_SURROGATE_FIRST ) {
								# Surrogates are legal only in UTF-16 code.
								# They are totally forbidden here in UTF-8
								# utopia.
								$replace[] = array( UTF8_REPLACEMENT,
								             $base + $i + 1 - strlen( $sequence ),
								             strlen( $sequence ) );
								$head = '';
								continue;
							}
						} else {
							# Slower, but rarer checks...
							$n = ord( $head );
							if(
								# "Overlong sequences" are those that are syntactically
								# correct but use more UTF-8 bytes than are necessary to
								# encode a character. Naïve string comparisons can be
								# tricked into failing to see a match for an ASCII
								# character, for instance, which can be a security hole
								# if blacklist checks are being used.
							       ($n  < 0xc2 && $sequence <= UTF8_OVERLONG_A)
								|| ($n == 0xe0 && $sequence <= UTF8_OVERLONG_B)
								|| ($n == 0xf0 && $sequence <= UTF8_OVERLONG_C)

								# U+FFFE and U+FFFF are explicitly forbidden in Unicode.
								|| ($n == 0xef &&
									   ($sequence == UTF8_FFFE)
									|| ($sequence == UTF8_FFFF) )

								# Unicode has been limited to 21 bits; longer
								# sequences are not allowed.
								|| ($n >= 0xf0 && $sequence > UTF8_MAX) ) {

								$replace[] = array( UTF8_REPLACEMENT,
								                    $base + $i + 1 - strlen( $sequence ),
								                    strlen( $sequence ) );
								$head = '';
								continue;
							}
						}
					}

					if( isset( $utfCheckOrCombining[$sequence] ) ) {
						# If it's NO or MAYBE, we'll have to rip
						# the string apart and put it back together.
						# That's going to be mighty slow.
						$looksNormal = false;
					}

					# The sequence is legal!
					$head = '';
				} elseif( $c < "\x80" ) {
					# ASCII byte.
					$head = '';
				} elseif( $c < "\xc0" ) {
					# Illegal tail bytes
					if( $head == '' ) {
						# Out of the blue!
						$replace[] = array( UTF8_REPLACEMENT, $base + $i, 1 );
					} else {
						# Don't add if we're continuing a broken sequence;
						# we already put a replacement character when we looked
						# at the broken sequence.
						$replace[] = array( '', $base + $i, 1 );
					}
				} else {
					# Miscellaneous freaks.
					$replace[] = array( UTF8_REPLACEMENT, $base + $i, 1 );
					$head = '';
				}
			}
			$base += $chunk;
		}
		if( count( $replace ) ) {
			# There were illegal UTF-8 sequences we need to fix up.
			$out = '';
			$last = 0;
			foreach( $replace as $rep ) {
				list( $replacement, $start, $length ) = $rep;
				if( $last < $start ) {
					$out .= substr( $string, $last, $start - $last );
				}
				$out .= $replacement;
				$last = $start + $length;
			}
			if( $last < strlen( $string ) ) {
				$out .= substr( $string, $last );
			}
			$string = $out;
		}
		return $looksNormal;
	}
}
