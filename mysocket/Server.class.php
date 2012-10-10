<?php

class Server extends socket
{
	public $data = '';
	public function __construct()
	{
		parent::__construct();
	}
	
	public function run($host = 'localhost', $port = '8100', $maxconnection = '100')
	{
		$this->_start($host, $port, $maxconnection);
		while(true){
	    $changed = $this->socketpool;
	    
	    //阻塞,等待SOCKET状态改变（一切设备皆文件）才往下执行
	    socket_select($changed,$write=NULL,$except=NULL,NULL);
	  	dump($this->socketpool);
//	  	echo count($this->socketpool)."\n";
//	  	echo microtime()."\n";
	    //遍历
	    foreach($changed as $socket){
	        //判断$socket是否获取到数据
	        if($socket == $this->socket){
	            $client=socket_accept($this->socket);
	            if($client < 0){ 
	            	/*socket_accept() failed*/
	                continue; 
	            }else{
	                $this->_createUserConnection($client);
	            }
	        }else{
	            //获取接收的数据
	            $bytes = @socket_recv($socket,$buffer,2048,0);
	            if(!$bytes){
	                $this->_closeUserConnection($socket);
	            }else{
	                //获取用户
	                $user = $this->_getuserbysocket($socket);
	                //如果没有握手，先握手
	                if(!$user->handshake){
	                    //判断是否为WEB端发送的信息
	                    if(false === stripos($buffer, 'websocket')){
	                        $user->web = 0;
//	                        $this->notify($buffer);
	                        sleep(2);
	                    }else{
	                        $this->_handshake($user,$buffer); 
	                    }
	                }else{
	                    $this->data = $this->_getMessage($buffer);
	                    $this->process($user);
	                }
	            }
	        }
	    }
	}
	}
	
	/**
	 * 
	 * 创建用户连接
	 */
	protected function _createUserConnection($client)
	{
		$user = new User();
	    $user->id = uniqid();
	    $user->socket = $client;
	    $user->web = 1;
	    $user->username = '';
	    array_push($this->users,$user);
	    array_push($this->socketpool,$client);
	}
	
	protected function _closeUserConnection($socket)
	{
		$userinfo = $this->_close($socket);
//		$this->notify($userinfo->username . '--退出聊天室!',$userinfo);
	}
	
	/**
	 * 
	 * 获取SOCKET对应的用户
	 * @param object $socket
	 */
	public function _getuserbysocket($socket)
	{
		$found=null;
		foreach($this->users as $user){
		    if($user->socket == $socket) { 
		    	$found = $user; 
		    	break; 
		    }
		}
		return $found;
	}
	
	/**
	 * 
	 * 获取消息
	 * @param string $message
	 */
	protected function _getMessage($message)
	{
		return json_decode($this->_unwrap($message));
	}
	
	public function process($user){
		if($user->socket && $this->data) {
		    if(!$user->username) $this->notify($this->data->username . '--加入到聊天室!',$user);
			$user->username = $this->data->username;
			$this->notify($this->data->username.'说：'.$this->data->content,$user);
		}
	}
	
	/**
	 * 
	 * 是否为web用户
	 * @param string $msg
	 * @param string|boolean $u
	 */
	public function notify($msg,$u=false){
	    /*貌似现在有\r\n字符会导致SOCKET连接中断*/
	    $msg = preg_replace(array('/\r$/','/\n$/','/\r\n$/',), '', $msg);

	    /*如果是非WEB用户的消息*/
	    if($u === false){
	        $msg = date('H:i:s') . ' server：' . $msg;
	    }
	    
	    $newMsg = $this->wrap($msg);
	    foreach($this->users as $user){
//            echo iconv('utf-8','gb2312//IGNORE',$msg);
            $this->_send($newMsg, $user->socket);
	    }
    }
    
    
	
	
}