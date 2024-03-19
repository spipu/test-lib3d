<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="en">
    <head>
        <title>Spipu.net - Test 3D</title>
        <style type="text/css">
            body {
                background-color: black;
                text-align: center;
                color: lightgray;
            }
            img {
                border: solid 1px grey;
                min-width: 320px;
                min-height: 200px;
            }
        </style>
    </head>
    <body onload="displayImage();">
        <form method="get" action="" onsubmit="return false;">
            <label for="fichier">Fichier</label>
            <select id="fichier">
                <option value="image_cube.php?"           >Cube</option>
                <option value="image_light.php?"          >Lumiere</option>
                <option value="image_objet.php?o=helico"  >Objet Helico</option>
                <option value="image_objet.php?o=lotus"   >Objet Lotus</option>
                <option value="image_objet.php?o=tank"    >Objet Tank</option>
                <option value="image_objet.php?o=tore"    >Objet Tore</option>
                <option value="image_objet.php?o=voiture" >Objet Voiture</option>
            </select> -
            <label for="val1">Angle 1 :</label> <input type="text" id="val1" value="120"> -
            <label for="val2">Angle 2 :</label> <input type="text" id="val2" value="30"> -
            <input type="button" value="Afficher" onclick="displayImage();">
        </form>
        <hr />
        <img id='obj_image' src="" alt="image" onload="nextImage()">
        <script type="text/javascript">
            function displayImage()
            {
                let url = document.getElementById('fichier').value;
                let va1 = parseInt(document.getElementById('val1').value);
                let va2 = parseInt(document.getElementById('val2').value);

                va1 = va1%360; if (va1<0) va1+= 360;
                va2 = va2%360; if (va2<0) va2+= 360;

                document.getElementById('val1').value = va1;
                document.getElementById('val2').value = va2;

                url+= '&va1='+va1+'&va2='+va2+'&rand=' + (new Date()).getTime();
                document.getElementById('obj_image').src = url;
            }

            function nextImage() {
                let input = document.getElementById('val2')
                input.value = parseInt(input.value) + 2;
                setTimeout('displayImage();', 10);
            }
        </script>
    </body>
</html>