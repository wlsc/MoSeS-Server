$(".btnApplyScientistSend").click(function(b){b.preventDefault();var a=$(this);a.removeClass("btn-success");a.attr("disabled",!0);a.text("Working...");$.ajax({type:"POST",url:"content_provider.php",data:$(".apply_scientist_form").serialize()}).done(function(b){"1"==b?$(".hero-unit").html('<h2 class="text-center">Your scientist application was sent!</h2>'):(a.addClass("btn-success"),a.text("Send"),a.attr("disabled",!1),alert("Something went wrong! Please, try again later."))})});