/*!
 * HTML5 falling effect
 *
 * @author Gavin<laigw.vip@gmail.com>
 */
(function($, w, UNDEF ) {
"use strict";

/*!
 * 屏幕点类
 */
var CPoint=function(_x, _y){this.x=_x||0;this.y=_y||0;this.xDir=0;this.yDir=0};

var doc = w.document;
var FallingEffect = function(pointNum, config) {
	this._config = $.extend({
		logEnable    : true,
		canvasId     : '',
		container    : doc.body,
		fallImg      : '/misc/images/flower.png',
		offsetLenX   : 2, //x轴方向偏移
		offsetLenY   : 2, //y轴方向偏移
		speed        : 50,//速度
		paddingLeft  : 0,
		paddingRight : 0,
		paddingTop   : 0,
		paddingBottom: 0,
		onFinish     : null
	}, config==UNDEF ? {} : config);
	
	this.$canvas     = UNDEF; //画布jQuery对象
	this._canvas     = UNDEF; //画布DOM对象
	this._canvas_ctx = UNDEF; //画布context
	this.point_num   = pointNum==UNDEF ? 0 : parseInt(pointNum);
	
	//初始化环境
	this._init();
};
FallingEffect.prototype = {
	//调试打印
	log: function(text) {
		if (this._config.logEnable) {
			w.console.log(text);
		}
		return this;
	},
	//初始化环境
	_init: function() {
		//检查是否需要动态生成canvas
		if (this._config.canvasId!='') {
			this.$canvas = $('#'+this._config.canvasId);
			if(this.$canvas.size()==0) {
				this.$canvas = UNDEF;
			}
		}
		if (this.$canvas==UNDEF) {
			var _w = $(this._config.container).width();
			var _h = $(this._config.container).height();
			this._config.canvasId = 'FEID_'+this.randstr();
			this.$canvas = $('<canvas id="'+this._config.canvasId+'" width="'+_w+'" height="'+_h+'">您的浏览器不支持canvas标签</canvas>');
			this.$canvas.css({'display':'none','position':'absolute','left':'0','top':'0','z-index':'10000'});
			this.$canvas.appendTo(this._config.container);
		}
		
		//设置dom对象
		this._canvas = this.$canvas.get(0);
		this._canvas_ctx = this._canvas.getContext("2d");
	},
	//显示效果
	show: function() {
		//检查必要输入参数
		if (!this.point_num) {
			w.console.error('第一个参数pointNum必须为大于0的整数');
			return;
		}
		if (!this._canvas_ctx) {
			w.console.error('您的浏览器不支持canvas标签');
			return;
		}
		
		//显示canvas
		this.$canvas.show();
		
		//窗口宽高
		var winW = this.$canvas.width();
		var winH = this.$canvas.height();
		
		//初始横轴开始位置、结束位置(左右的padding)
		var startX = this._config.paddingLeft;
		var endX   = winW - this._config.paddingRight;
		startX = this.round(startX);
		endX   = this.round(endX);
		
		//初始降落物区间位置
		var startMid  = this.round(winH/4);  //初始纵轴“中线”位置
		var startDiff = this.round(winH/5);  //中线上下振幅
		var startY = this._config.paddingTop;
		var endY   = startMid + startDiff;
		if (this.point_num <= 20) {
			endY = startMid + startDiff/2;
		}
		else if (this.point_num >= 100) {
			endY = startMid + startDiff*2;
		}
		startY = this.round(startY);
		endY   = this.round(endY);
		
		//计算降落物随机初始位置
		var beginPosSet = new Array();
		for(var i=0,pt; i<this.point_num; i++) {
			pt = new CPoint(this.randint(startX,endX), this.randint(startY,endY));
			pt.xDir = this.randint(0, 100); //确定左偏，还是右偏，还是正下
			//pt.xDir = pt.xDir < 70 ? -1 : (pt.xDir > 70 ? 1 : 0);
			pt.xDir = pt.xDir < 60 ? -1 : 1;
			beginPosSet.push(pt);
		}
		
		var oThis  = this;
		var incX   = this._config.offsetLenX;
		var incY   = this._config.offsetLenY;
		var _timer = null;
		var _img = new Image();
		_img.onload = function() {
			this.onload = null;
			_timer = setInterval(function(){
				oThis._draw(_img, beginPosSet, endX, winH);
				var endnum=0;
				for(var i=0; i<beginPosSet.length; i++) {
					beginPosSet[i].x += incX * beginPosSet[i].xDir; //向左向右移动打点
					beginPosSet[i].y += incY; //向下移动打点
					if (beginPosSet[i].x < startX || beginPosSet[i].x > (endX-_img.width)) { //触及边界，则反转运动方向
						beginPosSet[i].xDir *= -1;
					}
					if (beginPosSet[i].y >= winH) {
						endnum++;
					}
				}
				if (endnum >= beginPosSet.length) { //表示所有的点都已经超出范围，则要取消timer
					clearInterval(_timer);
					_timer = null;
					if (typeof(oThis._config.onFinish)=='function') {
						oThis._clear();
						oThis.$canvas.hide();
						oThis._config.onFinish();
					}
				}
			},oThis._config.speed);
		};
		_img.src = this._config.fallImg;
	},
	//在画布上绘制图形
	_draw: function(img, pos_set, max_width, max_height) {
		
		//this._canvas_ctx.fillStyle = "rgba(255,255,255,1)";
		//this._canvas_ctx.fillStyle = "#eeeeff";
		
		//清空之前绘制
		this._clear();
		
		var w = img.width, h = img.height;
		for(var i=0, cx, cy; i<pos_set.length; i++) {
			cx = pos_set[i].x;
			cy = pos_set[i].y;
			if (cx > (max_width-w)) {
				cx = max_width-w;
			}
			if (cy < max_height) {
				this._canvas_ctx.drawImage(img,0,0,w,h,cx,cy,w,h);
			}
		}
	},
	//情况画布对象
	_clear: function() {
		this._canvas_ctx.clearRect(0,0,this._canvas.width,this._canvas.height);
	},
	//返回from - to范围内的随机整数
	randint: function(from,to) {
		return this.round(Math.random()*(to-from)+from);
	},
	//返回指定长度的随机字符串
	randstr: function(len) {
		if (len==UNDEF) len = 10;
		var cs = ['a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','0','1','2','3','4','5','6','7','8','9'];
		var p = 0, ret = '';
		for (var i=0; i<len; i++) {
			p = this.round(Math.random() * (cs.length-1));
			ret += cs[p];
		}
		return ret;
	},
	//四舍五入返回数字的整数(比parseInt和Math.round高效)
	round: function(num) {
		return (0.5 + num) | 0;
	}
};
w.FallingEffect = FallingEffect;

})(jQuery,window);