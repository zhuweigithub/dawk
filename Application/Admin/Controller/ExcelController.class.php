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
		\Think\Log::record(time() . '===importExcel->send_detail');
		$tableName = "send_detail";
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
				$this->importExcel($tableName, $path );
			}
		} else {
			$this->error("请选择上传的文件");
		}
    }
/*    public $count = 0;
    public $runRow = 0;
    public function ajaxPlan(){
            $arr = array(
                "count" => $this->count,
                "run_row" => $this->runRow
            );
        echo json_encode($arr);
    }*/

	public function importExcel($tableName, $filename )
	{
		\Think\Log::record(time() . '===importExcel->zwzwZw');
		error_reporting(E_ALL);
		\Think\Log::record(time() . '===importExcel->1111');
        ini_set("memory_limit","200M");
		\Think\Log::record(time() . '===importExcel->2222');
        set_time_limit(2000);
		\Think\Log::record(time() . '===importExcel->333333');
        date_default_timezone_set('Asia/ShangHai');
		require_once 'Application/Admin/Lib/Org/Util/PHPExcel/IOFactory.php';
		$reader        = \PHPExcel_IOFactory::createReaderForFile($filename); //设置以Excel5格式(Excel97-2003工作簿)
		\Think\Log::record(time() . '===importExcel->4444');
		$PHPExcel      = $reader->load($filename); // 载入excel文件
		\Think\Log::record(time() . '===importExcel->55555');
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


        $report = [];
        $if_run = true;
        for ($row = 1; $row <= $highestRow; $row++) {
            for ($column = 'A'; $column <= $highestColMum; $column++) { //列数是以A列开始
                if( $column == 'C' || $column == 'D' || $column == 'F' || $column == 'G' || $column == 'I' || $column == 'J'
                    || $column == 'K'  || $column == 'M' || $column == 'P' || $column == 'R' || $column == 'U'){
                    continue;
                }
                $dataSet[] = $sheet->getCell($column . $row)->getValue();

            }
            if((int)$dataSet[0] <= 0){
                $report[] = $dataSet;
                unset($dataSet);
            }else{
                if($if_run == true){
                    $if_run = false;
                    $param = $this->insertReport($report);
                }
                $arr[] = array(
                    "in_out_date" => $dataSet[1],
                    "customer_code" => $dataSet[2],
                    "customer_name" => $dataSet[3],
                    "sub_store" => $dataSet[4],
                    "express_number" => $dataSet[5],
                    "send_province" => $dataSet[6],
                    "send_city" => $dataSet[7] ? $dataSet[7] : "",
                    "weight" => $dataSet[8],
                    "post_money" => $dataSet[9],
                    "area_id"    => $param['org_id'],
                    "oper_name"    => session('user_auth')['uid']
                );

                unset($dataSet);
                if( count($arr) >= 500 || $row == $highestRow ){
                    $this->saveData($db, $arr, $filename);
                    unset($arr);
                }
            }
        }

       // http://www.cnblogs.com/summerzi/archive/2015/04/05/4393790.html
        //建一个队列补全这个里面的数据晚上12点后执行
		//上传之后删除掉源excel，以免数据冗余
		@unlink($filename);
		$this->success("数据上传成功！");
	}
    private function insertReport($report){
        $reportDb = M("Report");
       // dump($report);
        $params['report_title'] = $report[0][0];
        $params['report_time']  = $report[1][0];
        $params['report_org']   = $report[2][0];
        $regex="'\d{4}-\d{1,2}-\d{1,2}'is";
        preg_match_all($regex,$params['report_time'],$matches);
        //dump($matches);
        $params['start_time']   = $matches[0][0];
        $params['end_time']     = $matches[0][1];

        $arr = explode("揽收机构：", $params['report_org']);
        $params['org_name']     = $arr[1];
        $param['in_out_org'] = $params['org_name'];
        $org_id = M("In_out_org")->field("id")->where($param)->find();
        $params['org_id']     = $org_id['id'];
        $params['user_id'] = session('user_auth')['uid'];
        $params['create_time'] = date("Y-m-d H:i:s",time());

        $data['end_time'] = array("egt" , $params['start_time']);
        $data['org_id'] =  $params['org_id'] ;
        $result = $reportDb->where($data)->find();
        if( count($result) <= 0){
            $reportDb->add($params);
        }else{
            $this->error("数据不能重复导入，请检查！");exit;
        }
        return $params;

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
    private function isAdmin(){
        return intval(session('user_auth')['uid']) === C('USER_ADMINISTRATOR');
    }
    public function getSendList($month,$type,$customer_name){
        $param['month'] = $month;

        if( $type == 2 ){
            $tableName = "send_count_customer";
            $param['customer_name'] = array('like',"%".$customer_name."%");
        }else{
            $tableName = "Send_count_date_customer";
            if(!$this->isAdmin()){
                $result = M("Member")->field("area")->where("uid = " .session('user_auth')['uid'])->find();
                $param['area_id'] = $result['area'];
            }
            if($customer_name){
                $param['sub_store'] = array('like',"%".$customer_name."%");
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
        "month" => $month,
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
        if(!$this->isAdmin()){
            $result = M("Member")->field("area")->where("uid = " .session('user_auth')['uid'])->find();
            $map['area_id'] = $result['area'];
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