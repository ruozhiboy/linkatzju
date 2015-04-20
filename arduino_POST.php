<?php
//取得_POST数据
$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

//分析得到的数据
if (!empty($postStr)){
    /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
     the best way is to check the validity of xml by yourself */
    libxml_disable_entity_loader(true);

    $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA); //利用php5.3自带函数，解析xml数据包	
    $Temperature=$postObj->Temperature; 
    $Humidity=$postObj->Humidity;
    $Windowstatus=$postObj->Windowstatus;
    $Ledstatus=$postObj->Ledstatu;  //get post data
    $link=mysql_connect("localhost","root","dHIoPOi7Ej3n");
    mysql_select_db("app_wcjdemo",$link);    //link data base        
    //updata data     
    $nowtime=date("Y/m/d H:i:s",time());
    $sql="UPDATE status SET Temperature='$Temperature',Humidity='$Humidity',Windowstatus='$Windowstatus',Ledstatus='$Ledstatus',Statustime='$nowtime' WHERE Id=1";
    if(!mysql_query($sql,$link)){
    die('Error:'.mysql_error());    
    }
        
    // select data
    $result=mysql_query("SELECT * FROM status");
    while($result_array=mysql_fetch_array($result)){
        if($result_array['Id']==1){
           $Led=$result_array['Led'];
         }
    }
        
    //close database 
    mysql_close($link);
        
    //echo to arduino
    echo " {".$Led."}";
}
    ?>