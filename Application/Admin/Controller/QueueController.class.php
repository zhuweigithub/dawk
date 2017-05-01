<?php
namespace Admin\Controller;

use Think\Exception;
use Think\Controller;

class QueueController extends Controller
{
    public function __construct(){
		\Think\Log::record(time() . '===Queue->runQueue--------start run');
        $this->runQueue();
    }
	public function runQueue()
	{
        set_time_limit(0);
        $param['config_name'] = "MONTHLY_COUNT_DATA";
        $rule = M("Config_system")->where($param)->find();
        $today = (int)date("d",time());

        //todo 当前时间为配置时间则运行处理队列
        if($today == $rule['config_value']){
		for($i = 1;$i < 5 ;$i++ ){
		$month = date("Y-m",strtotime("-" . $i . "months",strtotime(date("Y-m", time()))));
		$list  = M("Send_month")->where("month= '" . $month . "'")->find();

			if (count($list) <= 0) {
				$arr = array(
					"month"       => $month,
					"create_time" => date("Y-m-d H:i:s", time())
				);
				M("Send_month")->add($arr);
				$this->countStoreSendNumByMonth($month); //统计每个分仓每月发件数
				$this->traverseDetail($month);            //根据上面的发件数计算实际邮费
				$this->CustomerSendCountByDateQueue($month); //按分仓客户对应表生成每月客户每天发单数
				$this->sendCountByDateQueue($month); //生成每月每个客户发单数总计
			} else {
				\Think\Log::record(time() . '===Queue->runQueue'.$month.'队列已经跑过了');
			}
		}
       }
	}

	/*  1.统计每个客户上月的订单数  方法
	 *  遍历send_detail 表计算所有的差价保存进去
	 * 2. 生成所有客户的统计数据   方法
	 *   3. 生成每个月每天客户的发单统计
	 *
	 */

	public function countStoreSendNumByMonth($month)
	{
        set_time_limit(0);
		$dbCount = M("Count_sub_store");
		$db      = M();
		$sql     = "select sub_store,in_out_date, count(*) as num
                from t_send_detail where in_out_date like '" . $month . "%' GROUP BY sub_store ";
		$result  = $db->query($sql);
		foreach ($result as $key => $val) {
			$param['month']     = $month;
			$param['sub_store'] = $val['sub_store'];
			$result             = $dbCount->where($param)->find();
			$arr                = array(
				"month"       => $month,
				"sub_store"   => $val['sub_store'],
				"num"         => $val['num'],
				"create_time" => date('Y-m-d H:i:s', time())
			);
			if (count($result) <= 0) {
				$dbCount->add($arr);
			} else {
				$arr['id'] = $result['id'];
				$dbCount->save($arr);
			}
		}

	}

    /**  计算send_detail 表的价格
     * @param $month
     */
    public function traverseDetail($month)
	{
        set_time_limit(0);
		$db       = M();
		$detailDb = M("Send_detail");
		$sql      = "select id , sub_store,send_province,send_city,weight,post_money
                from t_send_detail where in_out_date like '" . $month . "%'";
		$result   = $db->query($sql);

		foreach ($result as $key => $val) {
			$type      = $this->getRule($month, $val['sub_store']);
			$balancing = $this->getBalancing($val['send_province'], $val['weight'], $type ,$val['sub_store']);
			$arr       = array(
				"id"        => $val['id'],
				"balancing" => $balancing
			);
			$detailDb->save($arr);
		}

	}

	private function getRule($month, $sub_store)
	{
        set_time_limit(0);
        $staple_rule = M("Staple_rule")->where("store_name='".$sub_store."'")->find();
        if(count($staple_rule) > 0 ){
            return 2;
        }
		$param['month']     = $month;
		$param['sub_store'] = $sub_store;
		$result             = M("Count_sub_store")->field("num")->where($param)->find();
		if (count($result) <= 0) {
			\Think\Log::record(time() . '===Queue->getRule ,没有找到规则，理论上肯定有的，');
		}
        $param['config_name'] = "MONTHLY_ORDER";
        $rule = M("Config_system")->where($param)->find();
        \Think\Log::record(time() . '===Queue->getRule '. json_encode($rule));
		if ($result['num'] >= $rule['config_value']) {
			return 1;
		} else {
			return 0;
		}

	}

	private function getBalancing($province, $weight, $type ,$sub_store)
	{
        set_time_limit(0);
		$weight        = $weight / 1000;
		$param['name'] = $province;
		$pro           = M("Province")->field("id,zone_id")->where($param)->find();
		if (count($pro) <= 0) {
			\Think\Log::record(time() . '===Queue->getBalancing ,没有找到省份，信息需补全，');
			return 0;
		}
		$weight_rule = M("Province_attr")->field("first_weight,second_weight,three_weight,first_weight_s,three_weight_s")->where("province_id = " . $pro["id"])->find();
		$charge_rule = M("Charge")->field("first_charge,second_charge,three_charge,first_charge_s,three_charge_s")->where("province_id = " . $pro["id"])->find();
		if (count($weight_rule) <= 0 || count($charge_rule) <= 0) {
			\Think\Log::record(time() . '===Queue->getBalancing ，请补全相应的邮费规则，');
			return 0;
		}
		$balancing = 0;
		if ($type == 0) {
			if ($weight_rule['first_weight'] >= $weight) {
				$balancing = $charge_rule['first_charge'];
			} else if ($weight_rule['second_weight'] >= $weight) {
				$balancing = $charge_rule['second_charge'];
			} else {
				$more_weight = $weight - $weight_rule['first_weight'];
				$balancing   = ceil($more_weight / $weight_rule['three_weight']) * $charge_rule['three_charge'] + $charge_rule['first_charge'];
			}
		} else if ($type == 1) {
			if ($weight_rule['first_weight_s'] >= $weight) {
				$balancing = $charge_rule['first_charge_s'];
			} else {
				$more_weight = $weight - $weight_rule['first_weight_s'];
				$balancing   = ceil($more_weight / $weight_rule['three_weight_s']) * $charge_rule['three_charge_s'] + $charge_rule['first_charge_s'];
			}

		}else if ($type == 2) {

            $param['store_name'] = $sub_store;
            $staple_rule = M("Staple_rule")->field("id,store_id")->where($param)->find();

            $zone_params['store_id'] = $staple_rule['store_id'];
            $zone_rule = M("zone")->find('id,province_ids')->where($zone_params)->select();
            $zone_rule_id = 0;
            for($i = 0; $i<count($zone_rule) ; $i++){
                $provinceIds = explode(',', $zone_rule[$i]['province_ids']);
                for ($j = 0; $j < count($provinceIds) - 1; $j++) {
                    if ($pro['id'] == $provinceIds[$j]) {
                        $zone_rule_id = $zone_rule[$i]['id'];
                    }
                }
            }
            $params['staple_id'] = $staple_rule['id'];
            $params['zone_id'] = $zone_rule_id;
            $staple_rule_ext = M("Staple_rule_ext")->where($params)->find();
            if($staple_rule_ext['first_weight_a'] >= $weight){
                if($staple_rule_ext['first_weight_b'] > 0 && $staple_rule_ext['first_weight_b'] >= $weight){
                    $balancing = $staple_rule_ext['first_fee_b'];
                }else{
                    $balancing = $staple_rule_ext['first_fee_a'];
                }
            }else{
                if($staple_rule_ext['second_weight_end'] == 0){
                    $more_weight = $weight - $staple_rule_ext['first_weight_a'];
                    $balancing   = ceil($more_weight / 1) * $staple_rule_ext['second_fee_a'] + $staple_rule_ext['first_fee_a'];
                }else{
                    if($staple_rule_ext['second_weight_end'] >= $weight){
                        $more_weight = $weight - $staple_rule_ext['first_weight_a'];
                        $balancing   = ceil($more_weight / 1) * $staple_rule_ext['second_fee_a'] + $staple_rule_ext['first_fee_a'];
                    }else{
                        $more_weight = $weight - $staple_rule_ext['first_weight_a'];
                        $balancing   = ceil($more_weight / 1) * $staple_rule_ext['second_fee_b'] + $staple_rule_ext['first_fee_a'];
                    }
                }
            }


        }
		return $balancing;
	}

	public function CustomerSendCountByDateQueue($month)
	{
        set_time_limit(0);
		$detailDb             = M("Send_detail");
		$param['in_out_date'] = array("like", $month . "%");
		$result               = $detailDb->where($param)
			->field("sub_store,count(1) as num ,sum(weight) as weight,sum(post_money) as post_money ,sum(balancing) as balancing ")
			->group("sub_store")
			->select();

		$storeDb              = M("Sub_store");
		$storeList            = $storeDb->field("id,sub_store_name,customer_name")->select();
		$k                    = 0;
		foreach ($result as $key => $val) {
			foreach ($storeList as $i => $j) {
				if ($val['sub_store'] == $j['sub_store_name']) {
					$data_base[$k]['customer_name'] = $j['customer_name'] ? $j['customer_name'] : $j['sub_store_name'];
					$data_base[$k]['num']           = $val['num'];
					$data_base[$k]['weight']        = $val['weight'];
					$data_base[$k]['post_money']    = $val['post_money'];
					$data_base[$k]['balancing']     = $val['balancing'];
					$k++;
				}
			}
		}
		$data_base = $this->dg($data_base);
		if (count($data_base) > 0) {
			foreach ($data_base as $val) {
				$arr = array(
					"month"         => $month,
					"customer_name" => $val['customer_name'],
					"num"           => $val['num'],
					"post_money"    => $val['post_money'],
					"balancing"     => $val['balancing'],
					"gap_money"     => $val['balancing'] - $val['post_money'],
                    "create_time"   => date("Y-m-d H:i:s", time())
				);
				M("Send_count_customer")->add($arr);
			}

		}

	}

	private function dg($data_base)
	{
        set_time_limit(0);
		$ifg = true;
		for ($x = 0; $x < count($data_base); $x++) {
			if ($data_base[$x]['customer_name'] == $data_base[$x + 1]['customer_name']) {
				$data_base[$x]['num']        = $data_base[$x]['num'] + $data_base[$x + 1]['num'];
				$data_base[$x]['weight']     = $data_base[$x]['weight'] + $data_base[$x + 1]['weight'];
				$data_base[$x]['post_money'] = $data_base[$x]['post_money'] + $data_base[$x + 1]['post_money'];
				$data_base[$x]['balancing']  = $data_base[$x]['balancing'] + $data_base[$x + 1]['balancing'];
				$ifg                         = false;
				unset($data_base[$x + 1]);
				$data_base = array_merge($data_base);
			}
		}
		if ($ifg == true) {
			return $data_base;
		} else {
			return $this->dg($data_base);
		}
	}

	public function sendCountByDateQueue($month)
	{
        set_time_limit(0);
		$db        = M();
		$storeDb   = M("Sub_store");
		$storeList = $storeDb->field("id,sub_store_name,customer_name")->select();
		foreach ($storeList as $val) {
			$sql    = "select in_out_date,sub_store,area_id, count(*) as num,sum(post_money) as post_money,sum(balancing) as balancing
                from t_send_detail where in_out_date like '" . $month . "%' and sub_store = '" . $val['sub_store_name'] . "' GROUP BY in_out_date ";
			$result = $db->query($sql);
			if(count($result) > 0){
				$this->insertDataCustomer($month, $result);
			}
		}
	}

	private function insertDataCustomer($month, $result)
	{
        set_time_limit(0);
		foreach ($result as $val) {
			$arr = array(
				"month"       => $month,
				"in_out_date" => $val['in_out_date'],
				"sub_store"   => $val['sub_store'],
				"area_id"   => $val['area_id'],
				"num"         => $val['num'],
				"post_money"  => $val['post_money'],
				"balancing"   => $val['balancing'],
				"gap_money"   => $val['balancing'] - $val['post_money'],
				"create_time" => date('Y-m-d H:i:s', time())
			);
			M("Send_count_date_customer")->add($arr);
		}
	/*	dump($arr);
		if (!empty($arr)) {
			$dbCount = M("Send_count_date_customer");
			$dbCount->startTrans();
			try {
				$dbCount->addAll($arr);
				echo $dbCount->getLastSql();
				$dbCount->commit();
			} catch (Exception $e) {
				$dbCount->rollback();
				\Think\Log::record(time() . '===Queue->insertDataCustomer' . $e);
			}
		}*/
	}

}