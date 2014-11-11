
$(document).ready(function(){

	$("#infoPanel").hide();
	$("#mascara").hide();
	$('#zoomContainer').hide();

	$('#mascara').click(
		function zoomImageOut(){
			$('#zoomContainer').hide();
			$("#mascara").hide(200);
			$("#login").hide(200);
		}
	);	

	$("#boton1").click(  
		function compraItem(){
			$("#footer").css("background-color","yellow");

		});

	//Hide Show In panel-------------------------------------------------
	
	$("#infoPanel").hide();
	$("#mascara").hide();

	$("#login").hide();

	var showPanel=false;

	$("#loginMenu").click(

		function showLogin(){
			$("#login").show(200);
			$("#mascara").show(200);
		}
	);


	$("#masInfo").click( 

		function showInfo(){

			if(showPanel){
				$("#infoPanel").hide(350,
					function(){
						$("#masInfo").text("+ Info");
						showPanel=false;
					}
				);
			}
			else{
				$("#infoPanel").show(350,
					function(){
						$("#masInfo").text("- Info");
						showPanel=true;
					}
				);
			}			
		
		}

	);

	//Hide Show MMenu-------------------------------------------------


	if($("#tipoPagina").text() == 'prod' || $("#tipoPagina").text() == 'prod1' || $("#tipoPagina").text() == 'prod2'){


		$('img.fotoImg1').click(
			function zoomImage(){
				$('#zoomContainer').show();
				$('#zoomContainer').html('<img src=\''+urlImg1+'\'>');
				$("#mascara").show(200);
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
				$('#zoomContainer').hide();
				$("#mascara").hide(200);
			}
		);		
		
	}	


	if($("#tipoPagina").text() == 'hom'){



	}	

	if($("#tipoPagina").text() == 'cat'){
		$("#menu ul").css('background-image', 'none');

	    $('img.fotoImg').hover(
			function () {
			    var $this = $(this);
			    var newSource = $this.data('alt-src');
			    $this.data('alt-src', $this.attr('src'));
			    $this.attr('src', newSource);
			}
    	);
	}	

	if($("#tipoPagina").text() == 'prod'){


		$("#menu ul").hide();

	}	

	if($("#tipoPagina").text() == 'prod1'){
		$("#infoPanel").show();
		$("#menu ul").show();
		$("#menu ul").css('background-image', 'none');
		$("#tituloCat").text("");
		$("#masInfo").text("");
	}	

	if($("#tipoPagina").text() == 'prod2'){
		$("#infoPanel").show();
		$("#masInfo").text("");		
		$("#menu ul").hide();
	}		

	$("#menu").height(23);	

	var showMenu=false;

	var contador=0;

	$("#tituloCat").click( 

		function showMenuA(){
			if(showMenu){
				showMenu=false;
				$("#tituloCat").text("+ Menu");
				$("#menu ul").hide(350);
				$("#menu").height(23);
			}
			else{
				showMenu=true;
				$("#tituloCat").text("- Menu");
				$("#menu ul").show(350);

				$("#menu").height(440);
				
			}			

		}

	);

	$("#menu li").hover(

		function shiftItem(){
			$(this).animate({
				'padding-left':"+=15"
			 }, 250);
		},
		function shiftItem(){
			$(this).animate({
				'padding-left':"-=15"
			 }, 350);

		}

	);	

});






