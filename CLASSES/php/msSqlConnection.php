<?php
class msSqlConnection{
	function __construct($conn_params,$enable_rollback=false){
		if($GLOBALS[$conn_params]){	
			$this->connectionParameters = $GLOBALS[$conn_params];

			$this->sqlLogging = $this->connectionParameters['log_sql'];
			$this->sqlErrorLogOverride = false;
			$this->sqlLogOverride = false;
			$this->queryStack = array();
			$this->transaction_id = strtoupper(uniqid());
			
			$this->conn = @sqlsrv_connect($this->connectionParameters['server'],array("Database"=>$this->connectionParameters['database'], "UID"=>$this->connectionParameters['username'], "PWD"=>$this->connectionParameters['password'],'ReturnDatesAsStrings'=>true));
			
			if(!$this->conn){
				$error = $this->generateSqlError(sqlsrv_errors(),'');
			}else{	
				$this->transaction_result = array(
					'success' 							=> true,
					'error_message' 				=> NULL,
					'query' 								=> 'Database connection opened for '.$this->transaction_id,
					'transaction_id' 				=> $this->transaction_id,
					'connection_parameters'	=> $this->connectionParameters,
					'rolled_back' 					=> false,
					'execution_time'				=> 0
				);
				
				$this->logSqlStatement($this->transaction_result);
				
				
				
				$this->transaction_result = array(
					'success' 							=> true,
					'transaction_id' 				=> $this->transaction_id,
					'connection_parameters'	=> $this->connectionParameters,
					'execution_time'				=> 0
				);
			}
			
		}else{
			$this->generateSqlError(array('message' => 'Unable to connect with the conection parameter "'.$conn_params.'"'),'');
		}
		
		return $this->transaction_result;
	}
	
	
	function closeConnection(){		
		@sqlsrv_close($this->conn);
		$this->transaction_result['query'] = 'Database connection closed for '.$this->transaction_id;
		$this->logSqlStatement($this->transaction_result, true);
		
		return $this->transaction_result;
	}
	
	
	function fetch_data($query, $return_type='set'){
		$start_time = microtime(true);
		
		$query_result = @sqlsrv_query($this->conn,$query);
		if($query_result){
			if($query_result!==true){
				$rowcount = 0;
				while($record_set = @sqlsrv_fetch_array($query_result, SQLSRV_FETCH_ASSOC)){
					foreach($record_set as $column_name => $record_details){
						$data[$rowcount][strtolower($column_name)] = $record_details;
					}
					
					if($return_type=='row' || $return_type=='value'){
						if($return_type=='row') $data = $data[0];
						if($return_type=='value') $data = current($data[0]);
						break;
					}
				
					$rowcount ++;
				}
			}
		}else{
			$error = $this->generateSqlError(@sqlsrv_errors(),$query);
		}

		$execution_time = microtime(true)-$start_time;
		
		$result = array(
			'transaction_id'	=>	$this->transaction_id,
			'success'					=> (!$error) ? true : false,
			'message'					=> $error,
			'query'						=> $query,
			'execution_time'	=> $execution_time,
			'data'						=> $data
		);
		$this->logSqlStatement($result);

		$this->transaction_result['success'] = ($error) ? false : $this->transaction_result['success'];
		$this->transaction_result['error_message'] = ($error) ? $error : $this->transaction_result['error_message'];
		$this->transaction_result['query'] = $query;
		$this->transaction_result['execution_time'] = $this->transaction_result['execution_time'] + $execution_time;

		return $result;
	}
	
	function modify_data($query){
		$start_time = microtime(true);

		$query_result = @sqlsrv_query($this->conn,$query);
		if(!$query_result) $error = $this->generateSqlError(@sqlsrv_errors(),$query);
		
		$execution_time = microtime(true)-$start_time;
		
		$result = array(
			'transaction_id'	=>	$this->transaction_id,
			'success'					=> (!$error) ? true : false,
			'message'					=> $error,
			'query'						=> $query,
			'execution_time'	=> $execution_time,
			'data'						=> $data
		);
		$this->logSqlStatement($result);

		$this->transaction_result['success'] = ($error) ? false : $this->transaction_result['success'];
		$this->transaction_result['error_message'] = ($error) ? $error : $this->transaction_result['error_message'];
		$this->transaction_result['query'] = $query;
		$this->transaction_result['execution_time'] = $this->transaction_result['execution_time'] + $execution_time;

		return $result;
	}
	
	
	function stack_transaction($query){
		$this->queryStack[] = $query."\n";
		
		$result = array(
			'transaction_id'	=>	$this->transaction_id,
			'success'					=> true,
			'stack'						=> true,
			'message'					=> '',
			'query'						=> $query,
			'execution_time'	=> 0,
			'data'						=> ''
		);
		$this->logSqlStatement($result);
		
		return $result;
	}
	
	function execute_stack(){
		$query = '';
		
		if(is_array($this->queryStack)){
			$query .= "BEGIN\n";
				$query .= "DECLARE @last_query varchar(2000) = NULL\n";
				
				$query .= "BEGIN TRY\n";
					$query .= "SET XACT_ABORT ON\n";
					$query .= "BEGIN TRANSACTION\n";
			
						foreach($this->queryStack as $value){
							$query .= "SET @last_query='".str_replace("'","''",$value)."'\n";
							$query .= $value."\n";
						}
			
					$query .= "COMMIT TRANSACTION\n";
					$query .= "SELECT error=NULL, last_query=NULL\n";
				$query .= "END TRY\n";
			
				$query .= "BEGIN CATCH\n";
					$query .= "ROLLBACK TRANSACTION\n";
					$query .= "SELECT error=ERROR_MESSAGE(), last_query=@last_query;\n";
				$query .= "END CATCH\n";
				
			$query .= "END";

			
			$start_time = microtime(true);
			$query_result = @sqlsrv_query($this->conn,$query);
			$execution_time = microtime(true)-$start_time;
			
	
			if(!$query_result || $query_result===true){
				$error = $this->generateSqlError(@sqlsrv_errors(),$query);
			}else{
				$record_set = @sqlsrv_fetch_array($query_result, SQLSRV_FETCH_ASSOC);
				if($record_set['error']){
					$error = $this->generateSqlError($record_set['error'],$record_set['last_query']);
					$error = $this->generateSqlError(@sqlsrv_errors(),$query);
				}
			}
		}
		
		
		$result = array(
			'transaction_id'	=>	$this->transaction_id,
			'success'					=> (!$error) ? true : false,
			'message'					=> $error,
			'query'						=> $query,
			'execution_time'	=> $execution_time,
			'data'						=> $data
		);
		$this->logSqlStatement($result);

		$this->transaction_result['success'] = ($error) ? false : $this->transaction_result['success'];
		$this->transaction_result['error_message'] = ($error) ? $error : $this->transaction_result['error_message'];
		$this->transaction_result['query'] = $query;
		$this->transaction_result['execution_time'] = $this->transaction_result['execution_time'] + $execution_time;
		
		
		return $result;
	}
	
	
	function displayEnvironment(){
		return 'SQL Server: '.$this->connectionParameters['server'].'<br>Database: '.$this->connectionParameters['database'];
	}
	
	
	function logSqlStatement($result, $new_line = false){
		if($this->sqlLogging==true && $this->sqlLogOverride==false){
			if($result['stack']==true){
				$result_txt = 'STCK';
			}else{
				$result_txt = ($result['success']==true) ? 'GOOD' : 'FAIL';
			}
			$text = date('d-M-y h:i:s A').': '; 
			$text .= '['.$result['transaction_id'].'] ';
			$text .= '['.$result_txt.'] ';
			$text .= '['.number_format($result['execution_time'],3).'] => ';
			$text .= preg_replace("/\s+/", " ", $result['query'])."\r\n";
			$text .= ($new_line==true) ? "\r\n" : "";
		
			$log_file =  './LOGS/SQL_LOGS/SQL_LOG.txt';		
		
			$fh = fopen($log_file, 'a');
			fwrite($fh, $text);
			fclose($fh);	
		}
	}
	
	
	function generateSqlError($sql_errors, $query){
		if($this->sqlErrorLogOverride==false){
			if(is_array($sql_errors)){
				foreach($sql_errors as $row => $error_array){
					trigger_error('2'.$error_array['message']."|".$query);
				}
			}else{
				trigger_error('2'.$sql_errors."|".$query);
			}
			return $error_array['message'];
		}
	}
}
?>
