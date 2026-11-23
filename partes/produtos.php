<?php
require_once __DIR__ . '/dados_produtos.php';

/* A página pode sobrescrever estes textos antes de incluir este arquivo. */
$produtosEyebrow = $produtosEyebrow ?? 'Nossa coleção:';
$produtosTitulo  = $produtosTitulo  ?? 'Os arranjos mais vendidos';
$apenasDestaque = $apenasDestaque ?? false;
?>
<section class="secao container" id="produtos">
    <div class="text-center mb-5">
        <span class="eyebrow"><?= e($produtosEyebrow) ?></span>
        <h2 class="titulo-script"><?= e($produtosTitulo) ?></h2>
    </div>

    <div class="row g-4">
        <?php foreach ($produtos as $slug => $p): ?>
            <?php if ($apenasDestaque && empty($p['destaque'])) continue; ?>
            <div class="col-12 col-sm-6 col-lg-4">
                <article class="produtoCard" tabindex="0">
                    <img src="imagens/<?= e($p['imagem']) ?>.jpg" alt="<?= e($p['alt']) ?>" loading="lazy">
                    <div class="info">
                        <h3><?= e($p['nome']) ?></h3>
                        <p><?= e($p['descricao']) ?></p>
                        <span class="preco"><?= preco_br($p['preco']) ?></span>
                        <a href="comprar.php?produto=<?= e($slug) ?>" class="btn btnVerde btn-sm rounded-pill"
                           aria-label="Comprar <?= e($p['nome']) ?>">
                            <i class="bi bi-bag-heart me-1" aria-hidden="true"></i> Comprar
                        </a>
                    </div>
                </article>
            </div>
        <?php endforeach; ?>
    </div>
</section>
