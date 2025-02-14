<?php
if (!defined('ABSPATH')) {
    exit; // Evita el acceso directo
}

/**
 * Clase para gestionar los Custom Post Types y sus funcionalidades asociadas.
 *
 * Esta clase se encarga de registrar los Custom Post Types (CPT),
 * definir sus columnas personalizadas en el administrador y modificar
 * la búsqueda para incluir datos relacionados.
 */
class GCC_Custom_Post_Types {

    /**
     * Constructor de la clase.
     *
     * Registra los Custom Post Types, columnas personalizadas, hooks de ACF y filtros de búsqueda.
     */
    public function __construct() {
        // Registrar los Custom Post Types
        add_action('init', array($this, 'register_custom_post_types'));

        // Agregar y renderizar columnas personalizadas en el administrador
        add_filter('manage_edit-contactos_columns', array($this, 'add_contactos_columns'));
        add_filter('manage_edit-empresas_columns', array($this, 'add_empresas_columns'));
        add_action('manage_contactos_posts_custom_column', array($this, 'render_contactos_columns'), 10, 2);
        add_action('manage_empresas_posts_custom_column', array($this, 'render_empresas_columns'), 10, 2);
        add_action('acf/init', array($this, 'register_acf_fields'));

        // Personalizar la visualización de los post en los campos post_object de ACF
        add_filter('acf/fields/post_object/result', function ($title, $post, $field, $post_id) {
            // Verifica que el campo ACF está definido y es el correcto
            if (!isset($field['key'])) {
                return $title;
            }
        
            // Formato para "empresa" en el Repeater de Experiencia Laboral
            if ($field['key'] === 'field_empresa') {
                $terms = get_the_terms($post->ID, 'sector_empresa');
                // Verificamos que existan términos y no haya errores
                $sector = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'Sin sector';
                
                return sprintf('%d - %s (%s)', $post->ID, get_the_title($post->ID), $sector);
            }
        
            // Formato para "superior_jerarquico" (solo ID - Nombre)
            if ($field['key'] === 'field_superior_jerarquico') {
                return sprintf('%d - %s', $post->ID, get_the_title($post->ID));
            }
        
            return $title;
        }, 10, 4);  
        
        /**
         * Modifica la búsqueda en el backend para incluir contactos relacionados con una empresa.
         *
         * Este filtro permite que, al buscar en el administrador dentro del post type "contactos",
         * también se obtengan los contactos que tengan relación con una empresa cuyo título coincida
         * con el término de búsqueda ingresado.    s
         */
        add_filter('posts_search', function ($search, $query) {
            global $wpdb;
        
            if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'contactos') {
                return $search;
            }
        
            $search_term = $query->get('s');
            if (empty($search_term)) {
                return $search;
            }
        
            // Obtener el ID de la empresa buscada
            $empresa_id = $wpdb->get_var($wpdb->prepare("
                SELECT ID FROM {$wpdb->posts} WHERE post_type = 'empresas' AND post_title LIKE %s", '%' . $wpdb->esc_like($search_term) . '%'));
        
            if (!$empresa_id) {
                return $search;
            }
        
            $meta_query = $wpdb->prepare("
                SELECT DISTINCT pm.post_id 
                FROM {$wpdb->postmeta} pm
                INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                WHERE pm.meta_key LIKE 'experiencia_laboral_%_empresa'
                AND pm.meta_value = %d
                AND p.post_type = 'contactos'", $empresa_id);
            
            $results = $wpdb->get_col($meta_query);
            
            if (!empty($results)) {
                $search .= " OR {$wpdb->posts}.ID IN (" . implode(',', array_map('intval', $results)) . ")";
            }
            
            return $search;
        }, 10, 2);  
    }

    /**
     * Genera etiquetas traducibles para los CPTs.
     *
     * @param string $singular Nombre en singular.
     * @param string $plural Nombre en plural.
     * @return array Etiquetas traducibles.
     */
    private function get_cpt_labels($singular, $plural) {
        return array(
            'name'                  => esc_html__($plural, GCC_TEXT_DOMAIN),
            'singular_name'         => esc_html__($singular, GCC_TEXT_DOMAIN),
            'add_new'               => esc_html__('Add New', GCC_TEXT_DOMAIN),
            'add_new_item'          => sprintf(esc_html__('Add New %s', GCC_TEXT_DOMAIN), $singular),
            'edit_item'             => sprintf(esc_html__('Edit %s', GCC_TEXT_DOMAIN), $singular),
            'new_item'              => sprintf(esc_html__('New %s', GCC_TEXT_DOMAIN), $singular),
            'view_item'             => sprintf(esc_html__('View %s', GCC_TEXT_DOMAIN), $singular),
            'search_items'          => sprintf(esc_html__('Search %s', GCC_TEXT_DOMAIN), $plural),
            'not_found'             => esc_html__('No items found', GCC_TEXT_DOMAIN),
            'not_found_in_trash'    => esc_html__('No items found in Trash', GCC_TEXT_DOMAIN),
            'all_items'             => sprintf(esc_html__('All %s', GCC_TEXT_DOMAIN), $plural),
            'archives'              => sprintf(esc_html__('%s Archives', GCC_TEXT_DOMAIN), $singular),
            'attributes'            => sprintf(esc_html__('%s Attributes', GCC_TEXT_DOMAIN), $singular),
            'insert_into_item'      => sprintf(esc_html__('Insert into %s', GCC_TEXT_DOMAIN), $singular),
            'uploaded_to_this_item' => sprintf(esc_html__('Uploaded to this %s', GCC_TEXT_DOMAIN), $singular),
        );
    }

    /**
     * Registra los Custom Post Types.
     */
    public function register_custom_post_types() {
        $this->register_cpt('contactos', esc_html__('Contact', GCC_TEXT_DOMAIN), esc_html__('Contacts', GCC_TEXT_DOMAIN), 'dashicons-id');
        $this->register_cpt('empresas', esc_html__('Company', GCC_TEXT_DOMAIN), esc_html__('Companies', GCC_TEXT_DOMAIN), 'dashicons-building');
    }

    /**
     * Función genérica para registrar un CPT.
     *
     * @param string $post_type Slug del CPT.
     * @param string $singular Nombre en singular.
     * @param string $plural Nombre en plural.
     * @param string $icon Icono del menú.
     */
    private function register_cpt($post_type, $singular, $plural, $icon) {
        $args = array(
            'labels'             => $this->get_cpt_labels($singular, $plural),
            'public'             => true,
            'has_archive'        => true,
            'show_in_menu'       => true,
            'show_in_rest'       => false, // Habilita la compatibilidad con el editor Gutenberg.
            'supports'           => array('title','thumbnail', 'custom-fields'),
            'menu_icon'          => $icon,
            'rewrite'            => array('slug' => $post_type), // Personaliza el slug del CPT.
        );

        $args = apply_filters("gcc_register_cpt_args_{$post_type}", $args);

        register_post_type($post_type, $args);
    }

    /**
     * Agrega columnas personalizadas a la lista de Contactos en el admin.
     */
    public function add_contactos_columns($columns) {
        $columns['contacto_nombre'] = esc_html__('Nombre', GCC_TEXT_DOMAIN);
        $columns['contacto_apellido'] = esc_html__('Apellido', GCC_TEXT_DOMAIN);
        $columns['contacto_datos'] = esc_html__('Datos de contacto', GCC_TEXT_DOMAIN);
        $columns['contacto_experiencia'] = esc_html__('Empresa actual', GCC_TEXT_DOMAIN);
        $columns['contacto_imagen'] = esc_html__('Imagen', GCC_TEXT_DOMAIN);
        $columns['last_modified'] = 'Última Modificación';
        return $columns;
    }

    /**
     * Agrega las columnas personalizadas para Empresas
     */
    public function add_empresas_columns($columns) {
        $columns['nombre_empresa'] = 'Nombre de la Empresa';
        $columns['matriz_empresa'] = 'Matriz';
        $columns['anio_fundacion'] = 'Año de Fundación';
        $columns['direccion_empresa'] = 'Dirección';
        $columns['contactos_empresa'] = 'Contactos Relacionados';
        $columns['numero_empleados'] = 'Número de Empleados';
        $columns['featured_image'] = 'Imagen de la Empresa';
        $columns['last_modified'] = 'Última Modificación';
        return $columns;
    }

    /**
     * Rellena las columnas personalizadas con datos.
     */
    public function render_contactos_columns($column, $post_id) {
        switch ($column) {
            case 'contacto_nombre':
                echo esc_html(get_post_meta($post_id, 'nombre', true));
                break;
            case 'contacto_apellido':
                echo esc_html(get_post_meta($post_id, 'apellido', true));
                break;
            case 'contacto_datos':
                echo esc_html(get_post_meta($post_id, 'datos_contacto', true));
                break;
            case 'contacto_experiencia':
                // Obtener el campo repeater de ACF
                $experiencias = get_field('experiencia_laboral', $post_id);
    
                if (!empty($experiencias)) {
                    // Ordenar por fecha de inicio descendente
                    usort($experiencias, function ($a, $b) {
                        return strtotime($b['fecha_inicio']) - strtotime($a['fecha_inicio']);
                    });
    
                    // Obtener la última experiencia laboral
                    $ultima_experiencia = $experiencias[0];
    
                    // Obtener el ID de la empresa
                    $empresa_id = $ultima_experiencia['empresa'];
    
                    if ($empresa_id) {
                        // Obtener el nombre de la empresa
                        $empresa_nombre = get_the_title($empresa_id);
    
                        // Obtener el sector (taxonomía 'sector_empresa')
                        $terms = get_the_terms($empresa_id, 'sector_empresa');
                        $sector = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->name : 'Sin sector';
    
                        // Mostrar el resultado con enlace
                        echo '<a href="' . get_edit_post_link($empresa_id) . '">' . esc_html($empresa_nombre) . '</a>';
                        echo ' (' . esc_html($sector) . ')';
                    } else {
                        echo 'Sin empresa';
                    }
                } else {
                    echo 'Sin experiencia registrada';
                }
                break;
            case 'contacto_imagen':
                $thumbnail = get_the_post_thumbnail($post_id, array(50, 50));
                echo $thumbnail ? $thumbnail : esc_html__('No Image', GCC_TEXT_DOMAIN);
                break;
            case 'last_modified':
                $last_modified_user_id = get_post_meta($post_id, '_edit_last', true);
                $last_modified_date = get_the_modified_date('d/m/Y H:i', $post_id);
                
                if ($last_modified_user_id) {
                    $user = get_user_by('ID', $last_modified_user_id);
                    echo esc_html($user->display_name) . ' (' . esc_html($last_modified_date) . ')';
                } else {
                    echo esc_html($last_modified_date);
                }
                break;
        }
    }

    /**
     * Renderiza los valores de las columnas personalizadas
     */
    public function render_empresas_columns($column, $post_id) {
        switch ($column) {
            case 'featured_image':
                echo get_the_post_thumbnail($post_id, [50, 50]);
                break;
            case 'contactos_empresa':
                global $wpdb;
            
                // Consulta para contar los contactos que tienen esta empresa en su experiencia laboral
                $count = $wpdb->get_var($wpdb->prepare("
                    SELECT COUNT(DISTINCT post_id) 
                    FROM {$wpdb->postmeta} 
                    WHERE meta_key LIKE %s 
                    AND meta_value = %d
                ", 'experiencia_laboral_%_empresa', $post_id));
            
                echo esc_html($count ? $count : '0');
                break;
            case 'matriz_empresa':
                $matriz = get_field('matriz_empresa', $post_id);
                if ($matriz && is_object($matriz)) {
                    echo '<a href="' . get_edit_post_link($matriz->ID) . '">' . esc_html(get_the_title($matriz->ID)) . '</a>';
                } else {
                    echo '-';
                }
                break;
            case 'last_modified':
                $last_modified_user_id = get_post_meta($post_id, '_edit_last', true);
                $last_modified_date = get_the_modified_date('d/m/Y H:i', $post_id);
                
                if ($last_modified_user_id) {
                    $user = get_user_by('ID', $last_modified_user_id);
                    echo esc_html($user->display_name) . ' (' . esc_html($last_modified_date) . ')';
                } else {
                    echo esc_html($last_modified_date);
                }
                break;
            default:
                echo get_field($column, $post_id);
                break;
        }
    }

    /**
     * Registra los campos personalizados de ACF.
     */
    public function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_detalles_contacto',
            'title' => 'Detalles de Contacto',
            'fields' => [
                [
                    'key' => 'field_nombre',
                    'label' => 'Nombre',
                    'name' => 'nombre',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_apellido',
                    'label' => 'Apellido',
                    'name' => 'apellido',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_datos_contacto',
                    'label' => 'Datos de contacto',
                    'name' => 'datos_contacto',
                    'type' => 'textarea',
                    'new_lines' => 'wpautop',
                    'required' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'contactos',
                    ],
                ],
            ],
            'menu_order' => 1,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'priority' => 'high'  
        ]);

        acf_add_local_field_group([
            'key' => 'group_experiencia_laboral',
            'title' => 'Experiencia Laboral',
            'fields' => [
                [
                    'key' => 'field_experiencia_laboral',
                    'label' => 'Experiencia Laboral',
                    'name' => 'experiencia_laboral',
                    'type' => 'repeater',
                    'layout' => 'block',
                    'button_label' => 'Añadir Experiencia',
                    'sub_fields' => [
                        [
                            'key' => 'field_empresa',
                            'label' => 'Empresa',
                            'name' => 'empresa',
                            'type' => 'post_object',
                            'post_type' => ['empresas'],
                            'multiple' => 0,
                            'return_format' => 'id',
                            'required' => 1,
                            'ui' => 1,
                            'ajax' => 1,
                            'wrapper' => ['width' => '100%'],
                        ],
                        [
                            'key' => 'field_puesto',
                            'label' => 'Puesto',
                            'name' => 'puesto',
                            'type' => 'text',
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_fecha_inicio',
                            'label' => 'Fecha de Inicio',
                            'name' => 'fecha_inicio',
                            'type' => 'date_picker',
                            'display_format' => 'd/m/Y',
                            'return_format' => 'Ymd',
                            'required' => 1,
                        ],
                        [
                            'key' => 'field_fecha_fin',
                            'label' => 'Fecha de Fin',
                            'name' => 'fecha_fin',
                            'type' => 'date_picker',
                            'display_format' => 'd/m/Y',
                            'return_format' => 'Ymd',
                            'required' => 0,
                        ],
                        [
                            'key' => 'field_superior_jerarquico',
                            'label' => 'Superior Jerárquico',
                            'name' => 'superior_jerarquico',
                            'type' => 'post_object',
                            'post_type' => ['contactos'],
                            'return_format' => 'id',
                            'required' => 0,
                            'ui' => 1,
                            'ajax' => 1,
                            'wrapper' => ['width' => '100%'],
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'contactos',
                    ],
                ],
            ],
            'menu_order' => 3,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'priority' => 'low'  
        ]);

        // Campos para Empresas
        acf_add_local_field_group([
            'key' => 'group_empresas',
            'title' => 'Información de la Empresa',
            'fields' => [
                [
                    'key' => 'field_nombre_empresa',
                    'label' => 'Nombre de la Empresa',
                    'name' => 'nombre_empresa',
                    'type' => 'text',
                    'required' => 1,
                ],
                [
                    'key' => 'field_matriz_empresa',
                    'label' => 'Matriz',
                    'name' => 'matriz_empresa',
                    'type' => 'post_object',
                    'post_type' => ['empresas'],
                    'return_format' => 'object',
                    'required' => 0,
                ],
                [
                    'key' => 'field_anio_fundacion',
                    'label' => 'Año de Fundación',
                    'name' => 'anio_fundacion',
                    'type' => 'number',
                    'required' => 0,
                ],
                [
                    'key' => 'field_direccion_empresa',
                    'label' => 'Dirección',
                    'name' => 'direccion_empresa',
                    'type' => 'text',
                    'required' => 0,
                ],
                [
                    'key' => 'field_numero_empleados',
                    'label' => 'Número de Empleados',
                    'name' => 'numero_empleados',
                    'type' => 'number',
                    'required' => 0,
                ],
            ],
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'empresas',
                    ],
                ],
            ],
            'menu_order' => 2,
            'position' => 'normal',
            'style' => 'default',
            'label_placement' => 'top',
            'instruction_placement' => 'label',
            'active' => true,
            'priority' => 'low'
        ]);
    }
}
