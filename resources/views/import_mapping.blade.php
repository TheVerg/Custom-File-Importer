@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Map Columns & Preview</div>
                <div class="card-body">
                    <form action="{{ route('import.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="selectedConnection" value="{{ $selectedConnection }}">
                        <input type="hidden" name="selectedDatabase" value="{{ $selectedDatabase }}">
                        <input type="hidden" name="selectedTable" value="{{ $selectedTable }}">
                        <input type="hidden" name="file_path" value="{{ $file_path }}">

                        <div class="mb-3">
                            <label class="form-label">Map columns</label>
                            @foreach($fileColumns as $fileCol)
                                <div class="mapping-row mb-2">
                                    <div class="mapping-file-column">{{ $fileCol }}</div>
                                    <div class="mapping-arrow">→</div>
                                    <div class="mapping-db-column">
                                        <select name="column_mapping[{{ $fileCol }}]" class="form-select form-select-sm">
                                            <option value="">-- Skip --</option>
                                            @foreach($tableColumns as $dbCol)
                                                <option value="{{ $dbCol }}">{{ $dbCol }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mb-3">
                            <h6>Data Preview (first 5 rows)</h6>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered">
                                    <thead class="table-light">
                                        <tr>
                                            @foreach($fileColumns as $fileCol)
                                                <th>{{ $fileCol }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($previewRows as $row)
                                            <tr>
                                                @foreach($row as $cell)
                                                    <td>{{ $cell }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
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
