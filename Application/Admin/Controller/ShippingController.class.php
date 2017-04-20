<?php
namespace Admin\Controller;
class ShippingController extends AdminController
{
    /**
     * 一些配置设置
     */
    public function index()
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
    public function other(){
        $list   = $this->lists('Staple_rule', '' ,"id");
        $db = M("Staple_rule_ext");
        foreach($list as $key=>$val){
            $result = $db->where("staple_id=".$val['id'])->select();
            $list[$key]['result'] = $result;
        }
        int_to_string($list);
        $this->assign('_list', $list);
        $this->display();
    }
    public function addZone(){
        $sub_id = $_GET['sub_id'];
        $sub_name = $_GET["sub_name"];
        if(empty($sub_id) || empty($sub_name)){
            $this->error("分仓不能为空！");
        }
        $Model  = M();
        $sql    = "select a.id ,a.zone_name,a.short_name,a.name,b.first_charge,b.second_charge,b.three_charge,b.first_charge_s,b.three_charge_s,
                    c.first_weight,c.second_weight,c.three_weight,c.first_weight_s,c.three_weight_s
                    from t_province as a left join t_charge as b on a.id= b.province_id left join t_province_attr as c  on a.id = c.province_id";
        $list = $Model->query($sql);
        $this->assign('_list', $list);
        $this->assign("sub_id",$sub_id);
        $this->assign("sub_name",$sub_name);
        $this->display();
    }

    public function getProvinceList(){
        $result = M("Province")->select();
        echo json_encode($result);
    }

	public function addZoneAjax(){
		$param['store_id'] = $_POST['store_id'];
		$param['zone_name'] = $_POST['zone_name'];
		if(empty($param['store_id']) || empty($param['zone_name'])){
			echo 2;//表示store_id不能为空
		}

        M("Zone")->add($param);
        echo 1;


	}
    /*public function getZoneList(){
        $param['store_id'] = $_POST['store_id'];
        if(empty($param['store_id'])){
            echo 2;//表示store_id不能为空
        }
        $param['status'] = 0;
        $result = M("Zone")->where($param)->select();
        if(count($result) > 0){
            for($i=0 ; $i < count($result) ; $i++){
                $provinceIds = str_split(',',$result[$i]['province_ids']);
             for($j=0 ; $j < count($provinceIds)-1;$j++){
                 $params['id'] = $provinceIds[$j];
                 $data = M("Province")->field("id,name,zone_name,zone_id")->where($params)->select();
             }
                $result[$i]['province_list'] = $data;
            }
        }
        echo json_encode($result);
    }*/
    public function getZoneList(){
        $param['store_id'] = $_POST['store_id'];
        if(empty($param['store_id'])){
            echo 2;//表示store_id不能为空
        }
        $param['status'] = 0;
        $result = M("Zone")->where($param)->select();
        $province['items'] = M("Province")->select();
        if(count($result) > 0){
            for($i=0 ; $i < count($result) ; $i++){
                $provinceIds = explode(',',$result[$i]['province_ids']);
                for($j=0 ; $j < count($provinceIds)-1;$j++){
                    for($z = 0; $z < count($province['items']) ;$z++ ){
                        if($province['items'][$z]["id"] == $provinceIds[$j]){
                            $province['items'][$z]['zone_name_n'] = $result[$i]['zone_name'];
                            $province['items'][$z]['zone_id_n'] = $result[$i]['id'];
                        }
                    }
                }
            }
            $province['zone'] = $result;
        }
        echo json_encode($province);
    }

    public function addZoneProvinceIds(){
        $store_id = $_POST['store_id'];
        if(empty($store_id)){
            echo 2;exit;//表示store_id不能为空
        }

        $zone_one    = $_POST['zone_one'];
        $zone_two    = $_POST['zone_two'];
        $zone_three    = $_POST['zone_three'];
        $zone_four    = $_POST['zone_four'];
        $zone_five    = $_POST['zone_five'];
        $zone_six    = $_POST['zone_six'];
        $zone_seven    = $_POST['zone_seven'];
            if($zone_one != null){
                $arr = array(
                    "province_ids" =>$zone_one,
                    "zone_name" =>"一区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("一区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }

            }
            if($zone_two != null){
                $arr = array(
                    "province_ids" =>$zone_two,
                    "zone_name" =>"二区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("二区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }
            }if($zone_three != null){
                $arr = array(
                    "province_ids" =>$zone_three,
                    "zone_name" =>"三区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("三区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }
            }
            if($zone_four != null){
                $arr = array(
                    "province_ids" =>$zone_four,
                    "zone_name" =>"四区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("四区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }
            }
            if($zone_five != null){
                $arr = array(
                    "province_ids" =>$zone_five,
                    "zone_name" =>"五区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("五区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }
            }
            if($zone_six != null){
                $arr = array(
                    "province_ids" =>$zone_six,
                    "zone_name" =>"六区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("六区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }
            }
            if($zone_seven != null){
                $arr = array(
                    "province_ids" =>$zone_seven,
                    "zone_name" =>"七区",
                    "store_id" =>$store_id,
                );
                $id = $this->selectZoneId("七区",$store_id);
                if($id > 0){
                    $arr['id'] = $id;
                    $id = M("Zone")->save($arr);
                }else{
                    $id = M("Zone")->add($arr);
                }
            }


      echo 1;

    }
    private function selectZoneId($zone_name,$store_id){
        $param['zone_name'] = $zone_name;
        $param['store_id']  = $store_id;
        $zoneObj = M("Zone")->where($param)->find();
        if(count($zoneObj) > 0){
            return $zoneObj['id'];
        }
        return 0;
    }

    public function addOther(){
        $sub_id = $_GET['sub_id'];
        $sub_name = $_GET["sub_name"];
        if(empty($sub_id) || empty($sub_name)){
            $this->error("分仓不能为空！");
        }


        $result = M("Province")->field("id,name,zone_name,zone_id")->order("zone_id")->select();
        $this->assign("result",$result);
        $this->assign("sub_id",$sub_id);
        $this->assign("sub_name",$sub_name);
        $this->display();
    }

    /*
     * public function addOther(){
        $sub_id = $_GET['sub_id'];
        $sub_name = $_GET["sub_name"];
        if(empty($sub_id) || empty($sub_name)){
            $this->error("分仓不能为空！");
        }

        $param['status'] = 0;
        $param['store_id'] = $sub_id;
        $result = M("Zone")->where($param)->select();
        $province = M("Province")->select();
        if(count($result) > 0){
            for($i=0 ; $i < count($result) ; $i++){
                $provinceIds = explode(',',$result[$i]['province_ids']);
                for($j=0 ; $j < count($provinceIds)-1;$j++){
                    for($z = 0; $z < count($province) ;$z++ ){
                        if($province[$z]["id"] == $provinceIds[$j]){
                            $province[$z]['zone_name_n'] = $result[$i]['zone_name'];
                            $province[$z]['zone_id_n'] = $result[$i]['id'];
                        }
                    }
                }
            }
        }

        //$result = M("Province")->field("id,name,zone_name,zone_id")->order("zone_id")->select();
        $this->assign("result",$province);
        $this->assign("zone",$result);
        $this->assign("sub_id",$sub_id);
        $this->assign("sub_name",$sub_name);
        $this->display();
    }
     *
     * */

	public function editOther(){
		$sub_id = $_GET['sub_id'];
		$sub_name = $_GET["sub_name"];
		if(empty($sub_id) || empty($sub_name)){
			$this->error("分仓不能为空！");
		}

		$result = M("Province")->field("id,name,zone_name,zone_id")->order("zone_id")->select();
		$rule = M("Staple_rule")->where("store_id=".$sub_id)->find();
		$db = M("Staple_rule_ext");
		$list = $db->where("staple_id=".$rule['id'])->select();
//dump($list);
		$this->assign("list",$list);
		$this->assign("result",$result);
		$this->assign("sub_id",$sub_id);
		$this->assign("sub_name",$sub_name);
		$this->display();
	}
    public function save_rule(){
        $staple_arr = $_POST['staple_arr'];
        $staple_arr = json_decode($staple_arr,true);
        $result = M("Staple_rule")->where("store_id=". $staple_arr['store_id'])->find();
        if(count($result) > 0){
            echo 2;exit;
        }
            $arr = array(
                "store_id" =>$staple_arr['store_id'],
                "store_name" =>$staple_arr['store_name'],
                "first_weight" =>$staple_arr['first_weight'],
            );
        $id = M("Staple_rule")->add($arr);
        if($id > 0){
            $db = M('Staple_rule_ext');
            $staple_ext_1 = $_POST['staple_ext_1'];
            $staple_ext_1 = json_decode($staple_ext_1,true);
            $arr1 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_1['zone_id'],
                "zone_name" =>$staple_ext_1['zone_name'],
                "first_weight_a" =>$staple_ext_1['first_weight_a'],
                "first_fee_a" =>$staple_ext_1['first_fee_a'],
                "first_weight_b" =>$staple_ext_1['first_weight_b'],
                "first_fee_b" =>$staple_ext_1['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_1['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_1['second_weight_end'],
                "second_fee_a" =>$staple_ext_1['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_1['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_1['second_fee_b'],
            );
            $db -> add($arr1);
            $staple_ext_2 = $_POST['staple_ext_2'];
            $staple_ext_2 = json_decode($staple_ext_2,true);
            $arr2 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_2['zone_id'],
                "zone_name" =>$staple_ext_2['zone_name'],
                "first_weight_a" =>$staple_ext_2['first_weight_a'],
                "first_fee_a" =>$staple_ext_2['first_fee_a'],
                "first_weight_b" =>$staple_ext_2['first_weight_b'],
                "first_fee_b" =>$staple_ext_2['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_2['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_2['second_weight_end'],
                "second_fee_a" =>$staple_ext_2['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_2['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_2['second_fee_b'],
            );
            $db -> add($arr2);

            $staple_ext_3 = $_POST['staple_ext_3'];
            $staple_ext_3 = json_decode($staple_ext_3,true);
            $arr3 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_3['zone_id'],
                "zone_name" =>$staple_ext_3['zone_name'],
                "first_weight_a" =>$staple_ext_3['first_weight_a'],
                "first_fee_a" =>$staple_ext_3['first_fee_a'],
                "first_weight_b" =>$staple_ext_3['first_weight_b'],
                "first_fee_b" =>$staple_ext_3['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_3['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_3['second_weight_end'],
                "second_fee_a" =>$staple_ext_3['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_3['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_3['second_fee_b'],
            );
            $db -> add($arr3);

            $staple_ext_4 = $_POST['staple_ext_4'];
            $staple_ext_4 = json_decode($staple_ext_4,true);
            $arr4 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_4['zone_id'],
                "zone_name" =>$staple_ext_4['zone_name'],
                "first_weight_a" =>$staple_ext_4['first_weight_a'],
                "first_fee_a" =>$staple_ext_4['first_fee_a'],
                "first_weight_b" =>$staple_ext_4['first_weight_b'],
                "first_fee_b" =>$staple_ext_4['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_4['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_4['second_weight_end'],
                "second_fee_a" =>$staple_ext_4['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_4['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_4['second_fee_b'],
            );
            $db -> add($arr4);

            $staple_ext_5 = $_POST['staple_ext_5'];
            $staple_ext_5 = json_decode($staple_ext_5,true);
            $arr5 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_5['zone_id'],
                "zone_name" =>$staple_ext_5['zone_name'],
                "first_weight_a" =>$staple_ext_5['first_weight_a'],
                "first_fee_a" =>$staple_ext_5['first_fee_a'],
                "first_weight_b" =>$staple_ext_5['first_weight_b'],
                "first_fee_b" =>$staple_ext_5['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_5['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_5['second_weight_end'],
                "second_fee_a" =>$staple_ext_5['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_5['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_5['second_fee_b'],
            );
            $db -> add($arr5);

            $staple_ext_6 = $_POST['staple_ext_6'];
            $staple_ext_6 = json_decode($staple_ext_6,true);
            $arr6 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_6['zone_id'],
                "zone_name" =>$staple_ext_6['zone_name'],
                "first_weight_a" =>$staple_ext_6['first_weight_a'],
                "first_fee_a" =>$staple_ext_6['first_fee_a'],
                "first_weight_b" =>$staple_ext_6['first_weight_b'],
                "first_fee_b" =>$staple_ext_6['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_6['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_6['second_weight_end'],
                "second_fee_a" =>$staple_ext_6['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_6['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_6['second_fee_b'],
            );
            $db -> add($arr6);

            $staple_ext_7 = $_POST['staple_ext_7'];
            $staple_ext_7 = json_decode($staple_ext_7,true);
            $arr7 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_7['zone_id'],
                "zone_name" =>$staple_ext_7['zone_name'],
                "first_weight_a" =>$staple_ext_7['first_weight_a'],
                "first_fee_a" =>$staple_ext_7['first_fee_a'],
                "first_weight_b" =>$staple_ext_7['first_weight_b'],
                "first_fee_b" =>$staple_ext_7['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_7['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_7['second_weight_end'],
                "second_fee_a" =>$staple_ext_7['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_7['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_7['second_fee_b'],
            );
            $db -> add($arr7);
            $staple_ext_8 = $_POST['staple_ext_8'];
            $staple_ext_8 = json_decode($staple_ext_8,true);
            $arr8 = array(
                "staple_id" => $id,
                "zone_id" =>$staple_ext_8['zone_id'],
                "zone_name" =>$staple_ext_8['zone_name'],
                "first_weight_a" =>$staple_ext_8['first_weight_a'],
                "first_fee_a" =>$staple_ext_8['first_fee_a'],
                "first_weight_b" =>$staple_ext_8['first_weight_b'],
                "first_fee_b" =>$staple_ext_8['first_fee_b'],
                "second_weight_start_a" =>$staple_ext_8['second_weight_start_a'],
                "second_weight_end" =>$staple_ext_8['second_weight_end'],
                "second_fee_a" =>$staple_ext_8['second_fee_a'],
                "second_weight_start_b" =>$staple_ext_8['second_weight_start_b'],
                "second_fee_b" =>$staple_ext_8['second_fee_b'],
            );
            $db -> add($arr8);

        }
		echo 1;
    }
	public function edit_save_rule(){
		$staple_arr = $_POST['staple_arr'];
		$staple_arr = json_decode($staple_arr,true);
/*		$result = M("Staple_rule")->where("store_id=". $staple_arr['store_id'])->find();
		if(count($result) > 0){
			echo 2;exit;
		}
		$arr = array(
			"store_id" =>$staple_arr['store_id'],
			"store_name" =>$staple_arr['store_name'],
			"first_weight" =>$staple_arr['first_weight'],
		);
		$id = M("Staple_rule")->add($arr);*/
		//if($id > 0){
			$db = M('Staple_rule_ext');
			$staple_ext_1 = $_POST['staple_ext_1'];
			$staple_ext_1 = json_decode($staple_ext_1,true);
			$arr1 = array(
				"id" => $staple_ext_1['id'],
				"zone_id" =>$staple_ext_1['zone_id'],
				"zone_name" =>$staple_ext_1['zone_name'],
				"first_weight_a" =>$staple_ext_1['first_weight_a'],
				"first_fee_a" =>$staple_ext_1['first_fee_a'],
				"first_weight_b" =>$staple_ext_1['first_weight_b'],
				"first_fee_b" =>$staple_ext_1['first_fee_b'],
				"second_weight_start_a" =>$staple_ext_1['second_weight_start_a'],
				"second_weight_end" =>$staple_ext_1['second_weight_end'],
				"second_fee_a" =>$staple_ext_1['second_fee_a'],
				"second_weight_start_b" =>$staple_ext_1['second_weight_start_b'],
				"second_fee_b" =>$staple_ext_1['second_fee_b'],
			);
			$db -> save($arr1);
			$staple_ext_2 = $_POST['staple_ext_2'];
			$staple_ext_2 = json_decode($staple_ext_2,true);
			$arr2 = array(
				"id" => $staple_ext_2['id'],
				"zone_id" =>$staple_ext_2['zone_id'],
				"zone_name" =>$staple_ext_2['zone_name'],
				"first_weight_a" =>$staple_ext_2['first_weight_a'],
				"first_fee_a" =>$staple_ext_2['first_fee_a'],
				"first_weight_b" =>$staple_ext_2['first_weight_b'],
				"first_fee_b" =>$staple_ext_2['first_fee_b'],
				"second_weight_start_a" =>$staple_ext_2['second_weight_start_a'],
				"second_weight_end" =>$staple_ext_2['second_weight_end'],
				"second_fee_a" =>$staple_ext_2['second_fee_a'],
				"second_weight_start_b" =>$staple_ext_2['second_weight_start_b'],
				"second_fee_b" =>$staple_ext_2['second_fee_b'],
			);
			$db -> save($arr2);

			$staple_ext_3 = $_POST['staple_ext_3'];
			$staple_ext_3 = json_decode($staple_ext_3,true);
			$arr3 = array(
				"id" => $staple_ext_3['id'],
				"zone_id" =>$staple_ext_3['zone_id'],
				"zone_name" =>$staple_ext_3['zone_name'],
				"first_weight_a" =>$staple_ext_3['first_weight_a'],
				"first_fee_a" =>$staple_ext_3['first_fee_a'],
				"first_weight_b" =>$staple_ext_3['first_weight_b'],
				"first_fee_b" =>$staple_ext_3['first_fee_b'],
				"second_weight_start_a" =>$staple_ext_3['second_weight_start_a'],
				"second_weight_end" =>$staple_ext_3['second_weight_end'],
				"second_fee_a" =>$staple_ext_3['second_fee_a'],
				"second_weight_start_b" =>$staple_ext_3['second_weight_start_b'],
				"second_fee_b" =>$staple_ext_3['second_fee_b'],
			);
			$db -> save($arr3);

			$staple_ext_4 = $_POST['staple_ext_4'];
			$staple_ext_4 = json_decode($staple_ext_4,true);
			$arr4 = array(
				"id" => $staple_ext_4['id'],
				"zone_id" =>$staple_ext_4['zone_id'],
				"zone_name" =>$staple_ext_4['zone_name'],
				"first_weight_a" =>$staple_ext_4['first_weight_a'],
				"first_fee_a" =>$staple_ext_4['first_fee_a'],
				"first_weight_b" =>$staple_ext_4['first_weight_b'],
				"first_fee_b" =>$staple_ext_4['first_fee_b'],
				"second_weight_start_a" =>$staple_ext_4['second_weight_start_a'],
				"second_weight_end" =>$staple_ext_4['second_weight_end'],
				"second_fee_a" =>$staple_ext_4['second_fee_a'],
				"second_weight_start_b" =>$staple_ext_4['second_weight_start_b'],
				"second_fee_b" =>$staple_ext_4['second_fee_b'],
			);
			$db -> save($arr4);

			$staple_ext_5 = $_POST['staple_ext_5'];
			$staple_ext_5 = json_decode($staple_ext_5,true);
			$arr5 = array(
				"id" => $staple_ext_5['id'],
				"zone_id" =>$staple_ext_5['zone_id'],
				"zone_name" =>$staple_ext_5['zone_name'],
				"first_weight_a" =>$staple_ext_5['first_weight_a'],
				"first_fee_a" =>$staple_ext_5['first_fee_a'],
				"first_weight_b" =>$staple_ext_5['first_weight_b'],
				"first_fee_b" =>$staple_ext_5['first_fee_b'],
				"second_weight_start_a" =>$staple_ext_5['second_weight_start_a'],
				"second_weight_end" =>$staple_ext_5['second_weight_end'],
				"second_fee_a" =>$staple_ext_5['second_fee_a'],
				"second_weight_start_b" =>$staple_ext_5['second_weight_start_b'],
				"second_fee_b" =>$staple_ext_5['second_fee_b'],
			);
			$db -> save($arr5);
        $staple_ext_6 = $_POST['staple_ext_6'];
        $staple_ext_6 = json_decode($staple_ext_6,true);
        $arr6 = array(
            "id" => $staple_ext_6['id'],
            "zone_id" =>$staple_ext_6['zone_id'],
            "zone_name" =>$staple_ext_6['zone_name'],
            "first_weight_a" =>$staple_ext_6['first_weight_a'],
            "first_fee_a" =>$staple_ext_6['first_fee_a'],
            "first_weight_b" =>$staple_ext_6['first_weight_b'],
            "first_fee_b" =>$staple_ext_6['first_fee_b'],
            "second_weight_start_a" =>$staple_ext_6['second_weight_start_a'],
            "second_weight_end" =>$staple_ext_6['second_weight_end'],
            "second_fee_a" =>$staple_ext_6['second_fee_a'],
            "second_weight_start_b" =>$staple_ext_6['second_weight_start_b'],
            "second_fee_b" =>$staple_ext_6['second_fee_b'],
        );
        $db -> save($arr6);

        $staple_ext_7 = $_POST['staple_ext_7'];
        $staple_ext_7 = json_decode($staple_ext_7,true);
        $arr7 = array(
            "id" => $staple_ext_7['id'],
            "zone_id" =>$staple_ext_7['zone_id'],
            "zone_name" =>$staple_ext_7['zone_name'],
            "first_weight_a" =>$staple_ext_7['first_weight_a'],
            "first_fee_a" =>$staple_ext_7['first_fee_a'],
            "first_weight_b" =>$staple_ext_7['first_weight_b'],
            "first_fee_b" =>$staple_ext_7['first_fee_b'],
            "second_weight_start_a" =>$staple_ext_7['second_weight_start_a'],
            "second_weight_end" =>$staple_ext_7['second_weight_end'],
            "second_fee_a" =>$staple_ext_7['second_fee_a'],
            "second_weight_start_b" =>$staple_ext_7['second_weight_start_b'],
            "second_fee_b" =>$staple_ext_7['second_fee_b'],
        );
        $db -> save($arr7);
        $staple_ext_8 = $_POST['staple_ext_8'];
        $staple_ext_8 = json_decode($staple_ext_8,true);
        $arr8 = array(
            "id" => $staple_ext_8['id'],
            "zone_id" =>$staple_ext_8['zone_id'],
            "zone_name" =>$staple_ext_8['zone_name'],
            "first_weight_a" =>$staple_ext_8['first_weight_a'],
            "first_fee_a" =>$staple_ext_8['first_fee_a'],
            "first_weight_b" =>$staple_ext_8['first_weight_b'],
            "first_fee_b" =>$staple_ext_8['first_fee_b'],
            "second_weight_start_a" =>$staple_ext_8['second_weight_start_a'],
            "second_weight_end" =>$staple_ext_8['second_weight_end'],
            "second_fee_a" =>$staple_ext_8['second_fee_a'],
            "second_weight_start_b" =>$staple_ext_8['second_weight_start_b'],
            "second_fee_b" =>$staple_ext_8['second_fee_b'],
        );
        $db -> save($arr8);
		//}
		echo 1;
	}
	public function delRule()
	{
		if (empty($_POST['id'])) {
			echo 0;
		}
		$id = $_POST['id'];
		M("Staple_rule")->delete($id);
		M("Staple_rule_ext")->where("staple_id=" . $id)->delete();
		echo 1;
	}
}