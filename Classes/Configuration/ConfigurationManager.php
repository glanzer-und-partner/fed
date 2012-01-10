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
 * Configuration Manager subclass. Contains additional configuration fetching
 * methods used in FED's features.
 *
 * @version $Id$
 * @copyright Copyright belongs to the respective authors
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @package Fed
 * @subpackage Configuration
 */
class Tx_Fed_Configuration_ConfigurationManager extends Tx_Extbase_Configuration_ConfigurationManager implements Tx_Extbase_Configuration_ConfigurationManagerInterface {

	/**
	 * Get definitions of paths for FCEs defined in TypoScript
	 *
	 * @param string $extensionName Optional extension name to get only that extension
	 * @return array
	 * @api
	 */
	public function getContentConfiguration($extensionName=NULL) {
		return $this->getTypoScriptSubConfiguration($extensionName, 'fce');
	}

	/**
	 * Get definitions of paths for Page Templates defined in TypoScript
	 *
	 * @param string $extensionName
	 * @return array
	 * @api
	 */
	public function getPageConfiguration($extensionName=NULL) {
		return $this->getTypoScriptSubConfiguration($extensionName, 'page');
	}

	/**
	 * Gets an array of TypoScript configuration from below plugin.tx_fed -
	 * if $extensionName is set in parameters it is used to indicate which sub-
	 * section of the result to return.
	 *
	 * @param string $extensionName
	 * @param string $memberName
	 * @return array
	 */
	protected function getTypoScriptSubConfiguration($extensionName, $memberName) {
		$config = $this->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		$config = $config['plugin.']['tx_fed.'][$memberName . '.'];
		if (is_array($config) === FALSE) {
			return array();
		}
		$config = Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($config);
		$config = Tx_Fed_Utility_Path::translatePath($config);
		if ($extensionName) {
			return $config[$extensionName];
		} else {
			return $config;
		}
	}

	/**
	 * Gets a human-readable label from a Fluid Page template file
	 *
	 * @param string $extensionName
	 * @param string $templateFile
	 * @return string
	 * @api
	 */
	public function getPageTemplateLabel($extensionName, $templateFile) {
		$config = $this->getPageConfiguration($extensionName);
		$exposedView = $this->objectManager->get('Tx_Flux_MVC_View_ExposedStandaloneView');
		$exposedView->setTemplatePathAndFilename($config['templateRootPath'] . 'Page/' . $templateFile . '.html');
		$exposedView->setLayoutRootPath($config['layoutRootPath']);
		$exposedView->setPartialRootPath($config['partialRootPath']);
		$page = $exposedView->getStoredVariable('Tx_Flux_ViewHelpers_FlexformViewHelper', 'storage', 'Configuration');
		return $page['label'] ? $page['label'] : $templateFile . '.html';
	}

	/**
	 * Gets a list of usable Page Templates from defined page template TypoScript
	 *
	 * @param string $format
	 * @return array
	 * @api
	 */
	public function getAvailablePageTemplateFiles($format='html') {
		$typoScript = $this->getPageConfiguration();
		$output = array();
		if (is_array($typoScript) === FALSE) {
			return $output;
		}
		foreach ($typoScript as $extensionName=>$group) {
			$path = $group['templateRootPath'] . 'Page' . DIRECTORY_SEPARATOR;
			$files = scandir($path);
			$output[$extensionName] = array();
			foreach ($files as $k=>$file) {
				$pathinfo = pathinfo($path . $file);
				$extension = $pathinfo['extension'];
				if (substr($file, 0, 1) === '.') {
					unset($files[$k]);
				} else if (strtolower($extension) != strtolower($format)) {
					unset($files[$k]);
				} else {
					$output[$extensionName][] = $pathinfo['filename'];
				}
			}
		}
		return $output;
	}

}

?>
