$ErrorActionPreference = 'Stop'

function Invoke-Step {
    param(
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][string]$WorkingDirectory,
        [Parameter(Mandatory = $true)][scriptblock]$Command
    )

    Write-Host "`n=== $Name ===" -ForegroundColor Cyan
    Push-Location $WorkingDirectory
    try {
        & $Command
        if ($LASTEXITCODE -ne 0) {
            throw "$Name gagal dengan exit code $LASTEXITCODE."
        }
    }
    finally {
        Pop-Location
    }
}

$root = $PSScriptRoot

Invoke-Step 'Laravel: format check' "$root\web" {
    vendor\bin\pint --test app bootstrap\app.php config routes database\seeders tests
}
Invoke-Step 'Laravel: automated tests' "$root\web" { php artisan test }
Invoke-Step 'Laravel: fresh migration and seed' "$root\web" {
    $previousAppEnvironment = $env:APP_ENV
    $previousDatabaseConnection = $env:DB_CONNECTION
    $previousDatabaseName = $env:DB_DATABASE
    $env:APP_ENV = 'testing'
    $env:DB_CONNECTION = 'sqlite'
    $env:DB_DATABASE = ':memory:'
    try {
        php artisan migrate:fresh --seed --force
    }
    finally {
        if ($null -eq $previousAppEnvironment) { Remove-Item Env:APP_ENV -ErrorAction SilentlyContinue } else { $env:APP_ENV = $previousAppEnvironment }
        if ($null -eq $previousDatabaseConnection) { Remove-Item Env:DB_CONNECTION -ErrorAction SilentlyContinue } else { $env:DB_CONNECTION = $previousDatabaseConnection }
        if ($null -eq $previousDatabaseName) { Remove-Item Env:DB_DATABASE -ErrorAction SilentlyContinue } else { $env:DB_DATABASE = $previousDatabaseName }
    }
}
Invoke-Step 'Laravel: compile Blade views' "$root\web" { php artisan view:cache }
Invoke-Step 'Web: production build' "$root\web" { npm run build }

Invoke-Step 'Flutter: static analysis' "$root\mobile" { flutter analyze --no-pub }
Invoke-Step 'Flutter: automated tests and coverage' "$root\mobile" { flutter test --coverage --reporter compact }
Invoke-Step 'Flutter: debug APK build' "$root\mobile" {
    flutter build apk --debug --dart-define=API_BASE_URL=http://localhost:8000/api
}

Invoke-Step 'FastAPI: automated tests' "$root\ai-service" { python -m unittest discover -s tests -v }
Invoke-Step 'FastAPI: compile and import smoke test' "$root\ai-service" {
    python -m compileall -q main.py tests
    if ($LASTEXITCODE -eq 0) {
        python -c "import main; assert main.mtcnn_model is None and main.facenet_model is None; print('FastAPI import OK tanpa load model')"
    }
}

Write-Host "`nSEMUA PENGUJIAN DAN BUILD BERHASIL." -ForegroundColor Green
