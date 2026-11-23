<?php
$letras = [
    ['F', 'florF1', 'Floral',    'Curadoria botânica de espécies raras e exclusivas.'],
    ['L', 'florL',  'Luxo',      'Experiência memorável com embalagens de alto-padrão.'],
    ['O', 'florO',  'Original',  'Designs autorais feitos à mão por artistas florais.'],
    ['R', 'florR',  'Raridade',  'Seleção rigorosa de flores e arranjos raros, com frescor e durabilidade impecáveis.'],
    ['I', 'florI',  'Incrível',  'Arranjos que surpreendem e encantam à primeira vista.'],
    ['F', 'florF2', 'Frescor',   'Flores colhidas e entregues no auge da sua beleza natural.'],
    ['Y', 'florY',  'Você (you)', 'Arranjos pensados sob medida para o seu estilo único. Tratamos a personalização como o essencial para transmitir a sua essência pessoal através das flores.'],
];
?>
<section class="secao text-center" id="nome">
    <div class="container">
        <span class="eyebrow">Além do nome:</span>

        <div class="expand-carousel">
            <?php foreach ($letras as [$letra, $imagem, $titulo, $texto]): ?>
                <div class="slide" tabindex="0" role="group"
                     aria-label="<?= $letra ?> de <?= htmlspecialchars($titulo) ?>"
                     style="background-image:url(imagens/<?= $imagem ?>.jpg);">
                    <span class="slide-letra" aria-hidden="true"><?= $letra ?></span>
                    <div class="slide-caption">
                        <h3><?= htmlspecialchars($titulo) ?></h3>
                        <p><?= htmlspecialchars($texto) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
