<?php
/* Dados compartilhados da loja. Edite aqui: a vitrine (produtos.php) e a
   página de compra (comprar.php) leem desta lista. */

/* Valor fixo de entrega — AJUSTE conforme a sua operação. */
if (!defined('FRETE_PADRAO')) define('FRETE_PADRAO', 15.00);

/* WhatsApp que recebe os pedidos (código do país + DDD + número). */
if (!defined('WHATSAPP_LOJA')) define('WHATSAPP_LOJA', '');

$produtos = [
    'buque-classico-rosas' => [
        'imagem' => 'rosasV',         'alt' => 'Buquê de rosas vermelhas',
        'nome'   => 'Buquê Clássico de Rosas',
        'descricao' => 'Perfeito para aniversários e datas românticas.',
        'preco'  => 149.90,
        'destaque' => true,
    ],
    'arranjo-flores-campo' => [
        'imagem' => 'floresdocampo',  'alt' => 'Arranjo de flores do campo',
        'nome'   => 'Arranjo de Flores de Campo',
        'descricao' => 'Cores vibrantes para alegrar qualquer ambiente doméstico.',
        'preco'  => 119.00,
        'destaque' => true,
    ],
    'orquidea-branca' => [
        'imagem' => 'orquideabranca', 'alt' => 'Vaso de orquídea branca',
        'nome'   => 'Orquídea Branca',
        'descricao' => 'Ideal para presentes corporativos ou decoração luxuosa.',
        'preco'  => 185.00,
        'destaque' => true,
    ],
    'buque-girassois' => [
        'imagem' => 'girassois',      'alt' => 'Buquê de girassóis',
        'nome'   => 'Buquê de Girassóis',
        'descricao' => 'Ideal para transmitir alegria e iluminar qualquer ocasião.',
        'preco'  => 139.90,
        'destaque' => true,
    ],
    'arranjo-tulipas' => [
        'imagem' => 'tulipas',        'alt' => 'Arranjo de tulipas',
        'nome'   => 'Arranjo de Tulipas',
        'descricao' => 'Uma opção delicada para presentear em momentos especiais.',
        'preco'  => 179.90,
        'destaque' => true,
    ],
    'cesto-flores-mistas' => [
        'imagem' => 'cesta',          'alt' => 'Cesto de flores mistas',
        'nome'   => 'Cesto de Flores Mistas',
        'descricao' => 'Combinação de flores selecionadas para surpreender com elegância.',
        'preco'  => 159.90,
        'destaque' => true,
    ],
    'arranjo-rosas-brancas' => [
        'imagem' => 'rosasBrancas',             'alt' => 'Buquê de rosas brancas',
        'nome' => 'Buquê de Rosas Brancas',
        'descricao' => 'Rosas brancas selecionadas para encantar.',
        'preco' => 149.90,
        'destaque' => false,
    ],
    'arranjo-margaridas' => [
        'imagem' => 'margaridas',             'alt' => 'Arranjo de margaridas',
        'nome' => 'Arranjo de Margaridas',
        'descricao' => 'Arranjo alegre que transmite leveza.',
        'preco' => 119.90,
        'destaque' => false,
    ],
    'arranjo-peonias' => [
        'imagem' => 'peonia',             'alt' => 'Arranjo de peonias',
        'nome' => 'Arranjo de Peônias',
        'descricao' => 'O glamour e o clássico em um único arranjo.',
        'preco' => 159.90,
        'destaque' => false,
    ],
];

function preco_br(float $valor): string {
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function e($texto): string {
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}
