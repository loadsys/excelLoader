<?php
/**
 * ExcelLoader Behavior
 * 
 * Loads custom adapters that define the extraction of data from a excel spreadsheet
 *
 * @package default
 * @author Joey Trapp
 * @version 1
 * @copyright Loadsys
 **/
class ExcelLoaderBehavior extends ModelBehavior {

	/**
	 * Contains configuration settings for use with individual model objects.
	 * Individual model settings should be stored as an associative array, 
	 * keyed off of the model name.
	 *
	 * @var array
	 * @access public
	 * @see Model::$alias
	 */
	public $settings = array();

	/**
	 * Allows the mapping of preg-compatible regular expressions to public or
	 * private methods in this class, where the array key is a /-delimited regular
	 * expression, and the value is a class method.  Similar to the functionality of
	 * the findBy* / findAllBy* magic methods.
	 *
	 * @var array
	 * @access public
	 */
	public $mapMethods = array();
	
	/**
	 * Holds instances of already used adapters.
	 * 
	 * @var array
	 * @access public
	 */
	public $instances = array();

	/**
	 * Initiate Behavior
	 *
	 * @param object $model
	 * @param array $config
	 * @return void
	 * @access public
	 */
	public function setup(&$model, $config = array()) {
		$a = $model->alias;
		$defaults = array(
			'adapter' => 'default',
			'path' => APP.'libs'.DS.'excel_adapters',
			'file_path' => APP.'webroot'.DS.'files'
		);
		$this->settings[$a] = Set::merge($defaults, $config);
	}
	
	
	/**
	 * The method that calls extract method on the assigned adapter.
	 * 
	 * @access public
	 * @param mixed &$model
	 * @param string $file
	 * @return void
	 */
	public function extract(&$model, $file = null, $options = array()) {
		$ret = false;
		if (!$file) {
			return $ret;
		}
		$args = array_slice(func_get_args(), 3);
		$settings = $this->settings[$model->alias];
		if (!empty($options)) {
			$settings = Set::merge($settings, $options);
		}
		$data = $this->_parseFile($file);
		if (!$data) {
			return $data;
		}
		$spreadsheet = $this->_toArray($data);
		$adapter =& $this->_getInstance($settings);
		if (method_exists($adapter, 'beforeExtract')) {
			$args = $adapter->beforeExtract($args);
		}
		if ($args !== false) {
			if (method_exists($adapter, 'extract')) {
				$ret = $adapter->extract($spreadsheet, $args);
			}
			if (method_exists($adapter, 'afterExtract')) {
				$ret = $adapter->afterExtract($ret, $args);
			}
		}
		return $ret;
	}
	
	/**
	 * Takes the file path and checks if a file exists. If it doesn't,
	 * method returns false. If it does exist the Spreadsheet_Excel_Reader
	 * is returned.
	 * 
	 * @access public
	 * @param mixed $file
	 * @return void
	 */
	public function _parseFile($file, $settings) {
		$path = $settings['file_path'];
		if (strpos('/', $file) !== false) {
			$pieces = explode('/', $file);
			$file = array_pop($pieces);
			$path = str_replace('//', '/', implode('/', $pieces));
		}
		$file = $file.'/'.$file;
		if (strpos(array('.xls', '.xlsx'), $file) === false) {
			if (file_exists($file.'.xls')) {
				$file .= '.xls';
			} else if (file_exists($file, '.xlsx')) {
				$file .= '.xlsx';
			} else {
				$file = false;
			}
		}
		$data = false;
		if ($file && file_exists($file)) {
			$data = new Spreadsheet_Excel_Reader($file, true);
		}
		return $data;
	}
	
	/**
	 * Retreives an instance of an adapter if the class/file exists.
	 * Will save an instance of an adapter incase it needs to be reused.
	 * 
	 * @access public
	 * @param array $settings
	 * @return void
	 */
	public function _getInstance($settings) {
		extract($settings);
		if (!array_key_exists($adapter, $this->instances)) {
			if (file_exists($path.DS.$adapter.'.php')) {
				require_once($path.DS.$adapter.'.php');
				$this->instances[$instance] = new {ucfirst($adapter).'ExcelAdapter'}();
			}
		}
		return $this->instances[$adapter];
	}
	
	/**
	 * Converts an instance of the Spreadsheet_Excel_Reader into
	 * an associative array of sheets/rows/columns.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function _toArray($data) {
		$arr = array();
		$sheet = 0;
		while ($data->rowcount($sheet) > 0) {
			for ($row = 1; $row <= $data->rowcount($sheet); $row++) {
				for ($col = 1; $col <= $data->colcount($sheet); $col++) {
					$arr[$row - 1][$col - 1] = $data->val($row, $col, $sheet);
				}
			}
			$sheet++;
		}
		return $arr;
	}

}

?>