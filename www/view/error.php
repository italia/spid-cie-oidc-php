<!DOCTYPE html>
<html lang="it">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=Edge" />
        <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no" />
        <link rel="stylesheet" href="<?php echo $BASEURL; ?>/assets/css/style.css" />
        <link rel="stylesheet" href="<?php echo $BASEURL; ?>/assets/css/custom.css" />
        <link rel="preconnect" href="https://fonts.googleapis.com" /> 
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Titillium+Web:wght@200;300;400;600;700;900&display=swap" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    </head>
    <body>
        <div id="root">
            <header aria-label="Funzioni di servizio">
                <div class="bg-transparent my-header" id="page-header">
                    <div class="row align-items-sm-center">
                        <div class="col-auto pr-0 pr-md-2">
                            <img src="<?php echo $BASEURL; ?>/assets/img/logo.png" alt="" class="logo my-2">
                        </div>
                        <div class="col">
                            <h1>
                                SPID CIE OIDC PHP
                            </h1>
                        </div>
                    </div>
                </div>
            </header>
            <div id="login" class="container-fluid d-flex flex-column justify-content-between py-3 py-md-4">
                <div id="loginPage" class="d-flex flex-column justify-content-between">
                    <main id="main" class="mb-5">
                        <div id="login-form" class="shadow mx-auto mt-3">
                            <h2 class="h3">Attenzione</h2>
                            <p>Si è verificato un errore durante l'autenticazione.</p>
                            <p class="alert alert-danger" role="alert"><strong><?php echo $error_description; ?></strong></p>
                            <p>Si prega di riprovare ad accedere dalla pagina del servizio.</p>
                        </div>
                    </main>
                </div>
            </div>
            <footer id="page-footer">
                <div class="container-fluid pb-3">
                    <hr aria-hidden="true" />
                    <ul class="list-inline mb-0 w-100">
                        <li class="list-inline-item">
                            <a href="#">Privacy</a>
                        </li>
                        <li class="list-inline-item">
                            <a href="#">Note legali</a>
                        </li>
                    </ul>
                </div>
            </footer>
        </div>

    </body>
</html>
