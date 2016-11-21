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

        }
echo 1;
    }
}