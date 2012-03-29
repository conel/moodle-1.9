<script type="text/javascript">
//<![CDATA[
	var zaznaczona;
	var odpowiedzi=[];
	function maxWidth(){
		var i=0;
		var div;
		while(document.getElementById("wyraz"+i)){
			div=document.getElementById("wyraz"+i);
			i++;
		}
	}

	function idPytania(obiekt){
		var ostatni=obiekt.id.lastIndexOf('_')+1;
		var idPytania=obiekt.id.substr(ostatni);
		return idPytania;
	}
	function idWyrazu(obiekt){
		var ostatni=obiekt.id.lastIndexOf('_');
		var idWyrazu=obiekt.id.substr(0, ostatni);
		idWyrazu=idWyrazu.substr(5);
		return idWyrazu;
	}

	function resetKolorkow(){

		var x=document.getElementsByTagName("div");
		var i=0;
		while(x[i]){
				if(x[i].id.indexOf('wyraz')!=-1){
					x[i].style.backgroundColor="#cccccc";
				}
			i++;
		}
	/*
		var i=0;
		var div;
		while(document.getElementById("wyraz"+i)){
			div=document.getElementById("wyraz"+i);
			div.style.backgroundColor="#cccccc";
			i++;
		}
		*/
	}
	function zaznaczone(obiekt){
		resetKolorkow();
		var wyr=obiekt;
		if(zaznaczona!=obiekt){
		wyr.style.backgroundColor="#CCFF80";
		zaznaczona=obiekt;
		}else{
		resetKolorkow();
		zaznaczona=undefined;
		}
	}
	function odpowiedz(obiekt, nrPytania, nrOdpowiedzi){
		
		var odp=obiekt;
	//	alert(zaznaczona+' '+odp.innerHTML);
		var wyr=zaznaczona;
		if(wyr!=null){
			var nrWyrazu=idWyrazu(wyr);
			var nrPytWyr=idPytania(wyr);		
		}else{
			var nrPytWyr=nrPytania;		
		}
		
		if(nrPytWyr==nrPytania){

			if(zaznaczona!=null && (odp.innerHTML=="&nbsp;" || odp.innerHTML.charCodeAt(0)==160)){
				odp.innerHTML=wyr.innerHTML;
				wyr.style.display="none";
				if(odpowiedzi[nrPytania]==undefined){
					odpowiedzi[nrPytania]=[];
				}
				odpowiedzi[nrPytania][nrOdpowiedzi]=nrWyrazu;
				zaznaczona=null;
				//odswietlOdpowiedz(numer);
				resetKolorkow();
			}else if(zaznaczona!=null && odp.innerHTML!="&nbsp;"){
				//document.style.display="inline";
				odp.innerHTML=wyr.innerHTML;
				document.getElementById('wyraz'+odpowiedzi[nrPytania][nrOdpowiedzi]+'_'+nrPytania).style.display="inline";
				wyr.style.display="none";
				odpowiedzi[nrPytania][nrOdpowiedzi]=nrWyrazu;
				zaznaczona=null;
				resetKolorkow();
			}else if(odp.innerHTML!="&nbsp;"){
				odp.innerHTML="&nbsp;"

				document.getElementById('wyraz'+odpowiedzi[nrPytania][nrOdpowiedzi]+'_'+nrPytania).style.display="inline";
				odswietlOdpowiedz(odp);
				odpowiedzi[nrPytania][nrOdpowiedzi]=undefined;
				resetKolorkow();
			}
			ustawOdpowiedz(nrPytania);
		}
	}
	function podswietlWyraz(obiekt){
		var wyr=obiekt;
		if(zaznaczona!=obiekt){
			wyr.style.backgroundColor="#FFCC80";
		}
	}
	function odswietlWyraz(obiekt){
		if(zaznaczona!=obiekt){
		var wyr=obiekt;
		wyr.style.backgroundColor="#cccccc";
		}
	}
	function podswietlOdpowiedz(obiekt){
		var wyr=obiekt;
		if((zaznaczona!=null && idPytania(obiekt)==idPytania(zaznaczona)) || (wyr.innerHTML!="&nbsp;" && wyr.innerHTML.charCodeAt(0)!=160)){	
			wyr.style.backgroundColor="#FFCC80";
		}
	}
	function odswietlOdpowiedz(obiekt){
		var wyr=obiekt;
		wyr.style.backgroundColor="#cccccc";
	}
	function ustawOdpowiedz(idPytania){
		var odp=document.getElementById('resp'+idPytania+'_');
		odp.value="";
		

		for (i in odpowiedzi){
			
			if(i==idPytania){
				for (j=0; j<odpowiedzi[i].length; j++){
				//alert(i+':'+j+':'+odpowiedzi[i][j]);
				if(odpowiedzi[i][j]!=undefined){
				odp.value+=document.getElementById("wyraz"+odpowiedzi[i][j]+"_"+idPytania).innerHTML;
				}else{
				odp.value+='[puste]';
				}
				odp.value+='[split]';
				}
			}	
		
		}
	}
	function iloscWyrazow(idPytania){
		var i=0;
		var wyrazow=0;
		var x=document.getElementsByTagName("div");
		while(x[i]){
				if(x[i].id.indexOf('wyraz')!=-1 && x[i].id.indexOf(idPytania)!=-1){
					wyrazow++;
				}
			i++;
		}
		return wyrazow;
	}
	function sprawdz(idPytania){
		var saPuste=true;
		var pelnych;
		var wyrazow=iloscWyrazow(idPytania);
		for (i in odpowiedzi){
			if(i==idPytania){
				pelnych=0;
				for (j=0; j<odpowiedzi[i].length; j++){
					if(odpowiedzi[i][j]!=undefined){
							pelnych++;
					}
				}
				if(pelnych==wyrazow){
					saPuste=false;
				}
				break;
			}	
		}
		
		
		if(saPuste==true){
			alert("<?php echo get_string('notAll', 'qtype_onteorder'); ?>");
		}else{
			document.getElementById('resp'+idPytania+'_submit').click();
		}
	}
//]]>
</script>