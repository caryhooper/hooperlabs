<html>

<?php include 'header.php';?>

<body>

<?php include 'navbar.php';?>

    <div class="body-content">
        <h3>
            ERROR!
        </h3>
        <p class="text">
		You have reached this page in error.  Please try again.
        <?php
            $http_codes = array(100,101,102,103,200,201,202,203,204,206,207,300,301,302,303,304,305,307,308,400,401,402,403,404,405,406,407,408,409,410,411,412,413,414,415,416,417,418,420,421,422,423,424,425,426,429,431,444,450,451,497,498,499,500,501,502,503,504,506,507,508,509,510,511,521,522,523,525,599);
            $random_index = array_rand($http_codes);
            echo '<img src="https://http.cat/'.strval($http_codes[$random_index]).'">';
        ?>
        </p>
        <br>
    </div>
</body>
<?php include 'footer.php';?>

</html>
