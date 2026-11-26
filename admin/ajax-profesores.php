<?php
if (!defined('ABSPATH')) exit;

// ==========================================
// CARGAR LISTA DE PROFESORES
// ==========================================
add_action('wp_ajax_edusiteco_load_profesores', 'edusiteco_load_profesores_ajax');
function edusiteco_load_profesores_ajax() {
    check_ajax_referer('edusiteco_admin_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos.');
    }

    $args = array(
        'post_type'      => 'profesor',
        'posts_per_page' => -1,
        'post_status'    => array('publish', 'draft'),
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    $query = new WP_Query($args);
    
    $profesores = array();
    
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            
            $id = get_the_ID();
            $thumbnail = get_the_post_thumbnail_url($id, 'thumbnail');
            if (!$thumbnail) {
                // Fallback
                $thumbnail = 'https://www.gravatar.com/avatar/0?d=mp&f=y';
            }
            
            $asignatura = get_post_meta($id, 'profesor_asignatura', true);
            $content = get_post_field('post_content', $id);
            $thumbnail_id = get_post_thumbnail_id($id);
            
            $profesores[] = array(
                'id' => $id,
                'title' => get_the_title(),
                'asignatura' => $asignatura,
                'description' => $content,
                'thumbnail_url' => $thumbnail,
                'thumbnail_id' => $thumbnail_id
            );
        }
        wp_reset_postdata();
    }
    
    wp_send_json_success(array('profesores' => $profesores));
}

// ==========================================
// GUARDAR / EDITAR PROFESOR
// ==========================================
add_action('wp_ajax_edusiteco_save_profesor', 'edusiteco_save_profesor_ajax');
function edusiteco_save_profesor_ajax() {
    check_ajax_referer('edusiteco_admin_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos.');
    }

    if (!isset($_POST['profesor_id']) || !is_scalar($_POST['profesor_id'])) {
        wp_send_json_error('El identificador del profesor no es válido.');
    }

    $raw_id = trim((string) wp_unslash($_POST['profesor_id']));
    if (!preg_match('/^\d+$/', $raw_id)) {
        wp_send_json_error('El identificador del profesor no es válido.');
    }
    $id = absint($raw_id);

    foreach (array('nombre', 'asignatura', 'descripcion', 'foto_id') as $field) {
        if (isset($_POST[$field]) && !is_scalar($_POST[$field])) {
            wp_send_json_error('Los datos del profesor no tienen un formato válido.');
        }
    }

    $nombre = isset($_POST['nombre']) ? sanitize_text_field(wp_unslash((string) $_POST['nombre'])) : '';
    $asignatura = isset($_POST['asignatura']) ? sanitize_text_field(wp_unslash((string) $_POST['asignatura'])) : '';
    $descripcion = isset($_POST['descripcion']) ? wp_kses_post(wp_unslash((string) $_POST['descripcion'])) : '';
    $foto_id_raw = isset($_POST['foto_id']) ? trim((string) wp_unslash($_POST['foto_id'])) : '';

    if ($foto_id_raw !== '' && !preg_match('/^\d+$/', $foto_id_raw)) {
        wp_send_json_error('La foto seleccionada no es válida.');
    }
    $foto_id = absint($foto_id_raw);

    if (empty($nombre)) {
        wp_send_json_error('El nombre es obligatorio.');
    }

    if ($id > 0) {
        $existing_profesor = get_post($id);
        if (!$existing_profesor || $existing_profesor->post_type !== 'profesor') {
            wp_send_json_error('El profesor solicitado no existe.');
        }
        if (!current_user_can('edit_post', $id)) {
            wp_send_json_error('No tienes permisos para editar este profesor.');
        }
    } elseif (!current_user_can('edit_profesores') || !current_user_can('publish_profesores')) {
        wp_send_json_error('No tienes permisos para crear y publicar profesores.');
    }

    if ($foto_id > 0) {
        $attachment = get_post($foto_id);
        if (
            !$attachment
            || $attachment->post_type !== 'attachment'
            || !wp_attachment_is_image($foto_id)
            || !current_user_can('edit_post', $foto_id)
        ) {
            wp_send_json_error('La imagen seleccionada no es válida o no tienes permisos para usarla.');
        }
    }

    $post_data = array(
        'post_title'   => $nombre,
        'post_content' => $descripcion,
        'post_type'    => 'profesor',
        'post_status'  => 'publish'
    );

    if ($id > 0) {
        $post_data['ID'] = $id;
        $post_id = wp_update_post($post_data, true);
    } else {
        $post_id = wp_insert_post($post_data, true);
    }

    if (is_wp_error($post_id)) {
        wp_send_json_error($post_id->get_error_message());
    }

    update_post_meta($post_id, 'profesor_asignatura', $asignatura);

    if ($foto_id > 0) {
        set_post_thumbnail($post_id, $foto_id);
    } else {
        delete_post_thumbnail($post_id);
    }

    wp_send_json_success(array(
        'message' => 'Profesor guardado exitosamente.',
        'post_id' => $post_id
    ));
}

// ==========================================
// ELIMINAR PROFESOR
// ==========================================
add_action('wp_ajax_edusiteco_delete_profesor', 'edusiteco_delete_profesor_ajax');
function edusiteco_delete_profesor_ajax() {
    check_ajax_referer('edusiteco_admin_nonce', 'security');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('No tienes permisos.');
    }

    if (!isset($_POST['profesor_id']) || !is_scalar($_POST['profesor_id'])) {
        wp_send_json_error('El identificador del profesor no es válido.');
    }

    $raw_id = trim((string) wp_unslash($_POST['profesor_id']));
    if (!preg_match('/^[1-9]\d*$/', $raw_id)) {
        wp_send_json_error('El identificador del profesor no es válido.');
    }

    $id = absint($raw_id);
    $profesor = get_post($id);
    if (!$profesor || $profesor->post_type !== 'profesor') {
        wp_send_json_error('El profesor solicitado no existe.');
    }
    if (!current_user_can('delete_post', $id)) {
        wp_send_json_error('No tienes permisos para eliminar este profesor.');
    }

    $result = wp_delete_post($id, true);
    if (!$result) {
        wp_send_json_error('Error al eliminar el profesor.');
    }

    wp_send_json_success('Profesor eliminado.');
}
