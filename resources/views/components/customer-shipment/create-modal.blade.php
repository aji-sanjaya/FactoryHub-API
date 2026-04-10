<div x-data="{
    isOpen: false,
    form: {
        warehouse_id: '',
        movement_date: '',
        description: ''
    },
    warehouses: [],
    loading: false,
    
    init() {
        document.addEventListener('open-create-modal', () => {
            this.isOpen = true;
            this.fetchWarehouses();
            // Reset form
            this.form = {
                warehouse_id: '',
                movement_date: new Date().toISOString().slice(0, 10), // Default today
                description: ''
            };
        });
    },

    fetchWarehouses() {
        // We can fetch warehouses from the existing API /auth/api/warehouses
        // Assuming current Org context is handled by session or we need to pass it.
        // Let's try fetching without params first, relying on backend session context.
        axios.get('/auth/api/warehouses') 
            .then(res => {
                this.warehouses = res.data.results || [];
            })
            .catch(err => console.error(err));
    },

    submit() {
        this.loading = true;
        axios.post('{{ route('customer-shipment.store') }}', this.form)
            .then(res => {
                this.isOpen = false;
                // Dispatch event to refresh table
                document.dispatchEvent(new CustomEvent('filter-applied', { detail: {} })); // Or triggers defaults
                // Better to just call fetchData if we can access it, or emit 'refresh-table'
                document.dispatchEvent(new CustomEvent('customer-shipment-created')); 
            })
            .catch(err => {
                console.error(err);
                alert('Failed to create customer shipment');
            })
            .finally(() => {
                this.loading = false;
            });
    }
}" x-show="isOpen" x-cloak class="relative z-[60]" aria-labelledby="modal-title" role="dialog" aria-modal="true">

    <!-- Backdrop -->
    <div x-show="isOpen" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity backdrop-blur-sm"></div>

    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <!-- Modal Panel -->
            <div x-show="isOpen" @click.outside="isOpen = false" x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg dark:bg-gray-900 border border-gray-200 dark:border-gray-800">

                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 dark:bg-gray-900">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                            <h3 class="text-lg font-semibold leading-6 text-gray-900 dark:text-white" id="modal-title">
                                Create Customer Shipment
                            </h3>
                            <div class="mt-4 space-y-4">
                                <!-- Warehouse -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Warehouse</label>
                                    <select x-model="form.warehouse_id"
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white">
                                        <option value="" disabled>Select Warehouse</option>
                                        <template x-for="wh in warehouses" :key="wh.id">
                                            <option :value="wh.id" x-text="wh.text"></option>
                                        </template>
                                    </select>
                                </div>

                                <!-- Movement Date -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Movement Date</label>
                                    <input type="date" x-model="form.movement_date"
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white" />
                                </div>

                                <!-- Description -->
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                    <textarea x-model="form.description" rows="3"
                                        class="mt-1 block w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2 text-sm text-gray-800 focus:border-brand-500 focus:outline-none dark:border-gray-700 dark:text-white"
                                        placeholder="Enter details..."></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 dark:bg-gray-800/50">
                    <button type="button" @click="submit()" :disabled="loading"
                        class="inline-flex w-full justify-center rounded-lg bg-brand-500 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-brand-600 sm:ml-3 sm:w-auto disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!loading">Save</span>
                        <span x-show="loading">Saving...</span>
                    </button>
                    <button type="button" @click="isOpen = false"
                        class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
