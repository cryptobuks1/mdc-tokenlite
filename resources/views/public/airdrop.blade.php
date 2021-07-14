<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>MDT AIR DROP</title>


	<style type="text/css">
		body {
			width:520px;
			margin:0; padding:0; border:0;
			font-family: verdana;
			background:url(repeat.png) repeat;
			margin-bottom:10px;
			}
			p, h1 {width:450px; margin-left:50px; color:#FFF;}
			p {font-size:11px;}

			#container_notlike, #container_like {
			    display:none
			}
	</style>
</head>
<body>
<div id="fb-root"></div>
<script src="http://connect.facebook.net/en_US/all.js"></script>
<script>
  FB.init({
    appId  : '207659214596052',
    status : true, 
    cookie : true, 
    xfbml  : true  
  });
</script>

<div id="container_notlike">
YOU DONT LIKE
</div>

<div id="container_like">
YOU LIKE
</div>




<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script type="text/javascript">
	$(document).ready(function(){

    FB.login(function(response) {
      if (response.session) {

          var user_id = response.session.uid;
          var page_id = "40796308305"; //coca cola
          var fql_query = "SELECT uid FROM page_fan WHERE page_id = "+page_id+"and uid="+user_id;
          var the_query = FB.Data.query(fql_query);

          the_query.wait(function(rows) {

              if (rows.length == 1 && rows[0].uid == user_id) {
                  $("#container_like").show();

                  //here you could also do some ajax and get the content for a "liker" instead of simply showing a hidden div in the page.

              } else {
                  $("#container_notlike").show();
                  //and here you could get the content for a non liker in ajax...
              }
          });


      } else {
        // user is not logged in
      }
    });

});
</script>
</body>
</html>