<!-- resources/views/import/index.blade.php -->
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">CSV/Excel Import System</h4>
                </div>
                <div class="card-body">
                    <!-- Progress Steps -->
                    <div class="steps mb-5">
                        <div class="step-row">
                            <div id="step1" class="step-col active">1. Select Connection</div>
                            <div id="step2" class="step-col">2. Select Database & Table</div>
                            <div id="step3" class="step-col">3. Upload File</div>
                            <div id="step4" class="step-col">4. Map Columns</div>
                            <div id="step5" class="step-col">5. Import</div>
                        </div>
                    </div>

                    <!-- Step 1: Connection Selection -->
                    <div id="step1-content" class="step-content">
                        <h5>Select Database Connection</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="connection">Connection</label>
                                    <select class="form-control" id="connection" name="connection">
                                        <option value="">Select a connection</option>
                                        @foreach($connections as $connection)
                                            <option value="{{ $connection->id }}">{{ $connection->name }} ({{ $connection->driver }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button id="step1-next" class="btn btn-primary">Next</button>
                        </div>
                    </div>

                    <!-- Step 2: Database & Table Selection -->
                    <div id="step2-content" class="step-content d-none">
                        <h5>Select Database and Table</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="database">Database</label>
                                    <select class="form-control" id="database" name="database" disabled>
                                        <option value="">Select database</option>
                                    </select>
                                    <div class="spinner-border spinner-border-sm d-none" id="database-spinner" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="table">Table</label>
                                    <select class="form-control" id="table" name="table" disabled>
                                        <option value="">Select table</option>
                                    </select>
                                    <div class="spinner-border spinner-border-sm d-none" id="table-spinner" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button id="step2-prev" class="btn btn-secondary">Previous</button>
                            <button id="step2-next" class="btn btn-primary">Next</button>
                        </div>
                    </div>

                    <!-- Step 3: File Upload -->
                    <div id="step3-content" class="step-content d-none">
                        <h5>Upload CSV/Excel File</h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="file">Select File</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="file" accept=".csv,.xlsx,.xls">
                                        <label class="custom-file-label" for="file">Choose file</label>
                                    </div>
                                    <small class="form-text text-muted">
                                        Supported formats: CSV, XLSX, XLS (Max: 10MB)
                                    </small>
                                </div>
                                
                                <div id="file-preview" class="mt-3 d-none">
                                    <h6>File Preview</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered" id="sample-table">
                                            <thead></thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button id="step3-prev" class="btn btn-secondary">Previous</button>
                            <button id="step3-next" class="btn btn-primary">Next</button>
                        </div>
                    </div>

                    <!-- Step 4: Column Mapping -->
                    <div id="step4-content" class="step-content d-none">
                        <h5>Map CSV Columns to Database Columns</h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div id="mapping-container"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button id="step4-prev" class="btn btn-secondary">Previous</button>
                            <button id="step4-next" class="btn btn-primary">Next</button>
                        </div>
                    </div>

                    <!-- Step 5: Import -->
                    <div id="step5-content" class="step-content d-none">
                        <h5>Import Settings & Start Import</h5>
                        <div class="row">
                            <div class="col-md-12">
                                <div id="import-summary"></div>
                                <div class="progress mt-3 d-none" id="import-progress">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                         role="progressbar" style="width: 0%"></div>
                                </div>
                                <div id="import-status" class="mt-3"></div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button id="step5-prev" class="btn btn-secondary">Previous</button>
                            <button id="start-import" class="btn btn-success">Start Import</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import History -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Import History</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>File</th>
                                <th>Connection</th>
                                <th>Database</th>
                                <th>Table</th>
                                <th>Status</th>
                                <th>Rows</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($importJobs as $job)
                                <tr>
                                    <td>{{ $job->id }}</td>
                                    <td>{{ $job->file_name }}</td>
                                    <td>{{ $job->connection->name }}</td>
                                    <td>{{ $job->database_name }}</td>
                                    <td>{{ $job->table_name }}</td>
                                    <td>
                                        <span class="badge badge-{{ $job->status === 'completed' ? 'success' : ($job->status === 'failed' ? 'danger' : 'warning') }}">
                                            {{ ucfirst($job->status) }}
                                        </span>
                                    </td>
                                    <td>{{ $job->successful_rows }}/{{ $job->total_rows }}</td>
                                    <td>{{ $job->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewJobDetails({{ $job->id }})">
                                            Details
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{ $importJobs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .steps {
        position: relative;
        margin-bottom: 30px;
    }
    .step-row {
        display: flex;
        justify-content: space-between;
        position: relative;
    }
    .step-col {
        text-align: center;
        position: relative;
        flex: 1;
        padding: 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        margin: 0 5px;
        cursor: pointer;
        transition: all 0.3s;
    }
    .step-col.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }
    .step-content {
        padding: 20px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        background: #f8f9fa;
    }
    .mapping-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
        padding: 10px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 5px;
    }
    
    /* Style for invalid form controls */
    .is-invalid {
        border-color: #dc3545 !important;
        padding-right: calc(1.5em + 0.75rem);
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: right calc(0.375em + 0.1875rem) center;
        background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
    }
    
    .is-invalid:focus {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    console.log('Import script loaded');
    
    // Global variables
    let currentStep = 1;
    let selectedConnection = null;
    let selectedDatabase = null;
    let selectedTable = null;
    let uploadedFile = null;
    let columnMappings = {};
    
    // Initialize everything when the DOM is fully loaded
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM fully loaded, initializing...');
        
        // Initialize all event listeners
        initEventListeners();
        
        // Show first step
        showStep(1);
        console.log('Initialization complete');
    });
    
    function initEventListeners() {
        console.log('Setting up event listeners...');
        
        // Navigation buttons
        const navButtons = [
            { id: 'step1-next', from: 1, to: 2 },
            { id: 'step2-prev', from: 2, to: 1 },
            { id: 'step2-next', from: 2, to: 3 },
            { id: 'step3-prev', from: 3, to: 2 },
            { id: 'step3-next', from: 3, to: 4 },
            { id: 'step4-prev', from: 4, to: 3 },
            { id: 'step4-next', from: 4, to: 5 },
            { id: 'step5-prev', from: 5, to: 4 }
        ];
        
        // Setup navigation buttons
        navButtons.forEach(btn => {
            const element = document.getElementById(btn.id);
            if (element) {
                element.addEventListener('click', function(e) {
                    e.preventDefault();
                    console.log(`${btn.id} clicked`);
                    if (btn.id.includes('next')) {
                        validateAndMove(btn.from, btn.to);
                    } else {
                        showStep(btn.to);
                    }
                });
            } else {
                console.error(`Could not find button: ${btn.id}`);
            }
        });
        
        // Step 4 Previous button
        const step4Prev = document.getElementById('step4-prev');
        if (step4Prev) {
            step4Prev.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Step 4 Previous clicked');
                showStep(3);
            });
        }

        // Step 4 Next button
        const step4Next = document.getElementById('step4-next');
        if (step4Next) {
            step4Next.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Step 4 Next clicked');
                validateAndMove(4, 5);
            });
        }

        // Step 5 Previous button
        const step5Prev = document.getElementById('step5-prev');
        if (step5Prev) {
            step5Prev.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Step 5 Previous clicked');
                showStep(4);
            });
        }

        // Start Import button
        const startImportBtn = document.getElementById('start-import');
        if (startImportBtn) {
            startImportBtn.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Start Import clicked');
                startImport();
            });
        }

        // Connection change listener
        const connectionSelect = document.getElementById('connection');
        if (connectionSelect) {
            connectionSelect.addEventListener('change', function() {
                console.log('Connection changed to:', this.value);
                handleConnectionChange(this.value);
            });
        }

        // Database change listener
        const databaseSelect = document.getElementById('database');
        if (databaseSelect) {
            databaseSelect.addEventListener('change', function() {
                console.log('Database changed to:', this.value);
                handleDatabaseChange(this.value);
            });
        }

        // Table change listener
        const tableSelect = document.getElementById('table');
        if (tableSelect) {
            tableSelect.addEventListener('change', function() {
                console.log('Table changed to:', this.value);
                selectedTable = this.value;
            });
        }

        // File upload listener
        const fileInput = document.getElementById('file');
        if (fileInput) {
            fileInput.addEventListener('change', function(e) {
                console.log('File selected:', e.target.files[0]?.name);
                handleFileUpload(e.target.files[0]);
            });
        }

        console.log('All wizard listeners initialized successfully');
    }

    function showStep(step) {
        console.log(`Showing step ${step}`);
        
        // Hide all step contents
        for (let i = 1; i <= 5; i++) {
            const stepElement = document.getElementById(`step${i}`);
            const contentElement = document.getElementById(`step${i}-content`);
            
            if (stepElement) {
                stepElement.classList.remove('active');
            }
            
            if (contentElement) {
                contentElement.classList.add('d-none');
            }
        }
        
        // Show selected step
        const currentStepElement = document.getElementById(`step${step}`);
        const currentContentElement = document.getElementById(`step${step}-content`);
        
        if (currentStepElement) {
            currentStepElement.classList.add('active');
        }
        
        if (currentContentElement) {
            currentContentElement.classList.remove('d-none');
        }
        
        currentStep = step;
        console.log(`Current step is now: ${currentStep}`);
    }

    function validateAndMove(fromStep, toStep) {
        console.log(`Validating step ${fromStep} before moving to step ${toStep}`);
        
        if (validateStep(fromStep)) {
            showStep(toStep);
            
            // Load data for specific steps
            if (toStep === 4) {
                loadColumnMapping();
            } else if (toStep === 5) {
                showImportSummary();
            }
        }
    }

    function validateStep(step) {
        console.log(`Validating step ${step}`);
        
        switch(step) {
            case 1:
                const connectionSelect = document.getElementById('connection');
                const connectionValue = connectionSelect ? connectionSelect.value : null;
                
                console.log('Connection value:', connectionValue);
                
                if (!connectionValue) {
                    console.error('No connection selected');
                    alert('Please select a database connection');
                    
                    // Add visual feedback
                    if (connectionSelect) {
                        connectionSelect.classList.add('is-invalid');
                        connectionSelect.focus();
                    }
                    
                    return false;
                }
                
                // Clear any invalid state
                if (connectionSelect) {
                    connectionSelect.classList.remove('is-invalid');
                }
                
                selectedConnection = connectionValue;
                console.log(`Selected connection: ${selectedConnection}`);
                return true;
                
            case 2:
                const databaseSelect = document.getElementById('database');
                const tableSelect = document.getElementById('table');
                
                const databaseValue = databaseSelect ? databaseSelect.value : null;
                const tableValue = tableSelect ? tableSelect.value : null;
                
                console.log('Database value:', databaseValue);
                console.log('Table value:', tableValue);
                
                if (!databaseValue || !tableValue) {
                    alert('Please select both a database and a table');
                    
                    // Add visual feedback
                    if (databaseSelect && !databaseValue) {
                        databaseSelect.classList.add('is-invalid');
                        databaseSelect.focus();
                    }
                    
                    if (tableSelect && !tableValue) {
                        tableSelect.classList.add('is-invalid');
                        if (databaseValue) tableSelect.focus();
                    }
                    
                    return false;
                }
                
                // Clear any invalid state
                if (databaseSelect) databaseSelect.classList.remove('is-invalid');
                if (tableSelect) tableSelect.classList.remove('is-invalid');
                
                selectedDatabase = databaseValue;
                selectedTable = tableValue;
                console.log(`Selected database: ${selectedDatabase}, table: ${selectedTable}`);
                return true;
                
            case 3:
                if (!uploadedFile) {
                    alert('Please upload a file');
                    return false;
                }
                console.log('File is uploaded:', uploadedFile.file_name);
                return true;
                
            case 4:
                const mappings = getMappings();
                console.log('Column mappings:', mappings);
                
                if (Object.keys(mappings).length === 0) {
                    alert('Please map at least one column');
                    return false;
                }
                
                columnMappings = mappings;
                console.log('Column mappings saved:', columnMappings);
                return true;
                
            default:
                return true;
        }
    }

    async function handleConnectionChange(connectionId) {
        console.log(`Handling connection change: ${connectionId}`);
        
        if (!connectionId) {
            console.log('No connection ID provided');
            return;
        }
        
        const databaseSelect = document.getElementById('database');
        const tableSelect = document.getElementById('table');
        const spinner = document.getElementById('database-spinner');
        
        // Reset
        if (databaseSelect) {
            databaseSelect.innerHTML = '<option value="">Select database</option>';
            databaseSelect.disabled = true;
        }
        
        if (tableSelect) {
            tableSelect.innerHTML = '<option value="">Select table</option>';
            tableSelect.disabled = true;
        }
        
        // Show spinner
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        
        try {
            console.log(`Fetching databases for connection ID: ${connectionId}`);
            
            console.log(`Fetching databases for connection ID: ${connectionId}`);
            
            const response = await fetch(`/import/connections/${connectionId}/databases`, {
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });
            
            console.log('Response status:', response.status);
            const data = await response.json();
            
            console.log('Database fetch response:', data);
            
            if (data.success && data.databases && databaseSelect) {
                console.log(`Found ${data.databases.length} databases`);
                
                data.databases.forEach(db => {
                    const option = document.createElement('option');
                    option.value = db;
                    option.textContent = db;
                    databaseSelect.appendChild(option);
                });
                
                databaseSelect.disabled = false;
                console.log('Databases loaded successfully');
            } else {
                console.error('Failed to load databases:', data.error);
                alert('Failed to load databases: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error fetching databases:', error);
            alert('Failed to load databases. Please check console for details.');
        } finally {
            if (spinner) {
                spinner.classList.add('d-none');
            }
        }
    }

    async function handleDatabaseChange(database) {
        console.log(`Handling database change: ${database}`);
        
        if (!database || !selectedConnection) {
            console.log('No database or connection selected');
            return;
        }
        
        const tableSelect = document.getElementById('table');
        const spinner = document.getElementById('table-spinner');
        
        // Reset
        if (tableSelect) {
            tableSelect.innerHTML = '<option value="">Select table</option>';
            tableSelect.disabled = true;
        }
        
        // Show spinner
        if (spinner) {
            spinner.classList.remove('d-none');
        }
        
        try {
            console.log(`Fetching tables for database: ${database}`);
            
            const response = await fetch(`/import/connections/${selectedConnection}/tables`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ database: database })
            });
            
            const data = await response.json();
            console.log('Table fetch response:', data);
            
            if (data.success && data.tables && tableSelect) {
                console.log(`Found ${data.tables.length} tables`);
                
                data.tables.forEach(table => {
                    const option = document.createElement('option');
                    option.value = table;
                    option.textContent = table;
                    tableSelect.appendChild(option);
                });
                
                tableSelect.disabled = false;
                console.log('Tables loaded successfully');
            } else {
                console.error('Failed to load tables:', data.error);
                alert('Failed to load tables: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error fetching tables:', error);
            alert('Failed to load tables. Please check console for details.');
        } finally {
            if (spinner) {
                spinner.classList.add('d-none');
            }
        }
    }

    async function handleFileUpload(file) {
        console.log('Handling file upload:', file?.name);
        
        if (!file) return;
        
        const formData = new FormData();
        formData.append('file', file);
        
        // Show loading
        const fileLabel = document.querySelector('.custom-file-label');
        if (fileLabel) {
            fileLabel.textContent = 'Uploading...';
        }
        
        try {
            console.log('Uploading file to server...');
            
            const response = await fetch('/import/upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            });
            
            const data = await response.json();
            console.log('File upload response:', data);
            
            if (data.success) {
                uploadedFile = data;
                
                if (fileLabel) {
                    fileLabel.textContent = data.file_name;
                }
                
                console.log('File uploaded successfully:', data.file_name);
                
                // Show preview
                showFilePreview(data.sample, data.headers);
            } else {
                console.error('File upload failed:', data.error);
                alert('File upload failed: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error uploading file:', error);
            alert('Failed to upload file. Please check console for details.');
        }
    }

    function showFilePreview(sample, headers) {
        console.log('Showing file preview');
        
        const container = document.getElementById('file-preview');
        const thead = document.querySelector('#sample-table thead');
        const tbody = document.querySelector('#sample-table tbody');
        
        if (!container || !thead || !tbody) {
            console.error('Preview elements not found');
            return;
        }
        
        // Clear existing content
        thead.innerHTML = '';
        tbody.innerHTML = '';
        
        // Create header
        const headerRow = document.createElement('tr');
        headers.forEach(header => {
            const th = document.createElement('th');
            th.textContent = header;
            headerRow.appendChild(th);
        });
        thead.appendChild(headerRow);
        
        // Create sample rows
        sample.forEach(row => {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td');
                td.textContent = cell || '';
                tr.appendChild(td);
            });
            tbody.appendChild(tr);
        });
        
        container.classList.remove('d-none');
        console.log('File preview displayed');
    }

    async function loadColumnMapping() {
        console.log('Loading column mapping...');
        
        if (!selectedConnection || !selectedDatabase || !selectedTable || !uploadedFile) {
            console.error('Missing data for column mapping');
            alert('Please complete previous steps first');
            showStep(3);
            return;
        }
        
        try {
            console.log('Fetching column mapping data...');
            
            const response = await fetch('/import/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    connection_id: selectedConnection,
                    database: selectedDatabase,
                    table: selectedTable,
                    file_path: uploadedFile.file_path,
                    file_type: uploadedFile.file_type
                })
            });
            
            const data = await response.json();
            console.log('Column mapping response:', data);
            
            if (data.success) {
                console.log('Column mapping data loaded successfully');
                displayColumnMapping(data.csv_headers, data.db_columns, data.sample);
            } else {
                throw new Error(data.error || 'Failed to load preview data');
            }
        } catch (error) {
            console.error('Error loading column mapping:', error);
            alert('Failed to load column mapping: ' + error.message);
        }
    }

    function displayColumnMapping(csvHeaders, dbColumns, sample) {
        console.log('Displaying column mapping interface');
        
        const container = document.getElementById('mapping-container');
        if (!container) {
            console.error('Mapping container not found');
            return;
        }
        
        container.innerHTML = '';
        
        // Create mapping header
        const header = document.createElement('div');
        header.className = 'row font-weight-bold mb-2';
        header.innerHTML = `
            <div class="col-md-5">CSV Column</div>
            <div class="col-md-1">→</div>
            <div class="col-md-5">Database Column</div>
            <div class="col-md-1">Sample Data</div>
        `;
        container.appendChild(header);
        
        // Create mapping rows
        csvHeaders.forEach((csvHeader, index) => {
            const row = document.createElement('div');
            row.className = 'row mb-3 align-items-center';
            
            // CSV column (readonly)
            const csvCol = document.createElement('div');
            csvCol.className = 'col-md-5';
            csvCol.innerHTML = `<input type="text" class="form-control" value="${csvHeader}" readonly>`;
            
            // Arrow
            const arrowCol = document.createElement('div');
            arrowCol.className = 'col-md-1 text-center';
            arrowCol.innerHTML = '→';
            
            // Database column dropdown
            const dbCol = document.createElement('div');
            dbCol.className = 'col-md-5';
            
            const select = document.createElement('select');
            select.className = 'form-control mapping-select';
            select.dataset.csvColumn = csvHeader;
            
            // Add empty option
            const emptyOption = document.createElement('option');
            emptyOption.value = '';
            emptyOption.textContent = '-- Skip this column --';
            select.appendChild(emptyOption);
            
            // Add database columns
            dbColumns.forEach(dbColumn => {
                const option = document.createElement('option');
                option.value = dbColumn.name;
                option.textContent = `${dbColumn.name} (${dbColumn.type})`;
                select.appendChild(option);
            });
            
            // Auto-match based on name similarity
            const lowerCsv = csvHeader.toLowerCase();
            dbColumns.forEach(dbColumn => {
                const lowerDb = dbColumn.name.toLowerCase();
                if (lowerCsv === lowerDb || lowerCsv.includes(lowerDb) || lowerDb.includes(lowerCsv)) {
                    select.value = dbColumn.name;
                }
            });
            
            dbCol.appendChild(select);
            
            // Sample data
            const sampleCol = document.createElement('div');
            sampleCol.className = 'col-md-1';
            const sampleValue = sample[0] && sample[0][index] ? sample[0][index] : '';
            sampleCol.innerHTML = `<small class="text-muted">${sampleValue}</small>`;
            
            row.appendChild(csvCol);
            row.appendChild(arrowCol);
            row.appendChild(dbCol);
            row.appendChild(sampleCol);
            
            container.appendChild(row);
        });
        
        console.log('Column mapping interface displayed');
    }

    function getMappings() {
        console.log('Getting column mappings...');
        const mappings = {};
        const selects = document.querySelectorAll('.mapping-select');
        
        console.log(`Found ${selects.length} mapping selects`);
        
        selects.forEach(select => {
            if (select.value) {
                mappings[select.dataset.csvColumn] = select.value;
                console.log(`Mapping: ${select.dataset.csvColumn} -> ${select.value}`);
            }
        });
        
        console.log('Total mappings:', Object.keys(mappings).length);
        return mappings;
    }

    function showImportSummary() {
        console.log('Showing import summary');
        
        const importSummary = document.getElementById('import-summary');
        if (!importSummary) {
            console.error('Import summary element not found');
            return;
        }
        
        importSummary.innerHTML = `
            <div class="alert alert-info">
                <h6>Import Summary</h6>
                <p><strong>Database:</strong> ${selectedDatabase}</p>
                <p><strong>Table:</strong> ${selectedTable}</p>
                <p><strong>File:</strong> ${uploadedFile.file_name}</p>
                <p><strong>Mapped Columns:</strong> ${Object.keys(columnMappings).length}</p>
                <p><strong>Connection ID:</strong> ${selectedConnection}</p>
            </div>
        `;
    }

    async function startImport() {
        console.log('Starting import process...');
        
        const importProgress = document.getElementById('import-progress');
        const importStatus = document.getElementById('import-status');
        
        if (!importProgress || !importStatus) {
            console.error('Import progress elements not found');
            return;
        }
        
        // Show progress bar
        importProgress.classList.remove('d-none');
        
        try {
            console.log('Sending import request...');
            
            const response = await fetch('/import/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    connection_id: selectedConnection,
                    database: selectedDatabase,
                    table: selectedTable,
                    file_path: uploadedFile.file_path,
                    file_name: uploadedFile.file_name,
                    file_type: uploadedFile.file_type,
                    mappings: columnMappings
                })
            });
            
            const data = await response.json();
            console.log('Import start response:', data);
            
            if (data.success) {
                const jobId = data.job_id;
                importStatus.innerHTML = `
                    <div class="alert alert-success">
                        Import started successfully! Job ID: ${jobId}
                    </div>
                `;
                
                console.log('Import started, job ID:', jobId);
                
                // Start polling for status
                pollImportStatus(jobId);
            } else {
                throw new Error(data.error || 'Failed to start import');
            }
        } catch (error) {
            console.error('Error starting import:', error);
            importStatus.innerHTML = `
                <div class="alert alert-danger">
                    Failed to start import: ${error.message}
                </div>
            `;
        }
    }

    async function pollImportStatus(jobId) {
        console.log(`Polling import status for job: ${jobId}`);
        
        const progressBar = document.querySelector('#import-progress .progress-bar');
        const importStatus = document.getElementById('import-status');
        
        if (!progressBar || !importStatus) {
            console.error('Polling elements not found');
            return;
        }
        
        const interval = setInterval(async () => {
            try {
                console.log(`Polling job ${jobId} status...`);
                
                const response = await fetch(`/import/jobs/${jobId}/status`);
                const data = await response.json();
                
                console.log('Job status response:', data);
                
                if (data.success) {
                    // Update progress
                    const percentage = data.total_rows > 0 ? (data.processed_rows / data.total_rows) * 100 : 0;
                    progressBar.style.width = `${percentage}%`;
                    progressBar.textContent = `${Math.round(percentage)}%`;
                    
                    // Update status
                    importStatus.innerHTML = `
                        <div class="alert alert-info">
                            <strong>Status:</strong> ${data.status.toUpperCase()}<br>
                            <strong>Processed:</strong> ${data.processed_rows}/${data.total_rows} rows<br>
                            <strong>Successful:</strong> ${data.successful_rows} rows<br>
                            <strong>Failed:</strong> ${data.failed_rows} rows
                        </div>
                    `;
                    
                    // Stop polling if completed
                    if (data.status === 'completed' || data.status === 'failed' || data.status === 'partial') {
                        console.log('Import completed with status:', data.status);
                        clearInterval(interval);
                        
                        // Show final status
                        const alertClass = data.status === 'completed' ? 'success' : 
                                         data.status === 'failed' ? 'danger' : 'warning';
                        
                        importStatus.innerHTML = `
                            <div class="alert alert-${alertClass}">
                                <h6>Import ${data.status.toUpperCase()}</h6>
                                <p><strong>Total Rows:</strong> ${data.total_rows}</p>
                                <p><strong>Successful:</strong> ${data.successful_rows}</p>
                                <p><strong>Failed:</strong> ${data.failed_rows}</p>
                                ${data.error_message ? `<p><strong>Error:</strong> ${data.error_message}</p>` : ''}
                                <p><a href="#" onclick="location.reload()" class="btn btn-sm btn-primary">Refresh page to see in history</a></p>
                            </div>
                        `;
                    }
                } else {
                    console.error('Failed to get job status:', data.error);
                }
            } catch (error) {
                console.error('Error polling job status:', error);
            }
        }, 2000);
    }

    function viewJobDetails(jobId) {
        console.log(`Viewing details for job: ${jobId}`);
        alert(`Viewing details for job ${jobId}`);
    }
</script>
@endpush