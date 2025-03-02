<?php
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //
/**
 * The uploader class of media files
 *
 * @copyright	http://www.impresscms.org/ The ImpressCMS Project
 * @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
 */
/*!
 Example

 $allowed_mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png');
 $maxfilesize = 50000;
 $maxfilewidth = 120;
 $maxfileheight = 120;
 $uploader = new \ImpressCMS\Core\File\MediaUploadHandler('/home/httpdocs/uploads', $allowed_mimetypes, $maxfilesize, $maxfilewidth, $maxfileheight);
 if($uploader->fetchMedia($_POST['uploade_file_name'])) {
	 if(!$uploader->upload()) {
		 echo $uploader->getErrors();
	 } else {
		 echo '<h4>File uploaded successfully!</h4>'
		 echo 'Saved as: ' . $uploader->getSavedFileName() . '<br />';
		 echo 'Full path: ' . $uploader->getSavedDestination();
	 }
 } else {
	 echo $uploader->getErrors();
 }
 */

namespace ImpressCMS\Core\File;

use League\MimeTypeDetection\GeneratedExtensionToMimeTypeMap;

/**
 * Upload Media files
 * Example of usage:
 * <code>
 * $allowed_mimetypes = array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png');
 * $maxfilesize = 50000;
 * $maxfilewidth = 120;
 * $maxfileheight = 120;
 * $uploader = new \ImpressCMS\Core\File\MediaUploadHandler('/home/httpdocs/uploads', $allowed_mimetypes, $maxfilesize, $maxfilewidth, $maxfileheight);
 * if($uploader->fetchMedia($_POST['uploade_file_name'])) {
 *     if(!$uploader->upload()) {
 *         echo $uploader->getErrors();
 *     } else {
 *         echo '<h4>File uploaded successfully!</h4>'
 *         echo 'Saved as: ' . $uploader->getSavedFileName() . '<br />';
 *         echo 'Full path: ' . $uploader->getSavedDestination();
 *     }
 * } else {
 *   echo $uploader->getErrors();
 * }
 * </code>
 *
 * @package	ICMS\File
 * @author      Kazumi Ono     <onokazu@xoops.org>
 * @author      phppp
 * @copyright	copyright (c) 2000-2007 XOOPS.org
 */
class MediaUploader {

	/**
	 * @var bool Flag indicating if unrecognized mimetypes should be allowed (use with precaution ! may lead to security issues )
	 */
	private $allowUnknownTypes = false;

	/** @var string Name of the file to upload */
	private $mediaName;

	/** @var string Type of the file to upload */
	private $mediaType;

	/** @var string Size of the file to upload */
	private $mediaSize;

	/** @var string Temp name after the file was uploaded */
	private $mediaTmpName;

	/** @var string Was there an error in media type or name */
	private $mediaError;

	/** @var string Real typ after upload */
	private $mediaRealType = '';

	/** @var string Upload directory */
	private $uploadDir = '';

	/** @var array Allowed Mime Types */
	private $allowedMimeTypes = array();

	/** @var string Denied Mime types */
	private $deniedMimeTypes = array(
		"application/x-httpd-php"
		);

	/** @var int Maximum Filesize */
	private $maxFileSize = 0;

	/** @var string Maximum witdth */
	private $maxWidth;

	/** @var string Maximum height */
	private $maxHeight;

	/** @var string Target Filename */
	private $targetFileName;

	/** @var string Prefix (for filename?) */
	private $prefix;

	/** @var array The errors that have occurred */
	private $errors = array();

	/** @var string Saved Destination after upload */
	private $savedDestination;

	/** @var string Saved Filename after upload */
	private $savedFileName;

	/** @var bool Would you like to check the image type? */
	private $checkImageType = true;

	/** @var array */
	private $extensionsToBeSanitized = array(
		'php',
		'phtml',
		'phtm',
		'php3',
		'php4',
		'cgi',
		'pl',
		'asp',
		'php5'
	);

	// extensions needed image check (anti-IE Content-Type XSS)
	/** @var array */
	private $imageExtensions = array(
		1 => 'gif',
		2 => 'jpg',
		3 => 'png',
		4 => 'swf',
		5 => 'psd',
		6 => 'bmp',
		7 => 'tif',
		8 => 'tif',
		9 => 'jpc',
		10 => 'jp2',
		11 => 'jpx',
		12 => 'jb2',
		13 => 'swc',
		14 => 'iff',
		15 => 'wbmp',
		16 => 'xbm'
	);

	/**
	 * Constructor
	 *
	 * @param   string  $uploadDir
	 * @param   array   $allowedMimeTypes
	 * @param   int     $maxFileSize
	 * @param   int     $maxWidth
	 * @param   int     $maxHeight
	 */
	public function __construct($uploadDir, $allowedMimeTypes, $maxFileSize = 0, $maxWidth = null, $maxHeight = null) {
		if (is_array($allowedMimeTypes)) {
			$this->allowedMimeTypes = & $allowedMimeTypes;
		}
		$this->uploadDir = $uploadDir;
		$this->maxFileSize = (int) $maxFileSize;
		if (isset($maxWidth)) {
			$this->maxWidth = (int) $maxWidth;
		}
		if (isset($maxHeight)) {
			$this->maxHeight = (int) $maxHeight;
		}

		icms_loadLanguageFile('core', 'uploader');
	}

	/**
	 * Do same as fetchMedia but from URL
	 *
	 * @param string $url
	 * @return bool
	 */
		public function fetchFromURL($url) {
			//header('Content-Type: text/plain');
			if (strpos($url, 'data:') === 0) {
				$fp   = fopen($url, 'r');
				$meta = stream_get_meta_data($fp);
				$content = stream_get_contents($fp);
				$headers = array(
					'content-type' => $meta['mediatype'] ?? 'text/plain',
					'base64' => $meta['base64'] ?? true,
					'content-length' => strlen($content),
					'charset' => $meta['charset'] ?? 'US-ASCII',
				);
				fclose($fp);
			} else {
				if (!function_exists('curl_init')) {
					self::setErrors('cURL not found!');
					return false;
				}
				$ch = curl_init($url);
				curl_setopt($ch, CURLOPT_HEADER, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_VERBOSE, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				$data = curl_exec($ch);

				$hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
				$headers = mb_substr($data, 0, $hsize);
				$content = mb_substr($data, $hsize);

				curl_close($ch);

				$hdrs = array();
				foreach (explode("\r\n", $headers) as $line) {
					$line = trim($line);
					$i = strpos($line, ':');
					if (!$i) {
						if (strpos($line, 'HTTP/') === 0) {
												   $hdrs['http'] = explode(' ', substr($line, 5));
						} elseif (trim($line) !== '') {
													$hdrs['unknown-header'][] = $line;
						}
					} else {
						$name = strtolower(trim(substr($line, 0, $i)));
						$value = trim(substr($line, $i + 1));
						if ($name == 'content-disposition') {
							preg_match_all('/([^;]+);\ *filename=(".+"|.+)/Ui', $value, $matches, PREG_SET_ORDER);
							if (mb_strpos($matches[0][2], '"') === 0) {
								$matches[0][2] = mb_substr($matches[0][2], 1, -1);
							}
							$hdrs[$name] = array(
								'type' => $matches[0][1],
								'filename' => $matches[0][2],
							);
						} else {
							$hdrs[$name] = $value;
						}
						unset($name, $value);
					}
				}
				$headers = $hdrs;
				unset($hdrs);
				if (!isset($headers['http'][1]) || ((int)$headers['http'][1] !== 200)) {
									return false;
				}
				if (!isset($headers['content-type'])) {
									$headers['content-type'] = 'application/octet-stream';
				}
			}
			if (is_string($headers['content-disposition']['filename']) && $headers['content-disposition']['filename'] !== '') {
				$this->mediaName = $headers['content-disposition']['filename'];
				$ext = pathinfo($this->mediaName, PATHINFO_EXTENSION);
			} else {
				$this->mediaName = preg_replace('([^A-Za-z0-9])', '_', basename(parse_url($url, PHP_URL_PATH)));
				if (strpos($this->mediaName, '.') === false) {
					$ext = substr($headers['content-type'], strpos($headers['content-type'], '/') + 1);
					$this->mediaName .= '.';
				} else {
					$ext = pathinfo($this->mediaName, PATHINFO_EXTENSION);
				}
			}
			$this->mediaType = $headers['content-type'];
			$this->mediaSize = (int) $headers['content-length'];
			$this->mediaTmpName = tempnam(sys_get_temp_dir(), 'icms_media');
			$this->mediaError = 0;
			$this->mediaRealType = (new GeneratedExtensionToMimeTypeMap())->lookupMimeType($ext) ?: $this->mediaType;
			$fp = fopen($this->mediaTmpName, 'w+');
			fwrite($fp, $content);
			fclose($fp);
			$this->errors = array();
			if ($this->mediaSize < 0) {
				self::setErrors(_ER_UP_INVALIDFILESIZE);
				return false;
			}
			if (!is_string($this->mediaName) || $this->mediaName === '') {
				self::setErrors(_ER_UP_FILENAMEEMPTY);
				return false;
			}
			if ($this->mediaError > 0) {
				self::setErrors(sprintf(_ER_UP_ERROROCCURRED, $this->mediaError));
				return false;
			}
			return true;
		}

	/**
	 * Fetch the uploaded file
	 * @param   string  $media_name Name of the file field
	 * @param   int     $index      Index of the file (if more than one uploaded under that name)
	 * @return  bool
	 */
	public function fetchMedia($media_name, $index = null) {
		if (!isset($_FILES[$media_name])) {
			self::setErrors(_ER_UP_FILENOTFOUND);
			return false;
		} elseif (is_array($_FILES[$media_name]['name']) && isset($index)) {
			$index = (int) ($index);
			$this->mediaName = $_FILES[$media_name]['name'][$index];
			$this->mediaType = $_FILES[$media_name]['type'][$index];
			$this->mediaSize = $_FILES[$media_name]['size'][$index];
			$this->mediaTmpName = $_FILES[$media_name]['tmp_name'][$index];
			$this->mediaError = !empty($_FILES[$media_name]['error'][$index])?$_FILES[$media_name]['error'][$index]:0;
		} else {
			if (!is_array($media_name)) {
				$media_name = &$_FILES[$media_name];
			}
			$this->mediaName = $media_name['name'];
			$this->mediaType = $media_name['type'];
			$this->mediaSize = $media_name['size'];
			$this->mediaTmpName = $media_name['tmp_name'];
			$this->mediaError = !empty($media_name['error']) ? $media_name['error'] : 0;
		}
		if (($ext = strrpos($this->mediaName, '.')) !== false) {
			$ext = strtolower(substr($this->mediaName, $ext + 1));
			if ($detectedMimeType = (new GeneratedExtensionToMimeTypeMap())->lookupMimeType($ext)) {
				$this->mediaRealType = $detectedMimeType;
			}
		}
		$this->errors = array();
		if ((int)($this->mediaSize) < 0) {
			self::setErrors(_ER_UP_INVALIDFILESIZE);
			return false;
		}
		if ($this->mediaName == '') {
			self::setErrors(_ER_UP_FILENAMEEMPTY);
			return false;
		}
		if (!$this->mediaTmpName || $this->mediaTmpName == 'none' || !is_uploaded_file($this->mediaTmpName)) {
			self::setErrors($this->getUploadErrorText($media_name['error']));
			return false;
		}
		if ($this->mediaError > 0) {
			self::setErrors(sprintf(_ER_UP_ERROROCCURRED, $this->mediaError));
			return false;
		}
		return true;
	}

	/**
	 * Get Text messages for POST upload errors
	 *
	 * @param int $err error number
	 * @return string error message
	 */
	private function getUploadErrorText($err) {
		switch ($err) {
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$err = _ER_UP_INVALIDFILESIZE;
				break;
			case UPLOAD_ERR_PARTIAL:
				$err = _ER_UP_PARTIALLY;
				break;
			case UPLOAD_ERR_NO_FILE:
				$err = _ER_UP_NOFILEUPLOADED;
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				$err = _ER_UP_NO_TMP_DIR;
				break;
			case UPLOAD_ERR_CANT_WRITE:
				$err = _ER_UP_CANT_WRITE;
				break;
			case UPLOAD_ERR_EXTENSION:
				$err = _ER_UP_NOFILEUPLOADED;
				break;
		}
		return $err;
	}

	/**
	 * Set the target filename
	 * @param   string  $value
	 */
	public function setTargetFileName($value) {
		$this->targetFileName = (string) (trim($value));
	}

	/**
	 * Set the prefix
	 * @param    string  $value
	 * @param    bool    $unique
	 */
	public function setPrefix($value, $unique = true) {
		if (isset($value) && $value !== '') {
			if (!isset($unique) || (isset($unique) && $unique !== true)) {
				$this->prefix = (string) trim($value);
			} elseif (isset($unique) && $unique == true) {
				$this->prefix = (string) (trim($value)) . '_' . uniqid(rand(0, 32767), true);
			}
		} elseif (!isset($value) || $value == '') {
			if (!isset($unique) || (isset($unique) && $unique !== true)) {
				$this->prefix = '';
			} elseif (isset($unique) && $unique == true) {
				$this->prefix = uniqid(rand(0, 32767), true);
			}
		}
	}

	/**
	 * Get the uploaded filename
	 * @return  string
	 */
	public function getMediaName() {
		return $this->mediaName;
	}

	/**
	 * Get the type of the uploaded file
	 * @return  string
	 */
	public function getMediaType() {
		return $this->mediaType;
	}

	/**
	 * Get the size of the uploaded file
	 * @return  int
	 */
	public function getMediaSize() {
		return $this->mediaSize;
	}

	/**
	 * Get the temporary name that the uploaded file was stored under
	 * @return  string
	 */
	public function getMediaTmpName() {
		return $this->mediaTmpName;
	}

	/**
	 * Get the saved filename
	 * @return  string
	 */
	public function getSavedFileName() {
		return $this->savedFileName;
	}

	/**
	 * Get the destination the file is saved to
	 * @return  string
	 */
	public function getSavedDestination() {
		return $this->savedDestination;
	}

	/**
	 * Check the file and copy it to the destination
	 * @return  bool
	 */
	public function upload($chmod = 0644) {
		if ($this->uploadDir == '') {
			self::setErrors(_ER_UP_UPLOADDIRNOTSET);
			return false;
		}
		if (!is_dir($this->uploadDir)) {
			self::setErrors(sprintf(_ER_UP_FAILEDOPENDIR, $this->uploadDir));
			return false;
		}
		if (!is_writeable($this->uploadDir)) {
			self::setErrors(sprintf(_ER_UP_FAILEDOPENDIRWRITE, $this->uploadDir));
			return false;
		}
		self::sanitizeMultipleExtensions();

		if (!self::checkMaxFileSize()) {
			return false;
		}
		if (!self::checkMimeType()) {
			return false;
		}
		if (!self::checkImageType()) {
			return false;
		}
		if (!self::checkMaxWidth()) {
			return false;
		}
		if (!self::checkMaxHeight()) {
			return false;
		}
		if (count($this->errors) > 0) {
			return false;
		}
		return self::_copyFile($chmod);
	}

	/**
	 * Copy the file to its destination
	 * @return  bool
	 */
	private function _copyFile($chmod) {
		$matched = array();
		if (!preg_match("/\.([a-zA-Z0-9]+)$/", $this->mediaName, $matched)) {
			self::setErrors(sprintf(_ER_UP_INVALIDFILENAME, $this->mediaName));
			return false;
		}
		if (isset($this->targetFileName)) {
			$this->savedFileName = $this->targetFileName;
		} elseif (isset($this->prefix) && $this->prefix !== '') {
			$this->savedFileName = $this->prefix . '.' . strtolower($matched[1]);
		} else {
			$this->savedFileName = strtolower($this->mediaName);
		}
		$this->savedDestination = $this->uploadDir . '/' . $this->savedFileName;
		if (is_uploaded_file($this->mediaTmpName)) {
			if (!move_uploaded_file($this->mediaTmpName, $this->savedDestination)) {
				self::setErrors(sprintf(_ER_UP_FAILEDSAVEFILE, $this->savedDestination));
				return false;
			}
		} else {
			if (!rename($this->mediaTmpName, $this->savedDestination)) {
				self::setErrors(sprintf(_ER_UP_FAILEDSAVEFILE, $this->savedDestination));
				return false;
			}
		}
		// Check IE XSS before returning success
		$ext = strtolower(substr(strrchr($this->savedDestination, '.'), 1));
		if (in_array($ext, $this->imageExtensions)) {
			$info = @ getimagesize($this->savedDestination);
			if ($info === false || $this->imageExtensions[(int) $info[2]] != $ext) {
				self::setErrors(_ER_UP_SUSPICIOUSREFUSED);
				@ unlink($this->savedDestination);
				return false;
			}
		}
		@ chmod($this->savedDestination, $chmod);
		return true;
	}

	/**
	 * Is the file the right size?
	 * @return  bool
	 */
	public function checkMaxFileSize() {
		if (!isset($this->maxFileSize) || $this->maxFileSize < 1) {
			return true;
		}
		if ($this->mediaSize > $this->maxFileSize) {
			self::setErrors(sprintf(_ER_UP_FILESIZETOOLARGE, $this->maxFileSize, $this->mediaSize));
			return false;
		}
		return true;
	}

	/**
	 * Is the picture the right width?
	 * @return  bool
	 */
	public function checkMaxWidth()
	{
		if (!isset($this->maxWidth) || $this->maxWidth < 1) {
			return true;
		}
		if (false !== $dimension = getimagesize($this->mediaTmpName)) {
			if ($dimension[0] > $this->maxWidth) {
				self::setErrors(sprintf(_ER_UP_FILEWIDTHTOOLARGE, $this->maxWidth, $dimension[0]));
				return false;
			}
		} else {
			trigger_error(sprintf(_ER_UP_FAILEDFETCHIMAGESIZE, $this->mediaTmpName), E_USER_WARNING);
		}
		return true;
	}

	/**
	 * Is the picture the right height?
	 *
	 * @return  bool
	 */
	public function checkMaxHeight()
	{
		if (!isset($this->maxHeight) || $this->maxHeight < 1) {
			return true;
		}
		if (false !== $dimension = getimagesize($this->mediaTmpName)) {
			if ($dimension[1] > $this->maxHeight) {
				self::setErrors(sprintf(_ER_UP_FILEHEIGHTTOOLARGE, $this->maxHeight, $dimension[1]));
				return false;
			}
		} else {
			trigger_error(sprintf(_ER_UP_FAILEDFETCHIMAGESIZE, $this->mediaTmpName), E_USER_WARNING);
		}
		return true;
	}

	/**
	 * Check whether or not the uploaded file type is allowed
	 * @return  bool
	 */
	public function checkMimeType() {
		if (empty($this->mediaRealType) && empty($this->allowUnknownTypes)) {
			self::setErrors(_ER_UP_UNKNOWNFILETYPEREJECTED);
			return false;
		}
		if ((!empty($this->allowedMimeTypes) && !in_array($this->mediaRealType, $this->allowedMimeTypes, false))
				|| (!empty($this->deniedMimeTypes) && in_array($this->mediaRealType, $this->deniedMimeTypes, false))
		) {
			self::setErrors(sprintf(_ER_UP_MIMETYPENOTALLOWED, $this->mediaType));
			return false;
		}
		return true;
	}

	/**
	 * Check whether or not the uploaded image type is valid
	 * @return  bool
	 */
	public function checkImageType() {
		if (empty($this->checkImageType)) {
			return true;
		}
		if (('image' === substr($this->mediaType, 0, strpos($this->mediaType, '/')))
				|| (!empty($this->mediaRealType) && 'image' === substr($this->mediaRealType, 0, strpos($this->mediaRealType, '/')))
			) {
			if (!($info = @ getimagesize($this->mediaTmpName))) {
				self::setErrors(_ER_UP_INVALIDIMAGEFILE);
				return false;
			}
		}
		return true;
	}

	/**
	 * Sanitize executable filename with multiple extensions
	 */
	public function sanitizeMultipleExtensions() {
		if (empty($this->extensionsToBeSanitized)) {
			return;
		}
		$patterns = array();
		$replaces = array();
		foreach ($this->extensionsToBeSanitized as $ext) {
			$patterns[] = "/\." . preg_quote($ext, '/') . "\./i";
			$replaces[] = "_" . $ext . ".";
		}
		$this->mediaName = preg_replace($patterns, $replaces, $this->mediaName);
	}

	/**
	 * Add an error
	 * @param   string  $error
	 */
	public function setErrors($error) {
		$this->errors[] = trim($error);
	}

	/**
	 * Get generated errors
	 * @param    bool    $ashtml Format using HTML?
	 * @return    array|string    Array of array messages OR HTML string
	 */
	public function getErrors($ashtml = true) {
		if (!$ashtml) {
			return $this->errors;
		} else {
			$ret = '';
			if (count($this->errors) > 0) {
				$ret = '<h4>' . _ER_UP_ERRORSRETURNED . '</h4>';
				foreach ($this->errors as $error) {
					$ret .= $error . '<br />';
				}
			}
			return $ret;
		}
	}
}
