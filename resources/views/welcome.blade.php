<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>QR Menu</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="page">
        <div class="page-wrapper">
            <div class="container-xl">
                <div class="page-header">
                    <div class="row g-2 align-items-center">
                        <div class="col">
                            <h1 class="page-title">
                                Welcome to QR Menu
                            </h1>
                            <p class="text-muted">
                                Current language: {{ app()->getLocale() }}
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="page-body">
                    <div class="row row-cards">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h3>Language Links</h3>
                                    <div class="btn-list">
                                        <a href="{{ LaravelLocalization::getLocalizedURL('en') }}" 
                                           class="btn btn-outline-primary">English</a>
                                        <a href="{{ LaravelLocalization::getLocalizedURL('tr') }}" 
                                           class="btn btn-outline-primary">Türkçe</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>