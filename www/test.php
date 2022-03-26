<?php

if(isset($_POST) && $_SERVER['HTTP_ORIGIN']=='https://'.$_SERVER['HTTP_HOST']) {

    echo "<div>";
    foreach ($_POST as $k => $v) {
        echo "<p>" . $k . ": <b>" . $v . "</b></p>";
    }

    echo "<div><a href=\"oidc/rp/logout\">Esci</a></div>";
    echo "</div>";

} else {
    header("Location: oidc/rp/authz");
}

?>