<?php
namespace Admin\Controller;
class ReadWriteDataController extends AdminController
{
	/**
	 * 导入数据
	 */
	public function writeData(){
        $result = C("db_table");
		$this->assign("result",$result);
		$this->display();
	}

	/**
	 * 一些配置设置
	 */
	public function configure(){

   		$this->display();
	}

	public function ReadData(){

		$this->display();

	}

}