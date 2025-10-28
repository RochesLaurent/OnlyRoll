# check-ci.ps1
# Script de vérification CI/CD pour OnlyRoll (aligné sur GitHub Actions)
# Usage: .\check-ci.ps1

$ErrorActionPreference = "Continue"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$errorsFound = $false
$results = @()

function Write-Section($title) {
    Write-Host ""
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host "  $title" -ForegroundColor Cyan
    Write-Host "==========================================" -ForegroundColor Cyan
    Write-Host ""
}

function Write-Step($message) {
    Write-Host "▶ $message" -ForegroundColor Yellow
}

function Write-Success($message) {
    Write-Host "✓ $message" -ForegroundColor Green
}

function Write-ErrorMsg($message) {
    Write-Host "✗ $message" -ForegroundColor Red
}

function Run-Command {
    param(
        [string]$Name,
        [string]$Command,
        [string]$WorkingDirectory = "."
    )
    
    Write-Step "$Name"
    
    $startTime = Get-Date
    $success = $true
    
    try {
        Push-Location $WorkingDirectory
        
        Invoke-Expression $Command
        
        if ($LASTEXITCODE -ne 0) {
            $success = $false
            $script:errorsFound = $true
        }
    }
    catch {
        $success = $false
        $script:errorsFound = $true
        Write-Host $_.Exception.Message -ForegroundColor Red
    }
    finally {
        Pop-Location
    }
    
    $endTime = Get-Date
    $duration = [math]::Round(($endTime - $startTime).TotalSeconds, 2)
    
    $result = [PSCustomObject]@{
        Name = $Name
        Success = $success
        Duration = $duration
    }
    
    $script:results += $result
    
    if ($success) {
        Write-Success "$Name - OK (${duration}s)"
    } else {
        Write-ErrorMsg "$Name - ÉCHEC (${duration}s)"
    }
    
    Write-Host ""
    return $success
}

# Début
Write-Host @"
╔═══════════════════════════════════════════╗
║   OnlyRoll - Vérification CI/CD           ║
║   Aligné sur GitHub Actions               ║
╚═══════════════════════════════════════════╝
"@ -ForegroundColor Magenta

Write-Host ""

# ============================================
# BACKEND
# ============================================
Write-Section "BACKEND - Qualité du Code"

if (-Not (Test-Path "backend")) {
    Write-ErrorMsg "Le dossier 'backend' n'existe pas!"
    exit 1
}

Write-Step "Installation des dépendances Composer..."
Push-Location backend
composer install --no-interaction --quiet 2>&1 | Out-Null
Pop-Location
Write-Success "Dépendances installées"
Write-Host ""

# PHPStan
Run-Command `
    -Name "PHPStan (Analyse statique)" `
    -Command "vendor\bin\phpstan analyse src --memory-limit=512M --error-format=github" `
    -WorkingDirectory "backend"

# PHP-CS-Fixer (--dry-run comme la CI)
Run-Command `
    -Name "PHP-CS-Fixer (Formatage)" `
    -Command "vendor\bin\php-cs-fixer fix --dry-run --diff --verbose" `
    -WorkingDirectory "backend"

Write-Section "BACKEND - Tests"

# PHPUnit
Run-Command `
    -Name "PHPUnit (Tests unitaires)" `
    -Command "vendor\bin\phpunit --testdox --colors=never" `
    -WorkingDirectory "backend"

# ============================================
# FRONTEND
# ============================================
Write-Section "FRONTEND - Qualité du Code"

if (-Not (Test-Path "frontend")) {
    Write-ErrorMsg "Le dossier 'frontend' n'existe pas!"
    exit 1
}

Write-Step "Installation des dépendances NPM..."
Push-Location frontend
npm ci --silent 2>&1 | Out-Null
Pop-Location
Write-Success "Dépendances installées"
Write-Host ""

# ESLint
Run-Command `
    -Name "ESLint (Linting)" `
    -Command "npm run lint" `
    -WorkingDirectory "frontend"

# Prettier
Run-Command `
    -Name "Prettier (Formatage)" `
    -Command "npm run format:check" `
    -WorkingDirectory "frontend"

# TypeScript
Run-Command `
    -Name "TypeScript (Types)" `
    -Command "npm run type-check" `
    -WorkingDirectory "frontend"

# Build
Run-Command `
    -Name "Build (Compilation)" `
    -Command "npm run build" `
    -WorkingDirectory "frontend"

Write-Section "FRONTEND - Tests"

# Vitest
Run-Command `
    -Name "Vitest (Tests unitaires)" `
    -Command "npm run test:unit" `
    -WorkingDirectory "frontend"

# ============================================
# RÉSUMÉ
# ============================================
Write-Section "RÉSUMÉ"

$totalTests = $results.Count
$successTests = ($results | Where-Object { $_.Success -eq $true }).Count
$failedTests = $totalTests - $successTests
$totalDuration = [math]::Round(($results | Measure-Object -Property Duration -Sum).Sum, 2)

Write-Host ""
Write-Host "Tests exécutés : $totalTests" -ForegroundColor White
Write-Host "Réussis        : $successTests" -ForegroundColor Green
Write-Host "Échoués        : $failedTests" -ForegroundColor $(if ($failedTests -gt 0) { "Red" } else { "Green" })
Write-Host "Durée totale   : ${totalDuration}s" -ForegroundColor White
Write-Host ""

# Tableau des résultats
$results | Format-Table -Property @{
    Label = "Test"
    Expression = { $_.Name }
}, @{
    Label = "Statut"
    Expression = { if ($_.Success) { "✓ OK" } else { "✗ ÉCHEC" } }
}, @{
    Label = "Durée (s)"
    Expression = { $_.Duration }
} -AutoSize

Write-Host ""

if ($errorsFound) {
    Write-Host @"
╔═══════════════════════════════════════════╗
║   DES ERREURS ONT ÉTÉ DÉTECTÉES           ║
║   Corrigez avant de push                  ║
╚═══════════════════════════════════════════╝
"@ -ForegroundColor Red
    
    Write-Host ""
    Write-Host "Pour corriger automatiquement :" -ForegroundColor Yellow
    Write-Host "  Backend  : cd backend && vendor\bin\php-cs-fixer fix && vendor\bin\phpcbf" -ForegroundColor Cyan
    Write-Host "  Frontend : cd frontend && npm run format && npm run lint" -ForegroundColor Cyan
    Write-Host ""
    
    exit 1
} else {
    Write-Host @"
╔═══════════════════════════════════════════╗
║   TOUS LES TESTS PASSENT                  ║
║   Prêt pour push !                        ║
╚═══════════════════════════════════════════╝
"@ -ForegroundColor Green
    exit 0
}