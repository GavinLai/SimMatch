<?php
/**
 * 生成选手二维码，并上传到七牛
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
require_once SIMPHP_INCS .'/libs/phpqrcode/qrlib.php';

// 初始化Qiniu SDK库
require_once SIMPHP_INCS.'/libs/qiniu/autoload.php';

// 引入鉴权类
use Qiniu\Auth;

// 引入上传类
use Qiniu\Storage\UploadManager;

// 作业类
class GenPlayerQRCodeJob extends CronJob {

	//const PATH_DOMAIN = 'http://7xnslu.com1.z0.glb.clouddn.com/';
	const PATH_DOMAIN = 'http://fimg.fxmapp.com/';
	const PATH_PREFIX = 'wxbs/qrcode';
	const SITE_DOMAIN = 'http://bs.fxmapp.com/';
	
	public function job($argc, $argv) {
	
		// 需要填写你的 Access Key 和 Secret Key
		$accessKey = 'degc0lsSBdDrin_ccL4q6yMdLUPvMkSIQONrFjWw';
		$secretKey = 'ePoKmcSWuBOBFEehJHKAKaErCkhO42jmUVNLDCrl';
	
		// 要上传的空间
		$bucket = 'fimg';
		//$bucket = 'static';
		
		// 从数据库获取要生成的player ids
		$playerIds = $this->getPlayerIds();
		
		if (!empty($playerIds)) {
			
			// 构建鉴权对象
			$auth = new Auth($accessKey, $secretKey);
				
			// 生成上传 Token
			$token = $auth->uploadToken($bucket);
				
			// 初始化 UploadManager 对象
			$uploadMgr = new UploadManager();
			
			foreach ($playerIds AS $id) {
				
				$qrinfo  = self::SITE_DOMAIN . 'player/' . $id .'?f=qr';
				$path    = '/a/player/qrcode/' . $id . '.png';
				$locfile = SIMPHP_ROOT . $path;
				
				//生成二维码
				QRcode::png($qrinfo, $locfile, QR_ECLEVEL_M, 7, 4);
				
				//更新qrcode字段
				$this->updateFields($id, ['qrcode' => $path]);
				
				// 上传到七牛后保存的文件名
				$key = $this->qiniu_filekey(basename($path));
				
				// 上传文件
				list($ret, $err) = $uploadMgr->putFile($token, $key, $locfile);
				if ($err !== null) { //失败
					$this->log("[FAIL]player_id={$id},locpath={$locfile}");
				} else { //成功
					$path_qn = $this->qiniu_filepath($ret['key']);
					//更新qrcode字段
					$this->updateFields($id, ['qrcode_cdn' => $path_qn]);
					$this->log("[SUCC]player_id={$id},locpath={$locfile} >> {$path_qn}");
				}
				
			}
		}
		
	}
	
	private function getPlayerIds() {
		$rs = D()->from("player")->where("`qrcode`='' AND `status`<>'D'")->select('player_id')->fetch_column('player_id');
		return $rs;
	}
	
	private function updateFields($player_id, Array $fields_data) {
		if (empty($fields_data)) return false;
		D()->update("player", $fields_data, ['player_id' => $player_id]);
		return D()->affected_rows()==1 ? true : false;
	}
	
	private function qiniu_filekey($filename) {
		return (''==self::PATH_PREFIX ? '' : (self::PATH_PREFIX.'/')) . $filename;
	}
	
	private function qiniu_filepath($filename) {
		return self::PATH_DOMAIN . $filename;
	}
	
}

/*----- END FILE: GenPlayerQRCodeJob.php -----*/