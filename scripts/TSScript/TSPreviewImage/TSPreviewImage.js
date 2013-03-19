/* See http://components.developers4web.com/rating-system */
YAHOO.namespace('TSPreviewImage.panel');YAHOO.TSPreviewImage.panel.panels = []; 
function createPreviewImage(id_in, title_in, thumb_url, context_in,iwidth, iheight,closeOnMouseOut){
	var $E   = YAHOO.util.Event,
			tspi = YAHOO.TSPreviewImage.panel.panels;
	if(!tspi[id_in]){
		tspi[id_in] = [];
		tspi[id_in][0] = new YAHOO.widget.Panel(id_in, { width:iwidth, visible:true, draggable:true, close:((closeOnMouseOut)?false:true),
								 constraintoviewport:true, context:[context_in, 'tl', 'bl'], underlay:'matte',
								 effect:{effect:eval(YAHOO.widget.ContainerEffect.FADE),duration:0.5}
								} 
						        );   
		tspi[id_in][0].setHeader(title_in); 
		tspi[id_in][0].setBody("<img src='"+thumb_url+"' width='"+iwidth+"' height='"+iheight+"'>");   
		tspi[id_in][0].render(document.body);
		tspi[id_in][1] = $E.getTarget($E.getEvent());
	}else{
		tspi[id_in][0].show();
	}
	try{  for (key in tspi){if(key != id_in){tspi[key][0].hide();}}   } catch (e) {}
	$E.addListener(document, 'mousemove', closePreviewImage, {id:id_in, cf:closeOnMouseOut});
}


function closePreviewImage(evt, conf){
	var $E   = YAHOO.util.Event,
	    $D	 = YAHOO.util.Dom,
	    mX, mY, regImgTh,
	    tspi = YAHOO.TSPreviewImage.panel.panels;
	
	try{
		if(!tspi[conf.id]){return;}
		regImgTh = $D.getRegion(tspi[conf.id][1]);
	        mX = $E.getPageX(evt); mY = $E.getPageY(evt);
	  	if((mX < regImgTh['left']) || (mX > regImgTh['right']) || (mY < regImgTh['top']) || (mY > regImgTh['bottom'])){
			if(conf.cf){tspi[conf.id][0].hide(); $E.removeListener(document, 'mousemove', closePreviewImage);}
	  	}
	}catch(e){} 
}