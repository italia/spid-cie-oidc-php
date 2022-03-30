<?php

if (isset($_POST) && $_SERVER['HTTP_ORIGIN'] == 'https://' . $_SERVER['HTTP_HOST']) {
    echo "<div>";
    foreach ($_POST as $k => $v) {
        echo "<p>" . $k . ": <b>" . $v . "</b></p>";
    }

    $ta_id = $_POST['trust_anchor_id'];
    $op_id = $_POST['provider_id'];

    //echo "<div><a href=\"oidc/rp/introspection?ta_id=".urlencode($ta_id)."&op_id=".urlencode($op_id)."\".>Introspection</a></div>";
    echo "<div><a href=\"oidc/rp/logout\">Esci</a></div>";
    echo "</div>";
} else {
    header("Location: oidc/rp/authz");
}
