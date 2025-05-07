<?php include_once './database_connect.php';?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Potęga Smaku</title>
    <link rel="stylesheet" href="./css/styl.css">
</head>
<body>
<!-- div pierwszy -->
    <div class="logo_div step1">
        <p class="monopoly size1">MONOPOLY</p>
        <img src="./zdj/logo.png" alt="Potega Smakow" class="logo_zdj">
        <button class="next nextright" onclick="one()"><p class="nextstep size3">	&larr;</p></button>
      </div>
      
<!-- div drugi -->
      <div class="logo_div step2" >
        <!-- style="display: none; opacity: 0;" -->
        <p class="monopoly size1">Podaj liczbe <br> graczy</p><br>
        <p id="playerCount" class="size2">2</p>
        <label for="quantity">
            <input type="range" class=" rangeplayer" name="quantity" id="quantity" min="2" max="4" value="2">
        </label>
        <button class="next nextleft" onclick="two()"><p class="nextstep size3">	&rarr;</p></button>
      </div>

<!-- div trzeci -->
      <!-- <div class="logo_div step3" >
        <p class="monopoly size1">Podaj niki <br> graczy</p><br>
        <p id="playerCount" class="size2">2</p>
        
        <button class="next nextleft" onclick="two()"><p class="nextstep size3">	&rarr;</p></button>
        <!-- <div class="player">
          <label for=""><p>Gracz 1</p><input type="text" name="" id=""></label>
        </div>
        <div class="player">
          <label for=""><p>Gracz 2</p><input type="text" name="" id=""></label>
        </div> -->
      </div>
      
      <img src="./zdj/tlo3.png" alt="" class="backg">
      
      <script src="./js/script_main.js"></script> -->
</body>
</html>
<!-- vw mixin
 clamp css
 bem css
  -->