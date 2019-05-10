<?php
function includePhpFile($include_path){
	ob_start();
	include($include_path);
	$result = ob_get_contents();
	ob_end_clean();
	return $result;
}

function getApplicationVersion(){
	$version_log = $_SERVER['DOCUMENT_ROOT'].'/version_log.txt';
	$f = @fopen($version_log,'r'); 
	$line = @strtoupper(@fgets($f)); 
	@fclose($f); 
	
	$replace_array = array('VERSION','-','#');
	$version = @trim(@str_replace($replace_array,'',$line));
	return $version;
}

function selectionBoxOptions($array_indexed, $text_column, $selected_value, $header_option){
	if(strlen($header_option)>0){
		?><option selected="selected" value=""><?=$header_option?></option><?
	}
	
	if(is_array($array_indexed)){
		foreach($array_indexed as $value => $value_array){
			$text = $value_array[$text_column];
			
			if($selected_value && $value==$selected_value){
				?><option value="<?=$value?>" selected="selected"><?=$text?></option><?
			}else{
				?><option value="<?=$value?>"><?=$text?></option><?
			}
		}
	}
}

function indexAssocArray($array, $index_columns, $re_index=true){
	if(is_array($array) && is_array($index_columns)){
		$levels = count($index_columns);
		
		if($levels<=3){
			foreach($array as $row => $details){
				unset($keys);
				foreach($index_columns as $index_column_name){
					$keys[] = $details[$index_column_name];
				}

				if($re_index==true){
					if($levels==1){
						$result[$keys[0]][] = $details;
					}elseif($levels==2){
						$result[$keys[0]][$keys[1]][] = $details;
					}elseif($levels==3){
						$result[$keys[0]][$keys[1]][$keys[2]][] = $details;
					}
				}else{
					if($levels==1){
						$result[$keys[0]] = $details;
					}elseif($levels==2){
						$result[$keys[0]][$keys[1]] = $details;
					}elseif($levels==3){
						$result[$keys[0]][$keys[1]][$keys[2]] = $details;
					}
				}
			}
			
			return $result;
		}else{
			return NULL;
		}
	}else{
		return NULL;
	}
}

function retrieveArrayValues($array, $column_name){
	if(is_array($array)){
		foreach($array as $row => $details){
			$array_values[] = $details[$column_name];
		}
	}
	return $array_values;
}

function arrayMerge($master_array){
	if(is_array($master_array)){
		$merged_array = array();
		foreach($master_array as $sub_array){
			if(is_array($sub_array)){
				$merged_array = array_merge($merged_array, $sub_array);
			}
		}
	}
	return $merged_array;
}

function createSqlInString($array,$column){
	$in_string = NULL;
	
	if(is_array($array)){
		foreach($array as $row => $v1){
			$value = $array[$row][$column];
			if(isset($value) && strpos($in_string,$value)===false){
				$in_string .= "'".$value."',";
			}
		}
		
		if(strlen($in_string)>0){
			$in_string = substr($in_string,0,strlen($in_string)-1);
		}
	}
	
	return ($in_string) ? $in_string : "''";
}

function cleanStringOracle($string,$max_chars = 0){
	if($max_chars > 0){
		$string = substr($string,0,$max_chars);
	}
	
	$filtered_characters = array('\\','/','"',"'","\r\n");
	return str_replace($filtered_characters,'',$string);
}
function cleanStringHtml($string,$max_chars = 0){
	if($max_chars > 0){
		$string = substr($string,0,$max_chars);
	}
	
	$filtered_characters = array('');
	return str_replace($filtered_characters,'',nl2br($string));
}
function str_replace_first($search, $replace, $subject){
    $search = '/'.preg_quote($search,'/').'/';
    return preg_replace($search,$replace,$subject,1);
}
function wrapText($string, $max_length=50){
	$wrapped_sting =  wordwrap($string,$max_length);
	return explode("\n", $wrapped_sting);
}

function getShift($date){
	$int_time = intval(date('Hi',strtotime($date)));
	if($int_time>=746 && $int_time<=1545){
		return 'days';
	}elseif($int_time>=1546 && $int_time<=2345){
		return 'afternoons';
	}else{
		return 'nights';
	}
}

function getDirContents($dir, $include_subfolders=true, $filter=array(), &$results=array()){
	$files = array_diff(scandir($dir), array('..', '.'));
	if($files){
		foreach($files as $key => $value){
			$path = realpath($dir.DIRECTORY_SEPARATOR.$value);

			if(!is_dir($path)){
				if($filter){
					foreach($filter as $filter_value){
						if(strpos($value,$filter_value)!==false){
							$results[] = $path;
						}
					}
				}else{
					$results[] = $path;
				}
			}else{
				if($include_subfolders==true) getDirContents($path, $include_subfolders, $filter, $results);
			}
		}
	}
	
	return $results;
}

?>
