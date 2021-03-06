<?php
namespace Admin\Controller;
class ReadWriteDataController extends AdminController
{

	public function test(){
		$this->display();
	}
	/**
	 * 导入数据
	 */
	public function writeData()
	{
		$result = C("db_table");
        $sub_store_list = [];
        if(intval(session('user_auth')['uid']) === C('USER_ADMINISTRATOR')){
           $sub_store_list = M("Auth_group")->field("id,title")->where("status = 1")->select();

        }
        $param['config_name'] = "MONTHLY_COUNT_DATA";
        $rule = M("Config_system")->where($param)->find();
        $this->assign("rule", $rule['config_value']);
		$this->assign("result", $result);
		$this->assign("sub_store_list", $sub_store_list);
		$this->display();
	}

	/**
	 * 一些配置设置
	 */
	public function configure()
	{

		$Model  = M();
		$sql    = "select a.id ,a.zone_name,a.short_name,a.name,b.first_charge,b.second_charge,b.three_charge,b.first_charge_s,b.three_charge_s,
                    c.first_weight,c.second_weight,c.three_weight,c.first_weight_s,c.three_weight_s
                    from t_province as a left join t_charge as b on a.id= b.province_id left join t_province_attr as c  on a.id = c.province_id";
		$result = $Model->query($sql);
        $param['config_name'] = "MONTHLY_ORDER";
        $rule = M("Config_system")->where($param)->find();
		$this->assign("result", $result);
        $this->assign("rule", $rule['config_value']);
		$this->display();
	}
     public function countData(){
         $result = M("Send_month")->select();
         $this->assign("result",$result);
         $this->assign('is_admin', $this->isAdmin());
         $this->display();
     }
	public function ReadData()
	{

        $start_time = $_GET['start_time'] ?  $_GET['start_time'] : date("Y-m-d",time()-30*24*3600);
        $end_time = $_GET['end_time'] ? $_GET['end_time'] : date("Y-m-d",time());
        $map['in_out_date'][] = array('egt',$start_time);
        $map['in_out_date'][] = array('elt',$end_time);
        if(!empty($_GET['customer_name'])){
            $map['customer_name'] = array('like',"%".$_GET['customer_name']."%");
        }
        if(!$this->isAdmin()){
            $result = M("Member")->field("area")->where("uid = " .session('user_auth')['uid'])->find();
            $map['area_id'] = $result['area'];
        }
        $field = "id,express_number,in_out_date,in_out_org_name,customer_name,customer_code,sub_store,send_province,send_city,
        weight,post_money,balancing,(balancing-post_money) as gap_money,team_name,team_member_name";
        $list   = $this->lists('Send_detail', $map ,"in_out_date",$field);
        int_to_string($list);
        $map['start_time'] = $start_time;
        $map['end_time'] = $end_time;
        $map['customer_name'] = $_GET['customer_name'];
        $this->assign('param',$map);
        $this->assign('result', $list);
        $this->meta_title = '数据总览';
        $this->display();

	}
    public function search_customer(){
        $tableName = "send_count_date_customer";
        $param['customer_name'] = array('like',"%".$_GET['customer_name']."%");
        $result = M($tableName)->field("customer_name")->where($param)->select();
        echo json_encode($result);
    }
    private function isAdmin(){
        return intval(session('user_auth')['uid']) === C('USER_ADMINISTRATOR');
    }
    public function getSendCount(){
        $month = $_POST['month'];
        $type = $_POST['type'];
        $customer_name = $_POST['customer_name'];

        $param['month'] = $month;

        //todo 仅仅只有系统管理员才能去统计顾客的每天发单数，因为顾客可以存在多个分仓总
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
        echo json_encode($result);
    }

	public function addOrUpdateProvince()
	{
		if (empty($_GET['id'])) {
			$result = M("Province")->find($_GET['id']);
			$this->assign("result", $result);
		}
		$this->display();
	}

	public function saveProvince()
	{
		if (empty($_POST['code']) || empty($_POST['small_name']) || empty($_POST['long_name'])) {
			$this->error("数据不能为空！");
		}
		$db = M("Province");
		if (empty($_POST['id'])) {
			$arr = array(
				"name"       => $_POST['long_name'],
				"short_name" => $_POST['small_name'],
				"zone_code"  => $_POST['code']
			);
			$db->add($arr);
		} else {
			$arr = array(
				"id"         => $_POST['id'],
				"name"       => $_POST['long_name'],
				"short_name" => $_POST['small_name'],
				"zone_code"  => $_POST['code']
			);
			$db->save($arr);
		}
		$this->success("数据更新成功！");

	}

	public function updateRule()
	{
		if (empty($_POST)) {
			echo 0;
			return;
		}
		$id            = $_POST['id'];
		$code          = $_POST['code'];
		$short_name    = $_POST['short_name'];
		$name          = $_POST['name'];
		$first         = $_POST['first'];
		$second        = $_POST['second'];
		$three         = $_POST['three'];
		$first_s       = $_POST['first_s'];
		$three_s       = $_POST['three_s'];
		$first_arr     = explode('/', $first);
		$first_charge  = $first_arr[0];
		$first_weight  = $first_arr[1];
		$second_arr    = explode('/', $second);
		$second_charge = $second_arr[0];
		$second_weight = $second_arr[1];
		$three_arr     = explode('/', $three);
		$three_charge  = $three_arr[0];
		$three_weight  = $three_arr[1];


        $first_s_arr     = explode('/', $first_s);
        $first_s_charge  = $first_s_arr[0];
        $first_s_weight  = $first_s_arr[1];
        $three_s_arr     = explode('/', $three_s);
        $three_s_charge  = $three_s_arr[0];
        $three_s_weight  = $three_s_arr[1];
		$province      = array(
			"id"         => $id,
			"code"       => $code,
			"short_name" => $short_name,
			"name"       => $name
		);
		M("Province")->save($province);
		$dbCharge     = M("Charge");
		$chargeResult = $dbCharge->where("province_id=" . $id)->select();
		if (empty($chargeResult)) {
			$charge = array(
				"province_id"   => $id,
				"first_charge"  => $first_charge,
				"second_charge" => $second_charge,
				"three_charge"  => $three_charge,
				"first_charge_s" => $first_s_charge,
				"three_charge_s"  => $three_s_charge

			);
			$dbCharge->add($charge);
		} else {
			$charge = array(
				"id"            => $chargeResult[0]['id'],
				"province_id"   => $id,
				"first_charge"  => $first_charge,
				"second_charge" => $second_charge,
				"three_charge"  => $three_charge,
                "first_charge_s" => $first_s_charge,
                "three_charge_s"  => $three_s_charge
			);
			$dbCharge->save($charge);
		}

		$dbProvinceAttr = M("Province_attr");
		$attrResult     = $dbProvinceAttr->where("province_id=" . $id)->select();
		if (empty($attrResult)) {
			$attr = array(
				"province_id"   => $id,
				"first_weight"  => $first_weight,
				"second_weight" => $second_weight,
				"three_weight"  => $three_weight,
                "first_weight_s" => $first_s_weight,
                "three_weight_s"  => $three_s_weight

			);
			$dbProvinceAttr->add($attr);
		} else {
			$attr = array(
				"id"            => $attrResult[0]['id'],
				"province_id"   => $id,
				"first_weight"  => $first_weight,
				"second_weight" => $second_weight,
				"three_weight"  => $three_weight,
                "first_weight_s" => $first_s_weight,
                "three_weight_s"  => $three_s_weight
			);
			$dbProvinceAttr->save($attr);
		}
		echo 1;
	}

	public function delRule()
	{
		if (empty($_POST['id'])) {
			echo 0;
		}
		$id = $_POST['id'];
		M("Province")->delete($id);
		M("Charge")->where("province_id=" . $id)->delete();
		M("Province_attr")->where("province_id=" . $id)->delete();
		echo 1;
	}

	public function inOutOrg()
	{

		$this->display();
	}

	public function subStore(){
        $id = $_GET['id'];
        if(empty($id)){
            $this->error("区域id不能为空");
        }
		$subDb = M("Sub_store");
		$result = $subDb->where("group_id=". $id)->select();

        foreach($result as $key=>$val){
            $subId[] = $val['id'];
        }
        $param['sub_store_id'] = array("in",$subId);
        $param['status'] = 1;
        $list = M('Member')->field("uid,nickname,sub_store_id")->where($param)->select();
        foreach($result as $key=>$val){
            foreach($list as $i => $j){
                if($val['id'] == $j['sub_store_id']){
                    $result[$key]['uid'] = $j['uid'];
                    $result[$key]['nickname'] = $j['nickname'];
                }
            }

        }
        $this->assign("group_id",$id);
		$this->assign("result",$result);
		$this->display();
	}
    public function getMember(){
        $group_id = $_POST['group_id'];
        $param['area'] = $group_id;
        $param['sub_store_id'] = 0;
        $param['status'] = 1;
        $db = M("Member");
        $list = $db->field("uid,nickname")->where($param)->select();
        echo json_encode($list);
    }
    public function band(){
        $sub_id     = $_POST['sub_id'];
        $selectVal  = $_POST['selectVal'];
        $param['sub_store_id'] = $sub_id;
        $dbM = M("Member");
        $list = $dbM->field("uid,nickname")->where($param)->find();
        if(count($list) > 0){
            $arr = array(
                "uid" => $list['uid'],
                "sub_store_id" => 0
            );
            $dbM->save($arr);
        }
        $arr = array(
            "uid" => $selectVal,
            "sub_store_id" => $sub_id
        );
        $dbM->save($arr);
        echo 1;
    }
    public function addSubStore(){
        $store_name = $_POST['store_name'];
        $group_id   = $_POST['group_id'];
        $arr = array(
            "group_id"   => $group_id,
            "sub_store_name" => $store_name
        );
        M("Sub_store")->add($arr);
        echo 1;
    }
    public function delSubStore(){
        $store_id = $_POST['store_id'];
        $param['sub_store_id'] = $store_id;
        $dbM = M("Member");
        $list = $dbM->field("uid,nickname")->where($param)->find();
        if(count($list) > 0){
            $arr = array(
                "uid" => $list['uid'],
                "sub_store_id" => 0
            );
            $dbM->save($arr);
        }
        M("Sub_store")->where('id ='.$store_id)->delete();
        echo 1;
    }
    public function setRule(){
       if( !empty($_POST['rule'])) {
           $systemDb = M("Config_system");
           $param['config_name'] = "MONTHLY_ORDER";
           $result = $systemDb->where($param)->find();
           if( count($result) <= 0 ){
               $arr = array(
                   "config_name" => "MONTHLY_ORDER",
                   "config_value" => $_POST['rule'],
                   "create_time" => date("Y-m-d H:i:s",time())
               );
               $systemDb->add($arr);
           }else{
               $arr = array(
                   "id"          => $result['id'],
                   "config_name" => "MONTHLY_ORDER",
                   "config_value" => $_POST['rule'],
                   "create_time" => date("Y-m-d H:i:s",time())
               );
               $systemDb->save($arr);
           }
           echo 1;
       }
    }
    public function setCountData(){
       if( !empty($_POST['rule'])) {
           $systemDb = M("Config_system");
           $param['config_name'] = "MONTHLY_COUNT_DATA";
           $result = $systemDb->where($param)->find();
           if( count($result) <= 0 ){
               $arr = array(
                   "config_name" => "MONTHLY_COUNT_DATA",
                   "config_value" => $_POST['rule'],
                   "create_time" => date("Y-m-d H:i:s",time())
               );
               $systemDb->add($arr);
           }else{
               $arr = array(
                   "id"          => $result['id'],
                   "config_name" => "MONTHLY_COUNT_DATA",
                   "config_value" => $_POST['rule'],
                   "create_time" => date("Y-m-d H:i:s",time())
               );
               $systemDb->save($arr);
           }
           echo 1;
       }
    }

}