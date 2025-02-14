<?php
if (!defined('ABSPATH')) {
    exit; // Evita el acceso directo
}

/**
 * Clase principal del plugin GCC (Contacts & Companies Manager).
 *
 * Esta clase gestiona la inicialización del plugin, la carga de scripts,
 * el registro de shortcodes, la configuración de endpoints REST API,
 * y la integración con Advanced Custom Fields (ACF).
 */
class GCC_Plugin {

 /**
     * Constructor de la clase.
     * 
     * - Define constantes del plugin.
     * - Incluye archivos necesarios.
     * - Inicializa clases adicionales.
     * - Registra acciones y filtros de WordPress.
     */ 
    public function __construct() {

        add_action('admin_notices', array($this, 'gcc_check_acf_dependency'));

        $this->define_constants();
        $this->include_files();
        $this->initialize_classes();
        
        /**
         * Carga el dominio de texto del plugin para la traducción.
         * Se ejecuta cuando todos los plugins han sido cargados.
         */
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        /**
         * Registra el menú de administración del plugin en el panel de WordPress.
         * Se ejecuta cuando se está generando el menú de administración.
         */
        add_action('admin_menu', array($this, 'register_admin_menu'));

        /**
         * Carga los scripts de Vue.js y otros archivos de frontend del plugin.
         * Se ejecuta en el hook 'wp_enqueue_scripts', que carga scripts en el frontend.
         */
        add_action('wp_enqueue_scripts', array($this,'gcc_enqueue_vue'));

        /**
         * Registra los endpoints personalizados para la API REST del plugin.
         * Se ejecuta cuando se inicializa la API REST de WordPress.
         */
        add_action('rest_api_init', array($this, 'gcc_register_endpoint'));


        /**
         * Maneja la acción de guardar campos ACF personalizados en la administración.
         * Se ejecuta cuando se envía el formulario correspondiente en el backend.
         */
        add_action('admin_post_save_acf_fields', array($this, 'save_acf_fields'));

        /**
         * Maneja la acción de guardar la configuración general del plugin.
         * Se ejecuta cuando se envía el formulario de ajustes en el backend.
         */
        add_action('admin_post_save_general_settings', array($this, 'save_general_settings'));

        /**
         * Registra los campos personalizados de ACF en WordPress.
         * Se ejecuta en el hook 'init', asegurando que se carguen correctamente.
         */
        add_action('init', array($this, 'register_acf_fields'));

        /**
         * Agrega un filtro para personalizar los estilos de los campos de ACF (Advanced Custom Fields).
         * Permite modificar la apariencia o el comportamiento de los campos antes de que se rendericen en la interfaz de usuario.
         */
        add_filter('acf/prepare_field', array($this, 'custom_acf_field_classes'));

        /**
         * Modifica la plantilla utilizada para mostrar los archivos del tipo de contenido personalizado 'contactos'.
         * Si se accede al archivo de 'contactos', se reemplaza la plantilla predeterminada de WordPress por una personalizada.
         * La nueva plantilla se encuentra en la carpeta 'templates' del plugin.
         *
         * @param string $template Ruta de la plantilla actual de WordPress.
         * @return string Ruta de la plantilla personalizada si existe, de lo contrario, mantiene la plantilla original.
         */
        add_filter('template_include', function ($template) {

            if (is_post_type_archive('contactos')) {
                $custom_template = dirname(plugin_dir_path(__FILE__),1) . '/templates/archive-gcc-contacts.php';
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }

            return $template;
        });

        /**
         * Registra un shortcode para listar empresas en el frontend.
         * Permite a los usuarios insertar un listado de empresas en cualquier parte del sitio usando [gcc_listar_empresas].
         *
         * @return string Salida generada por el shortcode con la lista de empresas.
         */
        add_shortcode('gcc_listar_empresas', array($this, 'gcc_listar_empresas_shortcode'));

        // Registrar el endpoint para eliminar campos
        add_action('admin_post_delete_acf_field', array($this, 'handle_delete_acf_field'));

        
    }

    /**
     * Verifica si ACF está instalado y activo. Si no lo está, muestra una alerta en el admin.
     */
    function gcc_check_acf_dependency() {
        // Asegurar que la función is_plugin_active() esté disponible
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Posibles rutas de instalación de ACF
        $acf_plugins = [
            'advanced-custom-fields1/acf.php',
            'advanced-custom-fields1-pro/acf.php',
            'advanced-custom-fields1-pro-main/acf.php'
        ];

        $acf_active = false;
        
        foreach ($acf_plugins as $acf_plugin) {
            if (is_plugin_active($acf_plugin)) {
                $acf_active = true;
                break;
            }
        }

        // Si ACF no está activo, mostrar advertencia
        if (!$acf_active) {
            echo '<div class="notice notice-error"><p>';
            echo esc_html__('Warning: El "Advanced Custom Fields" es requerido para que este plugin funcione correctamente. Por favor instalelo y activelo. Para efectos de pruebas puedes darcargar el plugin ACF pro desde aquí', 'gcc-text-domain');
            echo '</p></div>';
        }
    }

    /**
     * Define constantes globales del plugin.
     *
     * Esta función establece rutas esenciales del plugin y un dominio de texto
     * para la internacionalización.
     *
     * Constantes definidas:
     * - `GCC_PLUGIN_DIR`: Ruta absoluta al directorio del plugin.
     * - `GCC_PLUGIN_URL`: URL del directorio del plugin.
     * - `GCC_TEXT_DOMAIN`: Dominio de texto para la internacionalización.
     *
     * @return void
     */
    private function define_constants() {
        define('GCC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        define('GCC_PLUGIN_URL', plugin_dir_url(__FILE__));
        define('GCC_TEXT_DOMAIN', 'contact-company-manager');
    }

    /**
     * Incluye archivos necesarios para la funcionalidad del plugin.
     *
     * Aquí se cargan las clases responsables de la creación de Custom Post Types
     * y taxonomías personalizadas.
     *
     * @return void
     */
    private function include_files() {
        require_once GCC_PLUGIN_DIR . 'class-gcc-custom-post-types.php';
        require_once GCC_PLUGIN_DIR . 'class-gcc-taxonomies.php';
    }

    /**
     * Instancia las clases principales del plugin.
     *
     * Se inicializan las clases encargadas de registrar los Custom Post Types
     * y las taxonomías personalizadas.
     *
     * @return void
     */
    private function initialize_classes() {
        new GCC_Custom_Post_Types();
        new GCC_Taxonomies();
    }

    /**
     * Carga el dominio de texto para la internacionalización del plugin.
     *
     * Permite la traducción de cadenas de texto utilizando archivos `.mo`
     * ubicados en el directorio `/lang/` dentro del plugin.
     *
     * @return void
     */
    public function load_textdomain() {
        load_plugin_textdomain(GCC_TEXT_DOMAIN, false, dirname(plugin_basename(__FILE__), 2) . '/lang/');
    }

    /**
     * Carga los scripts de Vue.js y la aplicación Vue específica según el Post Type.
     *
     * Esta función carga la librería Vue.js desde un CDN y posteriormente carga el archivo de la aplicación Vue
     * correspondiente a la página en la que se encuentra el usuario.
     * Además, pasa datos de configuración desde PHP a JavaScript mediante wp_localize_script(),
     * incluyendo la URL del endpoint de la API REST y la configuración de sectores permitidos.
     *
     * @return void
     */
    public function gcc_enqueue_vue() {
        wp_enqueue_script('vue-js', 'https://cdn.jsdelivr.net/npm/vue@3/dist/vue.global.prod.js', array(), null, true);

        // Obtener el valor de "Posts per Page"
        $settings = get_option('gcc_general_settings', []);
        $posts_per_page = isset($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 10;
        $excluded_sectors = $settings['excluded_sectors'] ?? [];

        // Obtener solo los sectores que NO están excluidos
        $args = [
            'taxonomy'   => 'sector_empresa',
            'hide_empty' => false,
        ];

        if (!empty($excluded_sectors)) {
            $args['exclude'] = $excluded_sectors; // Excluir los sectores deshabilitados
        }

        $sectors = get_terms($args);
        $simplified_sectors = array_map(function ($sector) {
            return [
                'value' => $sector->term_id, // Usamos el ID como valor
                'label' => $sector->name,    // Usamos el nombre como etiqueta
            ];
        }, $sectors);

        if (is_post_type_archive('contactos')) {
            wp_enqueue_script('gcc-vue-app', dirname(plugin_dir_url(__FILE__), 1) . '/assets/build/js/vue-app-contact.js', array('vue-js'), '1.0.0', true);
            wp_localize_script('gcc-vue-app', 'gccContacts', [
                'apiUrl' => esc_url(rest_url('gcc-contacts/v1/list')),
                'postsPerPage' => $posts_per_page,
                'sectors'  => $simplified_sectors // Solo sectores permitidos
            ]);
        } 
        else{
            wp_enqueue_script('gcc-vue-app', dirname(plugin_dir_url(__FILE__), 1) . '/assets/build/js/vue-app-company.js', array('vue-js'), '1.0.0', true);
            wp_localize_script('gcc-vue-app', 'gccCompanies', [
                'apiUrl' => esc_url(rest_url('gcc-companies/v1/list')),
                'postsPerPage' => $posts_per_page,
                'sectors'  => $simplified_sectors // Solo sectores permitidos
            ]);
        }

    }
    
    /**
     * Registra los endpoints de la API REST para contactos y empresas.
     *
     * Esta función define dos rutas personalizadas en la API REST de WordPress:
     * - `/gcc-contacts/v1/list`: Devuelve una lista de contactos.
     * - `/gcc-companies/v1/list`: Devuelve una lista de empresas.
     *
     * Ambos endpoints utilizan el método `GET` y no requieren autenticación,
     * ya que la función `permission_callback` devuelve `true`, permitiendo el acceso público.
     *
     * @return void
     */
    public function gcc_register_endpoint() {
        register_rest_route('gcc-contacts/v1', '/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'gcc_get_contacts'),
            'permission_callback' => '__return_true',
        ));

        register_rest_route('gcc-companies/v1', '/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'gcc_get_companies'),
            'permission_callback' => '__return_true',
        ));
    }
    
    /**
     * Obtiene una lista de contactos con información detallada, incluyendo su última experiencia laboral.
     *
     * Esta función recopila todos los contactos disponibles y obtiene su última experiencia laboral
     * a partir de un campo repeater de ACF. Si el contacto tiene una empresa asociada, también se incluye
     * su sector y superior jerárquico. Se pueden excluir sectores específicos definidos en las opciones
     * generales del plugin.
     *
     * @param WP_REST_Request $request La solicitud de la API REST, que puede contener un parámetro 'sector'
     *                                 para filtrar los contactos por sector empresarial.
     * @return WP_REST_Response Lista de contactos con sus respectivos datos en formato JSON.
     */
    public function gcc_get_contacts($request) {
        // Obtener el parámetro de sector de la solicitud
        $sector_id = $request->get_param('sector');
    
        // Obtener la configuración de opciones
        $settings = get_option('gcc_general_settings', []);
        $excluded_sectors = $settings['excluded_sectors'] ?? [];
        $posts_per_page = isset($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 10;
    
        // Obtener todas las empresas que pertenecen a los sectores permitidos
        $exclude_companies = [];
        if (!empty($excluded_sectors)) {
            $company_args = [
                'post_type'      => 'empresas',
                'posts_per_page' => -1,
                'tax_query'      => [
                    [
                        'taxonomy' => 'sector_empresa',
                        'field'    => 'term_id',
                        'terms'    => $excluded_sectors,
                        'operator' => 'IN'
                    ]
                ]
            ];
            $companies = get_posts($company_args);
            $exclude_companies = wp_list_pluck($companies, 'ID');
        }
    
        // Obtener todos los contactos
        $contacts = get_posts(array(
            'post_type' => 'contactos',
            'posts_per_page' => -1,
        ));
    
        $data = array();
    
        foreach ($contacts as $contact) {
            // Obtener el campo repeater de ACF
            $experiencias = get_field('experiencia_laboral', $contact->ID);
    
            if (!empty($experiencias)) {
                // Ordenar por fecha de inicio descendente
                usort($experiencias, function ($a, $b) {
                    return strtotime($b['fecha_inicio']) - strtotime($a['fecha_inicio']);
                });
    
                // Obtener la última experiencia laboral
                $ultima_experiencia = $experiencias[0];
    
                // Obtener el ID de la empresa
                $empresa_id = $ultima_experiencia['empresa'];
                $nombre_empresa = "";
                $sector = "";
    
                if ($empresa_id) {
                    // Obtener el nombre de la empresa
                    $empresa_nombre = get_the_title($empresa_id);
    
                    // Obtener el Superior Jerárquico desde el repeater
                    $superior_id = isset($ultima_experiencia['superior_jerarquico']) ? $ultima_experiencia['superior_jerarquico'] : null;
                    $superior_nombre = ($superior_id) ? $superior_id . " - " . get_the_title($superior_id) : 'No asignado';
    
                    // Obtener el sector (taxonomía 'sector_empresa')
                    $terms = get_the_terms($empresa_id, 'sector_empresa');
                    $sector = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->term_id : null;
    
                    // Mostrar el resultado con enlace
                    $nombre_empresa = $empresa_id . ' - ' . esc_html($empresa_nombre) . ' (' . esc_html($terms[0]->name) . ')';
                } else {
                    $nombre_empresa = 'Sin empresa';
                }
            } else {
                $nombre_empresa = 'Sin experiencia registrada';
            }
    
            // Obtener la imagen del contacto
            $thumbnail_url = get_the_post_thumbnail_url($contact->ID, array(50, 50));
            $contact_image = !empty($thumbnail_url)
                ? $thumbnail_url
                : 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><circle cx="25" cy="25" r="25" fill="#ccc"/><text x="50%" y="50%" font-size="10" text-anchor="middle" fill="#333" dy=".3em">No Image</text></svg>');
    
            // Filtrar por sector si se proporciona
            if (!$sector_id || $sector == $sector_id) {
                // Excluir empresas de sectores no permitidos
                if (!in_array($empresa_id, $exclude_companies)) {
                    $data[] = array(
                        'id' => $contact->ID,
                        'name' => get_the_title($contact->ID),
                        'contact_data' => get_post_meta($contact->ID, 'datos_contacto', true),
                        'company' => $nombre_empresa,
                        'contact_image' => $contact_image,
                        'superior_jerarquico' => $superior_nombre,
                        'sector' => $sector // Agregar el sector al resultado
                    );
                }
            }
        }
    
        return rest_ensure_response($data);
    }
    
    /**
     * Registra el menú de administración para la configuración del plugin.
     *
     * Agrega una página en el panel de administración dentro del menú "Opciones".
     * Se utiliza `add_options_page()` para asegurarse de que solo los usuarios con
     * capacidad de gestionar opciones puedan acceder.
     *
     * @return void
     */
    public function register_admin_menu() {

        // Agregar el submenú de ACF Fields correctamente
        add_options_page(
            esc_html__('Contacts & Companies Manager', GCC_TEXT_DOMAIN),
            esc_html__('Contacts & Companies Manager', GCC_TEXT_DOMAIN),
            'manage_options',
            'gcc-plugin',
            array($this, 'render_dashboard_page')
        );
    }
    
    /**
     * Renderiza la página de administración del plugin.
     *
     * Muestra una interfaz de administración con pestañas para configurar las opciones generales
     * y gestionar los campos ACF
     * 
     * @return void
     */
    public function render_dashboard_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general_settings';
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Contacts & Companies Manager', GCC_TEXT_DOMAIN); ?></h1>
    
            <h2 class="nav-tab-wrapper">
                <a href="?page=gcc-plugin&tab=general_settings" class="nav-tab <?php echo $active_tab == 'general_settings' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('General Settings', GCC_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=gcc-plugin&tab=acf_fields" class="nav-tab <?php echo $active_tab == 'acf_fields' ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html__('ACF Fields', GCC_TEXT_DOMAIN); ?>
                </a>
            </h2>
    
            <div class="tab-content">
                <?php
                if ($active_tab == 'general_settings') {
                    $this->render_general_settings();
                } elseif ($active_tab == 'acf_fields') {
                    $this->render_admin_page_ACF_fields();
                }
                ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Renderiza la sección de configuración general del plugin.
     *
     * - Verifica si el usuario tiene permisos para gestionar opciones.
     * - Obtiene y muestra los valores de configuración almacenados.
     * - Permite establecer la cantidad de posts por página.
     * - Permite excluir sectores específicos usando checkboxes.
     * - Muestra un mensaje de éxito tras guardar los ajustes.
     * - Envía los datos a `admin-post.php` para su procesamiento mediante `POST`.
     *
     * @return void
     */
    private function render_general_settings() {

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', GCC_TEXT_DOMAIN));
        }

        // Obtener valores guardados previamente
        $saved_settings = get_option('gcc_general_settings', []);
        $sectors = get_terms(['taxonomy' => 'sector_empresa', 'hide_empty' => false]);
        if (isset($_GET['status']) && $_GET['status'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Fields saved successfully!', GCC_TEXT_DOMAIN) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('General Setting', GCC_TEXT_DOMAIN); ?></h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('save_general_settings', 'gcc_general_nonce'); ?>
                <input type="hidden" name="action" value="save_general_settings">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="posts_per_page"><?php echo esc_html__('Posts per Page', 'gcc-text-domain'); ?></label></th>
                        <td><input type="number" id="posts_per_page" name="posts_per_page" class="regular-text" value="<?php echo esc_attr($saved_settings['posts_per_page'] ?? 10); ?>" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('Exclude Sector', 'gcc-text-domain'); ?></th>
                        <td>
                            <?php foreach ($sectors as $sector) : ?>
                                <label>
                                    <input type="checkbox" name="excluded_sectors[]" value="<?php echo esc_attr($sector->term_id); ?>" <?php echo (isset($saved_settings['excluded_sectors']) && in_array($sector->term_id, (array)$saved_settings['excluded_sectors'])) ? 'checked' : ''; ?>>
                                    <?php echo esc_html($sector->name); ?>
                                </label><br>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button(esc_html__('Save Settings', 'gcc-text-domain')); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Agrega clases CSS personalizadas a los campos de Advanced Custom Fields (ACF).
     *
     * Esta función modifica el array de configuración de los campos ACF para incluir clases CSS
     * adicionales dentro de la clave `wrapper['class']`. Esto permite aplicar estilos específicos 
     * a cada tipo de campo en el panel de administración.
     *
     * @param array $field Datos del campo ACF.
     * @return array Datos del campo ACF con clases modificadas.
     */
    public function custom_acf_field_classes($field) {
        $field['wrapper']['class'] .= ' gcc-custom-acf-field';

        // Aplicar clases CSS según el tipo de campo
        if ($field['type'] === 'text') {
            $field['wrapper']['class'] .= ' gcc-acf-text';
        } elseif ($field['type'] === 'textarea') {
            $field['wrapper']['class'] .= ' gcc-acf-textarea';
        } elseif ($field['type'] === 'number') {
            $field['wrapper']['class'] .= ' gcc-acf-number';
        } elseif ($field['type'] === 'date_picker') {
            $field['wrapper']['class'] .= ' gcc-acf-date';
        }

        return $field;
    }

    /**
     * Renderiza la página de administración para gestionar los campos de Advanced Custom Fields (ACF).
     *
     * Esta función genera una interfaz en el área de administración de WordPress donde los usuarios pueden
     * configurar y guardar los detalles de los campos ACF que se asociarán a los tipos de publicaciones personalizadas.
     *
     * @return void
     */

    public function render_admin_page_ACF_fields() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para acceder a esta página.', GCC_TEXT_DOMAIN));
        }

        if (isset($_GET['status']) && $_GET['status'] === 'success') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Fields saved successfully!', GCC_TEXT_DOMAIN) . '</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Manage ACF Fields', GCC_TEXT_DOMAIN); ?></h1>
            
            <div style="display: flex; gap: 20px;">
                <!-- Formulario para crear campos -->
                <div style="width: 40%; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h2><?php echo esc_html__('Add New Field', GCC_TEXT_DOMAIN); ?></h2>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <?php wp_nonce_field('save_acf_fields', 'gcc_acf_nonce'); ?>
                        <input type="hidden" name="action" value="save_acf_fields">

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="group_title"><?php echo esc_html__('Field Group Title', GCC_TEXT_DOMAIN); ?></label></th>
                                <td><b>Custom Fileds</b><input type="text" id="group_title" name="group_title" class="regular-text" value="custom-fields" readonly></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="post_type"><?php echo esc_html__('Post Type', GCC_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <select id="post_type" name="post_type" required>
                                        <option value="contactos" <?php selected($saved_fields['post_type'] ?? '', 'contactos'); ?>><?php echo esc_html__('Contacts', GCC_TEXT_DOMAIN); ?></option>
                                        <option value="empresas" <?php selected($saved_fields['post_type'] ?? '', 'empresas'); ?>><?php echo esc_html__('Companies', GCC_TEXT_DOMAIN); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="field_key"><?php echo esc_html__('Field Key', GCC_TEXT_DOMAIN); ?></label></th>
                                <td><input type="text" id="field_key" name="field_key" class="regular-text" value="<?php echo esc_attr($saved_fields['field_key'] ?? ''); ?>" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="field_label"><?php echo esc_html__('Field Label', GCC_TEXT_DOMAIN); ?></label></th>
                                <td><input type="text" id="field_label" name="field_label" class="regular-text" value="<?php echo esc_attr($saved_fields['field_label'] ?? ''); ?>" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="field_type"><?php echo esc_html__('Field Type', GCC_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <select id="field_type" name="field_type" required>
                                        <option value="text" <?php selected($saved_fields['field_type'] ?? '', 'text'); ?>><?php echo esc_html__('Text', GCC_TEXT_DOMAIN); ?></option>
                                        <option value="textarea" <?php selected($saved_fields['field_type'] ?? '', 'textarea'); ?>><?php echo esc_html__('Textarea', GCC_TEXT_DOMAIN); ?></option>
                                        <option value="number" <?php selected($saved_fields['field_type'] ?? '', 'number'); ?>><?php echo esc_html__('Number', GCC_TEXT_DOMAIN); ?></option>
                                        <option value="date_picker" <?php selected($saved_fields['field_type'] ?? '', 'date_picker'); ?>><?php echo esc_html__('Date Picker', GCC_TEXT_DOMAIN); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>

                        <?php submit_button(esc_html__('Save Field', GCC_TEXT_DOMAIN)); ?>
                    </form>
                </div>

                <!-- Tabla con los campos ACF registrados -->
                <div style="width: 55%; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <h2><?php echo esc_html__('Existing Fields', GCC_TEXT_DOMAIN); ?></h2>
                   
                    <table class="widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Field Group', GCC_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Post Key', GCC_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Post Label', GCC_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Control Type', GCC_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Post Type', GCC_TEXT_DOMAIN); ?></th>
                                <th><?php echo esc_html__('Actions', GCC_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $saved_fields = get_option('gcc_acf_fields', []);
  
                            if (!empty($saved_fields)) :
                                foreach ($saved_fields as $field) :
                                    ?>
                                    <tr>
                                        <td><?php echo esc_html($field['group_title']); ?></td>
                                        <td><?php echo esc_html($field['field_key']); ?></td>
                                        <td><?php echo esc_html($field['field_label']); ?></td>
                                        <td><?php echo esc_html($field['field_type']); ?></td>
                                        <td><?php echo esc_html($field['post_type']); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=delete_acf_field&field_key=' . urlencode($field['field_key']))); ?>"
                                            class="button button-small button-danger"
                                            onclick="return confirm('<?php echo esc_html__('Are you sure you want to delete this field?', GCC_TEXT_DOMAIN); ?>');">
                                            <?php echo esc_html__('Delete', GCC_TEXT_DOMAIN); ?>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                endforeach;
                            else :
                                ?>
                                <tr>
                                    <td colspan="4"><?php echo esc_html__('No fields found.', GCC_TEXT_DOMAIN); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }


    /**
     * Guarda la configuración general del plugin.
     *
     * Verifica el nonce de seguridad, valida los permisos del usuario y guarda los ajustes 
     * en la base de datos. Luego redirige a la página de configuración con un mensaje de éxito.
     */
    public function save_general_settings() {
        if (!isset($_POST['gcc_general_nonce']) || !wp_verify_nonce($_POST['gcc_general_nonce'], 'save_general_settings')) {
            wp_die(esc_html__('Invalid request.', 'gcc-text-domain'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', 'gcc-text-domain'));
        }

        $settings = [
            'posts_per_page'  => absint($_POST['posts_per_page']),
            'excluded_sectors' => isset($_POST['excluded_sectors']) ? array_map('absint', $_POST['excluded_sectors']) : [],
        ];

        update_option('gcc_general_settings', $settings);
        wp_safe_redirect(admin_url('admin.php?page=gcc-plugin&status=success'));
        exit;
    }
    

    public function save_acf_fields() {
        error_log('Entrando a save_acf_fields()');
    
        // Verificar nonce para seguridad
        if (!isset($_POST['gcc_acf_nonce']) || !wp_verify_nonce($_POST['gcc_acf_nonce'], 'save_acf_fields')) {
            error_log('Nonce inválido');
            wp_die(esc_html__('Invalid request.', GCC_TEXT_DOMAIN));
        }
    
        // Verificar permisos del usuario
        if (!current_user_can('manage_options')) {
            error_log('Sin permisos');
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', GCC_TEXT_DOMAIN));
        }
    
        // Obtener los valores actuales de los campos guardados
        $saved_fields = get_option('gcc_acf_fields', []);
        if (!is_array($saved_fields)) {
            $saved_fields = []; // Asegurarse de que sea un array
        }
    
        // Preparar el nuevo campo con sanitización
        $new_field = [
            'group_title' => sanitize_text_field($_POST['group_title']),
            'post_type'   => sanitize_text_field($_POST['post_type']),
            'field_key'   => sanitize_text_field($_POST['field_key']),
            'field_label' => sanitize_text_field($_POST['field_label']),
            'field_type'  => sanitize_text_field($_POST['field_type']),
        ];
    
        error_log('Nuevo campo: ' . print_r($new_field, true));
    
        // Verificar si el campo ya existe basado en field_key
        $field_exists = false;
        foreach ($saved_fields as $index => $field) {
            if ($field['field_key'] === $new_field['field_key']) {
                // Si el campo ya existe, actualizarlo
                $saved_fields[$index] = $new_field;
                $field_exists = true;
                error_log('Campo actualizado: ' . $new_field['field_key']);
                break;
            }
        }
    
        // Si el campo no existe, agregarlo al array
        if (!$field_exists) {
            $saved_fields[] = $new_field;
            error_log('Campo agregado: ' . $new_field['field_key']);
        }
    
        // Guardar la lista actualizada en la base de datos
        update_option('gcc_acf_fields', $saved_fields);
    
        // Verificar si los datos se guardaron correctamente
        error_log('Datos guardados: ' . print_r(get_option('gcc_acf_fields'), true));
    
        // Redirigir con mensaje de éxito
        wp_safe_redirect(admin_url('admin.php?page=gcc-plugin&status=success&tab=acf_fields'));
        exit;
    }



    /**
     * Registra los campos ACF al cargar WordPress
     */
    public function register_acf_fields() {
        $saved_fields = get_option('gcc_acf_fields', []);
        if (!empty($saved_fields) && function_exists('acf_add_local_field_group')) {

            foreach ($saved_fields as $field){
                acf_add_local_field_group([
                    'key'    => 'group_' . sanitize_key($field['field_key']),
                    'title'  => $field['group_title'],
                    'fields' => [
                        [
                            'key'   => sanitize_key($field['field_key']),
                            'label' => $field['field_label'],
                            'name'  => sanitize_key($field['field_key']),
                            'type'  => $field['field_type'],
                        ],
                    ],
                    'location' => [
                        [
                            [
                                'param'    => 'post_type',
                                'operator' => '==',
                                'value'    => $field['post_type'],
                            ],
                        ],
                    ],
                ]);
            }         
        }
    }

    /**
     * Shortcode para listar empresas.
     */
    function gcc_listar_empresas_shortcode($atts) {
        // Iniciar el buffer de salida
        ob_start();

        // Incluir el template del listado
        include dirname(plugin_dir_path(__FILE__),1) . '/templates/archive-gcc-empresas-shortcode.php';

        // Devolver el contenido del buffer
        return ob_get_clean();
    }


    /**
     * Endpoint REST API para obtener empresas.
     *
     * @param WP_REST_Request $request Objeto de solicitud de la API REST.
     * @return WP_REST_Response Datos de las empresas en formato JSON.
     */
    public function gcc_get_companies($request) {
        // Obtener el parámetro de sector de la solicitud
        $sector_id = $request->get_param('sector');
    
        // Obtener la configuración de opciones
        $settings = get_option('gcc_general_settings', []);
        $excluded_sectors = $settings['excluded_sectors'] ?? [];
        $posts_per_page = isset($settings['posts_per_page']) ? intval($settings['posts_per_page']) : 10;
    
        // Obtener todas las empresas que pertenecen a los sectores excluidos
        $exclude_companies = [];
        if (!empty($excluded_sectors)) {
            $company_args = [
                'post_type'      => 'empresas',
                'posts_per_page' => -1,
                'tax_query'      => [
                    [
                        'taxonomy' => 'sector_empresa',
                        'field'    => 'term_id',
                        'terms'    => $excluded_sectors,
                        'operator' => 'IN'
                    ]
                ]
            ];
            $companies = get_posts($company_args);
            $exclude_companies = wp_list_pluck($companies, 'ID');
        }
    
        // Obtener todas las empresas
        $companies = get_posts(array(
            'post_type' => 'empresas',
            'posts_per_page' => -1,
        ));
    
        $data = array();
    
        foreach ($companies as $company) {
            // Obtener el ID de la empresa
            $empresa_id = $company->ID;
            $nombre_empresa = "";
            $sector = "";

            if ($empresa_id) {
                // Obtener el nombre de la empresa
                $empresa_nombre = get_the_title($empresa_id);

                // Obtener el sector (taxonomía 'sector_empresa')
                $terms = get_the_terms($empresa_id, 'sector_empresa');
                $sector = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->term_id : null;

                // Mostrar el resultado con enlace
                $nombre_empresa = $empresa_id . ' - ' . esc_html($empresa_nombre) . ' (' . esc_html($terms[0]->name) . ')';
            } else {
                $nombre_empresa = 'Sin empresa';
            }

    
            // Obtener la imagen del contacto
            $thumbnail_url = get_the_post_thumbnail_url($empresa_id, array(50, 50));
            $company_image = !empty($thumbnail_url)
                ? $thumbnail_url
                : 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50"><circle cx="25" cy="25" r="25" fill="#ccc"/><text x="50%" y="50%" font-size="10" text-anchor="middle" fill="#333" dy=".3em">No Image</text></svg>');
    
                
            // Filtrar por sector si se proporciona
            if (!$sector_id || $sector == $sector_id) {
                // Excluir empresas de sectores no permitidos
                $matriz_id = get_post_meta($empresa_id, 'matriz_empresa', true);
                // Obtener el nombre de la empresa matriz
                $matriz_nombre = $matriz_id ? get_the_title($matriz_id) : '';

                if (!in_array($empresa_id, $exclude_companies)) {
                    $data[] = array(
                        'id' => $empresa_id,
                        'name' => get_the_title($empresa_id),
                        'anio' => get_post_meta($empresa_id, 'anio_fundacion', true),
                        'address' => get_post_meta($empresa_id, 'direccion_empresa', true),
                        'employees_number' => get_post_meta($empresa_id, 'numero_empleados', true),
                        'matriz' => $matriz_nombre,
                        'company_image' => $company_image,
                        'sector' => $terms[0]->name, // Agregar el sector al resultado
                        'sectorId' => $sector // Agregar el sector al resultado
                    );
                }
            }
        }
    
        return rest_ensure_response($data);
    }


    function gcc_delete_acf_field() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', GCC_TEXT_DOMAIN));
        }

        if (!isset($_GET['field_key']) || empty($_GET['field_key'])) {
            wp_die(esc_html__('Campo inválido.', GCC_TEXT_DOMAIN));
        }

        $field_key = sanitize_text_field($_GET['field_key']);

        // Obtener todos los grupos de campos
        $field_groups = acf_get_field_groups();

        foreach ($field_groups as $group) {
            $fields = acf_get_fields($group['ID']);

            foreach ($fields as $field) {
                if ($field['key'] === $field_key) {
                    // Eliminar el campo
                    acf_delete_field($field['ID']);
                    wp_redirect(admin_url('admin.php?page=gcc_acf_manager&status=deleted'));
                    exit;
                }
            }
        }

        wp_die(esc_html__('No se encontró el campo.', GCC_TEXT_DOMAIN));
    }


    public function handle_delete_acf_field() {
        // Verificar permisos y nonce
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('No tienes permisos para realizar esta acción.', GCC_TEXT_DOMAIN));
        }

        // Obtener la clave del campo a eliminar
        $field_key = isset($_GET['field_key']) ? sanitize_text_field($_GET['field_key']) : '';

        if (empty($field_key)) {
            wp_die(esc_html__('Invalid request.', GCC_TEXT_DOMAIN));
        }

        // Obtener los campos guardados
        $saved_fields = get_option('gcc_acf_fields', []);

        // Buscar y eliminar el campo
        $updated_fields = array_filter($saved_fields, function($field) use ($field_key) {
            return $field['field_key'] !== $field_key;
        });

        // Guardar los campos actualizados
        update_option('gcc_acf_fields', $updated_fields);

        // Redirigir con mensaje de éxito
        wp_safe_redirect(admin_url('admin.php?page=gcc-plugin&status=deleted&tab=acf_fields'));
        exit;
    }

}