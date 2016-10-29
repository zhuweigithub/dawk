<?php
namespace Admin\Controller;
class OrgController extends AdminController
{
   public function index(){

       $Model  = M();
       $sql    = "select a.id ,a.in_out_org,a.org_code,a.area_name,b.id as gid,b.title
         from t_in_out_org as a left join t_auth_group as b on a.id= b.org_id";
       $result = $Model->query($sql);
       $this->assign("result", $result);
       $this->display();
   }
    public function getGroup(){
        $result = M("Auth_group")->field("id , title")->where("org_id = 0")->select();
        echo json_encode($result);
    }
    public function band(){
        $org_id     = $_POST['org_id'];
        $selectVal  = $_POST['selectVal'];

        $arr = array(
            "id" => $selectVal,
            "org_id" => $org_id
        );
        M("Auth_group")->save($arr);

        echo 1;
    }
}