<?php

if (
    isset($_POST) && (
    $_SERVER['HTTP_ORIGIN'] == 'https://' . $_SERVER['HTTP_HOST']
    || $_SERVER['HTTP_ORIGIN'] == 'http://' . $_SERVER['HTTP_HOST']
    )
) {
    $ta_id = $_POST['trust_anchor_id'];
    $op_id = $_POST['provider_id'];

    ?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
        <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no" />
        <link rel="preconnect" href="https://fonts.googleapis.com" /> 
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@200;300;400;600;700;900&display=swap" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" crossorigin="anonymous" />
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous" />
        <link rel="stylesheet" href="assets/spid-sp-access-button/css/spid-sp-access-button.min.css" />                
        <link rel="stylesheet" href="assets/css/style.css" />
        <link rel="stylesheet" href="assets/css/custom.css" />
        <title>SPID CIE OIDC PHP</title>
    </head>
    <body>
        <header aria-label="Funzioni di servizio">
            <div class="bg-transparent my-header" id="page-header">
                <div class="row align-items-sm-center">
                    <div class="col-auto pr-0 pr-md-2">
                        <img src="assets/img/logo.png" alt="" class="logo my-2">
                    </div>
                    <div class="col">
                        <h1>
                            SPID CIE OIDC PHP
                        </h1>
                    </div>
                </div>
            </div>
        </header>
        <section>
    <?php
    echo "<div class=\"p-5\">";
    foreach ($_POST as $k => $v) {
        echo "<p>" . $k . ": <b>" . $v . "</b></p>";
    }
    echo "<a class=\"btn btn-primary btn-lg m-2\" href=\"oidc/rp/introspection?ta_id=" . urlencode($ta_id) . "&op_id=" . urlencode($op_id) . "\".>Introspection</a>";
    echo "<a class=\"btn btn-primary btn-lg m-2\" href=\"oidc/rp/logout\">Esci</a>";
    echo "</div>";
    ?>

        </section>

    </body>
</html>


    <?php
} else {
    header("Location: oidc/rp/authz");
}

?>