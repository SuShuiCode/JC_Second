<?php
/**
 * Created by PhpStorm.
 * User: Ail
 * Date: 2017/10/14
 * Time: 16:49
 */
namespace Admin\Controller;
use PhpParser\Node\Expr\Array_;
use Think\Controller;
class ContractController extends Controller
{
    public $user = null;

    public function _initialize()
    {
        load('@.functions');
        D("account")->checkLogin();
        $this->assign('menu_active', strtolower(CONTROLLER_NAME));
        $this->assign('menu_secoud_active', strtolower(ACTION_NAME));
    }
    public function getTestReport(){
        $page = I("p",'int');
        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;
        $test_reprot=M("test_reprot");//实例化对象
        $where['authorizer']=1;
        $where['ifinnerissue']=0;
        $rs=$test_reprot->where($where)->field('id,centreNo')->order('id')->limit("{$offset},{$pagesize}")->select();//查找条件为已经批准并且内部尚未领取的报告
        $count = D("test_reprot")->where($where)->count();
        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $pagination= $Page->show();// 分页显示输出
        $body = array(
            'lists'=>$rs,
            'pagination'=>$pagination,
        );
        $this->assign($body);
        $this->display();
    }
    //外部签发检验报告
    public function issueTestReport(){
        $page = I("p",'int');
        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;
        $test_reprot=M("test_reprot");//实例化对象
        $contract=M("contract");//实例化对象
        //显示条件
        $where['authorizer']=1;
        $where['ifinnerissue']=1;
        $where['ifouterissue']=0;
        //从contract数据表中找出和test_reprot数据表centreno匹配的那一行
        //返回对应的clientSign和telephone
        $rs=$test_reprot->where($where)
            ->join('contract ON test_reprot.centreNo = contract.centreNo')
            ->field('test_reprot.id,test_reprot.centreNo,test_reprot.productUnit,contract.clientSign,contract.telephone')
            ->limit("{$offset},{$pagesize}")->order('test_reprot.id')->select();

        $count = D("test_reprot")->where($where)->count();
        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $pagination= $Page->show();// 分页显示输出
        $body = array(
            'lists'=>$rs,
            'pagination'=>$pagination,
        );
        $this->assign($body);
        $this->display();
    }
    //标记签发报告，更新数据库
    public function doUpd(){
// 要修改的数据对象属性赋值
        $data['ifouterissue'] = 1;
        $id =I("id",0,'intval');
        $rs = array("msg"=>"fail");
        if(D("test_reprot")->where("id=".$id)->save($data)){
            $rs['msg'] = 'succ';
        }
        $this->ajaxReturn($rs);
    }



//领取检验报告


    //进入合同录入页面
    public function input(){
        $admin_auth = session("admin_auth");
        $collector = $admin_auth['name'];
        $department = $admin_auth['department'];
        $if_admin = $admin_auth['super_admin'];
        //费用查询
        $criteria = I('criteria');
        $page = I("p",'int');
        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;
        $list = D("test_fee")->where('criteria like "%'.$criteria.'%"')->limit("{$offset},{$pagesize}")->select();
        $count = D("test_fee")->where('criteria like "%'.$criteria.'%"')->count();
        $count_unfinish=0;
        if($if_admin!=1){
            if($department=='G1') $count_unfinish = D("contract c")->join('LEFT JOIN contract_flow f on c.centreNo=f.centreNo')->where('collector_partment="G1" and f.centreNo is null')->count();
            else if($department=='G2')  $count_unfinish = D("contract c")->join('LEFT JOIN contract_flow f on c.centreNo=f.centreNo')->where('collector_partment="G2" and f.centreNo is null')->count();
            else $count_unfinish = D("contract c")->join('LEFT JOIN contract_flow f on c.centreNo=f.centreNo')->where('SUBSTR(c.centreNo,7,1) ="'.$department.'" and f.centreNo is null')->count();
        }
        //pr(D("contract c")->getLastSql());

        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</ a></ul>");
        $pagination= $Page->show();// 分页显示输出		

        $body=array(
            'collector'=>$collector,
            'department'=>$department,
            "fee_list"=>$list,
            'pagination'=>$pagination,
            'count_unfinish'=>$count_unfinish,
        );
        $this->assign($body);
        $this->display();
    }

    //合同录入
    public function doAddContract(){
        $clientName = str_replace(' ','',I("clientName"));
        $productUnit = str_replace(' ','',I("productUnit"));
        $sampleName = I("sampleName");
        $sampleCode = I("sampleCode");
        $grade = I("grade");
        $specification = I("specification");
        $trademark = I("trademark");
        $productionDate = I("productionDate");
        $sampleQuantity = I("sampleQuantity");
        //$sampleunti = I("sampleunti");
        $sampleStatus = I("sampleStatus");
        $ration = I("ration");
        $testCriteria = I("testCriteria");
        $testItem = I("testItem");
        $testCategory = I("testCategory");
        $ifOnline = I("ifOnline",0);
        $postMethod = I("postMethod");
        $ifSubpackage = I("ifSubpackage");
        $clientSign = I("clientSign");
        $telephone = I("telephone");
        $tax = I("tax");
        $postcode = I("postcode");
        $email = I("email");
        $address = I("address");
        $remark = I("remark");
        $sampleStaQuan = I("sampleStaQuan");
        $collector = I("collector");
        $centreNo = I("centreNo");
        $testCost = I("testCost",0,'intval');
        $collectDate = I("collectDate");
        $reportDate = I("reportDate");
        $ifHighQuantity = I("ifHighQuantity");
        $package_remark = I("package_remark");

        //费用详情
        $Arecord = I("Arecord",0,'intval');
        $Brecord = I("Brecord",0,'intval');
        $Crecord = I("Crecord",0,'intval');
        $Drecord = I("Drecord",0,'intval');
        $Erecord = I("Erecord",0,'intval');
        $Frecord = I("Frecord",0,'intval');
        $G1record = I("G1record",0,'intval');
        $G2record = I("G2record",0,'intval');
        $Hrecord = I("Hrecord",0,'intval');

        $RArecord = I("RArecord",0,'intval');
        $RBrecord = I("RBrecord",0,'intval');
        $RCrecord = I("RCrecord",0,'intval');
        $RDrecord = I("RDrecord",0,'intval');
        $RErecord = I("RErecord",0,'intval');
        $RFrecord = I("RFrecord",0,'intval');
        $RG1record = I("RG1record",0,'intval');
        $RG2record = I("RG2record",0,'intval');
        $RHrecord = I("RHrecord",0,'intval');

        $A_id_list = I("A_id_list");
        $B_id_list = I("B_id_list");
        $C_id_list = I("C_id_list");
        $D_id_list = I("D_id_list");
        $E_id_list = I("E_id_list");
        $F_id_list = I("F_id_list");
        $G1_id_list = I("G1_id_list");
        $G2_id_list = I("G2_id_list");
        $H_id_list = I("H_id_list");

        $arr_id_list = array(
            'a'=>explode(",",$A_id_list),
            'b'=>explode(",",$B_id_list),
            'c'=>explode(",",$C_id_list),
            'd'=>explode(",",$D_id_list),
            'e'=>explode(",",$E_id_list),
            'f'=>explode(",",$F_id_list),
            'g1'=>explode(",",$G1_id_list),
            'g2'=>explode(",",$G2_id_list),
            'h'=>explode(",",$H_id_list),
        );
        //pr($arr_id_list);
        $idList = serialize($arr_id_list);

        $fee_remark = I("fee_remark");

        $Dcopy = I("Dcopy",0,'intval');
        $Donline = I("Donline",0,'intval');
        $Drevise = I("Drevise",0,'intval');
        $Dother = I("Dother",0,'intval');

        $ifspecial = I("ifspecial");//是否是特殊编码

        $rs = array("msg"=>'fail');

        if($testCost<=0){
            $rs['msg'] = '费用输入不正确!';
            $this->ajaxReturn($rs);
        }
        $admin_auth = session("admin_auth");
        $department = $admin_auth['department'];
        //ABCEF和G1，G2 三者之间生产单位和委托单位不能共享
        if($department == 'A' || $department == 'B' || $department == 'C' || $department == 'E' || $department == 'F'){
            $where_same='clientName="'.$clientName.'" and collector_partment in("G1","G2") and testCost!=0';
            $same_count = D("contract")->where($where_same)->count();
        }else if($department == 'G1'){
            $where_same='
            clientName="'.$clientName.'"';
            $where_same.=' and collector_partment in("A","B","C","E","F","G2") and testCost!=0';
            $same_count = D("contract")->where($where_same)->count();
        }else if($department == 'G2'){
            $where_same='clientName="'.$clientName.'"';
            $where_same.=' and collector_partment in("A","B","C","E","F","G1") and testCost!=0';
            $same_count = D("contract")->where($where_same)->count();
        }
        if($same_count>0){
            $rs['msg'] = '委托单位未通过查新';
            $this->ajaxReturn($rs);
        }
        //验证手机号
        if(!empty($telephone)){
            $isMob="/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/";  //手机
            $isTel="/^([0-9]|[-])+$/"; //电话
            //if(!(funcmtel($telephone) || funcphone($telephone))){
            if(!(preg_match($isTel,$telephone) || preg_match($isMob,$telephone))){
                $rs['msg'] = '请输入正确的联系方式';
                $this->ajaxReturn($rs);
            }
        }

        //验证传真
        if(!empty($tax)){
            $isPostcode="/^([0-9]|[-])+$/";
            if(!(preg_match($isPostcode,$tax))){
                $rs['msg'] = '请输入正确的传真';
                $this->ajaxReturn($rs);
            }
        }

        //验证邮政编码
        if(!empty($postcode)){
            $isPostcode="/^\d{6}$/";
            if(!(preg_match($isPostcode,$postcode))){
                $rs['msg'] = '请输入正确的邮政编码';
                $this->ajaxReturn($rs);
            }
        }

        //验证邮箱
        if(!empty($email)){
            $isEmail="/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/";
            if(!(preg_match($isEmail,$email))){
                $rs['msg'] = '请输入正确的邮箱';
                $this->ajaxReturn($rs);
            }
        }

        if(empty($clientName)) {
            $rs['msg'] = '委托单位不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($productUnit)) {
            $rs['msg'] = '生产单位不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleName)) {
            $rs['msg'] = '样品名称不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($testCriteria)) {
            $rs['msg'] = '检验依据不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($testItem)) {
            $rs['msg'] = '检验项目不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleQuantity)) {
            $rs['msg'] = '样品数量不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleStatus)) {
            $rs['msg'] = '样品状态不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleStaQuan)) {
            $rs['msg'] = '样品状态及数量不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($collector)) {
            $rs['msg'] = '收样人不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($testCost)) {
            $rs['msg'] = '检验费用不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($collectDate)) {
            $rs['msg'] = '收样日期不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($reportDate)) {
            $rs['msg'] = '报告日期不能为空!';
            $this->ajaxReturn($rs);
        }





        if(empty($productionDate)){
            $productionDate=null;
        }


        $admin_auth = session("admin_auth");
        $collector_partment=substr($centreNo,6,1);
        if($collector_partment=='G'){
            $code = substr($centreNo,8,3);
            if($code>500) $collector_partment='G2';
            else $collector_partment='G1';
        }
        $data = array(
            "clientName"=>$clientName,
            "productUnit"=>$productUnit,
            "sampleName"=>$sampleName,
            "sampleCode"=>$sampleCode,
            "grade"=>$grade,
            "specification"=>$specification,
            "trademark"=>$trademark,
            "productionDate"=>$productionDate,
            "sampleQuantity"=>$sampleQuantity,
            //"sampleunti"=>$sampleunti,
            "sampleStatus"=>$sampleStatus,
            "ration"=>$ration,
            "testCriteria"=>$testCriteria,
            "testItem"=>$testItem,
            "testCategory"=>$testCategory,
            "ifOnline"=>$ifOnline,
            "postMethod"=>$postMethod,
            "ifSubpackage"=>$ifSubpackage,
            "package_remark"=>$package_remark,
            "clientSign"=>$clientSign,
            "telephone"=>$telephone,
            "tax"=>$tax,
            "postcode"=>$postcode,
            "email"=>$email,
            "address"=>$address,
            "remark"=>$remark,
            "sampleStaQuan"=>$sampleStaQuan,
            "collector"=>$collector,
            "centreNo"=>$centreNo,
            "testCost"=>$testCost,
            "collectDate"=>$collectDate,
            "reportDate"=>$reportDate,
            "ifHighQuantity"=>$ifHighQuantity,
            'input_time'=>Date("Y-m-d H:i:s"),
            'collector_partment'=>$collector_partment,
            'ifedit'=>0
        );
        $de = substr($centreNo,6,1);

        $filler = $admin_auth['name'];
        //检验工作通知单入库
        $data_work = array(
            "centreNo"=>$centreNo,
            "sampleName"=>$sampleName,
            "testCreiteria"=>$testCriteria,
            "testItem"=>$testItem,
            'testDepartment'=>$de,
            "ration"=>$ration,
            'workDate'=>$collectDate,
            'finishDate'=>$reportDate,
            "sampleAuantity"=>$sampleQuantity,
            "sampleStatus"=>$sampleStatus,
            'otherComments'=>$remark,
            'filler'=>$filler,
            'fillDate'=>Date("Y-m-d H:i:s"),
            //"sampleunti"=>$sampleunti,
        );

        //费用表
        $date_cost =array(
            "centreNo"=>$centreNo,
            "Arecord"=>$Arecord,
            "Brecord"=>$Brecord,
            "Crecord"=>$Crecord,
            "Drecord"=>$Drecord,
            "Erecord"=>$Erecord,
            "Frecord"=>$Frecord,
            "G1record"=>$G1record,
            "G2record"=>$G2record,
            "Hrecord"=>$Hrecord,
            "RArecord"=>$RArecord,
            "RBrecord"=>$RBrecord,
            "RCrecord"=>$RCrecord,
            "RDrecord"=>$RDrecord,
            "RErecord"=>$RErecord,
            "RFrecord"=>$RFrecord,
            "RG1record"=>$RG1record,
            "RG2record"=>$RG2record,
            "RHrecord"=>$RHrecord,
            "Dcopy"=>$Dcopy,
            "Donline"=>$Donline,
            "Drevise"=>$Drevise,
            "Dother"=>$Dother,
            "remark"=>$fee_remark,
            'costDate'=>Date("Y-m-d H:i:s"),
            'idList'=>$idList
        );

        //$contract_user_id = $admin_auth['id'];
        //pr($contract_user_id);
        /*$data_flow = array(
            "centreNo"=>$centreNo,
            'contract_user_id'=>$contract_user_id,
            'contract_time'=>Date("Y-m-d H:i:s"),
        );*/

        $type = substr($centreNo,7,1);
        /*if($type=='W'){
            $data['ifedit']=1;
        }*/

        $ifchoseSp = I("ifchoseSp");
        //pr($ifchoseSp);

        if(D("contract")->where('centreNo="'.$centreNo.'"')->count()==0){

            M()->startTrans();
            try{

                //合同入库
                D("contract")->data($data)->add();


                //特殊编码操作
                if($ifspecial==1 && $ifchoseSp==1){
                    $year = substr($centreNo,0,4);
                    $month = substr($centreNo,4,2);
                    $department = substr($centreNo,6,1);
                    $where['year']=$year;
                    $where['month']=$month;
                    $where['department']=$department;
                    $specialItem = D("special_centre_code")->field('id,getNum,remainNum')->where($where)->find();
                    //pr($specialItem);
                    $num = (int)$specialItem['remainnum'];
                    //pr("num=".$num);
                    $special_id = $specialItem['id'];
                    //if($num==1){
                    //D("special_centre_code")->delete($special_id);
                    //}else{
                    $num = $num-1;
                    $editData['remainNum'] = $num;
                    D("special_centre_code")->where('id='.$special_id)->save($editData);
                    //pr(D("special_centre_code")->getLastSql());
                    //}
                }

                //费用入库
                if(!D("test_cost")->data($date_cost)->add()) $flag=false;

                //通知单入库
                if(!D("work_inform_form")->data($data_work)->add()) $flag=false;

                //抽样单入库

                if($type=='C'){
                    $data_sample = array(
                        "centreNo"=>$centreNo,
                        "productUnit"=>$clientName,
                        "sampleName"=>$sampleName,
                        "specification"=>$specification,
                        //缺产品批号
                        "testCriteria"=>$testCriteria,
                        "trademark"=>$trademark,
                        "sampleQuantity"=>$sampleQuantity,
                        //"sampleUnit"=>$sampleunti,
                        //"productionDate"=>$productionDate,
                        "testItem"=>$testItem,
                        "ifOnline"=>$ifOnline,
                        "ifSubpackage"=>$ifSubpackage,
                        "package_remark"=>$package_remark,
                    );
                    D("sampling_form")->data($data_sample)->add();
                }/*else{
					D("contract_flow")->data($data_flow)->add();
				}*/
                M()->commit();
                $rs['msg'] = 'succ';
            }catch(Exception $e){
                $rs['msg'] = '信息有误，录入不成功';
                M()->rollback();
            }
        }else{
            $rs['msg'] = '中心编号已用';
        }
        ////if($flag){
        //$rs['msg'] = 'succ';
        //M()->commit();
        //}else{
        //	$rs['msg'] = '信息有误，录入不成功';
        //M()->rollback();
        //}
        $this->ajaxReturn($rs);
    }

    //合同修改页面
    public function editContract(){
        $contreno = I('id');
        $type_status = I('type_status');
        $where['centreNo']=$contreno;
        //$testCategory = substr($contreno,7,1);
        $contractItem = D('contract')->where($where)->find();
        $feeItem = D('test_cost')->where($where)->find();
        $id_list = unserialize($feeItem['idlist']);
        $a_id_list = implode(",",$id_list['a']);
        $b_id_list = implode(",",$id_list['b']);
        $c_id_list = implode(",",$id_list['c']);
        $d_id_list = implode(",",$id_list['d']);
        $e_id_list = implode(",",$id_list['e']);
        $f_id_list = implode(",",$id_list['f']);
        $g1_id_list = implode(",",$id_list['g1']);
        $g2_id_list = implode(",",$id_list['g2']);
        $h_id_list = implode(",",$id_list['h']);
        $body = array(
            'contract'=>$contractItem,
            'feeItem'=>$feeItem,
            'type_status'=>$type_status,
            'a_id_list'=>$a_id_list,
            'b_id_list'=>$b_id_list,
            'c_id_list'=>$c_id_list,
            'd_id_list'=>$d_id_list,
            'e_id_list'=>$e_id_list,
            'f_id_list'=>$f_id_list,
            'g1_id_list'=>$g1_id_list,
            'g2_id_list'=>$g2_id_list,
            'h_id_list'=>$h_id_list,
            //'$testCategory'=>$testCategory
        );
        $this->assign($body);
        $this->display();
    }

    //合同申请修改
    function applyEditContract(){
        $contreno = I('id');
        $type_status = I('type_status');
        $where['centreNo']=$contreno;
        $contractItem = D('contract')->where($where)->find();
        $feeItem = D('test_cost')->where($where)->find();
        $id_list = unserialize($feeItem['idlist']);
        $a_id_list = implode(",",$id_list['a']);
        $b_id_list = implode(",",$id_list['b']);
        $c_id_list = implode(",",$id_list['c']);
        $d_id_list = implode(",",$id_list['d']);
        $e_id_list = implode(",",$id_list['e']);
        $f_id_list = implode(",",$id_list['f']);
        $g1_id_list = implode(",",$id_list['g1']);
        $g2_id_list = implode(",",$id_list['g2']);
        $h_id_list = implode(",",$id_list['h']);
        $body = array(
            'contract'=>$contractItem,
            'feeItem'=>$feeItem,
            'type_status'=>$type_status,
            'a_id_list'=>$a_id_list,
            'b_id_list'=>$b_id_list,
            'c_id_list'=>$c_id_list,
            'd_id_list'=>$d_id_list,
            'e_id_list'=>$e_id_list,
            'f_id_list'=>$f_id_list,
            'g1_id_list'=>$g1_id_list,
            'g2_id_list'=>$g2_id_list,
            'h_id_list'=>$h_id_list,
        );
        $this->assign($body);
        $this->display();
    }

    //合同申请修改
    public function doApplyContract(){
        $clientName = str_replace(' ','',I("clientName"));
        $productUnit = str_replace(' ','',I("productUnit"));
        $sampleName = I("sampleName");
        $sampleCode = I("sampleCode");
        $grade = I("grade");
        $specification = I("specification");
        $trademark = I("trademark");
        $productionDate = I("productionDate");
        $sampleQuantity = I("sampleQuantity");
        $sampleStatus = I("sampleStatus");
        $ration = I("ration");
        $testCriteria = I("testCriteria");
        $testItem = I("testItem");
        $postMethod = I("postMethod");
        $ifSubpackage = I("ifSubpackage");
        $package_remark = I("package_remark");
        $clientSign = I("clientSign");
        $telephone = I("telephone");
        $tax = I("tax");
        $postcode = I("postcode");
        $email = I("email");
        $address = I("address");
        $remark = I("remark");
        $sampleStaQuan = I("sampleStaQuan");
        $centreNo = I("centreNo");
        $testCost = I("testCost",0,'intval');
        $reportDate = I("reportDate");

        //查看是否在申请中
        $sub_status=M('report_feedback')->field('status')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreNo.'")')->find();
        if($sub_status && $sub_status['status']==0){
            $rs['msg']='exist';
            $this->ajaxReturn($rs);
        }
        //费用详情
        $Arecord = I("Arecord",0,'intval');
        $Brecord = I("Brecord",0,'intval');
        $Crecord = I("Crecord",0,'intval');
        $Drecord = I("Drecord",0,'intval');
        $Erecord = I("Erecord",0,'intval');
        $Frecord = I("Frecord",0,'intval');
        $G1record = I("G1record",0,'intval');
        $G2record = I("G2record",0,'intval');
        $Hrecord = I("Hrecord",0,'intval');

        $RArecord = I("RArecord",0,'intval');
        $RBrecord = I("RBrecord",0,'intval');
        $RCrecord = I("RCrecord",0,'intval');
        $RDrecord = I("RDrecord",0,'intval');
        $RErecord = I("RErecord",0,'intval');
        $RFrecord = I("RFrecord",0,'intval');
        $RG1record = I("RG1record",0,'intval');
        $RG2record = I("RG2record",0,'intval');
        $RHrecord = I("RHrecord",0,'intval');

        $A_id_list = I("A_id_list");
        $B_id_list = I("B_id_list");
        $C_id_list = I("C_id_list");
        $D_id_list = I("D_id_list");
        $E_id_list = I("E_id_list");
        $F_id_list = I("F_id_list");
        $G1_id_list = I("G1_id_list");
        $G2_id_list = I("G2_id_list");
        $H_id_list = I("H_id_list");
        $Dcopy = I("Dcopy",0,'intval');
        $Drevise = I("Drevise",0,'intval');
        $Dother = I("Dother",0,'intval');
        $fee_remark = I("fee_remark");

        //ABCEF和G1，G2 三者之间生产单位和委托单位不能共享
        $admin_auth = session("admin_auth");
        $department = $admin_auth['department'];
        if($department == 'A' || $department == 'B' || $department == 'C' || $department == 'E' || $department == 'F'){
            $where_same='clientName="'.$clientName.'" and collector_partment in("G1","G2") and testCost!=0';
            $same_count = D("contract")->where($where_same)->count();
        }else if($department == 'G1'){
            $where_same='clientName="'.$clientName.'"';
            $where_same.=' and collector_partment in("A","B","C","E","F","G2") and testCost!=0';
            $same_count = D("contract")->where($where_same)->count();
        }else if($department == 'G2'){
            $where_same='clientName="'.$clientName.'"';
            $where_same.=' and collector_partment in("A","B","C","E","F","G1") and testCost!=0';
            $same_count = D("contract")->where($where_same)->count();
        }
        if($same_count>0){
            $rs['msg'] = '委托单位未通过查新';
            $this->ajaxReturn($rs);
        }
        //验证费用
        if($testCost<=0){
            $rs['msg'] = '费用输入不正确!';
            $this->ajaxReturn($rs);
        }
        //验证手机号
        if(!empty($telephone)){
            $isMob="/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/";  //手机
            $isTel="/^([0-9]|[-])+$/"; //电话
            //if(!(funcmtel($telephone) || funcphone($telephone))){
            if(!(preg_match($isTel,$telephone) || preg_match($isMob,$telephone))){
                $rs['msg'] = '请输入正确的联系方式';
                $this->ajaxReturn($rs);
            }
        }

        //验证传真
        if(!empty($tax)){
            $isPostcode="/^([0-9]|[-])+$/";
            if(!(preg_match($isPostcode,$tax))){
                $rs['msg'] = '请输入正确的传真';
                $this->ajaxReturn($rs);
            }
        }

        //验证邮政编码
        if(!empty($postcode)){
            $isPostcode="/^\d{6}$/";
            if(!(preg_match($isPostcode,$postcode))){
                $rs['msg'] = '请输入正确的邮政编码';
                $this->ajaxReturn($rs);
            }
        }

        //验证邮箱
        if(!empty($email)){
            $isEmail="/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/";
            if(!(preg_match($isEmail,$email))){
                $rs['msg'] = '请输入正确的邮箱';
                $this->ajaxReturn($rs);
            }
        }

        if(empty($clientName)) {
            $rs['msg'] = '委托单位不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($productUnit)) {
            $rs['msg'] = '生产单位不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleName)) {
            $rs['msg'] = '样品名称不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($testCriteria)) {
            $rs['msg'] = '检验依据不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($testItem)) {
            $rs['msg'] = '检验项目不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleQuantity)) {
            $rs['msg'] = '样品数量不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleStatus)) {
            $rs['msg'] = '样品状态不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($sampleStaQuan)) {
            $rs['msg'] = '样品状态及数量不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($testCost)) {
            $rs['msg'] = '检验费用不能为空!';
            $this->ajaxReturn($rs);
        }
        if(empty($reportDate)) {
            $rs['msg'] = '报告日期不能为空!';
            $this->ajaxReturn($rs);
        }


        $contract=D("contract")->where("centreNo='".$centreNo."'")->find();
        $count=0;
        //合同单修改过的数据
        $data_temp = Array();
        if($contract['clientname']!=$clientName){
            $count=$count+1;
            $data_temp['clientName']=$clientName;
        }
        if($contract['productunit']!=$productUnit){
            $count=$count+1;
            $data_temp['productUnit']=$productUnit;
        }
        if($contract['samplename']!=$sampleName){
            $count=$count+1;
            $data_temp['sampleName']=$sampleName;
        }
        if($contract['samplecode']!=$sampleCode){
            $count=$count+1;
            if($sampleCode=='') $data_temp['sampleCode']='changeNULL';
            else $data_temp['sampleCode']=$sampleCode;
        }
        if($contract['grade']!=$grade){
            $count=$count+1;
            if($grade=='') $data_temp['grade']='changeNULL';
            else $data_temp['grade']=$grade;
        }
        if($contract['specification']!=$specification){
            $count=$count+1;
            if($specification=='') $data_temp['specification']='changeNULL';
            else $data_temp['specification']=$specification;
        }
        if($contract['trademark']!=$trademark){
            $count=$count+1;
            if($trademark=='') $data_temp['trademark']='changeNULL';
            else $data_temp['trademark']=$trademark;
        }
        if($contract['productiondate']!=$productionDate){
            $count=$count+1;
            if($productionDate=='') $data_temp['productionDate']='changeNULL';
            else $data_temp['productionDate']=$productionDate;
        }
        if($contract['samplequantity']!=$sampleQuantity){
            $count=$count+1;
            $data_temp['sampleQuantity']=$sampleQuantity;
        }
        if($contract['samplestatus']!=$sampleStatus){
            $count=$count+1;
            $data_temp['sampleStatus']=$sampleStatus;
        }
        if($contract['ration']!=$ration){
            $count=$count+1;
            if($ration=='') $data_temp['ration']='changeNULL';
            else $data_temp['ration']=$ration;
        }
        if($contract['testcriteria']!=$testCriteria){
            $count=$count+1;
            $data_temp['testCriteria']=$testCriteria;
        }
        if($contract['testitem']!=$testItem){
            $count=$count+1;
            $data_temp['testItem']=$testItem;
        }
        if($contract['postmethod']!=$postMethod){
            $count=$count+1;
            $data_temp['postMethod']=$postMethod;
        }
        if($contract['ifsubpackage']!=$ifSubpackage){
            $count=$count+1;
            $data_temp['ifSubpackage']=$ifSubpackage;
        }
        if($contract['package_remark']!=$package_remark){
            $count=$count+1;
            $data_temp['package_remark']=$package_remark;
        }
        if($contract['clientsign']!=$clientSign){
            $count=$count+1;
            $data_temp['clientSign']=$clientSign;
        }
        if($contract['telephone']!=$telephone){
            $count=$count+1;
            if($telephone=='') $data_temp['telephone']='changeNULL';
            else $data_temp['telephone']=$telephone;
        }
        if($contract['tax']!=$tax){
            $count=$count+1;
            if($tax=='') $data_temp['tax']='changeNULL';
            else $data_temp['tax']=$tax;
        }
        if($contract['postcode']!=$postcode){
            $count=$count+1;
            if($postcode=='') $data_temp['postcode']='changeNULL';
            else $data_temp['postcode']=$postcode;
        }
        if($contract['email']!=$email){
            $count=$count+1;
            if($email=='') $data_temp['email']='changeNULL';
            else $data_temp['email']=$email;
        }
        if($contract['address']!=$address){
            $count=$count+1;
            if($address=='') $data_temp['address']='changeNULL';
            else $data_temp['address']=$address;
        }
        if($contract['remark']!=$remark){
            $count=$count+1;
            if($remark=='') $data_temp['remark']='changeNULL';
            else $data_temp['remark']=$remark;
        }
        if($contract['samplestaquan']!=$sampleStaQuan){
            $count=$count+1;
            $data_temp['sampleStaQuan']=$sampleStaQuan;
        }

        if($contract['reportdate']!=$reportDate){
            $count=$count+1;
            $data_temp['reportDate']=$reportDate;
        }
        $contract_cost = D("test_cost")->where("centreNo='".$centreNo."'")->find();
        //费用临时表
        $cost_temp = Array();
        $flag=0;
        if($contract_cost['arecord']!=$Arecord){
            $cost_temp['Arecord']=$Arecord;
        }
        if($contract_cost['brecord']!=$Brecord){
            $cost_temp['Brecord']=$Brecord;
        }
        if($contract_cost['crecord']!=$Crecord){
            $cost_temp['Crecord']=$Crecord;
        }
        if($contract_cost['drecord']!=$Drecord){
            $cost_temp['Drecord']=$Drecord;
        }
        if($contract_cost['erecord']!=$Erecord){
            $cost_temp['Erecord']=$Erecord;
        }
        if($contract_cost['frecord']!=$Frecord){
            $cost_temp['Frecord']=$Frecord;
        }
        if($contract_cost['g1record']!=$G1record){
            $cost_temp['G1record']=$G1record;
        }
        if($contract_cost['g2record']!=$G2record){
            $cost_temp['G2record']=$G2record;
        }
        if($contract_cost['hrecord']!=$Hrecord){
            $cost_temp['Hrecord']=$Hrecord;
        }
        if($contract_cost['rarecord']!=$RArecord){
            $cost_temp['RArecord']=$RArecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rbrecord']!=$RBrecord){
            $cost_temp['RBrecord']=$RBrecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rcrecord']!=$RCrecord){
            $cost_temp['RCrecord']=$RCrecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rdrecord']!=$RDrecord){
            $cost_temp['RDrecord']=$RDrecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rerecord']!=$RErecord){
            $cost_temp['RErecord']=$RErecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rfrecord']!=$RFrecord){
            $cost_temp['RFrecord']=$RFrecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rg1record']!=$RG1record){
            $cost_temp['RG1record']=$RG1record;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rg2record']!=$RG2record){
            $cost_temp['RG2record']=$RG2record;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['rhrecord']!=$RHrecord){
            $cost_temp['RHrecord']=$RHrecord;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['dcopy']!=$Dcopy){
            $cost_temp['Dcopy']=$Dcopy;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['drevise']!=$Drevise){
            $cost_temp['Drevise']=$Drevise;
            $count=$count+1;
            $flag=1;
        }
        if($contract_cost['dother']!=$Dother){
            $cost_temp['Dother']=$Dother;
            $count=$count+1;
            $flag=1;
        }
        if($flag==1){
            //$count=$count+1;
            $data_temp['testCost']=$testCost;
        }
        if($contract_cost['remark']!=$fee_remark){
            if($fee_remark=='') $cost_temp['remark']='changeNULL';
            else $cost_temp['remark']=$fee_remark;
            $count=$count+1;
        }
        $idList = unserialize($contract_cost['idlist']);
        //费用参考价格是否修改
        $flag=0;
        if(implode(",",$idList['a'])!=$A_id_list){
            $idList['a']=explode(",",$A_id_list);
            $count=$count+1;
            $flag=1;
        }

        if(implode(",",$idList['b'])!=$B_id_list){
            $idList['b']=explode(",",$B_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['c'])!=$C_id_list){
            $idList['c']=explode(",",$C_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['d'])!=$D_id_list){
            $idList['d']=explode(",",$D_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['e'])!=$E_id_list){
            $idList['e']=explode(",",$E_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['f'])!=$F_id_list){
            $idList['f']=explode(",",$F_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['g1'])!=$G1_id_list){
            $idList['g1']=explode(",",$G1_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['g2'])!=$G2_id_list){
            $idList['g2']=explode(",",$G2_id_list);
            $count=$count+1;
            $flag=1;
        }
        if(implode(",",$idList['h'])!=$H_id_list){
            $idList['h']=explode(",",$H_id_list);
            $count=$count+1;
            $flag=1;
        }
        if($flag==1){
            $cost_temp['idList']=serialize($idList);
        }
        $data_temp['centreNo']=$centreNo;
        $cost_temp['centreNo']=$centreNo;
        if($count==0){
            $rs['msg'] = "none";
            $this->ajaxReturn($rs);
        }
        if($count>0) {
            $feedback = Array(
                'centreNo' => $centreNo,
                'create_time' => Date("Y-m-d H:i:s"),
            );
            if ($count == 1) {
                $feedback['role_id'] = 17;//修改一项为修改审核-1项
            }
            M()->startTrans();
            try {
                D("contract_temp")->add($data_temp);
                D("test_cost_temp")->add($cost_temp);
                D("report_feedback")->add($feedback);
                M()->commit();
                $rs['msg'] = 'succ';
            } catch (Exception $e) {
                $rs['msg'] = 'fail';
                M()->rollback();
            }
        }
        $this->ajaxReturn($rs);
    }
    //合同修改入库
    public function doEditContract(){
        //是否为外部签发后的修改
        $type_status = I('type_status');
        if($type_status==6){
            $centreNo = I("centreNo");
            $testCost = I("testCost",0,'intval');
            $Dcopy = I("Dcopy",0,'intval');
            $Drevise = I("Drevise",0,'intval');
            $Dother = I("Dother",0,'intval');
            $fee_remark = I("fee_remark");

            $data = array(
                "testCost"=>$testCost,
            );
            //费用表
            $date_cost =array(
                "Dcopy"=>$Dcopy,
                "Drevise"=>$Drevise,
                "Dother"=>$Dother,
                "remark"=>$fee_remark,
            );
            if($testCost<=0){
                $rs['msg'] = '费用输入不正确!';
                $this->ajaxReturn($rs);
            }
            M()->startTrans();
            try{
                $where['centreNo']=$centreNo;
                //合同入库
                D("contract")->where($where)->save($data);

                //费用入库
                D("test_cost")->where($where)->save($date_cost);

                M()->commit();
                $rs['msg'] = 'succ';
            }catch(Exception $e){
                $rs['msg'] = '信息有误，修改不成功';
                M()->rollback();
            }
        }else{
            $clientName = str_replace(' ','',I("clientName"));
            $productUnit = str_replace(' ','',I("productUnit"));
            $sampleName = I("sampleName");
            $sampleCode = I("sampleCode");
            $grade = I("grade");
            $specification = I("specification");
            $trademark = I("trademark");
            $productionDate = I("productionDate");
            $sampleQuantity = I("sampleQuantity");
            //$sampleunti = I("sampleunti");
            $sampleStatus = I("sampleStatus");
            $ration = I("ration");
            $testCriteria = I("testCriteria");
            $testItem = I("testItem");
            //$testCategory = I("testCategory");
            $ifOnline = I("ifOnline");
            $postMethod = I("postMethod");
            $ifSubpackage = I("ifSubpackage");
            $package_remark = I("package_remark");
            $clientSign = I("clientSign");
            $telephone = I("telephone");
            $tax = I("tax");
            $postcode = I("postcode");
            $email = I("email");
            $address = I("address");
            $remark = I("remark");
            $sampleStaQuan = I("sampleStaQuan");
            $collector = I("collector");
            $centreNo = I("centreNo");
            $testCost = I("testCost",0,'intval');
            $collectDate = I("collectDate");
            $reportDate = I("reportDate");
            $ifHighQuantity = I("ifHighQuantity");

            //费用详情
            $Arecord = I("Arecord",0,'intval');
            $Brecord = I("Brecord",0,'intval');
            $Crecord = I("Crecord",0,'intval');
            $Drecord = I("Drecord",0,'intval');
            $Erecord = I("Erecord",0,'intval');
            $Frecord = I("Frecord",0,'intval');
            $G1record = I("G1record",0,'intval');
            $G2record = I("G2record",0,'intval');
            $Hrecord = I("Hrecord",0,'intval');

            $RArecord = I("RArecord",0,'intval');
            $RBrecord = I("RBrecord",0,'intval');
            $RCrecord = I("RCrecord",0,'intval');
            $RDrecord = I("RDrecord",0,'intval');
            $RErecord = I("RErecord",0,'intval');
            $RFrecord = I("RFrecord",0,'intval');
            $RG1record = I("RG1record",0,'intval');
            $RG2record = I("RG2record",0,'intval');
            $RHrecord = I("RHrecord",0,'intval');

            $A_id_list = I("A_id_list");
            $B_id_list = I("B_id_list");
            $C_id_list = I("C_id_list");
            $D_id_list = I("D_id_list");
            $E_id_list = I("E_id_list");
            $F_id_list = I("F_id_list");
            $G1_id_list = I("G1_id_list");
            $G2_id_list = I("G2_id_list");
            $H_id_list = I("H_id_list");

            $arr_id_list = array(
                'a'=>explode(",",$A_id_list),
                'b'=>explode(",",$B_id_list),
                'c'=>explode(",",$C_id_list),
                'd'=>explode(",",$D_id_list),
                'e'=>explode(",",$E_id_list),
                'f'=>explode(",",$F_id_list),
                'g1'=>explode(",",$G1_id_list),
                'g2'=>explode(",",$G2_id_list),
                'h'=>explode(",",$H_id_list),
            );
            //pr($arr_id_list);
            $idList = serialize($arr_id_list);

            $Dcopy = I("Dcopy",0,'intval');
            $Donline = I("Donline",0,'intval');
            $Drevise = I("Drevise",0,'intval');
            $Dother = I("Dother",0,'intval');
            $fee_remark = I("fee_remark");

            //ABCEF和G1，G2 三者之间生产单位和委托单位不能共享
            $admin_auth = session("admin_auth");
            $department = $admin_auth['department'];
            if($department == 'A' || $department == 'B' || $department == 'C' || $department == 'E' || $department == 'F'){
                $where_same='clientName="'.$clientName.'" and collector_partment in("G1","G2") and testCost!=0';
                $same_count = D("contract")->where($where_same)->count();
            }else if($department == 'G1'){
                $where_same='clientName="'.$clientName.'"';
                $where_same.=' and collector_partment in("A","B","C","E","F","G2") and testCost!=0';
                $same_count = D("contract")->where($where_same)->count();
            }else if($department == 'G2'){
                $where_same='clientName="'.$clientName.'"';
                $where_same.=' and collector_partment in("A","B","C","E","F","G1") and testCost!=0';
                $same_count = D("contract")->where($where_same)->count();
            }
            if($same_count>0){
                $rs['msg'] = '委托单位未通过查新';
                $this->ajaxReturn($rs);
            }


            if($testCost<=0){
                $rs['msg'] = '费用输入不正确!';
                $this->ajaxReturn($rs);
            }
            //验证手机号
            if(!empty($telephone)){
                $isMob="/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/";  //手机
                $isTel="/^([0-9]|[-])+$/"; //电话
                //if(!(funcmtel($telephone) || funcphone($telephone))){
                if(!(preg_match($isTel,$telephone) || preg_match($isMob,$telephone))){
                    $rs['msg'] = '请输入正确的联系方式';
                    $this->ajaxReturn($rs);
                }
            }

            //验证传真
            if(!empty($tax)){
                $isPostcode="/^([0-9]|[-])+$/";
                if(!(preg_match($isPostcode,$tax))){
                    $rs['msg'] = '请输入正确的传真';
                    $this->ajaxReturn($rs);
                }
            }

            //验证邮政编码
            if(!empty($postcode)){
                $isPostcode="/^\d{6}$/";
                if(!(preg_match($isPostcode,$postcode))){
                    $rs['msg'] = '请输入正确的邮政编码';
                    $this->ajaxReturn($rs);
                }
            }

            //验证邮箱
            if(!empty($email)){
                $isEmail="/^\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*$/";
                if(!(preg_match($isEmail,$email))){
                    $rs['msg'] = '请输入正确的邮箱';
                    $this->ajaxReturn($rs);
                }
            }


            if(empty($clientName)) {
                $rs['msg'] = '委托单位不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($productUnit)) {
                $rs['msg'] = '生产单位不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($sampleName)) {
                $rs['msg'] = '样品名称不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($testCriteria)) {
                $rs['msg'] = '检验依据不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($testItem)) {
                $rs['msg'] = '检验项目不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($sampleQuantity)) {
                $rs['msg'] = '样品数量不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($sampleStatus)) {
                $rs['msg'] = '样品状态不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($sampleStaQuan)) {
                $rs['msg'] = '样品状态及数量不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($collector)) {
                $rs['msg'] = '收样人不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($testCost)) {
                $rs['msg'] = '检验费用不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($collectDate)) {
                $rs['msg'] = '收样日期不能为空!';
                $this->ajaxReturn($rs);
            }
            if(empty($reportDate)) {
                $rs['msg'] = '报告日期不能为空!';
                $this->ajaxReturn($rs);
            }

            $data = array(
                "clientName"=>$clientName,
                "productUnit"=>$productUnit,
                "sampleName"=>$sampleName,
                "sampleCode"=>$sampleCode,
                "grade"=>$grade,
                "specification"=>$specification,
                "trademark"=>$trademark,
                "productionDate"=>$productionDate,
                "sampleQuantity"=>$sampleQuantity,
                //"sampleunti"=>$sampleunti,
                "sampleStatus"=>$sampleStatus,
                "ration"=>$ration,
                "testCriteria"=>$testCriteria,
                "testItem"=>$testItem,
                //"testCategory"=>$testCategory,
                "ifOnline"=>$ifOnline,
                "postMethod"=>$postMethod,
                "ifSubpackage"=>$ifSubpackage,
                "package_remark"=>$package_remark,
                "clientSign"=>$clientSign,
                "telephone"=>$telephone,
                "tax"=>$tax,
                "postcode"=>$postcode,
                "email"=>$email,
                "address"=>$address,
                "remark"=>$remark,
                "sampleStaQuan"=>$sampleStaQuan,
                "collector"=>$collector,
                "centreNo"=>$centreNo,
                "testCost"=>$testCost,
                "collectDate"=>$collectDate,
                "reportDate"=>$reportDate,
                "ifHighQuantity"=>$ifHighQuantity
            );
            $de = substr($centreNo,6,1);
            $admin_auth = session("admin_auth");
            $filler = $admin_auth['name'];
            //检验工作通知单入库
            $data_work = array(
                "centreNo"=>$centreNo,
                "sampleName"=>$sampleName,
                "testCreiteria"=>$testCriteria,
                "testItem"=>$testItem,
                'testDepartment'=>$de,
                "ration"=>$ration,
                'workDate'=>$collectDate,
                'finishDate'=>$reportDate,
                "sampleAuantity"=>$sampleQuantity,
                "sampleStatus"=>$sampleStatus,
                'otherComments'=>$remark,
                'filler'=>$filler,
                'fillDate'=>Date("Y-m-d H:i:s"),
                //"sampleunti"=>$sampleunti,
            );

            //费用表
            $date_cost =array(
                "centreNo"=>$centreNo,
                "Arecord"=>$Arecord,
                "Brecord"=>$Brecord,
                "Crecord"=>$Crecord,
                "Drecord"=>$Drecord,
                "Erecord"=>$Erecord,
                "Frecord"=>$Frecord,
                "G1record"=>$G1record,
                "G2record"=>$G2record,
                "Hrecord"=>$Hrecord,
                "RArecord"=>$RArecord,
                "RBrecord"=>$RBrecord,
                "RCrecord"=>$RCrecord,
                "RDrecord"=>$RDrecord,
                "RErecord"=>$RErecord,
                "RFrecord"=>$RFrecord,
                "RG1record"=>$RG1record,
                "RG2record"=>$RG2record,
                "RHrecord"=>$RHrecord,
                "Dcopy"=>$Dcopy,
                "Donline"=>$Donline,
                "Drevise"=>$Drevise,
                "Dother"=>$Dother,
                "remark"=>$fee_remark,
                'costDate'=>Date("Y-m-d H:i:s"),
                'idList'=>$idList
            );
            M()->startTrans();
            try{
                $where['centreNo']=$centreNo;
                //合同入库
                D("contract")->where($where)->save($data);

                //费用入库
                D("test_cost")->where($where)->save($date_cost);

                //通知单入库
                D("work_inform_form")->where($where)->save($data_work);

                //抽样单入库
                $type = substr($centreNo,7,1);
                if($type=='C'){
                    $data_sample = array(
                        "centreNo"=>$centreNo,
                        "productUnit"=>$clientName,
                        "sampleName"=>$sampleName,
                        "specification"=>$specification,
                        //缺产品批号
                        "testCriteria"=>$testCriteria,
                        "trademark"=>$trademark,
                        "sampleQuantity"=>$sampleQuantity,
                        //"sampleUnit"=>$sampleunti,
                        //"productionDate"=>$productionDate,
                        "testItem"=>$testItem,
                        "ifOnline"=>$ifOnline,
                        "ifSubpackage"=>$ifSubpackage,
                        "package_remark"=>$package_remark
                    );
                    D("sampling_form")->where($where)->save($data_sample);
                }

                M()->commit();
                $rs['msg'] = 'succ';
            }catch(Exception $e){
                $rs['msg'] = '信息有误，修改不成功';
                M()->rollback();
            }
        }
        $this->ajaxReturn($rs);
    }

    //申请修改完毕
    public function doUpdateEditState(){
        $centreno = I('centreno');
        //发送到状态
        $type_status = I('type_status');

        //原始状态
        $edit_status = I('edit_status');

        $ifback = I('ifback');

        $rs = array('msg'=>'fail');
        $where['centreNo']=$centreno;
        $data_apply = array(
            'isaudit'=>0,
            'internalpass'=>0,
            'status'=>$type_status,
            'ifback'=>0,
            //'back_time'=>null
        );
        if($type_status==4){
            $data_apply['isaudit']=1;
        }

        //外部签发后，申请修改
        if($edit_status == 6){
            //$data_apply['status']=7;
            $contract = D("contract")->where($where)->find();
            $data_contract = array();
            //pr($contract);
            if(empty($contract['centreno1'])){
                $centreNoNew = $centreno."G1";
                $data_contract['centreNo1']=$centreNoNew;
            }else if(empty($contract['centreno2'])){
                $centreNoNew = $centreno."G2";
                $data_contract['centreNo2']=$centreNoNew;
            }else{
                $centreNoNew = $centreno."G3";
                $data_contract['centreNo3']=$centreNoNew;
            }
            D('contract')->where($where)->save($data_contract);

            //修改反馈表
            $data_feedback = array(
                "status"=>3
            );
            D("report_feedback")
                ->where('id = (SELECT a.id from (SELECT max(id) as id from report_feedback WHERE centreNo = "'.$centreno.'") a )')
                ->save($data_feedback);
        }
        D("contract_flow")->where($where)->save($data_apply);

        $rs['msg']='修改提交成功';
        $this->ajaxReturn($rs);
    }

    //申请修改完毕-申请
    public function doUpdateApplyState(){
        $centreno = I('centreno');
        $type_status = I('type_status');

        $rs = array('msg'=>'fail');
        $where['centreNo']=$centreno;




        $data_feedback = array(
            "status"=>3
        );
        D("report_feedback")
            ->where('id = (SELECT a.id from (SELECT max(id) as id from report_feedback WHERE centreNo = "'.$centreno.'") a )')
            ->save($data_feedback);

        $rs['msg']='修改提交成功';
        $this->ajaxReturn($rs);
    }

    //生成检验工作单
    public function checkWorkList(){
        $this->display();
    }

    //跳转抽样单修改页面
    public function sampleEdit(){
        $centreno = I("id");
        $where['centreNo']=$centreno;
        $result=M('sampling_form')->where($where)->find();

        $ifedit=M('contract')->where($where)->find();
        //SELECT * from report_feedback where id = (SELECT max(id) from report_feedback WHERE centreNo='201711AC158')
        $sub_status=M('report_feedback')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreno.'")')->find();
        if(empty($sub_status)){
            $sub_status['status']=-1;
        }
        //判断角色，确定是否可以修改
        $admin_auth = session("admin_auth");
        $if_admin = $admin_auth['super_admin'];
        $roleid = $admin_auth['gid'];

        //$role = D('common_role')->where('id='.$roleid)->find();
        //if($role['rolename']=="领导" || $if_admin==1 || $role['rolename']=="前台人员"){
        if($roleid==8 || $if_admin==1 || $roleid==7){
            $if_edit = 1;
        }else{
            $if_edit = 0;
        }

        //判断是否已经提交完毕，目的判断是否出现抽样单录入完毕按钮
        if(empty($result['samplebase']) && empty($result['sampledate']) && empty($result['productiondate']) && empty($result['sampleplace']) && empty($result['samplemethod']) && empty($result['simplersign']) && empty($result['simsigndate']) && empty($result['sealersign']) && empty($result['seasingdate']) && empty($result['enterprisesign']) && empty($result['entsigndate'])){
            $if_save = 0;
        }else{
            $if_save = 1;
        }

        //判断是否上传现成照片，目的判断是否出现抽样单录入完毕按钮
        $where_p['centreNo']=$centreno;
        $where_p['type']=1;
        $if_picture = M('sample_picture')->where($where_p)->count();

        //判断是否所有抽样信息录入完毕，目的判断是否出现打印按钮
        //$if_submit=M('contract_flow')->where($where)->count();




        $simsigndateyear = $result['simsigndate'] ? date("Y",strtotime($result['simsigndate'])):"";
        $simsigndatemonth =  $result['simsigndate'] ? date("m",strtotime($result['simsigndate'])):"";
        $simsigndateday =  $result['simsigndate'] ? date("d",strtotime($result['simsigndate'])):"";
        array_push($result,$simsigndateyear);
        array_push($result,$simsigndatemonth);
        array_push($result,$simsigndateday);

        $seasingdateyear =  $result['seasingdate'] ? date("Y",strtotime($result['seasingdate'])):"";
        $seasingdatemonth =  $result['seasingdate'] ? date("m",strtotime($result['seasingdate'])):"";
        $seasingdateday =  $result['seasingdate'] ? date("d",strtotime($result['seasingdate'])):"";
        array_push($result,$seasingdateyear);
        array_push($result,$seasingdatemonth);
        array_push($result,$seasingdateday);

        $entsigndateyear =  $result['entsigndate'] ? date("Y",strtotime($result['entsigndate'])):"";
        $entsigndatemonth =  $result['entsigndate'] ? date("m",strtotime($result['entsigndate'])):"";
        $entsigndateday =  $result['entsigndate'] ? date("d",strtotime($result['entsigndate'])):"";
        array_push($result,$entsigndateyear);
        array_push($result,$entsigndatemonth);
        array_push($result,$entsigndateday);

        //查看是否已录入完毕
        $sample_count = D('contract_flow')->where($where)->count();

        $body=array(
            'one'=>$result,
            'if_edit'=>$if_edit,
            'sample_count'=>$sample_count,
            //'status'=>$status,
            'ifedit'=>$ifedit,
            'sub_status'=>$sub_status,
            'if_save'=>$if_save,
            'if_picture'=>$if_picture,
            //'if_submit'=>$if_submit,
        );
        $this->assign($body);
        $this->display();
    }

    //抽样单修改
    public function doEditSample(){
        $centreno = I("centreno");
        $samplebase=I("samplebase");
        $sampledate=I("sampledate");
        $sampleplace=I("sampleplace");
        $samplemethod=I("samplemethod");
        $productiondate=I("productiondate");
        $batchno=I("batchno");
        $simplerSign=I("simplerSign");
        $simSignDate=I("simSignDate");
        //$simplerYear=I("simplerYear");
        //$simplerMonth=I("simplerMonth");
        //$simplerDay=I("simplerDay");
        //$simSignDate = $simplerYear."-".$simplerMonth."-".$simplerDay;
        $sealerSign=I("sealerSign");
        $seaSingDate=I("seaSingDate");
        //$sealerYear=I("sealerYear");
        //$sealerMonth=I("sealerMonth");
        //$sealerDay=I("sealerDay");
        //$seaSingDate = $sealerYear."-".$sealerMonth."-".$sealerDay;
        $enterpriseSign=I("enterpriseSign");
        $entSignDate=I("entSignDate");
        //$enterpriseYear=I("enterpriseYear");
        //$enterpriseMonth=I("enterpriseMonth");
        //$enterpriseDay=I("enterpriseDay");
        //$entSignDate = $enterpriseYear."-".$enterpriseMonth."-".$enterpriseDay;
        $telephone=I("telephone");
        $tax=I("tax");
        $address=I("address");
        $data=array(
            'sampleBase'=>$samplebase,
            'sampleDate'=>$sampledate,
            'samplePlace'=>$sampleplace,
            'sampleMethod'=>$samplemethod,
            'productionDate'=>$productiondate,
            'batchNo'=>$batchno,
            'simplerSign'=>$simplerSign,
            'simSignDate'=>$simSignDate,
            'sealerSign'=>$sealerSign,
            'seaSingDate'=>$seaSingDate,
            'enterpriseSign'=>$enterpriseSign,
            'entSignDate'=>$entSignDate,
            'telephone'=>$telephone,
            'tax'=>$tax,
            'address'=>$address
        );
        //验证手机号
        if(!empty($telephone)){
            $isMob="/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/";  //手机
            $isTel="/^([0-9]|[-])+$/"; //电话
            //if(!(funcmtel($telephone) || funcphone($telephone))){
            if(!(preg_match($isTel,$telephone) || preg_match($isMob,$telephone))){
                $rs['msg'] = '请输入正确的联系方式';
                $this->ajaxReturn($rs);
            }
        }

        //验证传真
        if(!empty($tax)){
            $isPostcode="/^([0-9]|[-])+$/";
            if(!(preg_match($isPostcode,$tax))){
                $rs['msg'] = '请输入正确的传真';
                $this->ajaxReturn($rs);
            }
        }
        //pr($data);
        $where['centreNo']=$centreno;
        //pr($centreno);
        $rs['msg']='fail';
        M()->startTrans();
        if(D('sampling_form')->where($where)->save($data)){
            M()->commit();
            $rs['msg']='succ';
        }else{
            M()->rollback();
            $rs['msg']='数据没有修改！';
        }
        $this->ajaxReturn($rs);
    }

    //抽样单申请修改
    public function doApplyEditSample(){
        $centreno = I("centreno");
        $samplebase=I("samplebase");
        $sampledate=I("sampledate");
        $sampleplace=I("sampleplace");
        $samplemethod=I("samplemethod");
        $productiondate=I("productiondate");
        $batchno=I("batchno");
        $simplerSign=I("simplerSign");
        $simSignDate=I("simSignDate");
        $sealerSign=I("sealerSign");
        $seaSingDate=I("seaSingDate");
        $enterpriseSign=I("enterpriseSign");
        $entSignDate=I("entSignDate");
        $telephone=I("telephone");
        $tax=I("tax");
        $address=I("address");

        //验证手机号
        if(!empty($telephone)){
            $isMob="/^(1(([35][0-9])|(47)|[8][0126789]))\d{8}$/";  //手机
            $isTel="/^([0-9]|[-])+$/"; //电话
            //if(!(funcmtel($telephone) || funcphone($telephone))){
            if(!(preg_match($isTel,$telephone) || preg_match($isMob,$telephone))){
                $rs['msg'] = '请输入正确的联系方式';
                $this->ajaxReturn($rs);
            }
        }

        //验证传真
        if(!empty($tax)){
            $isPostcode="/^([0-9]|[-])+$/";
            if(!(preg_match($isPostcode,$tax))){
                $rs['msg'] = '请输入正确的传真';
                $this->ajaxReturn($rs);
            }
        }

        //查看是否在申请中
        $sub_status=M('report_feedback')->field('status')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreno.'")')->find();
        if($sub_status && $sub_status['status']==0){
            $rs['msg']='exist';
            $this->ajaxReturn($rs);
        }

        $sample=D("sampling_form")->where('centreNo="'.$centreno.'"')->find();
        $count=0;
        if($sample['samplebase']!=$samplebase){
            $data['sampleBase']=$samplebase;
            $count=$count+1;
        }
        if($sample['sampledate']!=$sampledate){
            $data['sampleDate']=$sampledate;
            $count=$count+1;
        }
        if($sample['sampleplace']!=$sampleplace){
            $data['samplePlace']=$sampleplace;
            $count=$count+1;
        }
        if($sample['samplemethod']!=$samplemethod){
            $data['sampleMethod']=$samplemethod;
            $count=$count+1;
        }
        if($sample['productiondate']!=$productiondate){
            $data['productionDate']=$productiondate;
            $count=$count+1;
        }
        if($sample['batchno']!=$batchno){
            if($batchno=='') {
                $data['batchNo']='changeNULL';
            }else{
                $data['batchNo']=$batchno;
            }

            $count=$count+1;
        }
        if($sample['simplersign']!=$simplerSign){
            $data['simplerSign']=$simplerSign;
            $count=$count+1;
        }
        if($sample['simsigndate']!=$simSignDate){
            $data['simSignDate']=$simSignDate;
            $count=$count+1;
        }
        if($sample['sealersign']!=$sealerSign){
            $data['sealerSign']=$sealerSign;
            $count=$count+1;
        }
        if($sample['seasingdate']!=$seaSingDate){
            $data['seaSingDate']=$seaSingDate;
            $count=$count+1;
        }
        if($sample['enterprisesign']!=$enterpriseSign){
            $data['enterpriseSign']=$enterpriseSign;
            $count=$count+1;
        }
        if($sample['entsigndate']!=$entSignDate){
            $data['entSignDate']=$entSignDate;
            $count=$count+1;
        }
        if($sample['telephone']!=$telephone){
            if($batchno=='') {
                $data['telephone']='changeNULL';
            }else{
                $data['telephone']=$telephone;
            }
            $count=$count+1;
        }
        if($sample['tax']!=$tax){
            if($batchno=='') {
                $data['tax']='changeNULL';
            }else{
                $data['tax']=$tax;
            }
            $count=$count+1;
        }
        if($sample['address']!=$address){
            if($batchno=='') {
                $data['address']='changeNULL';
            }else{
                $data['address']=$address;
            }
            $count=$count+1;
        }
        $data['centreNo']=$centreno;
        $where['centreNo']=$centreno;
        if($count==0){
            $rs['msg'] = "none";
            $this->ajaxReturn($rs);
        }
        if($count>0) {
            $feedback = Array(
                'centreNo' => $centreno,
                'if_sample' => 1,
                'create_time' => Date("Y-m-d H:i:s"),
            );
            if ($count == 1) {
                $feedback['role_id'] = 17;//修改一项为修改审核-1项
            }
            M()->startTrans();
            try {
                D("sampling_form_temp")->add($data);
                D("report_feedback")->add($feedback);
                M()->commit();
                $rs['msg'] = '申请成功';
            } catch (Exception $e) {
                $rs['msg'] = '申请失败，请重试！';
                M()->rollback();
            }
        }
        $this->ajaxReturn($rs);
    }

    //抽样单上传
    public function doUploadSampleImage(){
        $centreno = I('centreno');
        $type = I('type');

        $where['centreNo']=$centreno;
        //$result=M('sampling_form')->where($where)->find();
        //$status=M('contract_flow')->where($where)->find();
        $ifedit=M('contract')->where($where)->find();
        //$sub_status=M('report_feedback')->where($where)->find();

        $centreno_str = implode(',',$con_list);
        $sub_status = D('report_feedback')->where(' id in (select max(id) from report_feedback where if_report=0 and centreNo = "'.$centreno.'" group by centreNo)')->group('centreNo')->find();

        if(empty($sub_status)){
            $sub_status['status']=-1;
        }

        if($type=='sample'){
            $list = D('sample_picture')->where('type=0 and centreno="'.$centreno.'"')->select();
        }else{
            $list = D('sample_picture')->where('type=1 and centreno="'.$centreno.'"')->select();
        }
        $body=array(
            'centreno'=>$centreno,
            'type'=>$type,
            'list'=>$list,
            'ifedit'=>$ifedit,
            'sub_status'=>$sub_status,
            'type'=>$type,
        );
        //pr($sub_status);
        $this->assign($body);
        $this->display();
    }

    //检验是否录入完毕
    public function checkFinish(){
        $centreno = I('centreno');
        $where['centreNo']=$centreno;
        $if_finish = 1;
        $picture_count=0;
        $if_sample = substr($centreno,7,1);
        if($if_sample=='C'){
            $picture_count = D('sample_picture')->where('type=1 and centreno="'.$centreno.'"')->count();
            if($picture_count==0) $if_finish = 0;

            //判断是否已经提交完毕，目的判断是否出现抽样单录入完毕按钮
            $result=M('sampling_form')->where($where)->find();
            if(empty($result['samplebase']) && empty($result['sampledate']) && empty($result['productiondate']) && empty($result['sampleplace']) && empty($result['samplemethod']) && empty($result['simplersign']) && empty($result['simsigndate']) && empty($result['sealersign']) && empty($result['seasingdate']) && empty($result['enterprisesign']) && empty($result['entsigndate'])){
                $if_sample_save = 0;
                $if_finish = 0;
            }else{
                $if_save = 1;
            }
        }

        $rs=array(
            'picture_count'=>$picture_count,
            'if_sample_save'=>$if_sample_save,
            'if_finish'=>$if_finish,
        );
        $this->ajaxReturn($rs);
    }

    //抽样单图片删除
    public function doDeleteSample(){
        $id =I("id",0,'intval');
        $rs = array("msg"=>"fail");
        if(D("sample_picture")->where("id=".$id)->delete()){
            $rs['msg'] = 'succ';
        }
        $this->ajaxReturn($rs);
    }

    //保存抽样图片
    public function saveSampleImage(){
        $id = I("id");
        $type = I("type")=='sample'?0:1;
        $imgurl = I("imgurl");
        $remark = I("remark");
        $result = array("msg"=>"fail");

        if(empty($imgurl)){
            $result['msg'] = "无效的提交！";
            $this->ajaxReturn($result);
        }

        $data = array(
            'centreNo'=>$id,
            'picture_name'=>$imgurl,
            'remark'=>$remark,
            'type'=>$type
        );
        M()->startTrans();
        if(D(sample_picture)->add($data)){
            $result['msg'] = 'succ';
            M()->commit();
        }else{
            M()->rollback();
        }
        $this->ajaxReturn($result);
    }

    //合同状态入库
    public function doUpdateState(){
        $rs = array('msg'=>'fail');
        $centreNo = I('centreno');
        $admin_auth = session("admin_auth");
        $contract_user_id = $admin_auth['id'];
        $where['centreNo']=$centreNo;
        //pr($centreNo);
        $data_flow = array(
            "centreNo"=>$centreNo,
            'contract_user_id'=>$contract_user_id,
            'contract_time'=>Date("Y-m-d H:i:s"),
        );
        $data_contract = array(
            'ifedit'=>1
        );
        M()->startTrans();
        if(D("contract_flow")->data($data_flow)->add()){
            D("contract")->where($where)->save($data_contract);
            M()->commit();
            $rs['msg']='录入成功';
        }else{
            $rs['msg']='录入失败';
            M()->rollback();
        }
        $this->ajaxReturn($rs);
    }

    //特殊号段查询
    public function specialCodeSelect(){
        $list = D("special_centre_code")->where('remainNum>0')->select();

        $body = array(
            "special_list"=>$list,
        );
        //dump($body);
        $this->assign($body);
        $this->display();
    }

    //外部签发
    /*public function externalSign(){
        $rs = array('msg'=>'fail');
        $centreNo = I('centreno');
        //pr($centreNo);
        $admin_auth = session("admin_auth");
        $external_sign_user_id = $admin_auth['id'];
        //pr($centreNo);
        $data_flow = array(
            'status'=>6,
            'external_sign_user_id'=>$external_sign_user_id,
            'contract_time'=>Date("Y-m-d H:i:s"),
            'external_sign_time'=>Date("Y-m-d H:i:s")
        );
        $where['centreNo']=$centreNo;
        M()->startTrans();
        if(D("contract_flow")->where($where)->save($data_flow)){
            M()->commit();
            $rs['msg']='已外部签发';
        }else{
            $rs['msg']='签发失败';
            M()->rollback();
        }
        $this->ajaxReturn($rs);
    }*/

    //合同列表
    public function showList(){
        //判断角色，确定是否可以修改
        $admin_auth = session("admin_auth");
        $if_admin = $admin_auth['super_admin'];
        $roleid = $admin_auth['gid'];
        $department = $admin_auth['department'];
        $begin_time = I("begin_time");
        $end_time = I("end_time");
        $type = I("type");

        if($admin_auth){
            //$role = D('common_role')->where('id='.$roleid)->find();
            //if($role['rolename']=="领导" || $if_admin==1 || $role['rolename']=="前台人员"){
            if($roleid==8 || $if_admin==1 || $roleid==7){
                $if_edit = 1;
            }else{
                $if_edit = 0;
            }
        }

        $keyword = I("keyword");//获取参数
        $keyword = trim($keyword);
        $where = "1=1";
        $keyword && $where .= " and (c.centreNo like '%{$keyword}%' or c.clientName like '%{$keyword}%' or c.productUnit like '%{$keyword}%' or c.sampleName like '%{$keyword}%' or c.sampleCode like '%{$keyword}%' or c.grade like '%{$keyword}%' or c.specification like '%{$keyword}%' or
c.trademark like '%{$keyword}%' or c.sampleStatus like '%{$keyword}%' or c.package_remark like '%{$keyword}%' or c.clientSign like '%{$keyword}%' or
c.telephone like '%{$keyword}%' or c.tax like '%{$keyword}%' or c.postcode like '%{$keyword}%' or c.email like '%{$keyword}%' or
c.address like '%{$keyword}%' or c.remark like '%{$keyword}%' or c.sampleStaQuan like '%{$keyword}%' or c.collector like '%{$keyword}%' or
c.centreNo1 like '%{$keyword}%' or c.centreNo2 like '%{$keyword}%' or c.centreNo3 like '%{$keyword}%' or c.conclusion like '%{$keyword}%' or c.testCriteria like '%{$keyword}%' )";

        //if($role['rolename']=="领导" || $role['rolename']=="审核员" || $role['rolename']=="盖章人员" || $if_admin==1){
        if($roleid==8 || $roleid==13 || $roleid==15 || $if_admin==1){
            //
        }else{
            if($department=='G1') $where .= " and collector_partment='G1'";
            else if($department=='G2') $where .= " and collector_partment='G2'";
            else $where .= " and collector_partment = '{$department}'";
        }
        if(!empty($begin_time)){
            $where.=" and date_format(c.collectDate,'%Y-%m-%d') >='{$begin_time}'";
        }
        if(!empty($end_time)){
            $where.=" and date_format(c.collectDate,'%Y-%m-%d') <='{$end_time}'";
        }
        if($type=='yes'){
            $where.=" and f.status = 6";

        }else{
            $type='no';
            //0合同 1检测 3盖章退回 -4 批退回  7 已接单  8待上传报告
            $where.=" and (f.status !=6 or f.id is null)";
        }
        $page = I("p",'int');

        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;
        /*
                $list = D("contract as c")->field('if(f.id is null,-1,f.id) as flow_id,c.*,f.status,f.inner_sign_user_id,f.inner_sign_time,f.takelist_user_id,f.takelist_time,u.name as takename,u1.name as innername')->join('left join contract_flow as f on c.centreNo=f.centreNo LEFT JOIN common_system_user u on f.takelist_user_id=u.id LEFT JOIN common_system_user u1 on f.inner_sign_user_id=u1.id')->where($where)->order('c.input_time DESC')->limit("{$offset},{$pagesize}")->select();
        */

        $list = D("contract as c")->field('if(f.id is null,-1,f.id) as flow_id,c.*,f.status,f.ifback,back.back_reason,f.inner_sign_user_id,f.inner_sign_time,f.takelist_user_id,f.verify_time,f.takelist_time,u.name as takename,u1.name as innername,u2.name as auditname')
            ->join('left join contract_flow as f on c.centreNo=f.centreNo LEFT JOIN common_system_user u on f.takelist_user_id=u.id LEFT JOIN common_system_user u1 on f.inner_sign_user_id=u1.id LEFT JOIN common_system_user u2 on f.verify_user_id=u2.id
             left join back_report as back on c.centreNo=back.centreNo')
            ->where($where)->order('case when (f.status=3) then f.back_time else c.input_time end DESC')//要是有退回，退回显示在前边
            ->limit("{$offset},{$pagesize}")
            ->select();
        //pr(D("contract as c")->getLastSql());

        if($list){
            $con_list = array();//反馈
            foreach($list as $contract){
                array_push($con_list,"'".$contract['centreno']."'");
            }
            $centreno_str = implode(',',$con_list);
            $no_feed_list = D('report_feedback')->where(' id in (select max(id) from report_feedback where if_report=0 and centreNo in ('.$centreno_str.') group by centreNo)')->group('centreNo')->select();
            $con_list = array();
            if($no_feed_list){
                foreach($no_feed_list as $no_feed){
                    $con_list[$no_feed['centreno']]	= $no_feed;
                }
            }
            foreach($list as $key=>$val){
                if($con_list[$val['centreno']]){
                    $val['sub_status'] = $con_list[$val['centreno']]['status'];
                    $val['if_report'] = $con_list[$val['centreno']]['if_report'];
                    $val['if_outer'] = $con_list[$val['centreno']]['if_outer'];
                    $val['if_sample'] = $con_list[$val['centreno']]['if_sample'];
                    $val['if_invalid'] = $con_list[$val['centreno']]['if_invalid'];
                }else{
                    $val['sub_status'] = -1;
                    $val['if_report'] = -1;
                    $val['if_outer'] = -1;
                    $val['if_invalid'] = -1;
                    $val['if_sample'] = -1;
                }
                $list[$key] = $val;
            }
        }
        //dump($list);die;
        $count = D("contract as c")->field('if(f.id is null,-1,f.id) as flow_id,c.*,f.status,f.inner_sign_user_id,f.inner_sign_time,f.takelist_user_id,f.takelist_time,u.name as takename,u1.name as innername')->join('left join contract_flow as f on c.centreNo=f.centreNo LEFT JOIN common_system_user u on f.takelist_user_id=u.id LEFT JOIN common_system_user u1 on f.inner_sign_user_id=u1.id')->where($where)->count();
        //pr($count);
        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $pagination= $Page->show();// 分页显示输出

        $body = array(
            "list"=>$list,
            'pagination'=>$pagination,
            'if_edit'=>$if_edit,
            'begin_time'=>$begin_time,
            'end_time'=>$end_time,
            'type'=>$type,
        );
        //dump($body);

        $this->assign($body);
        $this->display();
    }

    //编号作废列表
    //合同列表
    public function dateList(){
        //判断角色，确定是否可以修改
        $admin_auth = session("admin_auth");
        $if_admin = $admin_auth['super_admin'];
        $roleid = $admin_auth['gid'];
        $department = $admin_auth['department'];
        $begin_time = I("begin_time");
        $end_time = I("end_time");
        $type = I("type");

        if($admin_auth){
            //$role = D('common_role')->where('id='.$roleid)->find();
            //if($role['rolename']=="领导" || $if_admin==1 || $role['rolename']=="前台人员"){
            if($roleid==8 || $if_admin==1 || $roleid==7){
                $if_edit = 1;
            }else{
                $if_edit = 0;
            }
        }

        $keyword = I("keyword");//获取参数
        $keyword = trim($keyword);
        $where = "1=1";
        $keyword && $where .= " and c.centreNo like '%{$keyword}%'";

        //if($role['rolename']=="领导" || $role['rolename']=="审核员" || $role['rolename']=="盖章人员" || $if_admin==1){
        if($roleid==8 || $roleid==13 || $roleid==15 || $if_admin==1){
            //
        }else{
            if($department=='G1') $where .= " and SUBSTR(c.centreNo,7,1) = 'G' and SUBSTR(c.centreNo,9,3)<=500";
            else if($department=='G2') $where .= " and SUBSTR(c.centreNo,7,1) = 'G' and SUBSTR(c.centreNo,9,3)>500";
            else $where .= " and SUBSTR(c.centreNo,7,1) = '{$department}'";
        }
        if(!empty($begin_time)){
            $where.=" and date_format(c.collectDate,'%Y-%m-%d') >='{$begin_time}'";
        }
        if(!empty($end_time)){
            $where.=" and date_format(c.collectDate,'%Y-%m-%d') <='{$end_time}'";
        }
        //-1 合同作废 0合同 1检测 2提交审核 3盖章退回 -4 批退回  7 已接单  8待上传报告
        //$where.=" and (f.status not in (2,4,5,6) or f.id is null) and c.centreNo1 is null and c.centreNo2 is null and c.centreNo3 is null";
        $where.=" and (f.status not in (2,4,5,6)) and (c.centreNo1 is null or c.centreNo1='') and (c.centreNo2 is null or c.centreNo2='') and (c.centreNo3 is null or c.centreNo3='')";
        $page = I("p",'int');
        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;

        $list = D("contract as c")
            ->field('if(f.id is null,-1,f.id) as flow_id,c.*,f.status,f.ifback,f.inner_sign_user_id,f.inner_sign_time,f.takelist_user_id,f.verify_time,f.takelist_time,u.name as takename,u1.name as innername,u2.name as auditname')
            ->join('left join contract_flow as f on c.centreNo=f.centreNo LEFT JOIN common_system_user u on f.takelist_user_id=u.id LEFT JOIN common_system_user u1 on f.inner_sign_user_id=u1.id LEFT JOIN common_system_user u2 on f.verify_user_id=u2.id')
            ->where($where)
            ->order('c.input_time DESC')
            ->limit("{$offset},{$pagesize}")
            ->select();
        if($list){
            $con_list = array();//反馈
            foreach($list as $contract){
                array_push($con_list,"'".$contract['centreno']."'");
            }
            $centreno_str = implode(',',$con_list);
            $no_feed_list = D('report_feedback')->where(' id in (select max(id) from report_feedback where centreNo in ('.$centreno_str.') ) ')->group('centreNo')->select();
            $feed_list =array();
            if($no_feed_list){
                foreach($no_feed_list as $no_feed){
                    $con_list[$no_feed['centreno']]	= $no_feed;
                }
            }

            foreach($list as $key=>$val){
                if($con_list[$val['centreno']]){
                    //申请作废状态sub_status
                    $val['sub_status'] = $con_list[$val['centreno']]['status'];
                    $val['if_invalid'] = $con_list[$val['centreno']]['if_invalid'];
                }else{
                    $val['sub_status'] = -1;
                    $val['if_invalid'] = -1;
                }
                $list[$key] = $val;
            }
        }

        //dump($list);die;
        $count = D("contract as c")->field('if(f.id is null,-1,f.id) as flow_id,c.*,f.status,f.ifback,f.inner_sign_user_id,f.inner_sign_time,f.takelist_user_id,f.verify_time,f.takelist_time,u.name as takename,u1.name as innername,u2.name as auditname')->join('left join contract_flow as f on c.centreNo=f.centreNo LEFT JOIN common_system_user u on f.takelist_user_id=u.id LEFT JOIN common_system_user u1 on f.inner_sign_user_id=u1.id LEFT JOIN common_system_user u2 on f.verify_user_id=u2.id')->where($where)->count();
        //pr($count);
        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $pagination= $Page->show();// 分页显示输出

        $body = array(
            "list"=>$list,
            'pagination'=>$pagination,
            'begin_time'=>$begin_time,
            'end_time'=>$end_time,
            'if_edit'=>$if_edit,
        );
        //dump($body);

        $this->assign($body);
        $this->display();
    }

    //申请作废
    public function applyDelCon(){
        $rs = array('msg'=>'fail');
        $centreno = I('centreno');
        $reason = I('reason');
        $a=D('report_feedback')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreno.'") and (status=0 or status=1)')->find();
        if(!empty($a)){
//            if($a['if_invalid']==1){
//                $rs['msg']='该审核员正在处理中，请勿重复提交！';
//            }
              $rs['msg']='exist';
              $this->ajaxReturn($rs);
        }
        $data = array(
            'centreNo'=>$centreno,
            'reason'=>$reason,
            'if_invalid'=>1
        );
        M()->startTrans();
        if(D('report_feedback')->add($data)){
            $rs['msg']='申请成功';
            //申请中  审核单不可修改
            M()->commit();
        }else{
            $rs['msg']='申请失败';
            M()->rollback();
        }
        $this->ajaxReturn($rs);
    }
    //报告管理下的合同列表
    public function showReportList(){
        $de = I("de",'A');

        //判断角色，确定是否可以修改
        $admin_auth = session("admin_auth");
        $if_admin = $admin_auth['super_admin'];
        $user = $admin_auth['gid'];
        $department = $admin_auth['department'];
        $begin_time = I("begin_time");
        $end_time = I("end_time");
        if($user==10 || $if_admin==1 ){
            $if_edit = 1;
        }else{
            $if_edit = 0;
        }
        if($user==8){
            $leader=1;
        }else{
            $leader=0;
        }
        //D("contract_flow as f");
        $keyword = I("keyword");//获取参数
        $keyword = trim($keyword);
        $where="1=1";
        $keyword && $where .= " and c.centreNo like '%{$keyword}%'";
        if($de =='A'){
            $where .= " and c.status = 8";
        }
        elseif ($de =='B'){
            $where .= " and c.status = 1";
        }
        else {
            $where .= " and c.status in (2,3,4,5,6)";
        }

        if($user==8 || $user==15 || $user==13 || $if_admin==1){
            //
        }else{
            //判断G1/G2的特殊化
            if($department == 'G1'){
                $where .= " and SUBSTR(c.centreno,7,1) = 'G' and SUBSTR(c.centreno,9,11) <='500'";
            }elseif ($department == 'G2'){
                $where .= " and SUBSTR(c.centreno,7,1) = 'G' and SUBSTR(c.centreno,9,11) >'500'";
            }
            else{
                $where .= " and SUBSTR(c.centreNo,7,1) = '{$department}'";
            }
        }
        if(!empty($begin_time)){
            $where .=" and date_format(c.contract_time,'%Y-%m-%d') >='{$begin_time}'";
        }
        if(!empty($end_time)){
            $where .=" and date_format(c.contract_time,'%Y-%m-%d') <='{$end_time}'";
        }
        $where .= " and c.status != 7 and c.status != 0";
        $page = I("p",'int');
        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;

        $list = D("contract_flow as c")->field('if(c.id is null,-1,c.id) as flow_id,f.*,c.isaudit,c.back_time,c.bz_back,c.sh_back,c.gz_back,c.ifback,c.status,c.inner_sign_user_id,c.inner_sign_time,c.external_sign_time,c.takelist_user_id,c.takelist_time,c.verify_time,u.name as takename,u1.name as innername,u2.name as externalname,u3.name as auditname,v.doc_path,v.pdf_path,v.qrcode_path')
                ->join('left join contract as f on c.centreNo=f.centreNo LEFT JOIN common_system_user u on c.takelist_user_id=u.id LEFT JOIN common_system_user u2 on c.external_sign_user_id=u2.id LEFT JOIN common_system_user u1 on c.inner_sign_user_id=u1.id LEFT JOIN common_system_user u3 on c.verify_user_id=u3.id left join test_report as v on c.centreNo=v.centreNo' )
                ->where($where)->order('case when (c.status=8 and c.back_time) then c.back_time else c.takelist_all_time end desc')->limit("{$offset},{$pagesize}")->select();

        if($list){
            $con_list = array();//反馈
            foreach($list as $contract){
                array_push($con_list,"'".$contract['centreno']."'");
            }
            $centreno_str = implode(',',$con_list);
            $no_feed_list = D('report_feedback')->where(' id in (select max(id) from report_feedback where if_report=1 and centreNo in ('.$centreno_str.') ) ')->group('centreNo')->select();
            $to_feed_list = D('report_feedback')->where(' id in (select max(id) from report_feedback where if_report=0 and centreNo in ('.$centreno_str.') ) ')->group('centreNo')->select();//查前台是否申请修改
            $con_list = array();
            $feed_list =array();
            if($no_feed_list){
                foreach($no_feed_list as $no_feed){
                    $con_list[$no_feed['centreno']]	= $no_feed;
                }
            }
            if($to_feed_list){
                foreach($to_feed_list as $to_feed){
                    $feed_list[$to_feed['centreno']]= $to_feed;
                }
            }
            foreach($list as $key=>$val){
                if($con_list[$val['centreno']]){
                    $val['sub_status'] = $con_list[$val['centreno']]['status'];
                    $val['if_report'] = $con_list[$val['centreno']]['if_report'];
                }else{
                    $val['sub_status'] = -1;
                    $val['if_report'] = -1;
                }
                $list[$key] = $val;
            }
            foreach($list as $key=>$num){
                if($feed_list[$num['centreno']]){
                    $num['to_status'] = $feed_list[$num['centreno']]['status'];
                    $num['if_outer'] = $feed_list[$num['centreno']]['if_outer'];
                }else{
                    $num['to_status'] = -1;
                    $num['if_outer'] = -1;
                }
                $list[$key] = $num;
            }
        }
        //dump($no_feed_list);
        //dump($to_feed_list);
        // dump($list);die;
        $count = D("contract_flow as c")->where($where)->count();
        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $pagination= $Page->show();// 分页显示输出

        $body = array(
            "list"=>$list,
            'de'=>$de,
            'pagination'=>$pagination,
            'if_edit'=>$if_edit,
            'begin_time'=>$begin_time,
            'end_time'=>$end_time,
            'keyword'=>$keyword,
            'leader'=>$leader,
            'if_admin'=>$if_admin
        );
        //dump($body);
        $this->assign($body);
        $this->display();
    }
    //确认生成报告
    public function doneConfirm(){
        $rs = array('msg'=>'fail');
        $centreno=I("centreno");
        $admin_auth = session("admin_auth");//获取当前登录用户信息
        $userid=$admin_auth['id'];
        $user=$admin_auth['gid'];//判断是哪个角色
        $if_admin = $admin_auth['super_admin'];
        $where= "centreno='{$centreno}'";
        $data=array(
            'status'=>1,
            'report_time'=>date("Y-m-d H:i:s"),
            'report_user_id'=>$userid,
        );
        if(D("contract_flow")->where($where)->save($data)){
            $rs['msg'] = 'succ';
        }
        $this->ajaxReturn($rs);
    }
    //申请修改报告
    public function doEditReport(){
        $rs = array('msg'=>'fail');
        $centreno = I('back_centreno');
        $reason = I('back_reason');
        $a=D('report_feedback')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreno.'") and (status=0 or status=1)')->find();
        if(!empty($a)){
            if($a['if_report']==1){
                $rs['msg']='该申请审核员正在处理中，请勿重复提交！';
            }
            elseif($a['if_report']==0){
                $rs['msg']='该报告前台正在申请修改，请稍后再试';
            }
        }
        else{
            $data = array(
                'centreNo'=>$centreno,
                'reason'=>$reason,
                'if_report'=>1
            );
            M()->startTrans();
            if(D('report_feedback')->add($data)){
                $rs['msg']='succ';
                //申请中  审核单不可修改
                M()->commit();
            }else{
                $rs['msg']='申请失败';
                M()->rollback();
            }
        }

        $this->ajaxReturn($rs);
    }
    //上传报告下的修改完毕
    public function doneAllUpdate(){
        $rs = array('msg'=>'fail');
        $centreno=I("centreno");
        $admin_auth = session("admin_auth");//获取当前登录用户信息
        $userid=$admin_auth['id'];
        $user=$admin_auth['gid'];//判断是哪个角色
        $if_admin = $admin_auth['super_admin'];
        $where= "centreno='{$centreno}'";
        $data=array(
            'status'=>2,
            'uploadreport_user_id'=>$userid,
            'uploadreport_time'=>date("Y-m-d H:i:s"),
        );
        $data1=array(
            'status'=>3
        );
        M()->startTrans();
        if(D("contract_flow")->where($where)->save($data) and D('report_feedback')->where('id = (SELECT a.id from (SELECT max(id) as id from report_feedback WHERE centreNo = "'.$centreno.'") a )')->save($data1)){
            $rs['msg'] = 'succ';
            M()->commit();
        }else{
            $rs['msg']='操作失败';
            M()->rollback();
        }
        $this->ajaxReturn($rs);
    }
    //申请修改
    public function doSubmitFeedback(){
        $rs = array('msg'=>'fail');
        $centreno = I('centreno');
        $reason = I('reason');
        $where['centreNo']=$centreno;
        $type_status = I('type_status',0,'intval')== 6?1:0;
        $where['status']=array('in','0,1');
        $a=D('report_feedback')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreno.'") and (status=0 or status=1)')->find();//只允许处理后再次提出申请，不用查最大的id
        if(!empty($a)){
            if($a['if_report']==0){
                $rs['msg']='该申请审核员正在处理中，请勿重复提交！';
            }
            elseif($a['if_report']==1){
                $rs['msg']='该报告编制员正在申请修改，请稍后再试';
            }
        }
        else{
            //pr($type_status);
            $data = array(
                'centreNo'=>$centreno,
                'reason'=>$reason,
                'if_outer'=>$type_status
            );
            M()->startTrans();
            if(D('report_feedback')->add($data)){
                $rs['msg']='申请成功';
                //申请中  审核单不可修改
                if($type_status==1){
                    $data_contract['if_edit']=1;
                    D("inspection_report")->where($where)->save($data_contract);
                }
                M()->commit();
            }else{
                $rs['msg']='申请失败';
                M()->rollback();
            }}
        $this->ajaxReturn($rs);
    }

    //更改补充检验报告单
    public function addorEditReport(){
        $centreNo = I('id');
        //pr($centreNo);
        $count = D('inspection_report')->where('centreNo="'.$centreNo.'"')->count();
        $one = D('inspection_report')->where('centreNo="'.$centreNo.'"')->find();
        $sub_status = M('report_feedback')->where('id = (SELECT max(id) from report_feedback WHERE centreNo="'.$centreNo.'")')->find();
        if(empty($sub_status)){
            $sub_status['status']=-1;
        }
        $body = array(
            'centreNo'=>$centreNo,
            'count'=>$count,
            'one'=>$one,
            'sub_status'=>$sub_status,
        );

        $this->assign($body);
        $this->display();
    }

    //检查是否录入更改检验报告单
    public function checkEditList(){
        $centreNo = I('centreno');
        $count = D('inspection_report')->where('centreNo="'.$centreNo.'"')->count();
        $rs = array(
            'count'=>$count,
        );
        $this->ajaxReturn($rs);
    }

    //跳转更改检验报告单打印页面
    public function reWriteTest(){
        $centreNo = I('id');
        $admin_auth = session("admin_auth");
        $if_admin = $admin_auth['super_admin'];
        $user = $admin_auth['gid'];
        $one = D('inspection_report')->where('centreNo="'.$centreNo.'"')->find();
        $update_item_list = explode("/&&/",$one['update_item']);
        //pr($update_item_list);
        $imageurl = str_replace("_thumb","",$one['imageurl']);
        $body = array(
            'one'=>$one,
            'update_item_list'=>$update_item_list,
            'imageurl'=>$imageurl,
            'user'=>$user,
            'if_admin'=>$if_admin
        );

        $this->assign($body);
        $this->display();
    }

    //更改检验报告单录入
    public function saveInspecReport(){
        $rs['msg']='fail';

        //$edit_No = I('edit_No');
        $centreNo = I('centreNo');
        $sampleName = I('sampleName');
        $clientName = I('clientName');
        $update_item = I('update_item');
        $imageurl = I('imgurl');
        $image_remark = I('image_remark');
        $update_item_list = "";
        for($i = 0;$i < 6;$i++){
            $update_item_list.=$update_item[$i]."/&&/";
        }
        $update_reason = I('update_reason');
        $applicant = I('applicant');
        $handler = I('handler');
        $handleDate = I('handleDate');
        if(empty($sampleName) || empty($clientName) || empty($update_item) || empty($imageurl)|| empty($update_reason)|| empty($applicant)|| empty($handler)|| (empty($update_item[0]) && empty($update_item[1]) && empty($update_item[2]) && empty($update_item[3]) && empty($update_item[4]) && empty($update_item[5]))){
            $rs['msg']='信息填写不完整！';
            $this->ajaxReturn($rs);
        }
        $where['centreNo'] = $centreNo;

        $data_list = array(
            "handler"=>$handler,
            'handleDate'=>$handleDate,
            //'edit_No'=>$edit_No,
            'centreNo'=>$centreNo,
            'sampleName'=>$sampleName,
            'clientName'=>$clientName,
            'update_item'=>$update_item_list,
            'update_reason'=>$update_reason,
            'applicant'=>$applicant,
            'imageurl'=>$imageurl,
            'image_remark'=>$image_remark,
        );
        if(D("inspection_report")->where($where)->count()>0){
            D('inspection_report')->where($where)->save($data_list);
        }else{
            D('inspection_report')->add($data_list);
        }
        $rs['msg']='succ';
        $this->ajaxReturn($rs);

    }

    //跳转上传附件页面
    /*function doUploadInsImage(){
        $centreNo = I('centreno');
        $body = array(
            'centreNo'=>$centreNo,
        );

        $this->assign($body);
        $this->display();
    }*/

    //获取最中心编号
    public function getLastCode(){
        $admin_auth = session("admin_auth");
        $department = $admin_auth['department'];
        $id_admin = $admin_auth['super_admin'];
        if($id_admin==1){
            $department=D;
        }
        $centreNo['re']='none';
        $year=I("year");
        $month=I("month");
        $centreHead=$year.$month;
        if($department=='G1'){
            $department='G';
            $list = D("contract")
                ->field('centreNo',SUBSTR(centreNo,9,3))
                ->where('substr(centreNo,7,1) = "'.$department.'" and centreNo like "'.$centreHead.'%" and SUBSTR(centreNo,9,3)<=500')
                ->order('SUBSTR(centreNo,9,3) desc')->select();
        }else if($department=='G2'){
            $department='G';
            $list = D("contract")
                ->field('centreNo',SUBSTR(centreNo,9,3))
                ->where('substr(centreNo,7,1) = "'.$department.'" and centreNo like "'.$centreHead.'%" and SUBSTR(centreNo,9,3)>500')
                ->order('SUBSTR(centreNo,9,3) desc')->select();
        }else{
            $list = D("contract")
                ->field('centreNo',SUBSTR(centreNo,9,3))
                ->where('substr(centreNo,7,1) = "'.$department.'" and centreNo like "'.$centreHead.'%" and SUBSTR(centreNo,9,3)>100')
                ->order('SUBSTR(centreNo,9,3) desc')->select();
        }

        //pr(D("contract")->getLastSql());
        if(count($list)>0){
            $centreNo['re']= $list[0]['centreno'];
        }

        //dump($list[0]['centreno']);
        $this->ajaxReturn($centreNo);
    }

    //获取新最优质中心编号
    public function getHighCode(){
        $admin_auth = session("admin_auth");
        $department = $admin_auth['department'];
        $id_admin = $admin_auth['super_admin'];
        if($id_admin==1){
            $department=D;
        }


        $centreNo['re']='none';
        $year=I("year");
        $month=I("month");
        $centreHead=$year.$month;

        $list = D("contract")
            ->field('centreNo',SUBSTR(centreNo,9,3))
            ->where('substr(centreNo,7,1) = "'.$department.'" and centreNo like "'.$centreHead.'%" and SUBSTR(centreNo,9,3)<=100')
            ->order('SUBSTR(centreNo,9,3) desc')
            ->select();


        if(count($list)>0){
            $centreNo['re']= $list[0]['centreno'];
        }
        //$centreNo['count'] = $count[0]['num'];
        //pr($centreNo['count']);
        //dump($list[0]['centreno']);
        $this->ajaxReturn($centreNo);
    }

    //费用查询
    public function feeManage(){
        $criteria = I('criteria_find');
        //$test_fee_list = D("test_fee")->where('criteria like %'.$criteria.'%')->select();
        $allChose = I('allChose',0,'intval');
        $admin_auth = session("admin_auth");
        if($admin_auth){
            $if_admin = $admin_auth['super_admin'];
            $roleid = $admin_auth['gid'];

            $role = D('common_role')->where('id='.$roleid)->find();
            //if($role['rolename']=="领导" || $if_admin==1){
            if($roleid==8 || $if_admin==1){
                $if_leader = 1;
            }else{
                $if_leader = 0;
            }
        }

        $page = I("p",'int');
        $pagesize = 10;
        if($page<=0) $page = 1;
        $offset = ( $page-1 ) * $pagesize;

        $where = 'criteria like replace("%'.$criteria.'%"," ","")';

        if($allChose==0){

        }else if($allChose==1){
            $where .= ' and quantity=1';
        }else if($allChose==2){
            $where .= ' and quantity=2';
        }
        $list = D("test_fee")->where($where)->limit("{$offset},{$pagesize}")->select();
        //pr(D("test_fee")->getLastSql());
        $count = D("test_fee")->where($where)->count();
        $Page= new \Think\Page($count,$pagesize);
        $Page->setConfig('theme',"<ul class='pagination'></li><li>%FIRST%</li><li>%UP_PAGE%</li><li>%LINK_PAGE%</li><li>%DOWN_PAGE%</li><li>%END%</li><li><a> %HEADER%  %NOW_PAGE%/%TOTAL_PAGE% 页</a></ul>");
        $pagination= $Page->show();// 分页显示输出
        $body = array(
            "fee_list"=>$list,
            'pagination'=>$pagination,
            'if_leader'=>$if_leader,
            'pagination'=>$pagination,
        );
        $this->assign($body);
        $this->display();
    }


    //标准号查询
    public function findCriteria(){
        $criterias = I('criteria');
        $criteria_list=D("test_fee")->where('criteria like replace("%'.$criterias.'%"," ","")')->group("criteria")->limit(10)->select();
        //pr(D("test_fee")->getLastSql());
        $productname_list = array();
        foreach($criteria_list as $c){
            $criteria = $c['criteria'];
            $productname=D("test_fee")->where('criteria like replace("%'.$criteria.'%"," ","")')->group("productname")->select();
            array_push($productname_list,$productname);
        }

        $rs = array(
            'criteria_list'=>$criteria_list,
            'productname_list'=>$productname_list,
        );
        $this->ajaxReturn($rs);
    }

    //标准号下的产品名称查询
    public function findProduct(){
        $criteria = I('criteria');
        $productname_list=D("test_fee")->where('criteria like replace("%'.$criteria.'%"," ","")')->group("productname")->select();
        //pr(D("test_fee")->getLastSql());
        $rs = array(
            'productname_list'=>$productname_list,
        );
        $this->ajaxReturn($rs);
    }

    //标准号下的产品名称下的选项查询
    public function findItem(){
        $criteria = I('criteria');
        $productname = I('productname');
        $where = 'criteria like replace("%'.$criteria.'%"," ","")';
        if($productname && $productname!='null'){
            $where.=' and productname = "'.$productname.'"';
        }
        $item_list = D("test_fee")->where($where)->select();
        //$item_list_arr = array();
        //$item_list_arr = explode(",",$item_list['child_item_list']);
        //pr(D("test_fee")->getLastSql());
        $rs = array(
            'item_list'=>$item_list,
            //'child_item_list'=>$item_list_arr
        );
        $this->ajaxReturn($rs);
    }

    //全项选中选项
    public function findAllItem(){
        $item_id = I('item_id');
        $where['id'] = $item_id;
        $item_list = D("test_fee")->where($where)->find();
        //pr(D("test_fee")->getLastSql());
        $rs = array(
            'fee_item'=>$item_list
        );
        $this->ajaxReturn($rs);
    }

    //显示特殊编码
    public function findSpecialCode(){
        $admin_auth = session("admin_auth");
        $department = $admin_auth['department'];
        $if_admin = $admin_auth['super_admin'];
        $type = I('type');
        $where=array();

        //特殊编码管理员和该部门都可见
        if($if_admin!=1) $where['department']=$department;

        $specialList = D("special_centre_code")->where($where)->select();
        //pr(D("special_centre_code")->getLastSql());
        //$year=array();
        $codeList=array();
        $numList=array();
        foreach($specialList as $special){
            //array_push($year,$special->year);
            $year = $special['year'];
            $month = str_pad($special['month'],2,"0",STR_PAD_LEFT);
            $num = $special['remainnum'];
            $department = $special['department'];
            $centreHead=$year.$month;
            //SELECT centreNo,SUBSTR(centreNo,9,3) from contract where ifHighQuantity=0 order by SUBSTR(centreNo,9,3) desc
            $special = D("contract")->field('centreNo,SUBSTR(centreNo,9,3) as codes')->where('centreNo like "'.$centreHead.'%" and SUBSTR(centreNo,9,3)>100 and collector_partment="'.$department.'"')->order('SUBSTR(centreNo,9,3) desc')->find();
            //pr($special);
            //pr(D("contract")->getLastSql());
            //pr(count($special));
            if(count($special)==0){
                $code=100;
            }else{
                $code = (int)$special['codes'];
                //pr($code);
            }
            /*for($i=0;$i<$num;$i++){
                $code = $code+1;
                $code3=str_pad($code,3,"0",STR_PAD_LEFT);
                $special_no=$centreHead.$department.'W'.$code3;
                array_push($codeList,$special_no);
            }*/
            $code = $code+1;
            $code3=str_pad($code,3,"0",STR_PAD_LEFT);
            $special_no=$centreHead.$department.$type.$code3;
            array_push($codeList,$special_no);
            array_push($numList,$num);
        }
        //pr(D("contract")->getLastSql());
        //if(count($list)>0){
        //	$centreNo['re']= $list[0]['centreno'];
        //}

        $rs = array(
            //'special_list'=>$specialList,
            'codeList'=>$codeList,
            'numList'=>$numList
        );
        $this->ajaxReturn($rs);
    }

    //删除特殊编号
    public function doDeleteSpec(){
        $id=I('id');
        if(D("special_centre_code")->where('id='.$id)->delete()){
            $rs['msg']='succ';
        }else{
            $rs['msg']='fail';
        }
        $this->ajaxReturn($rs);
    }

    //检验报告单详情
    public function checkDetail(){
        $body=array();
        $this->assign($body);
        $this->display();
    }

    //抽样单
    public function sampleDetail(){
        $centreno = I("id");
        $samdetail = D("contract")->where("centreNo=".$centreno)->find();
        $body = array();
        $this->assign($body);
        $this->display();
    }

    //费用标准修改数据回显
    public function doUpdateFee(){
        $id = I('id');
        if($id){
            $test_fee_item = D("test_fee")->where('id='.$id)->find();
            $item_list = array();
            //pr($test_fee_item['child_item_list']);
            $item_list = explode(",",$test_fee_item['child_item_list']);
            $rs['child_item_list'] = $item_list;
            $rs['rs'] = $test_fee_item;
        }else{
            $rs['rs'] = 'add';
        }
        $this->ajaxReturn($rs);
    }


    //费用标准修改
    public function updateFee(){
        $rs['msg'] = 'fail';
        $id = I('id');


        $meterial = I('meterial_edit');
        $criteria = I('criteria_edit');
        $productname = I('productname_edit');
        $item = I('item_edit');
        $samplequantity = I('samplequantity_edit');
        $testperiod = I('testperiod_edit');
        $remark = I('remark_edit');
        $fee = I('fee_edit');
        $quantity = I('quantity_edit');

        $where['meterial']=$meterial;
        $where['criteria']=$criteria;
        $where['productname']=$productname;
        $where['item']=$item;
        $where['sampleQuantity']=$samplequantity;
        $where['testPeriod']=$testperiod;
        $where['remark']=$remark;
        $where['fee']=$fee;
        $where['quantity']=$quantity;

        if(D("test_fee")->where('id='.$id)->save($where)){
            $rs['msg'] = 'succ';
        }
        $this->ajaxReturn($rs);
    }

    //费用标准添加和修改
    public function doAddFee(){
        $rs['msg'] = 'fail';
        $id = I('edit_id');
        $quantity = I('quantity');
        $meterial = I('meterial');
        $meterial = preg_replace('# #','',$meterial);
        $criteria = I('criteria');
        $criteria = preg_replace('# #','',$criteria);
        $productname = I('productname');
        $productname = preg_replace('# #','',$productname);
        $item = I('item');
        if($quantity == 2){
            $checkbox_item = I('checkbox_item');
        }
        $checkbox_item_str = implode(',',$checkbox_item);
        $samplequantity = I('samplequantity');
        $testperiod = I('testperiod');
        $remark = I('remark');
        $fee = I('fee',0,'intval');
        $quantity = I('quantity');

        if(empty($meterial) || empty($criteria)|| empty($item) || !isset($fee)){
            $rs['msg'] = 'fail';
            $this->ajaxReturn($rs);
        }

        $where['meterial']=trim($meterial);
        $where['criteria']=trim($criteria);
        $where['productname']=trim($productname);
        $where['item']=trim($item);
        $where['sampleQuantity']=trim($samplequantity);
        $where['testPeriod']=trim($testperiod);
        $where['remark']=trim($remark);
        $where['fee']=$fee;
        $where['quantity']=trim($quantity);
        $where['child_item_list']=trim($checkbox_item_str);
        //pr($where);

        if($id){
            D("test_fee")->where('id='.$id)->save($where);
            $rs['msg'] = '保存成功';
        }else{
            D("test_fee")->add($where);
            $rs['msg'] = '添加成功';
        }


        $this->ajaxReturn($rs);
    }


    //费用标准删除
    public function doDeleteFee(){
        $id = I('id');
        $test_item = D("test_fee")->where('id='.$id)->find();
        $quantity = $test_item['quantity'];

        M()->startTrans();
        $flag = 1;
        if($quantity==1){
            $criteria = $test_item['criteria'];
            $productname = $test_item['productname'];
            $where = 'quantity=2';
            $where .= ' and criteria like "%'.$criteria.'%"';
            $where .= ' and productname="'.$productname.'"';
            $test_all_item_list = D("test_fee")->where($where)->select();
            foreach($test_all_item_list as $test_all_item){
                $child_id_str = $test_all_item['child_item_list'];
                $child_id_list = explode(",",$child_id_str);
                $child_id_new_list = array();
                foreach($child_id_list as $child_id){
                    if($child_id != $id){
                        array_push($child_id_new_list,$child_id);
                    }
                }
                $child_id_new_str = implode(",",$child_id_new_list);
                $all_id = $test_all_item['id'];
                $update_status = D("test_fee")->where('id='.$all_id)->save(array('child_item_list' => $child_id_new_str,'modify_time'=>Date("Y-m-d H:i:s")));
                if(!$update_status){
                    $flag = 0;
                    break;
                }
                //pr("id:".$all_id." child:".$child_id_new_str);
                //pr($flag);
                //pr($flag);

            }
        }
        //pr($flag);
        if($flag == 1){
            if(D("test_fee")->where('id='.$id)->delete()){
                $rs['msg'] = '删除成功';
                M()->commit();
            }else{
                $rs['msg'] = '删除失败';
                M()->rollback();
            }
        }else{
            $rs['msg'] = '删除失败';
            M()->rollback();
        }

        $this->ajaxReturn($rs);
    }
}
?>