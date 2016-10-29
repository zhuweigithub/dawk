<?php
namespace Admin\Controller;
class ExcelController extends AdminController
{
    public function test(){
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
				$this->importExcel($tableName, $path);

			}
		} else {
			$this->error("请选择上传的文件");
		}
	}

	public function importExcel($tableName, $filename)
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

        for ($row = 1; $row <= $highestRow; $row++) {
            for ($column = 'A'; $column <= $highestColMum; $column++) { //列数是以A列开始
                if( $column == 'C' || $column == 'D' || $column == 'F' || $column == 'G' || $column == 'I' || $column == 'J'
                    || $column == 'K'  || $column == 'M' || $column == 'P' || $column == 'R' || $column == 'U'){
                    continue;
                }
                $dataSet[] = $sheet->getCell($column . $row)->getValue();
                if((int)$dataSet[0] <= 0){
                    unset($dataSet);
                    continue;
                }
            }
        }
        for( $i = 0; $i < 100 ; $i++){
            $arr[] = array(
                "in_out_date"    => $dataSet[1],
                "customer_code"  => $dataSet[2],
                "customer_name"  => $dataSet[3],
                "sub_store"      => $dataSet[4],
                "express_number" => $dataSet[5],
                "send_province"  => $dataSet[6],
                "send_city"      => $dataSet[7],
                "weight"         => $dataSet[8],
                "post_money"     => $dataSet[9]
            );
        }
            /*  批次处理插入数据
             * $arr=array(
    array("name"=>"张三",age=>"20"),

    array("name"=>"李四",age=>"19")

);
$user=M("User");
$user->addAll($arr);
            */
            $this->saveData($tableName, $dataSet, $filename);
            unset($dataSet);
        }
        //当发生异常的时候数据回滚，看以下实例
       // http://www.cnblogs.com/summerzi/archive/2015/04/05/4393790.html
        //建一个队列补全这个里面的数据晚上12点后执行
		//上传之后删除掉源excel，以免数据冗余
		@unlink($filename);
		$this->success("数据上传成功！");
	}

	private function saveData($tableName, $param, $filename)
	{
		$db = M($tableName);
		switch ($tableName) {
			case 'in_out_org':
				$arr = array(
					"in_out_org" => $param[0],
					"area_name"  => $param[1],
					"org_code"   => $param[2]
				);
				break;
			case 'send_detail':
				$arr = array(
					"in_out_date"    => $param[1],
					"customer_code"  => $param[2],
					"customer_name"  => $param[3],
					"sub_store"      => $param[4],
					"express_number" => $param[5],
					"send_province"  => $param[6],
					"send_city"      => $param[7],
					"weight"         => $param[8],
					"post_money"     => $param[9]
				);
				break;

		}
		if ($arr) {
			$db->add($arr);
		} else {
			@unlink($filename);
			$this->error("发生未知错误！");
		}
	}

	public function export($orders, $total)
	{
		if ($total > 1000000) {
			exit('最多导出1000000条订单');
		}
		ini_set('memory_limit', '128M');
		require_once 'Application/Admin/Lib/Org/Util/PHPExcel.php';
		$excel     = new \PHPExcel();
		$xlsWriter = new \PHPExcel_Writer_Excel5($excel);

		$cells = array(
			'A' => array('title' => '商户订单号', 'width' => '25', 'value_key' => 'order_no', 'format' => 'string'),
			'B' => array('title' => '客户ID号', 'width' => '15', 'value_key' => 'home_phone'),
			'C' => array('title' => '订单状态', 'width' => '12', 'value_key' => 'status', 'action' => 'get_order_status', 'action_param' => true),
			'D' => array('title' => '下单时间', 'width' => '20', 'value_key' => 'add_time', 'format' => 'date'),
			'E' => array('title' => '支付时间', 'width' => '20', 'value_key' => 'pay_time', 'format' => 'date'),
			'F' => array('title' => '总金额', 'width' => '12', 'value_key' => 'total_price', 'type' => 'recount'),
			'G' => array('title' => '付款金额', 'width' => '12', 'value_key' => 'pay_price', 'type' => 'recount'),
			'H' => array('title' => '支付方式', 'width' => '12', 'value_key' => 'pay_type', 'action' => 'get_pay_type', 'action_param' => true),
			'I' => array('title' => '支付流水号', 'width' => '12', 'value_key' => 'pay_no'),
			'J' => array('title' => '收货人', 'width' => '12', 'value_key' => 'consignee'),
			'K' => array('title' => '收货人手机号', 'width' => '15', 'value_key' => 'mobile'),
			'L' => array('title' => '收货地址', 'width' => '50', 'value_key' => array('province_str', 'city_str', 'zone_str', 'address_detail')),
			'M' => array('title' => '物流单号', 'width' => '15', 'value_key' => 'logistics_no', 'format' => 'string'),
			'N' => array('title' => '物流公司', 'width' => '12', 'value_key' => 'logistics_name_cn'),
			'O' => array('title' => '商品编号', 'width' => '12', 'value_key' => 'product_no'),
			'P' => array('title' => '商品名称', 'width' => '25', 'value_key' => 'name', 'type' => 'goods'),
			'Q' => array('title' => '商品原价', 'width' => '12', 'value_key' => 'price', 'type' => 'goods'),
			'R' => array('title' => '商品促销价', 'width' => '12', 'value_key' => 'pay_price', 'type' => 'goods'),
			'S' => array('title' => '商品数量', 'width' => '12', 'value_key' => 'num', 'type' => 'goods'),
		);

		if (ACTION_NAME == 'refund') {
			$cell_2 = array(
				'T' => array('title' => '退款金额', 'width' => '12', 'value_key' => 'refund_money'),
				'U' => array('title' => '退款申请时间', 'width' => '20', 'value_key' => 'refund_add_time', 'format' => 'date'),
				'V' => array('title' => '退款处理状态', 'width' => '12', 'value_key' => 'refund_flow', 'action' => 'get_order_refund_status'),
			);
			$cells  = array_merge($cells, $cell_2);
		} elseif (ACTION_NAME == 'repair') {
			$cell_2 = array(
				'T' => array('title' => '退款金额', 'width' => '12', 'value_key' => 'refund_money'),
				'U' => array('title' => '退款申请时间', 'width' => '20', 'value_key' => 'refund_add_time', 'format' => 'date'),
				'V' => array('title' => '退款处理状态', 'width' => '12', 'value_key' => 'refund_flow', 'action' => 'get_order_refund_status'),
				'W' => array('title' => '退款原因', 'width' => '20', 'value_key' => 'refund_cause', 'action' => 'get_refund_cause'),
			);
			$cells  = array_merge($cells, $cell_2);
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


		foreach ($orders as $order) {
			$redo = false;
			foreach ($order['goods_list'] as $goods_list) {
				++$row;
				foreach ($cells as $key => $value) {
					if (is_string($value['value_key'])) {
						if ($value['format'] == 'string') {
							$excel->getActiveSheet()->setCellValueExplicit($key . $row, $order[$value['value_key']], \PHPExcel_Cell_DataType::TYPE_STRING);
						} elseif ($value['format'] == 'date') {
							$datetime = !empty($order[$value['value_key']]) ? date('Y-m-d H:i:s', $order[$value['value_key']]) : '';
							$excel->getActiveSheet()->setCellValue($key . $row, $datetime);
						} else {
							$tmpValue = $order[$value['value_key']];
							$params   = array();
							if (!empty($value['action'])) {
								$params[] = $tmpValue;
								if (isset($value['action_param'])) $params[] = $value['action_param'];
								$tmpValue = call_user_func_array($value['action'], $params);
							}
							if ($value['type'] == 'goods') {
								$tmpValue = $goods_list[$value['value_key']];
							} elseif ($value['type'] == 'recount') {
								if ($value['value_key'] == 'total_price') {
									$tmpValue = $goods_list['num'] * $goods_list['price'];
								} else {
									$tmpValue = $goods_list['num'] * $goods_list['pay_price'];
								}
							}
							$excel->getActiveSheet()->setCellValue($key . $row, $tmpValue);
						}
					} elseif (is_array($value['value_key'])) {
						$str = '';
						foreach ($value['value_key'] as $v) {
							$str .= $order[$v];
						}
						$excel->getActiveSheet()->setCellValue($key . $row, $str);
					}
				}
			}
		}
		$outputFileName = date('Y-m-d') . '.xls';
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