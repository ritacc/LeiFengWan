<?php
namespace Home\Controller;
use Think\Controller;
/**
 * 支付界面控制器
 */
class PayController extends Controller {

// 101: 余额不足
// 102: 积分不足
// 103: 支付金额不对
// 104: 修改预支付订单失败
// 105: 扣除余额失败
// 106: 增加消费积分失败
// 107: 修改预付订单状态为已支付－修改失败
// 108: 无效订单
// 201: 秒杀商品已经完结
// 202: 生成秒杀客户随即码失败
// 203: 计算秒杀结果失败
// 204: 获取中奖用户失败
// 205: 保存秒杀历史失败
// 206: 保存秒杀主表失败
// 207: 增加秒杀记录失败
// 301: 拍卖商品不允许立即价
// 302: 拍卖商品已结束
// 303: 增加出价纪录失败
// 304: 立即拍下商品失败
// 305: 归还拍卖保证金到个人余额失败
// 306：归还拍卖保证金到个人余额纪录资金流水失败
// 401: 增加保证金报名人数失败
// 402: 增加保证金纪录失败
// 403: 退还保证金记录失败
// 404: 扣除保证金个人余额失败
// 504 修改拍卖支付状态失败
// 505: 归还拍卖保证金到个人余额失败
// 506：归还拍卖保证金到个人余额纪录资金流水失败

	public function index($payid){
		if(is_login()) {
			$uid = get_temp_uid();
			if(empty($payid)) {
				$payid = I('payid');
			}
			$db = D('Home/AccountGoods');
			$map['payid'] = $payid;
			$map['uid'] = $uid;
			$list = $db->where($map)->relation(true)->select();
			if(!empty($list)) {
				$this->assign('list', $list);
				$total = $this->total($list);
				$this->assign('total', $total);
			}
			
//			echo $db->getLastSql();
//			echo dump($list);
			
			$db = M('member');
			$user = $db->field('money,score')->find($uid);
			$score = intval($user['score']);
			$user['_score'] = $score;
			$score = floor($score / 100) * 100;
			$user['score'] = $score;
			$this->assign('account', $user);
	    		$this->assign('title', '结算支付');
			$this->assign('payid', $payid);
			layout(false);
			$this->display();
		} else {
			$this->redirect('Home/Person/login/'.encode('Pay/index'));
		}
    }
	
	function total($list) {
		$total = 0;
//		echo dump($list);
		foreach($list as $cart) {
			if($cart['type'] == 3) {
				if($cart['isbz'] == 1) { // 保证金
					$total += intval($cart['paimai']['baozhengjin']);
				} else if($cart['isbz'] == -1) { // 中标支付
					$total += intval($cart['paimai']['zuigaojia']);
				} else {
					$total += intval($cart['paimai']['lijijia']);
				}
			} else {
				$total += intval($cart['good']['danjia']) * intval($cart['count']) ;
			}
		}
		return $total;
	}
	
	public function baozhengjin($gid) {
		$db = M('paimai');
		$data = $db->field('gid, title, baozhengjin, thumb')->find($gid);
		$this->assign('data', $data);
		$this->assign('total', $data['baozhengjin']);
		
		$user = session('user');
		$uid = $user['uid'];
		$db = M('member');
		$user = $db->field('money,score')->find($uid);
		$this->assign('account', $user);
		
    		$this->assign('title', '保证金');
		layout(false);
		$this->display();
	}
	
	/**
	 * 预创建立即拍卖订单
	 */
	 public function createPreLJPPay($gid) {
	 	$this->createPreBZJPay($gid, 0);
	 }
	 
	/**
	 * 预创建中标拍卖订单
	 */
	 public function createPreZBPay($gid) {
	 	$this->createPreBZJPay($gid, -1);
	 }
	
	/**
	 * 预创建保证金订单
	 */
	 public function createPreBZJPay($gid, $isbz = 1) {
	 	if(is_login()) {
			$adb = M('account');
			$agdb = M('AccountGoods');
			$result['status'] = -1;
			$adb->startTrans();
			$uid = get_temp_uid();
			$payid = \Org\Util\String::keyGen();
			$accountData = array(
				'payid'			=> $payid,
				'uid'			=> $uid,
				'type'			=> -1,
				'status'			=> 0
			);
			if($adb->add($accountData) !== FALSE) {
				$map['uid'] = $uid;
				$result['status'] = 0;
				$result['message'] = '创建$accountData成功';
				
				$agddata = array(
					'payid'			=> $payid,
					'uid'			=> $uid,
					'gid'			=> $gid,
					'type'			=> 3,
					'flag'			=> 1,
					'count'			=> 1,
					'isbz'			=> $isbz, // 是保证金
				);
				if($agdb->add($agddata) === FALSE) {
					$result['status'] = -1;
				}
			}
			
			if($result['status'] == 0) {
				$adb->commit();
				$result['rst'] = $payid;
			} else {
				$adb->rollback();
			}
		} else { // 未登录
			$result['status'] = -2;
		}
		$this->ajaxReturn($result, 'JSON');
	 }
	
	/**
	 * 创建预支付订单
	 */
	public function createPrePay() {
		if(is_login()) {
			$adb = M('account');
			$cdb = D('cart');
			$agdb = M('AccountGoods');
			$result['status'] = -1;
			$adb->startTrans();
			$uid = get_temp_uid();
			$payid = \Org\Util\String::keyGen();
			$accountData = array(
				'payid'			=> $payid,
				'uid'			=> $uid,
				'type'			=> -1,
				'status'			=> 0
			);
			if($adb->add($accountData) !== FALSE) {
				$map['uid'] = $uid;
				$list = $cdb->where($map)->select();
				$result['status'] = 0;
				// 复制购物车
				foreach($list as $cart) {
					$cart['payid'] = $payid;
					if($agdb->add($cart) === FALSE) {
						$result['status'] = -1;
						break;
					}
				}
				
				// 清空购物车
				if($result['status'] == 0 && $cdb->where($map)->delete() === FALSE) {
					$result['status'] = -1; // 失败
					$result['message'] = $cdb->getLastSql();
				}
			}
			
			if($result['status'] == 0) {
				$adb->commit();
				$result['rst'] = $payid;
				empty_cart();
			} else {
				$adb->rollback();
			}
		} else { // 未登录
			$result['status'] = -2;
		}
		$this->ajaxReturn($result, 'JSON');
	}

	// 取消预创订单
	function cancelPrePay($payid) {
		$adb = M('account');
//		$map['uid'] = get_temp_uid();
		$map['payid'] = $payid;
		if($adb->where($map)->save(array('status'	=> -1)) !== FALSE) {
			return 0;
		}
		return -1;
	}

	// 修改预支付订单的金额、积分、第三方金额
	function updatePrePay($payid, $uid, $money, $score, $third,$type=0) {
		$status = $this->checkPrePay($payid, $uid, $money, $score, $third);
		if($status == 0) {
			$agdb = M('Account');
//			$map['uid'] = $uid;
			$map['payid'] = $payid;
			$money = (float)$money;
			$score = (int)$score;
			$third = (float)$third;
			$data = array(
				'money'			=> $money, // 预使用余额
				'score'			=> $score, // 预使用积分
				'third'			=> $third,  // 预第三方支付金额
				'type'			=>$type
			);
			if($agdb->where($map)->save($data) === FALSE) {
				$status = 104; // 修改预支付订单失败
			}
		}
		return $status;
	}
	
	// 检查支付是否合法
	function checkPrePay($payid, $uid, $money, $score, $third, $list = null) {
//		$uid = get_temp_uid();
		$udb = M('member');
		
		$account = $udb->field('uid, money,score')->find($uid);
		$account['money'] = floatval($account['money']);
		$account['score'] = intval($account['score']);
		
		if(empty($list)) {
			$agdb = D('Home/AccountGoods');
			$map['uid'] = $uid;
			$map['payid'] = $payid;
			$list = $agdb->where($map)->relation(true)->select();
		}
		$total = $this->total($list);
//		echo dump($money);
//		echo dump($third);
//		echo dump($score);
//		echo dump($total);
		
		if($account['money'] < $money) {
			return 101;  // 余额不足
		} if($account['score'] < $score) {
			return 102;  // 积分不足
		} else if($money + $third + ($score / 100) < $total) {
			return 103;  // 付款金额不对
		}
		return 0;
	}
	
	// 支付订单
	function pay($payid) {
		$adb = M('Account');
		$adb->startTrans();
		$status = $this->doPay($payid);
		if($status == 0) {
			$adb->commit();
		} else {
			$adb->rollback();
		}
		return $status;
	}

	// 支付订单
	function doPay($payid) {
		$udb = M('Member');
		$adb = M('Account');
		$msdb = M('MemberScore');
		$agdb = D('Home/AccountGoods');
		
		$status = 0;
//		$uid = get_temp_uid();
//		$amap['uid'] = $uid;
		$amap['payid'] = $payid;		
		$amap['ispay'] = 0;		
		// 第一步 查询所购商品
		$list = $agdb->where($amap)->relation(true)->select();
		
//		//从payid中获取到uid
//		$accountdata = $adb -> find($payid);
//		$uid=$accountdata["uid"];
		
		// 第二步 检测余额是否足够 
		$adata = $adb->where($amap)->find();
		$uid = $adata['uid'];
		$money = (float)$adata['money'];  // 余额
		$score = (int)$adata['score'];    // 积分
		$third = (float)$adata['third'];  // 第三方支付金额
//		echo dump($third);

		if(empty($adata)) {
			return 108; // 非法订单号
		}
		
		$status = $this->checkPrePay($payid, $uid, $money, $score, $third, $list);
		if($status != 0) {
			return $status;
		}
		
		// 第三步 结算商品
		foreach($list as $cart) {
			$goodsType = intval($cart['type']);
			if($goodsType == 3) { // 拍卖
				if(intval($cart['isbz'] == 1)) { // 保证金
					$status = $this->doPayBaozhengjin($uid, $cart['paimai']['gid'], $third);
				} else if(intval($cart['isbz'] == -1)) { // 中标支付
					$status = $this->zhongbiao($cart);
				} else {
					// 立即拍卖出价购买
					$status = $this->paimai($cart);
				}
			} else { // 秒杀
				$status = $this->miaosha($cart);
			}
			if($status != 0) {
				return $status;
			}
		}
		
		$addScore=$third+$money-$score;
		// 第四步 扣除个人余额
		$member = array(
			'money'				=> array('exp', '`money` - '.$money),
			'score'				=> array('exp', '`score` + '.$addScore),// 第三方支付增加消费积分(1:1)
		);
		if($udb->where(array('uid' => $uid))->save($member) === FALSE) {
			//echo $udb->getLastSql();
			return 105; // 扣除个人余额失败
		}
		
		// 第五步 增加消费纪录流水
		if($addScore != 0)
		{
			$msdata = array(
				'uid'			=> $uid,
				'scoresource'	=> '购买商品',
				'score'			=> $addScore,
			);
			if($msdb->add($msdata) === FALSE) {
				return 106; // 增加消费纪录流水失败
			}	
		}
		// 第六步 修改account状态为1
		if($adb->where($amap)->save(array('status'	=> 1)) === FALSE) {
			return 107; // 修改预付订单状态为已支付－修改失败
		}
		return 0;
	}
	
	/**
	 * 结算保证金
	 */
	private function doPayBaozhengjin($uid, $gid, $third, $money) {
//		$uid = get_temp_uid();
		$pdb = M('paimai');
		$adb = M('account');
		$udb = M('member');
		$pmap['gid'] = $gid;
		$pmap['status'] = array('lt', 2);
		$good = $pdb->where($pmap)->field('gid,baozhengjin, baomingrenshu')->find();
		if($good) {
			// 商品还存在，还没结束			
			// 保存商品状态
			$data['gid'] = $gid;
			$data['status'] = 1;
			// 增加报名人数
			$data['baomingrenshu'] = intval($good['baomingrenshu']) + 1;
			if($pdb->save($data) === FALSE) {
				return 401; // 增加报名人数失败
			}
			
			// 增加缴纳保证金记录
			$mpdb = M('MemberPaimai');
			$mpdata['uid'] = $uid;
			$mpdata['gid'] = $good['gid'];
			$mpdata['flag'] = 0;
			$mpdata['money'] = $good['baozhengjin'];
			if($mpdb->add($mpdata) === FALSE) {
				return 402; // 增加保证金纪录失败
			}
		} else if($third > 0) {
			// 商品已结束
			// 第三方支付的钱转到余额
			// 增加消费记录
			$adata = array(
				'uid'			=> $uid,
				'type'			=> 1, // 充值
				'third'			=> $third, // 第三方支付类型
				'content' 		=> '退还商品保证金到余额，商品id['.$gid.']',
			);
			if($adb->add($adata) == FALSE) {
				return 403; // 增加消费记录失败
			}
		
			// 退还个人账户余额
			$member = array('money'		=> array('exp', '`money` + '.$third));
			if($udb->where(array('uid'	=> $uid))->save($member) == FALSE) {
				return 404; // 扣除个人余额失败
			}
		}
		add_renci(1);
		return 0;
	}
	
	function miaosha($cart) {
		// 更新秒杀主记录
		$mdb = M('Miaosha');
		$good = $mdb->find($cart['good']['gid']);
		
		if(intval($good['status']) == 2 || intval($good['status']) == 3 || intval($good['shengyurenshu']) == 0) {
			return 201;  // 商品已经完结
		}
		
		$good['zongrenshu'] = intval($good['zongrenshu']);
		$good['canyurenshu'] = min(intval($good['canyurenshu']) + intval($cart['count']), $good['zongrenshu']);
		$good['shengyurenshu'] = $good['zongrenshu'] - $good['canyurenshu'];
			
		$mmdb = M('MemberMiaosha');
		// 添加用户秒杀记录
		$data['uid'] = $cart['uid'];
		$data['gid'] = $cart['good']['gid'];
		$data['count'] = intval($cart['count']);
		$data['ms'] = rand(0,999);
		$data['qishu'] = $good['qishu'];
		$data['canyu'] = $good['canyurenshu']; // 记录当前参与人数，用于计算中奖结果  （已废弃）
		$mid = $mmdb->add($data); // 增加秒杀纪录
		if($mid !== FALSE) {
			$gid = (int)$data['gid'];
			$uid = (int)$data['uid'];
			$count = (int)$data['count'];
			$qishu = (int)$data['qishu'];
			$rst = $mmdb->execute("select f_buy_miaosha($gid, $qishu, $mid, $uid, $count) rst");
//			echo $mmdb->getLastSql();
			if((int)$rst['rst'] < 0) {
				return 206; // 保存主表失败
			}
			
			return 0;
		}
		return 207; // 增加秒杀记录失败
	}

	  

	public function topay($payid) {
		$third = (float)$_POST["third"];  // 第三方付款
		$money = (float)$_POST["money"]; // 余额支付
		$score = (int)$_POST["score"]; // 积分支付
		$uid = get_temp_uid();
		
		$result['status'] = $this->updatePrePay($payid, $uid, $money, $score, $third);
		if($status != 0) { // 计算余额失败
			$this->ajaxReturn($result, 'JSON');
			return;
		}
		
		if($third > 0) { // 需要第三方支付
			$this->jubaopay($payid);
			// TODO: 第三方支付接口
		} else { // 本地直接支付
			$result['status'] = $this->pay($payid);
		}
		$this->ajaxReturn($result, 'JSON');
	}
	
		// 模拟创建号
	function genPayId($length = 6 ) {
	
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$password = "";
		for ( $i = 0; $i < $length; $i++ )
			$password .= $chars[ mt_rand(0, strlen($chars) - 1) ];
	
		return $password;
	}
	public function jubaopay()
	{				
			$uid = session("_uid");
			vendor('jubaopay.jubaopay');
				 
//			$payid=$this->genPayId(20);
			$partnerid=C("jubaopay.partnerid");
						
			$amount=$_POST["amount"];
			$accountmoney=$_POST["accountmoney"];
			$accountscore=$_POST["accountscore"];
			$accountbgid=$_POST["accountbgid"];
			
			$payid=$_POST["payid"];
			$goodsName=$_POST["goodsName"];
			$remark=$_POST["remark"];
			$paytype=$_POST["paytype"]; //rechargepc
			$accountpaytype=-1;
			
			//$orderNo = md5(time());
			$orderNo=$payid;
			if($paytype=='pc' || $paytype=='wap')
			{
				$type=1;
				if($paytype=='wap')
				{
					$type=11;
				}
				
				$result['status'] = $this->updatePrePay($payid, $uid, $accountmoney, $accountscore, $amount,$type);
				if($status != 0) { // 计算余额失败
					$this->ajaxReturn($result, 'JSON');
					return;
				}
			}
			else if($paytype=='rechargepc' || $paytype=='rechargewap')
			{
				$adb = M('account');		
				$payid=$this->genPayId(20);

				$type=30;
				if($paytype=='rechargewap')
				{
					$type=31;
				}

				$accountData = array(
					'payid'			=> $payid,
					'uid'			=> $uid,
					'type'			=> $type,
					'third'			=> $amount,
					'score'			=> 0,
					'status'		=> 0
				);
			
				if($adb->add($accountData) !== FALSE) {
					//echo $paytype.'成功';
				}
				else
				{
					$this->display("error");
					//echo $paytype.'失败';
					return ;		
				}
				
			}

//			//写入到 account 表。
//			$adb = M('account');
//			if($adb->add($data)) {			
				$payerName="zs001";//$_POST["payerName"];
				$returnURL=C("jubaopay.returnURL");//"http://pay.xxx.com/result.php";    // 可在商户后台设置
				$callBackURL=C("jubaopay.callBackURL");//"http://pay.xxx.com/notify.php";  // 可在商户后台设置
				$payMethod= "WANGYIN";//$_POST["payMethod"];
				
				//测试
				//$amount=0.5;
				
				//////////////////////////////////////////////////////////////////////////////////////////////////
				 //商户利用支付订单（payid）和商户号（mobile）进行对账查询
				$jubaopay=new \jubaopay('jubaopay.ini');
				$jubaopay->setEncrypt("payid", $payid);
				$jubaopay->setEncrypt("partnerid", $partnerid);
				$jubaopay->setEncrypt("amount", $amount);
				$jubaopay->setEncrypt("payerName", $payerName);
				$jubaopay->setEncrypt("remark", $remark);
				$jubaopay->setEncrypt("returnURL", $returnURL);
				$jubaopay->setEncrypt("callBackURL", $callBackURL);
				$jubaopay->setEncrypt("goodsName", $goodsName);
				
				//对交易进行加密=$message并签名=$signature
				$jubaopay->interpret();
				$message=$jubaopay->message;
				$signature=$jubaopay->signature;			 
			 
				$this->assign("message",$message);
				$this->assign("signature",$signature);
				$this->assign("payMethod",$payMethod);
			
				layout(false);
//				
//				 
				if($paytype=="rechargepc" || $paytype=="pc")
				{
					$this->display("jubaopaypc");
				}
				else if($paytype=="wap" || $paytype=="rechargewap")
				{
					$this->display("jubaopaywap");
				}
//			}
			
	}

	public function success()
	{
		layout(false);
		$this->display();
	}
	
	public function error()
	{
		layout(false);
		$this->display();
	}

}