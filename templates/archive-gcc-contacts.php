<?php
/**
 * Template Name: Contactos Archive
 * Template Post Type: contactos
 */

get_header(); ?>

<style>
    .contact-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .contact-table th, .contact-table td {
        border: 1px solid #ddd;
        padding: 10px;
        text-align: left;
    }
    .contact-table th {
        background-color: #0073aa;
        color: white;
    }
    .contact-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .contact-table img {
        border-radius: 50%;
        display: block;
    }
    .pagination {
        margin-top: 20px;
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    .pagination button {
        padding: 5px 10px;
        cursor: pointer;
    }
    .pagination button.active {
        background-color: #0073aa;
        color: white;
    }
    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Estilo del Select */
.filter-section select {
    width: 100%;
    max-width: 300px;
    padding: 10px;
    font-size: 16px;
    border: 2px solid #0073aa; /* Color azul de WordPress */
    border-radius: 5px;
    background: #fff;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
}

.filter-section select:focus {
    border-color: #005177;
    outline: none;
    box-shadow: 0 0 5px rgba(0, 115, 170, 0.5);
}

/* Estilo del Botón */
.filter-section button {
    background-color: #0073aa; /* Azul WordPress */
    color: white;
    padding: 10px 20px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background 0.3s ease-in-out;
}

.button:hover {
    background-color: #005177;
}
</style>

<div class="wrap">
    <h1 class="wp-heading-inline">Listado de Contactos</h1>
    <hr class="wp-header-end">
    <div id="app">
        <div v-if="loading">
            <p>Cargando contactos...</p>
        </div>
        <div v-else>
            <!-- Filtro por sector -->
            <div class="filter-section">
                <label for="sector">Filtrar por sector:</label>
                <select id="sector" v-model="selectedSector">
                    <option value="">Todos los sectores</option>
                    <option v-for="sector in sectors" :key="sector.value" :value="sector.value">
                        {{ sector.label }}
                    </option>
                </select>
                <button @click="applyFilter">Aplicar filtro</button>
            </div>
            <div v-if="contacts.length === 0">
                <p>No hay contactos disponibles.</p>
            </div>
            <div v-else>
                <table class="contact-table">
                    <thead>
                        <tr>
                            <th>Imagen</th>
                            <th>Nombre del contacto</th>
                            <th>Empresa actual</th>
                            <th>Nombre del superior jerárquico</th>
                            <th>Datos de contacto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="contact in paginatedContacts" :key="contact.id">
                            <td>
                                <img :src="contact.contact_image" 
                                     alt="Imagen de contacto" 
                                     width="50" 
                                     height="50">
                            </td>
                            <td>{{ contact.name }}</td>
                            <td>{{ contact.company }}</td>
                            <td>{{ contact.superior_jerarquico }}</td>
                            <td>{{ contact.contact_data }}</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Paginación -->
                <div class="pagination">
                    <button @click="prevPage" :disabled="currentPage === 1">Anterior</button>
                    <button v-for="page in totalPages" 
                            :key="page" 
                            @click="goToPage(page)" 
                            :class="{ active: currentPage === page }">
                        {{ page }}
                    </button>
                    <button @click="nextPage" :disabled="currentPage === totalPages">Siguiente</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?> 