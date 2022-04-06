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
        <link rel="stylesheet" href="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/css/spid-sp-access-button.min.css" />                
        <link rel="stylesheet" href="<?php echo $BASEURL; ?>/assets/css/style.css" />
        <link rel="stylesheet" href="<?php echo $BASEURL; ?>/assets/css/custom.css" />
        <title>SPID CIE OIDC PHP</title>
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
                        <div id="login-form" class="login-form-lg shadow mx-auto mt-3">
                            <h2 class="h3">Accedi con identità digitale</h2>

                            <ul class="nav nav-tabs flex-sm-row flex-sm-nowrap" id="myTab" role="tablist">
                                <li class="nav-item text-sm-center" role="presentation">
                                    <button class="nav-link h-100 px-4 active" id="tab-spid" data-bs-toggle="tab" data-bs-target="#panel-spid" type="button" role="tab" aria-controls="home" aria-selected="true">SPID</button>
                                </li>
                                <li class="nav-item text-sm-center" role="presentation">
                                    <button class="nav-link h-100 px-4" id="tab-cie" data-bs-toggle="tab" data-bs-target="#panel-cie" type="button" role="tab" aria-controls="profile" aria-selected="false">CIE</button>
                                </li>
                            </ul>
                            <div class="tab-content" id="tab-content">
                                <div class="tab-pane fade show active" id="panel-spid" role="tabpanel" aria-labelledby="tab-spid">
                                    <h3 class="sr-only">Accedi con identità digitale SPID</h3>
                                    <p>SPID, il&nbsp;<strong>Sistema Pubblico di Identità Digitale</strong>&nbsp;è il sistema di accesso che consente di utilizzare, con un'identità digitale unica, i servizi online della Pubblica Amministrazione e dei privati accreditati. Se sei già in possesso di un'identità digitale, accedi con le credenziali del tuo gestore. Se non hai ancora un'identità digitale, richiedila ad uno dei gestori.</p>

                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-6">
                                            <ul class="list-link px-1">
                                                <li class="mb-1">
                                                    <a href="https://www.spid.gov.it/" target="_blank" rel="noopener noreferrer">
                                                        <span class="sr-only">Apre una nuova finestra</span>Maggiori informazioni su SPID</a>
                                                </li>
                                                <li class="mb-1">
                                                    <a href="https://www.spid.gov.it/richiedi-spid" target="_blank" rel="noopener noreferrer">
                                                        <span class="sr-only">Apre una nuova finestra</span>Non hai SPID?</a>
                                                </li>
                                                <li class="mb-1">
                                                    <a href="https://www.spid.gov.it/serve-aiuto" target="_blank" rel="noopener noreferrer">
                                                        <span class="sr-only">Apre una nuova finestra</span>Serve aiuto?</a>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="col-12 col-md-6 text-center">
                               
                                            <!-- AGID - SPID IDP BUTTON MEDIUM "ENTRA CON SPID" * begin * -->
                                            <a href="#" class="italia-it-button italia-it-button-size-m button-spid" spid-idp-button="#spid-idp-button-medium-get" aria-haspopup="true" aria-expanded="false">
                                                <span class="italia-it-button-icon"><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-ico-circle-bb.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-ico-circle-bb.png'; this.onerror=null;" alt="" /></span>
                                                <span class="italia-it-button-text">Entra con SPID</span>
                                            </a>
                                            <div id="spid-idp-button-medium-get" class="spid-idp-button spid-idp-button-tip spid-idp-button-relative">
                                                <ul id="spid-idp-list-medium-root-get" class="spid-idp-button-menu" aria-labelledby="spid-idp">    
                                                    <li class="spid-idp-button-link" data-idp="test">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=TEST"><span class="spid-sr-only">TEST</span><img src="<?php echo $BASEURL; ?>/assets/img/spid_test_provider.png" onerror="this.src='<?php echo $BASEURL; ?>/assets/img/spid_test_provider.png'; this.onerror=null;" alt="Provider TEST" /></a>
                                                    </li>   
                                                    <!--
                                                    <li class="spid-idp-button-link" data-idp="arubaid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=ArubaPEC S.p.A."><span class="spid-sr-only">Aruba ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-arubaid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-arubaid.png'; this.onerror=null;" alt="Aruba ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="infocertid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=InfoCert S.p.A."><span class="spid-sr-only">Infocert ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-infocertid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-infocertid.png'; this.onerror=null;" alt="Infocert ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="intesaid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=IN.TE.S.A. S.p.A."><span class="spid-sr-only">Intesa ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-intesaid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-intesaid.png'; this.onerror=null;" alt="Intesa ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="lepidaid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=Lepida S.p.A."><span class="spid-sr-only">Lepida ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-lepidaid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-lepidaid.png'; this.onerror=null;" alt="Lepida ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="namirialid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=Namirial"><span class="spid-sr-only">Namirial ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-namirialid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-namirialid.png'; this.onerror=null;" alt="Namirial ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="posteid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=Poste Italiane SpA"><span class="spid-sr-only">Poste ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-posteid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-posteid.png'; this.onerror=null;" alt="Poste ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="sielteid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=Sielte S.p.A."><span class="spid-sr-only">Sielte ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-sielteid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-sielteid.png'; this.onerror=null;" alt="Sielte ID" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="spiditalia">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=Register.it S.p.A."><span class="spid-sr-only">SPIDItalia Register.it</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-spiditalia.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-spiditalia.png'; this.onerror=null;" alt="SpidItalia" /></a>
                                                    </li>
                                                    <li class="spid-idp-button-link" data-idp="timid">
                                                        <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://trust-anchor.org:8000/oidc/op/'); ?>?state=TI Trust Technologies srl"><span class="spid-sr-only">Tim ID</span><img src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-timid.svg" onerror="this.src='<?php echo $BASEURL; ?>/assets/spid-sp-access-button/img/spid-idp-timid.png'; this.onerror=null;" alt="Tim ID" /></a>
                                                    </li>
                                                    -->
                                                    <li class="spid-idp-support-link">
                                                        <a href="https://www.spid.gov.it">Maggiori informazioni</a>
                                                    </li>
                                                    <li class="spid-idp-support-link">
                                                        <a href="https://www.spid.gov.it/richiedi-spid">Non hai SPID?</a>
                                                    </li>
                                                    <li class="spid-idp-support-link">
                                                        <a href="https://www.spid.gov.it/serve-aiuto">Serve aiuto?</a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <!-- AGID - SPID IDP BUTTON MEDIUM "ENTRA CON SPID" * end * -->
                                        
                                        </div>
                                        <img id="spid-agid" class="img-fluid mx-auto" src="<?php echo $BASEURL; ?>/assets/img/spid-agid-logo-lb.png" alt="Logo SPID - AGID - Agenzia per l'Italia Digitale">
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="panel-cie" role="tabpanel" aria-labelledby="tab-cie">
                                    <h3 class="sr-only">Accedi con identità digitale CIE</h3>
                                    <p>La <strong>Carta di Identità Elettronica</strong> (CIE) è il documento personale che attesta l'identità del cittadino. 
                                        Dotata di microprocessore, oltre a comprovare l'identità personale, permette l'accesso ai servizi digitali della Pubblica Amministrazione.</p>

                                    <div class="row align-items-center">
                                        <div class="col-12 col-md-6">
                                            <a class="my-3" href="https://www.cartaidentita.interno.gov.it/" target="_blank" rel="noopener noreferrer">
                                                <span class="sr-only">Apre una nuova finestra</span>Maggiori informazioni
                                            </a>
                                        </div>
                                        <div class="col-12 col-md-6 text-center">
                                            <li class="btn p-0 border-0 my-3">
                                                <a href="<?php echo $BASEURL; ?>/oidc/rp/<?php echo $DOMAIN; ?>/authz/<?php echo base64_encode('http://trust-anchor.org:8000/'); ?>/<?php echo base64_encode('http://cie-provider.org:8002/oidc/op/'); ?>?state=state123">
                                                    <img class="img-fluid" src="<?php echo $BASEURL; ?>/assets/img/cie_button.png" alt="Entra con CIE">
                                                    <span class="sr-only">Accedi con identità digitale CIE</span>
                                                </a>
                                            </li>
                                        </div>
                                    </div>
                                    <img class="img-fluid mx-auto" src="<?php echo $BASEURL; ?>/assets/img/MI_logo.png" alt="Logo del Ministero dell’Interno">
                                </div>
                            </div>
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

        <script type="text/javascript" src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/js/jquery.min.js"></script><script type="text/javascript" src="<?php echo $BASEURL; ?>/assets/spid-sp-access-button/js/spid-sp-access-button.min.js"></script>
        <script>
            $(document).ready(function(){
                var rootList = $("#spid-idp-list-small-root-get");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-medium-root-get");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-large-root-get");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-xlarge-root-get");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-small-root-post");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-medium-root-post");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-large-root-post");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });

            $(document).ready(function(){
                var rootList = $("#spid-idp-list-xlarge-root-post");
                var idpList = rootList.children(".spid-idp-button-link");
                var lnkList = rootList.children(".spid-idp-support-link");
                while (idpList.length) {
                    rootList.append(idpList.splice(Math.floor(Math.random() * idpList.length), 1)[0]);
                }
                rootList.append(lnkList);
            });
        </script>            
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
    </body>
</html>

