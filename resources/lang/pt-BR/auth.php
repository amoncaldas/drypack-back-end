<?php
/*
 * This file is part of the DryPack Dynamic Authorization
 *
 * @author Amon Santana <amoncaldas@gmail.com>
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Roles translations
    |--------------------------------------------------------------------------
    */
    'roles'=> [
        'admin'=>'Admin',
        'basic'=>'Básico',
        'anonymous'=>'Anônimo',
        'news_subscriber'=>'Assinante de Newsletter'
    ],
    /*
    |--------------------------------------------------------------------------
    | Authorization Language strings/labels
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the dynamic authorization component t
    | o the output list of resources and actions available.
    | You can customize labels your views to better match your application.
    |
    */
    'resources'=> [
        'all'=>'Todos',
        'users'=>'Usuários',
        'audit'=>'Auditoria',
        'dataInspection'=>'Consultas dinâmicas',
        'emails'=>'Emails',
        'authorization'=>'Autorizações',
        'password'=>'Senha',
        'role'=>'Perfis',
        'authentication'=>'Autenticação',
        'project'=>'Projeto',
        'task'=>'Tarefa',
        'section'=> 'Seção',
        'page'=> 'Página',
        'post'=> 'Post',
        'domain-data' => 'Dados de domínio',
        'category' => 'Categoria',
        'media' => 'Mídia'
    ],
    'actions'=> [
        'all'=>'Todas',
        'index'=>'Listar',
        'store'=>'Criar',
        'update'=>'Atualizar',
        'destroy'=>'Excluir',
        'show'=>'Exibir',
        'authenticate'=>'Autenticar',
        'getAuthenticatedUser'=>'Recuperar usuário autenticado',
        'listModels'=>'Listar modelos',
        'resources'=>'Listar recursos',
        'actions'=>'Listar ações',
        'mapAndGet'=>'Listar dados',
        'models'=>'Listar recursos',
        'updateProfile'=>'Atualizar o próprio perfil',
        'postEmail'=>'Solicitar redefinição de senha',
        'postReset'=>'Salvar redefinição de senha',
        'toggleDone'=> 'Alternar estado',
        'registerNewsLetterSubscriberUser'=> 'Registrar assinante de newsletter',

        'draft' => 'Rascunho',
        'destroy_others' => "Excluir item de outros",
        'index_others' => "Listar items de outros",
        'publish' => "Publicar",
        'password_protect' => "Salvar protegido com senha",
        'update_others' => "Atualizar item de outros",
        'send_to_review' => 'Enviar para revisão',
        'update_owner' => 'Alterar proprietário',
        'revisions' => 'Listar versões',
        'revision' => 'Recuperar versão',
        'upload' => 'Enviar arquivo',
        'showContent' => 'Exibir o conteúdo de uma mídia',
        'upload_video' => 'Upload de vídeo',
        'upload_audio' => 'Upload de audio',
        'upload_image' => 'Upload de imagem',
        'upload_document' => 'Upload de documento',
        'save_html_media' => 'Salvar mídia html',
        'save_external_video' => 'Salvar vídeo externo'
    ],
    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */
    'messages'=> [
        'no_authorization_for_this_resource'=>'Sem permissão para acessar este recurso/tela',
        'no_authorization_for_this_type_of_action_in_resource'    => 'Sem permissão para este tipo de ação em :resourceName',
        'no_authorization_for_action_in_resource'    => 'Sem permissão :actionName em :resourceName',
        'mandatory_admin_profile_auto_added'=>"Este usuário deve ter o papel de Admin e este foi adicionado automaticamente",
        'mandatory_permissions_added'=>"Permissões mínimas necessárias para o papel Admin foram automaticamente adicionadas"
    ],
];
