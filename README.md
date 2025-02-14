# Contacts & Companies Manager

**Contributors:** cristian-collazos  
**Tags:** contactos, empresas, custom post types, ACF, repeater fields  
**Requiere Advanced Custom Fields PRO **  
**License:** GPLv2 or later  
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html 

## Descripción

**Contacts & Companies Manager** Es un plugin que permite gestionar contactos y empresas en un sitio WordPress. Con este plugin, puedes:

- Crear y gestionar **Custom Post Types (CPTs)** para contactos y empresas.
- Usar **Advanced Custom Fields PRO** para agregar campos personalizados a los CPTs.
- Relacionar contactos con empresas y gestionar su experiencia laboral mediante campos tipo **repeater**.
- Organizar y visualizar la información de manera eficiente.
  
## Requisitos

- **Advanced Custom Fields PRO**: Este plugin es requerido para que GCC Contactos y Empresas funcione correctamente. Puedes descargarlo desde [aquí](https://github.com/cristian-collazos/advanced-custom-fields-pro-main).

## Instalación

1. Sube la carpeta `gcc-contactos-empresas` al directorio `/wp-content/plugins/`.
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

## Changelog

### 6.3.12 

**Release Date:** 21st January 2025

* **Enhancement:** Error messages that occur when field validation fails due to an insufficient security nonce now have additional context.
* **Fix:** Duplicated ACF blocks no longer lose their field values after the initial save when block preloading is enabled.
* **Fix:** ACF Blocks containing complex field types now behave correctly when React StrictMode is enabled.

### 6.3.11

**Release Date:** 12th November 2024

* **Enhancement:** Field Group keys are now copyable on click.
* **Fix:** Repeater tables with fields hidden by conditional logic now render correctly.
* **Fix:** ACF Blocks now behave correctly in React StrictMode.
* **Fix:** Edit mode is no longer available to ACF Blocks with a WordPress Block API version of 3 as field editing is not supported in the iframe.

### 6.3.10.2

**Release Date:** 29th October 2024

*(Free Only Release)*

* **Fix:** ACF Free no longer causes a fatal error when any unsupported legacy ACF addons are active.

---

For the full changelog, visit [Advanced Custom Fields PRO Changelog](https://www.advancedcustomfields.com/changelog/).
