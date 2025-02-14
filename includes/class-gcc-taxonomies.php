<?php
if (!defined('ABSPATH')) {
    exit; // Evita el acceso directo
}

/**
 * Clase para registrar las taxonomías personalizadas.
 *
 * Esta clase se encarga de registrar y gestionar las taxonomías 
 * personalizadas utilizadas en el plugin, asegurando su correcta 
 * integración con los tipos de contenido personalizados.
 */
class GCC_Taxonomies {

    /**
     * Constructor de la clase.
     *
     * Registra los hooks necesarios para la creación de las taxonomías
     * personalizadas al inicializar el plugin.
     */
    public function __construct() {
        add_action('init', array($this, 'register_taxonomies'));
    }
    /**
     * Registra las taxonomías personalizadas.
     *
     * Define y registra las taxonomías para los tipos de contenido 
     */
    public function register_taxonomies() {
        $this->register_taxonomy('sector_empresa', esc_html__('empresas'), esc_html__('Company Sector'), esc_html__('Company Sectors'));
    }

    /**
     * Función genérica para registrar una taxonomía.
     *
     * @param string $taxonomy Slug de la taxonomía.
     * @param string $post_type CPT al que está asociada.
     * @param string $singular Nombre en singular.
     * @param string $plural Nombre en plural.
     */
    private function register_taxonomy($taxonomy, $post_type, $singular, $plural) {
        $labels = array(
            'name'                  => esc_html__($plural, GCC_TEXT_DOMAIN),
            'singular_name'         => esc_html__($singular, GCC_TEXT_DOMAIN),
            'search_items'          => sprintf(esc_html__('Search %s', GCC_TEXT_DOMAIN), $plural),
            'all_items'             => sprintf(esc_html__('All %s', GCC_TEXT_DOMAIN), $plural),
            'edit_item'             => sprintf(esc_html__('Edit %s', GCC_TEXT_DOMAIN), $singular),
            'update_item'           => sprintf(esc_html__('Update %s', GCC_TEXT_DOMAIN), $singular),
            'add_new_item'          => sprintf(esc_html__('Add New %s', GCC_TEXT_DOMAIN), $singular),
            'new_item_name'         => sprintf(esc_html__('New %s Name', GCC_TEXT_DOMAIN), $singular),
            'menu_name'             => esc_html__($plural, GCC_TEXT_DOMAIN),
            'not_found'             => sprintf(esc_html__('No %s found', GCC_TEXT_DOMAIN), $plural),
        );

        $args = array(
            'labels'                => $labels,
            'public'                => true,
            'hierarchical'          => true, // True para taxonomías tipo categoría, false para etiquetas.
            'show_admin_column'     => true, // Muestra la columna de la taxonomía en la tabla del CPT.
            'show_in_rest'          => true, // Compatibilidad con el editor Gutenberg.
            'rewrite'               => array(
                'slug'       => $taxonomy,
                'with_front' => false, // Evita que se agregue un prefijo a las URLs.
            ),
        );

        /**
         * Filtro para permitir personalización de los argumentos de la taxonomía.
         */
        $args = apply_filters("gcc_register_taxonomy_args_{$taxonomy}", $args);

        register_taxonomy($taxonomy, $post_type, $args);
    }
}
