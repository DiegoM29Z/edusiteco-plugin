<?php
if (!defined('ABSPATH')) {
    exit;
}

function edusiteco_psico_default_resources()
{
    return array(
        array(
            'slug' => 'estrategias-emocionales-para-aulas-multigrado',
            'title' => 'Estrategias emocionales para aulas multigrado',
            'description' => 'En grupos con edades distintas, trabaja un tema común —emociones, enojo, empatía, conflictos o autocuidado— y adapta la profundidad según el desarrollo. Los más pequeños pueden identificar y expresar emociones; los mayores, analizar causas y proponer soluciones. Se sugieren tutoría entre pares, juegos cooperativos, cuentos, dramatizaciones, música, rueda emocional, semáforo y rincón de calma. Conecta las actividades con experiencias de la comunidad y valora la participación sin comparar a estudiantes de distintas edades.',
            'file' => 'Estrategias_Aulas_Multigrado.pdf',
        ),
        array(
            'slug' => 'identidad-y-proyecto-de-vida-en-adolescencia',
            'title' => 'Identidad y proyecto de vida en adolescencia',
            'description' => 'Las mallas disponibles de 7.º, 8.º, 9.º y 11.º abordan identidad, cambios, autoestima, pertenencia, relaciones, autocuidado y toma de decisiones. En secundaria se reflexiona sobre la presión de pares y redes, el manejo de emociones, los riesgos y los hábitos saludables; en 11.º se incorpora el proyecto de vida y decisiones con impacto personal y colectivo. La propuesta recomienda actividades de reflexión, acuerdos grupales y registro personal, acompañados por adultos de confianza.',
            'file' => 'Mallas_Adolescencia_7_8_9_11.pdf',
        ),
        array(
            'slug' => 'habilidades-socioemocionales-en-primaria',
            'title' => 'Habilidades socioemocionales en primaria',
            'description' => 'Las mallas de 1.º a 5.º proponen fortalecer el autoconocimiento, la autoestima, el reconocimiento y la regulación de emociones, la autonomía y las decisiones responsables, la empatía, la comunicación, la cooperación y la convivencia. Las actividades combinan experiencias lúdicas, acuerdos de aula y reflexión en la agenda emocional: reconocer cualidades y gustos, valorar logros, expresar lo que se siente y pensar en las consecuencias de las acciones. Ajusta cada actividad al grado y al contexto del grupo.',
            'file' => 'Mallas_Primaria_1_a_5.pdf',
        ),
        array(
            'slug' => 'educacion-inicial',
            'title' => 'Educacion Inicial',
            'description' => 'Tiene como fin crear e implementar la Cátedra de Educación Emocional en los niveles preescolar, básica y media, en concordancia con el Proyecto Educativo Institucional (PEI). En preescolar (3 a 5 años), la malla propone fortalecer la autoestima, la autorregulación con apoyo adulto, la empatía, las habilidades sociales y la toma de decisiones sencillas. Sugiere trabajar alegría, tristeza, enojo, miedo y sorpresa mediante juego simbólico, cuentos, canciones, experiencias sensoriales, respiración y rutinas de calma. Propone una hora semanal, integrable en las rutinas, y evaluar mediante observación directa en juegos y actividades cotidianas.',
            'file' => 'preschool.pdf',
        ),
    );
}

function edusiteco_psico_default_attachment($resource)
{
    $plugin_root = dirname(__DIR__);
    $source = $plugin_root . '/assets/psicoorientacion/' . $resource['file'];
    if (!is_readable($source)) {
        error_log('Edusiteco: No se encuentra el PDF empaquetado ' . $source);
        return 0;
    }

    $uploads = wp_upload_dir();
    if (!empty($uploads['error'])) {
        error_log('Edusiteco: No se pudo preparar uploads para Psicoorientación: ' . $uploads['error']);
        return 0;
    }

    $relative_path = 'edusiteco-psicoorientacion/' . $resource['file'];
    $destination = trailingslashit($uploads['basedir']) . $relative_path;
    if (!file_exists($destination) || filesize($destination) !== filesize($source)) {
        if (!wp_mkdir_p(dirname($destination)) || !copy($source, $destination)) {
            error_log('Edusiteco: No se pudo copiar el PDF del Botiquín a uploads: ' . $destination);
            return 0;
        }
    }

    $existing = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'numberposts' => 1,
        'meta_key' => '_edusiteco_psico_bundle_file',
        'meta_value' => $resource['file'],
    ));

    if (!empty($existing)) {
        $attachment_id = (int) $existing[0]->ID;
    } else {
        $attachment_id = wp_insert_attachment(array(
            'post_title' => $resource['title'],
            'post_mime_type' => 'application/pdf',
            'post_status' => 'inherit',
        ), $destination, 0, true);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            $error = is_wp_error($attachment_id) ? $attachment_id->get_error_message() : 'No se pudo crear el adjunto.';
            error_log('Edusiteco: Error al registrar el PDF ' . $resource['file'] . ': ' . $error);
            return 0;
        }

        $attachment_id = (int) $attachment_id;
        update_post_meta($attachment_id, '_wp_attached_file', $relative_path);
        update_post_meta($attachment_id, '_edusiteco_psico_bundle_file', $resource['file']);
    }

    return $attachment_id;
}

function edusiteco_seed_psico_default_resources()
{
    $seed_version = '1';
    if (get_option('edusiteco_psico_resources_seed_version') === $seed_version) {
        return;
    }

    if (!post_type_exists('psico_recurso')) {
        return;
    }

    foreach (edusiteco_psico_default_resources() as $resource) {
        $attachment_id = edusiteco_psico_default_attachment($resource);
        if (!$attachment_id) {
            return;
        }

        $existing = get_posts(array(
            'post_type' => 'psico_recurso',
            'post_status' => 'any',
            'name' => $resource['slug'],
            'numberposts' => 1,
        ));

        if (!empty($existing)) {
            $resource_id = (int) $existing[0]->ID;
        } else {
            $resource_id = wp_insert_post(array(
                'post_type' => 'psico_recurso',
                'post_status' => 'publish',
                'post_name' => $resource['slug'],
                'post_title' => $resource['title'],
                'post_content' => $resource['description'],
            ), true);

            if (is_wp_error($resource_id) || !$resource_id) {
                $error = is_wp_error($resource_id) ? $resource_id->get_error_message() : 'No se pudo crear la ficha.';
                error_log('Edusiteco: Error al crear el recurso ' . $resource['slug'] . ': ' . $error);
                return;
            }

            $resource_id = (int) $resource_id;
        }

        update_post_meta($resource_id, '_edusiteco_default_resource', $resource['slug']);
        update_post_meta($resource_id, 'recurso_attachment_id', $attachment_id);
        update_post_meta($resource_id, 'recurso_url', wp_get_attachment_url($attachment_id));
    }

    update_option('edusiteco_psico_resources_seed_version', $seed_version);
}
add_action('init', 'edusiteco_seed_psico_default_resources', 20);
