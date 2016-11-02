<?php
namespace Admin\Controller;
class SubStoreController extends AdminController
{
    public function index(){
        $search_key = $_GET['search_key'];
        if(!empty($_GET['search_key'])){
            $map['sub_store_name|customer_name']=   array(array('like','%'.$search_key.'%'),array('like','%'.$search_key.'%'),'_multi'=>true);
        }
        $subDb = M("Sub_store");
        $result = $subDb->where($map)->order("id desc")->select();

        $this->assign("result",$result);
        $this->assign("search_key",$search_key);
        $this->display();
    }
    public function editSubStore(){
        $id = $_POST['id'];
        $sub_store_name = $_POST['sub_store_name'];
        $customer_name = $_POST['customer_name'];
        $arr = array(
            "id" => $id,
            "sub_store_name" => $sub_store_name,
            "customer_name" => $customer_name,
            "update_time" => date("Y-m-d H:s:i",time())
        );
        $id = M("Sub_store")->save($arr);
        if($id > 0){
            echo 1;
        }else{
            echo 0;
        }

    }
    public function addSubStore(){
        $store_name = $_POST['store_name'];
        $customer_name = $_POST['customer_name'];
        $arr = array(
            "sub_store_name" => $store_name,
            "customer_name" => $customer_name,
            "create_time" => date("Y-m-d H:s:i",time())
        );
        $id = M("Sub_store")->add($arr);
        if($id > 0){
            echo 1;
        }else{
            echo 0;
        }

    }


}