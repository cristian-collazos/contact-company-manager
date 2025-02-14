# Contacts & Companies Manager

**Contributors:** cristian-collazos  
**Tags:** contactos, empresas, custom post types, ACF, repeater fields   
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html 

## Descripción

**Contacts & Companies Manager** Es un plugin que permite gestionar contactos y empresas en un sitio WordPress. Con este plugin, puedes:

- Crear y gestionar **Custom Post Types (CPTs)** para contactos y empresas.
- Usar **Advanced Custom Fields PRO** para agregar campos personalizados a los CPTs.
- Relacionar contactos con empresas y gestionar su experiencia laboral mediante campos tipo **repeater**.
- Organizar y visualizar la información de manera eficiente.
- Visualizar en el frontend una lista de contactos, incluyendo:
    -	Nombre del contacto.
    -	Empresa actual (la más reciente en el repeater de experiencia laboral).
    -	Nombre del superior jerárquico, si está configurado.
    -	Un filtro en el frontend que permitr filtrar la lista de contactos por sector de la empresa actual.
-	crear un shortcode que liste las empresas publicadas en un template tipo listado.
-	El plugin cuenta con un panel de administración desde donde se puedgestionar la cantidad de registros que pueden aparecer en los listados y desde este panel tambien se pueden agregar campos personalizaos a los Custom Post Types (CPTs) de Contactos y Empresas.
  
## Requisitos

- **Advanced Custom Fields PRO**: Este plugin es requerido para que GCC Contactos y Empresas funcione correctamente. Puedes descargarlo desde [aquí](https://github.com/cristian-collazos/advanced-custom-fields-pro-main).

## Instalación

1. Sube la carpeta `contact-company-manager-master` al directorio `/wp-content/plugins/`.
2. Activa el plugin a través del menú 'Plugins' en WordPress.
3. Asegúrate de tener instalado y activado **Advanced Custom Fields PRO**.
4. ¡Comienza a gestionar tus contactos y empresas!

## Uso

### 1. Custom Post Types (CPTs)
El plugin crea dos CPTs:

- **Contactos**: Para gestionar información de personas (nombre, teléfono, correo, etc.).
- **Empresas**: Para gestionar información de empresas (nombre, dirección, sector, etc.).

### 2. Campos Personalizados
Usando Advanced Custom Fields PRO, el plugin agrega campos personalizados a los CPTs, como:

- **Campos básicos**: Nombre, teléfono, correo electrónico, etc.
- **Campos de relación**: Relaciona contactos con empresas.
- **Campos tipo repeater**: Gestiona la experiencia laboral de los contactos.

### 3. Experiencia Laboral
El plugin permite agregar la experiencia laboral de un contacto mediante campos tipo repeater. Puedes:

- Agregar múltiples experiencias laborales.
- Relacionar cada experiencia con una empresa.
- Especificar el cargo, fecha de inicio, fecha de fin y descripción.
- Agregar múltiples experiencias laborales.
- Relacionar cada experiencia con una empresa.
- Especificar el cargo, fecha de inicio, fecha de fin y descripción.
- 
### 4. Panel administracion
El plugin tiene un panel de administración, con dos tabs, uno para opciones generales y otro tab para agregar campos personalziados.
En el tab **General setting** se pueden gestionar algunas configuraciones como 
- Cantidad de registros (Paginacion) en los listados del FRONTEND
- Se pueden excluir sectore en los listados
En el tab **ACF Fileds** se pueden agregar y eliminar campos personalizados a los CPT´s de contacto y empresa
- Plugin agrega una nueva opción en el menú de ajustes para acceder al panel de administación del plugin llamada **Gestión de Contactos y Empresas**

### 5. Listados del Frontend
- El plugin genera un template de contactos en el frontend desde donde se pueden ver los contactos registrados  filtras por sector, para acceder al template se debe hacer del siguiente link 
**tu-domino/index.php/contactos/**  
En el CPT de contacto hay un botón "ver contactos en el frontend" para acceder al listado.

- El plugin genera un shortcode con un template tipo listado de empresas que se puede poner en cualquir parte del wordpress el shortcode es
  ** [gcc_listar_empresas] **

