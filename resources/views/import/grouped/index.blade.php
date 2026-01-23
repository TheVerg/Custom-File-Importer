@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-layer-group"></i>
                        Grouped File Import
                    </h4>
                    <a href="{{ route('import.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Standard Import
                    </a>
                </div>

                <div class="card-body">
                    <!-- Step 1: File Upload -->
                    <div id="step1" class="import-step">
                        <h5><i class="fas fa-upload"></i> Step 1: Upload Grouped File</h5>
                        <p class="text-muted">Upload a CSV or Excel file that contains grouping headers like "Loan Type: 1050101-MAJIC Agricultural Loans"</p>
                        
                        <div class="mb-3">
                            <label for="groupedFile" class="form-label">Select File</label>
                            <input type="file" class="form-control" id="groupedFile" accept=".csv,.xlsx,.xls">
                            <div class="form-text">Supported formats: CSV, XLSX, XLS (Max 10MB)</div>
                        </div>

                        <button type="button" id="uploadGroupedFile" class="btn btn-primary" disabled>
                            <i class="fas fa-upload"></i> Upload & Analyze File
                        </button>

                        <div id="uploadProgress" class="mt-3" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>
                            <small class="text-muted">Analyzing file structure and detecting loan types...</small>
                        </div>
                    </div>

                    <!-- Step 2: Loan Type Selection -->
                    <div id="step2" class="import-step" style="display: none;">
                        <h5><i class="fas fa-list"></i> Step 2: Select Loan Types to Import</h5>
                        <p class="text-muted">Choose which loan types you want to import and configure column mappings for each.</p>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="connectionSelect" class="form-label">Database Connection</label>
                                <select class="form-select" id="connectionSelect" required>
                                    <option value="">Select Connection...</option>
                                    @foreach($connections as $connection)
                                        <option value="{{ $connection->id }}">{{ $connection->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="databaseSelect" class="form-label">Database</label>
                                <select class="form-select" id="databaseSelect" disabled required>
                                    <option value="">Select Connection First...</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="tableSelect" class="form-label">Target Table</label>
                                <select class="form-select" id="tableSelect" disabled required>
                                    <option value="">Select Database First...</option>
                                </select>
                            </div>
                        </div>

                        <div id="loanTypesContainer">
                            <!-- Loan types will be populated here -->
                        </div>

                        <div class="mt-3">
                            <button type="button" id="backToStep1" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" id="proceedToMapping" class="btn btn-primary" disabled>
                                <i class="fas fa-arrow-right"></i> Configure Mappings
                            </button>
                        </div>
                    </div>

                    <!-- Step 3: Column Mapping -->
                    <div id="step3" class="import-step" style="display: none;">
                        <h5><i class="fas fa-exchange-alt"></i> Step 3: Configure Column Mappings</h5>
                        <p class="text-muted">Map columns from your file to database fields. This mapping will apply to all selected loan types.</p>
                        
                        <div id="mappingContainer">
                            <!-- Mappings will be populated here -->
                        </div>

                        <div class="mt-3">
                            <button type="button" id="backToStep2" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </button>
                            <button type="button" id="startGroupedImport" class="btn btn-success">
                                <i class="fas fa-play"></i> Start Import
                            </button>
                        </div>
                    </div>

                    <!-- Step 4: Import Progress -->
                    <div id="step4" class="import-step" style="display: none;">
                        <h5><i class="fas fa-cogs"></i> Step 4: Import Progress</h5>
                        
                        <div id="importProgress">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Overall Progress</h6>
                                            <div class="progress mb-2">
                                                <div id="overallProgressBar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                            </div>
                                            <small id="overallProgressText">0% Complete</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6>Statistics</h6>
                                            <div id="importStats">
                                                <small>Total Rows: <span id="totalRows">0</span></small><br>
                                                <small>Successful: <span id="successfulRows">0</span></small><br>
                                                <small>Failed: <span id="failedRows">0</span></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="loanTypeProgress" class="mt-3">
                                <!-- Individual loan type progress will be shown here -->
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="button" id="newImport" class="btn btn-primary">
                                <i class="fas fa-plus"></i> New Import
                            </button>
                            <a href="{{ route('import.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-list"></i> View All Imports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.import-step {
    margin-bottom: 2rem;
    padding: 1.5rem;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    background-color: #f8f9fa;
}

.loan-type-card {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    margin-bottom: 1rem;
    background-color: white;
}

.loan-type-header {
    background-color: #f8f9fa;
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.mapping-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.mapping-row select {
    flex: 1;
}

.mapping-row .btn {
    flex-shrink: 0;
}

.progress-item {
    margin-bottom: 1rem;
    padding: 0.75rem;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
}

.sample-data {
    max-height: 200px;
    overflow-y: auto;
    font-size: 0.875rem;
}

.mapping-row {
    margin-bottom: 15px;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 5px;
}

.csv-columns-list, .db-columns-list {
    margin-bottom: 15px;
}

.sample-data table {
    font-size: 0.8rem;
    margin-top: 10px;
}
</style>
@endpush

@push('scripts')
<script>
let uploadedFile = null;
let loanTypes = [];
let selectedLoanTypes = [];
let currentJobId = null;

// File upload handling
$('#groupedFile').on('change', function() {
    $('#uploadGroupedFile').prop('disabled', !this.files.length);
});

$('#uploadGroupedFile').on('click', function() {
    const fileInput = document.getElementById('groupedFile');
    const file = fileInput.files[0];
    
    if (!file) return;
    
    const formData = new FormData();
    formData.append('file', file);
    
    $('#uploadProgress').show();
    $('#uploadGroupedFile').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("grouped.import.upload") }}',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                uploadedFile = response;
                loanTypes = response.loan_types;
                displayLoanTypes();
                showStep(2);
            } else {
                alert('Error: ' + response.error);
            }
        },
        error: function(xhr) {
            alert('Upload failed: ' + (xhr.responseJSON?.error || 'Unknown error'));
        },
        complete: function() {
            $('#uploadProgress').hide();
            $('#uploadGroupedFile').prop('disabled', false);
        }
    });
});

function displayLoanTypes() {
    const container = $('#loanTypesContainer');
    container.empty();
    
    loanTypes.forEach(function(loanType, index) {
        const card = $(`
            <div class="loan-type-card">
                <div class="loan-type-header">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${loanType.code}" id="loanType${index}">
                        <label class="form-check-label" for="loanType${index}">
                            <strong>${loanType.code}</strong> - ${loanType.name}
                            <span class="badge bg-secondary">${loanType.row_count} rows</span>
                        </label>
                    </div>
                </div>
                <div class="card-body p-3">
                    <small class="text-muted">${loanType.full}</small>
                </div>
            </div>
        `);
        container.append(card);
    });
    
    // Enable proceed button when loan types are selected
    $('.loan-type-card input[type="checkbox"]').on('change', function() {
        const anyChecked = $('.loan-type-card input[type="checkbox"]:checked').length > 0;
        $('#proceedToMapping').prop('disabled', !anyChecked);
    });
}

// Database selection handling
$('#connectionSelect').on('change', function() {
    const connectionId = $(this).val();
    if (!connectionId) return;
    
    $.get(`/import/connections/${connectionId}/databases`, function(response) {
        if (response.success) {
            const databaseSelect = $('#databaseSelect');
            databaseSelect.empty().append('<option value="">Select Database...</option>');
            
            response.databases.forEach(function(db) {
                databaseSelect.append(`<option value="${db}">${db}</option>`);
            });
            
            databaseSelect.prop('disabled', false);
            $('#tableSelect').prop('disabled', true);
        } else {
            alert('Error loading databases: ' + response.error);
        }
    });
});

$('#databaseSelect').on('change', function() {
    const connectionId = $('#connectionSelect').val();
    const database = $(this).val();
    if (!connectionId || !database) return;
    
    $.get(`/import/connections/${connectionId}/tables?database=${database}`, function(response) {
        if (response.success) {
            const tableSelect = $('#tableSelect');
            tableSelect.empty().append('<option value="">Select Table...</option>');
            
            response.tables.forEach(function(table) {
                tableSelect.append(`<option value="${table}">${table}</option>`);
            });
            
            tableSelect.prop('disabled', false);
        } else {
            alert('Error loading tables: ' + response.error);
        }
    });
});

$('#tableSelect').on('change', function() {
    const anyChecked = $('.loan-type-card input[type="checkbox"]:checked').length > 0;
    const tableSelected = $(this).val();
    $('#proceedToMapping').prop('disabled', !anyChecked || !tableSelected);
});

// Navigation
$('#backToStep1').on('click', () => showStep(1));
$('#backToStep2').on('click', () => showStep(2));

$('#proceedToMapping').on('click', function() {
    selectedLoanTypes = [];
    $('.loan-type-card input[type="checkbox"]:checked').each(function() {
        selectedLoanTypes.push($(this).val());
    });
    
    loadMappings();
    showStep(3);
});

function loadMappings() {
    const connectionId = $('#connectionSelect').val();
    const database = $('#databaseSelect').val();
    const table = $('#tableSelect').val();
    
    console.log('loadMappings called with:', { connectionId, database, table });
    console.log('Element values:', {
        connectionElement: $('#connectionSelect').length,
        databaseElement: $('#databaseSelect').length,
        tableElement: $('#tableSelect').length
    });
    
    // Validate required fields
    if (!connectionId) {
        console.error('Connection ID is missing');
        alert('Please select a database connection');
        showStep(1);
        return;
    }
    
    if (!database) {
        console.error('Database is missing');
        alert('Please select a database');
        showStep(1);
        return;
    }
    
    if (!table) {
        console.error('Table is missing');
        alert('Please select a table');
        showStep(1);
        return;
    }
    
    console.log('All validations passed, loading single mapping interface');
    
    // Get columns from first selected loan type (they're all the same)
    const firstLoanType = selectedLoanTypes[0];
    if (!firstLoanType) {
        alert('Please select at least one loan type');
        showStep(2);
        return;
    }
    
    const loanType = loanTypes.find(lt => lt.code === firstLoanType);
    
    // Get CSV columns for this loan type
    $.ajax({
        url: '{{ route("grouped.import.columns") }}',
        type: 'GET',
        data: {
            file_path: uploadedFile.file_path,
            file_type: uploadedFile.file_type,
            loan_type_code: firstLoanType
        },
        success: function(response) {
            if (response.success) {
                displaySingleMapping(response.columns, connectionId, database, table);
            }
        }
    });
}

function displaySingleMapping(csvColumns, connectionId, database, table) {
    console.log('Displaying single mapping for all loan types', { connectionId, database, table });
    
    // Load database columns first
    const columnsUrl = `/import/connections/${connectionId}/columns?database=${database}&table=${table}`;
    console.log('Fetching database columns from:', columnsUrl);
    
    $.get(columnsUrl, function(response) {
        if (response.success) {
            const dbColumns = response.columns;
            
            const mappingHtml = `
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-exchange-alt"></i>
                            Column Mapping (Applies to All Selected Loan Types)
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>CSV Columns</h6>
                                <div class="csv-columns-list">
                                    ${csvColumns.map(col => `<span class="badge bg-light text-dark me-1 mb-1">${col}</span>`).join('')}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Database Columns</h6>
                                <div class="db-columns-list">
                                    ${dbColumns.map(col => `<span class="badge bg-secondary me-1 mb-1">${col}</span>`).join('')}
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <h6>Map Columns</h6>
                            <div id="singleMappingContainer">
                                <!-- Single mapping interface will be populated here -->
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-outline-info btn-sm" onclick="previewMapping()">
                                <i class="fas fa-eye"></i> Preview Sample Data
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            $('#mappingContainer').html(mappingHtml);
            
            // Create single mapping interface
            createSingleMappingInterface(csvColumns, dbColumns);
        } else {
            console.error('Failed to load database columns:', response.error);
            $('#mappingContainer').html(`<div class="alert alert-danger">Error: ${response.error}</div>`);
        }
    }).fail(function(xhr, status, error) {
        console.error('AJAX error loading columns:', { status, error, xhr });
        $('#mappingContainer').html(`<div class="alert alert-danger">Failed to load columns: ${error}</div>`);
    });
}

function createSingleMappingInterface(csvColumns, dbColumns) {
    const container = $('#singleMappingContainer');
    container.empty();
    
    csvColumns.forEach(function(csvCol) {
        const mappingRow = $(`
            <div class="mapping-row">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <label class="form-label">${csvCol}</label>
                    </div>
                    <div class="col-md-1">
                        <i class="fas fa-arrow-right text-muted"></i>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" data-csv-column="${csvCol}">
                            <option value="">-- Ignore --</option>
                            ${dbColumns.map(dbCol => `<option value="${dbCol}">${dbCol}</option>`).join('')}
                        </select>
                    </div>
                </div>
            </div>
        `);
        container.append(mappingRow);
    });
}

function previewMapping() {
    const firstLoanType = selectedLoanTypes[0];
    if (!firstLoanType) {
        alert('Please select at least one loan type');
        return;
    }
    
    $.ajax({
        url: '{{ route("grouped.import.sample") }}',
        type: 'GET',
        data: {
            file_path: uploadedFile.file_path,
            file_type: uploadedFile.file_type,
            loan_type_code: firstLoanType
        },
        success: function(response) {
            if (response.success && response.sample.length > 0) {
                const headers = Object.keys(response.sample[0]);
                let tableHtml = '<div class="sample-data mt-3"><h6>Sample Data Preview</h6><table class="table table-sm table-striped"><thead><tr>';
                
                headers.forEach(header => {
                    tableHtml += `<th>${header}</th>`;
                });
                tableHtml += '</tr></thead><tbody>';
                
                response.sample.forEach(row => {
                    tableHtml += '<tr>';
                    headers.forEach(header => {
                        tableHtml += `<td>${row[header] || ''}</td>`;
                    });
                    tableHtml += '</tr>';
                });
                
                tableHtml += '</tbody></table></div>';
                
                // Remove existing preview if any
                $('.sample-data').remove();
                
                // Add new preview after mapping container
                $('#mappingContainer').after(tableHtml);
            } else {
                alert('No sample data available for this loan type.');
            }
        },
        error: function(xhr) {
            alert('Failed to load sample data: ' + (xhr.responseJSON?.error || 'Unknown error'));
        }
    });
}

function monitorImportProgress() {
    if (!currentJobId) return;
    
    const interval = setInterval(function() {
        $.get(`/import/jobs/${currentJobId}/status`, function(response) {
            if (response.success) {
                updateProgressDisplay(response);
                
                if (response.status === 'completed' || response.status === 'failed' || response.status === 'partial') {
                    clearInterval(interval);
                }
            }
        });
    }, 2000);
}

function updateProgressDisplay(status) {
    const total = status.total_rows || 0;
    const processed = status.processed_rows || 0;
    const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
    
    $('#overallProgressBar').css('width', percentage + '%').text(percentage + '%');
    $('#overallProgressText').text(percentage + '% Complete');
    $('#totalRows').text(total);
    $('#successfulRows').text(status.successful_rows || 0);
    $('#failedRows').text(status.failed_rows || 0);
}

function showStep(stepNumber) {
    $('.import-step').hide();
    $(`#step${stepNumber}`).show();
}

$('#newImport').on('click', function() {
    location.reload();
});
</script>
@endpush
