<?php
require_once __DIR__.'/config/db.php';
$titulo='Produtos e Serviços — Florify'; $descricao='Explore todos os produtos e serviços da Florify.';
require __DIR__.'/partes/head.php'; require __DIR__.'/partes/header.php';
$termo=trim($_GET['busca']??'');
$sql='SELECT * FROM produtos'; $args=[]; if($termo!==''){ $sql.=' WHERE nome_produto LIKE ? OR descricao LIKE ? OR categoria LIKE ?'; $like='%'.$termo.'%'; $args=[$like,$like,$like]; } $sql.=' ORDER BY id_produto DESC';
$st=$pdo->prepare($sql); $st->execute($args); $lista=$st->fetchAll();
?>
<main>
<section class="secao pb-0 container text-center"><h1 class="titulo-script grande">Explore Todos os Nossos Produtos e Serviços</h1></section>
<section class="secao container" id="produtos">
<div class="text-center mb-5"><span class="eyebrow">Catálogo</span><h2 class="titulo-script">Escolha o seu arranjo</h2></div>
<form method="get" class="d-flex justify-content-center gap-2 mb-4"><input class="form-control" style="max-width:420px" type="search" name="busca" placeholder="Buscar flores e arranjos" value="<?= florify_e($termo) ?>"><button class="btn btnVerde rounded-pill px-4">Buscar</button></form>
<div class="row g-4">
<?php foreach($lista as $prod): $img=trim($prod['imagem']??''); if($img!=='' && !preg_match('~^(https?://|/)~i',$img)) $img='imagens/'.$img; if($img==='') $img='imagens/fotoCapa.jpg'; ?>
<div class="col-12 col-sm-6 col-lg-4"><article class="produtoCard" tabindex="0"><img src="<?= florify_e($img) ?>" alt="<?= florify_e($prod['nome_produto']) ?>" loading="lazy"><div class="info"><span class="eyebrow"><?= florify_e($prod['categoria']) ?></span><h3><?= florify_e($prod['nome_produto']) ?></h3><p><?= florify_e($prod['descricao']) ?></p><span class="preco">R$ <?= number_format((float)$prod['preco'],2,',','.') ?></span><?php if((int)$prod['estoque']>0): ?><?php if(($_SESSION['tipo_perfil']??'')==='ADMIN'): ?><span class="small">Estoque: <?= (int)$prod['estoque'] ?></span><?php elseif(($_SESSION['tipo_perfil']??'')==='CLIENTE'): ?><a href="comprar.php?id=<?= (int)$prod['id_produto'] ?>" class="btn btnVerde btn-sm rounded-pill"><i class="bi bi-bag-heart me-1"></i> Comprar</a><?php else: ?><a href="login.php" class="btn btnVerde btn-sm rounded-pill">Entrar para comprar</a><?php endif; ?><?php else: ?><span class="text-danger">Produto indisponível</span><?php endif; ?></div></article></div>
<?php endforeach; ?>
<?php if(!$lista): ?><div class="col-12 text-center"><p class="texto-corpo">Nenhum produto encontrado.</p></div><?php endif; ?>
</div></section></main>
<?php require __DIR__.'/partes/footer.php'; require __DIR__.'/partes/scripts.php'; ?>
