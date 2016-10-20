<?php
namespace Admin\Lib\Org\Util;
/*
*文件名：  Storage.php
*作者：张鹏
*创建时间：  2015年07月29日
*功能描述：系统文件存储模块
* */

final class Storage{
	static public $_instance = null;
	
	private $handle = array (
		'image' => array ( 
				"goods_pic",
				"goods_pics",
		),
		'video' => array (),
		'audio' => array (),
	);
	
	private $cfg = array();
	
	/**
	 * 构造函数
	*/
	private function storage () {
	}
	
	/**
	 * 构造函数
	 */
	private function __construct ($data = array ()) {
		$this->cfg = $data;
		if (!is_array ($this->cfg ) ) {
			$this->cfg = array ();
		}
		
		$this->cfg['doc_root'] = sto_fix_path (C('uploadPath') );
		$this->cfg['sto_root'] = sto_fix_path (C('uploadPath') . '/' . C('nasMap') );
		$this->cfg['sto_tmp_root'] = sto_fix_path ( C('uploadPath') . '/' . C('nasMap'). '/' . '/temp' );
		$this->cfg['sto_url_root'] = C('nasMap') . '/';
		//var_dump($this->cfg);die();
	}
	
	/**
	 * 类入口
	 * @return storage
	 */
	static public function instance () {
		if (!storage::$_instance instanceof storage ) {
			storage::$_instance = new storage ();
		}
		return storage::$_instance;
	}
	
	/**
	 * 将图片,音视频文件移动至相应文件夹
	 * @id 文件所属记录编号
	 * @file 文件相对路径
	 * @type 文件类型
	 * @suffix 倒入之后的文件名后缀，是扩展名之前的名称的后缀
	 * @return Image
	 */
	public function import_img($file,$id,$type,$suffix=''){
		$file = $this->format_url($file);
		$type = strtolower ($type );
		
		$handle = 'video';
		if ($type !== 'video' ) {
			$handle = 'image';
		}
		
		if (!in_array ($type, $this->handle[$handle]) ) {
			$type = $this->handle[$handle][0];
		}
	
		$size = array ('width' => 0, 'height' => 0, 'type' => 'video');
		if ($handle === 'image' ) {
			$size['type'] = 'pic';
		}
		
		$file_url = $this->publish( array ('id' => $id, 'file' => $file, 'type' => $type, 'handle' => $handle, 'publish_dir' => 'images', 'suffix' => $suffix) );
		
		$file_url = $this->format_url($file_url,true);
		
		$returnValue = new Image (
				$file_url,
				$size['width'], $size['height'], $size['type']
		);
		
		return $returnValue;
	}
	
	/**
	 * 将文件移到发布文件夹
	 * @data = array ('id' => 记录编号,'file'	=> 文件相对路径, 'type'	=> 文件类型, 'handle' => 文件分类, 'publish_dir' => 文件发布目录名称, 'suffix' => 倒入之后的文件名后缀，是扩展名之前的名称的后缀 )
	 * @return  NULL|string
	 */
	private function publish ($data) {
		$basename = $this->_basename($data['file'] );
		
		$tmp_root = sto_fix_path ($this->cfg['doc_root'] . '/' . dirname ($data['file'] ) . '/' );
		var_dump($tmp_root);die();
		if (!is_file ($tmp_root . $basename ) ) {
			return NULL;
		}
		
		$basename = explode ('.', strtolower($basename) );
		$basename = $data['id'] . '.' . $basename[count ($basename) - 1];
	
		if (strlen ($data['suffix'] ) ) {
			$basename = explode ('.', $basename );
			$basename[count ($basename) - 2] .= '_' . $data['suffix'];
			$basename = join ('.', $basename );
		}
	
		$publish_dir = $this->get_publish_dir ($data );
	
		$des_path = $this->cfg['sto_root'] . '/' . $data['publish_dir'] . '/' . $publish_dir;
		
		$this->make_dir ($des_path);
	
		$publish_dir =  sto_fix_path ($this->cfg['sto_url_root'] . '/' . $data['publish_dir']. '/' . $publish_dir . '/' );
		$returnValue =  sto_fix_path ($publish_dir . $basename );
		copy ($tmp_root . $this->_basename ($data['file'] ), $des_path . '/' . $basename );
		
		return $returnValue;
	}
	
	/**
	 * 递归创建目录
	 * @data 路径
	 * @return void
	 */
	private function make_dir ($data) {
		$IoHandler = new IoHandler();
		$IoHandler->MakeDir($data);
	}
	
	/**
	 * 获取路径的文件名称
	 * @data 路径
	 * @return string
	 */
	private function _basename ($data ) {
		$returnValue = new IoHandler();
		$returnValue = $returnValue->BaseName ($data );
	
		return $returnValue;
	}
	
	/**
	 * 获取相应类型的发布目录
	 * @data = array ('type' => 文件类型, 'id' => 文件所属记录编号)
	 * @return string
	 */
	private function get_publish_dir ($data) {
		$returnValue = $data['type'] . '/' . ($data['id'] - $data['id'] % 1000);
	
		return $returnValue;
	}
	
	/**
	 *  格式化url，
	 * @param unknown_type $url
	 * @param unknown_type $has_param 为true表示给url携带参数，为false是去掉url的参数
	 */
	static public function format_url($url,$has_param = false){
		if(empty($url)) return '';
		list($filepath,$param) = explode('?',$url);
		if(!empty($param)) $param = trim($param);
		if(empty($param)){
			$param_arr = array();
		}
		else{
			$param_arr = explode('&',$param);
		}
	
		if($has_param){
			$param_arr[] = time();
			$param = implode('&',$param_arr);
			$url = $filepath . '?' . $param;
		}
		else{
			$url = $filepath;
		}
		return $url;
	}
}

class Image {
	
	private $filename;
	private $width;
	private $height;
	private $type ; //取值为pic
	
	/**
	* @param $filename
	* @param $type 取值为pic
	* @param $width
	* @param $height
	*/
	function __construct($filename,$width = 0,$height = 0,$type = 'pic'){
		foreach (array ('filename', 'width', 'height', 'type') as $k => $v ) {
			$this->$v = $$v;
		}
		
		$this->width = intVal ($this->width );
		$this->height = intVal ($this->height );		
		$this->type = strtolower ($this->type );
	}
	
	/**
	 * 获取图片文件宽、高
	 *
	 * @height_or_width string = enum ('width', 'height' )
	 *
	 * @return int
	 */
	private function get_img_info ( ) {

		$size = @getImageSize (C('uploadPath') . storage::format_url($this->get_filename () ) );
		if (is_array ($size ) ) {
			$this->width = $size[0];
			$this->height = $size[1];
		}
		
		return $size;
	}
	
	/**
	* 获取图片的宽
	*/
	function get_width() {

		if (intVal ($this->width ) == 0 ) {
			 $this->get_img_info ( );
		}
		
		return $this->width;
	}
	
	/**
	* 获取图片的高
	*/
	function get_height() {
		
		if (intVal ($this->height ) == 0 ) {
			$this->get_img_info ( );
		}
		
		return $this->height;
	}
	
	/**
	* 获取图片的相对路径
	*/
	function get_filename() {
		$returnValue = $this->filename;
		return $returnValue;
	}

	/**
	 * 生成缩略图
	 * @param $suffix 取值为's','b','o'或为空
	 * @param $width  缩略图规格的宽,它和$height二者至少必输其一
	 * @param $height 缩略图规格的高,它和$width二者至少必输其一
	 * @param $zoom_mode 缩放模式，
	 *      1表示以宽为准缩放 $width 要大于0;
	 *      2表示以高为准缩放，$height要大于0;
	 *      3表示自动按短边自适应播放，$width和$height都要输入
	 * @param $clip_mode 表示裁剪模式.只有当$width 和$height都大于0时有效
	 *                    0表示不裁剪
	 *                    1表示居中裁剪
	 *                    2表示取左上角
	 *                    3表示取右下角
	 * @param $suffixs 缩略图后缀扩展数组 *
	 *
	 * @return Image
	 */
	function make_thumb ($suffix,$width,$height,$zoom_mode = 1,$clip_mode = 0,$suffixs = array ()) {
		$suffixs = array_merge ($suffixs, array ('o', 'b', 's') );
		if (in_array ($suffix, $suffixs ) ) {
			$suffix = '_' . $suffix;
		} else {
			$suffix = '';
		}
		
		$returnValue = $this;
		$s_pic = C('uploadPath')  . Storage::format_url($this->filename);
		
		$width = intVal ($width );
		$height = intVal ($height );
		
		if (is_file ($s_pic ) && ($width || $height)) {
			if($width == 0 && $height > 0){
				$zoom_mode = 2;
			}
			else if($width > 0 && $height == 0){
				$zoom_mode = 1;
			}
			$this->type = strtolower ($this->type );
			//if($this->type === 'video') {
				//$s_pic = C('uploadPath') .  sto_get_video_cover (Storage::format_url($this->filename ) );
			//}
			$t_pic = explode ('.', $s_pic );
			$t_pic[count ($t_pic) - 2] .=  $suffix;
				
			$t_pic = join ('.', $t_pic );
				
			$maxthumbwidth=0;$maxthumbheight=0;$src_x=0;$src_y=0;$src_w=0;$src_h = 0;
			
			switch($zoom_mode ){
				//以宽为准按比例缩放
				case 1:
					$size = get_img_size($s_pic,2,$width,$height);
					break;
					//以高为准缩放
				case 2:
					assert($height > 0);
					$size = get_img_size($s_pic,3,$width,$height);
					break;
				case 3:
					assert($height > 0 && $width > 0);
					/*
					 if($height < $width)  {$zoom_mode = 2;$size = get_img_size($s_pic,3,$width,$height);}
					else {$zoom_mode = 1;$size = get_img_size($s_pic,2,$width,$height);}*/
					$size = get_img_size($s_pic,2,$width,$height);
					$t_fwtoh = round(floatval($width)/$height,2);
					$s_fwtoh = round(floatval($size['src_width'])/$size['src_height'],2);
					if($t_fwtoh < $s_fwtoh)  {$zoom_mode = 2;$size = get_img_size($s_pic,3,$width,$height);}
					else {$zoom_mode = 1;$size = get_img_size($s_pic,2,$width,$height);}
					break;
				default:
					assert(0);
					break;

			}
			$src_w = $size['src_width'];
			$src_h = $size['src_height'];
				
			//裁剪只有当$width和$height的值大于0时有效
			if($width > 0 && $height > 0){
				switch($clip_mode ){
					//居中裁剪
					case 1:
						if($zoom_mode == 1){
							if($height < $size['height']){
								$src_h = $size['src_width'] / $width  * $height;
								$src_y = intval(($size['src_height'] - $src_h ) / 2);
								if($src_y < 0 ) $src_y = 0;
							}
						}
						else{
							if($width < $size['width']){
								$src_w = $size['src_height'] / $height * $width;
								$src_x = intval(($size['src_width'] - $src_w )/2);
								if($src_x < 0 ) $src_x = 0;
							}
						}

						break;
						//取左上角
					case 2:
						if($zoom_mode == 1){
							if($height < $size['height']){
								$src_h = $size['src_width'] / $width  * $height;

							}
						}
						else{
							if($width < $size['width']){
								$src_w = $size['src_height'] / $height * $width;
									
							}
						}
						break;
						//取右下角
					case 3:
						if($zoom_mode == 1){
							if($height < $size['height']){
								$src_h = $size['src_width'] / $width  * $height;
								$src_y = intval(($size['src_height'] - $src_h ) );
								if($src_y < 0 ) $src_y = 0;
							}
						}
						else{
							if($width < $size['width']){
								$src_w = $size['src_height'] / $height * $width;
								$src_x = intval(($size['src_width'] - $src_w ));
								if($src_x < 0 ) $src_x = 0;

							}
						}
						break;

				}
			}
			$maxthumbwidth =0;$maxthumbheight = 0;
			if($zoom_mode == 1) $height = 0;
			else $width = 0;
			
			if(!empty($t_pic)){
				$t_pic = str_replace('_o', '', $t_pic);
			}
			
			makethumb($s_pic,$t_pic,$width,$height,0,0,$src_x,$src_y,$src_w,$src_h);
			$t_pic = Storage::format_url($t_pic,true);
			$returnValue = new Image (sto_fix_path (str_replace (C('uploadPath'), '', $t_pic ) ), $size['width'], $size['height'] );
		}

		return $returnValue;
	}

}

/**
 * 标准化文件路径
 * @path String 文件路径
 * @return String
 */
function sto_fix_path ($path) {
	return str_replace (array ('\\', '//'), array ('/', '/'), $path );
}