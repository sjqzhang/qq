<?php

function format_json($content){
    $json= preg_replace('/(\w+\D[^"])(:)/',"\"$1\"$2",$content);
	print($json);die;
	return $json;
}
function get_json_array($content,$is_utf8=true) {
    $content=format_json($content);
    if (preg_match('/\[[\s\S]*\]/',$content,$result)) {
        if(!$is_utf8) {
            $result[0]= iconv('gbk','utf-8',$result[0]);
        }
        return json_decode($result[0],true);
    }
    return '[]';
}

function get_url_json($url,$is_utf8=true){

  $content=get($url);

  return get_json($content,$is_utf8);

}

function get_json($content,$is_utf8=true) {
    $content=format_json($content);
    $content= preg_replace("/\t|\r|\n/im", "", $content);
    if (preg_match('/\{[\s\S]*\}/im',$content,$result)) {
        if(!$is_utf8) {
            $result[0]= iconv('gbk','utf-8',$result[0]);
        }
        return json_decode($result[0],true);
    }
    return '{}';
}
function parse_graph_xml($xml) {
    $dom= simplexml_load_string($xml);
    $data=array();
    foreach($dom->series->value as $value) {
        $name= (string)$value;
        $name=preg_replace('/<[^>]+>/','',$name);
        $xid=(int)$value['xid'];
        $data[(int)$value['xid']]=$xid.','.$name;
    }
    foreach( $dom->graphs->graph as $graph){
        $gid= $graph['gid'];
        foreach($graph as $value){
            $data[(int)$value['xid']]=$data[(int)$value['xid']].','.$gid.','.(string)$value;
        }
    }
    return $data;
}


function get($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    $output = curl_exec($ch);
    curl_close($ch);
    //print_r($output);
    return $output;
}

function format_data($content){
    preg_match_all('/(?:sh|sz)(\w+)="([^"]+)?"/',$content,$data);
    $result=array();
    $i=0;
    foreach($data[1] as $key){

        $result[$key]=$data[2][$i];
        $i=$i+1;
    }
    return $result;

}

function format($str, $data) {
    if( preg_match_all('/\{\w+\}/i',$str,$match)){
        if(!is_null($match[0])) {
            $match=$match[0];
        }
        $is_map=false;
        foreach($data as $k =>$v){
            if(!is_numeric($k)){
                $is_map=true;
                break;
            }
        }
        if($is_map) {
            foreach($match as $i) {
                $str=preg_replace("/$i/",$data[preg_replace('/^\{|\}$/','',$i)],$str);
            }
        } else {
            foreach($match as $i) {
                $str=str_replace($i,$data[preg_replace('/^\{|\}$/','',$i)],$str);
            }
        }
    }
    return $str;
}

    class HttpClient {

    const CRLF = "\r\n";      //
    private $fh = null;       //socket handle
    private $errno = -1;      //socket open error no
    private $errstr = '';     //socket open error message
    private $timeout = 30;    //socket open timeout
    private $line = array();  //request line
    private $header = array();//request header
    private $body = array();  //request body
    private $url = array();   //request url
    private $response = '';   //response
    private $version = '1.1'; //http version

    public function __construct() {

    }

    /**
     * 发送HTTP get请求
     * @access public
     * @param string $url 请求的url
     */
    public function get($url = '') {
        $this->setUrl($url);
        $this->setLine();
        $this->setHeader();
        $this->request();
        return $this->response;
    }

    /**
     * 发送HTTP post请求
     * @access public
     */
    public function post() {
        $this->setLine('POST');
        $this->request();
        return $this->response;
    }

    /**
     * HTTP -> HEAD 方法，取得服务器响应一个 HTTP 请求所发送的所有标头
     * @access public
     * @param string $url 请求的url
     * @param int $fmt 数据返回形式，关联数组与普通数组
     * @return array 返回响应头信息
     */
    public function head($url = '', $fmt = 0) {
        $headers = null;
        if (is_string($url)) {
            $headers = get_headers($url, $fmt);
        }
        return $headers;
    }

    /**
     * 设置要请求的 url
     * @todo 这里未做url验证
     * @access public
     * @param string $url request url
     * @return bool
     */
    public function setUrl($url = '') {
        if (is_string($url)) {
            $this->url = parse_url($url);
            if (!isset($this->url['port'])) {//设置端口
                $this->url['port'] = 80;
            }
        } else {
            return false;
        }
    }

    /**
     * 设置HTTP协议的版本
     * @access public
     * @param string $version HTTP版本，default value = 1.1
     * @return bool 如果不在范围内返回false
     */
    public function setVersion($version = "1.1") {
        if ($version == '1.1' || $version == '1.0' || $version == '0.9') {
            $this->version = $version;
        } else {
            return false;
        }
    }

    /**
     * 设置HTTP请求行
     * @access public
     * @param string $method 请求方式 default value = GET
     */
    private function setLine($method = "GET") {
        //请求空：Method URI HttpVersion
        if (isset($this->url['query'])) {
            $this->line[0] = $method . " " . $this->url['path'] . "?" . $this->url['query'] . " HTTP/" . $this->version;
        } else {
            $this->line[0] = $method . " " . $this->url['path'] . " HTTP/" . $this->version;
        }
    }

    /**
     * 设置HTTP请求头信息
     * @access public
     * @param array $header 请求头信息
     */
    public function setHeader($header = null) {
        $this->header[0] = "Host: " . $this->url['host'];
        if (is_array($header)) {
            foreach($header as $k => $v) {
                $this->setHeaderKeyValue($k, $v);
            }
        }
    }

    /**
     * HTTP请求主体
     * @access public
     * @param array $body 请求主体
     */
    public function setBody($body = null) {
        if (is_array($body)) {
            foreach ($body as $k => $v) {
                $this->setBodyKeyValue($k, $v);
            }
        }
    }

    /**
     * 单条设置HTTP请求主体
     * @access public
     * @param string $key 请求主体的键
     * @param string $value 请求主体的值
     */
    public function setBodyKeyValue($key, $value) {
        if (is_string($key)) {
            $this->body[] = $key . "=" . $value;
        }
    }

    /**
     * 单条设置HTTP请求头信息
     * @access public
     * @param string $key 请求头信息的键
     * @param string $value 请求头信息的键
     */
    public function setHeaderKeyValue($key, $value) {
        if (is_string($key)) {
            $this->header[] = $key . ": " . $value;
        }
    }

    /**
     * socket连接host, 发送请求
     * @access private
     */
    private function request() {
        //构造http请求
        if (!empty($this->body)) {
            $bodyStr = implode("&", $this->body);
            $this->setHeaderKeyValue("Content-Length", strlen($bodyStr));
            $this->body[] = $bodyStr;
            $req = array_merge($this->line, $this->header, array(""), array($bodyStr), array(""));
        } else {
            $req = array_merge($this->line, $this->header, array(""), $this->body, array(""));
        }
        $req = implode(self::CRLF, $req);

        //socket连接host
        $this->fh = fsockopen($this->url['host'], $this->url['port'], $this->errno, $this->errstr, $this->timeout);

        if (!$this->fh) {
            echo "socket connect fail!";
            return false;
        }

        //写请求
        fwrite($this->fh, $req);

        //读响应
        while (!feof($this->fh)) {
            $this->response .= fread($this->fh, 1024);
        }
    }

    /**
     * 关闭socket连接
     * @access public
     */
    public function __destruct() {
        if ($this->fh) {
            fclose($this->fh);
        }
    }

}


class DB
{
    var $host='127.0.0.1';
    var $pwd='1016';
    var $db='test';
    var $user='root';
    var $db_type='mysql';
    var $db_port=3306;
    var $con=null;
	var $debug=true;
    function __construct($host,$user,$pwd,$db,$port=3306,$db_type='mysql',$charset='utf8')
    {
        $this->host=$host;
        $this->user=$user;
        $this->pwd=$pwd;
        $this->db=$db;
        $this->charset=$charset;
        if($db_type=='mysql')
        {
            $this->db_port= $port==3306?3306:$port;
        } else  if($db_type=='mssql')
        {
            $this->func_connect='mssql_connect';
            $this->func_query='mssql_query';
            $this->func_close='mssql_close';
            $this->func_escape_string='';
            $this->func_select_db='mssql_select_db';
            $this->func_fetch_array='mssql_fetch_array';
            $this->func_affected_rows='mssql_affected_rows';
            $this->tran_start='begin transaction';
            $this->db_type='mssql';
            $this->db_port= $port==3306?1433:$port;
        } else
        {
            exit("db $db_type not spport!!!");
        }
        $this->connect();
        if($db_type=='mysql')
        {
            $this->query("set names $charset");
        }
    }
    function getResult($rs)
    {
        if($rs)
        {
            $rows=array();
            $i=0;
            $func =$this->func_fetch_array;
            while($row= $func($rs))
            {
                $rows[$i]=$row;
                $i=$i+1;
            }
            return $rows;
        } else
            {
                return null;
            }
    }
    function start_tran()
    {
        $this->query($this->tran_start);
    }
    function commit()
    {
        $this->query($this->tran_commit);
    }
    function rollback()
    {
        $this->query($this->tran_rollback);
    }
    function close()
    {

        $func=$this->func_close;
        $func($this->con);
        $this->con=null;
    }
    function affected_rows()
    {
        $func=$this->func_affected_rows;
        return $func($this->con);
    }
    function scalar($sql)
    {
        $row= $this->query($sql);
        if(isset($row[0][0])) {
            return $row[0][0];
        } else if(isset($row[0]))
        {
            return $row[0];
        }  else
        {
            return $row;
        }
    }
    function query($sql)
    {
        $func=$this->func_query;
        $rs=$func($sql,$this->con);
        if($this->error()){
			if($this->debug) {
              echo $this->error();
              echo $sql;
              if(preg_match('/gone away/i',$this->error())||!is_resource($this->con)){
                $this->close();
                $this->connect();
                if(is_resource($this->con)&&$this->db_type=='mysql'){
                    $this->query("set names {$this->charset}");
                }
              }
			}
        }
        $sql= trim($sql);
        if(preg_match("/^select|show/i",$sql))
        {
            return $this->getResult($rs);
        } else
        {
            return $rs;
        }
    }
    function format($sql,$para){
        preg_match_all("/:\w+/",$sql,$pn);
        if(count($pn[0])<1) return $sql;
        $func=$this->func_escape_string;
        foreach ($pn[0] as $name) {
            $val="'".$func($para[substr($name,1,strlen($name))])."'";
            $sql= preg_replace("/".$name."/",$val,$sql);
        }
        return $sql;
    }
    function error()
    {  $func=$this->fnuc_error;
       if(is_resource($this->con)){
           return $func($this->con);
       } else {
          $this->connect();
          if(is_resource($this->con)&&$this->db_type=='mysql'){
            $this->query("set names {$this->charset}");
          }

       }
    }
    function get_con()
    {
        return $this->con;
    }
    private function connect()
    {
        $func=$this->func_connect; $this->con= $this->db_type=='mysql'? $func($this->host.":{$this->db_port}",$this->user,$this->pwd):$func($this->host.",{$this->db_port}",$this->user,$this->pwd);
        if(!$this->con) {
            echo ("can't connect db,database's name {$this->db}");
        }
        $func=$this->func_select_db; $func($this->db,$this->con);
    }
    var $func_fetch_array='mysql_fetch_array';
    var $func_query='mysql_query';
    var $func_connect='mysql_connect';
    var $fnuc_error='mysql_error';
    var $func_select_db='mysql_select_db';
    var $func_escape_string='mysql_escape_string';
    var $func_affected_rows='mysql_affected_rows';
    var $func_close='mysql_close';
    var $tran_start='start transaction';
    var $tran_commit='commit';
    var $tran_rollback='rollback';
}

?>
