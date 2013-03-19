YAHOO.namespace("TSGlossary.panel");YAHOO.TSGlossary.panel.panels = [];
function createGlossary(id_in, title_in, body_in, context_in, closeOnMouseOut){
	var $E   = YAHOO.util.Event,
	    tsg = YAHOO.TSGlossary.panel.panels;
	if(!tsg[id_in]){
		tsg[id_in] = [];
		tsg[id_in][0] = new YAHOO.widget.Panel(id_in, { visible:true, draggable:true, close:true,
						             constraintoviewport:true, context:[context_in, 'tl', 'bl'], underlay:'shadow',
							     effect:{effect:eval(YAHOO.widget.ContainerEffect.FADE),duration:0.5}
							   }
					            );   
		tsg[id_in][0].setHeader(title_in); 
		tsg[id_in][0].setBody("<div class='gd'>"+body_in+"</div>");   
		tsg[id_in][0].render(document.body);
		tsg[id_in][1] = $E.getTarget($E.getEvent());
	}else{
		tsg[id_in][0].show();
	}
	$E.addListener(document, 'mousemove', closeGlossary, {id:id_in, cf:closeOnMouseOut});
}

function closeGlossary(evt, conf){
	var $E   = YAHOO.util.Event,
	    $D	 = YAHOO.util.Dom,
	    mX, mY, regImgTh,
	    tsg = YAHOO.TSGlossary.panel.panels;
	
	try{
		if(!tsg[conf.id]){return;}
		regImgTh = $D.getRegion(tsg[conf.id][1]);
	        mX = $E.getPageX(evt); mY = $E.getPageY(evt);
	  	if((mX < regImgTh['left']) || (mX > regImgTh['right']) || (mY < regImgTh['top']) || (mY > regImgTh['bottom'])){
			if(conf.cf){tsg[conf.id][0].hide(); $E.removeListener(document, 'mousemove', closeGlossary);}
	  	}
	}catch(e){} 
}