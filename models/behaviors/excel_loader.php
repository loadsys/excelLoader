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
	 * Initiate My Behavior
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
			'path' => APP.'libs'.DS.'excel_adapters'
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
	public function extract(&$model, $file = null) {
		if (!$file) {
			return false;
		}
		$args = array_slice(func_get_args(), 2);
		$data = $this->_parseFile($file);
		$spreadsheet = array();
		$ret = false;
		$sheet = 0;
		while ($data->rowcount($sheet) > 0) {
			$spreadsheet[$sheet] = $this->_toArray($data, $sheet));
			$sheet++;
		}
		$adapter =& $this->_getInstance($this->settings[$model->alias]);
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
	 * Converts an instance of the Spreadsheet_Excel_Reader into
	 * an associative array of sheets/rows/columns.
	 * 
	 * @access public
	 * @param mixed $data
	 * @param int $sheet (default: 0)
	 * @return void
	 */
	public function _toArray($data, $sheet = 0) {
		$arr = array();
		for ($row = 1; $row <= $data->rowcount($sheet); $row++) {
			for ($col = 1; $col <= $data->colcount($sheet); $col++) {
				$arr[$row - 1][$col - 1] = $data->val($row, $col, $sheet);
			}
		}
		return $arr;
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
	public function _parseFile($file) {
		$data = new Spreadsheet_Excel_Reader($file, true);
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

}

?>