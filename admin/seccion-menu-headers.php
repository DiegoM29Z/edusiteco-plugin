<?php
$o = get_option('info_colegio_settings', array());
$menu_headers = is_array($o) && isset($o['menu_headers']) && is_array($o['menu_headers'])
    ? $o['menu_headers']
    : array();

$menu_header_defaults = array(
    'acerca_de' => 'Acerca de',
    'documental' => 'G. Documental',
    'academico' => 'G. Académico',
    'contacto' => 'Contáctanos',
    'administrativa_financiera' => 'G. Ad. y Financiera',
    'convocatorias' => 'Convocatorias',
);
?>
<h2>Encabezados del menú principal</h2>
<p>Personaliza los títulos que aparecen en la barra superior del sitio.</p>
<div class="notice notice-info inline">
    <p>Estos cambios se aplican al encabezado del tema Edusiteco V2 y no modifican el tema V1.</p>
</div>

<table class="form-table">
    <?php foreach ($menu_header_defaults as $key => $default) : ?>
        <?php
        $stored_title = isset($menu_headers[$key]) && is_scalar($menu_headers[$key])
            ? trim((string) $menu_headers[$key])
            : '';
        $field_value = '' !== $stored_title ? $stored_title : $default;
        ?>
        <tr>
            <th scope="row">
                <label for="menu-header-<?php echo esc_attr($key); ?>">
                    <?php echo esc_html($default); ?>
                </label>
            </th>
            <td>
                <input
                    type="text"
                    id="menu-header-<?php echo esc_attr($key); ?>"
                    name="info_colegio_settings[menu_headers][<?php echo esc_attr($key); ?>]"
                    value="<?php echo esc_attr($field_value); ?>"
                    class="regular-text"
                >
            </td>
        </tr>
    <?php endforeach; ?>
</table>
