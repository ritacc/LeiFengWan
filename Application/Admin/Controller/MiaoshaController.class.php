<?php
namespace Admin\Controller;
class MiaoshaController extends GoodsBaseController {
	protected $_config = array(
		'type'			=> 1,
		'listTitle'		=> '秒杀商品列表',
		'addTitle'		=> '添加秒杀商品',
		'editTitle'		=> '编辑秒杀商品',
		'listMid'		=> 'mslst',
		'addMid'			=> 'addms',
	);
	
	public function index($pageSize = 50, $pageNum = 1) {
		$this->assign('type', $this->_config['type']);
		$map['type'] = $this->_config['type'];
		// 分页
		$db = D('miaosha');
		$count = $db->where($map)->count();
		if(!$pageSize) {
			$pageSize = 50;
		}
		$pageNum = intval($pageNum);
		$pageCount = ceil($count / $pageSize);
		if($pageNum > $pageCount) {
			$pageNum = $pageCount;
		}
		$this->assign('pageSize', $pageSize);
		$this->assign('pageNum', $pageNum);
		$this->assign('count', $count);
		$this->assign('pageCount', $pageCount);
		$this->assign('minPageNum', floor(($pageNum-1)/10.0) * 10 + 1);
		$this->assign('maxPageNum', min(ceil(($pageNum)/10.0) * 10 + 1, $pageCount));
		
		$list = $db->where($map)->order('sort desc,time desc')->relation(true)->page($pageNum, $pageSize)->select();
		$auto_id=S('auto_id');
		$quick_id=S('quick_id');
		foreach ($list as $key => $value) {
//			if(in_array($value['gid'], $auto_id)){
//				$list[$key]['in_auto']=1;
//			}
//			else{
//				$list[$key]['in_auto']=0;
//			}
			if(in_array($value['gid'], $quick_id)){
				$list[$key]['in_quick']=1;
			}
			else{
				$list[$key]['in_quick']=0;
			}
			if($value['status']!=1){
				$liat[$key]['in_auto']=0;
			}

		}
		$this->assign('list',$list);// 模板变量赋值
		
		$this->assign('title', $this->_config['lstTitle']);
		$this->assign('addTitle', $this->_config['addTitle']);
		$this->assign('pid', 'gdmgr');
		$this->assign('mid', $this->_config['listMid']);
		$this->display('Miaosha/index');
	}
	
	public function add() {
		if(IS_POST) {
			$jishi = intval($_POST['jishijiexiao']);
			$money = floatval($_POST['money']);
			$danjia = floatval($_POST['danjia']);
			$_POST['zongrenshu'] = ceil($money / $danjia);
			$_POST['shengyurenshu'] = $_POST['zongrenshu'];
			$zongrenshu = (int)$_POST['zongrenshu'];
			
			$db = M('miaosha');
			$data = $db->create();
			if($data) {
				$status = -1;
				$result = $db->add(); // 写入数据到数据库 
				if($result > 0) {
					$status = 1;
				}
				
				if($status == 1) {
					self::saveImages($result, $this->_config['type']);
					$this->success('操作成功', U('index', '', ''));
				} else {
					$this->ajaxReturn('数据错误');						
				}
 			} else {
				$this->ajaxReturn('数据创建错误');
			}
		} else {
			$this->assign('type', $this->_config['type']);
			
			$cdb = M('category');
			$categories = $cdb->select();
			$this->assign('allCategories', $categories);
			$this->assign('action', U('add','',''));
			$this->assign('categoryAction', U('Category/brands', '', ''));
			$this->assign('uploader', U('upload', '', ''));
			$this->assign('pid', 'gdmgr');
			$this->assign('mid', $this->_config['addMid']);
			$this->assign('title', $this->_config['addTitle']);
			$this->assign('status', 0);
			$this->display('Miaosha/add');
		}
	}
	
	public function edit($gid = null) {
		if(IS_POST) {
			$jishi = intval($_POST['jishijiexiao']);
			$money = floatval($_POST['money']);
			$danjia = floatval($_POST['danjia']);
			
			$db = M('miaosha');
			$data = $db->create();
			if($data) {
				$result = $db->save(); // 写入数据到数据库 
				self::saveImages($_POST['gid'], $this->_config['type']);
				$this->success('操作成功', U('index', '', ''));
			} else {
				$this->ajaxReturn('数据创建错误');
			}
		} else {
			$this->assign('type', $this->_config['type']);
			
			$db = D('miaosha');
			$map['gid'] = $gid;
			$map['type'] = $this->_config['type'];
			$data = $db->relation(true)->find($gid);
			$data['content'] = htmlspecialchars_decode(html_entity_decode($data['content']));
			$this->assign('data', $data);
			
			$imgdb = M('GoodsImages');
			$imgmap['gid'] = $gid;
			$images = $imgdb->where($imgmap)->select();
			$this->assign('images', $images);
			
			$cdb = M('category');
			$categories = $cdb->select();
			$this->assign('allCategories', $categories);
			
			$bdb = D('category');
			$bdata = $bdb->relation(true)->find($data['cid']);
			$this->assign('allBrands', $bdata['brands']);
			
			$this->assign('action', U('edit','',''));
			$this->assign('categoryAction', U('Category/brands', '', ''));
			
			$this->assign('uploader', U('upload', '', ''));
			$this->assign('pid', 'gdmgr');
			$this->assign('mid', $this->_config['addMid']);
			$this->assign('title', $this->_config['editTitle']);
			$this->assign('status', $data['status']);
			
			$this->display('Miaosha/add');
		}
	}
	
	public function remove($gid = 0) {
		$db = M('Miaosha');
		$ret = $db->delete($gid);
		if($ret > -1) {
			$this->success('操作成功');
		} else {
			$this->error('数据错误');
		}
	}
	
	public function history($gid) {
		$db = D('Miaosha');
		$goods = $db->relation(true)->find($gid);
		$this->assign('goods', $goods);
		
		$mdb = M('MiaoshaHistory');
		$mmap['gid'] = $gid;
		$list = $mdb->where($mmap)->order('qishu desc')->select();
		$this->assign('list', $list);
		$this->assign("pid", "gdmgr");
		$this->assign("title", "往期");
		$this->display();
	}
	public function add_auto(){
		$id=I('post.gid');
		if(!IS_AJAX)$this->error('非法操作');
//		$auto_id=S('auto_id');
//		if(empty($auto_id)){
//			$auto_id=array();
//		}
//		if(!in_array($id, $auto_id)){
//			array_push($auto_id, $id);
//			S('auto_id',$auto_id);
//		}
		$rs=M('miaosha')->where(array('gid'=>$id))->setField('in_auto',1);
		
		$this->ajaxReturn(array('status'=>1,'info'=>' 添加成功'));
	}
	
	public function delete_auto(){
		$id=I('post.gid');
		if(!IS_AJAX)$this->error('非法操作');
//		$auto_id=S('auto_id');
//		if(empty($auto_id)){
//			$auto_id=array();
//		}
//		if(!in_array($id, $auto_id)){
//			array_push($auto_id, $id);
//			S('auto_id',$auto_id);
//		}
		$rs=M('miaosha')->where(array('gid'=>$id))->setField('in_auto',1);
		
		$this->ajaxReturn(array('status'=>1,'info'=>' 添加成功'));
	}

	/*添加快速开奖商品*/
	public function add_quick(){
		$id=I('post.gid');
		if(!IS_AJAX)$this->error('非法操作');
		$auto_id=S('auto_id');
		if(in_array($id, $auto_id)){
			$this->ajaxReturn(array('status'=>0,'info'=>'该商品已经在自动下单列表'));
		}
		$rs=M('miaosha')->where(array('gid'=>$id))->setField('is_quick',1);
		if(!$rs)$this->ajaxReturn(array('status'=>0,'info'=>'添加失败，请稍后重试'));
		$this->ajaxReturn(array('status'=>1,'info'=>' 添加成功'));
	}

	/*取消快速开奖商品*/
	public function del_quick(){
		$id=I('post.gid');
		if(!IS_AJAX)$this->error('非法操作');
		$rs=M('miaosha')->where(array('gid'=>$id))->setField('is_quick',0);
		if(!$rs)$this->ajaxReturn(array('status'=>0,'info'=>'取消失败，请稍后重试'));
		$this->ajaxReturn(array('status'=>1,'info'=>' 取消成功'));
	}
}