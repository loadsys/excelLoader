<?php

class DefaultExcelAdapter {

	/**
	 * Method called by the excel_loader behavior. The first param
	 * is the spreadsheet file that was run through the excel_reader2.php
	 * vendor class. The args is a hash table of arguments that were passed
	 * to the ExcelLoaderBehavior::extract(), except for the first
	 * since that was the file to parse.
	 * 
	 * @access public
	 * @param mixed $spreadsheet
	 * @param array $args (default: array())
	 * @return void
	 */
	public function extract($spreadsheet, $args = array()) {
	
	}
	
}

?>