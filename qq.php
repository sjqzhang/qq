#!/usr/bin/php
<?php
require_once(dirname(__FILE__).'/common.inc.php');
require_once(dirname(__FILE__).'/phpQuery.php');
//$db=new DB('127.0.0.1','root','root','stock');
$db=new DB('172.16.132.230','root','root','stock');

class QQ3G {

    var $qq;
    var $pwd;
    var $sid;
    var $ch;
    var $md5=array();
    var $logdir= './logs';
    var $dir= './';

    function trimhtml($content){

    }

    function init(){
        if(!file_exists($this->dir.'/sid')){
            touch($this->dir.'/sid');
        }
        if(!is_dir($this->logdir)){
            mkdir($this->logdir);
        }

    }

    function readsid(){
        $fp= fopen($this->dir.'/sid','r');
        $sid= fread($fp,filesize($this->dir.'/sid'));
        fclose($fp);
        return $sid;
    }
    function writesid($sid){
        $fp= fopen($this->dir.'/sid','w');
        $sid= fwrite($fp,$sid);
        fclose($fp);
        return $sid;
    }

    function write($txt){
        $fp= fopen($this->dir.'/qqlog','a+');
        $sid= fwrite($fp,$txt);
        fclose($fp);
    }
    function QQ3G($qq,$pwd) {
        $this->logdir=dirname(__FILE__).'/logs';
        $this->dir=dirname(__FILE__);
        $this->ch = curl_init();
        $this->qq=$qq;
        $this->pwd=$pwd;
        $this->init();
        $sid= $this->readsid();
        if(empty($sid)){
            $this->login();
        }else {
            $this->sid=$sid;
        }
    }

    function request_long($url,$data,$is_post=true){
        $ch=$this->ch;
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,$is_post);
        if(!empty($data)){
            curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data));
        }
        $file = curl_exec($ch);
        return $file;
    }

    function request($url,$data,$is_post=true){

        //print_r($url."\n");
        //print_r($data);
        $ch = curl_init();
        curl_setopt($ch,CURLOPT_URL,$url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_POST,$is_post);
        if(!empty($data)){
            curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($data));
        }
        $file = curl_exec($ch);
        curl_close($ch);
        //echo $file;
        //$this->write($file);
        return $file;

    }

    function  login() {
        $qq=$this->qq;
        $pwd=$this->pwd;
        $data = array(
            'qq'            => $qq,
            'pwd'           => $pwd,
            'bid_code'      => '3GQQ',
            'toQQchat'      => true,
            'login_url'     => 'http://pt.3g.qq.com/s?aid=nLoginnew&q_from=3GQQ',
            'q_from'        => '',
            'modifySKey'    => 0,
            'loginType:'    => 1,
            'aid'           => 'nLoginHandle',
            'i_p_w'         => 'qq|pwd|',
        );
        $file=$this->request_long('http://pt.3g.qq.com/psw3gqqLogin',$data);
        preg_match('%sid=(.*?)&%si',$file,$sid);
        $sid = $sid[1];
        //echo $sid;
        if($sid){
            echo date('Y-m-d H:i:s')."\t".'登陆成功！ sid:'.$sid."\n";
            $this->sid=$sid;
            $this->writesid($sid);
            return $sid;
        }else{
            echo '登陆失败！请检查用户名和密码是否正确！';

            return '';
        }

    }


    function log($msg){
        if(is_array($msg)){
            $tmp='';
            foreach($msg as $k=> $v){
                $tmp.=$k.':'.$v.',';
            }
            $msg=$tmp;
        }

        $msg=preg_replace("/[\r\n]+/",',',$msg);
        $logfn=$this->logdir.'/'.date('Y-m-d').".log";
        if(!file_exists($logfn)){
            touch($logfn);
        }
        error_log(date('Y-m-d H:i:s')."\t".$msg."\n",3,$logfn);
    }


    function send($to,$msg){
        $qq   = $to;
        $text = $msg;
        //print_r(strlen($msg));
        //
        $msgs=preg_split("/\-{10,}/",$msg);
        foreach($msgs as $msg) {
            $msg=trim($msg);
            if(empty($msg)){
                continue;
            }

            if(strlen($msg)>1024) {
                foreach(str_split($msg,1024) as $mm){
                    $data = array(
                        'u'         => $qq,
                        'saveUrl'   => 0,
                        'do'        => 'send',
                        'on'        => 1,
                        'aid'       => '发送',
                        'msg'       => $mm,
                    );


                    $file=$this->request( 'http://q16.3g.qq.com/g/s?sid='. $this->sid,$data);
                    preg_match('%<p align="left">(.*?)<br/>%si',$file,$callback);
                    $callback = $callback[1];

                    echo $callback;
                    $this->log('qq:'.$qq."\t".$callback.$msg);
                }
            } else {

                $data = array(
                    'u'         => $qq,
                    'saveUrl'   => 0,
                    'do'        => 'send',
                    'on'        => 1,
                    'aid'       => '发送',
                    'msg'       => $msg,
                );


                $file=$this->request( 'http://q16.3g.qq.com/g/s?sid='. $this->sid,$data);
                preg_match('%<p align="left">(.*?)<br/>%si',$file,$callback);
                $callback = $callback[1];

                echo $callback;
                $this->log('qq:'.$qq."\t".$callback.$msg);

            }

        }
        //$this->request("http://q16.3g.qq.com/g/s?sid={$this->sid}&aid=nqqChat&u={$to}&on=1&referer=",array(),false);

    }

    function jump(){

        $content= $this->request("http://q32.3g.qq.com/g/s?aid=nqqchatMain&sid={$this->sid}&myqq={$this->qq}",array(),false);

    }
    function recv(){

        $data=array(

        );

        $content='';
        //$content= $this->jump();
        //$this->jump();

        $msg=array();



        if(preg_match('/QQMsg/',$content,$match)||true) {

            $content=$this->request("http://q32.3g.qq.com/g/s?sid={$this->sid}&3G_UIN={$this->qq}&saveURL=0&aid=nqqChat",$data,false);
            if(preg_match('/http:\/\/pt.3g.qq.com\/s\?aid=nLogin3gqqbysid/',$content,$match)){
                $this->login();
            }
            $num='';
            $name='';
            if(preg_match('/与(.*?)聊天-3GQQ/',$content,$match)){
                $name=$match[1];
                //echo $name;
            }
            if(preg_match('/postfield name=\"u\" value=\"(.*?)\"\/>/',$content,$match)){
                $num=$match[1];
                //echo $num;
            }

            $pos= strpos($content,'提示',0);
            $end= strpos($content,'</go>发送',0);
            //$this->write($content);
            //echo "---------------------".$end."-------------------";
            //$content= substr($content,$pos,$end);
            //echo $content;
            unset($match);
            //$content= preg_replace("/\n/",'',$content);
            if(preg_match_all("/$name: &nbsp;([\s\S]*?)<br\/>([\s\S]*?)<br\/>/",$content,$match)){
                //print_r($match);
                foreach($match[1] as $i=> $time) {
                    $mm=array('message'=>trim($match[2][$i]),'num'=>$num,'time'=>trim(preg_replace('/&nbsp;/','',$time)));
                    array_push($msg,$mm);

                }
            }

            return $msg;


        } else if(preg_match('/QQQunMsg/',$content,$match)) {

            $content=$this->request("http://q32.3g.qq.com/g/s?sid={$this->sid}&3G_UIN={$this->qq}&saveURL=0&aid=nqqQunChat",$data,false);

        }



    }



}
//echo request('http://www.qq.com',array(),false);

//$qq=new QQ3G('2967440563','gsxq8888');
$qq=new QQ3G('2696025131','gsxq8888');

// $qq->send('413133880','可以吗');
//
//


class GP3G{

    var $db;
    var $spliter='------------------------------------';

    function GP3G($db){

        $this->db=$db;

    }

    function query($sql){

        return $this->msgformat($this->db->query($sql));

    }

    function getworkdate($n){
        $w=date('w');
        $i=1;
        if($n>5){
            $i=floor( $n/5);
        }
        if($n>$w-1){
            $n=$n+2*$i;
        }
        return date("Y-m-d", strtotime("-$n day"));
    }

    function msgformat($rows){

        $msg=array();
        foreach($rows as $i=> $row)
        {   $j=$i+1;
        foreach($row as $k=>$v){
            if(!is_numeric($k)){
                array_push($msg,$k.": ".$v);
            }
        }
        if($j%3==0){
            array_push($msg,$this->spliter);
        }
        array_push($msg,"\n");
        }
        return join("\n",$msg);
    }

    function gg($stockno)
    {
        $stockno=mysql_real_escape_string($stockno);

        /*

            SELECT gpxx.stockno,gpxx.name, cur,zdb rd1,rd5,rd10,np,wp,ltsj,main_rd,small_rd,super_rd, ROUND(super_money/10000) super_money, ROUND(small_money/10000) small_money,
case when stype=1 then concat('http://image.sinajs.cn/newchart/daily/n/sh',gpxx.stockno,'.gif')
         when stype=2  then  concat('http://image.sinajs.cn/newchart/daily/n/sh',gpxx.stockno,'.gif')
                  end url
                   FROM
                    stock.gpxx left JOIN stock.ggzj ON gpxx.stockno=ggzj.stockno AND gpxx.cdate=ggzj.cdate
                    left JOIN stock.ggzl ON gpxx.`cdate`=ggzl.`cdate` AND gpxx.`stockno`=ggzl.`stockno`
                    WHERE gpxx.cdate=DATE(NOW())
         */
        $sql="
            SELECT now() ctime, gpxx.stockno,gpxx.name, cur,zdb rd1,rd5,rd10,np,wp,ltsj,main_rd,small_rd,super_rd, ROUND(super_money/10000) super_money, ROUND(small_money/10000) small_money FROM
            stock.gpxx left JOIN stock.ggzj ON gpxx.stockno=ggzj.stockno AND gpxx.cdate=ggzj.cdate
            left JOIN stock.ggzl ON gpxx.`cdate`=ggzl.`cdate` AND gpxx.`stockno`=ggzl.`stockno`
            WHERE gpxx.cdate=DATE(NOW()) AND (gpxx.stockno ='$stockno' or gpxx.name like '%$stockno%') limit 15
            ";
        return $this->query($sql);

    }


    function k($stockno){

        $sql="
            SELECT * FROM (
                SELECT  '周K',stockno, CASE WHEN stype=1 THEN CONCAT('http://image.sinajs.cn/newchart/weekly/n/sh',stockno,'.gif')
                WHEN stype=2  THEN  CONCAT('http://image.sinajs.cn/newchart/weekly/n/sh',stockno,'.gif')
                END 'K' FROM sn WHERE (stype=1 OR stype=2)
                UNION ALL
                SELECT '日K',  stockno, CASE WHEN stype=1 THEN CONCAT('http://image.sinajs.cn/newchart/daily/n/sh',stockno,'.gif')
                WHEN stype=2  THEN  CONCAT('http://image.sinajs.cn/newchart/daily/n/sh',stockno,'.gif')
                END 'K' FROM sn WHERE (stype=1 OR stype=2)
            ) T WHERE T.stockno='$stockno'
            ";
        return $this->query($sql);

    }

    function dpzj(){

        $sql="
            select T.*,gpjs.rd from (

                select cdate, ROUND( SUM(main_money/10000/10000),2) main_money, round( sum(super_money/10000/10000),2) super_money, round(SUM(small_money/10000/10000),2) small_money from ggzj  group by cdate order by cdate desc limit 5

            ) T left join gpjs on gpjs.cdate=T.cdate and gpjs.scode='0000011' order by T.cdate



            ";

        return $this->query($sql);

    }

    function dp(){

        $sql="SELECT  rd,sindex,lindex, up, down,eq, cdate, ctime,NAME FROM stock.gpjs WHERE cdate=DATE(NOW())";

        return $this->query($sql);

    }

    function zj(){
        $sql="select * from (select gpxx.name,gpxx.stockno,zdb,cur,ltsj,ROUND( super_money/10000) super_money,ROUND( small_money/10000) small_money from ggzj inner join gpxx
            on gpxx.cdate=ggzj.cdate and gpxx.stockno=ggzj.stockno
            where ggzj.cdate=date(now())
            order by super_money desc limit 15) T order by super_money";
        return $this->query($sql);
    }


    function bft(){
        $sql="     select now() ctime,gpxx.name,gpxx.stockno,ltsj,cur,zdb, ROUND(super_money/10000) super_money, ROUND(small_money/10000) small_money from stock.gpxx left join stock.ggzj on
            gpxx.cdate=ggzj.cdate and gpxx.stockno=ggzj.stockno where zdb>9.7 and zdb<10 and gpxx.cdate=date(now())";
        return $this->query($sql);
    }

    function h(){

        $h['h']='帮助';
        $h['dp']='大盘';
        $h['yc']='预测';
        $h['bk']='板块';
        $h['gz']='跟庄';
        $h['df']='跌幅选股';
        $h['zf']='涨幅选股';
        $h['zj']='资金选股';
        $h['dj']='低价选股';
        $h['ldn']='连续n天下跌选股';
        $h['bft']='不封停';
        $h['dpzj']='大盘资金';
        $h['cg']='股评选股';
        $h['p000001']='个股评论';
        $h['代码/名称']='直接股票代码查个股';
        $h['板块代码']='板块代码';
        $h['小Q工作时间']='按正常交易时间上班';
        $h['说明']='小Q所有判断都是根据当天数据进行处理，所以只适合做超短线参考，非易时间数据没有参考价值';
        return $this->msgformat(array('0'=>$h));

    }

    function bk($bkcode=''){

        if(empty($bkcode)){
            return $this->zlbk();
        } else {
            $sql="select * from (SELECT gpxx.name, easymoney_pk.stockno,cur,zdb,rd5,rd10,ltsj,zsj , ROUND(super_money/10000) super_money, ROUND(small_money/10000) small_money FROM stock.easymoney_pk INNER JOIN stock.gpxx ON
                easymoney_pk.stockno=gpxx.stockno AND gpxx.cdate=DATE(NOW())
                left join ggzl on ggzl.cdate=gpxx.cdate and ggzl.stockno=gpxx.stockno
                left join ggzj on ggzj.cdate=gpxx.cdate and ggzj.stockno=gpxx.stockno
                WHERE easymoney_pk.pkcode='${bkcode}' order by zdb desc) t order by zdb";
            return $this->query($sql);

        }


    }

    function cg(){

        //$key="主力实力强大|主力拉抬明显|多头市道|走势形态良好|建议跟进|积极增仓|强势特征明显";
        //$key="主力实力强大|主力拉抬明显|多头市道|强势特征明显";
        $key="主力实力强大|主力拉抬明显|强势特征明显";
        $keys=preg_split('/\|/',$key);
        if(!function_exists('addor')){
            function addor(&$value){
                $value="remark like '%".$value."%'";
            }
        }
        array_walk($keys,'addor');

        $like= '( '. join($keys,' OR ').' ) ';

        $w=date('w');

        if($w==6){
            $w=-1;
        } else if($w==7){
            $w=-2;
        }else if($w==1){
            $w=-3;
        } else {
            $w=-1;
        }


        $sql="   select cgcp.stockno,cgcp.name,cgcp.cdate,cur,rd,ltsj,remark from cgcp inner join gpxx on cgcp.stockno=gpxx.stockno and
                   gpxx.cdate=date(now())  where cgcp.cdate= date_add(date(now()),INTERVAL $w day) and ltsj<100 and price<8 and cur>0 and
                          cgcp.name not like '%st%' and $like  limit 18";


       // echo $sql;

            return $this->query($sql);

    }


    function gz(){

        $sql="select * from (SELECT ggzl.stockno,ggzl.name,ggzl.price,ggzl.rd1,ggzl.rd5,ggzl.rd10,mr1,ROUND( main_money/10000) main_money,ROUND( super_money/10000) super_money,ROUND( small_money/10000) small_money,ROUND(gpxx.ltsj) ltsj,ROUND(gpxx.zsj) zsj FROM stock.ggzl INNER JOIN stock.ggzj ON
            ggzl.stockno = ggzj.stockno AND ggzl.cdate=ggzj.cdate AND ggzl.cdate=DATE(NOW())
            INNER JOIN stock.gpxx ON gpxx.stockno=ggzl.stockno AND gpxx.cdate=ggzl.cdate
            WHERE (ltsj<80 OR ggzl.price<8) AND super_money>small_money AND small_money<0 ORDER BY super_money DESC LIMIT 10) t order by super_money
            ";
        return $this->query($sql);
    }

    function df($n){
        if(empty($n)){
            $n=0;
        }
        $cdate=$this->getworkdate($n);
        /*
        $sql="select * from (SELECT gpxx.`name`,gpxx.`stockno`,cur,rd10,rd5,rd1,ltsj,zsj,ROUND(super_money/10000) super_money, ROUND(small_money/10000) small_money FROM ggzl
            INNER JOIN gpxx ON ggzl.`cdate`=gpxx.`cdate` AND ggzl.`stockno`=gpxx.`stockno`
            INNER JOIN ggzj ON ggzj.`cdate`=gpxx.`cdate` AND ggzj.`stockno`=gpxx.`stockno`
            WHERE ggzl.cdate=DATE(NOW()) AND (ggzl.stype=1 OR ggzl.stype=2) ORDER BY rd10 limit 15) t order by rd10 desc";
         */
        $sql="select * from (
            SELECT gpxx.name,gpxx.stockno,cur,ltsj,SUM(zdb) zrd,ROUND(SUM(zdb)/COUNT(1),2) avgrd FROM stock.gpxx WHERE cdate>='$cdate' GROUP BY stockno ORDER BY zrd asc LIMIT 15
        ) T order by zrd desc
        ";
        return $this->query($sql);

    }

    function zf($n){

        if(empty($n)){
            $n=0;
        }
        $cdate=$this->getworkdate($n);
        /*
        $sql=" select * from (SELECT gpxx.`name`,gpxx.`stockno`,cur,rd10,rd5,rd1,ltsj,zsj,ROUND(super_money/10000) super_money, ROUND(small_money/10000) small_money FROM ggzl
            INNER JOIN gpxx ON ggzl.`cdate`=gpxx.`cdate` AND ggzl.`stockno`=gpxx.`stockno`
            INNER JOIN ggzj ON ggzj.`cdate`=gpxx.`cdate` AND ggzj.`stockno`=gpxx.`stockno`
            WHERE ggzl.cdate=DATE(NOW()) AND (ggzl.stype=1 OR ggzl.stype=2) ORDER BY rd10 desc limit 15) t order by rd10";
         */


        $sql="select * from (
            SELECT gpxx.name,gpxx.stockno,cur,ltsj,SUM(zdb) zrd,ROUND(SUM(zdb)/COUNT(1),2) avgrd FROM stock.gpxx WHERE cdate>='$cdate' GROUP BY stockno ORDER BY zrd desc LIMIT 15
        ) T order by zrd asc
        ";

        return $this->query($sql);

    }

    function dj(){

        $sql="select * from (SELECT gpxx.name,gpxx.stockno,gpxx.ltsj,gpxx.zdb rd, gpxx.cur,round(ggzj.super_money/10000) super_money,ROUND(ggzj.small_money/10000) small_money
            FROM stock.gpxx
            inner join stock.ggzj on  ggzj.cdate=gpxx.cdate AND ggzj.stockno=gpxx.stockno
            WHERE gpxx.cdate=DATE(NOW()) AND gpxx.ltsj<50 AND gpxx.cur>0 AND  gpxx.cur <5 ORDER BY gpxx.cur limit 15) t order by cur desc";
        return $this->query($sql);

    }

    function ld($n){


        $cdate=$this->getworkdate($n);

        $sql="select * from (SELECT T2.name,T2.stockno,IFNULL( T3.cur,T2.cur) cur,T2.ltsj,T2.zdb zrd,T3.zdb rd FROM (

            SELECT * FROM (

                SELECT NAME,stockno, COUNT(1) cnt,SUM(zdb) zdb,MAX(cur) cur,ltsj FROM stock.gpxx WHERE cdate>='$cdate' AND cdate<DATE(NOW()) AND zdb<0  GROUP BY stockno

            ) T1 WHERE cnt=$n ORDER BY zdb LIMIT 15

        ) T2 LEFT JOIN stock.gpxx T3 ON T3.stockno=T2.stockno AND T3.cdate=DATE(NOW()) ) T4 order by zrd desc";
        //echo $sql;

        return $this->query($sql);
    }

    function yc(){


        $message='小Ｑ持观望太态';
        $rows= $this->db->query("SELECT * FROM yc ORDER BY cdate DESC LIMIT 2");

        $mid=($rows[1]['o']+$rows[1]['c'])/2;

        if($rows[0]['o']-$rows[1]['h']>=0) {
            $message='高开，小Ｑ建议买进';
    }  else if($rows[0]['o']-$rows[1]['l']<=0) {
        $message="低开，小Ｑ建议卖出";
    } else if($rows[0]['o']-$mid>=0) {
        $message= "平高开,小Ｑ持观望态度";
    } else if($rows[0]['o']-$mid<=0) {
        $message= "平低开，小Ｑ持观望态度";
    }
    return $message;

    }

    function pp($stockno){

        $sql="select * from (select stockno,name,price,cdate,remark from cgcp where stockno='$stockno' or name like '%$stockno%' order by cdate desc limit 9) t order by cdate";

        return $this->query($sql);
    }
    function p($stockno){

        $sql="select stockno,name,price,cdate,remark from cgcp where stockno='$stockno' or name like '%$stockno%' order by cdate desc limit 1";

        return $this->query($sql);
    }

    function zlbk()
    {
        $sql="select * from (SELECT * FROM (

            SELECT  T3.*,easymoney_pk_type.pkname FROM (

                SELECT T1.pkcode,total,up,up/total AS rd,T2.avgrd FROM (

                    SELECT pkcode,COUNT(stockno) total FROM stock.easymoney_pk GROUP BY pkcode

                ) T1 LEFT JOIN (


                    SELECT pkcode,COUNT(1) up,SUM(zdb)/COUNT(1) AS avgrd FROM stock.easymoney_pk INNER JOIN stock.gpxx ON easymoney_pk.stockno=gpxx.stockno AND gpxx.cdate=DATE(NOW())
                    WHERE gpxx.zdb>0
                    GROUP BY easymoney_pk.pkcode

                ) T2 ON T1.pkcode=T2.pkcode

            ) T3 LEFT JOIN stock.easymoney_pk_type ON easymoney_pk_type.pkcode=T3.pkcode

        ) T4 WHERE NOT ISNULL(pkname) AND NOT ISNULL(rd) GROUP BY T4.pkcode ORDER BY rd DESC LIMIT 10) T5 order by rd   ;


        ";

        return $this->query($sql);
    }


    function his(){

        $sql="

            SELECT * FROM (

                SELECT * FROM (

                    SELECT  gpxx.stockno,gpxx.name, gpxx.cur, T1.mincls,gpxx.ltsj, ROUND( (gpxx.cur-T1.mincls)/mincls,2) rd FROM stock.gpxx INNER JOIN (

                        SELECT stockno, MIN(cls) mincls  FROM stock_his.his WHERE cycle=0
                        AND cdate>DATE_ADD(NOW(),INTERVAL -180 DAY)
                        GROUP BY stockno

                    ) T1 ON gpxx.stockno=T1.stockno AND gpxx.cdate=DATE(NOW()) AND cur>0

                ) T2 WHERE T2.cur<8.5 AND ltsj<100 ORDER BY rd LIMIT 15

            ) T3 ORDER BY rd DESC";

        return $this->query($sql);

    }

    function sql($sql){

        return $this->query($sql);
    }

    function cmd($cmd){

        // $cmd=preg_replace('/&quot;/','"',$cmd);
        // $cmd=preg_replace('/&amp;/','&',$cmd);
        // $cmds=array();
        // if(preg_match_all('/\s*[\w.]*\s*|\s*"[^"]*?"\s*/',$cmd,$match)){

        // foreach($match[0] as $m){
        //  $m=trim($m);
        //  if(!empty($m)){
        //      $cmds[]= preg_replace('/^[\s\"]*|[\s\"]*$/','', $m);
        //  }
        // }
        // }

        // print_r($cmds);
        // $shell = new ssh2($cmds[0]);
        // $shell->authPassword($cmds[1],$cmds[2]);
        // $shell->openShell("xterm");
        // $result=$shell->cmdExec($cmds[3]);
        // $shell->writeShell("exit");
        // return $result;


    }

    function refresh(){

       $dir="/var/www/stock/collector/";
       $ret=system("/usr/bin/php ".$dir."tencent.php")."\n";

       $ret.=system("/usr/bin/php ".$dir."eastmoney.php");

       return $ret;
    }


    function news(){
        $rows=array();
        $url="http://new.sousuo.gov.cn/list.htm?q=&n=25&p=0&t=paper&sort=pubtime&childtype=&subchildtype=&pcodeJiguan=&pcodeYear=&pcodeNum=&location=&searchfield=&title=&content=&pcode=&puborg=&timetype=timeqb&mintime=&maxtime=";
        phpQuery::newDocumentFileHTML($url);
        foreach(  pq('.info') as $info){
            $td= pq('td', pq($info)->parent());
            $href= pq('a',$info)->attr('href');
            //$href=preg_replace('/http:\/\//','',$href);
            $text= pq('a',$info)->text();
            $cdate= $td->eq(3)->text();//preg_replace('/年|月|日/','-',$td->eq(3)->text());
            $udate= $td->eq(4)->text();// preg_replace('/年|月|日/','-',$td->eq(4)->text());
            $row=array('标题'=>$text,'创建'=>$cdate,'发布'=>$udate);
            array_unshift($rows,$row);
        }
        return $this->msgformat($rows);

    }

    function hot(){
        phpQuery::newDocumentFileHTML("http://xueqiu.com/");
        $data=array();
        foreach(pq('.hot_rank:eq(0) .ti_addition a') as $item){
            $name= pq($item)->attr('title');
            if(preg_match('/\d{6}/', pq($item)->attr('href'),$match)){
                $code=$match[0];
            } else {
                $code='';
            }

            if(!empty($code)){
                array_push($data,array('name'=>$name,'code'=>$code));
            }
        }
        return $this->msgformat($data);
    }

    function i($stockno){



        //phpQuery::newDocumentFileHTML("http://stockpage.10jqka.com.cn/".$stockno."/company/");

        //$text=pq('.product_name')->text();
        //$text=$text."\n\n持股\t".pq('.gray')->parent()->text();
        //$text= preg_replace('/\s+/'," ",$text);
        //$text= preg_replace('/\n+/',"\n",$text);
        //$data=array('info'=>$text);

        //return $text;
        //
        //
        //


        global $db;


        $row= $db->query("select * from gudong where stockno='$stockno'");

        if(empty($row)){
            phpQuery::newDocumentFileHTML("http://www.bestopview.com/gudong/".$stockno.".html");
            $text=pq('#DataNews')->text();
        } else {
            $text= $row[0]['gudong'];
        }

        preg_match("/\n【1.控股股东与实际控制人】[\S\s]+?【2.股东持股变动】/i",$text,$match);



       $info=  $this->gg($stockno);


        return $info.preg_replace('/\||┌|┐|└┼|┬|─|┴ |┤|┘|└|┬\｜||┼|├|┴|┬ |├/','',$match[0]);


    }


    function gy(){

        $sql="
            select * from (
            SELECT gpxx.stockno,gpxx.name,gpxx.ltsj, gpxx.zdb,ROUND( ggzj.super_money/10000) super_money, ROUND(ggzj.small_money/10000) small_money FROM gpxx

            INNER JOIN gudong ON gpxx.stockno=gudong.stockno
            INNER JOIN ggzj ON gpxx.stockno=ggzj.stockno AND ggzj.cdate=gpxx.cdate

            WHERE gpxx.cdate=DATE(NOW())

            AND ( kg LIKE '%中央汇金%' OR  kg LIKE '%国有资产监督管理委员会%' OR kg LIKE '%中华人民共和国财政部%')

            ORDER BY super_money DESC LIMIT 18) T order by super_money";


        return $this->query($sql);

    }


    function dispatch($num,$message,$sender){
        $func='gg';
        $stockno='';
        $flag=false;
        if(preg_match('/^[a-z]+/',$message,$match)){
            $func=$match[0];
            $flag=true;
        }
        if(preg_match('/[0-9 ]+$/',$message,$match)){
            $stockno=trim($match[0]);
            if(strlen($stockno)==8){
                $func='bk';
            }
        } else {
           $stockno= str_replace($func,'',$message);
           $stockno=trim($stockno);
        }

        if(method_exists($this,$func)){
            $sender->send($num,$this->$func($stockno));
        } else {
            $sender->send($num,'无法识别指令');
        }

    }

    }

    $gp=new GP3G($db);




    while(true) {
        do
    {
        $message=$qq->recv();
        //print_r($message);
        foreach($message as $msg) {
            $qq->log($msg);
            $num=$msg['num'];
            if(!in_array($num,$qq->md5)){

                $qq->send($num,$gp->h());
                array_push($qq->md5,$num);
            }
            $txt=$msg['message'];

            try {
                $gp->dispatch($num,$txt,$qq);
            }catch(Exception $e){

                $qq->send($num,$e);

            }
            //$txts=preg_split('/\s+/', $msg['message']);
            //$act=$txts[0];
            //$cmd=isset($txts[1])?$txts[1]:$txts[0];
            //if( preg_match('/^\d{6}$/', $msg['message'],$match)){
            //    $qq->send($num, $gp->gg($txt) );
            //} else if( preg_match('/^28\d{6}$/', $msg['message'],$match)){
            //    $qq->send($num, $gp->bk($txt) );

            //} else if(preg_match('/^[a-z 0-9]*$/',$txt)&&strlen($txt)<20) {

            //    try{
            //        $cmds=array();
            //        if(preg_match('/\s+/',$txt)){
            //            $cmds= preg_split('/\s+/',$txt);
            //        } else if(preg_match('/([a-z]+)([0-9]+)/',$txt,$match)){
            //            $cmds[0]=$match[1];
            //            $cmds[1]=$match[2];
            //        } else {
            //            preg_match('/[a-z0-9]+/',$txt,$cmds);
            //        }
            //        if(count($cmds)==2){
            //            $qq->send($num, $gp->$cmds[0]($cmds[1]));
            //        } else if(!is_numeric($cmds[0])){
            //            echo $cmds[0];
            //            $qq->send($num, $gp->$cmds[0](0));
            //        } else {
            //            $qq->send($num,"无法识别指令");
            //        }
            //    }catch(Exception $e){
            //        $qq->send($num,"无法识别指令");
            //    }

            //}  else if( preg_match('/^cmd(.*)/', $msg['message'],$match)){

            //    $qq->send($num,$gp->cmd($match[1]));

            //} else if( preg_match('/^sql(.*)/', $msg['message'],$match)){
            //    $qq->send($num,$gp->sql($match[1]));

            //}   else if(preg_match('/^[\x80-\xff]*^/',$txt)&&strlen($txt)<=12){
            //    $qq->send($num,$gp->gg($txt));

            //}



        }
    }while(!empty($message));
        //echo "sleep";
        sleep(rand(1,4));
        //$qq->send('546499741',rand(1,444444444444444));
    }
?>
