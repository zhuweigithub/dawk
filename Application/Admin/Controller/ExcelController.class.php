<?php
namespace Admin\Controller;
use Think\Exception;

class ExcelController extends AdminController
{
    const MONTHLY_ORDER = 30;
    public function test(){

        $param = array(
            "0" => array("customer_name"=> "11111"),
            "1" => array("customer_name"=> "2222"),
        );
        $db = M("Send_detail");
        $db->startTrans();
        try{
            $result = $db->addAll($param);
            $db->commit();
            echo   $db->getLastSql();
        }catch (Exception  $e){
            $db->rollback();
            $this->error("发生未知错误,数据回滚,详情：".$e);
        }

        exit;
        $highestRow = 27468;
        $startIndex = 1;
        $maxLength = $highestRow;

        if( $highestRow > 1000 ){
            $forNum = ceil( $highestRow / 1000 );
            $lastNum = $highestRow % 1000;
            for( $i = 0 ; $i < $forNum ; $i++ ){
                if( $lastNum != 0 && ($i + 1) == $forNum){
                    $maxLength = $lastNum;
                }
                $startIndex = $i * $maxLength + 1;
                echo $i .'------'.$startIndex .'---'.$maxLength.'---'.$maxLength .'\n';
            }
        }else{

        }

    }
	public function importExp()
	{
		$tableName = $_POST['table_name'];

        $uid = session('user_auth')['uid'];
        if(empty($_POST['sub_store'])){
            $param['uid'] = $uid;
            $param['position'] = 1;
            $member = M("Member")->field("area")->where($param)->find();
            if(count($member) > 0){
                $group_id = $member['area'];
            }else{
                $this->error("请选择地域组");
            }
        }else{
            $group_id = $_POST['sub_store'];
		$tableName = C("db_table")[$tableName];
		if (empty($tableName)) {
			$this->error("请选择上传的库");
		}
		header("Content-type: text/html;charset=utf-8"); //设置页面内容是html编码格式是utf-8
		if (!empty($_FILES['excel']['name'])) {
			$uploads          = "Public/Uploads/";
			$upload           = new \Think\Upload(); // 实例化上传类
			$upload->maxSize  = 5242880; // 设置附件上传大小
			$upload->exts     = array('xlsx', 'xls'); // 设置附件上传类型
			$upload->rootPath = $uploads; // 设置附件上传根目录
			$upload->subName  = array('date', 'Ymd');
			// 上传单个文件
			$info = $upload->uploadOne($_FILES['excel']);
			if (!$info) { // 上传错误提示错误信息
				$this->error($upload->getError());
			} else { //上传Excel成功
				$base_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/'));
				$base_path = str_replace('/Application/Admin/Controller', '', $base_path) . '/';
				$path      = $base_path . $uploads . $info['savepath'] . $info['savename'];
				$this->importExcel($tableName, $path ,$group_id);
			}
		} else {
			$this->error("请选择上传的文件");
		}
	    }
    }

	public function inputCsv(){

		header("Content-type: text/html;charset=utf-8"); //设置页面内容是html编码格式是utf-8
		if (!empty($_FILES['file']['name'])) {
			$uploads          = "Public/Uploads/";
			$upload           = new \Think\Upload(); // 实例化上传类
			$upload->maxSize  = 5242880; // 设置附件上传大小
			$upload->exts     = array('csv'); // 设置附件上传类型
			$upload->rootPath = $uploads; // 设置附件上传根目录
			$upload->subName  = array('date', 'Ymd');
			// 上传单个文件
			$info = $upload->uploadOne($_FILES['file']);
			if (!$info) { // 上传错误提示错误信息
				$this->error($upload->getError());
			} else { //上传Excel成功
				$base_path = str_replace('\\', '/', realpath(dirname(__FILE__) . '/'));
				$base_path = str_replace('/Application/Admin/Controller', '', $base_path) . '/';
				$path      = $base_path . $uploads . $info['savepath'] . $info['savename'];
				//$this->importExcel($tableName, $path ,$group_id);
				echo $path;exit;
				$file = fopen($path,"r");
				while(! feof($file))
				{
					dump(fgetcsv($file));
				}
				fclose($file);
			}
		} else {
			$this->error("请选择上传的文件");
		}


}
	public function importExcel($tableName, $filename ,$group_id)
	{

		error_reporting(E_ALL);
        ini_set("memory_limit","1000M");
        set_time_limit(2000);
        date_default_timezone_set('Asia/ShangHai');
		require_once 'Application/Admin/Lib/Org/Util/PHPExcel/IOFactory.php';
		$reader        = \PHPExcel_IOFactory::createReaderForFile($filename); //设置以Excel5格式(Excel97-2003工作簿)

		$PHPExcel      = $reader->load($filename); // 载入excel文件
		$sheet         = $PHPExcel->getSheet(0); // 读取第一個工作表
		$highestRow    = $sheet->getHighestRow(); // 取得总行数
		$highestColMum = $sheet->getHighestColumn(); // 取得总列数
        /*创建回滚机制*/
        if($tableName == 'send_detail'){
            $db = M($tableName);
            $db->startTrans();
        }else{
            $this->error("请选择正确的库！");
        }

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($column = 'A'; $column <= $highestColMum; $column++) { //列数是以A列开始
                if( $column == 'C' || $column == 'D' || $column == 'F' || $column == 'G' || $column == 'I' || $column == 'J'
                    || $column == 'K'  || $column == 'M' || $column == 'P' || $column == 'R' || $column == 'U'){
                    continue;
                }
                $dataSet[] = $sheet->getCell($column . $row)->getValue();

            }
            if((int)$dataSet[0] <= 0){
                unset($dataSet);
                continue;
            }
            $param = $this->getSubStore($dataSet[4] ,$group_id );

            $type = $this->getRule($dataSet[2]);
            $balancing = $this->getBalancing($dataSet[6],$dataSet[8],$type);
            $arr[] = array(
                "in_out_date" => $dataSet[1],
                "customer_code" => $dataSet[2],
                "customer_name" => $dataSet[3],
                "sub_store" => $dataSet[4],
                "express_number" => $dataSet[5],
                "send_province" => $dataSet[6],
                "send_city" => $dataSet[7],
                "weight" => $dataSet[8],
                "post_money" => $dataSet[9],
                "balancing" => $balancing,
                "in_out_org_name" => $param['in_out_org_name'],
                "in_out_org_code" => $param['in_out_org_code'],
                "in_out_org_id" => $param['in_out_org_id'],
                "team_id" => $param['team_id'],
                "team_name" => $param['team_name'],
                "member_id" => $param['member_id'],
                "team_member_name" => $param['team_member_name'],
                "sub_store_id" => $param['sub_store_id']
            );
            unset($dataSet);
            if( count($arr) >= 500 || $row == $highestRow ){
                $this->saveData($db, $arr, $filename);
            }
        }

       // http://www.cnblogs.com/summerzi/archive/2015/04/05/4393790.html
        //建一个队列补全这个里面的数据晚上12点后执行
		//上传之后删除掉源excel，以免数据冗余
		@unlink($filename);
		$this->success("数据上传成功！");
	}
    private function getRule($customer_code){
       // $customer_code= "43060100374000";
        $month = date("Y-m", strtotime("-1 month"));
        $param['month'] = $month;
        $param['customer_code'] = $customer_code;
        $result = M("Send_count_customer")->field("num")->where($param)->find();
        if(count($result) <= 0){
            return 0;
        }
        if($result['num'] >= self::MONTHLY_ORDER){
            return 1;
        }else{
            return 0;
        }

    }
    private function saveData($db, $param, $filename)
    {
        //当发生异常的时候数据回滚，看以下实例
        if(!empty($param)){
            try{
                $result = $db->addAll($param);
                $db->commit();
            }catch (Exception  $e){
                $db->rollback();
                @unlink($filename);
                $this->error("发生未知错误,数据回滚,详情：".$e ,'',5);
            }
        }
    }
    private function getBalancing($province,$weight,$type){
        $weight = $weight / 1000;
        $param['name'] = $province;
        $pro = M("Province")->field("id")->where($param)->find();
        if(count($pro) <= 0){
            $this->error("没有找到省份！");
        }
        $weight_rule = M("Province_attr")->field("first_weight,second_weight,three_weight,first_weight_s,three_weight_s")->where("province_id = ".$pro["id"])->find();
        $charge_rule = M("Charge")->field("first_charge,second_charge,three_charge,first_charge_s,three_charge_s")->where("province_id = ".$pro["id"])->find();
        if(count($weight_rule) <= 0 || count($charge_rule) <= 0){
            $this->error("请补全相应的邮费规则！");
        }
        $balancing = 0;
        if( $type == 0){
            if($weight_rule['first_weight'] >= $weight ){
                $balancing = $charge_rule['first_charge'];
            }else if($weight_rule['second_weight'] >= $weight){
                $balancing = $charge_rule['second_charge'];
            }else{
                $more_weight = $weight - $weight_rule['first_weight'];
                $balancing = ceil($more_weight / $weight_rule['three_weight']) * $charge_rule['three_charge'] + $charge_rule['first_charge'];
            }
        }else if( $type == 1){
            if($weight_rule['first_weight_s'] >= $weight ){
                $balancing = $charge_rule['first_charge_s'];
            }else{
                $more_weight = $weight - $weight_rule['first_weight_s'];
                $balancing = ceil($more_weight / $weight_rule['three_weight_s']) * $charge_rule['three_charge_s'] + $charge_rule['first_charge_s'];
            }

        }
       return $balancing;
    }
    private function getSubStore($sub_name ,$group_id ){
        $subDb = M("Sub_store");

        /*新加的分仓，操作员都为区域管理员*/
        $group = M("Auth_group")->field("id,title,org_id")->where("id =".$group_id)->find();
        if($group['org_id'] > 0){
            $org   = M("In_out_org")->field("id,in_out_org,org_code")->where("id=".$group['org_id'])->find();
            if(count($org) <= 0){
                $this->error("没有找到对应的机构，请检查配置信息！");
            }
        }else{
            $this->error("请先配置好区域和机构对应关系！");
        }

        $dataSet['team_id'] =$group['id'];
        $dataSet['team_name'] =$group['title'];

        $dataSet['in_out_org_name'] =$org['in_out_org'];
        $dataSet['in_out_org_code'] =$org['org_code'];
        $dataSet['in_out_org_id'] =$org['id'];

        $param['group_id'] = $group_id;
        $param['sub_store_name'] = $sub_name;
        $param['status'] = 0;
        $result = $subDb->where($param)->find();
        $sub_id = 0;

        if(count($result) > 0){
            $sub_id = $result['id'];

        }else{
            $arr = array(
                "group_id" =>$group_id,
                "sub_store_name" => $sub_name
            );
            $sub_id = $subDb->add($arr);
        }
        $member = M("member")->field("uid,nickname")->where("sub_store_id=".$sub_id)->find();
        if(count($member) <= 0){
            $member = M("member")->field("uid,nickname")->where("area=".$group_id)->find();
        }
        $dataSet['sub_store_id'] = $sub_id;
        $dataSet['member_id'] =$member['uid'];
        $dataSet['team_member_name'] =$member['nickname'];
        return $dataSet;

    }

    private function isAdmin(){
        return intval(session('user_auth')['uid']) === C('USER_ADMINISTRATOR');
    }
    public function getSendList($month,$type,$customer_name){
        $param['month'] = $month;
        if(!$this->isAdmin()){
            $result = M("Member")->field("area")->where("uid = " .session('user_auth')['uid'])->find();
            $param['team_id'] = $result['area'];
        }
        if($customer_name){
            $tableName = "send_count_date_customer";
            $param['customer_name'] = array('like',"%".$customer_name."%");
        }else{
            if($type == 1 && $this->isAdmin()){
                $tableName = "send_count_date";
            }else if($type == 1 && !$this->isAdmin()){
                $tableName = "send_count_date_group";
            }
            if($type == 2 ){
                $tableName = "send_count_customer";
            }
        }
        $result = M($tableName)->where($param)->select();
        $num_count = 0;
        $post_money_count = 0;
        $balancing_count = 0;
        $gap_money_count = 0;
        foreach($result as $key=>$val){
            $num_count += $val['num'];
            $post_money_count += $val['post_money'];
            $balancing_count += $val['balancing'];
            $gap_money_count += $val['gap_money'];
        }
        $result[count($result)] = array(
        "month" => "2016-10",
        "num" => $num_count,
        "post_money" => $post_money_count,
        "balancing" => $balancing_count,
        "gap_money" => $gap_money_count
        );
        if(type == 1){
            $result[count($result)-1]['in_out_date'] = "合计";
        }else{
            $result[count($result)-1]['customer_name'] = "合计";
        }

        //dump($result);exit;
        return $result;
    }
	public function exportExcel()
	{
        $month = $_GET['month'];
        $type = $_GET['type'];
        $customer_name = $_GET['customer_name'];
        $result = $this->getSendList($month,$type,$customer_name);
        $total = count($result);
		if ($total > 1000000) {
			exit('最多导出1000000条订单');
		}

		ini_set('memory_limit', '128M');
		require_once 'Application/Admin/Lib/Org/Util/PHPExcel.php';
		$excel     = new \PHPExcel();
		$xlsWriter = new \PHPExcel_Writer_Excel5($excel);
        if($customer_name){
            $cells = array(
                'A' => array('title' => '收寄日期', 'width' => '30', 'value_key' => 'in_out_date','format' => 'date'),
                'B' => array('title' => '收寄件数', 'width' => '15', 'value_key' => 'num'),
                'C' => array('title' => '系统结算', 'width' => '20', 'value_key' => 'post_money'),
                'D' => array('title' => '实际结算', 'width' => '20', 'value_key' => 'balancing'),
                'E' => array('title' => '结算差额', 'width' => '20', 'value_key' => 'gap_money')
            );
        }else{
           if($type == 1){
               $cells = array(
                   'A' => array('title' => '收寄日期', 'width' => '30', 'value_key' => 'in_out_date','format' => 'date'),
                   'B' => array('title' => '收寄件数', 'width' => '15', 'value_key' => 'num'),
                   'C' => array('title' => '系统结算', 'width' => '20', 'value_key' => 'post_money'),
                   'D' => array('title' => '实际结算', 'width' => '20', 'value_key' => 'balancing'),
                   'E' => array('title' => '结算差额', 'width' => '20', 'value_key' => 'gap_money')
               );
           }else{
               $cells = array(
                   'A' => array('title' => '客户名', 'width' => '25', 'value_key' => 'customer_name', 'format' => 'string'),
                   'B' => array('title' => '收寄件数', 'width' => '15', 'value_key' => 'num'),
                   'C' => array('title' => '系统结算', 'width' => '20', 'value_key' => 'post_money'),
                   'D' => array('title' => '实际结算', 'width' => '20', 'value_key' => 'balancing'),
                   'E' => array('title' => '结算差额', 'width' => '20', 'value_key' => 'gap_money')
               );
           }
        }
		$row = 1;

		foreach ($cells as $key => $value) {
			$excel->getActiveSheet()->setCellValue($key . $row, $value['title']);
			if (isset($value['width'])) {
				$excel->getActiveSheet()->getColumnDimension($key)->setWidth($value['width']);
			} else {
				$excel->getActiveSheet()->getColumnDimension($key)->setAutoSize(true);
			}
			$excel->getActiveSheet()->getStyle($key . $row)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
			$excel->getActiveSheet()->getStyle($key . $row)->getFill()->getStartColor()->setARGB('FF808080');
		}


		foreach ($result as $key=>$items) {
            ++$row;
            foreach ($cells as $key => $value) {
                if (is_string($value['value_key'])) {
                    if ($value['format'] == 'string') {
                        $excel->getActiveSheet()->setCellValueExplicit($key . $row, $items[$value['value_key']], \PHPExcel_Cell_DataType::TYPE_STRING);
                    } elseif ($value['format'] == 'date') {
                        $datetime = !empty($items[$value['value_key']]) ? str_replace("00:00:00", "", $items[$value['value_key']])  : '';
                        $excel->getActiveSheet()->setCellValue($key . $row, $datetime);
                    } else {
                        $tmpValue = $items[$value['value_key']];
                        $excel->getActiveSheet()->setCellValue($key . $row, $tmpValue);
                    }
                }
			}
		}
		$outputFileName = $month . $customer_name.'收寄统计报表.xls';
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition:inline;filename="' . $outputFileName . '"');
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$xlsWriter->save("php://output");
		exit;
	}
	public function getDetailList($start_time,$end_time,$customer_name){
        if(!empty($customer_name)){
            $map['customer_name'] = array('like',"%".$customer_name."%");
        }
		$map['in_out_date'][] = array('egt',$start_time);
		$map['in_out_date'][] = array('elt',$end_time);
		$result = M("Send_detail")->field("id,express_number,in_out_date,customer_name,sub_store,send_province,send_city,weight,post_money,balancing,(balancing-post_money) as gap_money")
                                  ->where($map)
                                  ->select();
        return $result;
	}

	public function originalExport()
	{
		$start_time = $_GET['start_time'];
		$end_time = $_GET['end_time'];
		$customer_name = $_GET['customer_name'];
		$result = $this->getDetailList($start_time , $end_time,$customer_name);
		$total = count($result);
		if ($total > 1000000) {
			exit('最多导出1000000条订单');
		}

		ini_set('memory_limit', '128M');
		require_once 'Application/Admin/Lib/Org/Util/PHPExcel.php';
		$excel     = new \PHPExcel();
		$xlsWriter = new \PHPExcel_Writer_Excel5($excel);

			$cells = array(
				'A' => array('title' => '序号', 'width' => '30', 'value_key' => 'id'),
				'B' => array('title' => '收寄日期', 'width' => '15', 'value_key' => 'in_out_date' ,'format' => 'date'),
				'C' => array('title' => '大宗客户', 'width' => '20', 'value_key' => 'customer_name','format' => 'string'),
				'D' => array('title' => '分仓', 'width' => '20', 'value_key' => 'sub_store','format' => 'string'),
				'E' => array('title' => '邮件号码', 'width' => '20', 'value_key' => 'express_number','format' => 'string'),
				'F' => array('title' => '寄达省', 'width' => '20', 'value_key' => 'send_province','format' => 'string'),
				'G' => array('title' => '寄达市', 'width' => '20', 'value_key' => 'send_city','format' => 'string'),
				'H' => array('title' => '重量(克)', 'width' => '20', 'value_key' => 'weight'),
				'I' => array('title' => '总邮资(元)', 'width' => '20', 'value_key' => 'post_money'),
				'J' => array('title' => '结算资费', 'width' => '20', 'value_key' => 'balancing'),
				'K' => array('title' => '资费差额', 'width' => '20', 'value_key' => 'gap_money'),
				'L' => array('title' => '修改资费', 'width' => '20', 'value_key' => ''),
				'M' => array('title' => '修改后差额', 'width' => '20', 'value_key' => '')
			);
		$row = 1;
		foreach ($cells as $key => $value) {
			$excel->getActiveSheet()->setCellValue($key . $row, $value['title']);
			if (isset($value['width'])) {
				$excel->getActiveSheet()->getColumnDimension($key)->setWidth($value['width']);
			} else {
				$excel->getActiveSheet()->getColumnDimension($key)->setAutoSize(true);
			}
			$excel->getActiveSheet()->getStyle($key . $row)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID);
			$excel->getActiveSheet()->getStyle($key . $row)->getFill()->getStartColor()->setARGB('FF808080');
		}

		foreach ($result as $key=>$items) {
			++$row;
			foreach ($cells as $key => $value) {
				if (is_string($value['value_key'])) {
					if ($value['format'] == 'string') {
						$excel->getActiveSheet()->setCellValueExplicit($key . $row, $items[$value['value_key']], \PHPExcel_Cell_DataType::TYPE_STRING);
					} elseif ($value['format'] == 'date') {
						$datetime = !empty($items[$value['value_key']]) ? str_replace("00:00:00", "", $items[$value['value_key']])  : '';
						$excel->getActiveSheet()->setCellValue($key . $row, $datetime);
					} else {
						$tmpValue = $items[$value['value_key']];
						$excel->getActiveSheet()->setCellValue($key . $row, $tmpValue);
					}
				}
			}
		}
		$outputFileName = $start_time .'—'. $end_time .'系统资费与实际资费明细.xls';
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header('Content-Disposition:inline;filename="' . $outputFileName . '"');
		header("Content-Transfer-Encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Pragma: no-cache");
		$xlsWriter->save("php://output");
		exit;
	}

}