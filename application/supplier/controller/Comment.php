<?php

namespace ylt\supplier\controller;

use think\AjaxPage;
use think\Page;
use think\Db;
use think\Request;

class Comment extends Base {

		
    public function index(){
        return $this->fetch();
    }

    public function detail(){
		$supplier_id = session('supplier_id');
        $id = I('get.id/d');
        $res = Db::name('comment')->where(array('comment_id'=>$id,'supplier_id'=>$supplier_id))->find();
        if(!$res){
            exit($this->error('不存在该评论'));
        }
        if(IS_POST){
            $add['parent_id'] = $id;
            $add['content'] = I('post.content');
            $add['goods_id'] = $res['goods_id'];
            $add['add_time'] = time();
            $add['username'] = 'admin';

            $add['is_show'] = 1;

            $row =  Db::name('comment')->insert($add);
            if($row){
                $this->success('添加成功');
            }else{
                $this->error('添加失败');
            }
            exit;

        }
        $reply = Db::name('comment')->where(array('parent_id'=>$id,'supplier_id'=>$supplier_id))->select(); // 评论回复列表
         
        $this->assign('comment',$res);
        $this->assign('reply',$reply);
        return $this->fetch();
    }


    public function del(){
        $id = I('get.id/d');
        $row = Db::name('comment')->where(array('comment_id'=>$id))->delete();
        if($row){
            $this->success('删除成功');
        }else{
            $this->error('删除失败');
        }
    }

    public function op(){
        $type = I('post.type');
        $selected_id = I('post.selected/a');
        if(!in_array($type,array('del','show','hide')) || !$selected_id)
            $this->error('非法操作');
        $where['comment_id'] = array('IN', $selected_id);
        if($type == 'del'){
            //删除回复
            $where['parent_id'] = ['IN',$selected_id];
            $row = Db::name('comment')->whereOr($where)->delete();
//            exit(DB::getLastSql());
        }
        if($type == 'show'){
            $row = Db::name('comment')->where($where)->update(array('is_show'=>1));
        }
        if($type == 'hide'){
            $row = Db::name('comment')->where($where)->update(array('is_show'=>0));
        }
        if(!$row)
            $this->error('操作失败');
        $this->success('操作成功');

    }

    public function ajaxindex(){
		$supplier_id = session('supplier_id');
        $model = Db::name('comment');
        $username = I('nickname','','trim');
        $content = I('content','','trim');
        $where['parent_id'] = 0;
		$where['supplier_id'] = $supplier_id;
        if($username){
            $where['username'] = $username;
        }
        if ($content) {
            $where['content'] = ['like', '%' . $content . '%'];
        }
        $count = $model->where($where)->count();
        $Page = $pager = new AjaxPage($count,16);
        $show = $Page->show();
                
        $comment_list = $model->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        if(!empty($comment_list))
        {
            $goods_id_arr = get_arr_column($comment_list, 'goods_id');
            $goods_list = Db::name('Goods')->where("goods_id", "in" , implode(',', $goods_id_arr))->column("goods_id,goods_name");
        }
        $this->assign('goods_list',$goods_list);
        $this->assign('comment_list',$comment_list);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);// 赋值分页输出
        return $this->fetch();
    }
    
    public function ask_list(){
    	return $this->fetch();
    }
    
    public function ajax_ask_list(){
    	$model = Db::name('goods_consult');
    	$username = I('username','','trim');
    	$content = I('content','','trim');
    	$where=' parent_id = 0';
    	if($username){
    		$where .= " AND username='$username'";
    	}
    	if($content){
    		$where .= " AND content like '%{$content}%'";
    	}
        $count = $model->where($where)->count();        
        $Page  = $pager = new AjaxPage($count,10);
        $show  = $Page->show();            	
    	
        $comment_list = $model->where($where)->order('add_time DESC')->limit($Page->firstRow.','.$Page->listRows)->select(); 
    	if(!empty($comment_list))
    	{
    		$goods_id_arr = get_arr_column($comment_list, 'goods_id');
    		$goods_list = Db::name('Goods')->where("goods_id", "in", implode(',', $goods_id_arr))->column("goods_id,goods_name");
    	}
    	$consult_type = array(0=>'默认咨询',1=>'商品咨询',2=>'支付咨询',3=>'配送',4=>'售后');
    	$this->assign('consult_type',$consult_type);
    	$this->assign('goods_list',$goods_list);
    	$this->assign('comment_list',$comment_list);
    	$this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$pager);// 赋值分页输出
    	return $this->fetch();
    }
    
    public function consult_info(){
    	$id = I('get.id/d');
    	$res = Db::name('goods_consult')->where(array('id'=>$id))->find();
    	if(!$res){
    		exit($this->error('不存在该咨询'));
    	}
    	if(IS_POST){
    		$add['parent_id'] = $id;
    		$add['content'] = I('post.content');
    		$add['goods_id'] = $res['goods_id'];
            $add['consult_type'] = $res['consult_type'];
    		$add['add_time'] = time();    		
    		$add['is_show'] = 1;   	
    		$row =  Db::name('goods_consult')->insert($add);
    		if($row){
    			$this->success('添加成功');
    		}else{
    			$this->error('添加失败');
    		}
    		exit;    	
    	}
    	$reply = Db::name('goods_consult')->where(array('parent_id'=>$id))->select(); // 咨询回复列表
    	$this->assign('comment',$res);
    	$this->assign('reply',$reply);
    	return $this->fetch();
    }

    public function ask_handle()
    {
        $type = I('post.type');
        $selected_id = I('post.selected/a');
        if (!in_array($type, array('del', 'show', 'hide')) || !$selected_id)
            $this->error('操作完成');

        $selected_id = implode(',', $selected_id);
        if ($type == 'del') {
            //删除咨询
            $row = Db::name('goods_consult')->where('id', 'IN', $selected_id)->whereOr('parent_id', 'IN', $selected_id)->delete();
        }
        if ($type == 'show') {
            $row = Db::name('goods_consult')->where('id', 'IN', $selected_id)->update(array('is_show' => 1));
        }
        if ($type == 'hide') {
            $row = Db::name('goods_consult')->where('id', 'IN', $selected_id)->update(array('is_show' => 0));
        }
        $this->success('操作完成');
    }
}