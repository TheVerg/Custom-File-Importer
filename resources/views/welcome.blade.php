@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h1 class="h3 mb-0">Welcome to Data Importer</h1>
                    </div>

                    <div class="card-body">
                        <div class="mb-5">
                            <i class="fas fa-file-import fa-4x text-primary mb-4"></i>
                            <h2 class="h4 mb-3">Easily import data from CSV and Excel files</h2>
                            <p class="text-muted mb-4">
                                Quickly upload and import your data into any database table with just a few clicks.
                                Our intuitive interface makes data importation a breeze.
                            </p>
                        </div>
                        
                        <div class="d-grid gap-3">
                            <a href="{{ route('import.index') }}" class="btn btn-primary btn-lg">
                                <i class="fas fa-upload me-2"></i> Start Importing Data
                            </a>
                            
                            <div class="text-muted small mt-3">
                                <div class="d-flex justify-content-center gap-4 mb-2">
                                    <span><i class="fas fa-check-circle text-success me-1"></i> CSV Support</span>
                                    <span><i class="fas fa-check-circle text-success me-1"></i> Excel Support</span>
                                    <span><i class="fas fa-check-circle text-success me-1"></i> Secure</span>
                                </div>
                                <div>No account required. Start importing in seconds.</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4 text-muted small">
                    <p>Need help? Check out our <a href="#" class="text-decoration-none">documentation</a> or <a href="#" class="text-decoration-none">contact support</a>.</p>
                </div>
            </div>
        </div>
    </div>
@endsection