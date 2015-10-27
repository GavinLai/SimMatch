<?php
/**
 * 上传图片到七牛作业
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
// 初始化Qiniu SDK库
require_once SIMPHP_INCS.'/libs/qiniu/autoload.php';

// 引入鉴权类
use Qiniu\Auth;

// 引入上传类
use Qiniu\Storage\UploadManager;

// 作业类
class QiniuFileUpJob extends CronJob {

	//const PATH_DOMAIN = 'http://7xnslp.com1.z0.glb.clouddn.com/';
	const PATH_DOMAIN = 'http://fimg.fxmapp.com/';
	const PATH_PREFIX = 'wxbs';
	
	public function job($argc, $argv) {
		
		// 需要填写你的 Access Key 和 Secret Key
		$accessKey = 'degc0lsSBdDrin_ccL4q6yMdLUPvMkSIQONrFjWw';
		$secretKey = 'ePoKmcSWuBOBFEehJHKAKaErCkhO42jmUVNLDCrl';
		
		// 要上传的空间
		$bucket = 'fimg';
		
		// 从数据库获取要同步的文件信息列表
		$fileList = $this->getFileList();
		
		if (!empty($fileList)) {
			
			// 记下记录ID集合
			$rids = [];
			foreach ($fileList AS $it) {
				array_push($rids, $it['rid']);
			}
			
			// 立即锁定当前进程准备处理的记录
			$this->lockFiles($rids);
			
			// 构建鉴权对象
			$auth = new Auth($accessKey, $secretKey);
			
			// 生成上传 Token
			$token = $auth->uploadToken($bucket);
			
			// 初始化 UploadManager 对象
			$uploadMgr = new UploadManager();
			
			// 循环上传
			$this->log("Begin loop...Records Num: ".count($fileList));
			foreach ($fileList AS &$it) {
				
				// 先初始化字段
				$it['img_std_cdn'] = $it['img_thumb_cdn'] = '';
				
				//~ 上传标准图
				// 要上传文件的本地路径
				$filePath = SIMPHP_ROOT.$it['img_std'];
					
				// 上传到七牛后保存的文件名
				$key = $this->qiniu_filekey(basename($filePath));
					
				// 上传文件
				list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
				if ($err !== null) { //失败
					$this->log("[FAIL]rid=".$it['rid'].", img_std=".$it['img_std']);
				} else { //成功
					$it['img_std_cdn'] = $this->qiniu_filepath($ret['key']);
					$this->log("[SUCC]rid=".$it['rid'].", img_std=".$it['img_std']." >> ".$it['img_std_cdn']);
				}
				
				//~ 上传缩略图
				// 要上传文件的本地路径
				$filePath = SIMPHP_ROOT.$it['img_thumb'];
					
				// 上传到七牛后保存的文件名
				$key = $this->qiniu_filekey(basename($filePath));
					
				// 上传文件
				list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
				if ($err !== null) { //失败
					$this->log("[FAIL]rid=".$it['rid'].", img_thumb=".$it['img_thumb']);
				} else { //成功
					$it['img_thumb_cdn'] = $this->qiniu_filepath($ret['key']);
					$this->log("[SUCC]rid=".$it['rid'].", img_thumb=".$it['img_thumb']." >> ".$it['img_thumb_cdn']);
				}
				
				// 更新同步标志位
				if (!empty($it['img_std_cdn']) && !empty($it['img_thumb_cdn'])) {
					D()->query("UPDATE `{player_gallery}` SET `img_std_cdn`='%s', `img_thumb_cdn`='%s', `synced`=1 WHERE `rid`=%d", 
					           $it['img_std_cdn'], $it['img_thumb_cdn'], $it['rid']);
				}
				
			}
			$this->log("End loop.");
			
			// 最后解锁当前进程处理的全部记录
			$this->unlockFiles($rids);
			
		}

	}
	
	private function getFileList() {
		$rs = D()->from("player_gallery")->where("`synced`=0 AND `locked`=0")->select()->fetch_array_all();
		return $rs;
	}
	
	private function lockFiles(Array $rids = []) {
		if (empty($rids)) return false;
		D()->query("UPDATE `{player_gallery}` SET `locked`=1 WHERE `rid` IN(%s)", implode(',', $rids));
	}
	
	private function unlockFiles(Array $rids = []) {
		if (empty($rids)) return false;
		D()->query("UPDATE `{player_gallery}` SET `locked`=0 WHERE `rid` IN(%s)", implode(',', $rids));
	}
	
	private function updateSyncFlag($rid) {
		if (empty($rid)) return false;
		D()->query("UPDATE `{player_gallery}` SET `synced`=1 WHERE `rid`=%d", $rid);
	}
	
	private function qiniu_filekey($filename) {
		return (''==self::PATH_PREFIX ? '' : (self::PATH_PREFIX.'/')) . $filename;
	}
	
	private function qiniu_filepath($filename) {
		return self::PATH_DOMAIN . $filename;
	}

}
 
/*----- END FILE: QiniuFileUpJob.php -----*/