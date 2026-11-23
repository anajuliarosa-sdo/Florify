<?php
/*
    Configuração da API da Florify.
    Defina OPENAI_API_KEY no ambiente do servidor.
    Não coloque chaves secretas neste arquivo nem publique este arquivo em repositórios.
*/

return [
    'api_key'       => getenv('OPENAI_API_KEY') ?: '',
    'modelo_texto'  => 'gpt-4o-mini',
    'modelo_imagem' => 'gpt-image-2',
    'qualidade'     => 'medium', // low | medium | high
    'max_upload_mb' => 8,
];
