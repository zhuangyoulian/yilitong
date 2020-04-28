<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/11/7
 * Time: 15:03
 */

namespace ylt\admin\controller;

use think\AjaxPage;
use think\Page;
use think\Db;
use think\Url;

class Excel extends Base
{


    //订单导入页面
    public function up_load(){
        return $this->fetch();
    }
    //订单导出功能
    public function download()
    {
        $list = Db::name('order')->limit(5)->order("order_id desc")->select();
        // dump($list);die;
        vendor("PHPExcel176.PHPExcel");
        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getProperties()->setCreator("ctos")
            ->setLastModifiedBy("ctos")
            ->setTitle("Office 2007 XLSX Test Document")
            ->setSubject("Office 2007 XLSX Test Document")
            ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
            ->setKeywords("office 2007 openxml php")
            ->setCategory("Test result file");

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(8);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(10);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(50);
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth(50);

        //设置行高度
        $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(22);

        $objPHPExcel->getActiveSheet()->getRowDimension('2')->setRowHeight(20);

        //set font size bold
        $objPHPExcel->getActiveSheet()->getDefaultStyle()->getFont()->setSize(10);
        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getFont()->setBold(true);

        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('A2:E2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);

        //设置水平居中
        $objPHPExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT);
        $objPHPExcel->getActiveSheet()->getStyle('B')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('D')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('E')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $objPHPExcel->getActiveSheet()->getStyle('F')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

        //合并cell
        $objPHPExcel->getActiveSheet()->mergeCells('A1:J1');

        // set table header content
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '订单数据汇总  时间:' . date('Y-m-d H:i:s'))
            ->setCellValue('A2', '订单ID')
            ->setCellValue('B2', '订单编号')
            ->setCellValue('C2', '收货人')
            ->setCellValue('D2', '价格')
            ->setCellValue('E2', '手机号')
            ->setCellValue('F2', '地址');


        // Miscellaneous glyphs, UTF-8
        for ($i = 0; $i < count($list) - 1; $i++) {
            $objPHPExcel->getActiveSheet(0)->setCellValue('A' . ($i + 3), $list[$i]['order_id']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('B' . ($i + 3), $list[$i]['order_sn']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('C' . ($i + 3), $list[$i]['consignee']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('D' . ($i + 3), $list[$i]['goods_price']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('E' . ($i + 3), $list[$i]['mobile']);
            $objPHPExcel->getActiveSheet(0)->setCellValue('F' . ($i + 3), $list[$i]['address']);
            //$objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':J'.($i+3))->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
            //$objPHPExcel->getActiveSheet()->getStyle('A'.($i+3).':J'.($i+3))->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THIN);
            $objPHPExcel->getActiveSheet()->getRowDimension($i + 3)->setRowHeight(16);
        }


        //  sheet命名
        $objPHPExcel->getActiveSheet()->setTitle('订单汇总表');


        // Set active sheet index to the first sheet, so Excel opens this as the first sheet
        $objPHPExcel->setActiveSheetIndex(0);


        // excel头参数
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="商品表(' . date('Ymd-His') . ').xls"');  //日期为文件名后缀
        header('Cache-Control: max-age=0');

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');  //excel5为xls格式，excel2007为xlsx格式

        $objWriter->save('php://output');


    }


    public function daoru()
    {
        $file = request()->file('excel');
        header("Content-Type:text/html;charset=utf-8");
        $info = $file->validate(['size' => 10000000, 'ext' => 'xlsx,xls,csv'])->move(ROOT_PATH . 'public' . DS . 'excel');
        if ($info) {
            Vendor('PHPExcel176.PHPExcel');
            $exclePath = $info->getSaveName();  //获取文件名
            $file_name = ROOT_PATH . 'public' . DS . 'excel' . DS . $exclePath;   //上传文件的地址
            $objReader = \PHPExcel_IOFactory::createReader('Excel2007');
            $obj_PHPExcel = $objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
            //echo "<pre>";
            $excel_array = $obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
            array_shift($excel_array);  //删除第一个数组(标题);
            $data = [];
            $i=0;
            $j=0;
            $h=0;

            foreach ($excel_array as $k => $v) {
                if (!empty($v[2])) {
                    $mobile = $v[2];
                    if (check_mobile($mobile)) {
                        $is_validated = 1;
                        $map['mobile_validated'] = 1;
                        $map['nickname'] = $map['mobile'] = $mobile; //手机注册
                    }
                    //验证是否存在用户名
                    $user = get_user_info($mobile, 2);
                    if ($user) {
                        $user_id = $user['user_id'];
                    } else {
                        $password = "123456";
                        $map['password'] = encrypt($password);
                        $map['reg_time'] = time();
                        //$map['parent_id'] = cookie('parent_id'); // 推荐人id

                        $map['token'] = md5(time() . mt_rand(1, 99999));

                        $user_id = Db::name('users')->insertGetId($map);
                    }

                    $data["order_sn"] = date('YmdHis') . rand(1000, 9999); // 订单编号;
                    $data["user_id"] = $user_id;//用户id
                    $data["order_status"] = 4;//订单状态 4已完成
                    $data["shipping_status"] = 1;//发货状态
                    $data["pay_status"] = 1;//支付状态
                    $data["consignee"] =trim($v[1]) ;//收货人
                    $data["province"] = $this->sel_cityid($v[3]);//省id
                    $data["city"] = $this->sel_cityid($v[4]);//市id
                    $data["district"] = $this->sel_cityid($v[5]);//区id
                    $data["address"] = trim($v[6]);//地址详情
                    $data["mobile"] =trim($v[2]) ;//手机号
                    $data["shipping_code"] =trim($v[13]);//物流code
                    $data["shipping_name"] =trim( $v[12]);//物流名称
                    $data["pay_code"] = "xianxia";//支付code
                    $data["pay_name"] = "线下支付";//支付方式名称
                    $data["goods_price"] = $v[8]*$v[9];//商品总价
                    $data["order_amount"] = $v[8]*$v[9];//应付款金额
                    $data["total_amount"] = $v[8]*$v[9];//订单总价
                    $data["add_time"] = 0;//下单时间
                    $data["shipping_time"] = 0;//最后新发货时间
                    $data["confirm_time"] = 0;//收货确认时间
                    $data["pay_time"] = 0;//支付时间
                    $data["supplier_id"] = '41';//商家id
                    $data["supplier_name"] = "一礼通自营";//商家名称

                    $order_id = Db::name("Order")->insertGetId($data);
                    $i++;
                    //订单商品信息插入
                    if ($order_id) {
                        $goods = Db::name('Goods')->where(array('goods_id' => $v['14'], 'is_on_sale' => '1'))->find();
                        if ($goods) {
                            $data2['order_id'] = $order_id; // 订单id
                            $data2['goods_id'] = $goods['goods_id']; // 商品id
                            $data2['goods_name'] = $goods['goods_name']; // 商品名称
                            $data2['goods_sn'] = $goods['goods_sn']; // 商品货号
                            $data2['goods_num'] = $v['9']; // 购买数量
                            $data2['market_price'] = $goods['market_price']; // 市场价
                            $data2['goods_amount'] = $v['9'] * $v[8];
                            $data2['goods_price'] = $v[8]; // 商品价
                            $data2['spec_key'] = ""; // 商品规格
                            $data2['spec_key_name'] = ""; // 商品规格名称
                            $data2['member_goods_price'] = 0; // 会员折扣价
                            $data2['discount_price'] = $goods['cost_price']; // 成本价
                            $data2['give_integral'] = 0; // 购买商品赠送积分
                            $data2['prom_type'] = 0; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠
                            $data2['prom_id'] = 0; // 活动id
                            $data2['commission_price'] = 0; //推广佣金
                            $data2['goods_thumb'] = $goods['goods_thumb']; //缩略图
                            $order_goods_id = Db::name("OrderGoods")->insertGetId($data2);
                            $j++;

                        }
                    }
                    //项目信息预约信息插入
                    if ($order_id) {
                        $mobile = trim($v['2']);
                        $userid =Db::name('users')->where(array('mobile' =>$mobile ))->find();
                        if ($userid) {
                            $data_yujing['floorInput'] =  $v[8]; //楼栋
                            $data_yujing['roomNum'] =  $v[8]; // 房间号
                            $data_yujing['customerName'] =  $v[1]; // 姓名
                            $data_yujing['tel'] = $mobile; //手机号
                            $order_goods_id = Db::name("hd_yujing")->insertGetId($data_yujing);
                            $h++;

                        }
                    }


                }

            }
            $this->ajaxReturn(array('msg' => '操作成功'.$i));
        } else {
            // 上传失败获取错误信息
            echo $file->getError();
        }
    }

    public function daorucs()
    {
        $time = time();
        header("Content-Type:text/html;charset=utf-8");
        $name=$_FILES["excel"]["name"];
        $suffixName = explode('.', $name)[1];//后缀名
        $filePath = ROOT_PATH . 'public' . DS . 'excel' . DS .date("Ymd",$time). DS . $time.".".$suffixName;   //上传文件的地址
        $url = 'public/excel/'.date("Ymd",$time);
        $urls = '/public/excel/'.date("Ymd",$time).'/'.$time.".".$suffixName;
        if (!file_exists($url)){
            mkdir($url,0777,true);
        }//如果地址不存在，创建地址
        if ($suffixName == "xls" || $suffixName == "xlsx") {
            if (!move_uploaded_file($_FILES["excel"]["tmp_name"], $filePath)) {
                return array('status'=>-1,'msg'=>'上传文件失败');
            } else {
                return array('status'=>1,'msg'=>'上传文件成功','url'=>$urls);
            }
        } else {
            return array('status'=>-2,'msg'=>'上传文件格式错误');
        }
    }

    public function sel_cityid($name)
    {
        $where['name'] = ['like', "%$name%"];
        $order_list = Db::name('region')->where($where)->value("id");
        if ($order_list) {
            return $order_list;
        } else {

            header("Content-Type:text/html;charset=utf-8");
            $nametwo = mb_substr($name, 0, 2, 'utf-8');
            $wheres['name'] = ['like', "%$nametwo%"];
            $order_list1 = Db::name('region')->where($wheres)->value("id");
            if($order_list1){
                return $order_list1;
            }else{
                $namethree = mb_substr($name, 0, 1, 'utf-8');
                $wheres['name'] = ['like', "%$namethree%"];
                $order_list2 = Db::name('region')->where($wheres)->value("id");
                return $order_list2;
            }

        }

    }

}