<?php
	
	/**
	 * 
	 * 输出日志到终端
	 * @param string|array $log
	 */
	function logP($log)
	{
		$log = is_array($log) ? $log : array($log);
		foreach($log as $v) {
			$msg = explode(PHP_EOL, $v);
			foreach($v as $v1) {
				echo date('Y-m-d H:i:s')."{$v1}".PHP_EOL;
			}
		}
	}
	
	/**
	 * 
	 * 输出日志到文件
	 * @param string|array $log
	 */
	function logFile($log)
	{
		$filename = '/home/wwwroot/www/websocket/mysocket/test.log';
	    if(!file_exists($filename)) {
	        $f = @fopen($filename, 'w');
	        fclose($f);
	    }
	
	    $strs = array();
	    if(is_string($log)) $strs[] =$log;
	    else if(is_array($log)) $strs = $log;
	    else return false;
	    if(is_writable($filename)){
	        if (!$handle = @fopen($filename, 'a')) return false;
	        $content = '';
	        foreach($strs as $k => $v) {
	        	$content .= "$k = $v ".PHP_EOL;
	        }
	        if (@fwrite($handle, $content) === FALSE) return false;
	        @fclose($handle);
	        return true;
	    }else {
	        return false;
	    }
	}
	
	function dump($param) {
		var_dump($param);
	}
	
	
	
	
	
	
	
	
	
	
	
	
	