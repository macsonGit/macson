
$(document).ready(function(){




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
	$("#mascara").hide();
	$('#zoomContainer').hide();
	$("#login").hide();
	$("#carrito").hide();
	$("#loading").hide();
	$("#contenedor1").css('visibility','visible');
	$("#cuidadosPanel").hide();
	
	var showPanel=false;
	var showPanelCuidados=false;


	
	$('#mascara').click(
		function zoomImageOut(){
			$('#zoomContainer').hide();
			$("#mascara").hide(200);
			$("#login").hide(200);
			$("#carrito").hide(200);
		}
	);	

	$("#boton1").click(  
		function addItem(){
			ope='add';
			//var route = "{{ path('macson_chart', { 'pid': 'PLACEHOLDER1' , 'vid':'PLACEHOLDER2' }) }}";
			if (sizeSelected==1){
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
		}
	);
	//Hide Show In panel-------------------------------------------------
	

	$("#loginMenu").click(

		function showLogin(){
			window.scrollTo(0, 0);
			$("#carrito").hide();
			$("#login").show(200);
			$("#mascara").show(200);
		}
	);
	$("#carritoResumen").click(

		function showCart(){
			windowndow.scrollTo(0, 0);
			$("#login").hide();
			$("#carrito").show(200);
			$("#mascara").show(200);
		}
	);

	$("#sumItems").click(

		function showCart(){
			$("#login").hide();
			$("#carrito").show(200);
			$("#mascara").show(200);
		}
	);

	$("#masInfo").click( 

		function showInfo(){

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

	if($("#tipoPagina").text() == 'prod' || $("#tipoPagina").text() == 'prod1' || $("#tipoPagina").text() == 'prod2'){


		$('#menu').click(
			function zoomImage(){
				if (!showMenu){
					$('#zoomContainer').show();
					$('#zoomContainer').html('<img src=\''+urlImg1+'\'>');
					$("#mascara").show(200);
				}
			}
		);

		


		$('img.fotoImg2').click(
			function zoomImage(){
				$('#zoomContainer').show();
				$('#zoomContainer').html('<img src=\''+urlImg2+'\'>');
				$("#mascara").show(200);
			}
		);
		$('img.fotoImg3').click(
			function zoomImage(){
				$('#zoomContainer').show();
				$('#zoomContainer').html('<img src=\''+urlImg3+'\'>');
				$("#mascara").show(200);
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

	if($("#tipoPagina").text() == 'cat'){
		$("#menu ul").css('background-image', 'none');
	    $(".tagName").hide();

	    $('img.fotoImg').hover(
			function () {
			    var $this = $(this);
			    var newSource = $this.data('alt-src');
			    $this.data('alt-src', $this.attr('src'));
			    $this.attr('src', newSource);
			    $this.children( '.tagPrice' ).hide();
			}			
	    );
	    $('.foto').hover(
			function () {
			    var $this = $(this);
			    $this.children( '.tagPrice' ).hide();
			    $this.children( '.tagName' ).show();
			},
			function () {
			    var $this = $(this);
			    $this.children( '.tagPrice' ).show();
			    $this.children( '.tagName' ).hide();
			}
			
	    );
	}	

	if($("#tipoPagina").text() == 'prod'){


		$("#menu ul").hide();
		showMenu=false;

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
		$("#infoPanel").show();
		$("#masInfo").text("");		
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
				$("#menu").height(23);
			}
			else{
				showMenu=true;
				$("#tituloCat").text("- MENU");
				$("#menu ul").show(350);

				$("#menu").height(440);
				
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

							$("#listaCarritoResumen").append('<div class="row height102 pad5"><div class="cell"><a href="../'+lang+'/'+item.product.target+'"><img src="'+imageThumbPath+item.product.sgu+'_1.jpg"></a></div><div class="cell width85 pad5"><p class="fs10">'+item.product.sgu+'</p></div><div class="cell width180 pad5">' + item.product.title+'</div><div class="cell width50 pad5">'+item.product.size+'</div><div class="cell width100 pad5">'+item.product.pricePVP+' €</div><div class="cell width50 pad5">'+item.count+'</div><div class="cell width50 pad5"><div class="borrarArticulo"  product="'+item.product.id+'" variety="'+item.product.varProdId+'">'+removeText+'</div></div>');	
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
					}
			}	
		 })

}




