@props(['isMultiRow' => false])

<v-datagrid {{ $attributes }}>
    {{ $slot }}
</v-datagrid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-datagrid-template"
    >
        <div>
            <!-- Toolbar -->
            <x-admin::datagrid.toolbar />

            <div class="mt-4 flex">
                <x-admin::datagrid.table :isMultiRow="$isMultiRow">
                    <template #header="{
                        isLoading,
                        available,
                        applied,
                        selectAll,
                        sort,
                        performAction
                    }">
                        <slot
                            name="header"
                            :is-loading="isLoading"
                            :available="available"
                            :applied="applied"
                            :select-all="selectAll"
                            :sort="sort"
                            :perform-action="performAction"
                        >
                        </slot>
                    </template>

                    <template #body="{
                        isLoading,
                        available,
                        applied,
                        selectAll,
                        sort,
                        performAction
                    }">
                        <slot
                            name="body"
                            :is-loading="isLoading"
                            :available="available"
                            :applied="applied"
                            :select-all="selectAll"
                            :sort="sort"
                            :perform-action="performAction"
                        >
                        </slot>
                    </template>
                </x-admin::datagrid.table>
            </div>
        </div>
    </script>

    <script type="module">
        app.component('v-datagrid', {
            template: '#v-datagrid-template',

            props: ['src'],

            data() {
                return {
                    isLoading: false,

                    available: {
                        id: null,

                        columns: [],

                        actions: [],

                        massActions: [],

                        records: [],

                        meta: {},
                    },

                    applied: {
                        massActions: {
                            meta: {
                                mode: 'none',

                                action: null,
                            },

                            indices: [],

                            value: null,
                        },

                        pagination: {
                            page: 1,

                            perPage: 10,
                        },

                        sort: {
                            column: null,

                            order: null,
                        },

                        filters: {
                            columns: [
                                {
                                    index: 'all',
                                    value: [],
                                },
                            ],
                        },

                        savedFilterId: null,
                    },
                };
            },

            watch: {
                'available.records': function (newRecords, oldRecords) {
                    this.setCurrentSelectionMode();

                    this.updateDatagrids();

                    this.updateExportComponent();
                },

                'applied.savedFilterId': function (newSavedFilterId, oldSavedFilterId) {
                    this.updateDatagrids();
                },

                'applied.massActions.indices': function (newIndices, oldIndices) {
                    this.setCurrentSelectionMode();
                },
            },

            mounted() {
                this.boot();
            },

            methods: {
                /**
                 * Initialization: This function checks for any previously saved filters in local storage and applies them as needed.
                 *
                 * @returns {void}
                 */
                boot() {
                    let datagrids = this.getDatagrids();

                    const urlParams = new URLSearchParams(window.location.search);

                    if (urlParams.has('search')) {
                        let searchAppliedColumn = this.applied.filters.columns.find(column => column.index === 'all');

                        searchAppliedColumn.value = [urlParams.get('search')];
                    }

                    if (datagrids?.length) {
                        const currentDatagrid = datagrids.find(({ src }) => src === this.src);

                        if (currentDatagrid) {
                            this.applied.pagination = currentDatagrid.applied.pagination;

                            this.applied.sort = currentDatagrid.applied.sort;

                            this.applied.filters = currentDatagrid.applied.filters;

                            this.applied.savedFilterId = currentDatagrid.applied.savedFilterId;

                            if (urlParams.has('search')) {
                                let searchAppliedColumn = this.applied.filters.columns.find(column => column.index === 'all');

                                searchAppliedColumn.value = [urlParams.get('search')];
                            }

                            this.get();

                            return;
                        }
                    }

                    this.get();
                },

                /**
                 * Get. This will prepare params from the `applied` props and fetch the data from the backend.
                 *
                 * @returns {void}
                 */
                get(extraParams = {}) {
                    let params = {
                        pagination: {
                            page: this.applied.pagination.page,
                            per_page: this.applied.pagination.perPage,
                        },

                        sort: {},

                        filters: {},
                    };

                    if (
                        this.applied.sort.column &&
                        this.applied.sort.order
                    ) {
                        params.sort = this.applied.sort;
                    }

                    this.applied.filters.columns.forEach(column => {
                        params.filters[column.index] = column.value;
                    });

                    const urlParams = new URLSearchParams(window.location.search);

                    urlParams.forEach((param, key) => params[key] = param);

                    this.isLoading = true;

                    this.$axios
                        .get(this.src, {
                            params: { ...params, ...extraParams }
                        })
                        .then((response) => {
                            /**
                             * Precisely taking all the keys to the data prop to avoid adding any extra keys from the response.
                             */
                            const {
                                id,
                                columns,
                                actions,
                                mass_actions,
                                records,
                                meta
                            } = response.data;

                            this.available.id = id;

                            this.available.columns = columns;

                            this.available.actions = actions;

                            this.available.massActions = mass_actions;

                            this.available.records = records;

                            this.available.meta = meta;

                            this.isLoading = false;
                        });
                },

                /**
                 * Change Page. When the child component has handled all the cases, it will send the
                 * valid new page; otherwise, it will block. Here, we are certain that we have
                 * a new page, so the parent will simply call the AJAX based on the new page.
                 *
                 * @param {integer} newPage
                 * @returns {void}
                 */
                changePage(newPage) {
                    this.applied.pagination.page = newPage;

                    this.get();
                },

                /**
                 * Change per page option.
                 *
                 * @param {integer} option
                 * @returns {void}
                 */
                changePerPageOption(option) {
                    this.applied.pagination.perPage = option;

                    /**
                     * When the total records are less than the number of data per page, we need to reset the page.
                     */
                    if (this.available.meta.last_page >= this.applied.pagination.page) {
                        this.applied.pagination.page = 1;
                    }

                    this.get();
                },

                /**
                 * Sort results.
                 *
                 * @param {object} column
                 * @returns {void}
                 */
                sort(column) {
                    if (column.sortable) {
                        this.applied.sort = {
                            column: column.index,
                            order: this.applied.sort.order === 'asc' ? 'desc' : 'asc',
                        };

                        /**
                         * When the sorting changes, we need to reset the page.
                         */
                        this.applied.pagination.page = 1;

                        this.get();
                    }
                },

                /**
                 * Search results.
                 *
                 * @param {object} filters
                 * @returns {void}
                 */
                search(filters) {
                    this.applied.filters.columns = [
                        ...(this.applied.filters.columns.filter((column) => column.index !== 'all')),
                        ...filters.columns,
                    ];

                    /**
                     * We need to reset the page on filtering.
                     */
                    this.applied.pagination.page = 1;

                    this.get();
                },

                /**
                 * Filter results.
                 *
                 * @param {object} filters
                 * @returns {void}
                 */
                 filter(filters) {
                    this.applied.filters.columns = [
                        ...(this.applied.filters.columns.filter((column) => column.index === 'all')),
                        ...filters.columns,
                    ];

                    /**
                     * This will check for empty column values and reset the saved filter ID to ensure the saved filter is not highlighted.
                     */
                    const isEmptyColumnValue = this.applied.filters.columns
                        .filter((column) => column.index !== 'all')
                        .every((column) => column.value.length === 0);

                    if (isEmptyColumnValue) {
                        this.applied.savedFilterId = null;
                    }

                    /**
                     * We need to reset the page on filtering.
                     */
                    this.applied.pagination.page = 1;

                    this.get();
                },

                /**
                 * Filter results by the saved filter.
                 *
                 * @param {Object} filter
                 * @returns {void}
                 */
                 applySavedFilter(filter) {
                    if (! filter) {
                        this.applied.savedFilterId = null;

                        return;
                    }

                    this.applied = filter.applied;

                    this.applied.savedFilterId = filter.id;

                    this.get();
                },

                /**
                 * This will analyze the current selection mode based on the mass action indices.
                 *
                 * @returns {void}
                 */
                setCurrentSelectionMode() {
                    this.applied.massActions.meta.mode = 'none';

                    if (! this.available.records.length) {
                        return;
                    }

                    let selectionCount = 0;

                    this.available.records.forEach(record => {
                        const id = record[this.available.meta.primary_column];

                        if (this.applied.massActions.indices.includes(id)) {
                            this.applied.massActions.meta.mode = 'partial';

                            ++selectionCount;
                        }
                    });

                    if (this.available.records.length === selectionCount) {
                        this.applied.massActions.meta.mode = 'all';
                    }
                },

                /**
                 * This will select all records and update the mass action indices.
                 *
                 * @returns {void}
                 */
                selectAll() {
                    if (['all', 'partial'].includes(this.applied.massActions.meta.mode)) {
                        this.available.records.forEach(record => {
                            const id = record[this.available.meta.primary_column];

                            this.applied.massActions.indices = this.applied.massActions.indices.filter(selectedId => selectedId !== id);
                        });

                        this.applied.massActions.meta.mode = 'none';
                    } else {
                        this.available.records.forEach(record => {
                            const id = record[this.available.meta.primary_column];

                            let found = this.applied.massActions.indices.find(selectedId => selectedId === id);

                            if (! found) {
                                this.applied.massActions.indices = [
                                    ...this.applied.massActions.indices,
                                    id,
                                ];
                            }
                        });

                        this.applied.massActions.meta.mode = 'all';
                    }
                },

                /**
                 * Updates the export component properties whenever new results appear in the datagrid.
                 *
                 * @returns {void}
                 */
                 updateExportComponent() {
                    /**
                     * This event should be fired whenever new results appear. This allows the export feature to
                     * listen to it and update its properties accordingly.
                     */
                     this.$emitter.emit('change-datagrid', {
                        available: this.available,
                        applied: this.applied
                    });
                },

                //=======================================================================================
                // Support for previous applied values in datagrid's. All code is based on local storage.
                //=======================================================================================

                /**
                 * Updates the datagrid's stored in local storage with the latest data.
                 *
                 * @returns {void}
                 */
                updateDatagrids() {
                    let datagrids = this.getDatagrids();

                    if (datagrids?.length) {
                        const currentDatagrid = datagrids.find(({ src }) => src === this.src);

                        if (currentDatagrid) {
                            datagrids = datagrids.map(datagrid => {
                                if (datagrid.src === this.src) {
                                    return {
                                        ...datagrid,
                                        requestCount: ++datagrid.requestCount,
                                        applied: this.applied,
                                    };
                                }

                                return datagrid;
                            });
                        } else {
                            datagrids.push(this.getDatagridInitialProperties());
                        }
                    } else {
                        datagrids = [this.getDatagridInitialProperties()];
                    }

                    this.setDatagrids(datagrids);
                },

                /**
                 * Returns the initial properties for a datagrid.
                 *
                 * @returns {object} Initial properties for a datagrid.
                 */
                getDatagridInitialProperties() {
                    return {
                        src: this.src,
                        requestCount: 0,
                        applied: this.applied,
                    };
                },

                /**
                 * Returns the storage key for datagrid's in local storage.
                 *
                 * @returns {string} Storage key for datagrid's.
                 */
                getDatagridsStorageKey() {
                    return 'datagrids';
                },

                /**
                 * Retrieves the datagrids stored in local storage.
                 *
                 * @returns {Array} Datagrids stored in local storage.
                 */
                getDatagrids() {
                    let datagrids = localStorage.getItem(
                        this.getDatagridsStorageKey()
                    );

                    return JSON.parse(datagrids) ?? [];
                },

                /**
                 * Sets the datagrid's in local storage.
                 *
                 * @param {Array} datagrids - Datagrid's to be stored in local storage.
                 * @returns {void}
                 */
                setDatagrids(datagrids) {
                    localStorage.setItem(
                        this.getDatagridsStorageKey(),
                        JSON.stringify(datagrids)
                    );
                },
            },
        });
    </script>
@endPushOnce
