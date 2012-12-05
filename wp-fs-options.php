<?php
/**
 * WordPress FS Options
 * Saves/Caches the whole WordPress options DB table into neat files, for horrifying fast read/write actions ;)
 *
 * @version 0.1
 * @author Fabian Wolf
 * @link http://usability-idealist.de/
 * 
 * Initialization Params
 * array(	'options_file' => '/path/to/options-file.php', 'lockfile_timeout' => 5, 'option_set' = array('option_key' => 'option_value', ...) )
 * 
 * 
 * Lock-File struct:
 * - php-header
 * - array( 'file' => 'file_name', 'author_id' => 0, 'timeout' => timestamp)
 */
 
class WP_FS_Options {
	var $strOptionsFile, 
		$iLockFileTimeout, 
		$arrOptions,
		$classVersion = 0.1,
		$className = 'WordPress FS Options';
	
	function __construct( $arrParams = array() ) {
		
		if( !isset($arrParams['options_file']) ) {
			$strError = sprintf('<p>Error: %s file not set.</p>', $this->className );
			if( defined('WP_DEBUG') != false) {
				echo $strError;
			}
			return;
		} else {
			$this->strOptionsFile = $arrParams['options_file'];
		}
		
		$this->iLockFileTimeout = (isset($arrParams['lockfile_timeout'] != false && is_int($arrParams['lockfile_timeout']) != false ? $arrParams['lockfile_timeout'] : 10 ); // default lock timeout: 10 seconds
		
		
		
		
		// check whether given path points to an actual file or something else
		$strFileType = filetype( $this->strOptionsFile);
		
		if( $strFileType == 'file' ) {
			
			// load options file if existing, else use defaults
			if(file_exists($this->strOptionsFile) != false) {
				include( $this->strOptionsFile );
				if( isset( $arrOptions) != false && is_assoc( $arrOptions) != false ) {
					$this->arrOptions = $arrOptions;
				}
			} else {
				// check if directory is write-able
				$strPath = str_replace(basename( $this->strOptionsFile ), '', $this->strOptionsFile);
				if( substr( fileperms( $strPath ), -4) < 0755 ) { // try changing the permissions
					chmod( $strPath, 0775) or die( sprintf('%s: Could not change file permissions for the options file!', $this->className) );
				}
				// check if file is write-able
				
				if( substr( fileperms( $this->strOptionsFile ), -4) < 0755 ) { // try changing the permissions
					chmod( $this->strOptionsFile, 0775) or die( sprintf('%s: Could not change file permissions for the options file!', $this->className) );
				}
			
				// create file
			}
		} else {
			
			die( sprintf('%s: Options file got the wrong file type (%s)!', $this->className, $strFileType) );
		}
	}
	
	public function add_option( $option_key, $option_value = null ) {
		$return = false;
		
		
		if( !array_key_exists( $option_key, $this->arrOptions) ) {
			$this->arrOptions[$option_key] = $option_value;
			$result = $this->save_options();
			
			if($result['type'] == 'success') {
				$return = true;
			}
		}
		
		return $return;
	}
	
	public function update_option( $option_key, $option_value = null ) {
		$return = false;
		
		if( !array_key_exists( $option_key, $this->arrOptions) ) {
			$return = $this->add_option( $option_key, $option_value );
		} elseif( array_key_exists( $option_key, $this->arrOptions) != false && $this->arrOptions[$option_key] != $option_value) {
			$this->arrOptions[$option_key] = $option_value;
			$result = $this->save_options();
			
			if($result['type'] == 'success') {
				$return = true;
			}
		}
		
		return $return;
	}
	
	public function get_option( $option_key, $default_value = false ) {
		$return = $default_value;
		
		if( array_key_exists( $option_key, $this->arrOptions) != false) {
			$return = $this->arrOptions[$option_key];
		}
		
		return $return;
	}
	
	public function delete_option( $option_key = null ) {
		$return = false;
		
		if( !empty($option_key) && array_key_exists($option_key, $this->arrOptions) != false ) {
			unset($this->arrOptions[$option_key]);
			$result = $this->save_options();
			
			if($result['type'] == 'success') {
				$return = true;
			}
		}
		
		return $return;
	}
	
	/**
	 * Updates the options file
	 */
	
	private function save_options() {
		$return = array('type' => 'error', 'message' => 'Could not update options file because somebody else (%author%) is already editing it. File will be update-able again in %timeout% seconds.');
		
		// check if lock is set
		if( !$this->is_file_locked( $this->strOptionsFile ) ) {
			
			// compile data
			$strOptionsMeta = '<' . '? ' . '/'. '*'. ' Automatically generated options file by ' . $this->className . ' v' . $this->classVersion . '. Please do not edit manually.' . '*' . '/';
			
			foreach($arrOption as $strOptionKey => $optionValue) {
				$arrOptionsData[] = $strOptionKey . " = '" . maybe_serialize( $optionValue ) . "'";
			}
			$strOptionsData = '$arrOptions = array(' .implode(", \n", $arrOptionsData) . ');';
			
			
			// write options
			$rOptionsFile = fopen( $this->strOptionsFile, 'w' );
			/** TODO: implement error handling if file could not be opened */
			
			fwrite($rOptionsFile, $strOptionsMeta . "\n\n" . $arrOptionsData);
			fclose($rOptionsFile);
			
			$return['type'] = 'success';
			$return['message'] = 'Options file has been updated successfully.';
			
			
		} else { // file is being edited by somebody else
			$arrLockData = $this->get_lock_data( $this->strOptionsFile );
			$author = get_user_by('id', $arrLockData['author_id']);
			
			$return['message'] = str_replace(
				array('%author%', '%timeout%', ),
				array($author->first_name . ' ' . $author->last_name, $arrLockData['timeout']),
				$return['message']
			);
			$return['data'] = $arrLockData;
		}
		
		return $return;
	}
	
/**
 * Class-internal concurrent write mechanisms
 */
	
	/**
	 * Tests if the given file is currently being edited. Returns true if so, else false.
	 * 
	 * @param string $filename  Full path of the file to be tested.
	 * @return bool $status		Returns true if locked, false if not.
	 */
	
	public function is_file_locked( $file ) {
		$return = false;
		
		if( file_exist( $file ) != false ) { // no check for imaginary files
			$strLockFile = basename($file, '.php') . '.lock.php';
			
			// lock exists
			if( file_exists( $strLockFile ) != false) {
				// check if locked is timed out
				include_once( $strLockFile );
				
				// check if lock is timed out
				if(isset($arrLockData) != false && isset($arrLockData['timeout']) != false && $arrLockData['timeout'] < time() - $arrLockData['timeout'] ) {
					$this->unlock_file( $file );
				}
				
			}
		}
		
		return $return;
	}
	
	public function get_lock_data( $file ) {
		$return = false;
		
		if( file_exist( $file ) != false ) { // no check for imaginary files
			$strLockFile = basename($file, '.php') . '.lock.php';
			
			if( file_exist($strLockFile) != false ) {
				include( $strLockFile );
				if(isset($arrLockData) != false) {
					$return = $arrLockData;
				}
			}
		}
		
		return $return;
	}
	
	
	
	private function lock_file( $file, $author_id = 0 ) {
		$return = false;
		
		$strLockFile = $file;
		if(stripos( $file, '.lock.php' === false) { // better safe than sorry
			$strLockFile = basename($file, '.php') . '.lock.php';
		}
		
			$strLockHeader = '<' . '? ' . '/* automatically generated lock-file by ' . $this->className . ' v' . $this->classVersion . '. Please do not modify. Timeout is in ' . $timeout . ' seconds (' . date('Y-m-d H:i:s', time() + ) . '). */';
		if(file_exists($strLockFile) != false) {
			
			$iLockTimeout = time() + $this->iLockFileTimeout;
			$iCurrentUserID = get_current_user_id();
			
			$strLockMeta = '$arrLockData = array' . "('author_id' => '$iCurrentUserID', 'timeout' => $iLockTimeout)";
			
			
			$lock = fopen( $strLockFile, 'w');
		
			fwrite($lock, $strLockMeta);
			fclose($lock);
		}
		
		return $return;
	} 
	
	private function unlock_file( $file, $author_id = 0 ) {
		$return = false;
		
		$strLockFile = $file;
		if(stripos( $file, '.lock.php' === false) { // better safe than sorry
			$strLockFile = basename($file, '.php') . '.lock.php';
		}
		
		if(file_exists($strLockFile) != false) {
			include( $strLockFile );
			if(isset($arrLockData) != false && $arrLockData['timeout'] < time() ) {
				$return = unlink( $strLockFile );
			}
		}
		
		return $return;
	}

}


/**
 * Helper functions
 */

/**
 * is_assoc WITH empty() checking
 * @see http://de3.php.net/manual/en/function.is-array.php#102652
 */

if( !function_exists('is_assoc') ) {
	function is_assoc ($arr) {
		return (is_array($arr) && (!count($arr) || count(array_filter(array_keys($arr),'is_string')) == count($arr)));
	}
}
