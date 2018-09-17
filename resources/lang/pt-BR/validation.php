<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted'             => ':attribute deve ser aceito.',
    'active_url'           => ':attribute não é uma URL válida.',
    'after'                => ':attribute deve ser uma data depois de :date.',
    'alpha'                => ':attribute deve conter somente letras.',
    'alpha_dash'           => ':attribute deve conter letras, números e traços.',
    'alpha_num'            => ':attribute deve conter somente letras e números.',
    'array'                => ':attribute deve ser um array.',
    'before'               => ':attribute deve ser uma data antes de :date.',
    'between'              => [
        'numeric' => ':attribute deve estar entre :min e :max.',
        'file'    => ':attribute deve estar entre :min e :max kilobytes.',
        'string'  => ':attribute deve estar entre :min e :max caracteres.',
        'array'   => ':attribute deve ter entre :min e :max itens.',
    ],
    'boolean'              => ':attribute deve ser verdadeiro ou falso.',
    'confirmed'            => 'A confirmação de :attribute não confere.',
    'date'                 => ':attribute não é uma data válida.',
    'date_format'          => ':attribute não confere com o formato :format.',
    'different'            => ':attribute e :other devem ser diferentes.',
    'digits'               => ':attribute deve ter :digits dígitos.',
    'digits_between'       => ':attribute deve ter entre :min e :max dígitos.',
    'email'                => ':attribute deve ser um endereço de e-mail válido.',
    'filled'               => ':attribute é um campo obrigatório.',
    'exists'               => 'O :attribute selecionado é inválido.',
    'image'                => ':attribute deve ser uma imagem.',
    'in'                   => ':attribute é inválido.',
    'integer'              => ':attribute deve ser um inteiro.',
    'ip'                   => ':attribute deve ser um endereço IP válido.',
    'max'                  => [
        'numeric' => ':attribute não deve ser maior que :max.',
        'file'    => ':attribute não deve ter mais que :max kilobytes.',
        'string'  => ':attribute não deve ter mais que :max caracteres.',
        'array'   => ':attribute não pode ter mais que :max itens.',
    ],
    'mimes'                => ':attribute deve ser um arquivo do tipo: :values.',
    'min'                  => [
        'numeric' => ':attribute deve ser no mínimo :min.',
        'file'    => ':attribute deve ter no mínimo :min kilobytes.',
        'string'  => ':attribute deve ter no mínimo :min caracteres.',
        'array'   => ':attribute deve ter no mínimo :min itens.',
    ],
    'not_in'               => 'O :attribute selecionado é inválido.',
    'numeric'              => ':attribute deve ser um número.',
    'regex'                => 'O formato de :attribute é inválido.',
    'required'             => 'O campo :attribute é obrigatório.',
    'required_if'          => 'O campo :attribute é obrigatório quando :other é :value.',
    'required_with'        => 'O campo :attribute é obrigatório quando :values está presente.',
    'required_with_all'    => 'O campo :attribute é obrigatório quando :values estão presentes.',
    'required_without'     => 'O campo :attribute é obrigatório quando :values não está presente.',
    'required_without_all' => 'O campo :attribute é obrigatório quando nenhum destes estão presentes: :values.',
    'same'                 => ':attribute e :other devem ser iguais.',
    'size'                 => [
        'numeric' => ':attribute deve ser :size.',
        'file'    => ':attribute deve ter :size kilobytes.',
        'string'  => ':attribute deve ter :size caracteres.',
        'array'   => ':attribute deve conter :size itens.',
    ],
    'timezone'             => ':attribute deve ser uma timezone válida.',
    'unique'               => ':attribute já está em uso.',
    'url'                  => 'O formato de :attribute é inválido.',
    'same_parent'          => ':resources em todas as versões de cultura devem ter o mesmo ascendente',
    'all_required_in_all_locales'       => 'Todos os campos das :resources ,  em todas as versões de cultura são obrigatórios',
    'field_required_in_all_locales'       => 'O campo :field é obrigatório em todas as versões de cultura',
    'field_min_in_all_locales'       => 'O campo :field deve ter no mínimo :min caracteres em todas as culturas',
    'there_are_required_fields'       => 'Há campo(s) obrigatórios não preenchido(s) em uma ou mais versão de cultura',
    'unique_name_and_slug_in_all_locales'          => 'A(o) :resources em todas as versões de cultura deve ter um nome e slug únicos',
    'unique_title_and_slug_in_all_locales'          => 'A(o) :resources em todas as versões de cultura deve ter título e slug únicos',
    'at_least_one_translation_required' => 'Devem ser informado o(s) campo(s) obrigatórios em pelo menos uma das culturas disponíveis',
    'field_in'=> 'O campo :field deve ser igual a uma das opções :in',
    'password_required'=> 'O campo senha é obrigatório quando o status selecionado é protegido por senha',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'id' => 'id',
        'password' => 'Senha',
        'email' => 'Email',
        'name' => 'Nome',
        'title' => 'título',
        'description' => 'descrição',
        'priority' => 'prioridade',
        'scheduled_to' => 'Agendado para',
        'cost' => 'Custo',
        'done' => 'feito',
        'project_id' => 'projeto',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
        'roles' => 'Perfis',
        'users' => 'Para',
        'subject' => 'Assunto',
        'message' => 'Mensagem',
        'date' => 'Data',
        'image' => 'Imagem',
        'categories'=> 'categorias',
        'category'=> 'categoria',
        'pages'=> 'páginas',
        'page'=> 'página',
        'section'=> 'seção',
        'sections'=> 'seções',
        'locale'=> 'cultura',
        'content' => 'conteúdo',
        'abstract' => 'resumo',
        'short_desc' => 'descrição curta',
        'featured_image_id' => 'imagem destaque',
        'featured_video_id' => 'video destaque',
        'contents'=> 'contents',

    ],

    'types' => [
        'html'=> 'HTML',
        'external_video'=> 'vídeo externo'
    ]

];
