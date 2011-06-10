<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Claus Due <claus@wildside.dk>, Wildside A/S
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Image ViewHelper
 *
 * In addition to doing all that f:image does, this ViewHelper supports:
 *
 * - multi-image rendering using wildcard filenames, path + CSV-of-filenames or
 *   array of files.
 * - automatic click-enlarge version of multiple images through a single tag.
 * - use of alternative image (for all images) if "src" is not a file
 *
 *
 *
 *
 * @author Claus Due, Wildside A/S
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @package Fed
 * @subpackage ViewHelpers
 *
 */
class Tx_Fed_ViewHelpers_ImageViewHelper extends Tx_Fluid_ViewHelpers_ImageViewHelper {

	/**
	 * @var Tx_Fed_Utility_DocumentHead
	 */
	protected $documentHead;

	/**
	 * @param Tx_Fed_Utility_DocumentHead $documentHead
	 * @return void
	 */
	public function injectDocumentHead(Tx_Fed_Utility_DocumentHead $documentHead) {
		$this->documentHead = $documentHead;
	}

	/**
	 * Initialize arguments
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('alt', 'string', 'Specifies an alternate text for an image', FALSE, NULL);
		$this->registerTagAttribute('ismap', 'string', 'Specifies an image as a server-side image-map. Rarely used. Look at usemap instead', FALSE);
		$this->registerTagAttribute('longdesc', 'string', 'Specifies the URL to a document that contains a long description of an image', FALSE);
		$this->registerTagAttribute('usemap', 'string', 'Specifies an image as a client-side image-map', FALSE);
		$this->registerArgument('src', 'mixed', 'Filename(s) to render', TRUE);
		$this->registerArgument('width', 'mixed', '');
		$this->registerArgument('height', 'mixed', '');
		$this->registerArgument('minWidth', 'integer', '');
		$this->registerArgument('minHeight', 'integer', '');
		$this->registerArgument('maxWidth', 'integer', '');
		$this->registerArgument('maxHeight', 'integer', '');
		$this->registerArgument('path', 'string', 'Using this triggers CSV filename parsing but still
			allows rendering a single image if only one is specified', FALSE, NULL);
		$this->registerArgument('altsrc', 'string', 'Displays this image if "src" is not a file', FALSE, NULL);
		$this->registerArgument('divider', 'string', 'String divider to insert between images', FALSE, NULL);
		$this->registerArgument('largeWidth', 'string', 'Specify this to render a large version of files too, for switch-viewing', FALSE);
		$this->registerArgument('largeHeight', 'string', 'Specify this to render a large version of files too, for switch-viewing', FALSE);
		$this->registerArgument('largePosition', 'string', 'Controls where large image goes. Use top, left, right or bottom -
			is added as class on large img', FALSE, 'left');
		$this->registerArgument('sortBy', 'string', 'Sort field of multiple files. Possible: filename, modified, created, size,
			resolution, x, y, exif:<fieldname> - "resolution" mode means (x+y*dpi) size becomes sort value');
		$this->registerArgument('sortDirection', 'string', 'Direction to sort', FALSE, 'ASC');
	}

	/**
	 * Render the image(s) to HTML
	 *
	 * @return string
	 */
	public function render() {
		$pathinfo = pathinfo($this->arguments['src']);
		if ($pathinfo['filename'] === '*') {
			$images = $this->documentHead->getFilenamesOfType($pathinfo['dirname'], $pathinfo['extension']);
		} else if ($this->arguments->hasArgument('path')) {
			$images = explode(',', $this->arguments['src']);
			// patch for CSV files missing relative pathnames
			foreach ($images as $k=>$v) {
				$images[$k] = $this->arguments['path'] . $v;
			}
		} else if (is_array($this->arguments['src'])) {
			$images = $this->arguments['src'];
		} else {
			$images = array($this->arguments['src']);
		}
		return $this->renderImages($images);
	}

	/**
	 * Render the images into HTML
	 *
	 * @param array $files
	 * @param boolean $returnConverted
	 * @return string
	 */
	protected function renderImages(array $images, $returnConverted=FALSE) {
		$converted = array();
		$lines = array();
		$setup = array(
			'width' => $this->arguments['width'],
			'height' => $this->arguments['height'],
			'minW' => $this->arguments['minW'],
			'minH' => $this->arguments['minH'],
			'maxW' => $this->arguments['maxW'],
			'maxH' => $this->arguments['maxH']
		);
		if ($this->arguments['largeWidth'] > 0 || $this->arguments['largeHeight'] > 0) {
			$this->addScript();
			$largeSetup = array(
				'width' => $this->arguments['largeWidth'],
				'height' => $this->arguments['largeHeight'],
				'minW' => $this->arguments['largeWidth'],
				'minH' => $this->arguments['largeHeight'],
				'maxW' => $this->arguments['largeWidth'],
				'maxH' => $this->arguments['largeHeight']
			);
			$large = array();
			if ($this->arguments['id']) {
				$uniqid = $this->arguments['id'];
			} else {
				$uniqid = uniqid('fed-xl-');
			}
			foreach ($images as $image) {
				$large[] = $this->renderImage($image, $largeSetup);
			}
			$convertedImageFilename = $this->renderImage($images[0], $largeSetup);
			$this->tag->addAttribute('width', $this->arguments['largeWidth']);
			$this->tag->addAttribute('height', $this->arguments['largeHeight']);
			$this->tag->addAttribute('class', 'large ' . $this->arguments['largePosition']);
			$this->tag->addAttribute('id', $uniqid);
			$this->tag->addAttribute('src', $convertedImageFilename);
			$lines[] = $this->tag->render();
			$this->tag->removeAttribute('id');
		}
		foreach ($images as $k=>$image) {
			$convertedImageFilename = $this->renderImage($image, $setup);
			$imagesize = getimagesize(PATH_site . $convertedImageFilename);
			$this->tag->addAttribute('width', $imagesize[0]);
			$this->tag->addAttribute('height', $imagesize[1]);
			$this->tag->addAttribute('src', $convertedImageFilename);
			if ($large) {
				$this->tag->addAttribute('onclick', "fedImgXL('{$uniqid}', '{$large[$k]}');");
				$this->tag->addAttribute('class', 'small');
			}
			$lines[] = $this->tag->render();
		}
		$html = implode($this->arguments['divider'], $lines);
		return $html;
	}

	/**
	 * Sort the images as defined by arguments
	 *
	 * @param array $images
	 * @return array
	 */
	protected function sortImages(array $images) {
		return $images;
	}

	/**
	 * Gets the value used for sort index for this image
	 *
	 * @param string $src
	 * @return mixed
	 */
	protected function getSortValue($src) {

	}

	/**
	 * Reads EXIF info in $field for $src
	 *
	 * @param string $src
	 * @return array
	 */
	protected function readExifInfoField($src, $field) {

	}

	/**
	 * Reads EXIF info for $src
	 *
	 * @param string $src
	 * @return array
	 */
	protected function readExifInfo($src) {

	}

	/**
	 * Returns the proper new src value for an img tag
	 *
	 * @param string $src
	 * @param array $setup
	 * @return string
	 */
	protected function renderImage($src, $setup) {
		if (TYPO3_MODE === 'BE' && substr($src, 0, 3) === '../') {
			$src = substr($src, 3);
		}
		$imageInfo = $this->contentObject->getImgResource($src, $setup);
		$GLOBALS['TSFE']->lastImageInfo = $imageInfo;
		if (!is_array($imageInfo)) {
			throw new Tx_Fluid_Core_ViewHelper_Exception('Could not get image resource for "' . htmlspecialchars($src) . '".' , 1253191060);
		}
		$imageInfo[3] = t3lib_div::png_to_gif_by_imagemagick($imageInfo[3]);
		$GLOBALS['TSFE']->imagesOnPage[] = $imageInfo[3];

		$imageSource = $GLOBALS['TSFE']->absRefPrefix . t3lib_div::rawUrlEncodeFP($imageInfo[3]);
		if (TYPO3_MODE === 'BE') {
			$imageSource = '../' . $imageSource;
			$this->resetFrontendEnvironment();
		}
		return $imageSource;
	}

	/**
	 * Attach the scripts necessary for clickenlarge
	 *
	 * @return void
	 */
	protected function addScript() {
		$script = "function fedImgXL(parent, filename) {  document.getElementById(parent).src = filename; };";
		$this->documentHead->includeHeader($script, 'js');
	}

}

?>