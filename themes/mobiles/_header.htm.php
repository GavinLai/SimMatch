<?php defined('IN_SIMPHP') or die('Access Denied');?>

<?php if(isset($nav_flag1)):?>

<?php if('match_detail'==$nav_flag1):?>

<div class="bbsizing match-top" id="match-top"></div>

<?php elseif('match_rank'==$nav_flag1):?>

<nav class="topnav topnav-rank" id="rank-top"></nav>

<?php endif;?>

<?php endif;/*END if(isset($nav_flag1))*/?>