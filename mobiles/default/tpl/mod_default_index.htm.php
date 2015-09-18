<?php defined('IN_SIMPHP') or die('Access Denied');?>
<?php if(''!==$errmsg):?>

<div class="error"><?=$errmsg?></div>
<script>$(function(){nav_set_disabled();});</script>

<?php else:?>

<div class="block-page match-page" style="background:url('<?=$ninfo['thumb_url']?>') no-repeat;background-size: 100%;">
  
</div>

<?php endif;?>