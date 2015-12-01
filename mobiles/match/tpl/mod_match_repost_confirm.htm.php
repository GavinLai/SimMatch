<?php defined('IN_SIMPHP') or die('Access Denied');?>
<div class="match-join match-confirm">
  <form name="frm_matchconfirm" id="frm_matchconfirm" action="<?php echo U('match/'.$match_id.'/repost_confirm')?>" method="post" target="_self">
    <div class="row row-tit"><h1>确认参赛者</h1><input name="match_id" type="hidden" value="<?=$match_id?>" /></div>
    <p class="row"><input name="player_id" id="frm_player_id" type="text" class="inptxt row-ele" value="" placeholder="请输入参赛者编号" /></p>
    <p class="row"><input name="mobile" id="frm_mobile" type="text" class="inptxt row-ele" value="" placeholder="请输入报名时填写的手机号或微信号" /></p>
    <p class="row row-btn"><input name="submit" type="submit" class="btn btn-purple" id="frm_submit" value="提 交" /></p>
    <p class="row">或&nbsp;<a href="<?php echo U('match/'.$match_id)?>">返回首页</a></p>
    <p class="err"><?=$errmsg?></p>
  </form>
</div>