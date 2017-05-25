<?php
namespace Admin\Controller;

use Think\Exception;
use Think\Controller;

class QueueController extends Controller
{
	public function __construct()
	{
		\Think\Log::record(time() . '===Queue->runQueue--------start run');
		$this->runQueue();
	}

	public function runQueue()
	{

		set_time_limit(0);
		$param['config_name'] = "MONTHLY_COUNT_DATA";
		$rule                 = M("Config_system")->where($param)->find();
		$today                = (int)date("d", time());
		$today                = 7;
		//todo 当前时间为配置时间则运行处理队列
		if ($today == $rule['config_value']) {
			for ($i = 1; $i < 5; $i++) {

				$month = date("Y-m", strtotime("-" . $i . "months", strtotime(date("Y-m", time()))));
				$list  = M("Send_month")->where("month= '" . $month . "'")->find();

				if (count($list) <= 0) {
					$arr = array(
						"month"       => $month,
						"create_time" => date("Y-m-d H:i:s", time())
					);
					//M("Send_month")->add($arr);
					//$this->countStoreSendNumByMonth($month); //统计每个分仓每月发件数
					$this->traverseDetail($month); //根据上面的发件数计算实际邮费
					//$this->CustomerSendCountByDateQueue($month); //按分仓客户对应表生成每月客户每天发单数
					//$this->sendCountByDateQueue($month); //生成每月每个客户发单数总计
					\Think\Log::record(time() . '===Queue->runQueue' . $month . '汇总统计成功');
					echo $month . '汇总统计成功';
					return;
				} else {
					\Think\Log::record(time() . '===Queue->runQueue' . $month . '队列已经跑过了');
					return;
				}
			}
		} else {
			return;
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
		// echo $month;
		$db       = M();
		$detailDb = M("Send_detail");
		$sqls     = "select count(1) as total from t_send_detail where in_out_date like '" . $month . "%'";
		$count    = $db->query($sqls);

		$pageNum   = 10000;
		$pageTotal = ceil($count[0]['total'] / $pageNum);

		$provinceList = M("Province")->field("id,short_name,zone_id")->select(); //所有省
		$weightRuleList = M("Province_attr")
			->field("province_id,first_weight,second_weight,three_weight,first_weight_s,three_weight_s")
			->select(); //所有规则
		$chargeRuleList = M("Charge")
			->field("province_id,first_charge,second_charge,three_charge,first_charge_s,three_charge_s")
			->select();
		//省份规则表
		$stapleRuleList = M("Staple_rule")->field("id,store_id,store_name")->select(); //规则针对单个分仓的规则
		$zoneRuleList = M("zone")->field('id,store_id,province_ids')->select(); //分区id
		for ($i = 0; $i < $pageTotal; $i++) {
			$start = $i * $pageNum;
			echo $start;
			echo "<br>----" . $i . "----<br>";
			echo $pageNum;
			$sql    = "select id , sub_store,send_province,send_city,weight,post_money
                from t_send_detail where in_out_date like '" . $month . "%' limit " . $start . " ," . $pageNum;
			$result = $db->query($sql);
			foreach ($result as $key => $val) {
				$type        = $this->getRule($month, $val['sub_store']);
				$totalParams = $this->getTotalParams($val['send_province'], $val['sub_store'], $provinceList,
					$weightRuleList, $chargeRuleList, $stapleRuleList, $zoneRuleList);
				$balancing            = $this->getBalancing($val['weight'], $type, $totalParams);
				$arr                  = array();
				$arr[$i]['id']        = $val['id'];
				$arr[$i]['balancing'] = $balancing;
				if (count($arr) >= 1000) {
					echo "<br/>-----------***************---------------<br/>";
					dump($arr);
					echo "<br/>-----------***************---------------<br/>";
					$detailDb->save($arr);
				}
			}
		}
	}

	private function getTotalParams($send_province, $sub_store, $provinceList, $weightRuleList, $chargeRuleList, $stapleRuleList, $zoneRuleList)
	{
		$arrayList = array();
		foreach ($provinceList as $key => $val) {

			if ($val['short_name'] == $send_province) {
				$arrayList['province_id'] = $val['id'];
			}
		}
		foreach ($weightRuleList as $i => $j) {
			if ($j['province_id'] == $arrayList['province_id']) {
				$arrayList['weight_rule'] = $j;
			}
		}

		foreach ($chargeRuleList as $k => $z) {
			if ($z['province_id'] == $arrayList['province_id']) {
				$arrayList['charge_rule'] = $z;
			}
		}
		foreach ($stapleRuleList as $k => $z) {
			if ($z['store_name'] == $sub_store) {
				$arrayList['staple_rule'] = $z;
			}
		}
		$i = 0;
		foreach ($zoneRuleList as $k => $z) {
			if ($z['store_id'] == $arrayList['staple_rule']['store_id']) {
				$arrayList['zone_rule'][$i] = $z;
				$i++;
			}
		}
		return $arrayList;
	}

	private function getRule($month, $sub_store)
	{
		set_time_limit(0);
		$staple_rule = M("Staple_rule")->where("store_name='" . $sub_store . "'")->find();
		if (count($staple_rule) > 0) {
			return 2;
		}
		$param['month']     = $month;
		$param['sub_store'] = $sub_store;
		$result             = M("Count_sub_store")->field("num")->where($param)->find();
		if (count($result) <= 0) {
			\Think\Log::record(time() . '===Queue->getRule ,没有找到规则，理论上肯定有的，');
		}
		$param['config_name'] = "MONTHLY_ORDER";
		$rule                 = M("Config_system")->where($param)->find();
		\Think\Log::record(time() . '===Queue->getRule ' . json_encode($rule));
		if ($result['num'] >= $rule['config_value']) {
			return 1;
		} else {
			return 0;
		}

	}

	private function getBalancing($weight, $type, $totalParams)
	{
		echo $type;
		set_time_limit(0);
		$weight    = $weight / 1000;
		$balancing = 0;
		switch ($type) {
			case 0:
				if (count($totalParams['weight_rule']) <= 0 || count($totalParams['charge_rule']) <= 0) {
					\Think\Log::record(time() . '===Queue->getBalancing ，请补全相应的邮费规则，');
					return 0;
				}
				if ($totalParams['weight_rule']['first_weight'] >= $weight) {
					$balancing = $totalParams['charge_rule']['first_charge'];
				} else if ($totalParams['weight_rule']['second_weight'] >= $weight) {
					$balancing = $totalParams['charge_rule']['second_charge'];
				} else {
					$more_weight = $weight - $totalParams['weight_rule']['first_weight'];
					$balancing   = ceil($more_weight / $totalParams['weight_rule']['three_weight']) * $totalParams['charge_rule']['three_charge'] + $totalParams['charge_rule']['first_charge'];
				}
				break;
			case 1:
				if ($totalParams['weight_rule']['first_weight_s'] >= $weight) {
					$balancing = $totalParams['charge_rule']['first_charge_s'];
				} else {
					$more_weight = $weight - $totalParams['weight_rule']['first_weight_s'];
					$balancing   = ceil($more_weight / $totalParams['weight_rule']['three_weight_s']) * $totalParams['charge_rule']['three_charge_s'] + $totalParams['charge_rule']['first_charge_s'];
				}
				break;
			case 2:
				if (count($totalParams['province_id']) <= 0) {
					\Think\Log::record(time() . '===Queue->getBalancing ,没有找到省份，信息需补全，');
					return 0;
				}

				$zone_rule_id = 0;
				for ($i = 0; $i < count($totalParams['zone_rule']); $i++) {
					if ($totalParams['zone_rule'][$i]['province_ids'] == null) {
						continue;
					}
					$provinceIds = explode(',', $totalParams['zone_rule'][$i]['province_ids']);
					for ($j = 0; $j < count($provinceIds) - 1; $j++) {
						if ($totalParams['province_id'] == $provinceIds[$j]) {
							$zone_rule_id = $totalParams['zone_rule'][$i]['id'];
						}
					}
				}
				$params['staple_id'] = $totalParams['staple_rule']['id'];
				$params['zone_id']   = $zone_rule_id;
				$staple_rule_ext     = M("Staple_rule_ext")->where($params)->find();
				echo "<br>===========================<br>";
				if (count($staple_rule_ext) <= 0) {
					$balancing = 0;
				} else {
					if ($staple_rule_ext['first_weight_a'] >= $weight) {
						if ($staple_rule_ext['first_weight_b'] > 0 && $staple_rule_ext['first_weight_b'] >= $weight) {
							$balancing = $staple_rule_ext['first_fee_b'];
						} else {
							$balancing = $staple_rule_ext['first_fee_a'];
						}
					} else {
						if ($staple_rule_ext['second_weight_end'] == 0) {
							$more_weight = $weight - $staple_rule_ext['first_weight_a'];
							$balancing   = ceil($more_weight / 1) * $staple_rule_ext['second_fee_a'] + $staple_rule_ext['first_fee_a'];
						} else {
							if ($staple_rule_ext['second_weight_end'] >= $weight) {
								$more_weight = $weight - $staple_rule_ext['first_weight_a'];
								$balancing   = ceil($more_weight / 1) * $staple_rule_ext['second_fee_a'] + $staple_rule_ext['first_fee_a'];
							} else {
								$more_weight = $weight - $staple_rule_ext['first_weight_a'];
								$balancing   = ceil($more_weight / 1) * $staple_rule_ext['second_fee_b'] + $staple_rule_ext['first_fee_a'];
							}
						}
					}
				}
				break;
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

		$storeDb   = M("Sub_store");
		$storeList = $storeDb->field("id,sub_store_name,customer_name")->select();
		$k         = 0;
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
			if (count($result) > 0) {
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
				"area_id"     => $val['area_id'],
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