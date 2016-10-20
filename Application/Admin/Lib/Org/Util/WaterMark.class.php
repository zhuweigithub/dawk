<?php

namespace Admin\Lib\Org\Util;
/**  
 * 加水印类，支持文字、图片水印以及对透明度的设置、水印图片背景透明。   
 * date : 2015-07-13 
 * */
/*
 * =============水印类使用示例==============
//为图片添加水印==>
$main_pic = APP_ROOT_PATH.ATTACH_PATH.'image1.png';
//实例化对象
$obj = new \Admin\Lib\Org\Util\WaterMask($main_pic);
//类型：0为文字水印、1为图片水印
$obj->waterType = 1;
//水印透明度，值越小透明度越高
$obj->transparent = 30;
//水印文字
$obj->waterStr = '实意良心';
//水印图片（绝对路径）
$obj->waterImg = APP_ROOT_PATH.ATTACH_PATH.'watermark/img/logo.png';//水印图片
//文字字体大小
$obj->fontSize = 15;
//水印文字颜色（RGB）
$obj->fontColor = array(255,0,0);
//字体文件（绝对路径）
$obj->fontFile = APP_ROOT_PATH.ATTACH_PATH.'watermark/font/simsun.ttc';
//输出水印图片文件覆盖到输入的图片文件
$obj->output();
//为图片添加水印<==
*/

class WaterMark { 

    /**
     * 水印类型
     * @var int $waterType 0为文字水印 ；1为图片水印   
     */ 
	public $waterType = 1;

    /**
     * 水印位置 类型
     * @var int $pos  默认为9(右下角)
     */ 
    public $pos = 9;

    /**
     * 水印透明度 
     * @var int  $transparent  水印透明度(值越小越透明)
     */ 
    public $transparent = 30;

    /**
     * 如果是文字水印，则需要加的水印文字
     * @var string $waterStr  默认值
     */ 
    public $waterStr = '实意良心';

    /**
     * 文字字体大小   
     * @var int $fontSize  字体大小
     */ 
    public $fontSize = 22;

    /**
     * 水印文字颜色（RGB）   
     * @var array $fontColor  水印文字颜色（RGB）   
     */ 
    public $fontColor = array ( 144, 238, 144 );

    /**
     * 字体文件   
     * @var unknown_type
     */ 
    public $fontFile = 'simsun.ttc';	//'stcaiyun.ttf';

    /**
     * 水印图片   
     * @var string $waterImg
     */ 
    public $waterImg = 'logo.png';

    /**
     * 需要添加水印的图片   
     * @var string $srcImg
     */ 
    public $srcImg = '';  

    /**
     * 图片句柄   
     * @var string $im
     */ 
    private $im = '';  

    /**
     * 水印图片句柄   
     * @var string $water_im  
     */ 
    private $water_im = '';  

    /**
     * 图片信息   
     * @var array  $srcImg_info
     */ 
    private $srcImg_info = '';  

    /**
     * 水印图片信息   
     * @var array $waterImg_info  
     */ 
    private $waterImg_info = '';  

    /**
     * 水印文字宽度   
     * @var int $str_w  
     */ 
    private $str_w = '';  

    /**
     * 水印文字高度   
     * @var int $str_h  
     */ 
    private $str_h = '';  

    /**
     * 水印X坐标   
     * @var int $x
     */ 
    private $x = '';  

    /**
     * 水印y坐标   
     * @var int   $y
     */ 
    private $y = ''; 

    /**
     * 构造函数，通过传入需要加水印的源图片初始化源图片
     * @param string $srcImg  需要加水印的源图片
     */ 
    public function __construct ( $srcImg ) {
        if(file_exists( $srcImg )){		//源文件存在 
            $this->srcImg = $srcImg;													//源文件
            $this->waterImg = APP_ROOT_PATH.'/Public/img/watermark/'.$this->waterImg;	//水印图片(默认)
            $this->fontFile = APP_ROOT_PATH.'/Public/fonts/watermark/'.$this->fontFile;	//字体文件(默认)
        }else{							//源文件不存在 
            echo '源文件'.$srcImg.'不存在，请检查看文件路径是否正确'; 
            exit(); 
        } 
    } 

    /**
     * 获取需要添加水印的图片的信息，并载入图片
     */ 
    public function imginfo () {    
        $this -> srcImg_info = getimagesize($this -> srcImg); 
        //var_dump($this -> srcImg);exit(); 
        switch ($this -> srcImg_info[2]) { 
            case 3 :	//png 
                $this -> im = imagecreatefrompng($this -> srcImg); 
                break 1; 
            case 2 :  	//jpeg/jpg 
                $this -> im = imagecreatefromjpeg($this -> srcImg); 
                break 1; 
            case 1 :  	//gif 
                $this -> im = imagecreatefromgif($this -> srcImg); 
                break 1; 
            default : 
                echo '源图片文件'. $this -> srcImg .'格式不正确，目前本函数只支持PNG、JPEG、GIF图片水印功能'; 
                exit(); 
        } 
    } 

    /**
     * 获取水印图片的信息，并载入图片
     */ 
    private function waterimginfo () {  
        $this -> waterImg_info = getimagesize($this -> waterImg); 
        //var_dump($this -> waterImg);exit();
        switch ($this -> waterImg_info[2]) { 
            case 3 : 
                $this -> water_im = imagecreatefrompng($this -> waterImg); 
                break 1; 
            case 2 : 
                $this -> water_im = imagecreatefromjpeg($this -> waterImg); 
                break 1; 
            case 1 : 
                $this -> water_im = imagecreatefromgif($this -> waterImg); 
                break 1; 
            default : 
                echo '源图片文件'. $this -> srcImg .'格式不正确，目前本函数只支持PNG、JPEG、GIF图片水印功能'; 
                exit(); 
        } 
    } 

    /**
     * 水印位置算法   
     */ 
    private function waterpos (){  
        switch ($this -> pos) { 
            case 0 : //随机位置    
                $this -> x = rand(0, $this -> srcImg_info[0] - $this -> waterImg_info[0]); 
                $this -> y = rand(0, $this -> srcImg_info[1] - $this -> waterImg_info[1]); 
                break 1; 
            case 1 : //上左    
                $this -> x = 20; 
                $this -> y = 20; 
                break 1; 
            case 2 : //上中    
                $this -> x = ($this -> srcImg_info[0] - $this -> waterImg_info[0]) / 2; 
                $this -> y = 20; 
                break 1; 
            case 3 : //上右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0]; 
                $this -> y = 20; 
                break 1; 
            case 4 : //中左    
                $this -> x = 20; 
                $this -> y = ($this -> srcImg_info[1] - $this -> waterImg_info[1]) / 2; 
                break 1; 
            case 5 : //中中    
                $this -> x = ($this -> srcImg_info[0] - $this -> waterImg_info[0]) / 2; 
                $this -> y = ($this -> srcImg_info[1] - $this -> waterImg_info[1]) / 2; 
                break 1; 
            case 6 : //中右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0] - 20; 
                $this -> y = ($this -> srcImg_info[1] - $this -> waterImg_info[1]) / 2; 
                break 1; 
            case 7 : //下左    
                $this -> x = 20; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
            case 8 : //下中
                $this -> x = ($this -> srcImg_info[0] - $this -> waterImg_info[0]) / 2; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
            case 9 : //下右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0] - 20; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
            default : //下右    
                $this -> x = $this -> srcImg_info[0] - $this -> waterImg_info[0] - 20; 
                $this -> y = $this -> srcImg_info[1] - $this -> waterImg_info[1] - 20; 
                break 1; 
        } 
    } 

    /**
     * 加图片水印
     */ 
    private function waterimg (){ 
        if ($this -> srcImg_info[0] <= $this -> waterImg_info[0] || $this -> srcImg_info[1] <= $this -> waterImg_info[1]) { 
            echo '图片尺寸太小，无法加水印，请上传一张大图片'; 
            exit(); 
        } 

        //计算水印位置 
        $this->waterpos(); 
        $cut = imagecreatetruecolor($this -> waterImg_info[0], $this -> waterImg_info[1]); 
        imagecopy($cut, $this -> im, 0, 0, $this -> x, $this -> y, $this -> waterImg_info[0],  
        $this -> waterImg_info[1]); 
        $pct = $this -> transparent; 
        imagecopy($cut, $this -> water_im, 0, 0, 0, 0, $this -> waterImg_info[0],  
        $this -> waterImg_info[1]); 
        
        //将图片与水印图片合成 
        imagecopymerge($this -> im, $cut, $this -> x, $this -> y, 0, 0, $this -> waterImg_info[0], $this -> waterImg_info[1], $pct); 
    } 

    /**
     * 加文字水印
     */ 
    private function waterstr () { 
        $rect = imagettfbbox($this -> fontSize, 0, $this -> fontFile, $this -> waterStr); 
        $w = abs($rect[2] - $rect[6]); 
        $h = abs($rect[3] - $rect[7]); 
        $fontHeight = $this -> fontSize; 
        $this -> water_im = imagecreatetruecolor($w, $h); 
        imagealphablending($this -> water_im, false); 
        imagesavealpha($this -> water_im, true); 
        $white_alpha = imagecolorallocatealpha($this -> water_im, 255, 255, 255, 127); 
        imagefill($this -> water_im, 0, 0, $white_alpha); 
        $color = imagecolorallocate($this -> water_im, $this -> fontColor[0], $this -> fontColor[1],  
        $this -> fontColor[2]); 
        imagettftext($this -> water_im, $this -> fontSize, 0, 0, $this -> fontSize, $color,  
        $this -> fontFile, $this -> waterStr); 
        $this -> waterImg_info = array ( 
            0 => $w, 1 => $h 
        ); 
        $this->waterimg();
    } 

    /**
     * 水印图片输出
     */ 
    public function output (){ 
        $this->imginfo(); 
        if ($this -> waterType == 0) { 
            $this->waterstr(); 
        }else{ 
            $this->waterimginfo(); 
            $this->waterimg(); 
        }
        switch ($this -> srcImg_info[2]) { 
            case 3 : 
                imagepng($this -> im, $this -> srcImg); 
                break 1; 
            case 2 : 
                imagejpeg($this -> im, $this -> srcImg); 
                break 1; 
            case 1 : 
                imagegif($this -> im, $this -> srcImg); 
                break 1; 
            default : 
                die('添加水印失败！'); 
                break; 
        } 
        
        //图片合成后的后续销毁处理 
        imagedestroy($this -> im);
        imagedestroy($this -> water_im);
    } 

}