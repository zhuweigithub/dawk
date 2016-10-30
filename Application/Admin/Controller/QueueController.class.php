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
            $this->sendCountByCustomerQueue($month);
            $this->sendCountByDateQueue($month);
            $this->sendCountGroupByDateQueue($month);
        }else{
            \Think\Log::record(time().'===Queue->runQueue队列已经跑过了');
        }

    }
    public function sendCountGroupByDateQueue($month){
        // in_out_org_id,

        $db = M();
        $sql = "select team_id,in_out_date, count(*) as num,sum(post_money) as post_money,sum(balancing) as balancing
                from t_send_detail where in_out_date like '".$month."%' GROUP BY team_id,in_out_date ";
        $result = $db->query($sql);
        foreach($result as $key=>$val){
            $arr[] = array(
                "month"  => $month,
                "team_id"  => $val['team_id'],
                "in_out_date"  => $val['in_out_date'],
                "num"  => $val['num'],
                "post_money"  => $val['post_money'],
                "balancing"  => $val['balancing'],
                "gap_money"  => $val['balancing'] - $val['post_money'],
                "create_time"  => date('Y-m-d H:i:s',time())
            );
        }
        if(!empty($arr)){
            $dbCount = M("Send_count_date_group");
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
    public function sendCountByCustomerQueue($month){
        $db = M();
         $sql = "select team_id,count(*) as num,customer_code,customer_name,sum(post_money) as post_money,sum(balancing) as balancing
                 from t_send_detail where in_out_date like '".$month."%' GROUP BY customer_code ";
        $result = $db->query($sql);
        foreach($result as $key=>$val){

            $arr[] = array(
                "month"  => $month,
                "team_id"  => $val['team_id'],
                "customer_code"  => $val['customer_code'],
                "customer_name"  => $val['customer_name'],
                "num"  => $val['num'],
                "post_money"  => $val['post_money'],
                "balancing"  => $val['balancing'],
                "gap_money"  => $val['balancing'] - $val['post_money'],
                "create_time"  => date('Y-m-d H:i:s',time())
            );
        }
        if(!empty($arr)){
            $dbCount = M("Send_count_customer");
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