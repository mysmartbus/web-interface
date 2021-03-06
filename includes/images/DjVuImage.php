<?php
/**
 * Module defining helper functions for working with DjVu image files.
 *
 * This file comes from MediaWiki 1.22.1 with some modifications for use with Kraven
 * Filename was not changed.
 *
 * Things modified:
 *   Converted wfDebug() lines to PHP code comments or removed them
 *   Removed function retrieveMetaData() since it was easier than pulling in
 *      a bunch of functions from MediaWikis' GlobaFunctions.php file and 
 *      massaging them to work with Kraven
 *
 * Last modified for Kraven on 2014-11-11
**/

/**
 * DjVu image handler.
 *
 * Copyright © 2006 Brion Vibber <brion@pobox.com>
 * http://www.mediawiki.org/
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Media
 */

/**
 * Support for detecting/validating DjVu image files and getting
 * some basic file metadata (resolution etc)
 *
 * File format docs are available in source package for DjVuLibre:
 * http://djvulibre.djvuzone.org/
 *
 * @ingroup Media
 */
class DjVuImage {
	/**
	 * Constructor
	 *
	 * @param string $filename The DjVu file name.
	 */
	function __construct( $filename ) {
		$this->mFilename = $filename;
	}

	/**
	 * @const DJVUTXT_MEMORY_LIMIT Memory limit for the DjVu description software
	 */
	const DJVUTXT_MEMORY_LIMIT = 300000;

	/**
	 * Check if the given file is indeed a valid DjVu image file
	 * @return bool
	 */
	public function isValid() {
		$info = $this->getInfo();
		return $info !== false;
	}

	/**
	 * Return data in the style of getimagesize()
	 * @return array or false on failure
	 */
	public function getImageSize() {
		$data = $this->getInfo();

		if ( $data !== false ) {
			$width = $data['width'];
			$height = $data['height'];

			return array( $width, $height, 'DjVu', "width=\"$width\" height=\"$height\"" );
		}
		return false;
	}

	// ---------

	/**
	 * For debugging; dump the IFF chunk structure
	 */
	function dump() {
		$file = fopen( $this->mFilename, 'rb' );
		$header = fread( $file, 12 );
		// @todo FIXME: Would be good to replace this extract() call with something that explicitly initializes local variables.
		extract( unpack( 'a4magic/a4chunk/NchunkLength', $header ) );
		echo "$chunk $chunkLength\n";
		$this->dumpForm( $file, $chunkLength, 1 );
		fclose( $file );
	}

	private function dumpForm( $file, $length, $indent ) {
		$start = ftell( $file );
		$secondary = fread( $file, 4 );
		echo str_repeat( ' ', $indent * 4 ) . "($secondary)\n";
		while ( ftell( $file ) - $start < $length ) {
			$chunkHeader = fread( $file, 8 );
			if ( $chunkHeader == '' ) {
				break;
			}
			// @todo FIXME: Would be good to replace this extract() call with something that explicitly initializes local variables.
			extract( unpack( 'a4chunk/NchunkLength', $chunkHeader ) );
			echo str_repeat( ' ', $indent * 4 ) . "$chunk $chunkLength\n";

			if ( $chunk == 'FORM' ) {
				$this->dumpForm( $file, $chunkLength, $indent + 1 );
			} else {
				fseek( $file, $chunkLength, SEEK_CUR );
				if ( $chunkLength & 1 == 1 ) {
					// Padding byte between chunks
					fseek( $file, 1, SEEK_CUR );
				}
			}
		}
	}

	function getInfo() {
		$file = fopen( $this->mFilename, 'rb' );
		if ( $file === false ) {
			// Missing or failed file read
			return false;
		}

		$header = fread( $file, 16 );
		$info = false;

		if ( strlen( $header ) >= 16 ) {
			// @todo FIXME: Would be good to replace this extract() call with something that explicitly initializes local variables.
			extract( unpack( 'a4magic/a4form/NformLength/a4subtype', $header ) );

			if ( $magic != 'AT&T' ) {
				// Not a DjVu file\n" );
			} elseif ( $subtype == 'DJVU' ) {
				// Single-page document
				$info = $this->getPageInfo( $file, $formLength );
			} elseif ( $subtype == 'DJVM' ) {
				// Multi-page document
				$info = $this->getMultiPageInfo( $file, $formLength );
			} else {
				// Unrecognized DJVU file type '$formType'
			}
		}
		fclose( $file );
		return $info;
	}

	private function readChunk( $file ) {
		$header = fread( $file, 8 );
		if ( strlen( $header ) < 8 ) {
			return array( false, 0 );
		} else {
			// @todo FIXME: Would be good to replace this extract() call with something that explicitly initializes local variables.
			extract( unpack( 'a4chunk/Nlength', $header ) );
			return array( $chunk, $length );
		}
	}

	private function skipChunk( $file, $chunkLength ) {
		fseek( $file, $chunkLength, SEEK_CUR );

		if ( $chunkLength & 0x01 == 1 && !feof( $file ) ) {
			// padding byte
			fseek( $file, 1, SEEK_CUR );
		}
	}

	private function getMultiPageInfo( $file, $formLength ) {
		// For now, we'll just look for the first page in the file
		// and report its information, hoping others are the same size.
		$start = ftell( $file );
		do {
			list( $chunk, $length ) = $this->readChunk( $file );
			if ( !$chunk ) {
				break;
			}

			if ( $chunk == 'FORM' ) {
				$subtype = fread( $file, 4 );
				if ( $subtype == 'DJVU' ) {
					// Found first subpage
					return $this->getPageInfo( $file, $length );
				}
				$this->skipChunk( $file, $length - 4 );
			} else {
				// Skipping '$chunk' chunk
				$this->skipChunk( $file, $length );
			}
		} while ( $length != 0 && !feof( $file ) && ftell( $file ) - $start < $formLength );

		// Multi-page DJVU file contained no pages
		return false;
	}

	private function getPageInfo( $file, $formLength ) {
		list( $chunk, $length ) = $this->readChunk( $file );
		if ( $chunk != 'INFO' ) {
			// Expected INFO chunk, got '$chunk'
			return false;
		}

		if ( $length < 9 ) {
			// INFO should be 9 or 10 bytes, found $length bytes
			return false;
		}
		$data = fread( $file, $length );
		if ( strlen( $data ) < $length ) {
			// INFO chunk cut off
			return false;
		}

		// @todo FIXME: Would be good to replace this extract() call with something that explicitly initializes local variables.
		extract( unpack(
			'nwidth/' .
			'nheight/' .
			'Cminor/' .
			'Cmajor/' .
			'vresolution/' .
			'Cgamma', $data ) );
		# Newer files have rotation info in byte 10, but we don't use it yet.

		return array(
			'width' => $width,
			'height' => $height,
			'version' => "$major.$minor",
			'resolution' => $resolution,
			'gamma' => $gamma / 10.0 );
	}

	function pageTextCallback( $matches ) {
		# Get rid of invalid UTF-8, strip control characters
		return '<PAGE value="' . htmlspecialchars( UtfNormal::cleanUp( $matches[1] ) ) . '" />';
	}

	/**
	 * Hack to temporarily work around djvutoxml bug
	 * @return bool|string
	 */
	function convertDumpToXML( $dump ) {
		if ( strval( $dump ) == '' ) {
			return false;
		}

		$xml = <<<EOT
<?xml version="1.0" ?>
<!DOCTYPE DjVuXML PUBLIC "-//W3C//DTD DjVuXML 1.1//EN" "pubtext/DjVuXML-s.dtd">
<DjVuXML>
<HEAD></HEAD>
<BODY>
EOT;

		$dump = str_replace( "\r", '', $dump );
		$line = strtok( $dump, "\n" );
		$m = false;
		$good = false;
		if ( preg_match( '/^( *)FORM:DJVU/', $line, $m ) ) {
			# Single-page
			if ( $this->parseFormDjvu( $line, $xml ) ) {
				$good = true;
			} else {
				return false;
			}
		} elseif ( preg_match( '/^( *)FORM:DJVM/', $line, $m ) ) {
			# Multi-page
			$parentLevel = strlen( $m[1] );
			# Find DIRM
			$line = strtok( "\n" );
			while ( $line !== false ) {
				$childLevel = strspn( $line, ' ' );
				if ( $childLevel <= $parentLevel ) {
					# End of chunk
					break;
				}

				if ( preg_match( '/^ *DIRM.*indirect/', $line ) ) {
					// Indirect multi-page DjVu document, bad for server!
					return false;
				}
				if ( preg_match( '/^ *FORM:DJVU/', $line ) ) {
					# Found page
					if ( $this->parseFormDjvu( $line, $xml ) ) {
						$good = true;
					} else {
						return false;
					}
				}
				$line = strtok( "\n" );
			}
		}
		if ( !$good ) {
			return false;
		}

		$xml .= "</BODY>\n</DjVuXML>\n";
		return $xml;
	}

	function parseFormDjvu( $line, &$xml ) {
		$parentLevel = strspn( $line, ' ' );
		$line = strtok( "\n" );

		# Find INFO
		while ( $line !== false ) {
			$childLevel = strspn( $line, ' ' );
			if ( $childLevel <= $parentLevel ) {
				# End of chunk
				break;
			}

			if ( preg_match( '/^ *INFO *\[\d*\] *DjVu *(\d+)x(\d+), *\w*, *(\d+) *dpi, *gamma=([0-9.-]+)/', $line, $m ) ) {
				$xml .= Xml::tags( 'OBJECT',
					array(
						#'data' => '',
						#'type' => 'image/x.djvu',
						'height' => $m[2],
						'width' => $m[1],
						#'usemap' => '',
					),
					"\n" .
					Xml::element( 'PARAM', array( 'name' => 'DPI', 'value' => $m[3] ) ) . "\n" .
					Xml::element( 'PARAM', array( 'name' => 'GAMMA', 'value' => $m[4] ) ) . "\n"
				) . "\n";
				return true;
			}
			$line = strtok( "\n" );
		}
		# Not found
		return false;
	}
}
