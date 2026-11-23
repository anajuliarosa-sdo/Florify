<?php
$titulo    = 'Florify — Floricultura de alto-padrão';
$descricao = 'Arranjos florais exclusivos, feitos à mão, para momentos inesquecíveis. Conheça a Florify.';
require __DIR__ . '/partes/head.php';
require __DIR__ . '/partes/header.php';
?>
<main>
<?php
$apenasDestaque = true;
foreach (['headerfoto', 'essencia', 'nome', 'diferenciais', 'produtos', 'faq'] as $parte) {
    require __DIR__ . "/partes/{$parte}.php";
}
?>
</main>
<?php
require __DIR__ . '/partes/footer.php';
require __DIR__ . '/partes/scripts.php';
