@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Import Data</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-{{ session('status') }}" role="alert">
                            {{ session('message') }}
                            @if (session('rows_imported'))
                                <div class="mt-2">
                                    <strong>{{ session('rows_imported') }}</strong> rows were imported successfully.
                                </div>
                            @endif
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('import.index') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="connection" class="form-label">Database Connection</label>
                            <select name="connection" id="connection" class="form-select @error('connection') is-invalid @enderror" required onchange="this.form.submit()">
                                <option value="">-- Select Connection --</option>
                                @foreach($connections as $conn)
                                    <option value="{{ $conn }}" {{ (old('connection', $selectedConnection ?? '') == $conn) ? 'selected' : '' }}>
                                        {{ ucfirst($conn) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('connection')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="database" class="form-label">Select Database</label>
                            <input type="text" name="database" id="database" class="form-control @error('database') is-invalid @enderror" value="{{ old('database', $selectedDatabase ?? '') }}" onchange="this.form.submit()">
                            @error('database')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="table" class="form-label">Select Table</label>
                            <select name="table" id="table" class="form-select @error('table') is-invalid @enderror" required>
                                <option value="">-- Select a Table --</option>
                                @foreach($tables as $tbl)
                                    <option value="{{ $tbl }}" {{ old('table', $selectedTable ?? '') == $tbl ? 'selected' : '' }}>{{ $tbl }}</option>
                                @endforeach
                            </select>
                            @error('table')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Select the table you want to import data into.</div>
                        </div>

                        <!-- Mapping and preview will be shown in step 2 -->

                        <div class="mb-3">
                            <label for="file" class="form-label">Data File (CSV, XLSX)</label>
                            <input type="file" 
                                   name="file" 
                                   id="file" 
                                   class="form-control @error('file') is-invalid @enderror" 
                                   accept=".csv,.xlsx,.xls" 
                                   required>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Maximum file size: 10MB. First row should contain column headers.</div>
                        </div>

                        <div class="d-grid mb-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-upload me-2"></i> Import Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .card {
        margin-top: 2rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }
    .card-header {
        font-weight: 600;
        background-color: #f8f9fa;
    }
    .form-label {
        font-weight: 500;
    }
    .mapping-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 8px;
    }
    .mapping-file-column {
        flex: 1;
        padding: 0.375rem 0.75rem;
        background-color: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.25rem;
        min-height: 38px;
        display: flex;
        align-items: center;
    }
    .mapping-arrow {
        font-weight: bold;
        min-width: 30px;
        text-align: center;
    }
    .mapping-db-column {
        flex: 1;
    }
    .mapping-db-column select {
        width: 100%;
    }
    #preview-container {
        /* optional: add styling if desired */
    }
    #preview-container th {
        white-space: nowrap;
    }
</style>
@endpush

