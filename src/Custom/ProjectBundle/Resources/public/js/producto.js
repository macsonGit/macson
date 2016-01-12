
$(document).ready(function(){

	mobile=false;

	var ancho=screen.width;


	$("#infoPanel_mob").hide();
	$("#header_mob").hide();
	$(".tiendas_mob").hide();
	$("#cartel_mob").hide();
	$("#menu_header_mob").hide();
	$("#logo_mob").hide();
	$("#logoPortada_mob").hide();
	$("#portada_mob").hide();
	$("#payment_method").hide();
	$(".tallasStyle_mob").hide();
	$(".portada_main").hide();
	$("#buyLoginPanel").hide();

	
$('html').click(function() {

	$('.desplegaFooter_mob').hide(200);

}
);

	if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/.test(navigator.userAgent) ) {

 		var mobile=true;
		$("#footer").attr('id', 'footer_mob');
		$(".listFooter").attr('class', 'listFooter_mob');
		$(".desplegaFooter").attr('class', 'desplegaFooter_mob');
		$(".listDesplegaFooter").attr('class', 'listDesplegaFooter_mob');
		$('body').css({'letter-spacing': '2px'});
		$('body').css({'min-width': '200px'});
		$("#portada").hide();
		$("#portada_mob").show();
		$("#cartel").hide();
		$("#cartel_mob").show();
		$(".menu_bar").attr('class', 'menu_bar_mob');
		$("#menu").attr('id', 'menu_mob');
		$("#menu_size").attr('id', 'menu_size_mob');
		$(".contenido").attr('class', 'contenido_mob');
		$(".fotoContenido").attr('class', 'fotoContenido_mob');
		$(".camposContenido").attr('class', 'camposContenido_mob');
		$("#payment_method").attr('id', 'payment_method__mob');
		$("#contenedor1").attr('id', 'contenedor1_mob');
		$(".loginStyle").attr('class', 'loginStyle_mob');
		$(".second-column").attr('class', 'mg-top10');
		$(".third-column").attr('class', 'borderBottom');
		$(".tallasStyle_mob").css({'letter-spacing':'1.5px'});
		$(".stiches").css({'width':'100%'});
		$("#tituloVolver").attr('id', 'tituloVolver_mob');
		$(".zoomContainerClass").attr('class', 'zoomContainerClass_mob');
		$("#login").attr('width', '100%');
		$(".palitroque").hide();
		$(".block-title").attr('class', 'block-title_mob');
		$(".thanksClass").attr('class', 'thanksClass_mob');
		$(".cell_store").attr('class', 'cell_store_mob');
		$(".item1").attr('class', 'item1_mob');
		$(".item2").attr('class', 'item2_mob');
		$(".item3").attr('class', 'item3_mob');
		$(".item4").attr('class', 'item4_mob');
		$(".store").attr('class', 'store_mob');
		$(".cell_store").attr('class', 'cell_store_mob');
		$(".texto").attr('class', 'texto_mob');
		$(".tagName").attr('class', 'tagName_mob');
		$(".fotoImg").attr('class', 'fotoImg_mob');
		$(".foto").attr('class', 'foto_mob');
		$(".fotoProd").attr('class', 'fotoProd_mob');
		$(".fotoLarge1").attr('class', 'fotoLarge1_mob');
		$(".fotoLarge2").attr('class', 'fotoLarge2_mob');
		$(".fotoLarge3").attr('class', 'fotoLarge3_mob');
		$(".itemSize").attr('class', 'itemSize_mob');
		$("#menuPortada").attr('id', 'menu_mob');
		$("#menucommerce .inline").removeClass('pad15');
		$("#menucommerce .inline").removeClass('inline');
		$(".precioDecimal").attr('class', 'precioDecimal_mob');
		$("#menu_header_mob").show();
		$("#header_mob").show();
		$("#header").hide();
		$("#headerInfo").hide();
		$("#logoPortada").hide();
		$("#logoPortada_mob").show();
		$("#logo").hide();
		$("#logo_mob").show();
		$("#headerFreeShipping").css({'width': '100%'});
		$("#headerFreeShipping").text('SPAIN:FREE SHIPPING');
		$("#headerFreeShipping").css({'text-align':'center'});
		$(".fotoImage2").hide();
		$("#masInfo").hide();
		$("#menuImg").remove();
		$("#tituloCat").remove();
		$(".foto_blank").hide();
		$(".large").hide();
		var alturaText=$("#menu_mob").height()+70;
		$(".foto_blank1").css({'height': '0'});
		$(".checkout .col").css({'width': '100%'});
		$(".checkout .col").css({'float': 'none'});
		$(".page-title").css({'height': '0px'});
		$(".page-footer").css({'width': '100%'});
		$(".icon-comment").css({'position': 'relative'});
		$(".checkout .wd90 .h2").css({'font-size': '20px'});
		$(".addresses").css({'letter-spacing': '1px'});
		$(".addresses").css({'font-size': '10px'});
		$(".verticalcenter120").css({'line-height': 'normal'});
		$(".col_mob").css({'float': 'none'});
		$(".checkout .wd90").css({'width': '96%'});
		$("#buyLoginPanel").css({'width':'80%'});
		$(".titleCheck").css({'font-size': '18px'});
		$("#carritoId").remove();
		$("#listaCarritoResumen").remove();

		$("#infoPanel_mob").show();
		$(".tallasStyle").hide();
		$(".tallasStyle_mob").show();
		$("#menu_mob").hide();
		$(".portada_main").show();



		alturaTextIni=0;
		if($("#tipoPagina").text() == 'prod1'){
			$("#foto1").hide();
			$("#carritoId_mob").css({'top':'0px'});
			$(".loginStyle_mob").css({'top':'0px'});

		}
		if($("#tipoPagina").text() == 'prod2'){

			$("#carritoId_mob").css({'top':'0px'});
			$(".loginStyle_mob").css({'top':'0px'});

		}
		if($("#tipoPagina").text() == 'prod3'){

			$("#carritoId_mob").css({'top':'0px'});
			$(".loginStyle_mob").css({'top':'0px'});

		}
		if($("#tipoPagina").text() == 'hom'){
			$(".foto_blank1").css({'height':'411px'});
			alturaTextIni=411;
			$("#contenedor1_mob").css({'top':'50px'});
			$("#footer_mob").css({'bottom':'-200px'});

		}

	}
	else{
		$(".titleCheck").css({'height': '25px'});
		$("#carritoId_mob").remove();
		$("#listaCarritoResumen_mob").remove();
	}

	$(".fotoEmpty").hide();

	var timer;
	var hoverdelay = 200;

      	var target = $("#target");
      	if (target.length) {
        	$('html,body').animate({
          	scrollTop: (target.offset().top-30)
        	}, 100);
	}

	$(function()
	{
		$('.contenido').jScrollPane();
	});

	$(function()
	{
		$('.menu_bar').jScrollPane();
	});

	$("#tituloVolver").hide();
	$('#zoomContainer').hide();
	$("#mascara").hide();
	$("#loading").hide();
	$("#contenedor1").css('visibility','visible');
	$("#contenedor1_mob").css('visibility','visible');
	$("#cuidadosPanel").hide();
	$(".novedad").hide();
	$("#orderList").hide();
		
	$("#login").hide();
	$(".carrito").hide();


	$('#back').click(
		function back(){
			window.history.back();

		}

	);	
	



	
	var showPanel=false;
	var showPanelCuidados=false;
	var showMascara=false;


	$('#cookie-button-m').click(
		function cookieOut(){
			$('#cookie-out').hide();
			$('#cookie-container').hide();
			$("#mascara").hide(200);
			$("#login").hide(200);
			$(".carrito").hide(200);
			showMascara=false;
		}
	);	
	
	$('#mascara').click(
		function mascaraOut(){
			$('#zoomContainer').hide();
			$("#mascara").hide(200);
			$("#login").hide(200);
			$(".carrito").hide(200);
			showMascara=false;
		}
	);	
	$('#buyLogin').click(
		function butFunction(){
			$('#buyLoginPanel').show();
		}
	);	

	$("#boton1").click(  
		function addItem(){
			ope='add';
			//var route = "{{ path('macson_chart', { 'pid': 'PLACEHOLDER1' , 'vid':'PLACEHOLDER2' }) }}";
			if (sizeSelected==1){
				
				showMascara=true;
				opeItem(ope,product,variety);
			}
			else{
				alert("Selecciona Talla");
			}

		}
	);

	$("#boton1_mov").click(  
		function addItem(){
			ope='add';
			//var route = "{{ path('macson_chart', { 'pid': 'PLACEHOLDER1' , 'vid':'PLACEHOLDER2' }) }}";
			if (sizeSelected==1){
				$("html,body").scrollTop(0);
				showMascara=true;
				opeItem(ope,product,variety);
			}
			else{
				alert("Selecciona Talla");
			}

		}
	);
	$("#listaCarritoResumen").on('click','.borrarArticulo',  
		function removeItem(){
			ope='remove';
			productR =$(this).attr("product");
			varietyR =$(this).attr("variety");
			//var route = "{{ path('macson_chart', { 'pid': 'PLACEHOLDER1' , 'vid':'PLACEHOLDER2' }) }}";
			showMascara=true;
			opeItem(ope,productR,varietyR);

		}
	);

	$("#listaCarritoResumen_mob").on('click','.borrarArticulo',  
		function removeItem(){
			ope='remove';
			productR =$(this).attr("product");
			varietyR =$(this).attr("variety");
			//var route = "{{ path('macson_chart', { 'pid': 'PLACEHOLDER1' , 'vid':'PLACEHOLDER2' }) }}";
			showMascara=true;
			opeItem(ope,productR,varietyR);

		}
	);
	$(".itemTalla").click(  
		function selectSize(){
			var index;
			sizeSelected = 1;
			for (index = 0; index < varieties.length; ++index) {   
    				$( "#tallas li" ).eq( index +1).css("background-color","inherit");
    				$( "#tallas_mob li" ).eq( index +1).css("background-color","inherit");
				if (varietiesVal[index]==$( this ).text()){
					$(this).css("background-color","red");
					variety=varieties[index];
				}
			}	

		});


	$("#continue").click(

		function continueShopping(){
			$(".carrito").hide();
			$("#mascara").hide();
			showMascara=false;
		}
	);
	//Hide Show In panel-------------------------------------------------
	
	if ($(".has-error").length>0){

			$("#login").show(200);
			$("#mascara").show(200);
			showMascara=true;

	}

	$("#loginMenu").click(

		function showLogin(){
			$("html,body").scrollTop(0);
			$(".carrito").hide();
			$(".large").hide();
			$("#login").show(200);
			$("#mascara").show(200);
			showMascara=true;
			return false;
		}
	);
	$("#loginMenu_mob").click(

		function showLogin_mob(){
			$("html,body").scrollTop(0);
			$(".carrito").hide();
			$(".large").hide();
			$("#login").show(200);
			$("#mascara").show(200);
			showMascara=true;
			return false;
		}
	);
	$("#orderOption").click(

		function showLogin(){
			$("#orderList").show(200);
			$("#orderOption").css("background-color","gray");;
			return false;
		}
	);
	$("#carritoResumen").click(

		function showCart(){
			$("html,body").scrollTop(0);
			$("#login").hide();
			$(".carrito").show(200);
			$(".large").hide();
			$("#mascara").show(200);
			showMascara=true;
			return false;
		}
	);
	$("#carritoResumen_mob").click(

		function showCart_mob(){
			$("html,body").scrollTop(0);
			$("#login").hide();
			$(".carrito").show(200);
			$(".large").hide();
			$("#mascara").show(200);
			showMascara=true;
			return false;
		}
	);

	$("#sumItems").click(

		function showCart(){
			$("#login").hide();
			$(".carrito").show(200);
			$("#mascara").show(200);
			showMascara=true;
			return false;

		}
	);

	$("#masInfo").click( 

		function showInfo(){

			$(".large").hide();
			if(showPanel){
				$("#infoPanel").hide(350,
					function(){
						$("#masInfo").text("PRODUCT INFO +");
						showPanel=false;
					}
				);
			}
			else{
				$("#infoPanel").show(350,
					function(){
						$("#masInfo").text("PRODUCT INFO +");
						showPanel=true;
					}
				);
			}			
		
		}

	);
	$("#cuidados").click( 

		function showInfo(){

			if(showPanelCuidados){
				$("#cuidadosPanel").hide(350);
				showPanelCuidados=false;
			}
			else{
				$("#cuidadosPanel").show(350);
				showPanelCuidados=true;
			}			
		
		}

	);

	//Hide Show MMenu-------------------------------------------------

	if($("#tipoPagina").text()=="noLogin"){
		
		$('#loginMenu').hide();

	}

	if($("#tipoPagina").text()=="payPage"){
		
		$('#loginMenu').hide();
		$('#carritoResumen').hide();
		$('#sumItems').hide();
		$('#footerBar').hide();
		

	}

	if($("#tipoPagina").text() == 'prod1' || $("#tipoPagina").text() == 'prod2' || $("#tipoPagina").text() == 'prod3'){


		$('.fotoLarge1_mob').click(
			function zoomImage(){
				if (!showMenu){
					$('#zoomContainer').show();
					$('#zoomContainer').html('<img src=\''+urlImg1+'\'>');
					$("#mascara").show(200);
					$(".large").hide(2);
				}
			}
		);
		$('.fotoLarge2_mob').click(
			function zoomImage(){
				if (!showMenu){
					$('#zoomContainer').show();
					$('#zoomContainer').html('<img src=\''+urlImg2+'\'>');
					$("#mascara").show(200);
					$(".large").hide(2);
				}
			}
		);
		$('.fotoLarge3_mob').click(
			function zoomImage(){
				if (!showMenu){
					$('#zoomContainer').show();
					$('#zoomContainer').html('<img src=\''+urlImg3+'\'>');
					$("#mascara").show(200);
					$(".large").hide(2);
				}
			}
		);
		$('.large').click(
			function zoomImage(){
				if (!showMenu){
					$('#zoomContainer').show();
					$('#zoomContainer').html('<img src=\''+urlImage+'\'>');
					$("#mascara").show(200);
					$(".large").hide(2);
				}
			}
		);
		$('#zoomContainer').click(
			function zoomImage(){
				console.log($('img.fotoImg'));
				$('#zoomContainer').hide(200);
				$("#mascara").hide(200);
			}
		);		
		
	}	


	if($("#tipoPagina").text() == 'hom'){

		$("#back").hide();	
		$("#headerAux").hide();	
		$("#menu ul").css('position', 'absolute');
		$(".listDesplegaFooter").css('background-color', 'white');
		$("#payment_method").show();

	}	

	if($("#tipoPagina").text() == 'cat' || $("#tipoPagina").text() == 'con' || $("#tipoPagina").text() == 'cat_main'){
		$("#menu ul").css('background-image', 'none');

	    $('.foto').hover(
			function () {
			    var foto = $(this);
			    foto.find( '.fotoImg' ).fadeOut(700);
			},
			function () {
			    var foto = $(this);
			    foto.find( '.fotoImg' ).fadeIn(700);
			}
			
	    );
	}	
	
        if($("#tipoPagina").text() == 'cat_main'){
				$("#menu_boton_mob").hide();
				showMenu_mob=true;
				$("#menu_boton_mob").text("MENU -");
				$(".foto_blank1").css({'height': alturaText});
				$("#menu_mob").show(0);
	}

	if($("#tipoPagina").text() == 'prod1'){
		$("#infoPanel").show();
		$("#menu ul").show();
		$("#menu_mob").hide();
		$("#menu ul").css('background-image', 'none');
		$("#tituloCat").text("");
		$("#masInfo").text("");
		showMenu=true;
	}	

	if($("#tipoPagina").text() == 'prod2'){
		$("#masInfo").text("");		
		$("#menu ul").hide();
		$("#menu_mob").hide();
		$("#menu ul").css('background-image', 'none');
		$("#tituloCat").text("");
		showMenu=false;
	}		

	if($("#tipoPagina").text() == 'prod3'){
		$("#infoPanel").hide();
		$("#menu_mob").hide();
		$("#menu ul").hide();
		showMenu=false;
	}
			
	$("#menu").height(23);	


	var contador=0;

	$("#tituloCat").click( 

		function showMenuA(){
			if(showMenu){
				showMenu=false;
				$("#tituloCat").text("+ MENU");
				$("#menu ul").hide(350);
			}
			else{
				showMenu=true;
				$("#tituloCat").text("- MENU");
				$("#menu ul").show(350);
	
			}			

		}

	);

	showMenu_mob=false;
	$("#menu_boton_mob").click( 

		function showMenuA_mob(){
			if(showMenu_mob){
				showMenu_mob=false;
				$("#menu_mob").hide(350);
				$(".foto_blank1").css({'height': alturaTextIni});
				$("#foto_blank1_mob").hide(0);
			}
			else{
				showMenu_mob=true;
				$(".foto_blank1").css({'height': alturaText});
				$("#menu_mob").show(0);
	
			}			

		}

	);

	$("#menu li").hover(

		function shiftItem(){
			$(this).animate({
				'padding-left':"+=5"
			 }, 250);
		},
		function shiftItem(){
			$(this).animate({
				'padding-left':"-=5"
			 }, 350);

		}

	);	

	$(".listFooter").hover(
		function (){
			position=$(this).position();
			desplega=$(this).find(".desplegaFooter");
			timer = setTimeout(function() {
				desplega.show(200);
				desplega.css("left",position.left);
 			}
			, hoverdelay);

		},
		function (){
			clearTimeout(timer);
			desplega=$(this).find(".desplegaFooter");
			desplega.hide(200);
		}

	);	
	$(".listFooter_mob").click(
		function (e){
			desplega=$(this).find(".desplegaFooter_mob");
			if (desplega.is(":visible")){
				desplega.hide(200);
			}
			else{
				lists=$(this).parent().find(".listFooter_mob");
			
				desplega=$(this).find(".desplegaFooter_mob");
				for (index = 0; index < lists.length; ++index) {   
					desplega=lists.eq(index).find(".desplegaFooter_mob");
					desplega.hide(200);
				}
				desplega=$(this).find(".desplegaFooter_mob");
				desplega.show(200);
			}
    			e.stopPropagation();
		}
	);	
	var native_width = 0;
	var native_height = 0;

	var timeoutid = 0;
	var este= $(this);


	$(".itemSize").click(

		function(){
			replaced = urlImgSize.replace("XXXX",$(this).attr("value")+"_"+lang);
			$(".itemSize").removeClass("underline");
			$(this).addClass("underline"); 	
			$("#sizeImg").attr("src",replaced);
		}
	);

	$(".itemSize_mob").click(

		function(){
			replaced = urlImgSize.replace("XXXX",$(this).attr("value")+"_"+lang+"_mob");
			$(".itemSize_mob").removeClass("underline");
			$(this).addClass("underline"); 	
			$("#sizeImg").attr("src",replaced);
		}
	);



	$("#selectZone").change(

		function(){

			$('.store').hide();			
			$('.province').hide();
			objects='*[province="'+$(' #selectZone option:selected ' ).text()+'"]'; 			
			$(objects).show();
		}

	);

	
	//Now the mousemove function

	

	$(".magnify").mouseleave(function(e){
		$(".large").hide();
	});
	$(".magnify").mousemove(function(e){


    		if (timeoutid) {
			clearTimeout(timeoutid);
			timeoutid = 0;
		}

		var idattr = $(this).attr('id'); 
		var magnify_offset = $(this).offset();

		var container =$(this);
		var grande =$(this).find(".large");
		var peque =$(this).find(".small");
	
			if(idattr=="foto1"){
				urlImage=urlImg1;
				if(showMenu){
					return true;
				}	
			}
			if(idattr=="foto2"){
				urlImage=urlImg2;	
			
			}
			if(idattr=="foto3"){
				if(showPanel){
					return true;
				}	
				urlImage=urlImg3;	
			
			}
			if(showMascara){
				return true;
			}

			

			grande.css('background','url('+urlImage+') no-repeat');
			//When the user hovers on the image, the script will first calculate
			//the native dimensions if they don't exist. Only after the native dimensions
			//are available, the script will show the zoomed version.
			native_width = 700;
			native_height = 900;
			if(!native_width && !native_height)
			{
				//This will create a new image object with the same image as that in .small
				//We cannot directly get the dimensions from .small because of the 
				//width specified to 200px in the html. To get the actual dimensions we have
				//created this image object.
				var image_object = new Image();


				image_object.src = peque.attr("src");
				
				//This code is wrapped in the .load function which is important.
				//width and height of the object would return 0 if accessed before 
				//the image gets loaded.
				native_width = image_object.width;
				native_height = image_object.height;
			}
			else
			{
				//x/y coordinates of the mouse
				//This is the position of .magnify with respect to the document.
				//We will deduct the positions of .magnify from the mouse positions with
				//respect to the document to get the mouse positions with respect to the 
				//container(.magnify)
				var mx = e.pageX - magnify_offset.left;
				var my = e.pageY - magnify_offset.top;
				
				//Finally the code to fade out the glass if the mouse is outside the container
				if(mx < container.width() && my < container.height() && mx > 0 && my > 0)
				{
					grande.fadeIn(100);
				}
				else
				{
					grande.fadeOut(100);
				}
				if(grande.is(":visible"))
				{
					//The background position of .large will be changed according to the position
					//of the mouse over the .small image. So we will get the ratio of the pixel
					//under the mouse pointer with respect to the image and use that to position the 
					//large image inside the magnifying glass
					var rx = Math.round(mx/$(".small").width()*native_width - $(".large").width()/2)*-1;
					var ry = Math.round(my/$(".small").height()*native_height - $(".large").height()/2)*-1;
					var bgp = rx + "px " + ry + "px";
					
					//Time to move the magnifying glass with the mouse
					var px = mx - grande.width()/2;
					var py = my - grande.height()/2;
					//Now the glass moves with the mouse
					//The logic is to deduct half of the glass's width and height from the 
					//mouse coordinates to place it with its center at the mouse coordinates
					
					//If you hover on the image now, you should see the magnifying glass in action
					grande.css({left: px, top: py, backgroundPosition: bgp});
				}
			}
	})

});


function opeItem(ope,productO,varietyO){
	//var route = "{{ path('macson_chart', { 'pid': 'PLACEHOLDER1' , 'vid':'PLACEHOLDER2' }) }}";
		urlPHP=route.replace("PLACEHOLDER1",ope);
		urlPHP=urlPHP.replace("PLACEHOLDER2",productO);
		urlPHP=urlPHP.replace("PLACEHOLDER3",varietyO);
		$.ajax({
			type: "POST",
			url: urlPHP,
			dataType: 'json',
			success: function(cart){
					$("#mascara").show(200);
					$("#listaCarritoResumen").empty();	
					$("#listaCarritoResumen_mob").empty();	
					$("#carritoResumen").empty();	
					$("#sumItems").empty();	
					$("#precioTotal").empty();	
					
					if(cart.length > 0){
						precioTotal=0,00;
						sumItems=0;
						$.each(cart, function( index, item ) {
						precioTotal=precioTotal+item.product.pricePVP*parseInt(item.count);
						sumItems=sumItems+item.count;
							$("#listaCarritoResumen").append('<div class="row height102 pad5"><div class="cell"><a href="../../../'+lang+'/'+item.product.target+'"><img src="'+imageThumbPath+item.product.sgu+'_1.jpg"></a></div><div class="cell width85 pad5"><p class="fs10">'+item.product.sgu+'</p></div><div class="cell width180 pad5">' + item.product.title+'</div><div class="cell width50 pad5">'+item.product.size+'</div><div class="cell width100 pad5">'+item.product.pricePVP+' €</div><div class="cell width50 pad5">'+item.count+'</div><div class="cell width50 pad5"><div class="borrarArticulo"  product="'+item.product.id+'" variety="'+item.product.varProdId+'">'+removeText+'</div></div>');	
							$("#listaCarritoResumen_mob").append('<div class="row pad5"><div class="mg-top10"><a href="../../../'+lang+'/'+item.product.target+'"><img src="'+imageThumbPath+item.product.sgu+'_1.jpg"></a></div><div class="pad5"><p class="fs10">'+item.product.sgu+'</p></div><div class="pad5">' + item.product.title+'</div><div class="pad5">'+sizeText+':'+item.product.size+'</div><div class="pad5">'+item.product.pricePVP+' €</div><div class="pad5">'+unitsText+":"+item.count+'</div><div class="pad5"><div class="borrarArticulo borderBottom"  product="'+item.product.id+'" variety="'+item.product.varProdId+'">'+removeText+'</div></div>');	
						});
						precioTotal = precioTotal.toFixed(2);
						$("#precioTotal").append('<b>'+precioTotal+' €</b>');	
						$("#sumItems").append('('+sumItems+') |');	
						//$("#carrito").hide(200);			
							$("#listaCarritoResumen_mob").trigger('create');
							$("#listaCarritoResumen").trigger('create');
							$( "#carritoId_mob" ).show();
							$( "#carritoId" ).show();
						$("#carritoResumen").append('<img src="'+imageCarrito+'">');	
					}
					else{
						$("#listaCarritoResumen").append('<div class="row">'+noProductCartText+'</div>');
						$("#listaCarritoResumen_mob").append('<div class="row">'+noProductCartText+'</div>');
						$(".carrito").hide(200);			
						$("#mascara").hide(200);
						showMascara=false;
					}
			}	
		 })


}




