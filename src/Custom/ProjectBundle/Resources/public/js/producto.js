
$(document).ready(function(){

	mobile=false;

	var ancho=screen.width;

	if( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) ) {

		if(ancho>700){

 		var mobile=true;
		$('body').css({'letter-spacing': '11px'});
		$('.fotoA').hide();
		$("#portada").attr('id', 'portada_mob');
		$("#menu").attr('id', 'menu_mob');
		$("#header").attr('id', 'header_mob');
		$("#logo").attr('id', 'logo_mob');
		$(".tagPriceProducto").attr('id', 'tagPriceProducto_mob');
		$(".tagPrice").attr('class', 'tagPrice_mob');
		$(".tagName").attr('class', 'tagName_mob');
		$(".tagName_mob").hide();
		$(".tagAgotado").attr('class', 'tagAgotado_mob');
		$(".item1").attr('class', 'item1_mob');
		$(".item2").attr('class', 'item2_mob');
		$(".item3").attr('class', 'item3_mob');
		$(".item4").attr('class', 'item4_mob');
		$(".foto_blank").attr('class', 'foto_blank_mob');
		$(".foto_blank1").attr('class', 'foto_blank1_mob');
		$(".fotoRow").attr('class', 'fotoRow_mob');
		$(".foto").attr('class', 'foto_mob');
		$(".precioDecimal").attr('class', 'precioDecimal_mob');
		$(".tachado").attr('class', 'tachado_mob');
		$("#footer").hide();
		$("#menuImg").remove();
		$(".foto_blank_mob").hide();


		


    		var str = $("#logoImg").attr('src');
		$("#logoImg").attr("src",str.replace(".png","_mob.png"));


		$('.fotoA').each(function(){
    			var str = $(this).attr('src');
			$(this).attr("src",str.replace("Standard","Original"));
		});


		$('.fotoA').show();

		}
		else{


		$('body').css({'letter-spacing': '5px'});
		$("#portada").attr('id', 'portada_mob2');
		$("#menu").attr('id', 'menu_mob2');
		$("#header").attr('id', 'header_mob2');
		$(".tagPriceProducto").attr('id', 'tagPriceProducto_mob2');
		$(".tagPrice").attr('class', 'tagPrice_mob2');
		$(".tagName").attr('class', 'tagName_mob2');
		$(".tagName_mob").hide();
		$(".tagAgotado").attr('class', 'tagAgotado_mob2');
		$(".item1").attr('class', 'item1_mob2');
		$(".item2").attr('class', 'item2_mob2');
		$(".item3").attr('class', 'item3_mob2');
		$(".item4").attr('class', 'item4_mob2');
		$(".fotoRow").attr('class', 'fotoRow_mob2');
		$(".precioDecimal").attr('class', 'precioDecimal_mob2');
		$(".tachado").attr('class', 'tachado_mob2');
		$("#footer").hide();
		$("#menuImg").remove();
		$(".foto_blank_mob").hide();


		





		}

	}

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

	$("#infoPanel").hide();
	$('#zoomContainer').hide();
	$("#mascara").hide();
	$("#loading").hide();
	$("#contenedor1").css('visibility','visible');
	$("#cuidadosPanel").hide();
	$(".novedad").hide();
	$("#orderList").hide();
		
		$("#login").hide();
		$("#carrito").hide();


	
	var showPanel=false;
	var showPanelCuidados=false;
	var showMascara=false;


	
	$('#mascara').click(
		function zoomImageOut(){
			$('#zoomContainer').hide();
			$("#mascara").hide(200);
			$("#login").hide(200);
			$("#carrito").hide(200);
			showMascara=false;
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

	$(".itemTalla").click(  
		function selectSize(){
			var index;
			sizeSelected = 1;
			for (index = 0; index < varieties.length; ++index) {   
    				$( "#tallas li" ).eq( index +1).css("background-color","inherit");
				if (varietiesVal[index]==$( this ).text()){
					$(this).css("background-color","red");
					variety=varieties[index];
				}
			}	

		});


	$("#continue").click(

		function continueShopping(){
			$("#carrito").hide();
			$("#mascara").hide();
			showMascara=false;
		}
	);
	//Hide Show In panel-------------------------------------------------
	

	$("#loginMenu").click(

		function showLogin(){
			$("html,body").scrollTop(0);
			$("#carrito").hide();
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
			$("#carrito").show(200);
			$(".large").hide();
			$("#mascara").show(200);
			showMascara=true;
			return false;
		}
	);

	$("#sumItems").click(

		function showCart(){
			$("#login").hide();
			$("#carrito").show(200);
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

	if($("#tipoPagina").text()=="noLoginNoChart"){
		
		$('#loginMenu').hide();
		$('#carritoResumen').hide();
		$('#sumItems').hide();

	}

	if($("#tipoPagina").text() == 'prod1' || $("#tipoPagina").text() == 'prod2' || $("#tipoPagina").text() == 'prod3'){


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

		

	}	

	if($("#tipoPagina").text() == 'cat' || $("#tipoPagina").text() == 'con'){
		$("#menu ul").css('background-image', 'none');
	    $(".tagName").hide();

	    $('.foto').hover(
			function () {
			    var foto = $(this);
			    foto.children( '.tagPrice' ).hide();
			    foto.children( '.tagName' ).show();
			    foto.find( '.fotoImg' ).fadeOut(700);
			},
			function () {
			    var foto = $(this);
			    foto.children( '.tagPrice' ).show();
			    foto.children( '.tagName' ).hide();
			    foto.find( '.fotoImg' ).fadeIn(700);
			}
			
	    );
	}	


	if($("#tipoPagina").text() == 'prod1'){
		$("#infoPanel").show();
		$("#menu ul").show();
		$("#menu ul").css('background-image', 'none');
		$("#tituloCat").text("");
		$("#masInfo").text("");
		showMenu=true;
	}	

	if($("#tipoPagina").text() == 'prod2'){
		$("#infoPanel").hide();
		$("#masInfo").text("");		
		$("#menu ul").show();
		$("#menu ul").css('background-image', 'none');
		$("#tituloCat").text("");
		showMenu=false;
	}		

	if($("#tipoPagina").text() == 'prod3'){

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
	var native_width = 0;
	var native_height = 0;

	var timeoutid = 0;
	var este= $(this);
	
	//Now the mousemove function


	$(".magnify").mousemove(function(e){


    		if (timeoutid) {
			clearTimeout(timeoutid);
			timeoutid = 0;
		}

		var idattr = $(this).attr('id' ); 
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
					$("#carritoResumen").empty();	
					$("#sumItems").empty();	
					$("#precioTotal").empty();	
					
					if(cart.length > 0){
						precioTotal=0,00;
						sumItems=0;
						$.each(cart, function( index, item ) {
							precioTotal=precioTotal+item.product.pricePVP*parseInt(item.count);
							sumItems=sumItems+item.count;

							$("#listaCarritoResumen").append('<div class="row height102 pad5"><div class="cell"><a href="../../'+lang+'/'+item.product.target+'"><img src="'+imageThumbPath+item.product.sgu+'_1.jpg"></a></div><div class="cell width85 pad5"><p class="fs10">'+item.product.sgu+'</p></div><div class="cell width180 pad5">' + item.product.title+'</div><div class="cell width50 pad5">'+item.product.size+'</div><div class="cell width100 pad5">'+item.product.pricePVP+' €</div><div class="cell width50 pad5">'+item.count+'</div><div class="cell width50 pad5"><div class="borrarArticulo"  product="'+item.product.id+'" variety="'+item.product.varProdId+'">'+removeText+'</div></div>');	
						});
						precioTotal = precioTotal.toFixed(2);
						$("#precioTotal").append('<b>'+precioTotal+' €</b>');	
						$("#sumItems").append('('+sumItems+') |');	
						//$("#carrito").hide(200);			
						$("#listaCarritoResumen").trigger('create');
						$("#carritoResumen").append('<img src="'+imageCarrito+'">');	
						$("#carrito").show(200);			
					}
					else{
						$("#listaCarritoResumen").append('<div class="row">'+noProductCartText+'</div>');
						$("#carrito").hide(200);			
						$("#mascara").hide(200);
						showMascara=false;
					}
			}	
		 })


}




