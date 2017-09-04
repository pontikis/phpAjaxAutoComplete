<?php

/**
 * Class phpAjaxAutoComplete
 *
 * php ajax call to create autocomplete control
 * https://github.com/pontikis/phpAjaxAutoComplete
 *
 * @author     Christos Pontikis http://pontikis.net
 * @copyright  Christos Pontikis
 * @license    MIT http://opensource.org/licenses/MIT
 * @version    0.7.1 (XX XXX 2017)
 *
 */
class phpAjaxAutoComplete {

	// arguments - no getters available (?)
	private $ds;

	// options - no getters available (?)

	// required
	private $term;
	private $select_sql;
	private $parts_where_sql;
	private $order_sql;

	// optional
	private $strings;
	private $trim_term;
	private $replace_multiple_spaces_with_one;
	private $preg_match_pattern;
	private $msg_invalid_characters_in_term;
	private $fixed_where_sql;
	private $fixed_bind_params;
	/**
	 * @var string|false a valid delimeter or false
	 */
	private $term_parts_delimiter;
	/**
	 * @var integer|string a valid integer >= 2 or 'any'
	 */
	private $term_parts_max;
	/**
	 * @var string one of: "text_contains_term' (default), 'text_starts_with_term'
	 */
	private $term_comparison_operator;

	private $highlight_results;


	// getters available (?)
	private $last_error;


	/**
	 * phpAjaxAutoComplete constructor.
	 * @param dacapo $ds
	 * @param array $options
	 */
	public function __construct(dacapo $ds, $options = array()) {

		// initialize ----------------------------------------------------------
		$this->ds = $ds;

		// options -------------------------------------------------------------
		$defaults = array(
			'strings' => array(
				'invalid_preg_match_pattern' => 'Match pattern is invalid',
				'term_contains_invalid_characters' => 'Term contains invalid characters',
				'invalid_parameter' => 'Invalid parameter',
				'error_executing_query' => 'Error executing query'
			),
			'trim_term' => true,
			'replace_multiple_spaces_with_one' => true,
			'preg_match_pattern' => '',
			'fixed_where_sql' => array(),
			'fixed_bind_params' => array(),
			'term_parts_delimiter' => ' ',
			'term_parts_max' => 'any',
			'term_comparison_operator' => 'text_contains_term',
			'highlight_results' => true
		);

		$opt = array_merge($defaults, $options);

		// required
		$this->term = $opt['term'];
		$this->select_sql = $opt['select_sql'];
		$this->parts_where_sql = $opt['parts_where_sql'];
		$this->order_sql = $opt['order_sql'];

		// optional
		$this->strings = $opt['strings'];
		$this->trim_term = $opt['trim_term'];
		$this->replace_multiple_spaces_with_one = $opt['replace_multiple_spaces_with_one'];
		$this->preg_match_pattern = $opt['preg_match_pattern'];
		$this->msg_invalid_characters_in_term = $opt['msg_invalid_characters_in_term'];
		$this->fixed_where_sql = $opt['fixed_where_sql'];
		$this->fixed_bind_params = $opt['fixed_bind_params'];
		$this->term_parts_delimiter = $opt['term_parts_delimiter'];
		$this->term_parts_max = $opt['term_parts_max'];
		$this->term_comparison_operator = $opt['term_comparison_operator'];
		$this->highlight_results = $opt['highlight_results'];

		// error ---------------------------------------------------------------
		$this->last_error = null;
	}


	// public functions - getters ----------------------------------------------

	public function getLastError() {
		return $this->last_error;
	}

	// public functions - setters ----------------------------------------------

	/**
	 * Set option
	 *
	 * @param $opt
	 * @param $val
	 */
	public function setOption($opt, $val) {
		$this->$opt = $val;
	}

	// public functions - main methods -----------------------------------------

	/**
	 * @return array
	 */
	public function createList() {

		$a_res = array();

		// trim term
		if($this->trim_term) {
			$this->term = trim($this->term);
		}

		// replace multiple spaces with one
		if($this->replace_multiple_spaces_with_one) {
			$this->term = preg_replace('/\s+/', ' ', $this->term);
		}

		// sanitize user input
		if($this->preg_match_pattern) {
			$match = preg_match($this->preg_match_pattern, $this->term);
			if($match === false) {
				$msg_invalid = $this->strings['invalid_preg_match_pattern'];
				array_push($a_res, array("value" => $this->term, "label" => $msg_invalid));
				$this->last_error = __METHOD__ . ' ' . $msg_invalid;
				return $a_res;
			} else {
				if($match) {
					$msg_invalid = $this->msg_invalid_characters_in_term ? $this->msg_invalid_characters_in_term : $this->strings['term_contains_invalid_characters'];
					array_push($a_res, array("value" => $this->term, "label" => $msg_invalid));
					$this->last_error = __METHOD__ . ' ' . $msg_invalid;
					return $a_res;
				}
			}
		}

		// create where_sql
		$a_where_sql = array();
		$where_sql = '';
		$bind_params = array();

		foreach($this->fixed_where_sql as $criterion) {
			array_push($a_where_sql, $criterion);
		}
		foreach($this->fixed_bind_params as $bind_param) {
			array_push($bind_params, $bind_param);
		}

		// calculate term parts
		$sqlPlaceHolder = $this->ds->getSqlPlaceholder();

		if($this->term_parts_delimiter) {

			if($this->term_parts_max == 'any') {
				$parts = explode($this->term_parts_delimiter, $this->term);
				$part_occurences = substr_count($this->parts_where_sql[0], $sqlPlaceHolder);
				foreach($parts as $part) {
					array_push($a_where_sql, $this->parts_where_sql[0]);
					for($i = 1; $i <= $part_occurences; $i++) {
						array_push($bind_params, $this->_create_term_sql($part));
					}
				}
			} else {
				if($this->_is_positive_integer($this->term_parts_max, 2) && $this->term_parts_max >= 2) {
					$parts = explode($this->term_parts_delimiter, $this->term, (int)$this->term_parts_max);
					foreach($parts as $key => $part) {
						array_push($a_where_sql, $this->parts_where_sql[$key]);
						$part_occurences = substr_count($this->parts_where_sql[$key], $sqlPlaceHolder);
						for($i = 1; $i <= $part_occurences; $i++) {
							array_push($bind_params, $this->_create_term_sql($part));
						}
					}
				} else {
					$msg_invalid = $this->strings['invalid_parameter'] . ' (term_parts_max)';
					array_push($a_res, array("value" => $this->term, "label" => $msg_invalid));
					$this->last_error = __METHOD__ . ' ' . $msg_invalid;
					return $a_res;
				}
			}
		} else {
			$parts = array($this->term);
			array_push($a_where_sql, $this->parts_where_sql[0]);
			$part_occurences = substr_count($this->parts_where_sql[0], $sqlPlaceHolder);
			for($i = 1; $i <= $part_occurences; $i++) {
				array_push($bind_params, $this->_create_term_sql($this->term));
			}
		}

		$filters_all = count($a_where_sql);
		if($filters_all) {
			$where_sql = 'WHERE ';
		}
		foreach($a_where_sql as $key => $filter) {
			$where_sql .= $filter;
			if($key < $filters_all - 1)
				$where_sql .= ' AND ';
		}

		$sql = $this->select_sql . ' ' . $where_sql . ' ' . $this->order_sql;
		$res = $this->ds->select($sql, $bind_params);
		if(!$res) {
			trigger_error('WRONG SQL: ' . $sql . ' ERROR:' . $this->ds->getLastError(), E_USER_NOTICE);
			$msg_invalid = $this->strings['error_executing_query'];
			array_push($a_res, array("value" => $this->term, "label" => $msg_invalid));
			return $a_res;
		}
		$a_res = $this->ds->getData();

		// highlight results
		if($a_res) {
			if($this->highlight_results) {
				$a_res = $this->_apply_highlight($a_res, $parts);
			}
		}

		return $a_res;
	}


	// private functions -------------------------------------------------------

	/**
	 * @param $term
	 * @return bool|string
	 */
	private function _create_term_sql($term) {

		switch($this->term_comparison_operator) {
			case 'text_contains_term':
				return '%' . $this->_removeAccents(mb_strtolower($term)) . '%';
				break;
			case 'text_starts_with_term':
				return $this->_removeAccents(mb_strtolower($term)) . '%';
				break;
			default:
				return false;
				break;
		}

	}


	/**
	 * Replace accented characters with non accented
	 *
	 * @param $str
	 * @return mixed
	 * @link http://myshadowself.com/coding/php-function-to-convert-accented-characters-to-their-non-accented-equivalant/
	 */
	private function _removeAccents($str) {
		$a = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Æ', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ð', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'Ù', 'Ú', 'Û', 'Ü', 'Ý', 'ß', 'à', 'á', 'â', 'ã', 'ä', 'å', 'æ', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'ù', 'ú', 'û', 'ü', 'ý', 'ÿ', 'Ā', 'ā', 'Ă', 'ă', 'Ą', 'ą', 'Ć', 'ć', 'Ĉ', 'ĉ', 'Ċ', 'ċ', 'Č', 'č', 'Ď', 'ď', 'Đ', 'đ', 'Ē', 'ē', 'Ĕ', 'ĕ', 'Ė', 'ė', 'Ę', 'ę', 'Ě', 'ě', 'Ĝ', 'ĝ', 'Ğ', 'ğ', 'Ġ', 'ġ', 'Ģ', 'ģ', 'Ĥ', 'ĥ', 'Ħ', 'ħ', 'Ĩ', 'ĩ', 'Ī', 'ī', 'Ĭ', 'ĭ', 'Į', 'į', 'İ', 'ı', 'Ĳ', 'ĳ', 'Ĵ', 'ĵ', 'Ķ', 'ķ', 'Ĺ', 'ĺ', 'Ļ', 'ļ', 'Ľ', 'ľ', 'Ŀ', 'ŀ', 'Ł', 'ł', 'Ń', 'ń', 'Ņ', 'ņ', 'Ň', 'ň', 'ŉ', 'Ō', 'ō', 'Ŏ', 'ŏ', 'Ő', 'ő', 'Œ', 'œ', 'Ŕ', 'ŕ', 'Ŗ', 'ŗ', 'Ř', 'ř', 'Ś', 'ś', 'Ŝ', 'ŝ', 'Ş', 'ş', 'Š', 'š', 'Ţ', 'ţ', 'Ť', 'ť', 'Ŧ', 'ŧ', 'Ũ', 'ũ', 'Ū', 'ū', 'Ŭ', 'ŭ', 'Ů', 'ů', 'Ű', 'ű', 'Ų', 'ų', 'Ŵ', 'ŵ', 'Ŷ', 'ŷ', 'Ÿ', 'Ź', 'ź', 'Ż', 'ż', 'Ž', 'ž', 'ſ', 'ƒ', 'Ơ', 'ơ', 'Ư', 'ư', 'Ǎ', 'ǎ', 'Ǐ', 'ǐ', 'Ǒ', 'ǒ', 'Ǔ', 'ǔ', 'Ǖ', 'ǖ', 'Ǘ', 'ǘ', 'Ǚ', 'ǚ', 'Ǜ', 'ǜ', 'Ǻ', 'ǻ', 'Ǽ', 'ǽ', 'Ǿ', 'ǿ', 'Ά', 'ά', 'Έ', 'έ', 'Ό', 'ό', 'Ώ', 'ώ', 'Ί', 'ί', 'ϊ', 'ΐ', 'Ύ', 'ύ', 'ϋ', 'ΰ', 'Ή', 'ή');
		$b = array('A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'D', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'Y', 's', 'a', 'a', 'a', 'a', 'a', 'a', 'ae', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'y', 'y', 'A', 'a', 'A', 'a', 'A', 'a', 'C', 'c', 'C', 'c', 'C', 'c', 'C', 'c', 'D', 'd', 'D', 'd', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'E', 'e', 'G', 'g', 'G', 'g', 'G', 'g', 'G', 'g', 'H', 'h', 'H', 'h', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'I', 'i', 'IJ', 'ij', 'J', 'j', 'K', 'k', 'L', 'l', 'L', 'l', 'L', 'l', 'L', 'l', 'l', 'l', 'N', 'n', 'N', 'n', 'N', 'n', 'n', 'O', 'o', 'O', 'o', 'O', 'o', 'OE', 'oe', 'R', 'r', 'R', 'r', 'R', 'r', 'S', 's', 'S', 's', 'S', 's', 'S', 's', 'T', 't', 'T', 't', 'T', 't', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'W', 'w', 'Y', 'y', 'Y', 'Z', 'z', 'Z', 'z', 'Z', 'z', 's', 'f', 'O', 'o', 'U', 'u', 'A', 'a', 'I', 'i', 'O', 'o', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'U', 'u', 'A', 'a', 'AE', 'ae', 'O', 'o', 'Α', 'α', 'Ε', 'ε', 'Ο', 'ο', 'Ω', 'ω', 'Ι', 'ι', 'ι', 'ι', 'Υ', 'υ', 'υ', 'υ', 'Η', 'η');
		return str_replace($a, $b, $str);
	}


	/**
	 * mb_stripos all occurences
	 * based on http://www.php.net/manual/en/function.strpos.php#87061
	 *
	 * Find all occurrences of a needle in a haystack
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return array|false
	 */
	private function _mb_stripos_all($haystack, $needle) {

		$s = 0;
		$i = 0;

		while(is_integer($i)) {

			$i = mb_stripos($haystack, $needle, $s);

			if(is_integer($i)) {
				$aStrPos[] = $i;
				$s = $i + mb_strlen($needle);
			}
		}

		if(isset($aStrPos)) {
			return $aStrPos;
		} else {
			return false;
		}
	}


	/**
	 * Apply highlight to row label
	 *
	 * @param array $a_json json data array
	 * @param array $parts strings to search
	 * @param string $hl_class highlight class
	 * @param bool $accent_insensitive
	 * @return array
	 */
	private function _apply_highlight($a_json, $parts, $hl_class = 'bg-primary', $accent_insensitive = true) {

		$p = count($parts);
		$rows = count($a_json);

		for($row = 0; $row < $rows; $row++) {

			$label = $a_json[$row]["label"];
			if($accent_insensitive) {
				$label_no_accents = $this->_removeAccents($label);
			}
			$a_label_match = array();

			for($i = 0; $i < $p; $i++) {

				$part_len = mb_strlen($parts[$i]);
				if($accent_insensitive) {
					$a_match_start = $this->_mb_stripos_all($label_no_accents, removeAccents($parts[$i]));
				} else {
					$a_match_start = $this->_mb_stripos_all($label, $parts[$i]);
				}

				if(is_array($a_match_start)) {
					foreach($a_match_start as $part_pos) {

						$overlap = false;
						foreach($a_label_match as $pos => $len) {
							if($part_pos - $pos >= 0 && $part_pos - $pos < $len) {
								$overlap = true;
								break;
							}
						}
						if(!$overlap) {
							$a_label_match[$part_pos] = $part_len;
						}

					}
				}
			}

			if(count($a_label_match) > 0) {
				ksort($a_label_match);

				$label_highlight = '';
				$start = 0;
				$label_len = mb_strlen($label);

				foreach($a_label_match as $pos => $len) {
					if($pos - $start > 0) {
						$no_highlight = mb_substr($label, $start, $pos - $start);
						$label_highlight .= $no_highlight;
					}
					$highlight = '<span class="' . $hl_class . '">' . mb_substr($label, $pos, $len) . '</span>';
					$label_highlight .= $highlight;
					$start = $pos + $len;
				}

				if($label_len - $start > 0) {
					$no_highlight = mb_substr($label, $start);
					$label_highlight .= $no_highlight;
				}

				$a_json[$row]["label"] = $label_highlight;
			}

		}
		return $a_json;

	}


	/**
	 * Check if expression is positive integer
	 *
	 * @param $str
	 * @param $length
	 * @return bool
	 */
	private function _is_positive_integer($str, $length) {
		// allow only digits
		if(preg_match("/[^\pN]/u", $str)) {
			return false;
		}
		// allow only positive values
		if($str == 0) {
			return false;
		}
		// check integer length
		if(strlen($str) > $length) {
			return false;
		}
		return true;
	}

}