<?php
defined('ABSPATH') || exit;
// create custom plugin settings menu
add_action('admin_menu', 'ab_test_report_submenu');

function ab_test_report_submenu() {

    //call register settings function
    // add_action('admin_init', 'register_my_cool_plugin_settings');

    //create new sub-level menu

    add_submenu_page(
        'edit.php?post_type=campanhas_ab',
        __('Relatórios', 'abtestx'),
        __('Relatórios', 'abtestx'),
        'manage_options',
        'relatorios_ab',
        'pagina_relatorios_cb'
    );

}

// function register_my_cool_plugin_settings() {
//     //register our settings
//     register_setting('my-cool-plugin-settings-group', 'new_option_name');
//     register_setting('my-cool-plugin-settings-group', 'some_other_option');
//     register_setting('my-cool-plugin-settings-group', 'option_etc');
// }

function pagina_relatorios_cb() {

    //enqueue your scripts
    wp_enqueue_script('ab_test_moment', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js', '2.29.4', true);
    wp_enqueue_script('ab_test_datatables', 'https://cdn.datatables.net/1.13.1/js/jquery.dataTables.js', array('jquery'), '1.13.1', true);
    wp_enqueue_script('ab_test_date_formater', 'https://cdn.datatables.net/plug-ins/1.13.1/dataRender/datetime.js', array('jquery'), '1.13.1', true);

    // wp_enqueue_script('ab_test_datatables_bs', 'https://cdn.datatables.net/1.12.1/js/dataTables.bootstrap4.min.js', array('jquery'), '1.12.1', true);
    wp_enqueue_style('ab_test_datatables_css', 'https://cdn.datatables.net/1.13.1/css/jquery.dataTables.css', '1.13.1', true);
    // wp_enqueue_style('ab_test_datatables_css_bs', 'https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css', '1.12.1', true);
    //
    wp_enqueue_style('ab_test_bootstrap_css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css', '5.3.0', true);
    wp_enqueue_script('ab_test_bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js', array('jquery'), true);

    ?>
<style>
.dataTables_length select {
    width: 50px;
}

th,
td {
    font-size: 0.7em;
}
#wpcontent{
    background: #f0f0f1;
}
.container{
    background: #fff;
    border-radius: 5px;
    -webkit-border-radius: 5px;
    -moz-border-radius: 5px;
    padding: 3px;
}
</style>
<div class="container" id="reports">
    <h1>Relatórios dos testes A/B</h1>
    <!-- divider -->
    <div class="row">
        <div class="col mb-3">
            <label for="ab_filter_data" class="form-label">Selecione uma campanha para filtrar os dados</label>
            <select class="form-select" aria-label="Default select example" id="ab_filter_data">
                <option selected value="">Todas</option>
                <?php

                    $pages = get_posts(array(
                        'post_type' => 'campanhas_ab',
                        'sort_order' => 'ASC',
                        'sort_column' => 'post_title',
                    ));

                    //$post_relatorio = $pages;

                    if ($pages) {
                        foreach ($pages as $page) {
                            //$selected = (in_array($page->ID, $post_relatorio))
                            // ? 'selected'
                            //    : '';

                            printf(
                                '<option value="%1$s">%2$s</option>',
                                //$selected,
                                $page->ID,
                                $page->post_title
                            );
                        }
                    }

                ?>
            </select>
        </div>
        <div class="col mb-3">
            <label for="startDate" class="form-label">Data inicial dos acessos</label>
            <input id="startDate" class="form-control" type="date" />           
        </div>
        <div class="col mb-3">
            <label for="endDate" class="form-label">Data final dos acessos</label>
            <input id="endDate" class="form-control" type="date" />           
        </div>
    </div>
    <div class="container text-center">
        <div class="row">
            <div class="col">
                <div class="card acessos">
                    <div class="card-body">
                        <h5 class="card-title">Acessos</h5>
                        <!-- <h6 class="card-subtitle mb-2 text-muted">Visitantes únicos</h6> -->
                        <h2 class="card-title result">--</h2>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card conversoes_full">
                    <div class="card-body">
                        <h5 class="card-title">Conversões</h5>
                        <!-- <h6 class="card-subtitle mb-2 text-muted">Vendas geradas</h6> -->
                        <h2 class="card-title result">--</h2>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card">
                    <div class="card-body conversoes_partial">
                        <h5 class="card-title">Taxa de conversão</h5>
                        <!-- <h6 class="card-subtitle mb-2 text-muted">Sem cookies</h6> -->
                        <h2 class="card-title result">--</h2>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="container">
        <h2>Resultados</h2>
        <table class="table table-striped resultados_paginas">
            <thead>
                <tr>
                <th scope="col">Página</th>
                <th scope="col">Campanha</th>
                <th scope="col">Acessos</th>
                <th scope="col">Conversões</th>
                <th scope="col">Taxa de conversão</th>
                </tr>
            </thead>
            <tbody class="result">
                
            </tbody>
        </table>
        <div class="row w-100">
            <div class="col">
                <div class="card w-100 p-0 " >
                    <ul class="list-group list-group-flush ">

                    </ul>
                </div>
            </div>
        </div>
    </div>
    <hr>
    <div class="container">
        <h2>Histórico de acessos</h2>
        <table id="report_list" class="table display compact " style="width:100%">
            <thead>
                <tr>
                    <th>Campanha</th>
                    <th>Hash</th>
                    <th>IP Origem</th>
                    <th>Data Acesso</th>
                    <th>Página de entrada</th>
                    <th>Parametros</th>
                    <th>Página destino</th>
                    <th>IP Conversão</th>
                    <th>Data Conversão</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th>Campanha</th>
                    <th>Hash</th>
                    <th>IP Origem</th>
                    <th>Data Acesso</th>
                    <th>Página de entrada</th>
                    <th>Parametros</th>
                    <th>Página destino</th>
                    <th>IP Conversão</th>
                    <th>Data Conversão</th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php }?>