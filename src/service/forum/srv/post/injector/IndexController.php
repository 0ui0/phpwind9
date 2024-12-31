<?php
/**
 * 应用前台入口
 *
 * @author Shi Long <long.shi@alibaba-inc.com>
 * @copyright ©2003-2103 phpwind.com
 * @license http://www.windframework.com
 * @version $Id: IndexController.php 21266 2012-12-03 10:40:28Z long.shi $
 * @package demo
 */
class IndexController extends PwBaseController {
	
	public function run() {
		$app_name = 'demo';
		$this->setOutput($app_name, 'name');
	}
	public function jhreadAction() {
	}
	//今日热门
	public function todayAction() {
	}
	//find
	public function findAction() {
	}
	//签到
	public function qdAction() {
	}
	//板块快捷关注
	public function plateAction() {
	}
	//快速发帖
	public function ksfbAction() {
	}
	//客户案例
	public function khalAction() {
	}
	//read图阅
	public function viewpicAction() {
		$tid = $this->getInput('tid', 'get');
		$pid = $this->getInput('pid', 'get');
		$aid = $this->getInput('aid', 'get');
		
		$pid = $pid ? $pid : 0;
		$type = 'img';
	/**
	 * 统计帖子中某个类型的附件的个数
	 *
	 * @param int $tid 帖子id
	 * @param int $pid 回复id
	 * @param string $type 附件类型
	 * @return int
	 */
	 	Wind::import('SRV:forum.bo.PwThreadBo');
		$thread1 = new PwThreadBo($tid);
		$thread2 = $thread1->getThreadInfo();
	/**
	 * 统计帖子中某个类型的附件的个数
	 *
	 * @param int $tid 帖子id
	 * @param int $pid 回复id
	 * @param string $type 附件类型
	 * @return int
	 */
	 	$count = Wekit::loadDao('attach.PwThreadAttach')->countType($tid, $pid, $type);
	/**
	 * 获取帖子(A)中回复序列(B)中的附件信息
	 *
	 * @param int $tid 帖子(A)
	 * @param array $pids 回复序列(B)
	 * @return array
	 */
		$fdsfds = Wekit::loadDao('attach.PwThreadAttach')->getAttachByTid($tid,array($pid));
		$attachdb = array();
		$curnum = 0;
		foreach($fdsfds as $k => $v){
			if($v['type'] == 'img'){
				if($v['aid'] == $aid)$curkey = $curnum;
				$attachdb[] = array('url'=>$v['path'],'aid'=>$v['aid']);
				$curnum++;
			}
		}
		//var_dump($attachdb);
		$curkey = $curkey ? $curkey : 0 ;
		$page = $curkey + 1;
		$this->setOutput($count, 'count');
		$this->setOutput($page, 'page');
		$this->setOutput($curkey, 'curkey');
		$this->setOutput($attachdb, 'attachdb');
		$this->setOutput($thread2, 'thread');
	}
	public function postautoAction() {
		$url = 'http://m.uczzd.cn/iflow/api/v1/channel/100?app=share-iflow&pagetype=share&zzd_from=webapp&client_os=webapp&sn=13912586077216812189&fr=android&ve=11.8.3&summary=0&content_cnt=0&m_ch=000&bid=999&count=20&method=new&recoid=9695481040302657600216&ftime=1529057058760&city_name=&callback=jsonp_1529057058762_87091';
		$info = file_get_contents($url);
		$info = preg_replace('/jsonp_1529057058762_87091\(/','',$info); 
		$info = preg_replace('/\);/','',$info); 
		$info = json_decode($info,true);
		
		
		//发帖的动作
		Wind::import('SRV:forum.srv.PwPost');
		Wind::import('SRV:forum.srv.post.PwTopicPost');
		
		
		
		
		if($info['message']=='ok'){
			foreach ($info['data']['articles'] as $k=>$v) {//articles-文章
				$info_b = file_get_contents($v['url']);
				$info_b_preg = "/xissJsonData\s=\s{(.*?)}};/";
				preg_match($info_b_preg,$info_b,$info_c);
				$data = '{'.$info_c[1].'}}';
				$data = json_decode($data,true);
				if($data['id']){
					$attr = $this->_WriteDownImg($data['images']);
					foreach($attr as $kkk=>$vvv){
						$flashatt[] = $vvv['aid'];
						//$flashatt = $this->getInput('flashatt', 'post');
					}
					
					
					
					echo 'okkkkkkkkkkkkkkkkkkkkkkkkkkk';
					//thumbnails
					foreach ($data['images'] as $k=>$v) {//加入图片
						$data['content'] = str_replace('<!--','',$data['content']);
						$data['content'] = str_replace('-->','',$data['content']);
						$data['content'] = str_replace('{img:'.$k.'}','[img]'.$v['url'].'[/img]',$data['content']);
					}
					$data['content'] = preg_replace('/<(.*?)>/','',$data['content']); 
					$fid = '4';
					$postAction = new PwTopicPost($fid);
					$pwPost = new PwPost($postAction);
					
					
					
					Wind::import('SRV:forum.srv.post.do.PwPostDoAtt');
					$postAtt = new PwPostDoAtt($pwPost, $flashatt);
					if ($postAtt->hasAttach()) {
						$oldatt_desc = $this->getInput('oldatt_desc', 'post');
						$oldatt_needrvrc = $this->getInput('oldatt_needrvrc', 'post');
						$oldatt_ctype = $this->getInput('oldatt_ctype', 'post');
						$postAtt->editAttachs($oldatt_desc, $oldatt_needrvrc, $oldatt_ctype);
					} elseif (!$flashatt) {
						return null;
					}
					
					
					
					$postDm = $pwPost->getDm();
					$postDm->setTitle($data['title'])
								->setContent($data['content']);
					if (($result = $pwPost->execute($postDm)) !== true) {
							}
					$tid = $pwPost->getNewId();
				}
			}
		}
	}
	private function _WriteDownImg($imgs) {
		$imgdir = ATTACH_PATH . 'downremoteimg';
		if (!file_exists($imgdir)) {
			mkdir($imgdir, 0777);
		}
		$n = 1;
		foreach($imgs as $v) {
			$n++;
			$get_file = @file_get_contents($v['url'], false, $ext);
			if($get_file) {
				$imgExtension = pathinfo($v['url'], PATHINFO_EXTENSION);
				$imgExtension = substr($imgExtension,0,strrpos($imgExtension,'?'));  
				$imgName = rand(1,1000000) . '.' . $imgExtension;
				$writeFile = $imgdir . '/' . $imgName;
				$fp = @fopen($writeFile, "w"); 
				@fwrite($fp,$get_file); 
				@fclose($fp);
				if (@getimagesize($writeFile)) {
					$imgSize = filesize($writeFile);
					$e6_files[$n] = array(
						'name' 	=> $imgName,
						'tmp_name' => $writeFile,
						'error'	=> 0,
						'size'	=> $imgSize
					);
				} else {
					@unlink($writeFile);
				}
			}
		}
		if ($e6_files) {
			Wind::import('LIB:upload.PwUploadFile');
			$user = Wekit::getLoginUser();
			Wind::import('SRV:upload.action.PwAttMultiUpload');
			$bhv = new PwAttMultiUpload($user, '4');
			Wind::import('EXT:motouch.service.AppDown_Upload');
			$upload = new AppDown_Upload($bhv, $e6_files);
			$info = $upload->execute();
			foreach($e6_files as $value) {
				@unlink($value['tmp_name']);
			}
			return $info;
		}
		return false;
	}
}

?>