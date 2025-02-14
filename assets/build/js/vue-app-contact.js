const { createApp } = Vue;

createApp({
    data() {
        return {
            contacts: [], // Lista de contactos
            loading: true, // Estado de carga
            currentPage: 1, // Página actual
            itemsPerPage: gccContacts.postsPerPage || 10, // Contactos por página
            sectors: gccContacts.sectors || [], // Sectores disponibles
            selectedSector: '' // Sector seleccionado para filtrar
        };
    },
    computed: {
        // Contactos filtrados por sector
        filteredContacts() {
            if (!this.selectedSector) {
                return this.contacts; // Si no hay sector seleccionado, mostrar todos los contactos
            }

            // Convertir selectedSector a número
            const sectorId = Number(this.selectedSector);

            // Filtrar los contactos
            return this.contacts.filter(contact => {
                console.log('Sector del contacto:', contact.sector, 'Sector seleccionado:', sectorId); // Depuración
                return contact.sector === sectorId;
            });
        },
        // Contactos paginados
        paginatedContacts() {
            const start = (this.currentPage - 1) * this.itemsPerPage;
            const end = start + this.itemsPerPage;
            return this.filteredContacts.slice(start, end);
        },
        // Total de páginas
        totalPages() {
            return Math.ceil(this.filteredContacts.length / this.itemsPerPage);
        }
    },
    methods: {
        // Ir a la página anterior
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
            }
        },
        // Ir a la página siguiente
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
            }
        },
        // Ir a una página específica
        goToPage(page) {
            if (page >= 1 && page <= this.totalPages) {
                this.currentPage = page;
            }
        },
        // Obtener los contactos desde la API
        fetchContacts() {
            fetch(gccContacts.apiUrl)
                .then(response => response.json())
                .then(data => {
                    this.contacts = data;
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error al obtener los contactos:', error);
                    this.loading = false;
                });
        },
        // Aplicar el filtro por sector
        applyFilter() {
            this.currentPage = 1; // Reiniciar a la primera página al aplicar un filtro
        }
    },
    mounted() {
        // Cargar los contactos al montar el componente
        this.fetchContacts();
    }
}).mount('#app');