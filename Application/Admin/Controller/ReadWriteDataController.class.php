<?php
namespace Admin\Controller;
class ReadWriteDataController extends AdminController
{
    /**
     * 导入数据
     */
    public function writeData()
    {
        $result = C("db_table");
        $this->assign("result", $result);
        $this->display();
    }

    /**
     * 一些配置设置
     */
    public function configure()
    {
        $Model = M();
        $sql = "select a.id ,a.zone_code,a.short_name,a.name,b.first_charge,b.second_charge,b.three_charge,c.first_weight,c.second_weight,c.three_weight
         from t_province as a left join t_charge as b on a.id= b.province_id left join t_province_attr as c  on a.id = c.province_id";
        $result = $Model->query($sql);
        $this->assign("result", $result);
        $this->display();
    }

    public function ReadData()
    {

        $result = C("CONFIG_TABLE");
        $this->assign("result", $result);
        $this->display();

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
                "name" => $_POST['long_name'],
                "short_name" => $_POST['small_name'],
                "zone_code" => $_POST['code']
            );
            $db->add($arr);
        } else {
            $arr = array(
                "id" => $_POST['id'],
                "name" => $_POST['long_name'],
                "short_name" => $_POST['small_name'],
                "zone_code" => $_POST['code']
            );
            $db->save($arr);
        }
        $this->success("数据更新成功！");

    }
    public function updateRule(){
        if(empty($_POST)){
            echo 0;return;
        }
        $id = $_POST['id'];
        $code = $_POST['code'];
        $short_name = $_POST['short_name'];
        $name = $_POST['name'];
        $first = $_POST['first'];
        $second = $_POST['second'];
        $three = $_POST['three'];
        $first_arr = explode('/',$first);
        $first_charge = $first_arr[0];
        $first_weight = $first_arr[1];
        $second_arr = explode('/',$second);
        $second_charge = $second_arr[0];
        $second_weight = $second_arr[1];
        $three_arr = explode('/',$three);
        $three_charge = $three_arr[0];
        $three_weight = $three_arr[1];
        $province = array(
            "id" =>$id,
            "code" =>$code,
            "short_name" =>$short_name,
            "name" =>$name
        );
        M("Province")->save($province);
        $dbCharge = M("Charge");
        $chargeResult = $dbCharge->where("province_id=".$id)->select();
        if(empty($chargeResult)){
            $charge = array(
                "province_id" => $id,
                "first_charge" =>$first_charge,
                "second_charge" =>$second_charge,
                "three_charge" =>$three_charge

            );
            $dbCharge->add($charge);
        }else{
            $charge = array(
                "id" =>$chargeResult[0]['id'],
                "province_id" => $id,
                "first_charge" =>$first_charge,
                "second_charge" =>$second_charge,
                "three_charge" =>$three_charge
            );
            $dbCharge->save($charge);
        }

        $dbProvinceAttr = M("Province_attr");
        $attrResult = $dbProvinceAttr->where("province_id=".$id)->select();
        if(empty($attrResult)){
            $attr = array(
                "province_id" => $id,
                "first_weight" =>$first_weight,
                "second_weight" =>$second_weight,
                "three_weight" =>$three_weight

            );
            $dbProvinceAttr->add($attr);
        }else{
            $attr = array(
                "id" =>$attrResult[0]['id'],
                "province_id" => $id,
                "first_weight" =>$first_weight,
                "second_weight" =>$second_weight,
                "three_weight" =>$three_weight
            );
            $dbProvinceAttr->save($attr);
        }
        echo 1;
    }
    public function delRule(){
        if(empty($_POST['id'])){
            echo 0;
        }
        $id = $_POST['id'];
        M("Province")->delete($id);
        M("Charge")->where("province_id=".$id)->delete();
        M("Province_attr")->where("province_id=".$id)->delete();
        echo 1;
    }
	public function inOutOrg(){
		$this->display();
	}

}