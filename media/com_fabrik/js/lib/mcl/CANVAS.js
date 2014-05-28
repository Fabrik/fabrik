/*
---
script: CANVAS.js
description: CANVAS, static object.
license: MIT-style
authors:
 - Martin Tillmann
requires:
  core/1.2.4: '*'
provides: [CANVAS]
...
*/

var FbCanvas = new Class({
	
	Implements: [Options],
	layers : [],
	ctx : null,
	ctxEl : null,
	lastMouseOverTarget : null,
	dragTarget : null,
	threads : null,
	ctxPos : null,
	cacheCtxPos : true,
	
	initialize : function (options) {
		this.setOptions(options);
		if(options.canvasElement) this.setCtx(options.canvasElement);
		this.layers = new LayerHash();
		
		if(options.enableMouse)this.setupMouse();
		if(options.cacheCtxPos)this.cacheCtxPos = this.options.cacheCtxPos;
		
		this.threads = new Hash();
		
		this.ctxPos = $(this.ctxEl).getPosition();
	
		return this;
	},
	
	setDrag : function( item )
	{
		this.dragTarget = item.fullid;
	},
	
	clearDrag : function(  )
	{
		this.dragTarget = null;
	},

	getMouse : function(e){
		var ctxPos = this.cacheCtxPos ? this.ctxPos : $(this.ctxEl).getPosition();
		return [
			e.event.pageX - ctxPos.x,
			e.event.pageY - ctxPos.y
		];
	},
	
	setupMouse : function(){
		$(this.ctxEl).addEvents({	
			click : function(e)
			{
				var p = this.getMouse(e);
				var item;
				if(item = this.findTarget(p))
				{
					item.fireEvent('click',p);
				}
			}.bind(this),
			
			mousedown : function(e)
			{
				var p = this.getMouse(e);
				if(this.dragTarget)
				{
					this.fromPath(this.dragTarget).fireEvent('mousedown',p);
					return;
				}
				var item;
				if(item = this.findTarget(p))
				{
					item.fireEvent('mousedown',p);
				}
			}.bind(this),

			mouseup : function(e)
			{
				var p = this.getMouse(e);
				if(this.dragTarget)
				{
					this.fromPath(this.dragTarget).fireEvent('mouseup',p);
					return;
				}

				var item;
				if(item = this.findTarget(p))
				{
					item.fireEvent('mouseup',p);
				}
			}.bind(this),
			
			mousemove : function(e)
			{
				var p = this.getMouse(e);
				if(this.dragTarget)
				{
					this.fromPath(this.dragTarget).fireEvent('mousemove',p);
					return
				}
				
				var item;
				if(item = this.findTarget(p))
				{
					if(item.fullid != this.lastMouseOverTarget)
					{
						item.fireEvent('mouseover',p);
						if(this.lastMouseOverTarget)
						{
							this.fromPath(this.lastMouseOverTarget).fireEvent('mouseout',p);
						}
						this.lastMouseOverTarget = item.fullid;
					}
					else
					{
						item.fireEvent('mousemove',p);
					}
				}
				else
				{
					if(this.lastMouseOverTarget)
					{
						this.fromPath(this.lastMouseOverTarget).fireEvent('mouseout',p);
						this.lastMouseOverTarget = null;
					}
				}
			}.bind(this),
			
			dblclick : function(e)
			{
				var p = this.getMouse(e);
				var item;
				if(item = this.findTarget(p))
					item.fireEvent('dblclick',p);
			}.bind(this),
			
			mouseleave : function(e){
				var p = this.getMouse(e);
				
				if(this.dragTarget){
					this.fromPath(this.dragTarget).fireEvent('mouseup',p);
					this.dragTarget = null;
				}
				
				if(this.lastMouseOverTarget){
					this.fromPath(this.lastMouseOverTarget).fireEvent('mouseout',p);
					this.lastMouseOverTarget = null;
				}
			}.bind(this)
		});
	},
			
	setCtx : function( el ){
		this.ctxEl = el;
		this.ctx = $(el).getContext('2d');
	},
	
	getCtx : function(){
		return this.ctx;
	},
	
	contains : function(r,p){
		return p[0] >= r[0] && p[1] >= r[1] && p[0] <= r[0] + r[2] && p[1] <= r[1] + r[3]; 
	},
	
	fromPath : function( path )
	{
		path = path.split('/');
		return this.layers.get(path[0]).get(path[1]);				
	},
	
	layerFromPath : function( path )
	{
		path = path.split('/');
		return this.layers.get(path[0]);							
	},
	
	findTarget : function(p)
	{
		for(var i = this.layers.layers.length - 1, layer, items; layer = this.layers.layers[i]; i--)
		{
			var items = layer.items.getClean();
			for(var item in items)
			{
				if(items[item].interactive && items[item].dims)
				{
					if(this.contains(items[item].dims,p)){
						return items[item];
					}
				}
			}
		}
		return false;
	},

	addThread : function( thread )
	{
		if(!thread.options)thread = new Thread(thread);
		this.threads.set(
			thread.options.id,
			thread
		);
		return thread;
	},
	
	removeThread : function( id )
	{
			this.threads.get( id ).destroy();
			this.items.erase( id );		
	},
	
	clear : function(rect)
	{
		rect = rect || [
				0,
				0,
				$(this.ctxEl).get('width'),
				$(this.ctxEl).get('height')
			];
		this.ctx.clearRect(rect[0],rect[1],rect[2],rect[3]);
		return this;
	},
	
	draw : function()
	{
		this.layers.draw();
	}
	
});
