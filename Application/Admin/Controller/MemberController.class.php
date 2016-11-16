<?php
namespace Admin\Controller;
use Think\Controller;
use Think\Model;
class MemberController extends CommonController {
	protected $ROOT_PATH = "/Uploads/Members/";
	public function index($pageSize = 25, $pageNum = 1) {
		self::select($pageSize, $pageNum);
	}
	
	public function today($pageSize = 25, $pageNum = 1) {
		$filter = 'date(time) = curdate()';
		self::select($pageSize, $pageNum, $filter);
	}
	
	private function select($pageSize = 25, $pageNum = 1, $filter = null) {
		// 分页
		$filter['is_false'] = 0;
		$db = M('member');
		$count = $db->where($filter)->count();
		if(!$pageSize) {
			$pageSize = 25;
		}
		$pageSize = 10;
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
		
		
		
		$list = $db->where($filter)->page($pageNum, $pageSize)->order(" time desc ")->select();
		$this->assign('list',$list);// 模板变量赋值
		
		$this->assign('title', '会员列表');
		$this->assign('pid', 'mbmgr');
		$this->assign('mid', 'mblst');
		$this->display("index");
	}
	
	public function add() {
		if(IS_POST) {
			$_POST['password'] = md5($_POST['password']);
			$db = M('member');
			$db->create();
			if($db->add() != false) {
				$this->success('操作成功', U('index', '', ''));
			} else {
				$this->error('数据错误');
			}
		} else {
			$this->assign('action', U('add', '', ''));
			$this->assign('pid', 'mbmgr');
			$this->assign('mid', 'addmb');
			$this->assign('title', '添加会员');
			$this->display();
		}
	}
	public function edit($uid = null) {
		if(IS_POST) {
			if($_POST['password']) {
				$_POST['password'] = md5($_POST['password']);
			} else {
				unset($_POST['password']);
			}
			$db = M('member');
			$db->create();
			$db->save();
			$this->success('操作成功', U('index', '', ''));
		} else {
			$db = M('member');
			$data = $db->find($uid);
			$this->assign('data', $data);
			$this->assign('action', U('edit', '', ''));
			$this->assign('title', '修改会员');
			$this->assign('pid', 'mbmgr');
			$this->assign('mid', 'addmb');
			$this->display('add');
		}
	}
	
	public function remove($uid = 0) {
		$db = M('member');
		$ret = $db->delete($uid);
		if($ret > -1) {
			$this->success('操作成功');
		} else {
			$this->error('数据错误');
		}
	}

	public function find() {
		if(IS_POST) {
			$type=I("type");
			$value=I("inputValue");
			switch ($type) {
				case 0:
					$filter['uid'] = $value;
			  		break;  
				case 1:
					$filter['username'] = array('LIKE', '%'.$value.'%');
			 		break;
				case 2:
					$filter['email'] = array('LIKE', '%'.$value.'%');
			 		break;
				case 3:
					$filter['mobile'] = array('LIKE', '%'.$value.'%');
			 		break;
				default:
					$filter = null;
					break;
			}
//			echo $value;
//			echo $type;
			if($filter && $value) {
				$db = M('member');
				$list = $db->where($filter)->order("time desc")->limit(100)->select();
				$this->assign('list',$list);// 模板变量赋值
			}
		}
		
		$this->assign('title', '查找会员');
		$this->assign('pid', 'mbmgr');
		$this->assign('mid', 'fdmb');
		$this->display();
	}
	public function setauto(){
		$this->assign('title', '查找会员');
		$this->assign('pid', 'usrmgr');
		$this->assign('mid', 'setauto');
		$this->display();
	}

	/*余额排名*/
	public function moneysort($pageSize = 20, $pageNum = 1) {
		$map=I('get.map');
		if(empty($map)){
			$map=S('moneysort');
		}
		S('moneysort',$map);
		switch ($map) {
			case 'money':
				$order="money desc";
				break;
			case 'score':
				$order="score desc";
				break;
			case 'jingyan':
				$order="jingyan desc";
				break;
			case 'level':
				$order="level desc";
				break;
			default:
				$order="money desc";
				break;
		}
		// 分页
		$db = M('member');
		$filter['is_register']=1;
		$filter['is_false']=0;
		$count = $db->where($filter)->count();
		if(!$pageSize) {
			$pageSize = 25;
		}
		$pageSize = 20;
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
		
		$list = $db->where($filter)->page($pageNum, $pageSize)->order($order)->select();
		$this->assign('list',$list);// 模板变量赋值
		// $this->assign('title', '余额排名');
		$this->assign('pid', 'mbmgr');
		$this->assign('mid', 'moneysort');
		$this->display();
	}
	/*消费排名*/
	public function buysort($pageSize = 10, $pageNum = 1) {
		$time1=microtime(true)."||";
		echo $time1;
		$date=I('get.date');
		$type=I('get.type');
		$keyword=I('get.keyword');
		if(empty($keyword)){
			S('keyword',null);
		}
		if(empty($date)){
			$date=S('buydate');
			if(empty($date))
			{
				$date=date('Y-m-d',time());
			}
		}
		S('buydate',$date);
		if(empty($type)){
			$type=S('buytype');
		}
		S('buytype',$type);
		// echo S('buytype');
		switch ($type) {
			case 'money':
				$order="mmoney desc";
				break;
			case 'score':
				$order="sscore desc";
				break;
			case 'third':
				$order="tthird desc";
				break;
			default:
				$order="mmoney desc";
				break;
		}
		S('keyword',$keyword);
		$this->keyword=S('keyword');
		$date1=$date." 00:00:00";
		$date2=$date." 23:59:59";
		$filter['time']=array('between',"$date1,$date2");
		$filter['status']=1;
		$filter['type']=array('in','11,1,0,-1');
		$filter['member.is_false']=0;
		$filter['member.username|member.mobile']=array('like','%'.$keyword.'%');
		// 分页
		$db = D('Common/AccountMember','VModel');
		$count = $db->where($filter)->group('uid')->count();
		if(!$pageSize) {
			$pageSize = 25;
		}
		$pageSize = 100;
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
		
		$list = $db->where($filter)->field('uid,sum(account.`money`) as mmoney,sum(account.`score`) as sscore,sum(account.`third`) as tthird,username,mobile,member.time as reg_time')->page($pageNum, $pageSize)->group('uid')->order($order)->select();
		$total_money=0;
		$total_third=0;
		foreach ($list as $key => $value) {
			$total_money+=$value['mmoney'];
			$total_third+=$value['tthird'];
		}
		$time2=microtime(true)."";
		echo $time2;
		$run=$time2-$time1;
		echo "执行时间：".$run."秒";
		echo "<br>总余额：$total_money";
		echo "总第三方消费：$total_third";
		// print_r($list);
		// echo $db->getlastsql();
		$this->assign('list',$list);// 模板变量赋值
		$this->buydate=S('buydate');
		$this->buytype=S('type');
		$this->assign('pid', 'mbmgr');
		$this->assign('mid', 'buysort');
		$this->display();
	}	

	public function get_address($pageSize = 10, $pageNum = 1)
	{
		$ad = I('inputValue');
		if(IS_POST)
		{	
			if($ad !=null)
			{
				S('a',null );
				S('a',$ad );
				S('b',1);
				$filter['_string'] = "login_ip is not NULL and login_ip !='0.0.0.0' and address like '%".S('a')."%'";
			}
			else
			{
				S('b',0);
				$filter['_string'] = "login_ip is not NULL and login_ip !='0.0.0.0'";
			}
		}
		else if(S('b')==1)
		{
			$filter['_string'] = "login_ip is not NULL and login_ip !='0.0.0.0' and address like '%".S('a')."%'";
		}
		else
		{
			$filter['_string'] = "login_ip is not NULL and login_ip !='0.0.0.0'";
		}
		$filter['is_false'] =0;
		$db = M('member');
		$field = "uid,username,mobile,login_time,login_ip,time,address";

		$count = $db->where($filter)->count();
		if(!$pageSize) {
			$pageSize = 25;
		}
		$pageSize = 20;
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

		$records = $db->where($filter)->field($field)->page($pageNum,$pageSize)->order('login_time desc')->select();
		$this->assign('list',$records);
		$this->assign('pid', 'mbmgr');
		$this->assign('mid', 'get_address');
		$this->display();
	}	

	public function get_mobile()
	{
		if(IS_POST)
		{
			$address =  S('a');
			if($address!=null)
			{
				$filter['_string'] = "login_ip is not NULL and login_ip !='0.0.0.0' and address like '%".$address."%'";
				$db = M('member');
				$records = $db->where($filter)->order('login_time desc')->getField("mobile",true);
				$records=implode(';<br>',$records);
				$this->ajaxreturn($records); 
			}
			else
			{
				$this->ajaxreturn("请输入地址"); 
			}
			S('a',null);
		}
	}

		/*粉丝排行榜*/
		public function fan($pageSize = 20, $pageNum = 1)
		{	
			$this->sort=1;
			$db = M('member');
			$map['_string']='`yaoqing` is not null and `yaoqing` <> 0';
			$count=$db->where($map)->field('count(*) as num,yaoqing')->group('yaoqing')->select();
			$count=count($count);
			// echo $db->getlastsql();
			// print_r(count($count));die;
			if(!$pageSize) {
				$pageSize = 25;
			}
			$pageSize = 20;
			$pageNum = intval($pageNum);
			$pageCount = ceil($count / $pageSize);
			if($pageNum > $pageCount) {
				$pageNum = $pageCount;
			}
			$rs=$db->where($map)->order('num desc')->field('count(*) as num,yaoqing')->group('yaoqing')->page($pageNum,$pageSize)->select();
			foreach ($rs as $key => $value) {
				$uids[]=$value['yaoqing'];
			}
			unset($map);
			$map['uid']=array('in',$uids);
			$list=$db->where($map)->select();
			foreach ($list as $key2 => $value2) {
				foreach ($rs as $key3 => $value3) {
					if($value3['yaoqing']==$value2['uid']){
						$list[$key2]['num']=$value3['num'];
						$new_array[$key3]=$list[$key2];
					}
				}
			}
			ksort($new_array);
			$this->list=$new_array;
			$this->assign('pageSize', $pageSize);
			$this->assign('pageNum', $pageNum);
			$this->assign('count', $count);
			$this->assign('pageCount', $pageCount);
			$this->assign('minPageNum', floor(($pageNum-1)/10.0) * 10 + 1);
			$this->assign('maxPageNum', min(ceil(($pageNum)/10.0) * 10 + 1, $pageCount));
			$this->assign('pid', 'mbmgr');
			$this->assign('mid', 'fan');
			$this->display();
		}
		

		/*粉丝排行榜*/
		public function fan2($pageSize = 20, $pageNum = 1)
		{	
			$time1=microtime(true)."||";
			echo $time1;
			$this->sort=2;
			$db = M('member');
			$map['_string']='`yaoqing` is not null and `yaoqing` <> 0';
			$count=$db->where($map)->field('count(*) as num,yaoqing')->group('yaoqing')->select();
			$count=count($count);
			// echo $db->getlastsql();
			// print_r(count($count));die;
			if(!$pageSize) {
				$pageSize = 25;
			}
			$pageSize = 20;
			$pageNum = intval($pageNum);
			$pageCount = ceil($count / $pageSize);
			if($pageNum > $pageCount) {
				$pageNum = $pageCount;
			}
			$rs=$db->where($map)->order('num desc')->field('count(*) as num,yaoqing')->group('yaoqing')->page($pageNum,$pageSize)->select();
			foreach ($rs as $key => $value) {
				$uids[]=$value['yaoqing'];
			}
			unset($map);
			$map['uid']=array('in',$uids);
			$list=$db->where($map)->select();
			foreach ($list as $key2 => $value2) {
				foreach ($rs as $key3 => $value3) {
					if($value3['yaoqing']==$value2['uid']){
						$list[$key2]['num']=$value3['num'];
						$new_array[$key3]=$list[$key2];
					}
				}
			}
			ksort($new_array);
			unset($list);
			$list=$new_array;
			foreach ($list as $k => $v) {
				$list[$k]['num1']=0;
				$list[$k]['num2']=0;
				$list[$k]['num3']=0;
				$list[$k]['no1']=$db->where(array('yaoqing'=>$v['uid'],'is_false'=>0))->getField('uid',true);
				$list[$k]['num1']=count($list[$k]['no1']);
				$list2=$list[$k]['no1'];
				foreach ($list2 as $k2 => $v2) {
					$list[$k]['no2'][$k2]=$db->where(array('yaoqing'=>array('in',$v2),'is_false'=>0))->getField('uid',true);
					$list[$k]['num2']+=count($list[$k]['no2'][$k2]);
					$list3=$list[$k]['no2'][$k2];
					foreach ($list3 as $k3 => $v3) {
						$list[$k]['no3'][$k3]=$db->where(array('yaoqing'=>array('in',$v3),'is_false'=>0))->getField('uid',true);
						$list[$k]['num3']+=count($list[$k]['no3'][$k3]);
					}
				}
				$list[$k]['total']=$list[$k]['num1']+$list[$k]['num2']+$list[$k]['num3'];
				$list[$k]['num']=$list[$k]['num1'];
				// print_r($list2);
			}
			$this->list=$list;
			$this->assign('pageSize', $pageSize);
			$this->assign('pageNum', $pageNum);
			$this->assign('count', $count);
			$this->assign('pageCount', $pageCount);
			$this->assign('minPageNum', floor(($pageNum-1)/10.0) * 10 + 1);
			$this->assign('maxPageNum', min(ceil(($pageNum)/10.0) * 10 + 1, $pageCount));
			$this->assign('pid', 'mbmgr');
			$this->assign('mid', 'fan');
			$time2=microtime(true)."";
			echo $time2;
			echo "<br>";
			$run=$time2-$time1;
			echo "执行时间：".$run."秒";
			$this->display('fan');
		}
}