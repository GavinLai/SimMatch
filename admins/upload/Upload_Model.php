<?php
defined('IN_SIMPHP') or die('Access Denied');

class Upload_Model extends Model {
  
	static function saveUpload($data){
		$data['timeline'] = time();
		return D()->insert_table('upload_pic', $data);
	}
	
	/**
	 *
	 * @param integer $player_id
	 * @param array $img_path ['ori'=>'','std'=>'','thumb'=>'']
	 * @return boolean
	 */
	static function savePlayerGallery($player_id, Array $img_path) {
		$_data = [
				'player_id' => $player_id,
				'img_ori'   => $img_path['ori'],
				'img_std'   => $img_path['std'],
				'img_thumb' => $img_path['thumb'],
		];
		$rid = D()->insert("player_gallery", $_data);
		return $rid ? : false;
	}
	
	/**
	 * 保存base64式图片到文件系统，返回文件路径
	 *
	 * @param string $img_path
	 * @return number | string
	 *   -1: 图片错误
	 *   -100: 保存发生错误
	 *   string: 正确，保存的文件路径
	 */
	static function makeImgThumb($img_path) {
	
		//$img_path like 'a/player/201509/original/15_173852_nonezh.jpg'
		$img_info  = getimagesize(SIMPHP_ROOT.$img_path);
		if(FALSE===$img_info){
			return -1;
		}
	
		$width    = $img_info[0];
		$height   = $img_info[1];
		$imgtype  = $img_info[2];
		$ratio    = $width / ($height ? : 1);
	
		$filetype = 'player';
		$extpart  = '.jpg';
		switch ($imgtype) { //image type
			case IMAGETYPE_GIF:
				$extpart = '.gif';
				break;
			case IMAGETYPE_PNG:
				$extpart = '.png';
				break;
		}
	
		$maxwidth     = 750; //width of iPhone 6
		$thumbwidth   = 270; //width of thumbnail
	
		//~ 写文件
		$oripath = $img_path;
		$result  = ['ori'=>$oripath, 'std'=>$oripath, 'thumb'=>$oripath];
		$oripath = SIMPHP_ROOT . $oripath;

		switch ($imgtype) { //image type
			default:
			case IMAGETYPE_JPEG:
				$img = imagecreatefromjpeg($oripath);
				break;
			case IMAGETYPE_GIF:
				$img = imagecreatefromgif($oripath);
				break;
			case IMAGETYPE_PNG:
				$img = imagecreatefrompng($oripath);
				break;
		}

		if (is_resource($img)) {

			$targetdir = dirname($img_path);
			$targetdir = str_replace('original', 'thumb', $targetdir);
			$filecode  = basename($img_path);
			$filecode  = substr($filecode, 0, strrpos($filecode, '.'));
			
			//std版本
			if ($width > $maxwidth) { //只有宽度大于$maxwidth才需要生成标准图，否则直接用原图做标准图
				$dstpath = SIMPHP_ROOT.$targetdir.'/'.$filecode.$extpart;
				$rv = self::writeImgFile($img, $dstpath, $imgtype, $width, $height, $maxwidth, intval($maxwidth/$ratio));
				if ($rv) {
					$result['std'] = preg_replace("/^".preg_quote(SIMPHP_ROOT,'/')."/", '', $dstpath);
				}
			}

			//thumb版本
			if ($width > $thumbwidth) { //只有宽度大于$thumbwidth才需要生成缩略图，否则直接用原图做缩略图
				$dstpath = SIMPHP_ROOT.$targetdir.'/'.$filecode.'_thumb'.$extpart;
				$rv = self::writeImgFile($img, $dstpath, $imgtype, $width, $height, $thumbwidth, intval($thumbwidth/$ratio));
				if ($rv) {
					$result['thumb'] = preg_replace("/^".preg_quote(SIMPHP_ROOT,'/')."/", '', $dstpath);
				}
			}

			imagedestroy($img);
		}
	
		return $result;
	}
	
	static function writeImgFile($srcimg, $dstpath, $imgtype, $src_w, $src_h, $dst_w, $dst_h) {
		$rv = FALSE;
		$dstimg = imagecreatetruecolor($dst_w, $dst_h);
		if (is_resource($dstimg)) {
			if(!is_dir(dirname($dstpath))) {
				mkdirs(dirname($dstpath), 0777, TRUE);
			}
			imagecopyresampled($dstimg, $srcimg, 0, 0, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
			switch ($imgtype) { //image type
				default:
				case IMAGETYPE_JPEG:
					$rv = imagejpeg($dstimg, $dstpath);
					break;
				case IMAGETYPE_GIF:
					$rv = imagegif($dstimg, $dstpath);
					break;
				case IMAGETYPE_PNG:
					$rv = imagepng($dstimg, $dstpath);
					break;
			}
			if ($rv) {
				chmod($dstpath, 0444);
			}
			imagedestroy($dstimg);
		}
		return $rv;
	}
	
}