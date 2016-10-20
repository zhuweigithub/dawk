<?php
/**
http://edi.zjs.com.cn/test4/receive.asmx
客户标识：TestClient
密钥： 17AF4124-BC69-4A5F-A439-1505E30DF24B
常量：0123456789abc
 */


/**
 * 线上
 *
 * 账号:ShiYi_WangLuo
秘钥:A8D3D23E-D231-45FF-93BA-EEA712D77B65
常量：z宅J急S送g
正式下单接口
http://edi.zjs.com.cn/svsr/receive.asmx
正式查货接口
http://edi.zjs.com.cn/svst/tracking.asmx
 */

namespace   Admin\Lib\Api\Logistics;

/**
 * Class ZJSLogistics
 * @package Admin\Lib\Api
 */
class ZjsLogistics{

    private $_clientFlag;//客户标识

    private $_secretKey;//客户密钥

    private $_strConst;//常量值

    private $xml;

    private $postData; //post数据

    private $rootNode;//post数据根节点

    private $url;//post地址

    public function __construct(){
        $this->_clientFlag = 'TestClient';
        $this->_secretKey = '17AF4124-BC69-4A5F-A439-1505E30DF24B';
        $this->_strConst = '0123456789abc';

        /*$this->_clientFlag = 'DaLing';
        $this->_secretKey = '6B711F1A-F1EE-4558-A0C8-39FAA6AF75A5';
        $this->_strConst = 'z宅J急S送g';*/

        //正式
        /*$this->_clientFlag = 'ShiYi_WangLuo';
        $this->_secretKey = 'A8D3D23E-D231-45FF-93BA-EEA712D77B65';
        $this->_strConst = 'z宅J急S送g';*/
    }

    public function create_order($data){

        $this->rootNode = 'RequestOrder';

        $this->data = $data;

        $this->setPostData();

        $this->url = 'http://edi.zjs.com.cn/test4/receive.asmx/OrderXML';

        $output = http($this->url,$this->postData,'post');

        return $this->_get_arr($output);
    }

    public function get($data){

        $this->rootNode = 'BatchQueryRequest';

        $this->data = $data;

        $this->_setPostData();

        $this->url = 'http://edi.zjs.com.cn/svst/tracking.asmx/Get';

        $output = http($this->url,$this->postData,'post');

        return $this->_get_arr($output);
    }

    private function _get_arr($output){
        $output=str_replace(array('&lt;', '&gt;'), array('<','>'),$output);

        $arrayXml = new \Admin\Lib\Org\Util\ArrayXml();
        $arr = $arrayXml->toArray($output);

        $key = array_keys($arr);
        $arr = current($arr[current($key)]);

        $key = array_keys($arr);
        $arr = current($arr[current($key)]);

        return $this->_format_array($arr);
    }

    private function _format_array($arr,$flag = FALSE){
        if($flag){
            foreach($arr as $k=>$v){
                $arr[$k] = $this->_format_array($v);
            }
        }else{
            foreach($arr as $key=>$value){
                if(is_string($key)){
                    $value = current($value);
                    if(is_array($value)){//fb($value);
                        $tmpKey = array_keys($value);//fb($tmpKey);
                        $tmp = $value[current($tmpKey)];
                        $keys = array_keys($tmp);
                        if(array_sum($keys) > 1){
                            unset($arr[$key]);
                            $arr[$key][current($tmpKey)] = $this->format_array($tmp , TRUE);
                        }else{
                            $arr[$key] = $this->format_array($value);
                        }
                    }elseif(is_string($value)){
                        $arr[$key] = $value;
                    }
                }
            }
        }
        return $arr;
    }

    function __set($property, $value){
        if($property == 'data' && is_array($value)){
            $this->$property = array_merge(array(
                    'logisticProviderID' => $this->_clientFlag
                ) , $value);
        }
    }

    //设置post数据
    private function _setPostData(){
        $postData = array(
                'clientFlag' => $this->_clientFlag
                ,'xml' => $this->_getXml()
                ,'verifyData' => $this->_getVerifyData()
            );
        $this->postData = $this->_build_query($postData);
    }

    private function _getXml(){
        $xml = new \Admin\Lib\Org\Util\ArrayXml();
        $xml->set('rootNode',$this->rootNode);
        //fb($this->data);
        $this->xml = $xml->toXml($this->data);//fb($this->xml);
        return $this->xml;
    }

    private function _build_query($arr){
        if(!empty($arr) && is_array($arr)){
            $str = '';
            foreach($arr as $key=>$value){
                $value = trim($value);
                $str .= "&$key=$value";
            }
            return substr($str,1);
        }
    }

    /**
     * 生成密钥
     * @return string
     */
    private function _getVerifyData(){
        $rdm1 = getRandChar(4);
        $rdm2 = getRandChar(4);

        $str = $rdm1 . $this->_clientFlag . trim($this->xml) . $this->_secretKey . $this->_strConst . $rdm2;

        $fileType=mb_detect_encoding($str,array('UTF-8','GBK','LATIN1','BIG5'));
        if($fileType!='UTF-8') {
            $str = mb_convert_encoding($str, "UTF-8", "GBK");
        }

        $strVerifyData = $rdm1.substr(md5($str),7,21).$rdm2;//生成密钥

        return $strVerifyData;
    }
}