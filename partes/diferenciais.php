<?php
$diferenciais = [
    ['Curadoria Especializada', 'Cada espécie é escolhida a dedo por floristas com anos de experiência em design botânico.'],
    ['Embalagem Exclusiva',    'Cada arranjo chega em embalagem autoral, pensada para impressionar antes mesmo de abrir.'],
    ['Garantia de Frescor',    'Se sua flor murchar antes do prazo prometido, trocamos sem custo nenhum.'],
    ['Edições Limitadas',      'Coleções sazonais com espécies raras, disponíveis por tempo limitado.'],
    ['Sob Medida',             'Escolha cores, espécies e o estilo do arranjo de acordo com a ocasião e o seu gosto.'],
    ['Atendimento Consultivo', 'Nossa equipe ajuda a escolher o arranjo ideal antes mesmo da compra, sem custo.'],
];
?>
<section class="secao secao-verde" id="diferenciais">
    <div class="container">
        <div class="text-center mb-5">
            <span class="eyebrow">O Que Oferecemos:</span>
            <h2 class="titulo-script">Sempre buscando o melhor para você</h2>
        </div>

        <div class="row g-4 justify-content-center">
            <?php foreach ($diferenciais as [$titulo, $texto]): ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="diferencialCard text-center h-100">
                        <h3><?= htmlspecialchars($titulo) ?></h3>
                        <p><?= htmlspecialchars($texto) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
