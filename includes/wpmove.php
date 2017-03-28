<?php

class wpmove {
	// private variables
	private static $sPathToConfig = '../wp-config.php';
	private static $oConnection;
	private static $sDB_HOST;
	private static $sDB_USER;
	private static $sDB_PASSWORD;
	private static $sDB_NAME;
	private static $sTablePrefix;
	private static $bIsMultisite = false;
	// public variables
	public static $aErrors = array();
	public static $aBlogs = array();
	public static $sNewDomain;
	public static $bHttps;
	public static $bUpdate = false;
	public static $aAffectedRows = array();
	public static $iExecTime = array();
	// constants
	const WP_CONFIG_ERROR = 'Error[100] Could not find wp-config.php. Please make sure that the file exists.';
	const DB_HOST_ERROR = 'Error[101] Could not fetch the database host from wp-config.php';
	const DB_USER_ERROR = 'Error[102] Could not fetch the database user from wp-config.php';
	const DB_PASSWORD_ERROR = 'Error[103] Could not fetch the database password from wp-config.php';
	const DB_NAME_ERROR = 'Error[104] Could not fetch the database name from wp-config.php';
	const TABLE_PREFIX_ERROR = 'Error[105] Could not fetch the table prefix from wp-config.php';

	static public function init() {
		// Check if the config file is found. If not, abort here.
		if (!file_exists(self::$sPathToConfig)) {
			self::$aErrors[] = self::WP_CONFIG_ERROR;

			return;
		}

		// Load the wp-config.php
		$sWP_Config = file_get_contents(self::$sPathToConfig, null, null, 0);

		// Read content from wp-config.php
		self::$sDB_HOST = self::extractString($sWP_Config, "define('DB_HOST', '", "');");
		self::$sDB_USER = self::extractString($sWP_Config, "define('DB_USER', '", "');");
		self::$sDB_PASSWORD = self::extractString($sWP_Config, "define('DB_PASSWORD', '", "');");
		self::$sDB_NAME = self::extractString($sWP_Config, "define('DB_NAME', '", "');");
		self::$sTablePrefix = self::extractString($sWP_Config, "table_prefix  = '", "';");

		// Check if all necessary variables were found in the config.php.
		self::checkVariables();

		if (empty(self::$aErrors)) {
			self::openConnection();
			self::getBlogs();
			self::updateDomain();
			self::processUpdate();
		}
	}

	static private function checkVariables() {
		if (empty(self::$sDB_HOST)) {
			self::$aErrors[] = self::DB_HOST_ERROR;
		}
		if (empty(self::$sDB_USER)) {
			self::$aErrors[] = self::DB_USER_ERROR;
		}
		if (empty(self::$sDB_PASSWORD)) {
			self::$aErrors[] = self::DB_PASSWORD_ERROR;
		}
		if (empty(self::$sDB_NAME)) {
			self::$aErrors[] = self::DB_NAME_ERROR;
		}
		if (empty(self::$sTablePrefix)) {
			self::$aErrors[] = self::TABLE_PREFIX_ERROR;
		}
	}

	static public function openConnection() {
		self::$oConnection = new mysqli(self::$sDB_HOST, self::$sDB_USER, self::$sDB_PASSWORD, self::$sDB_NAME);

		// Check connection
		if (self::$oConnection->connect_error) {
			die("</br>Connection failed: " . self::$oConnection->connect_error);
		}
//		echo '<div class="message">Connection established...</div>';
	}

	static public function closeConnection() {
		if (is_object(self::$oConnection)) {
			if (self::$oConnection->close()) {
//				echo '<div class="message">Connection closed...</div>';
			}
			die;
		}
	}

	static private function updateDomain() {
		if (isset($_POST['https']) && !empty($_POST['https'])) {
			self::$bHttps = true;
		} else {
			self::$bHttps = false;
		}

		if (isset($_POST['newdomain']) && !empty($_POST['newdomain'])) {
			self::$sNewDomain = $_POST['newdomain'];
			self::$bUpdate = true;
		} else {
			self::$sNewDomain = "";
			self::$bUpdate = false;
		}
	}

	static private function processUpdate() {
		if (self::$bUpdate === true) {
			$sBlogsCount = count(self::$aBlogs);

			self::$iExecTime = 0;
			$iTimePre = microtime(true);

			foreach (self::$aBlogs as $sTablePrefixID => $oBlog) {
				$sOldDomain = $oBlog['url'];
				$sTablePrefix = self::$sTablePrefix;

				if ($sTablePrefixID != '1') {
					$sTablePrefix = $sTablePrefix . $sTablePrefixID . '_';
				}

				if ($oBlog['https']) {
					$sOriginalHttp = 'https://';
				} else {
					$sOriginalHttp = 'http://';
				}

				if (self::$bHttps === true) {
					$sNewHttp = 'https://';
				} else {
					$sNewHttp = 'http://';
				}

				$sOriginalURL = $sOriginalHttp . $sOldDomain;
				$sNewURL = $sNewHttp . self::$sNewDomain;
				$sTable = 'options';
				self::$oConnection->query("UPDATE " . $sTablePrefix . $sTable . " SET option_value = replace(option_value, '" . $sOriginalURL . "', '" . $sNewURL . "') WHERE option_name = 'home' OR option_name = 'siteurl'");
				self::$aAffectedRows[$sTablePrefix . $sTable] = self::$oConnection->affected_rows;

				$sTable = 'posts';
				self::$oConnection->query("UPDATE " . $sTablePrefix . $sTable . " SET guid = replace(guid, '" . $sOriginalURL . "', '" . $sNewURL . "');");
				self::$aAffectedRows[$sTablePrefix . $sTable] = self::$oConnection->affected_rows;

				$sTable = 'posts';
				self::$oConnection->query("UPDATE " . $sTablePrefix . $sTable . " SET post_content = replace(post_content, '" . $sOriginalURL . "', '" . $sNewURL . "');");
				self::$aAffectedRows[$sTablePrefix . $sTable] = self::$oConnection->affected_rows;

				$sTable = 'postmeta';
				self::$oConnection->query("UPDATE " . $sTablePrefix . $sTable . " SET meta_value = replace(meta_value,'" . $sOriginalURL . "', '" . $sNewURL . "');");
				self::$aAffectedRows[$sTablePrefix . $sTable] = self::$oConnection->affected_rows;
			}

			if ($sBlogsCount > 1 && self::$bIsMultisite === true) {
				$sTable = 'blogs';
				self::$oConnection->query("UPDATE " . self::$sTablePrefix . $sTable . " SET domain = replace(domain,'" . self::removeWWW(self::$aBlogs[1]['url']) . "', '" . self::removeWWW(self::$sNewDomain) . "');");
				self::$aAffectedRows[self::$sTablePrefix . $sTable] = self::$oConnection->affected_rows;
			}

			$iTimePost = microtime(true);
			self::$iExecTime = round($iTimePost - $iTimePre, 2);
		}

		// Update blog information for display
		self::getBlogs();
	}

	static private function extractString($string, $start, $end) {
		$string = " " . $string;
		$ini = strpos($string, $start);
		if ($ini == 0)
			return "";
		$ini += strlen($start);
		$len = strpos($string, $end, $ini) - $ini;

		return substr($string, $ini, $len);
	}

	static private function getBlogs() {
		$oTableExists = self::$oConnection->query("SHOW TABLES LIKE 'wp_blogs';");

		if ($oTableExists->num_rows === 0) {
			self::$bIsMultisite = false;
		} else {
			self::$bIsMultisite = true;
		}

		$oResult = self::$oConnection->query("SHOW TABLES LIKE 'wp_%options';");

		$aTables = array();
		while ($oRow = $oResult->fetch_object()) {
			$aTables[] = current(get_object_vars($oRow));
		}

		foreach ($aTables as $sTable) {
			$oRes = self::$oConnection->query("SELECT option_value FROM " . $sTable . " where option_name = 'siteurl';")->fetch_object();
			$sBlogID = self::getBlogID($sTable);
			$aURL = parse_url($oRes->option_value);
			self::$aBlogs[$sBlogID]['url'] = $aURL['host'];
			self::$aBlogs[$sBlogID]['https'] = ($aURL['scheme'] === 'https') ? 1 : 0;
		}
	}

	static private function getBlogID($sTableName) {
		$iResult = preg_match('/^wp_(\d+)_options$/', $sTableName, $aMatches);

		// If no number found it means table is wp_options and gets id 1
		if ($iResult == 0) {
			return '1';
		}

		return $aMatches[1];
	}

	static private function removeWWW($sDomain) {
		if (substr($sDomain, 0, 4) === 'www.') {
			return substr($sDomain, 4);
		}

		return $sDomain;
	}
}