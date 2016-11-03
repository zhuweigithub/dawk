<?php
namespace Admin\Controller;
use Think\Exception;

class QueueController extends AdminController
{

    public function runQueue(){
        $month = date("Y-m",time() - 7*24*3600);
        $list = M("Send_month")->where("month= '".$month."'")->find();
        if(count($list) <= 0){
            $arr = array(
                "month" => $month,
                "create_time" => date("Y-m-d H:i:s",time())
            );
            M("Send_month")->add($arr);
            $this->CustomerSendCountByDateQueue($month); //按分仓客户对应表生成每月客户每天发单数
            $this->sendCountByCustomerQueue($month); //生成每月每个客户发单数总计
        }else{
            \Think\Log::record(time().'===Queue->runQueue队列已经跑过了');
        }

    }

    /*  1.统计每个客户上月的订单数  方法
     *  遍历send_detail 表计算所有的差价保存进去
     * 2. 生成所有客户的统计数据   方法
     *   3. 生成每个月每天客户的发单统计
     *
     */

    public function countStoreSendNumByMonth($month){
        $dbCount = M("Count_sub_store");
        $db = M();
        $sql = "select sub_store,in_out_date, count(*) as num
                from t_send_detail where in_out_date like '".$month."%' GROUP BY sub_store ";
        $result = $db->query($sql);
        foreach($result as $key=>$val){
            $param['month'] = $month;
            $param['sub_store'] = $val['sub_store'];
            $result = $dbCount ->where($param)->find();
            $arr = array(
                "month"  => $month,
                "sub_store"  => $val['sub_store'],
                "num"  => $val['num'],
                "create_time"  => date('Y-m-d H:i:s',time())
            );
            if(count($result) <= 0){
                $dbCount->add($arr);
            }else{
                $arr['id'] = $result['id'];
                $dbCount->save($arr);
            }
        }

    }
    public function traverseDetail($month){
        $db = M();
        $detailDb = M("Send_detail");
        $sql = "select id , sub_store,send_province,send_city,weight,post_money
                from t_send_detail where in_out_date like '".$month."%' GROUP BY sub_store ";
        $result = $db->query($sql);

        foreach($result as $key=>$val){
            $type = $this->getRule($month ,$val['sub_store']);
            $balancing = $this->getBalancing($val['send_province'],$val['weight'],$type);
            $arr = array(
                "id" => $val['id'],
                "balancing" => $balancing
            );
            $detailDb->save($arr);
        }

    }
    private function getRule($month,$sub_store){
        $param['month'] = $month;
        $param['sub_store'] = $sub_store;
        $result = M("Count_sub_store")->field("num")->where($param)->find();
        if(count($result) <= 0){
            \Think\Log::record(time().'===Queue->getRule ,没有找到规则，理论上肯定有的，');
        }
        if($result['num'] >= C("MONTHLY_ORDER")){
            return 1;
        }else{
            return 0;
        }

    }

    private function getBalancing($province,$weight,$type){
        $weight = $weight / 1000;
        $param['name'] = $province;
        $pro = M("Province")->field("id")->where($param)->find();
        if(count($pro) <= 0){
            \Think\Log::record(time().'===Queue->getBalancing ,没有找到省份，信息需补全，');
            return 0;
        }
        $weight_rule = M("Province_attr")->field("first_weight,second_weight,three_weight,first_weight_s,three_weight_s")->where("province_id = ".$pro["id"])->find();
        $charge_rule = M("Charge")->field("first_charge,second_charge,three_charge,first_charge_s,three_charge_s")->where("province_id = ".$pro["id"])->find();
        if(count($weight_rule) <= 0 || count($charge_rule) <= 0){
            \Think\Log::record(time().'===Queue->getBalancing ，请补全相应的邮费规则，');
            return 0;
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

    public function CustomerSendCountByDateQueue($month){
        // in_out_org_id,




    }

	public function sendCountByDateQueue($month){
       // in_out_org_id,

        $db = M();
        $sql = "select in_out_date, count(*) as num,sum(post_money) as post_money,sum(balancing) as balancing
                from t_send_detail where in_out_date like '".$month."%' GROUP BY in_out_date ";
        $result = $db->query($sql);
        foreach($result as $key=>$val){
            $arr[] = array(
                "month"  => $month,
                "in_out_date"  => $val['in_out_date'],
                "num"  => $val['num'],
                "post_money"  => $val['post_money'],
                "balancing"  => $val['balancing'],
                "gap_money"  => $val['balancing'] - $val['post_money'],
                "create_time"  => date('Y-m-d H:i:s',time())
            );
        }
        if(!empty($arr)){
            $dbCount = M("Send_count_date");
            $dbCount->startTrans();
            try{
                $dbCount->addAll($arr);
                $dbCount->commit();
            }catch (Exception $e){
                $dbCount->rollback();
                \Think\Log::record(time().'===Queue->sendDetailMonthCountQueue'.$e);
            }
        }

	}

}