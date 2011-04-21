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
	
	public function extract($file = null) {
		if (!$file) {
			return array();
		}
		$args = func_get_args();
	
	}

}

?>