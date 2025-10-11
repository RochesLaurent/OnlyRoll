# check-ci.ps1
# Script de vérification complète CI/CD pour OnlyRoll
# Usage: .\check-ci.ps1

# Configuration
$ErrorActionPreference = "Continue"
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$logFile = "ci-check-results_$timestamp.log"
$errorsFound = $false
$results = @()

# Couleurs pour la console
function Write-ColorOutput($ForegroundColor) {
    $fc = $host.UI.RawUI.ForegroundColor
    $host.UI.RawUI.ForegroundColor = $ForegroundColor
    if ($args) {
        Write-Output $args
    }
    $host.UI.RawUI.ForegroundColor = $fc
}

function Write-Section($title) {
    Write-Host ""
    Write-ColorOutput Cyan "=========================================="
    Write-ColorOutput Cyan "  $title"
    Write-ColorOutput Cyan "=========================================="
    Write-Host ""
}

function Write-Step($message) {
    Write-ColorOutput Yellow "▶ $message"
}

function Write-Success($message) {
    Write-ColorOutput Green "✓ $message"
}

function Write-Error-Custom($message) {
    Write-ColorOutput Red "✗ $message"
}

function Run-Command {
    param(
        [string]$Name,
        [string]$Command,
        [string]$WorkingDirectory = ".",
        [string]$OutputFile = ""
    )
    
    Write-Step "$Name"
    
    $startTime = Get-Date
    $output = ""
    $success = $true
    
    try {
        Push-Location $WorkingDirectory
        
        if ($OutputFile) {
            Invoke-Expression $Command 2>&1 | Tee-Object -FilePath $OutputFile
            $exitCode = $LASTEXITCODE
        } else {
            $output = Invoke-Expression $Command 2>&1 | Out-String
            $exitCode = $LASTEXITCODE
        }
        
        if ($exitCode -ne 0) {
            $success = $false
            $script:errorsFound = $true
        }
    }
    catch {
        $success = $false
        $script:errorsFound = $true
        $output = $_.Exception.Message
    }
    finally {
        Pop-Location
    }
    
    $endTime = Get-Date
    $duration = ($endTime - $startTime).TotalSeconds
    
    $result = [PSCustomObject]@{
        Name = $Name
        Success = $success
        Duration = [math]::Round($duration, 2)
        OutputFile = $OutputFile
    }
    
    $script:results += $result
    
    if ($success) {
        Write-Success "$Name - OK (${duration}s)"
    } else {
        Write-Error-Custom "$Name - ÉCHEC (${duration}s)"
        if ($OutputFile) {
            Write-Host "  └─ Voir les détails dans: $OutputFile" -ForegroundColor Gray
        }
    }
    
    return $success
}

# Début du script
Write-ColorOutput Magenta @"
╔═══════════════════════════════════════════╗
║   OnlyRoll - Vérification CI/CD           ║
║   Tous les tests vont être exécutés       ║
╚═══════════════════════════════════════════╝
"@

Write-Host "Les résultats seront sauvegardés dans: $logFile" -ForegroundColor Gray
Write-Host ""

# Créer le fichier de log
"CI/CD Check Results - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" | Out-File $logFile
"=" * 80 | Out-File $logFile -Append
"" | Out-File $logFile -Append

# ============================================
# BACKEND CHECKS
# ============================================
Write-Section "BACKEND - PHP/Symfony"

# Vérifier que le dossier backend existe
if (-Not (Test-Path "backend")) {
    Write-Error-Custom "Le dossier 'backend' n'existe pas!"
    exit 1
}

# Installation des dépendances
Write-Step "Installation des dépendances Composer"
Push-Location backend
composer install --no-interaction --quiet
Pop-Location
Write-Success "Dépendances installées"

# PHPStan
Run-Command `
    -Name "PHPStan (Analyse statique)" `
    -Command "vendor/bin/phpstan analyse src --error-format=github --memory-limit=512M" `
    -WorkingDirectory "backend" `
    -OutputFile "phpstan_$timestamp.log"

# PHP CS Fixer
Run-Command `
    -Name "PHP-CS-Fixer (Style de code)" `
    -Command "vendor/bin/php-cs-fixer fix --dry-run --diff --verbose" `
    -WorkingDirectory "backend" `
    -OutputFile "php-cs-fixer_$timestamp.log"

# PHPCS
Run-Command `
    -Name "PHPCS PSR-12 (Standards)" `
    -Command "vendor/bin/phpcs --standard=PSR12 src" `
    -WorkingDirectory "backend" `
    -OutputFile "phpcs_$timestamp.log"

# PHPUnit Tests
Run-Command `
    -Name "PHPUnit (Tests unitaires)" `
    -Command "vendor/bin/phpunit --testdox" `
    -WorkingDirectory "backend" `
    -OutputFile "phpunit_$timestamp.log"

# Security Check
Run-Command `
    -Name "Composer Audit (Sécurité)" `
    -Command "composer audit" `
    -WorkingDirectory "backend" `
    -OutputFile "composer-audit_$timestamp.log"

# ============================================
# FRONTEND CHECKS
# ============================================
Write-Section "FRONTEND - Vue/TypeScript"

# Vérifier que le dossier frontend existe
if (-Not (Test-Path "frontend")) {
    Write-Error-Custom "Le dossier 'frontend' n'existe pas!"
    exit 1
}

# Installation des dépendances
Write-Step "Installation des dépendances NPM"
Push-Location frontend
npm ci --silent
Pop-Location
Write-Success "Dépendances installées"

# ESLint
Run-Command `
    -Name "ESLint (Linting JavaScript/Vue)" `
    -Command "npm run lint" `
    -WorkingDirectory "frontend" `
    -OutputFile "eslint_$timestamp.log"

# Prettier
Run-Command `
    -Name "Prettier (Format de code)" `
    -Command "npm run format:check" `
    -WorkingDirectory "frontend" `
    -OutputFile "prettier_$timestamp.log"

# TypeScript Type Check
Run-Command `
    -Name "TypeScript (Vérification des types)" `
    -Command "npm run type-check" `
    -WorkingDirectory "frontend" `
    -OutputFile "typescript_$timestamp.log"

# Build Check
Run-Command `
    -Name "Build (Compilation)" `
    -Command "npm run build" `
    -WorkingDirectory "frontend" `
    -OutputFile "build_$timestamp.log"

# Unit Tests
Run-Command `
    -Name "Vitest (Tests unitaires)" `
    -Command "npm run test:unit" `
    -WorkingDirectory "frontend" `
    -OutputFile "vitest_$timestamp.log"

# NPM Audit
Run-Command `
    -Name "NPM Audit (Sécurité)" `
    -Command "npm audit --audit-level=moderate" `
    -WorkingDirectory "frontend" `
    -OutputFile "npm-audit_$timestamp.log"

# ============================================
# RÉSUMÉ FINAL
# ============================================
Write-Section "RÉSUMÉ DES RÉSULTATS"

$totalTests = $results.Count
$successTests = ($results | Where-Object { $_.Success -eq $true }).Count
$failedTests = $totalTests - $successTests
$totalDuration = ($results | Measure-Object -Property Duration -Sum).Sum

Write-Host ""
Write-Host "Tests exécutés : $totalTests" -ForegroundColor White
Write-Host "Réussis        : $successTests" -ForegroundColor Green
Write-Host "Échoués        : $failedTests" -ForegroundColor $(if ($failedTests -gt 0) { "Red" } else { "Green" })
Write-Host "Durée totale   : $([math]::Round($totalDuration, 2))s" -ForegroundColor White
Write-Host ""

# Tableau des résultats
Write-Host "Détails par test:" -ForegroundColor Cyan
Write-Host ""
$results | Format-Table -Property @{
    Label = "Test"
    Expression = { $_.Name }
    Width = 40
}, @{
    Label = "Statut"
    Expression = { if ($_.Success) { "✓ OK" } else { "✗ ÉCHEC" } }
    Width = 10
}, @{
    Label = "Durée (s)"
    Expression = { $_.Duration }
    Width = 10
}, @{
    Label = "Fichier de log"
    Expression = { $_.OutputFile }
    Width = 30
} -AutoSize

# Sauvegarder le résumé dans le fichier de log
"" | Out-File $logFile -Append
"RÉSUMÉ" | Out-File $logFile -Append
"=" * 80 | Out-File $logFile -Append
"Tests exécutés : $totalTests" | Out-File $logFile -Append
"Réussis        : $successTests" | Out-File $logFile -Append
"Échoués        : $failedTests" | Out-File $logFile -Append
"Durée totale   : $([math]::Round($totalDuration, 2))s" | Out-File $logFile -Append
"" | Out-File $logFile -Append
$results | ForEach-Object {
    $status = if ($_.Success) { "OK" } else { "ÉCHEC" }
    "$($_.Name): $status ($($_.Duration)s)" | Out-File $logFile -Append
}

# Message final
Write-Host ""
if ($errorsFound) {
    Write-ColorOutput Red @"
╔═══════════════════════════════════════════╗
║   DES ERREURS ONT ÉTÉ DÉTECTÉES           ║
║   Corrigez les erreurs avant de push      ║
╚═══════════════════════════════════════════╝
"@
    Write-Host ""
    Write-Host "Consultez les fichiers de log pour plus de détails" -ForegroundColor Yellow
    exit 1
} else {
    Write-ColorOutput Green @"
╔═══════════════════════════════════════════╗
║   TOUS LES TESTS SONT PASSÉS              ║
║   Vous pouvez push en toute sécurité !    ║
╚═══════════════════════════════════════════╝
"@
    exit 0
}