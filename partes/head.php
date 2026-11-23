<?php
/* Cabeçalho HTML compartilhado. Antes de incluir, a página pode definir:
   $titulo e $descricao. */
$titulo    = $titulo    ?? 'Florify — Floricultura de alto-padrão';
$descricao = $descricao ?? 'Florify: arranjos florais exclusivos, feitos à mão, para momentos inesquecíveis.';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
    <meta name="description" content="<?= htmlspecialchars($descricao) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/estilo.css">
    <link rel="icon" type="image/png" href="imagens/logo3.png">
</head>
<body>
