<?php
/*
Plugin Name: Plantões MPCE - Sistema de Agenda
Description: Um sistema de agenda para gerenciar plantões diários e seus responsáveis.
Version: 1.0
Author: Emanoel de Oliveira
*/

// Enfileirar scripts e estilos necessários
function meus_scripts() {
    if (!wp_style_is('bootstrap')) {
        wp_enqueue_style('bootstrap', plugins_url('assets/bootstrap/css/bootstrap.min.css', __FILE__));
    }

    if (!wp_script_is('bootstrap')) {
        wp_enqueue_script('bootstrap', plugins_url('assets/bootstrap/js/bootstrap.min.js', __FILE__), array('jquery'), '5.3.2', true);
    }

    if (!wp_style_is('lightbox-css')) {
        wp_enqueue_style('lightbox-css', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css');
    }

    if (!wp_script_is('lightbox-js')) {
        wp_enqueue_script('lightbox-js', 'https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js', array('jquery'), '2.11.3', true);
    }
}
add_action('wp_enqueue_scripts', 'meus_scripts');

// Adicionar menus de administração
function agenda_menu() {
    add_menu_page(
        'Agenda',
        'Agenda',
        'manage_options',
        'agenda',
        'agenda_pagina_principal',
        'dashicons-calendar-alt',
        20
    );

    add_submenu_page(
        'agenda',
        'Plantões Por Data',
        'Plantões Por Data',
        'manage_options',
        'visualizar_plantoes',
        'visualizar_plantoes_pagina'
    );

    add_submenu_page(
        'agenda',
        'Todos os Plantões',
        'Todos os Plantões',
        'manage_options',
        'edit.php?post_type=plantao'
    );

    remove_menu_page('edit.php?post_type=plantao');
}
add_action('admin_menu', 'agenda_menu');

// Registrar o tipo de post "Plantões"
function registrar_post_plantao() {
    register_post_type('plantao', array(
        'labels' => array(
            'name' => 'Plantões',
            'singular_name' => 'Plantão',
        ),
        'public' => false,
        'show_ui' => true,
        'supports' => array('title', 'editor', 'excerpt'),
    ));
}
add_action('init', 'registrar_post_plantao');

// Página principal do plugin para adicionar plantões
function agenda_pagina_principal() {
    echo '<h2>Plantões Agendados</h2>';
    echo '<a class="button page-title-action" href="' . admin_url('post-new.php?post_type=plantao') . '">Adicionar Novo Plantão</a>';
    exibir_todos_plantoes();
}

// Exibir todos os plantões cadastrados em uma tabela
function exibir_todos_plantoes() {
    $args = array(
        'post_type' => 'plantao',
        'posts_per_page' => -1,
    );

    $plantoes = new WP_Query($args);

    if ($plantoes->have_posts()) {
        echo '<table class="table wp-list-table widefat fixed striped table-view-list pages">';
        echo '<thead><tr><th>Título e Data</th><th>Conteúdo</th><th>Editar</th><th>Excluir</th></tr></thead><tbody>';
        while ($plantoes->have_posts()) {
            $plantoes->the_post();
            $post_id = get_the_ID();
            echo '<tr class="iedit">';
            echo '<td class="title column-title has-row-actions column-primary page-title"><strong>' . get_the_title() . ' - ' . get_the_date('d/m/Y') . '</strong></td>';
            echo '<td>' . get_the_content() . '</td>';
            echo '<td><a class="button" href="' . get_edit_post_link($post_id) . '">Editar</a></td>';
            echo '<td>' . get_delete_post_link('Excluir', '', true) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        wp_reset_postdata();
    } else {
        echo '<p>Não há plantões agendados.</p>';
    }
}

// Função para excluir um plantão (mover para a lixeira)
function excluir_plantao($post_id) {
    if (current_user_can('delete_post', $post_id)) {
        $result = wp_trash_post($post_id);
        if ($result !== false) {
            echo '<p>Plantão movido para a lixeira com sucesso.</p>';
        } else {
            echo '<p>Ocorreu um erro ao mover o plantão para a lixeira.</p>';
        }
    } else {
        echo '<p>Você não tem permissão para excluir este plantão.</p>';
    }
}


function exibir_plantoes_data($data, $categoria) {
    // Argumentos da consulta para obter os plantões com base na data e categoria selecionadas
    $args = array(
        'post_type'      => 'plantao',
        'posts_per_page' => -1,
        'tax_query'      => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'categoria_plantao',
                'field'    => 'slug',
                'terms'    => $categoria, // Usando a categoria selecionada
            ),
        ),
        'post_status'    => array('publish', 'future'), // Inclui plantões publicados e agendados
        'date_query'     => array(
            array(
                'year'  => date('Y', strtotime($data)),
                'month' => date('m', strtotime($data)),
                'day'   => date('d', strtotime($data)),
            ),
        ),

        'hide_empty'     => false, // Mostrar categorias mesmo que estejam vazias
    );

    // Consulta WP_Query para obter os plantões com base nos argumentos definidos
    $plantoes = new WP_Query($args);

    // Verificar se a consulta retornou algum plantão
    if ($plantoes->have_posts()) {
        // Se houver plantões, exibir o cabeçalho com a data selecionada
        echo '<h3>Plantões para ' . date('d/m/Y', strtotime($data)) . '</h3>';
        // Iniciar uma lista para exibir os plantões
        echo '<ul class="list-group list-group-flush">';
        // Loop através dos plantões encontrados na consulta
        while ($plantoes->have_posts()) {
            $plantoes->the_post();
            // Exibir o título do plantão como item da lista
            echo '<li class="list-group-item">' . get_the_title() . '</li>';
            // Exibir o conteúdo do plantão (se necessário)
            echo '<strong>Conteúdo:</strong> ' . get_the_content() . '</li>';
        }
        // Fechar a lista
        echo '</ul>';
        // Restaurar os dados do post
        wp_reset_postdata();
    } else {
        // Se não houver plantões encontrados, exibir uma mensagem indicando isso
        echo '<p>Não há plantões agendados para a data selecionada.</p>';
    }
}



// Adicionar taxonomia de categoria para plantões
function registrar_taxonomia_categoria_plantao() {
    $args = array(
        'label' => 'Categorias de Plantão',
        'public' => true,
        'hierarchical' => true, // Se as categorias devem ter hierarquia como categorias padrão do WordPress ou não
    );
    register_taxonomy('categoria_plantao', 'plantao', $args);
}
add_action('init', 'registrar_taxonomia_categoria_plantao');

// Modificar a função de visualização de plantões para exibir abas de categorias
function visualizar_plantoes_pagina() {
    echo '<h2>Visualizar Plantões</h2>';

    // Adicionando a linha necessária para definir ajaxurl
    echo '<script>var ajaxurl = "' . admin_url('admin-ajax.php') . '";</script>';

    /* Formulário para selecionar a data e visualizar os plantões
    echo '<form id="form-visualizar-plantoes" method="get">';
    echo '<label for="data">Selecione a Data:</label>';
    echo '<input type="date" id="data" name="data" required>';
    echo '<input type="submit" value="Visualizar Plantões">';
    echo '</form>'; */
    ?>
    <form class="row g-3" id="form-visualizar-plantoes" method="get">
        <div class="col-auto">
            <label for="staticEmail2" class="visually-hidden">Selecione a Data:</label>            
        </div>
        <div class="col-auto">
            <label for="inputPassword2" class="visually-hidden">Data</label>
            <input type="date" class="form-control" id="data" placeholder="Sua data aqui">
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-danger mb-3">Visualizar Plantões</button>
        </div>
    </form>

    <?php

    // Exibir abas para cada categoria de plantão
    $categorias = get_terms('categoria_plantao');
    if (!empty($categorias) && !is_wp_error($categorias)) {
        echo '<ul class="nav nav-tabs" id="categorias-plantao-tabs" role="tablist">';
        foreach ($categorias as $categoria) {
            echo '<li class="nav-item">';
            echo '<a class="nav-link" id="' . $categoria->slug . '-tab" data-toggle="tab" href="#' . $categoria->slug . '" role="tab" aria-controls="' . $categoria->slug . '" aria-selected="false">' . $categoria->name . '</a>';
            echo '</li>';
        }
        echo '</ul>';

        // Conteúdo das abas
        echo '<div class="tab-content" id="categorias-plantao-content">';
        foreach ($categorias as $categoria) {
            echo '<div class="tab-pane fade" id="' . $categoria->slug . '" role="tabpanel" aria-labelledby="' . $categoria->slug . '-tab">';
            echo '<div id="' . $categoria->slug . '-plantoes" class="lista-plantoes"></div>';
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>Não há categorias de plantão.</p>';
    }

    // Div para exibir os plantões
    echo '<div id="lista-plantoes"></div>';

   
    // Script jQuery para lidar com a submissão do formulário e as chamadas AJAX
    echo '<script>
        jQuery(document).ready(function($) {
            $("#form-visualizar-plantoes").submit(function(event) {
                event.preventDefault();
                var data = $("#data").val();
                var categoria = $(".nav-tabs .nav-link.active").attr("id").replace("-tab", "");
                $.ajax({
                    url: ajaxurl,
                    type: "post",
                    data: {
                        action: "atualizar_lista_plantoes",
                        data: data,
                        categoria: categoria
                    },
                    success: function(response) {
                        $("#lista-plantoes").html(response);
                    }
                });
            });

            // Funções para editar e excluir plantões
            function editarPlantao(postId) {
                window.location.href = "' . admin_url('post.php?post='). '" + postId + "&action=edit";
            }

            function excluirPlantao(postId) {
                var confirmarExclusao = confirm("Tem certeza que deseja excluir este plantão?");
                if (confirmarExclusao) {
                    $.ajax({
                        url: ajaxurl,
                        type: "post",
                        data: {
                            action: "excluir_plantao",
                            post_id: postId
                        },
                        success: function(response) {
                            $("#lista-plantoes").html(response);
                        }
                    });
                }
            }
        });
    </script>';
}



function atualizar_lista_plantoes_callback() {
    if (isset($_POST['data']) && isset($_POST['categoria'])) {
        $data_selecionada = sanitize_text_field($_POST['data']);
        $categoria_selecionada = sanitize_text_field($_POST['categoria']);

   
           // Chamada para a função exibir_plantoes_data atualizada para exibir os plantões por categoria e data
        exibir_plantoes_data($data_selecionada, $categoria_selecionada);        
    } 
    wp_die();
}
add_action('wp_ajax_atualizar_lista_plantoes', 'atualizar_lista_plantoes_callback');


// Shortcode para exibir a página de visualização de plantões na página pública
function shortcode_visualizar_plantoes() {
    ob_start();
    visualizar_plantoes_pagina();
    return ob_get_clean();
}
add_shortcode('visualizar_plantoes', 'shortcode_visualizar_plantoes');

?>