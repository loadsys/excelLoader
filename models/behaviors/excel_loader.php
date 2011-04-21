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
	public $mapMethods = array(
		'/^excel(.)*/' => '_call'
	);
	
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
	 * excelLoad function.
	 * 
	 * @access public
	 * @param mixed &$model
	 * @param mixed $file (default: null)
	 * @return void
	 */
	public function excelLoad(&$model, $file = null) {
	
	}
	
	/**
	 * _call function.
	 * 
	 * @access public
	 * @param mixed &$model
	 * @param mixed $file (default: null)
	 * @return void
	 */
	public function _call(&$model, $method, $file = null) {
		if (!$file) {
			return false;
		}
		$method = Inflector::variable(preg_replace('/^excel/', '', $method));
		$args = array_slice(func_get_args(), 3);
		$data = $this->_parseFile($file);
		$spreadsheet = array();
		$ret = false;
		$sheet = 0;
		while ($data->rowcount($sheet) > 0) {
			$spreadsheet[$sheet] = $this->_toArray($data, $sheet));
			$sheet++;
		}
		$adapter =& $this->_getInstance($this->settings[$model->alias]);
		if (method_exists($adapter, 'before'.Inflector::camelize($method))) {
			$continue = $adapter->{'before'.Inflector::camelize($method)}($args);
		}
		if ($continue !== false) {
			if (method_exists($adapter, $method)) {
				$ret = $adapter->{$method}($spreadsheet, $args);
			}
			if (method_exists($adapter, 'after'.Inflector::camelize($method))) {
				$ret = $adapter->{'after'.Inflector::camelize($method)}($ret, $args);
			}
		}
		return $ret;
	}
	
	/**
	 * _toArray function.
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
	 * _parseFile function.
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
	 * _getInstance function.
	 * 
	 * @access public
	 * @param mixed $adapter
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