﻿<?php
/******************
 * 和微信通讯
 * ****************/

//自定义和微信验证的token
define("TOKEN", "weixin");
$wechatObj = new wechat();
//$wechatObj->valid();    //这句用来最开始和微信验证，验证成功之后要注释掉这句

$wechatObj->responseMsg();

class wechat
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature , option
        if($this->checkSignature()){
            echo $echoStr;
            exit;
        }
    }

    private function checkSignature()
    {
        // you must define TOKEN by yourself
        if (!defined("TOKEN")) {
            throw new Exception('TOKEN is not defined!');
        }
    
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];
    
        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        // use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );
    
        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }
    
    public function responseMsg()
    {
        //取得_POST数据
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //分析得到的数据
        if (!empty($postStr)){
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
             the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA); //利用php5.3自带函数，解析xml数据包
            $fromUsername = $postObj->FromUserName; //取得发送者的名字
            $toUsername = $postObj->ToUserName; //取得收信者名字
            $msgType=$postObj->MsgType; //取得数据类型
            
            $textTpl = "<xml>
						<ToUserName><![CDATA[%s]]></ToUserName>
						<FromUserName><![CDATA[%s]]></FromUserName>
						<CreateTime>%s</CreateTime>
						<MsgType><![CDATA[%s]]></MsgType>
						<Content><![CDATA[%s]]></Content>
						<FuncFlag>0</FuncFlag>
						</xml>";  //定义文本信息的xml格式
            
            //如果输入的是文本
            if($msgType=='text'){
                $keyword = trim($postObj->Content);
                $time = time();
                $this->textandvoice($textTpl,$fromUsername,$toUsername,$keyword,$time);    
            }
            //如果输入的是语音
            else if($msgType=='voice'){
                $keyword = trim($postObj->Recognition);
                $time = time();
                $this->textandvoice($textTpl,$fromUsername,$toUsername,$keyword,$time);   
            }
            //如果是关注和取消关注事件
            else if($msgType=="event"){
                $msgTypeEvent=$postObj->Event;
                if($msgTypeEvent=='subscribe'){
                    $time = time();
                    $msgType = "text";
                    $contentStr = '欢迎来到王传军写字的地方，不保证不会五毛，不保证政治正确，不保证不会乱扯，不定时更新，对我不要有期待哟！';
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                }
                else{
                    $time = time();
                    $msgType = "text";
                    $contentStr = '人生就是人与人谈恋爱，总是遇见与分开，遇见之时切莫热切，分开之时也勿拉扯~感谢对我的支持，再见，有缘再见！';
                    $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                    echo $resultStr;
                }
                exit;
            }
            //不支持其他类型的输入
            else{
                $time = time();
                $msgType = "text";
                $contentStr = '目前不支持除文本以外的其他输入类型！';
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
                exit;
            }

        }
        else {
            echo "";
            exit;
        }
    }

    public function textandvoice($textTpl,$fromUsername,$toUsername,$keyword,$time){
        
        $msgType='text';
        
        /* 温度 */
        if(strstr($keyword,'温度')){
            $link=mysql_connect("localhost","root","dHIoPOi7Ej3n");
            mysql_select_db("app_wcjdemo",$link);    //选择数据库
            
            //查找温度值
            $result=mysql_query("SELECT * FROM status");
            while($result_array=mysql_fetch_array($result)){
                if($result_array['Id']==1){
                    $Temperature=$result_array['Temperature'];
                }
            }
  
            //输出回应
            $contentStr="报告大王，现在温度为：".$Temperature."℃";
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;   
        }
        
        /* 湿度 */
        else if(strstr($keyword,'湿度')){
            $link=mysql_connect("localhost","root","dHIoPOi7Ej3n");
            mysql_select_db("app_wcjdemo",$link);    //选择数据库
        
            //查找湿度值
            $result=mysql_query("SELECT * FROM status");
            while($result_array=mysql_fetch_array($result)){
                if($result_array['Id']==1){
                    $Humidity=$result_array['Humidity'];
                }
            }
        
            //输出回应
            $contentStr="报告大王，现在湿度为：".$Humidity."%";
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }
        
        /* 开灯 */
        else if(strstr($keyword,'开灯')){
            $link=mysql_connect("localhost","root","dHIoPOi7Ej3n");
            mysql_select_db("app_wcjdemo",$link);    //选择数据库
        
            //查找灯的状态
            $result=mysql_query("SELECT * FROM status");
            while($result_array=mysql_fetch_array($result)){
                if($result_array['Id']==1){
                    $Ledstatus=$result_array['Ledstatus'];
                }
            }
            
            //开灯指令
            if($Ledstatus==1){
                //输出回应
                $contentStr="报告大王，灯已亮，不需要重复开灯。";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{
                //更新数据库
                $nowtime=date("Y/m/d H:i:s",time());
                $sql="UPDATE status SET Led=1,Statustime='$nowtime' WHERE Id=1";
                if(!mysql_query($sql,$link)){
                    die('Error:'.mysql_error());
                }
                //输出回应
                $contentStr="报告大王，我已经帮您打开灯了。";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }
        }
        
        /* 关灯 */
        else if(strstr($keyword,'关灯')){
            $link=mysql_connect("localhost","root","dHIoPOi7Ej3n");
            mysql_select_db("app_wcjdemo",$link);    //选择数据库
        
            //查找灯的状态
            $result=mysql_query("SELECT * FROM status");
            while($result_array=mysql_fetch_array($result)){
                if($result_array['Id']==1){
                    $Ledstatus=$result_array['Ledstatus'];
                }
            }
        
            //关灯指令
            if($Ledstatus==0){
                //输出回应
                $contentStr="报告大王，灯已关，不需要重复关灯。";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{
                //更新数据库
                $nowtime=date("Y/m/d H:i:s",time());
                $sql="UPDATE status SET Led=0,Statustime='$nowtime' WHERE Id=1";
                if(!mysql_query($sql,$link)){
                    die('Error:'.mysql_error());
                }
                //输出回应
                $contentStr="报告大王，我已经帮您关闭灯了。";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }
        }
        
        /* 查看窗户是否有人 */
        else if(strstr($keyword,'安全')){
            $link=mysql_connect("localhost","root","dHIoPOi7Ej3n");
            mysql_select_db("app_wcjdemo",$link);    //选择数据库
        
            //查找湿度值
            $result=mysql_query("SELECT * FROM status");
            while($result_array=mysql_fetch_array($result)){
                if($result_array['Id']==1){
                    $Windowstatus=$result_array['Windowstatus'];
                }
            }
            
            if($Windowstatus==1){
                //输出回应
                $contentStr="报告大王，您的窗户上有人经过，请小心小偷！";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }else{
                //输出回应
                $contentStr="报告大王，您的窗户上没有人经过，家里很安全！";
                $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
                echo $resultStr;
            }
        }
        else{
            $contentStr="抱歉大王，无法识别您的指令：".$keyword;
            $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
            echo $resultStr;
        }
    }   
}

?>
