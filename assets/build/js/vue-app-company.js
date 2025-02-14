const { createApp } = Vue;

createApp({
    data() {
        return {
            companies: [], // Lista de contactos
            loading: true, // Estado de carga
            currentPage: 1, // Página actual
            itemsPerPage: gccCompanies.postsPerPage || 10, // Contactos por página
            sectors: gccCompanies.sectors || [], // Sectores disponibles
            selectedSector: '' // Sector seleccionado para filtrar
        };
    },
    computed: {
        // Contactos filtrados por sector
        filteredContacts() {
            if (!this.selectedSector) {
                return this.companies; // Si no hay sector seleccionado, mostrar todos las empresas
            }

            // Convertir selectedSector a número
            const sectorId = Number(this.selectedSector);

            // Filtrar los contactos
            return this.companies.filter(contact => {
                console.log('Sector del contacto:', contact.sectorId, 'Sector seleccionado:', sectorId); // Depuración
                return contact.sectorId === sectorId;
            });
        },
        // Contactos paginados
        paginatedCompanies() {
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
            fetch(gccCompanies.apiUrl)
                .then(response => response.json())
                .then(data => {
                    console.log(data);
                    this.companies = data;
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