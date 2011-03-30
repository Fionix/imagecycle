<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Juergen Furrer <juergen.furrer@gmail.com>
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
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 * Hint: use extdeveval to insert/update function index above.
 */

require_once(PATH_tslib.'class.tslib_pibase.php');

if (t3lib_extMgm::isLoaded('t3jquery')) {
	require_once(t3lib_extMgm::extPath('t3jquery').'class.tx_t3jquery.php');
}

/**
 * Plugin 'Image Cycle' for the 'imagecycle' extension.
 *
 * @author	Juergen Furrer <juergen.furrer@gmail.com>
 * @package	TYPO3
 * @subpackage	tx_imagecycle
 */
class tx_imagecycle_pi1 extends tslib_pibase
{
	public $prefixId      = 'tx_imagecycle_pi1';
	public $scriptRelPath = 'pi1/class.tx_imagecycle_pi1.php';
	public $extKey        = 'imagecycle';
	public $pi_checkCHash = true;
	public $images        = array();
	public $hrefs         = array();
	public $captions      = array();
	public $type          = 'normal';
	protected $lConf      = array();
	protected $contentKey = null;
	protected $jsFiles    = array();
	protected $js         = array();
	protected $cssFiles   = array();
	protected $css        = array();
	protected $imageDir   = 'uploads/tx_imagecycle/';
	protected $templateFileJS = null;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	public function main($content, $conf)
	{
		$this->conf = $conf;
		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		// define the key of the element
		$this->setContentKey("imagecycle");

		// set the system language
		$this->sys_language_uid = $GLOBALS['TSFE']->sys_language_content;
		if ($this->cObj->data['list_type'] == $this->extKey.'_pi1') {
			$this->type = 'normal';
			// It's a content, al data from flexform
			// Set the Flexform information
			$this->pi_initPIflexForm();
			$piFlexForm = $this->cObj->data['pi_flexform'];
			foreach ($piFlexForm['data'] as $sheet => $data) {
				foreach ($data as $lang => $value) {
					foreach ($value as $key => $val) {
						if ($key == 'imagesRTE') {
							if (count($val['el']) > 0) {
								foreach ($val['el'] as $elKey => $el) {
									if (is_numeric($elKey)) {
										$this->lConf[$key][] = array(
											"image"   => $el['data']['el']['image']['vDEF'],
											"href"    => $el['data']['el']['href']['vDEF'],
											"caption" => $this->pi_RTEcssText($el['data']['el']['caption']['vDEF']),
										);
									}
								}
							}
						} else {
							$this->lConf[$key] = $this->pi_getFFvalue($piFlexForm, $key, $sheet);
						}
					}
				}
			}

			// define the key of the element
			$this->setContentKey("imagecycle_c" . $this->cObj->data['uid']);

			// define the images
			switch ($this->lConf['mode']) {
				case "" : {}
				case "folder" : {}
				case "upload" : {
					$this->setDataUpload();
					break;
				}
				case "uploadRTE" : {
					$this->setDataUploadRTE();
					break;
				}
				case "dam" : {
					$this->setDataDam(false, 'tt_content', $this->cObj->data['uid']);
					break;
				}
				case "dam_catedit" : {
					$this->setDataDam(true, 'tt_content', $this->cObj->data['uid']);
					break;
				}
			}
			// Override the config with flexform data
			if ($this->lConf['imagewidth']) {
				$this->conf['imagewidth'] = $this->lConf['imagewidth'];
			}
			if ($this->lConf['imageheight']) {
				$this->conf['imageheight'] = $this->lConf['imageheight'];
			}
			if ($this->lConf['type']) {
				$this->conf['type'] = $this->lConf['type'];
			}
			if ($this->lConf['transition']) {
				$this->conf['transition'] = $this->lConf['transition'];
			}
			if ($this->lConf['transitiondir']) {
				$this->conf['transitionDir'] = $this->lConf['transitiondir'];
			}
			if ($this->lConf['transitionduration']) {
				$this->conf['transitionDuration'] = $this->lConf['transitionduration'];
			}
			if ($this->lConf['displayduration']) {
				$this->conf['displayDuration'] = $this->lConf['displayduration'];
			}
			if (is_numeric($this->lConf['delayduration']) && $this->lConf['delayduration'] != 0) {
				$this->conf['delayDuration'] = $this->lConf['delayduration'];
			}
			if ($this->lConf['fastOnEvent']) {
				$this->conf['fastOnEvent'] = $this->lConf['fastOnEvent'];
			}
			// Will be overridden, if not "from TS"
			if ($this->lConf['showcaption'] < 2) {
				$this->conf['showcaption'] = $this->lConf['showcaption'];
			}
			if ($this->lConf['showControl'] < 2) {
				$this->conf['showControl'] = $this->lConf['showControl'];
			}
			if ($this->lConf['showPager'] < 2) {
				$this->conf['showPager'] = $this->lConf['showPager'];
			}
			if ($this->lConf['random'] < 2) {
				$this->conf['random'] = $this->lConf['random'];
			}
			if ($this->lConf['stoponmousover'] < 2) {
				$this->conf['stopOnMousover'] = $this->lConf['stoponmousover'];
			}
			if ($this->lConf['pausedBegin'] < 2) {
				$this->conf['pausedBegin'] = $this->lConf['pausedBegin'];
			}
			if ($this->lConf['sync'] < 2) {
				$this->conf['sync'] = $this->lConf['sync'];
			}
			$this->conf['options'] = $this->lConf['options'];
		} else {
			$this->type = 'header';
			// It's the header
			$used_page = array();
			$pageID    = false;
			foreach ($GLOBALS['TSFE']->rootLine as $page) {
				if (! $pageID) {
					if (trim($page['tx_imagecycle_effect']) && ! $this->conf['disableRecursion']) {
						$this->conf['type'] = $page['tx_imagecycle_effect'];
					}
					if (
						(($page['tx_imagecycle_mode'] == 'upload' || ! $page['tx_imagecycle_mode']) && trim($page['tx_imagecycle_images']) != '') ||
						($page['tx_imagecycle_mode'] == 'dam'         && trim($page['tx_imagecycle_damimages']) != '') ||
						($page['tx_imagecycle_mode'] == 'dam_catedit' && trim($page['tx_imagecycle_damcategories']) != '') ||
						$this->conf['disableRecursion'] ||
						$page['tx_imagecycle_stoprecursion']
					) {
						$used_page = $page;
						$pageID    = $used_page['uid'];
						$this->lConf['mode']          = $used_page['tx_imagecycle_mode'];
						$this->lConf['damcategories'] = $used_page['tx_imagecycle_damcategories'];
					}
				}
			}
			if ($pageID) {
				if ($this->sys_language_uid) {
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_imagecycle_images, tx_imagecycle_hrefs, tx_imagecycle_captions','pages_language_overlay','pid='.intval($pageID).' AND sys_language_uid='.$this->sys_language_uid,'','',1);
					$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
					if (trim($used_page['tx_imagecycle_effect'])) {
						$this->conf['type'] = $row['tx_imagecycle_effect'];
					}
				}
				// define the images
				switch ($this->lConf['mode']) {
					case "" : {}
					case "folder" : {}
					case "upload" : {
						$this->images   = t3lib_div::trimExplode(',',     $used_page['tx_imagecycle_images']);
						$this->hrefs    = t3lib_div::trimExplode(chr(10), $used_page['tx_imagecycle_hrefs']);
						$this->captions = t3lib_div::trimExplode(chr(10), $used_page['tx_imagecycle_captions']);
						// Language overlay
						if ($this->sys_language_uid) {
							if (trim($row['tx_imagecycle_images']) != '') {
								$this->images   = t3lib_div::trimExplode(',',     $row['tx_imagecycle_images']);
								$this->hrefs    = t3lib_div::trimExplode(chr(10), $row['tx_imagecycle_hrefs']);
								$this->captions = t3lib_div::trimExplode(chr(10), $row['tx_imagecycle_captions']);
							}
						}
						break;
					}
					case "dam" : {
						$this->setDataDam(false, 'pages', $pageID);
						break;
					}
					case "dam_catedit" : {
						$this->setDataDam(true, 'pages', $pageID);
						break;
					}
				}
			}
		}

		$data = array();
		foreach ($this->images as $key => $image) {
			$data[$key]['image']   = $image;
			$data[$key]['href']    = $this->hrefs[$key];
			$data[$key]['caption'] = $this->captions[$key];
		}

		return $this->parseTemplate($data);
	}

	/**
	 * Set the contentKey
	 * @param string $contentKey
	 */
	public function setContentKey($contentKey=null)
	{
		$this->contentKey = ($contentKey == null ? $this->extKey : $contentKey);
	}

	/**
	 * Get the contentKey
	 * @return string
	 */
	public function getContentKey()
	{
		return $this->contentKey;
	}

	/**
	 * Set the Information of the images if mode = upload
	 * @return boolean
	 */
	protected function setDataUpload()
	{
		if ($this->lConf['images']) {
			// define the images
			$this->images = t3lib_div::trimExplode(',', $this->lConf['images']);
			// define the hrefs
			if ($this->lConf['hrefs']) {
				$this->hrefs = t3lib_div::trimExplode(chr(10), $this->lConf['hrefs']);
			}
			// define the captions
			if ($this->lConf['captions']) {
				$this->captions = t3lib_div::trimExplode(chr(10), $this->lConf['captions']);
			}
			return true;
		}
		return false;
	}

	/**
	 * Set the information of the images if mode = uploadRTE
	 */
	protected function setDataUploadRTE()
	{
		if (count($this->lConf['imagesRTE']) > 0) {
			foreach ($this->lConf['imagesRTE'] as $key => $image) {
				$this->images[]   = $image['image'];
				$this->hrefs[]    = $image['href'];
				$this->captions[] = $image['caption'];
			}
		}
	}

	/**
	 * Set the Information of the images if mode = dam
	 * @return boolean
	 */
	protected function setDataDam($fromCategory=false, $table='tt_content', $uid=0)
	{
		// clear the imageDir
		$this->imageDir = '';
		// get all fields for captions
		$damCaptionFields = t3lib_div::trimExplode(',', $this->conf['damCaptionFields'], true);
		$damHrefFields    = t3lib_div::trimExplode(',', $this->conf['damHrefFields'], true);
		$fields  = (count($damCaptionFields) > 0 ? ','.implode(',tx_dam.', $damCaptionFields) : '');
		$fields .= (count($damHrefFields) > 0    ? ','.implode(',tx_dam.', $damHrefFields)    : '');
		if ($fromCategory === true) {
			// Get the images from dam category
			$damcategories = $this->getDamcats($this->lConf['damcategories']);
			$res = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
				tx_dam_db::getMetaInfoFieldList() . $fields,
				'tx_dam',
				'tx_dam_mm_cat',
				'tx_dam_cat',
				" AND tx_dam_cat.uid IN (".implode(",", $damcategories).") AND tx_dam.file_mime_type='image' AND tx_dam.sys_language_uid=" . $GLOBALS['TYPO3_DB']->fullQuoteStr($this->sys_language_uid, 'tx_dam'),
				'',
				'tx_dam.sorting',
				''
			);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$images['rows'][] = $row;
			}
		} else {
			// Get the images from dam
			$images = tx_dam_db::getReferencedFiles(
				$table,
				$uid,
				'imagecycle',
				'tx_dam_mm_ref',
				tx_dam_db::getMetaInfoFieldList() . $fields,
				"tx_dam.file_mime_type = 'image'"
			);
		}
		if (count($images['rows']) > 0) {
			// overlay the translation
			$conf = array(
				'sys_language_uid' => $this->sys_language_uid,
				'lovl_mode' => ''
			);
			// add image
			foreach ($images['rows'] as $key => $row) {
				$row = tx_dam_db::getRecordOverlay('tx_dam', $row, $conf);
				// set the data
				$this->images[] = $row['file_path'].$row['file_name'];$
				// set the href
				$href = '';
				unset($href);
				if (count($damHrefFields) > 0) {
					foreach ($damHrefFields as $damHrefField) {
						if (! isset($href) && trim($row[$damHrefField])) {
							$href = $row[$damHrefField];
							break;
						}
					}
				}
				$this->hrefs[] = $href;
				// set the caption
				$caption = '';
				unset($caption);
				if (count($damCaptionFields) > 0) {
					foreach ($damCaptionFields as $damCaptionField) {
						if (! isset($caption) && trim($row[$damCaptionField])) {
							$caption = $row[$damCaptionField];
							break;
						}
					}
				}
				$this->captions[] = $caption;
			}
		}
		return true;
	}

	/**
	 * return all DAM categories including subcategories
	 *
	 * @return	array
	 */
	protected function getDamcats($dam_cat='')
	{
		$damCats = t3lib_div::trimExplode(",", $dam_cat, true);
		if (count($damCats) < 1) {
			return;
		} else {
			// select subcategories
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'uid, parent_id',
				'tx_dam_cat',
				'parent_id IN ('.implode(",", $damCats).') '.$this->cObj->enableFields('tx_dam_cat'),
				'',
				'parent_id',
				''
			);
			$subcats = array();
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$damCats[] = $row['uid'];
			}
		}
		return $damCats;
	}

	/**
	 * Parse all images into the template
	 * @param $data
	 * @return string
	 */
	public function parseTemplate($data=array(), $dir='', $onlyJS=false)
	{
		// define the directory of images
		if ($dir == '') {
			$dir = $this->imageDir;
		}

		// Check if $data is array
		if (count($data) == 0 && $onlyJS === false) {
			return false;
		}

		// define the contentKey if not exist
		if ($this->getContentKey() == '') {
			$this->setContentKey("imagecycle_key");
		}

		// The template for JS
		if (! $this->templateFileJS = $this->cObj->fileResource($this->conf['templateFileJS'])) {
			$this->templateFileJS = $this->cObj->fileResource("EXT:imagecycle/res/tx_imagecycle.js");
		}

		// set the key
		$markerArray = array();
		$markerArray["KEY"] = $this->getContentKey();

		// define the jQuery mode and function
		if ($this->conf['jQueryNoConflict']) {
			$jQueryNoConflict = "jQuery.noConflict();";
		} else {
			$jQueryNoConflict = "";
		}

		$options = array();

		if (! $this->conf['imagewidth']) {
			$this->conf['imagewidth'] = ($this->conf['imagewidth'] ? $this->conf['imagewidth'] : "200c");
		}
		if (! $this->conf['imageheight']) {
			$this->conf['imageheight'] = ($this->conf['imageheight'] ? $this->conf['imageheight'] : "200c");
		}
		if ($this->conf['type']) {
			$options['fx'] = "fx: '{$this->conf['type']}'";
		}
		if (in_array($this->conf['transition'], array('linear', 'swing'))) {
			$options['easing'] = "easing: '{$this->conf['transition']}'";
		} elseif ($this->conf['transitionDir'] && $this->conf['transition']) {
			$options['easing'] = "easing: 'ease{$this->conf['transitionDir']}{$this->conf['transition']}'";
		}
		if ($this->conf['transitionDuration'] > 0) {
			$options['speed'] = "speed: '{$this->conf['transitionDuration']}'";
		}
		if ($this->conf['displayDuration'] > 0) {
			$options['timeout'] = "timeout: '{$this->conf['displayDuration']}'";
		}
		if (is_numeric($this->conf['delayDuration']) && $this->conf['delayDuration'] != 0) {
			$options['delay'] = "delay: {$this->conf['delayDuration']}";
		}
		if ($this->conf['fastOnEvent'] > 0) {
			$options['fastOnEvent'] = "fastOnEvent: ".$this->conf['fastOnEvent'];
		}

		if ($this->conf['stopOnMousover']) {
			$options['pause'] = "pause: true";
		}
		$options['sync'] = "sync: ".($this->conf['sync'] ? 'true' : 'false');
		$options['random'] = "random: ".($this->conf['random'] ? 'true' : 'false');

		$captionTag = $this->cObj->stdWrap($this->conf['cycle.'][$this->type.'.']['captionTag'], $this->conf['cycle.'][$this->type.'.']['captionTag.']);
		$markerArray["CAPTION_TAG"] = $captionTag;
		$before = null;
		$after  = null;
		// add caption
		if ($this->conf['showcaption']) {
			// define the animation for the caption
			$fx = array();
			if (! $this->conf['captionAnimate']) {
				$before .= "jQuery('{$captionTag}', this).css('display', 'none');";
				$after  .= "jQuery('{$captionTag}', this).css('display', 'block');";
			} else {
				if ($this->conf['captionTypeOpacity']) {
					$fx[] = "opacity: 'show'";
				}
				if ($this->conf['captionTypeHeight']) {
					$fx[] = "height: 'show'";
				}
				if ($this->conf['captionTypeWidth']) {
					$fx[] = "width: 'show'";
				}
				// if no effect is choosen, opacity is the fallback
				if (count($fx) < 1) {
					$fx[] = "opacity: 'show'";
				}
				if (! is_numeric($this->conf['captionSpeed'])) {
					$this->conf['captionSpeed'] = 200;
				}
				$before .= "jQuery('{$captionTag}', this).css('display', 'none');";
				$after  .= "jQuery('{$captionTag}', this).animate({".(implode(",", $fx))."},{$this->conf['captionSpeed']});";
			}
			if ($this->conf['captionSync']) {
				$before = $before . $after;
				$after = null;
			}
		}
		// 
		if ($this->conf['showPager']) {
			$templateActivatePagerCode = trim($this->cObj->getSubpart($this->templateFileJS, "###TEMPLATE_ACTIVATE_PAGER_JS###"));
			$after .= $this->cObj->substituteMarkerArray($templateActivatePagerCode, $markerArray, '###|###', 0);
		}
		if ($before) {
			$options['before'] = "before: function(a,n,o,f) {".$before."}";
		}
		if ($after) {
			$options['after'] = "after: function(a,n,o,f) {".$after."}";
		}

		// overwrite all options if set
		if (trim($this->conf['options'])) {
			$options = array($this->conf['options']);
		}

		// define the js file
		$this->addJsFile($this->conf['jQueryCycle']);

		// define the css file
		$this->addCssFile($this->conf['cssFile']);

		// get the Template of the Javascript
		if (! $templateCode = trim($this->cObj->getSubpart($this->templateFileJS, "###TEMPLATE_JS###"))) {
			$templateCode = "alert('Template TEMPLATE_JS is missing')";
		}
		$templateCode = $this->cObj->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

		// Show the caption when sync is turned off
		if ($this->conf['showcaption'] && ! $this->conf['captionSync']) {
			$templateShowCaption = trim($this->cObj->getSubpart($templateCode, "###SHOW_CAPTION_AT_START###"));
		} else {
			$templateShowCaption = null;
		}
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###SHOW_CAPTION_AT_START###', $templateShowCaption, 0);

		// define the control
		if ($this->conf['showControl']) {
			$templateControl = trim($this->cObj->getSubpart($templateCode, "###CONTROL###"));
			$templateControlAfter = trim($this->cObj->getSubpart($templateCode, "###CONTROL_AFTER###"));
			$options[] = trim($this->cObj->getSubpart($templateCode, "###CONTROL_OPTIONS###"));
		} else {
			$templateControl = null;
		}
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###CONTROL###', $templateControl, 0);
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###CONTROL_AFTER###', $templateControlAfter, 0);
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###CONTROL_OPTIONS###', '', 0);

		// define the play class
		if ($this->conf['pausedBegin']) {
			$templatePaused = $this->cObj->getSubpart($templateCode, "###PAUSED###");
			$templatePausedBegin = $this->cObj->getSubpart($templateCode, "###PAUSED_BEGIN###");;
		} else {
			$templatePaused = null;
			$templatePausedBegin = null;
		}
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###PAUSED###', $templatePaused, 0);
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###PAUSED_BEGIN###', $templatePausedBegin, 0);

		// define the pager
		if ($this->conf['showPager']) {
			$templatePager = $this->cObj->getSubpart($templateCode, "###PAGER###");
		} else {
			$templatePager = null;
		}
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###PAGER###', $templatePager, 0);

		// Slow connection will have a load to start
		if ($this->conf['fixSlowConnection']) {
			$templateSlowBefore = $this->cObj->getSubpart($templateCode, "###SLOW_CONNECTION_BEFORE###");
			$templateSlowAfter  = $this->cObj->getSubpart($templateCode, "###SLOW_CONNECTION_AFTER###");
		} else {
			$templateSlowBefore = null;
		}
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###SLOW_CONNECTION_BEFORE###', $templateSlowBefore, 0);
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###SLOW_CONNECTION_AFTER###',  $templateSlowAfter, 0);

		// If only one image is displayed, the caption will be show
		if (count($data) == 1) {
			$templateOnlyOneImage = $this->cObj->getSubpart($templateCode, "###ONLY_ONE_IMAGE###");
		} else {
			$templateOnlyOneImage = null;
		}
		$templateCode = $this->cObj->substituteSubpart($templateCode, '###ONLY_ONE_IMAGE###', $templateOnlyOneImage, 0);

		// define the markers
		$markerArray = array();
		$markerArray["OPTIONS"]     = implode(",\n		", $options);
		$markerArray["CAPTION_TAG"] = $captionTag;

		// set the markers
		$templateCode = $this->cObj->substituteMarkerArray($templateCode, $markerArray, '###|###', 0);

		$this->addJS($jQueryNoConflict . $templateCode);

		// Add the ressources
		$this->addResources();

		if ($onlyJS === true) {
			return true;
		}

		$return_string = null;
		$images = null;
		$pager = null;
		$GLOBALS['TSFE']->register['key'] = $this->getContentKey();
		$GLOBALS['TSFE']->register['imagewidth']  = $this->conf['imagewidth'];
		$GLOBALS['TSFE']->register['imageheight'] = $this->conf['imageheight'];
		$GLOBALS['TSFE']->register['showcaption'] = $this->conf['showcaption'];
		$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = 0;
		$GLOBALS['TSFE']->register['IMAGE_COUNT'] = count($data);
		if (count($data) > 0) {
			foreach ($data as $key => $item) {
				$image = null;
				$imgConf = $this->conf['cycle.'][$this->type.'.']['image.'];
				$totalImagePath = $dir . $item['image'];
				$GLOBALS['TSFE']->register['file']    = $totalImagePath;
				$GLOBALS['TSFE']->register['href']    = $item['href'];
				$GLOBALS['TSFE']->register['caption'] = $item['caption'];
				$GLOBALS['TSFE']->register['CURRENT_ID'] = $GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] + 1;
				if ($this->hrefs[$key]) {
					$imgConf['imageLinkWrap.'] = $imgConf['imageHrefWrap.'];
				}
				$image = $this->cObj->IMAGE($imgConf);
				if ($item['caption'] && $this->conf['showcaption']) {
					$image = $this->cObj->stdWrap($image, $this->conf['cycle.'][$this->type.'.']['captionWrap.']);
				}
				$image = $this->cObj->stdWrap($image, $this->conf['cycle.'][$this->type.'.']['itemWrap.']);
				$images .= $image;
				// create the pager
				if ($this->conf['showPager']) {
					$pager .= trim($this->cObj->cObjGetSingle($this->conf['cycle.'][$this->type.'.']['pager'], $this->conf['cycle.'][$this->type.'.']['pager.']));
				}
				$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] ++;
			}
			$markerArray['PAGER'] = $this->cObj->stdWrap($pager, $this->conf['cycle.'][$this->type.'.']['pagerWrap.']);
			// the stdWrap
			$images = $this->cObj->stdWrap($images, $this->conf['cycle.'][$this->type.'.']['stdWrap.']);
			$return_string = $this->cObj->substituteMarkerArray($images, $markerArray, '###|###', 0);
		}
		return $return_string;
	}

	/**
	 * Include all defined resources (JS / CSS)
	 *
	 * @return void
	 */
	protected function addResources()
	{
		// checks if t3jquery is loaded
		if (T3JQUERY === true) {
			tx_t3jquery::addJqJS();
		} else {
			$this->addJsFile($this->conf['jQueryLibrary'], true);
			$this->addJsFile($this->conf['jQueryEasing']);
		}
		if (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
			$pagerender = $GLOBALS['TSFE']->getPageRenderer();
		}
		// Fix moveJsFromHeaderToFooter (add all scripts to the footer)
		if ($GLOBALS['TSFE']->config['config']['moveJsFromHeaderToFooter']) {
			$allJsInFooter = true;
		} else {
			$allJsInFooter = false;
		}
		// add all defined JS files
		if (count($this->jsFiles) > 0) {
			foreach ($this->jsFiles as $jsToLoad) {
				if (T3JQUERY === true) {
					$conf = array(
						'jsfile' => $jsToLoad,
						'tofooter' => ($this->conf['jsInFooter'] || $allJsInFooter),
						'jsminify' => $this->conf['jsMinify'],
					);
					tx_t3jquery::addJS('', $conf);
				} else {
					$file = $this->getPath($jsToLoad);
					if ($file) {
						if (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
							if ($allJsInFooter) {
								$pagerender->addJsFooterFile($file, 'text/javascript', $this->conf['jsMinify']);
							} else {
								$pagerender->addJsFile($file, 'text/javascript', $this->conf['jsMinify']);
							}
						} else {
							$temp_file = '<script type="text/javascript" src="'.$file.'"></script>';
							if ($allJsInFooter) {
								$GLOBALS['TSFE']->additionalFooterData['jsFile_'.$this->extKey.'_'.$file] = $temp_file;
							} else {
								$GLOBALS['TSFE']->additionalHeaderData['jsFile_'.$this->extKey.'_'.$file] = $temp_file;
							}
						}
					} else {
						t3lib_div::devLog("'{$jsToLoad}' does not exists!", $this->extKey, 2);
					}
				}
			}
		}
		// add all defined JS script
		if (count($this->js) > 0) {
			foreach ($this->js as $jsToPut) {
				$temp_js .= $jsToPut;
			}
			$conf = array();
			$conf['jsdata'] = $temp_js;
			if (T3JQUERY === true && t3lib_div::int_from_ver($this->getExtensionVersion('t3jquery')) >= 1002000) {
				$conf['tofooter'] = ($this->conf['jsInFooter'] || $allJsInFooter);
				$conf['jsminify'] = $this->conf['jsMinify'];
				$conf['jsinline'] = $this->conf['jsInline'];
				tx_t3jquery::addJS('', $conf);
			} else {
				// Add script only once
				$hash = md5($temp_js);
				if ($this->conf['jsInline']) {
					$GLOBALS['TSFE']->inlineJS[$hash] = $temp_css;
				} elseif (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
					if ($this->conf['jsInFooter'] || $allJsInFooter) {
						$pagerender->addJsFooterInlineCode($hash, $temp_js, $this->conf['jsMinify']);
					} else {
						$pagerender->addJsInlineCode($hash, $temp_js, $this->conf['jsMinify']);
					}
				} else {
					if ($this->conf['jsMinify']) {
						$temp_js = t3lib_div::minifyJavaScript($temp_js);
					}
					if ($this->conf['jsInFooter'] || $allJsInFooter) {
						$GLOBALS['TSFE']->additionalFooterData['js_'.$this->extKey.'_'.$hash] = t3lib_div::wrapJS($temp_js, true);
					} else {
						$GLOBALS['TSFE']->additionalHeaderData['js_'.$this->extKey.'_'.$hash] = t3lib_div::wrapJS($temp_js, true);
					}
				}
			}
		}
		// add all defined CSS files
		if (count($this->cssFiles) > 0) {
			foreach ($this->cssFiles as $cssToLoad) {
				// Add script only once
				$file = $this->getPath($cssToLoad);
				if ($file) {
					if (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
						$pagerender->addCssFile($file, 'stylesheet', 'all', '', $this->conf['cssMinify']);
					} else {
						$GLOBALS['TSFE']->additionalHeaderData['cssFile_'.$this->extKey.'_'.$file] = '<link rel="stylesheet" type="text/css" href="'.$file.'" media="all" />'.chr(10);
					}
				} else {
					t3lib_div::devLog("'{$cssToLoad}' does not exists!", $this->extKey, 2);
				}
			}
		}
		// add all defined CSS Script
		if (count($this->css) > 0) {
			foreach ($this->css as $cssToPut) {
				$temp_css .= $cssToPut;
			}
			$hash = md5($temp_css);
			if (t3lib_div::int_from_ver(TYPO3_version) >= 4003000) {
				$pagerender->addCssInlineBlock($hash, $temp_css, $this->conf['cssMinify']);
			} else {
				// addCssInlineBlock
				$GLOBALS['TSFE']->additionalCSS['css_'.$this->extKey.'_'.$hash] .= $temp_css;
			}
		}
	}

	/**
	 * Return the webbased path
	 * 
	 * @param string $path
	 * return string
	 */
	protected function getPath($path="")
	{
		return $GLOBALS['TSFE']->tmpl->getFileName($path);
	}

	/**
	 * Add additional JS file
	 * 
	 * @param string $script
	 * @param boolean $first
	 * @return void
	 */
	protected function addJsFile($script="", $first=false)
	{
		$script = t3lib_div::fixWindowsFilePath($script);
		if ($this->getPath($script) && ! in_array($script, $this->jsFiles)) {
			if ($first === true) {
				$this->jsFiles = array_merge(array($script), $this->jsFiles);
			} else {
				$this->jsFiles[] = $script;
			}
		}
	}

	/**
	 * Add JS to header
	 * 
	 * @param string $script
	 * @return void
	 */
	protected function addJS($script="")
	{
		if (! in_array($script, $this->js)) {
			$this->js[] = $script;
		}
	}

	/**
	 * Add additional CSS file
	 * 
	 * @param string $script
	 * @return void
	 */
	protected function addCssFile($script="")
	{
		$script = t3lib_div::fixWindowsFilePath($script);
		if ($this->getPath($script) && ! in_array($script, $this->cssFiles)) {
			$this->cssFiles[] = $script;
		}
	}

	/**
	 * Add CSS to header
	 * 
	 * @param string $script
	 * @return void
	 */
	protected function addCSS($script="")
	{
		if (! in_array($script, $this->css)) {
			$this->css[] = $script;
		}
	}

	/**
	 * Returns the version of an extension (in 4.4 its possible to this with t3lib_extMgm::getExtensionVersion)
	 * @param string $key
	 * @return string
	 */
	protected function getExtensionVersion($key)
	{
		if (! t3lib_extMgm::isLoaded($key)) {
			return '';
		}
		$_EXTKEY = $key;
		include(t3lib_extMgm::extPath($key) . 'ext_emconf.php');
		return $EM_CONF[$key]['version'];
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagecycle/pi1/class.tx_imagecycle_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/imagecycle/pi1/class.tx_imagecycle_pi1.php']);
}

?>