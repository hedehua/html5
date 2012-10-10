<?php

class Socket
{
	protected $socket = '';
	protected $host = '';
	protected $port = '';
	protected $users = array();
	protected $maxconnection = 0;
	protected $clients = array();
	
	/* 连接池 */
	protected $socketpool = array();
	
	protected $key = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
	
	protected function __construct(){}
	
	/**
	 * 
	 * 开启socket
	 */
	protected function _start($host = 'localhost', $port = '8100', $maxconnection = '100')
	{
		try {
		    $this->_init($host, $port, $maxconnection);		
			$this->_createLink();
			
			echo "开始启动服务器 : ".date('Y-m-d H:i:s').PHP_EOL;  
	        echo "SOCKET ID  : ".$this->socket.PHP_EOL;  
	        echo "监听地址   : ".$this->host." 端口 ".$this->port.PHP_EOL.PHP_EOL;
		} catch (Exception $e) {
		    die($e->getMessage().PHP_EOL);
		}
	}
	
	/**
	 * 
	 * 初始化socket
	 */
	protected function _init($host, $port, $maxconnection = '')
	{
		if(!$host) throw new Exception("主机地址设置失败，错误信息: 不能为空");
		if(!$port || $port > 65535) throw new Exception("端口设置失败，错误信息: 不能为0");
		if(!$maxconnection) throw new Exception("连接数设置失败，错误信息: 不能为0");
		
		$this->_setHost($host)->_setPort($port)->_setMaxConnention($maxconnection);
		if(!$this->socketpool && count($this->socketpool) >= $this->maxconnection) 
			throw new Exception("连接数已满，错误信息: 连接数为{$this->maxconnection}");
	}
	
	/**
	 * 
	 * 创建socket连接
	 */
	protected function _createLink()
	{
		/* 创建连接 */
		$this->socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
		if(!$this->socket) throw new Exception("创建连接失败，错误信息: ".socket_strerror($this->socket));
		
		/* 设置socket */
		$setsocket = socket_set_option($this->socket,SOL_SOCKET,SO_REUSEADDR,1);
		if(!$setsocket) throw new Exception("设置socket失败，错误信息: ".socket_strerror($this->socket));

		/* 绑定socket */
		$bindsocket=socket_bind($this->socket,$this->host,$this->port);
		if(!$bindsocket) throw new Exception("绑定socket失败，错误信息: ".socket_strerror($bindsocket));

		/* 监听socket*/
//		$listentsocket=socket_listen($this->socket,50);
		$listentsocket=socket_listen($this->socket);
		if(!$listentsocket) throw new Exception("监听socket失败，错误信息: ".socket_strerror($listentsocket));

		/* 放入连接池*/
		if(!in_array($this->socket, $this->socketpool)) $this->socketpool[] = $this->socket;
	}
	
	/**
	 * 
	 * 解密信息
	 * @param string $message
	 */
	protected function _unwrap($message)
	{
		$mask = array();  
	    $data = "";  
	    $message = unpack("H*",$message);  
	      
	    $head = substr($message[1],0,2);  
	      
	    if (hexdec($head{1}) === 8) {  
	        $data = false;  
	    } else if (hexdec($head{1}) === 1) {  
	        $mask[] = hexdec(substr($message[1],4,2));  
	        $mask[] = hexdec(substr($message[1],6,2));  
	        $mask[] = hexdec(substr($message[1],8,2));  
	        $mask[] = hexdec(substr($message[1],10,2));  
	      
	        $s = 12;  
	        $e = strlen($message[1])-2;  
	        $n = 0;  
	        for ($i= $s; $i<= $e; $i+= 2) {  
	            $data .= chr($mask[$n%4]^hexdec(substr($message[1],$i,2)));  
	            $n++;  
	        }
	    }  
	      
	    return $data;  
	}
	
	/**
	 * 
	 * 开始握手
	 * @param object $user
	 * @param int $buffer
	 */
	protected function _handshake($user,$buffer){  
	    preg_match('#Sec-WebSocket-Key: (.*)\r\n#', $buffer, $matches);
	    /*构造响应头信息*/
	    $reponseheader = $this->_setReponseHeader($this->_setNewKey($matches[1]));
	    //写入握手信息到SOCKET
	    socket_write($user->socket, $reponseheader, strlen($reponseheader));
	    $user->handshake=true;
	    return true;
    }
    
//    protected function getheaders($req){  
//        $r=$h=$o=null;  
//        if(preg_match("/GET (.*) HTTP\/1\.1\r\n/"   ,$req,$match)){ $r=$match[1]; }  
//        if(preg_match("/Host: (.*)\r\n/"  ,$req,$match)){ $h=$match[1]; }  
//        if(preg_match("/Sec-WebSocket-Origin: (.*)\r\n/",$req,$match)){ $o=$match[1]; }  
//        if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/",$req,$match)){ $key=$match[1]; }  
//        return array($r,$h,$o,$key);  
//    }

    /**
	 * 
	 * 设置请求头
	 * @param string $str
	 */
    protected function _setReponseHeader($str)
    {
    	return "HTTP/1.1 101 Switching Protocols\r\n"
			     . "Upgrade: websocket\r\n"
			     . "Sec-WebSocket-Version: 13\r\n"
			     . "Connection: Upgrade\r\n"
			     . "Sec-WebSocket-Accept: " . $str . "\r\n\r\n";
    }
    
	/**
	 * 
	 * 握手码
	 * @param string $key
	 */
	protected function _setNewKey($key)
	{
	    return base64_encode(sha1($key.$this->key,true));
	}
	
	/**
	 * 
	 * 发送消息
	 * @param string $message
	 */
	protected function _send($message, $client = '')
	{
		socket_write($client,$message,strlen($message));
	}
	
	/**
	 * 
	 * 广播消息
	 * @param string $message
	 */
//	protected function _broadcast($message, $client = '')
//	{
//		$client = $client ? $client : $this->socketpool;
//		foreach($client as $socket) {
//			$this->_send($socket,"{$message}");
//		}
//	}
	
	/**
	 * 
	 * 关闭socket
	 * @param int $socketid
	 */
	protected function _close($socket = '')
	{
		$found = '';
		$userinfo = '';
		$total = count($this->users);
		if($total) {
		    for($i = 0; $i < $total; $i++){
		      	if($this->users[$i]->socket == $socket) {
		      		$found = $i; 
		      		$userinfo = $this->users[$i];
		      		break; 
		      	}
		    }
			if(!is_null($found)) {
				array_splice($this->users, $found, 1);
			}
		}
	    $index = array_search($socket,$this->socketpool);
	    if($index >= 0){
	    	array_splice($this->socketpool,$index,1);
	    	array_splice($this->clients,$index,1);
	    }
	    socket_close($socket);
	    return $userinfo;
	}
	
	/**
	 * 
	 * 设定socket主机地址
	 * @param string $host
	 */
	protected function _setHost($host = 'localhost')
	{
		$this->host = $host;
		return $this;
	}
	
	protected function _setPort($port = '8100')
	{
		$this->port = intval($port);
		return $this;
	}
	
	/**
	 * 
	 * 设定最大连接数
	 * @param int $max
	 */
	protected function _setMaxConnention($maxconnection = 100)
	{
		$this->maxconnection = intval($maxconnection);
		return $this;
	}
	
	/**
	 * 
	 * ASCII十进制到十六进制
	 * @param string $data
	 */
	protected function ord_hex($data)  {  
	    $message = "";
	    for($i= 0; $i< strlen($data); $i++){  
	        $message .= dechex(ord($data{$i}));  
	    }
	    return $message;  
	} 
	
	/**
	 * 
	 * 加密信息算法
	 * @param string $msg
	 */
	protected function wrap($message = ""){
	    $frame = array();  
	    $frame[0] = "81";  
	    $len = strlen($message);  
	    $frame[1] = $len<16?"0".dechex($len):dechex($len);  
	    $frame[2] = $this->ord_hex($message);
	    $data = implode("",$frame);
	    return pack("H*", $data);
	}
	
	
	
	
	
	
	
	
	
}