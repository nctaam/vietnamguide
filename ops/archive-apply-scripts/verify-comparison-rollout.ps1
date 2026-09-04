param(
    [string]$RepoRootOverride = '',
    [switch]$Json,
    [switch]$EmitArtifact,
    [ValidateSet('approvals', 'runtime', 'fixtures', 'canary', 'six', 'portfolio')]
    [string]$Scope = 'portfolio',
    [datetime]$AsOfDate = [datetime]'2026-08-03T00:00:00Z'
)

Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

$requiredInputs = @(
    'ops/comparison-rollout/schema.json'
    'ops/comparison-rollout/organizations.json'
    'ops/comparison-rollout/sources.json'
    'ops/comparison-rollout/identities.json'
    'ops/comparison-rollout/manifest.json'
    'ops/comparison-rollout-validator.psm1'
)

$requiredModuleExports = @(
    'Read-VgJsonDocument'
    'ConvertTo-VgCanonicalJson'
    'Get-VgSha256Hex'
    'Test-VgSchemaDocument'
    'Resolve-VgComparisonPortfolio'
    'Test-VgComparisonPortfolio'
    'New-VgComparisonArtifact'
    'Compare-VgArtifactDeterminism'
)

$requiredPhpFunctions = @(
    'vg_comparison_canonical_json'
    'vg_comparison_sha256'
    'vg_comparison_validate_schema'
    'vg_comparison_compile_portfolio'
    'vg_comparison_probe_source'
    'vg_comparison_verify_approval'
    'vg_comparison_content_fingerprint'
    'vg_comparison_insert_modules'
    'vg_comparison_render_metrics'
)

$baselinePaths = @(
    'destinations/ho-chi-minh-city-travel-guide'
    'itineraries/10-days-in-vietnam'
    'itineraries/7-days-in-vietnam'
    'itineraries/14-days-in-vietnam'
    'itineraries/21-days-in-vietnam'
    'itineraries/hanoi-in-2-days'
    'compare/ha-long-bay-vs-lan-ha-bay'
    'plan/vietnam-evisa'
)

$targetMappings = @(
    [ordered]@{ path = 'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'; post_id = 279 }
    [ordered]@{ path = 'compare/da-nang-vs-hoi-an'; post_id = 209 }
    [ordered]@{ path = 'compare/hoi-an-vs-hue'; post_id = 227 }
    [ordered]@{ path = 'compare/mui-ne-vs-nha-trang'; post_id = 247 }
    [ordered]@{ path = 'compare/ninh-binh-day-trip-vs-overnight'; post_id = 326 }
    [ordered]@{ path = 'compare/north-central-south-vietnam'; post_id = 104 }
    [ordered]@{ path = 'compare/old-quarter-vs-french-quarter-vs-west-lake'; post_id = 301 }
    [ordered]@{ path = 'compare/phu-quoc-vs-nha-trang'; post_id = 241 }
    [ordered]@{ path = 'compare/trang-an-vs-tam-coc'; post_id = 336 }
)

$canaryActivation = @(
    'compare/old-quarter-vs-french-quarter-vs-west-lake'
    'compare/ninh-binh-day-trip-vs-overnight'
    'compare/north-central-south-vietnam'
)

$targetPaths = @($targetMappings | ForEach-Object { $_.path })
$canaryInventory = @($baselinePaths) + @($canaryActivation)
$fullInventory = @($baselinePaths) + @($targetPaths)
$sixInventory = @($targetPaths | Where-Object { $canaryActivation -cnotcontains $_ })
$permanentControls = @(
    'compare'
    'destinations/hanoi-travel-guide'
    'plan/sim-esim-vietnam'
    'plan/transport-within-vietnam'
)

$requiredDefinitions = @(
    'stableId'
    'sha256'
    'isoDate'
    'pagePath'
    'sourceClass'
    'claimGroup'
    'evidenceLabel'
    'freshnessTier'
    'ruleKind'
    'routeGroup'
    'changeReason'
    'canonicalDomain'
    'languageTag'
    'mediaType'
    'contextTag'
    'sourceFreshnessState'
    'publisherRecord'
    'claimDefinition'
    'notApplicableRecord'
    'axisDefinition'
    'outcomeDefinition'
    'ruleDefinition'
    'sourceMapping'
    'sourceAssignment'
    'comparisonPage'
    'fixtureManifest'
    'impactIndexEntry'
    'manifest'
    'sourceRegistry'
    'organizationRegistry'
    'identityRegistry'
    'resolvedBundleV1'
    'resolvedBundleV2'
    'activationSnapshot'
    'approvalArtifact'
    'backup'
    'ledgerEvent'
    'sourceProbe'
    'coverageRow'
    'impactReport'
)

$enumContracts = [ordered]@{
    sourceClass = @('national_official', 'local_official', 'operational', 'conditions_heritage', 'independent_corroboration')
    claimGroup = @('access_transport', 'timing_duration', 'cost_booking', 'season_current_conditions', 'experience_fit', 'constraints_safety')
    evidenceLabel = @('primary', 'corroborating', 'live_check_required')
    freshnessTier = @('live', 'current', 'stable')
    ruleKind = @('hard_constraint', 'preference', 'tie_breaker')
    routeGroup = @('deepen_place', 'build_route', 'check_practical')
    changeReason = @('source_refresh', 'operational_change', 'decision_change', 'route_change', 'correction')
}

$publicEnumContracts = [ordered]@{
    sourceClass = $enumContracts.sourceClass
    claimGroup = $enumContracts.claimGroup
    evidenceLabel = $enumContracts.evidenceLabel
    freshnessTier = $enumContracts.freshnessTier
    mediaType = @('html', 'pdf', 'json')
    sourceFreshnessState = @('current', 'stale', 'live_check_required')
    expectedTitleMode = @('exact', 'pattern')
    ruleKind = $enumContracts.ruleKind
    routeGroup = $enumContracts.routeGroup
    changeReason = $enumContracts.changeReason
    activationStage = @('baseline', 'canary', 'full')
    approvalRole = @('author', 'reviewer')
    terminalOutcome = @('option', 'no_clear_winner', 'combine_or_sequence')
    archetype = @('competing_day_trips', 'city_or_heritage_base', 'coast_and_island', 'time_allocation', 'neighborhood', 'macro_region', 'attraction_and_landscape')
    requiredFeature = @('decision_frame', 'evidence_labels', 'source_checked_dates', 'related_routes', 'update_log', 'editorial_byline')
    sourceProbeOutcome = @('available', 'changed', 'unavailable', 'manual_review')
    impactReportOutcome = @('pass', 'fail', 'manual_review')
    schemaVersion = @('v1', 'v2')
}

function Get-RepoRoot {
    if ([string]::IsNullOrWhiteSpace($RepoRootOverride)) {
        return (Split-Path -Parent $PSScriptRoot)
    }

    return [System.IO.Path]::GetFullPath($RepoRootOverride)
}

function Get-Property {
    param(
        [Parameter(Mandatory = $true)]$Object,
        [Parameter(Mandatory = $true)][string]$Name
    )

    if ($Object -is [System.Collections.IDictionary]) {
        foreach ($key in $Object.Keys) {
            if ([string]$key -ceq $Name) {
                $value = $Object[$key]
                if (Test-IsArray $value) {
                    return ,$value
                }
                return $value
            }
        }
        return $null
    }

    foreach ($property in $Object.PSObject.Properties) {
        if ($property.Name -ceq $Name) {
            if (Test-IsArray $property.Value) {
                return ,$property.Value
            }
            return $property.Value
        }
    }
    return $null
}

function Test-HasProperty {
    param(
        [Parameter(Mandatory = $true)]$Object,
        [Parameter(Mandatory = $true)][string]$Name
    )

    if ($Object -is [System.Collections.IDictionary]) {
        foreach ($key in $Object.Keys) {
            if ([string]$key -ceq $Name) {
                return $true
            }
        }
        return $false
    }
    foreach ($property in $Object.PSObject.Properties) {
        if ($property.Name -ceq $Name) {
            return $true
        }
    }
    return $false
}

function Test-IsArray {
    param($Value)
    return (($Value -is [System.Collections.IList]) -and -not ($Value -is [string]))
}

function Test-IsObject {
    param($Value)
    if ($null -eq $Value -or (Test-IsArray $Value) -or $Value -is [string] -or $Value -is [ValueType]) {
        return $false
    }
    return (($Value -is [System.Collections.IDictionary]) -or ($Value -is [pscustomobject]))
}

function Get-ObjectPropertyNames {
    param($Value)
    if ($Value -is [System.Collections.IDictionary]) {
        return @($Value.Keys | ForEach-Object { [string]$_ })
    }
    return @($Value.PSObject.Properties.Name)
}

function Test-IsNumericClrValue {
    param($Value)
    return ($Value -is [sbyte] -or $Value -is [byte] -or
        $Value -is [int16] -or $Value -is [uint16] -or
        $Value -is [int32] -or $Value -is [uint32] -or
        $Value -is [int64] -or $Value -is [uint64] -or
        $Value -is [single] -or $Value -is [double] -or
        $Value -is [decimal] -or $Value -is [System.Numerics.BigInteger])
}

function Test-IsJsonNumber {
    param($Value)
    if (-not (Test-IsNumericClrValue $Value)) {
        return $false
    }
    if ($Value -is [double]) {
        return (-not [double]::IsNaN($Value) -and -not [double]::IsInfinity($Value))
    }
    if ($Value -is [single]) {
        return (-not [single]::IsNaN($Value) -and -not [single]::IsInfinity($Value))
    }
    return $true
}

function Test-IsMathematicalInteger {
    param($Value)
    if (-not (Test-IsJsonNumber $Value)) {
        return $false
    }
    if ($Value -is [sbyte] -or $Value -is [byte] -or
        $Value -is [int16] -or $Value -is [uint16] -or
        $Value -is [int32] -or $Value -is [uint32] -or
        $Value -is [int64] -or $Value -is [uint64] -or
        $Value -is [System.Numerics.BigInteger]) {
        return $true
    }
    if ($Value -is [decimal]) {
        return ([decimal]::Truncate($Value) -eq $Value)
    }
    return ([Math]::Truncate([double]$Value) -eq [double]$Value)
}

function Get-JsonNumberInvariantText {
    param([Parameter(Mandatory = $true)]$Value)
    if (-not (Test-IsJsonNumber $Value)) {
        return $null
    }
    $invariantCulture = [System.Globalization.CultureInfo]::InvariantCulture
    if ($Value -is [decimal]) {
        return $Value.ToString('G29', $invariantCulture)
    }
    if ($Value -is [double] -or $Value -is [single]) {
        return $Value.ToString('R', $invariantCulture)
    }
    return ([System.IFormattable]$Value).ToString('D', $invariantCulture)
}

function ConvertTo-JsonNumberRational {
    param(
        [Parameter(Mandatory = $true)]$Value,
        [Parameter(Mandatory = $true)][ref]$Numerator,
        [Parameter(Mandatory = $true)][ref]$Denominator
    )

    $numberText = Get-JsonNumberInvariantText $Value
    if ([string]::IsNullOrWhiteSpace($numberText)) {
        return $false
    }
    $match = [regex]::Match($numberText, '^(?<sign>[+-]?)(?<integer>[0-9]+)(?:\.(?<fraction>[0-9]+))?(?:[eE](?<exponent>[+-]?[0-9]+))?$')
    if (-not $match.Success) {
        return $false
    }
    $fractionText = if ($match.Groups['fraction'].Success) { $match.Groups['fraction'].Value } else { '' }
    $digits = $match.Groups['integer'].Value + $fractionText
    try {
        $parsedNumerator = [System.Numerics.BigInteger]::Parse(
            $digits,
            [System.Globalization.NumberStyles]::None,
            [System.Globalization.CultureInfo]::InvariantCulture
        )
    } catch {
        return $false
    }
    if ($match.Groups['sign'].Value -ceq '-') {
        $parsedNumerator = -$parsedNumerator
    }
    $exponent = 0
    if ($match.Groups['exponent'].Success -and -not [int]::TryParse(
        $match.Groups['exponent'].Value,
        [System.Globalization.NumberStyles]::AllowLeadingSign,
        [System.Globalization.CultureInfo]::InvariantCulture,
        [ref]$exponent
    )) {
        return $false
    }
    $scale = [int64]$fractionText.Length - [int64]$exponent
    if ([Math]::Abs($scale) -gt 10000) {
        return $false
    }
    if ($scale -ge 0) {
        $parsedDenominator = [System.Numerics.BigInteger]::Pow(10, [int]$scale)
    } else {
        $parsedNumerator *= [System.Numerics.BigInteger]::Pow(10, [int](-$scale))
        $parsedDenominator = [System.Numerics.BigInteger]::One
    }
    if ($parsedNumerator.IsZero) {
        $parsedDenominator = [System.Numerics.BigInteger]::One
    }
    $Numerator.Value = $parsedNumerator
    $Denominator.Value = $parsedDenominator
    return $true
}

function Test-JsonValueEqual {
    param($Left, $Right)
    if ($null -eq $Left -or $null -eq $Right) {
        return ($null -eq $Left -and $null -eq $Right)
    }
    if ($Left -is [string] -and $Right -is [string]) {
        return ($Left -ceq $Right)
    }
    if ($Left -is [bool] -or $Right -is [bool]) {
        return ($Left -is [bool] -and $Right -is [bool] -and $Left -eq $Right)
    }
    if ((Test-IsNumericClrValue $Left) -or (Test-IsNumericClrValue $Right)) {
        if (-not (Test-IsJsonNumber $Left) -or -not (Test-IsJsonNumber $Right)) {
            return $false
        }
        $leftNumerator = [System.Numerics.BigInteger]::Zero
        $leftDenominator = [System.Numerics.BigInteger]::One
        $rightNumerator = [System.Numerics.BigInteger]::Zero
        $rightDenominator = [System.Numerics.BigInteger]::One
        if (-not (ConvertTo-JsonNumberRational $Left ([ref]$leftNumerator) ([ref]$leftDenominator)) -or
            -not (ConvertTo-JsonNumberRational $Right ([ref]$rightNumerator) ([ref]$rightDenominator))) {
            return $false
        }
        return ($leftNumerator * $rightDenominator -eq $rightNumerator * $leftDenominator)
    }
    if ((Test-IsArray $Left) -or (Test-IsArray $Right)) {
        if (-not (Test-IsArray $Left) -or -not (Test-IsArray $Right) -or $Left.Count -ne $Right.Count) {
            return $false
        }
        for ($index = 0; $index -lt $Left.Count; $index++) {
            if (-not (Test-JsonValueEqual $Left[$index] $Right[$index])) {
                return $false
            }
        }
        return $true
    }
    if ((Test-IsObject $Left) -or (Test-IsObject $Right)) {
        if (-not (Test-IsObject $Left) -or -not (Test-IsObject $Right)) {
            return $false
        }
        $leftNames = @(Get-ObjectPropertyNames $Left)
        $rightNames = @(Get-ObjectPropertyNames $Right)
        if ($leftNames.Count -ne $rightNames.Count) {
            return $false
        }
        foreach ($propertyName in $leftNames) {
            if (-not (Test-HasProperty $Right $propertyName) -or
                -not (Test-JsonValueEqual (Get-Property $Left $propertyName) (Get-Property $Right $propertyName))) {
                return $false
            }
        }
        return $true
    }
    return ($Left.GetType() -eq $Right.GetType() -and $Left -eq $Right)
}

function Get-JsonStringCodePointLength {
    param(
        [Parameter(Mandatory = $true)][string]$Value,
        [Parameter(Mandatory = $true)][ref]$Length
    )

    $codePointCount = 0
    for ($index = 0; $index -lt $Value.Length; $index++) {
        $character = $Value[$index]
        if ([char]::IsHighSurrogate($character)) {
            if ($index + 1 -ge $Value.Length -or -not [char]::IsLowSurrogate($Value[$index + 1])) {
                return $false
            }
            $index++
        } elseif ([char]::IsLowSurrogate($character)) {
            return $false
        }
        $codePointCount++
    }
    $Length.Value = $codePointCount
    return $true
}

function Resolve-SchemaReference {
    param(
        [Parameter(Mandatory = $true)][string]$Reference,
        [Parameter(Mandatory = $true)]$RootSchema
    )

    if (-not $Reference.StartsWith('#/$defs/', [System.StringComparison]::Ordinal)) {
        throw "unsupported schema reference '$Reference'"
    }
    $definitionName = $Reference.Substring('#/$defs/'.Length).Replace('~1', '/').Replace('~0', '~')
    $definitions = Get-Property $RootSchema '$defs'
    if (-not (Test-HasProperty $definitions $definitionName)) {
        throw "missing schema definition '$definitionName'"
    }
    return (Get-Property $definitions $definitionName)
}

function Test-SchemaNode {
    param(
        $Value,
        [Parameter(Mandatory = $true)]$SchemaNode,
        [Parameter(Mandatory = $true)]$RootSchema,
        [Parameter(Mandatory = $true)][string]$Path
    )

    $nodeErrors = @()
    if ($SchemaNode -is [bool]) {
        if (-not $SchemaNode) {
            $nodeErrors += "$Path is forbidden"
        }
        return @($nodeErrors)
    }

    if (Test-HasProperty $SchemaNode '$ref') {
        try {
            $referencedSchema = Resolve-SchemaReference (Get-Property $SchemaNode '$ref') $RootSchema
            $nodeErrors += @(Test-SchemaNode $Value $referencedSchema $RootSchema $Path)
        } catch {
            $nodeErrors += "$Path has invalid schema reference"
        }
    }

    if (Test-HasProperty $SchemaNode 'allOf') {
        foreach ($childSchema in (Get-Property $SchemaNode 'allOf')) {
            $nodeErrors += @(Test-SchemaNode $Value $childSchema $RootSchema $Path)
        }
    }

    if (Test-HasProperty $SchemaNode 'anyOf') {
        $anyOfMatched = $false
        foreach ($childSchema in (Get-Property $SchemaNode 'anyOf')) {
            $childMatched = $false
            try {
                $childMatched = (@(Test-SchemaNode $Value $childSchema $RootSchema $Path).Count -eq 0)
            } catch {
                $childMatched = $false
            }
            if ($childMatched) {
                $anyOfMatched = $true
                break
            }
        }
        if (-not $anyOfMatched) {
            $nodeErrors += "$Path does not satisfy anyOf"
        }
    }

    if (Test-HasProperty $SchemaNode 'type') {
        $expectedType = [string](Get-Property $SchemaNode 'type')
        $typeMatches = switch ($expectedType) {
            'object' { Test-IsObject $Value; break }
            'array' { Test-IsArray $Value; break }
            'string' { $Value -is [string]; break }
            'integer' { Test-IsMathematicalInteger $Value; break }
            'number' { Test-IsJsonNumber $Value; break }
            'boolean' { $Value -is [bool]; break }
            'null' { $null -eq $Value; break }
            default { $false }
        }
        if (-not $typeMatches) {
            $nodeErrors += "$Path must be $expectedType"
            return @($nodeErrors)
        }
    }

    if (Test-HasProperty $SchemaNode 'const') {
        if (-not (Test-JsonValueEqual $Value (Get-Property $SchemaNode 'const'))) {
            $nodeErrors += "$Path does not match const"
        }
    }

    if (Test-HasProperty $SchemaNode 'enum') {
        $enumMatch = $false
        foreach ($allowedValue in (Get-Property $SchemaNode 'enum')) {
            if (Test-JsonValueEqual $Value $allowedValue) {
                $enumMatch = $true
                break
            }
        }
        if (-not $enumMatch) {
            $nodeErrors += "$Path is not an allowed enum value"
        }
    }

    if ($Value -is [string]) {
        $codePointLength = 0
        $hasValidSurrogates = Get-JsonStringCodePointLength $Value ([ref]$codePointLength)
        if (-not $hasValidSurrogates) {
            $nodeErrors += "$Path contains an unpaired UTF-16 surrogate"
        } else {
            if ((Test-HasProperty $SchemaNode 'minLength') -and $codePointLength -lt [int](Get-Property $SchemaNode 'minLength')) {
                $nodeErrors += "$Path is shorter than minLength"
            }
            if ((Test-HasProperty $SchemaNode 'maxLength') -and $codePointLength -gt [int](Get-Property $SchemaNode 'maxLength')) {
                $nodeErrors += "$Path is longer than maxLength"
            }
        }
        if (Test-HasProperty $SchemaNode 'pattern') {
            try {
                if (-not [regex]::IsMatch($Value, [string](Get-Property $SchemaNode 'pattern'))) {
                    $nodeErrors += "$Path does not match pattern"
                }
            } catch {
                $nodeErrors += "$Path uses an invalid schema pattern"
            }
        }
        if ((Test-HasProperty $SchemaNode 'format') -and (Get-Property $SchemaNode 'format') -ceq 'date') {
            $parsedDate = [datetime]::MinValue
            $isValidDate = [datetime]::TryParseExact(
                $Value,
                'yyyy-MM-dd',
                [System.Globalization.CultureInfo]::InvariantCulture,
                [System.Globalization.DateTimeStyles]::None,
                [ref]$parsedDate
            )
            if (-not $isValidDate -or $parsedDate.ToString('yyyy-MM-dd', [System.Globalization.CultureInfo]::InvariantCulture) -cne $Value) {
                $nodeErrors += "$Path is not a valid calendar date"
            }
        }
    }

    if (Test-IsJsonNumber $Value) {
        if ((Test-HasProperty $SchemaNode 'minimum') -and $Value -lt (Get-Property $SchemaNode 'minimum')) {
            $nodeErrors += "$Path is below minimum"
        }
        if ((Test-HasProperty $SchemaNode 'maximum') -and $Value -gt (Get-Property $SchemaNode 'maximum')) {
            $nodeErrors += "$Path is above maximum"
        }
    }

    if (Test-IsObject $Value) {
        if (Test-HasProperty $SchemaNode 'required') {
            foreach ($requiredName in (Get-Property $SchemaNode 'required')) {
                if (-not (Test-HasProperty $Value ([string]$requiredName))) {
                    $nodeErrors += "$Path.$requiredName is required"
                }
            }
        }

        $propertySchemas = Get-Property $SchemaNode 'properties'
        if ($null -ne $propertySchemas) {
            foreach ($propertyName in @(Get-ObjectPropertyNames $Value)) {
                if (Test-HasProperty $propertySchemas $propertyName) {
                    $nodeErrors += @(Test-SchemaNode (Get-Property $Value $propertyName) (Get-Property $propertySchemas $propertyName) $RootSchema "$Path.$propertyName")
                } elseif ((Test-HasProperty $SchemaNode 'additionalProperties') -and -not [bool](Get-Property $SchemaNode 'additionalProperties')) {
                    $nodeErrors += "$Path.$propertyName is not allowed"
                }
            }
        }
    }

    if (Test-IsArray $Value) {
        $arrayValue = @($Value)
        if ((Test-HasProperty $SchemaNode 'minItems') -and $arrayValue.Count -lt [int](Get-Property $SchemaNode 'minItems')) {
            $nodeErrors += "$Path has fewer than minItems"
        }
        if ((Test-HasProperty $SchemaNode 'maxItems') -and $arrayValue.Count -gt [int](Get-Property $SchemaNode 'maxItems')) {
            $nodeErrors += "$Path has more than maxItems"
        }
        if ((Test-HasProperty $SchemaNode 'uniqueItems') -and [bool](Get-Property $SchemaNode 'uniqueItems')) {
            for ($index = 0; $index -lt $arrayValue.Count; $index++) {
                for ($earlierIndex = 0; $earlierIndex -lt $index; $earlierIndex++) {
                    if (Test-JsonValueEqual $arrayValue[$index] $arrayValue[$earlierIndex]) {
                        $nodeErrors += "$Path[$index] duplicates an earlier item"
                        break
                    }
                }
            }
        }

        $prefixCount = 0
        if (Test-HasProperty $SchemaNode 'prefixItems') {
            $prefixSchemas = Get-Property $SchemaNode 'prefixItems'
            $prefixCount = $prefixSchemas.Count
            for ($index = 0; $index -lt [Math]::Min($arrayValue.Count, $prefixCount); $index++) {
                $nodeErrors += @(Test-SchemaNode $arrayValue[$index] $prefixSchemas[$index] $RootSchema "$Path[$index]")
            }
        }
        if (Test-HasProperty $SchemaNode 'items') {
            $itemsSchema = Get-Property $SchemaNode 'items'
            for ($index = $prefixCount; $index -lt $arrayValue.Count; $index++) {
                $nodeErrors += @(Test-SchemaNode $arrayValue[$index] $itemsSchema $RootSchema "$Path[$index]")
            }
        }
        if (Test-HasProperty $SchemaNode 'contains') {
            $matchingItems = 0
            foreach ($item in $arrayValue) {
                if (@(Test-SchemaNode $item (Get-Property $SchemaNode 'contains') $RootSchema "$Path[]").Count -eq 0) {
                    $matchingItems++
                }
            }
            $minimumMatches = if (Test-HasProperty $SchemaNode 'minContains') { [int](Get-Property $SchemaNode 'minContains') } else { 1 }
            if ($matchingItems -lt $minimumMatches) {
                $nodeErrors += "$Path does not satisfy contains"
            }
        }
    }

    return @($nodeErrors)
}

function Copy-JsonLikeValue {
    param(
        [Parameter(Mandatory = $true)][AllowNull()]$Value,
        [Parameter(Mandatory = $true)][ref]$Result
    )

    if ($null -eq $Value) {
        $Result.Value = $null
        return
    }
    if (Test-IsArray $Value) {
        $arrayCopy = New-Object 'object[]' $Value.Count
        for ($index = 0; $index -lt $Value.Count; $index++) {
            $itemCopy = $null
            Copy-JsonLikeValue $Value[$index] ([ref]$itemCopy)
            $arrayCopy[$index] = $itemCopy
        }
        $Result.Value = $arrayCopy
        return
    }
    if (Test-IsObject $Value) {
        $objectCopy = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
        foreach ($propertyName in @(Get-ObjectPropertyNames $Value)) {
            $propertyCopy = $null
            Copy-JsonLikeValue (Get-Property $Value $propertyName) ([ref]$propertyCopy)
            $objectCopy.Add($propertyName, $propertyCopy)
        }
        $Result.Value = $objectCopy
        return
    }
    $Result.Value = $Value
}

function ConvertTo-FixtureObject {
    param([Parameter(Mandatory = $true)][AllowNull()]$Value)
    $copy = $null
    Copy-JsonLikeValue $Value ([ref]$copy)
    return ,$copy
}

function Copy-FixtureObject {
    param([Parameter(Mandatory = $true)][AllowNull()]$Value)
    return (ConvertTo-FixtureObject $Value)
}

function Get-Definition {
    param(
        [Parameter(Mandatory = $true)]$Schema,
        [Parameter(Mandatory = $true)][string]$Name
    )
    return (Get-Property (Get-Property $Schema '$defs') $Name)
}

function Get-SerializedUtf8Size {
    param([Parameter(Mandatory = $true)]$Value)
    $serialized = ConvertTo-Json $Value -Depth 100 -Compress
    return [System.Text.Encoding]::UTF8.GetByteCount($serialized)
}

function Test-OrdinalSequence {
    param(
        [Parameter(Mandatory = $true)]$Actual,
        [Parameter(Mandatory = $true)]$Expected
    )

    $actualItems = @($Actual)
    $expectedItems = @($Expected)
    if ($actualItems.Count -ne $expectedItems.Count) {
        return $false
    }
    for ($index = 0; $index -lt $expectedItems.Count; $index++) {
        if ([string]$actualItems[$index] -cne [string]$expectedItems[$index]) {
            return $false
        }
    }
    return $true
}

function Test-ManifestOrdinalContracts {
    param([Parameter(Mandatory = $true)]$Manifest)

    $contractErrors = @()
    $inventoryContracts = [ordered]@{
        baseline = $baselinePaths
        canary = $canaryInventory
        full = $fullInventory
        six = $sixInventory
        permanent_controls = $permanentControls
        canary_activation = $canaryActivation
    }
    foreach ($inventoryName in $inventoryContracts.Keys) {
        if (-not (Test-OrdinalSequence (Get-Property $Manifest $inventoryName) $inventoryContracts[$inventoryName])) {
            $contractErrors += "$inventoryName does not match the exact ordinal contract"
        }
    }

    $actualMappings = Get-Property $Manifest 'target_mappings'
    if ($actualMappings.Count -ne $targetMappings.Count) {
        $contractErrors += 'target_mappings does not match the exact count contract'
    } else {
        for ($index = 0; $index -lt $targetMappings.Count; $index++) {
            if ((Get-Property $actualMappings[$index] 'path') -cne $targetMappings[$index].path -or
                [int](Get-Property $actualMappings[$index] 'post_id') -ne [int]$targetMappings[$index].post_id) {
                $contractErrors += 'target_mappings does not match the exact ordinal path/post contract'
                break
            }
        }
    }
    return @($contractErrors)
}

function Test-WithinBundleSizeLimit {
    param([Parameter(Mandatory = $true)]$Value)
    return ((Get-SerializedUtf8Size $Value) -le 65536)
}

function Get-ResolvedBundleV2ContractErrors {
    param(
        [Parameter(Mandatory = $true)]$Value,
        [Parameter(Mandatory = $true)]$Schema,
        [Parameter(Mandatory = $true)][string]$Path
    )

    $contractErrors = @()
    $bundleSchema = Get-Definition $Schema 'resolvedBundleV2'
    foreach ($schemaError in @(Test-SchemaNode $Value $bundleSchema $Schema '$')) {
        $contractErrors += "E_SCHEMA ${Path}: $schemaError"
    }
    $serializedSize = Get-SerializedUtf8Size $Value
    if ($serializedSize -gt 65536) {
        $contractErrors += "E_SIZE ${Path}: serialized resolvedBundleV2 is $serializedSize UTF-8 bytes; maximum is 65536"
    }
    return @($contractErrors)
}

function New-PositiveBundle {
    $optionA = [ordered]@{ option_id = 'option-a'; label = 'Option A'; summary = 'Best for direct access and a compact visit.' }
    $optionB = [ordered]@{ option_id = 'option-b'; label = 'Option B'; summary = 'Best for a slower pace and broader context.' }
    $rule = [ordered]@{
        rule_id = 'rule-primary'
        order = 1
        kind = 'hard_constraint'
        condition = 'Choose the route that fits the available transfer window.'
        outcome = 'Option A when the day is tightly constrained.'
        context_tags = @('short-time')
        option_ids = @('option-a')
        claim_ids = @('claim-access')
        axis_ids = @('axis-1')
        outcome_id = 'outcome-a'
    }
    $assessmentA = [ordered]@{ option_id = 'option-a'; outcome = 'Faster access with less transfer overhead.' }
    $assessmentB = [ordered]@{ option_id = 'option-b'; outcome = 'More context when an overnight stop is possible.' }
    $axes = @()
    foreach ($axisNumber in 1..3) {
        $axes += [ordered]@{
            axis_id = "axis-$axisNumber"
            label = "Decision axis $axisNumber"
            explanation = 'Compare the practical trade-off using checked evidence.'
            claim_ids = @('claim-access')
            option_ids = @('option-a', 'option-b')
            decisive = ($axisNumber -eq 1)
            assessments = @($assessmentA, $assessmentB)
            source_ids = @("source-$axisNumber")
        }
    }
    $lenses = @()
    foreach ($lensNumber in 1..3) {
        $lenses += [ordered]@{
            lens_id = "lens-$lensNumber"
            traveler = "Traveler profile $lensNumber"
            context_tags = @('short-time')
            outcome_id = 'outcome-a'
            outcome = 'Use the ordered rule path and choose the option that fits the stated constraint.'
            trade_off = 'A faster transfer provides less time for a deeper visit.'
            rule_path = @($rule)
        }
    }
    $sources = @()
    $claimGroups = @('access_transport', 'timing_duration', 'cost_booking', 'season_current_conditions', 'experience_fit', 'constraints_safety')
    foreach ($sourceNumber in 1..6) {
        $sources += [ordered]@{
            source_id = "source-$sourceNumber"
            publisher_id = "publisher-$sourceNumber"
            publisher_name = "Publisher $sourceNumber"
            organization_id = "organization-$sourceNumber"
            canonical_domain = "source-$sourceNumber.example.vn"
            source_class = if ($sourceNumber -eq 1) { 'national_official' } elseif ($sourceNumber -le 3) { 'local_official' } elseif ($sourceNumber -le 5) { 'operational' } else { 'independent_corroboration' }
            title = "Checked source $sourceNumber"
            url = "https://source-$sourceNumber.example.vn/guidance"
            evidence_label = if ($sourceNumber -eq 1) { 'primary' } elseif ($sourceNumber -eq 6) { 'live_check_required' } else { 'corroborating' }
            checked_on = '2026-08-03'
            freshness_tier = if ($sourceNumber -eq 6) { 'live' } else { 'current' }
            freshness_state = if ($sourceNumber -eq 6) { 'live_check_required' } else { 'current' }
            localities = @('fixture-locality')
            language = if ($sourceNumber -eq 2) { 'vi' } else { 'en' }
            media_type = 'html'
            claim_groups = @($claimGroups[$sourceNumber - 1])
            mappings = @([ordered]@{ claim_id = 'claim-access'; option_id = if (($sourceNumber % 2) -eq 0) { 'option-b' } else { 'option-a' }; axis_id = 'axis-1'; outcome_id = 'outcome-a' })
        }
    }
    $routes = @(
        [ordered]@{ path = 'destinations/hanoi-travel-guide'; label = 'Hanoi guide'; route_group = 'deepen_place' }
        [ordered]@{ path = 'destinations/ho-chi-minh-city-travel-guide'; label = 'Ho Chi Minh City guide'; route_group = 'deepen_place' }
        [ordered]@{ path = 'itineraries/7-days-in-vietnam'; label = 'Seven day route'; route_group = 'build_route' }
        [ordered]@{ path = 'itineraries/10-days-in-vietnam'; label = 'Ten day route'; route_group = 'build_route' }
        [ordered]@{ path = 'plan/vietnam-evisa'; label = 'Vietnam eVisa'; route_group = 'check_practical' }
        [ordered]@{ path = 'plan/transport-within-vietnam'; label = 'Vietnam transport'; route_group = 'check_practical' }
    )

    return (ConvertTo-FixtureObject ([ordered]@{
        schema_version = 'v2'
        bundle_hash = ('a' * 64)
        manifest_version = 'manifest-v2'
        source_registry_version = 'sources-v2'
        organization_registry_version = 'organizations-v2'
        activation_artifact_version = 'activation-v2'
        path = 'compare/cu-chi-tunnels-vs-mekong-delta-day-trip'
        post_id = 279
        editorial = [ordered]@{
            reviewed_guide = $true
            written_by_identity_id = 'author-one'
            reviewed_by_identity_id = 'reviewer-one'
            last_meaningful_update = '2026-08-03'
            update_summary = 'Reviewed access, timing, booking, and current operating constraints.'
            change_reason = 'source_refresh'
            affected_public_labels = @('Decision frame', 'Sources')
        }
        archetype = 'competing_day_trips'
        localities = @('ho-chi-minh-city', 'mekong-delta')
        options = @($optionA, $optionB)
        primary_decision = [ordered]@{
            outcome_id = 'outcome-a'
            outcome = 'option'
            winner_option_id = 'option-a'
            summary = 'Choose Option A for a tight day; choose Option B when depth matters more than transfer time.'
            rules = @($rule)
        }
        field_note = 'Operating details can change, so confirm the final departure time before travel.'
        evidence_moat = @('The decision combines official access facts with operational and independent corroboration.')
        axes = $axes
        traveler_lenses = $lenses
        sources = $sources
        related_routes = $routes
        update_log = @([ordered]@{
            date = '2026-08-03'
            summary = 'Refreshed source checks and clarified the transfer-time decision.'
            change_reason = 'source_refresh'
            affected_public_labels = @('Decision frame', 'Sources')
        })
        module_requirements = [ordered]@{
            validator_version = 'validator-v2'
            renderer_version = 'renderer-v2'
            cache_key_fields = @('path', 'bundle_hash', 'schema_version', 'source_registry_version', 'activation_artifact_version')
            required_features = @('decision_frame', 'evidence_labels', 'source_checked_dates')
        }
        provenance_hash = ('b' * 64)
        render_contract = [ordered]@{
            decision_heading = 'Decision guide'
            source_heading = 'Sources checked'
            route_heading = 'Continue planning'
            update_heading = 'What changed'
            page_language = 'vi'
            max_visible_characters = 1800
        }
    }))
}

function New-SizedBundleFixture {
    param([Parameter(Mandatory = $true)][int]$TargetBytes)

    $bundle = New-PositiveBundle
    $options = @($bundle['options'])
    foreach ($optionNumber in 3..4) {
        $options += [ordered]@{
            option_id = "option-$optionNumber"
            label = "Option $optionNumber"
            summary = "Alternative option $optionNumber for the comparison."
        }
    }
    $bundle['options'] = ConvertTo-FixtureObject $options

    $rules = @($bundle['primary_decision']['rules'])
    foreach ($ruleNumber in 2..8) {
        $rules += [ordered]@{
            rule_id = "rule-$ruleNumber"
            order = $ruleNumber
            kind = if ($ruleNumber -eq 8) { 'tie_breaker' } else { 'preference' }
            condition = "Qualitative condition $ruleNumber for this decision path."
            outcome = "Qualitative outcome $ruleNumber without scoring or hidden weights."
            context_tags = @('short-time')
            option_ids = @('option-a')
            claim_ids = @('claim-access')
            axis_ids = @('axis-1')
            outcome_id = 'outcome-a'
        }
    }
    $bundle['primary_decision']['rules'] = ConvertTo-FixtureObject $rules

    $bundle['evidence_moat'] = ConvertTo-FixtureObject @(
        'Official access evidence.'
        'Operational timing evidence.'
        'Independent corroboration evidence.'
        'Current-condition evidence.'
    )

    $axes = @()
    foreach ($axisNumber in 1..6) {
        $assessments = @()
        foreach ($optionNumber in 1..4) {
            $optionId = if ($optionNumber -le 2) { "option-$([char](96 + $optionNumber))" } else { "option-$optionNumber" }
            $assessments += [ordered]@{ option_id = $optionId; outcome = "Axis $axisNumber outcome for option $optionNumber." }
        }
        $axes += [ordered]@{
            axis_id = "axis-$axisNumber"
            label = "Decision axis $axisNumber"
            explanation = "Checked explanation for decision axis $axisNumber."
            claim_ids = @('claim-access')
            option_ids = @('option-a', 'option-b', 'option-3', 'option-4')
            decisive = ($axisNumber -eq 1)
            assessments = $assessments
            source_ids = @("source-$axisNumber")
        }
    }
    $bundle['axes'] = ConvertTo-FixtureObject $axes

    $lenses = @()
    foreach ($lensNumber in 1..5) {
        $lensRules = @()
        foreach ($ruleNumber in 1..5) {
            $lensRules += [ordered]@{
                rule_id = "lens-$lensNumber-rule-$ruleNumber"
                order = $ruleNumber
                kind = if ($ruleNumber -eq 1) { 'hard_constraint' } elseif ($ruleNumber -eq 5) { 'tie_breaker' } else { 'preference' }
                condition = "Lens $lensNumber condition $ruleNumber."
                outcome = "Lens $lensNumber qualitative outcome $ruleNumber."
                context_tags = @('short-time')
                option_ids = @('option-a')
                claim_ids = @('claim-access')
                axis_ids = @('axis-1')
                outcome_id = 'outcome-a'
            }
        }
        $lenses += [ordered]@{
            lens_id = "lens-$lensNumber"
            traveler = "Traveler profile $lensNumber"
            context_tags = @('short-time')
            outcome_id = 'outcome-a'
            outcome = "Outcome for traveler profile $lensNumber."
            trade_off = "Trade-off for traveler profile $lensNumber."
            rule_path = $lensRules
        }
    }
    $bundle['traveler_lenses'] = ConvertTo-FixtureObject $lenses

    $sources = @($bundle['sources'])
    foreach ($sourceNumber in 7..10) {
        $sources += [ordered]@{
            source_id = "source-$sourceNumber"
            publisher_id = "publisher-$sourceNumber"
            publisher_name = "Publisher $sourceNumber"
            organization_id = "organization-$sourceNumber"
            canonical_domain = "source-$sourceNumber.example.vn"
            source_class = 'independent_corroboration'
            title = "Checked source $sourceNumber"
            url = "https://source-$sourceNumber.example.vn/guidance"
            evidence_label = 'corroborating'
            checked_on = '2026-08-03'
            freshness_tier = 'current'
            freshness_state = 'current'
            localities = @('fixture-locality')
            language = 'en'
            media_type = 'html'
            claim_groups = @('experience_fit')
            mappings = @([ordered]@{ claim_id = 'claim-access'; option_id = 'option-a'; axis_id = 'axis-1'; outcome_id = 'outcome-a' })
        }
    }
    $bundle['sources'] = ConvertTo-FixtureObject $sources

    $routes = @($bundle['related_routes'])
    $routes += [ordered]@{ path = 'destinations/ninh-binh-travel-guide'; label = 'Ninh Binh guide'; route_group = 'deepen_place' }
    $routes += [ordered]@{ path = 'itineraries/14-days-in-vietnam'; label = 'Fourteen day route'; route_group = 'build_route' }
    $bundle['related_routes'] = ConvertTo-FixtureObject $routes

    $updates = @($bundle['update_log'])
    foreach ($updateNumber in 2..12) {
        $updates += [ordered]@{
            date = '2026-08-03'
            summary = "Public update summary $updateNumber."
            change_reason = 'source_refresh'
            affected_public_labels = @("Decision frame $updateNumber", "Sources $updateNumber")
        }
    }
    $bundle['update_log'] = ConvertTo-FixtureObject $updates
    $bundle['module_requirements']['required_features'] = ConvertTo-FixtureObject $publicEnumContracts.requiredFeature

    $slots = New-Object System.Collections.ArrayList
    function Add-PaddingSlot {
        param($Container, $Key, [int]$MaxLength)
        [void]$slots.Add([ordered]@{ container = $Container; key = $Key; max_length = $MaxLength })
    }

    Add-PaddingSlot $bundle['editorial'] 'update_summary' 240
    foreach ($option in $bundle['options']) {
        Add-PaddingSlot $option 'label' 48
        Add-PaddingSlot $option 'summary' 180
    }
    Add-PaddingSlot $bundle['primary_decision'] 'summary' 240
    foreach ($ruleItem in $bundle['primary_decision']['rules']) {
        Add-PaddingSlot $ruleItem 'condition' 140
        Add-PaddingSlot $ruleItem 'outcome' 160
    }
    Add-PaddingSlot $bundle 'field_note' 320
    for ($index = 0; $index -lt $bundle['evidence_moat'].Count; $index++) {
        Add-PaddingSlot $bundle['evidence_moat'] $index 240
    }
    foreach ($axis in $bundle['axes']) {
        Add-PaddingSlot $axis 'label' 48
        Add-PaddingSlot $axis 'explanation' 180
        foreach ($assessment in $axis['assessments']) {
            Add-PaddingSlot $assessment 'outcome' 160
        }
    }
    foreach ($lens in $bundle['traveler_lenses']) {
        Add-PaddingSlot $lens 'traveler' 80
        Add-PaddingSlot $lens 'outcome' 220
        Add-PaddingSlot $lens 'trade_off' 220
        foreach ($ruleItem in $lens['rule_path']) {
            Add-PaddingSlot $ruleItem 'condition' 140
            Add-PaddingSlot $ruleItem 'outcome' 160
        }
    }
    foreach ($route in $bundle['related_routes']) {
        Add-PaddingSlot $route 'label' 72
    }
    foreach ($update in $bundle['update_log']) {
        Add-PaddingSlot $update 'summary' 240
    }
    foreach ($headingName in @('decision_heading', 'source_heading', 'route_heading', 'update_heading')) {
        Add-PaddingSlot $bundle['render_contract'] $headingName 72
    }

    $remainingBytes = $TargetBytes - (Get-SerializedUtf8Size $bundle)
    if ($remainingBytes -lt 0) {
        throw "target size $TargetBytes is smaller than the expanded fixture"
    }
    $remainder = $remainingBytes % 3
    if ($remainder -gt 0) {
        $firstSlot = $slots[0]
        $suffix = if ($remainder -eq 1) { 'x' } else { [string][char]0x00E9 }
        $firstSlot.container[$firstSlot.key] = ([string]$firstSlot.container[$firstSlot.key]) + $suffix
        $remainingBytes -= $remainder
    }
    $unicodeCharacters = [int]($remainingBytes / 3)
    foreach ($slot in $slots) {
        if ($unicodeCharacters -le 0) {
            break
        }
        $currentValue = [string]$slot.container[$slot.key]
        $availableCharacters = [int]$slot.max_length - $currentValue.Length
        $charactersToAdd = [Math]::Min($unicodeCharacters, $availableCharacters)
        if ($charactersToAdd -gt 0) {
            $slot.container[$slot.key] = $currentValue + ([string][char]0x1ED9 * $charactersToAdd)
            $unicodeCharacters -= $charactersToAdd
        }
    }
    if ($unicodeCharacters -ne 0 -or (Get-SerializedUtf8Size $bundle) -ne $TargetBytes) {
        throw "could not construct a schema-valid bundle at exactly $TargetBytes UTF-8 bytes"
    }
    return $bundle
}

function Get-PublicEnumSchemaNodes {
    param([Parameter(Mandatory = $true)]$Schema)

    $bundleProperties = Get-Property (Get-Definition $Schema 'resolvedBundleV2') 'properties'
    $manifestProperties = Get-Property (Get-Definition $Schema 'manifest') 'properties'
    $approvalProperties = Get-Property (Get-Definition $Schema 'approvalArtifact') 'properties'
    $primaryDecisionProperties = Get-Property (Get-Property $bundleProperties 'primary_decision') 'properties'
    $moduleProperties = Get-Property (Get-Property $bundleProperties 'module_requirements') 'properties'
    $sourceProbeProperties = Get-Property (Get-Definition $Schema 'sourceProbe') 'properties'
    $impactReportProperties = Get-Property (Get-Definition $Schema 'impactReport') 'properties'
    $backupProperties = Get-Property (Get-Definition $Schema 'backup') 'properties'
    $sourceRegistryProperties = Get-Property (Get-Definition $Schema 'sourceRegistry') 'properties'
    $sourceRegistryItemProperties = Get-Property (Get-Property (Get-Property $sourceRegistryProperties 'sources') 'items') 'properties'

    return [ordered]@{
        sourceClass = Get-Definition $Schema 'sourceClass'
        claimGroup = Get-Definition $Schema 'claimGroup'
        evidenceLabel = Get-Definition $Schema 'evidenceLabel'
        freshnessTier = Get-Definition $Schema 'freshnessTier'
        mediaType = Get-Definition $Schema 'mediaType'
        sourceFreshnessState = Get-Definition $Schema 'sourceFreshnessState'
        expectedTitleMode = Get-Property $sourceRegistryItemProperties 'expected_title_mode'
        ruleKind = Get-Definition $Schema 'ruleKind'
        routeGroup = Get-Definition $Schema 'routeGroup'
        changeReason = Get-Definition $Schema 'changeReason'
        activationStage = Get-Property $manifestProperties 'activation_stage'
        approvalRole = Get-Property $approvalProperties 'role'
        terminalOutcome = Get-Property $primaryDecisionProperties 'outcome'
        archetype = Get-Property $bundleProperties 'archetype'
        requiredFeature = Get-Property (Get-Property $moduleProperties 'required_features') 'items'
        sourceProbeOutcome = Get-Property $sourceProbeProperties 'outcome'
        impactReportOutcome = Get-Property $impactReportProperties 'outcome'
        schemaVersion = Get-Property $backupProperties 'schema_version'
    }
}

function Get-SchemaEnumInventory {
    param([Parameter(Mandatory = $true)]$Schema)

    $inventory = New-Object System.Collections.ArrayList
    function Visit-SchemaNode {
        param($Node, [string]$Path)
        if (Test-IsObject $Node) {
            if (Test-HasProperty $Node 'enum') {
                $enumValues = Get-Property $Node 'enum'
                [void]$inventory.Add([ordered]@{
                    path = $Path
                    key = [string]::Join([char]0x1F, @($enumValues))
                })
            }
            foreach ($propertyName in @(Get-ObjectPropertyNames $Node)) {
                Visit-SchemaNode (Get-Property $Node $propertyName) "$Path/$propertyName"
            }
        } elseif (Test-IsArray $Node) {
            for ($index = 0; $index -lt $Node.Count; $index++) {
                Visit-SchemaNode $Node[$index] "$Path/$index"
            }
        }
    }

    Visit-SchemaNode $Schema '$'
    return $inventory.ToArray()
}

function Invoke-FixtureValidation {
    param([Parameter(Mandatory = $true)]$Schema)

    $script:fixtureErrors = @()
    $script:fixtureChecks = 0

    function Assert-Accepted {
        param([string]$Name, $Value, $SchemaNode)
        $script:fixtureChecks++
        $validationErrors = @(Test-SchemaNode $Value $SchemaNode $Schema '$')
        if ($validationErrors.Count -gt 0) {
            $script:fixtureErrors += "E_SCHEMA fixture/${Name}: expected acceptance; $($validationErrors[0])"
        }
    }

    function Assert-Rejected {
        param([string]$Name, $Value, $SchemaNode)
        $script:fixtureChecks++
        $validationErrors = @(Test-SchemaNode $Value $SchemaNode $Schema '$')
        if ($validationErrors.Count -eq 0) {
            $script:fixtureErrors += "E_SCHEMA fixture/${Name}: expected rejection but value was accepted"
        }
    }

    function Assert-ApprovalVectorTrue {
        param([string]$Name, [bool]$Condition, [string]$Explanation)
        $script:fixtureChecks++
        if (-not $Condition) { $script:fixtureErrors += "E_APPROVAL fixture/${Name}: $Explanation" }
    }

    function Assert-BundleAccepted {
        param([string]$Name, $Value)
        $script:fixtureChecks++
        $validationErrors = @(Get-ResolvedBundleV2ContractErrors $Value $Schema "fixture/$Name")
        if ($validationErrors.Count -gt 0) {
            $script:fixtureErrors += $validationErrors[0]
        }
    }

    function Assert-BundleRejected {
        param([string]$Name, $Value, [string]$ExpectedCode = '')
        $script:fixtureChecks++
        $validationErrors = @(Get-ResolvedBundleV2ContractErrors $Value $Schema "fixture/$Name")
        if ($validationErrors.Count -eq 0) {
            $script:fixtureErrors += "E_SCHEMA fixture/${Name}: expected rejection but value was accepted"
            return
        }
        if (-not [string]::IsNullOrWhiteSpace($ExpectedCode)) {
            $matchingErrors = @($validationErrors | Where-Object { $_.StartsWith("$ExpectedCode ", [System.StringComparison]::Ordinal) })
            if ($matchingErrors.Count -eq 0) {
                $script:fixtureErrors += "E_SCHEMA fixture/${Name}: expected $ExpectedCode rejection"
            }
            if ($ExpectedCode -ceq 'E_SIZE') {
                $schemaErrors = @($validationErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) })
                if ($schemaErrors.Count -gt 0) {
                    $script:fixtureErrors += "E_SCHEMA fixture/${Name}: oversized regression bundle must remain schema-valid"
                }
            }
        }
    }

    function Assert-BundleSchemaValidatorParity {
        param([string]$Name, $Value, [bool]$ExpectedValid)
        $script:fixtureChecks++
        try {
            $exportedErrors = @(Test-VgSchemaDocument -Document $Value -Schema $Schema -DefinitionName 'resolvedBundleV2' -DocumentId "fixture/$Name exported")
            $internalErrors = @(Get-ResolvedBundleV2ContractErrors $Value $Schema "fixture/$Name internal")
            if ($ExpectedValid) {
                if ($exportedErrors.Count -ne 0 -or $internalErrors.Count -ne 0) {
                    $script:fixtureErrors += "E_FIXTURE fixture/${Name}: valid bundle validators disagreed or rejected the bundle"
                }
            } elseif ($exportedErrors.Count -ne 1 -or $internalErrors.Count -ne 1 -or
                -not $exportedErrors[0].StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) -or
                -not $internalErrors[0].StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal)) {
                $script:fixtureErrors += "E_FIXTURE fixture/${Name}: invalid bundle did not produce one deterministic E_SCHEMA from both validators"
            }
        } catch {
            $script:fixtureErrors += "E_FIXTURE fixture/${Name}: bundle validator parity check threw unexpectedly"
        }
    }

    $positiveBundle = New-PositiveBundle
    Assert-BundleAccepted 'resolved-bundle-v2-positive' $positiveBundle
    $tradeOffOnlyBundle = Copy-FixtureObject $positiveBundle
    Assert-BundleSchemaValidatorParity 'resolved-bundle-v2-trade-off-only-anyof' $tradeOffOnlyBundle $true
    $reversalOnlyBundle = Copy-FixtureObject $positiveBundle
    [void]$reversalOnlyBundle.traveler_lenses[0].Remove('trade_off')
    $reversalOnlyBundle.traveler_lenses[0].reversal_condition = 'Choose the other option when the transfer constraint changes.'
    Assert-BundleSchemaValidatorParity 'resolved-bundle-v2-reversal-only-anyof' $reversalOnlyBundle $true
    $missingTradeOffAndReversalBundle = Copy-FixtureObject $positiveBundle
    [void]$missingTradeOffAndReversalBundle.traveler_lenses[0].Remove('trade_off')
    Assert-BundleSchemaValidatorParity 'resolved-bundle-v2-missing-trade-off-and-reversal-anyof' $missingTradeOffAndReversalBundle $false

    $copySource = [ordered]@{
        empty_array = @()
        single_array = @('one')
        nested_arrays = @(@('alpha', 'beta'), @())
        empty_object = [ordered]@{}
        null_value = $null
        integer_value = 7
        boolean_value = $true
        string_value = 'copy me'
    }
    $copyResult = Copy-FixtureObject $copySource
    $script:fixtureChecks++
    if (-not (Test-IsArray (Get-Property $copyResult 'empty_array')) -or (Get-Property $copyResult 'empty_array').Count -ne 0) {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-empty-array: empty array was not preserved'
    }
    $script:fixtureChecks++
    if (-not (Test-IsArray (Get-Property $copyResult 'single_array')) -or (Get-Property $copyResult 'single_array').Count -ne 1) {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-single-array: single-item array was not preserved'
    }
    $script:fixtureChecks++
    if (-not (Test-IsArray (Get-Property $copyResult 'nested_arrays')) -or
        -not (Test-IsArray (Get-Property $copyResult 'nested_arrays')[1]) -or
        (Get-Property $copyResult 'nested_arrays')[1].Count -ne 0) {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-nested-arrays: nested arrays were not preserved'
    }
    $script:fixtureChecks++
    if (-not (Test-IsObject (Get-Property $copyResult 'empty_object')) -or @(Get-ObjectPropertyNames (Get-Property $copyResult 'empty_object')).Count -ne 0) {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-empty-object: empty object was not preserved'
    }
    $script:fixtureChecks++
    if (-not (Test-HasProperty $copyResult 'null_value') -or $null -ne (Get-Property $copyResult 'null_value')) {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-null: null property was not preserved'
    }
    $script:fixtureChecks++
    if ((Get-Property $copyResult 'integer_value') -ne 7 -or
        (Get-Property $copyResult 'boolean_value') -ne $true -or
        (Get-Property $copyResult 'string_value') -cne 'copy me') {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-scalars: scalar values were not preserved'
    }
    (Get-Property $copyResult 'single_array')[0] = 'changed'
    $script:fixtureChecks++
    if ((Get-Property $copySource 'single_array')[0] -cne 'one') {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-independence: copied arrays still reference the source'
    }
    $caseKeySource = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $caseKeySource.Add('CaseName', 'upper')
    $caseKeySource.Add('caseName', 'lower')
    $caseKeyCopy = Copy-FixtureObject $caseKeySource
    $script:fixtureChecks++
    if (@(Get-ObjectPropertyNames $caseKeyCopy).Count -ne 2 -or
        (Get-Property $caseKeyCopy 'CaseName') -cne 'upper' -or
        (Get-Property $caseKeyCopy 'caseName') -cne 'lower') {
        $script:fixtureErrors += 'E_SCHEMA fixture/deep-copy-case-sensitive-keys: case-differing property names were not preserved'
    }

    $casePropertySchema = '{"type":"object","additionalProperties":false,"required":["schema_version"],"properties":{"schema_version":{"const":"v2"}}}' | ConvertFrom-Json
    $exactPropertyObject = '{"schema_version":"v2"}' | ConvertFrom-Json
    Assert-Accepted 'exact-property-lowercase' $exactPropertyObject $casePropertySchema
    foreach ($caseFixture in @(
        [ordered]@{ name = 'pscustomobject'; value = ('{"Schema_Version":"v2"}' | ConvertFrom-Json) }
        [ordered]@{ name = 'idictionary'; value = @{ Schema_Version = 'v2' } }
    )) {
        $script:fixtureChecks++
        $caseErrors = @(Test-SchemaNode $caseFixture.value $casePropertySchema $Schema '$')
        if ($caseErrors -cnotcontains '$.schema_version is required' -or $caseErrors -cnotcontains '$.Schema_Version is not allowed') {
            $script:fixtureErrors += "E_SCHEMA fixture/exact-property-$($caseFixture.name): mixed-case property did not produce required and additionalProperties errors"
        }
    }

    $uniqueItemsSchema = '{"type":"array","uniqueItems":true}' | ConvertFrom-Json
    Assert-Accepted 'unique-items-string-case-sensitive' (ConvertTo-FixtureObject @('Label', 'label')) $uniqueItemsSchema
    $orderedObjectA = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $orderedObjectA.Add('a', 1)
    $orderedObjectA.Add('b', 2)
    $orderedObjectB = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $orderedObjectB.Add('b', 2)
    $orderedObjectB.Add('a', 1)
    Assert-Rejected 'unique-items-object-order-independent' (ConvertTo-FixtureObject @($orderedObjectA, $orderedObjectB)) $uniqueItemsSchema
    $caseObjectA = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $caseObjectA.Add('Label', 1)
    $caseObjectB = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $caseObjectB.Add('label', 1)
    Assert-Accepted 'unique-items-property-name-case-sensitive' (ConvertTo-FixtureObject @($caseObjectA, $caseObjectB)) $uniqueItemsSchema
    Assert-Accepted 'unique-items-arrays-positional' (ConvertTo-FixtureObject @(@(1, 2), @(2, 1))) $uniqueItemsSchema
    Assert-Rejected 'unique-items-json-numeric-equality' (ConvertTo-FixtureObject @([int]1, [double]1.0)) $uniqueItemsSchema
    Assert-Accepted 'unique-items-boolean-distinct-from-number' (ConvertTo-FixtureObject @($true, 1)) $uniqueItemsSchema
    $nearOneDouble = [double]1.0000000000000002
    $oneDouble = [double]1.0
    $script:fixtureChecks++
    if (Test-JsonValueEqual $nearOneDouble $oneDouble) {
        $script:fixtureErrors += 'E_SCHEMA fixture/double-roundtrip-direct-distinct: adjacent round-trip doubles compared equal'
    }
    Assert-Accepted 'unique-items-double-roundtrip-distinct' (ConvertTo-FixtureObject @($nearOneDouble, $oneDouble)) $uniqueItemsSchema
    $nearOneObject = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $nearOneObject.Add('value', $nearOneDouble)
    $oneObject = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $oneObject.Add('value', $oneDouble)
    Assert-Accepted 'unique-items-object-double-roundtrip-distinct' (ConvertTo-FixtureObject @($nearOneObject, $oneObject)) $uniqueItemsSchema
    Assert-Rejected 'unique-items-decimal-double-tenth-equal' (ConvertTo-FixtureObject @([decimal]0.1, [double]0.1)) $uniqueItemsSchema
    Assert-Rejected 'unique-items-exponent-decimal-equal' (ConvertTo-FixtureObject @([double]1e-5, [decimal]0.00001)) $uniqueItemsSchema
    Assert-Rejected 'unique-items-negative-zero-equal' (ConvertTo-FixtureObject @([double](-0.0), [int]0)) $uniqueItemsSchema
    $numberTypeSchema = '{"type":"number"}' | ConvertFrom-Json
    $nonfiniteFixtures = @(
        [ordered]@{ name = 'positive-infinity'; token = 'Infinity'; value = [double]::PositiveInfinity }
        [ordered]@{ name = 'negative-infinity'; token = '-Infinity'; value = [double]::NegativeInfinity }
        [ordered]@{ name = 'nan'; token = 'NaN'; value = [double]::NaN }
    )
    foreach ($nonfiniteFixture in $nonfiniteFixtures) {
        $script:fixtureChecks++
        if (Test-JsonValueEqual $nonfiniteFixture.value $nonfiniteFixture.value) {
            $script:fixtureErrors += "E_SCHEMA fixture/nonfinite-equality-$($nonfiniteFixture.name): nonfinite value compared equal to itself"
        }
        Assert-Accepted "unique-items-nonfinite-$($nonfiniteFixture.name)" (ConvertTo-FixtureObject @($nonfiniteFixture.value, $nonfiniteFixture.value)) $uniqueItemsSchema
        Assert-Rejected "number-type-nonfinite-$($nonfiniteFixture.name)" $nonfiniteFixture.value $numberTypeSchema
        try {
            $parsedNonfinite = ("{`"value`":$($nonfiniteFixture.token)}" | ConvertFrom-Json).value
            $script:fixtureChecks++
            if (@(Test-SchemaNode $parsedNonfinite $numberTypeSchema $Schema '$').Count -eq 0 -or
                (Test-JsonValueEqual $parsedNonfinite $parsedNonfinite)) {
                $script:fixtureErrors += "E_SCHEMA fixture/nonfinite-convertfromjson-$($nonfiniteFixture.name): parsed nonfinite value did not fail closed"
            }
        } catch {
            # Engines that reject non-standard JSON tokens already fail closed.
        }
    }
    $hugeIntegerA = [System.Numerics.BigInteger]::Parse('10000000000000000000000000000000000000000')
    $hugeIntegerB = [System.Numerics.BigInteger]::Parse('10000000000000000000000000000000000000001')
    $script:fixtureChecks++
    if (Test-JsonValueEqual $hugeIntegerA $hugeIntegerB) {
        $script:fixtureErrors += 'E_SCHEMA fixture/big-integer-direct-distinct: distinct huge integers compared equal'
    }
    $script:fixtureChecks++
    if (-not (Test-JsonValueEqual $hugeIntegerA $hugeIntegerA)) {
        $script:fixtureErrors += 'E_SCHEMA fixture/big-integer-direct-equal: identical huge integers compared distinct'
    }
    Assert-Accepted 'unique-items-big-integer-distinct' (ConvertTo-FixtureObject @($hugeIntegerA, $hugeIntegerB)) $uniqueItemsSchema
    Assert-Rejected 'unique-items-big-integer-duplicate' (ConvertTo-FixtureObject @($hugeIntegerA, $hugeIntegerA)) $uniqueItemsSchema
    $hugeObjectA = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $hugeObjectA.Add('value', $hugeIntegerA)
    $hugeObjectB = New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal)
    $hugeObjectB.Add('value', $hugeIntegerB)
    Assert-Accepted 'unique-items-object-big-integer-distinct' (ConvertTo-FixtureObject @($hugeObjectA, $hugeObjectB)) $uniqueItemsSchema
    Assert-Rejected 'unique-items-object-big-integer-duplicate' (ConvertTo-FixtureObject @($hugeObjectA, $hugeObjectA)) $uniqueItemsSchema

    $integerTypeSchema = '{"type":"integer"}' | ConvertFrom-Json
    Assert-Accepted 'integer-type-single-integral' ([single]1.0) $integerTypeSchema
    Assert-Accepted 'integer-type-double-integral' ([double]1.0) $integerTypeSchema
    Assert-Accepted 'integer-type-decimal-integral' ([decimal]1.0) $integerTypeSchema
    Assert-Accepted 'integer-type-big-integer' ([System.Numerics.BigInteger]::Parse('10000000000000000000000000000000000000000')) $integerTypeSchema
    Assert-Rejected 'integer-type-single-fractional' ([single]1.5) $integerTypeSchema
    Assert-Rejected 'integer-type-double-fractional' ([double]1.5) $integerTypeSchema
    Assert-Rejected 'integer-type-decimal-fractional' ([decimal]1.5) $integerTypeSchema
    Assert-Rejected 'integer-type-nan' ([double]::NaN) $integerTypeSchema
    Assert-Rejected 'integer-type-positive-infinity' ([double]::PositiveInfinity) $integerTypeSchema
    Assert-Rejected 'integer-type-negative-infinity' ([double]::NegativeInfinity) $integerTypeSchema

    $astralEmoji = [char]::ConvertFromUtf32(0x1F600)
    $oneCodePointSchema = '{"type":"string","maxLength":1}' | ConvertFrom-Json
    $zeroCodePointSchema = '{"type":"string","maxLength":0}' | ConvertFrom-Json
    $twoCodePointSchema = '{"type":"string","minLength":2,"maxLength":2}' | ConvertFrom-Json
    Assert-Accepted 'unicode-length-one-astral' $astralEmoji $oneCodePointSchema
    Assert-Rejected 'unicode-length-one-astral-over-zero' $astralEmoji $zeroCodePointSchema
    Assert-Accepted 'unicode-length-two-astral' ($astralEmoji + $astralEmoji) $twoCodePointSchema
    Assert-Rejected 'unicode-length-one-astral-under-two' $astralEmoji $twoCodePointSchema
    Assert-Accepted 'unicode-length-bmp-vietnamese' ([string][char]0x1EC3) $oneCodePointSchema
    $plainStringSchema = '{"type":"string"}' | ConvertFrom-Json
    Assert-Rejected 'unicode-unpaired-high-surrogate' ([string][char]0xD83D) $plainStringSchema
    Assert-Rejected 'unicode-unpaired-low-surrogate' ([string][char]0xDE00) $plainStringSchema

    $missingRequired = Copy-FixtureObject $positiveBundle
    [void]$missingRequired.Remove('editorial')
    Assert-BundleRejected 'required-key' $missingRequired

    $wrongScalar = Copy-FixtureObject $positiveBundle
    $wrongScalar.post_id = '279'
    Assert-BundleRejected 'scalar-type' $wrongScalar

    $tooFewAxes = Copy-FixtureObject $positiveBundle
    $tooFewAxes.axes = @($tooFewAxes.axes | Select-Object -First 2)
    Assert-BundleRejected 'axis-count' $tooFewAxes

    $longAxisLabel = Copy-FixtureObject $positiveBundle
    $longAxisLabel.axes[0].label = ('x' * 49)
    Assert-BundleRejected 'axis-label-length' $longAxisLabel

    $longAxisExplanation = Copy-FixtureObject $positiveBundle
    $longAxisExplanation.axes[0].explanation = ('x' * 181)
    Assert-BundleRejected 'axis-explanation-length' $longAxisExplanation

    $longDecision = Copy-FixtureObject $positiveBundle
    $longDecision.primary_decision.summary = ('x' * 241)
    Assert-BundleRejected 'primary-decision-length' $longDecision

    $longLensOutcome = Copy-FixtureObject $positiveBundle
    $longLensOutcome.traveler_lenses[0].outcome = ('x' * 221)
    Assert-BundleRejected 'traveler-lens-length' $longLensOutcome

    $tooFewSources = Copy-FixtureObject $positiveBundle
    $tooFewSources.sources = @($tooFewSources.sources | Select-Object -First 5)
    Assert-BundleRejected 'source-count' $tooFewSources

    $tooFewRoutes = Copy-FixtureObject $positiveBundle
    $tooFewRoutes.related_routes = @($tooFewRoutes.related_routes | Select-Object -First 5)
    Assert-BundleRejected 'route-count' $tooFewRoutes

    $missingRouteGroup = Copy-FixtureObject $positiveBundle
    foreach ($route in $missingRouteGroup.related_routes) {
        if ($route.route_group -ceq 'check_practical') {
            $route.route_group = 'build_route'
        }
    }
    Assert-BundleRejected 'route-group-coverage' $missingRouteGroup

    $unknownKey = Copy-FixtureObject $positiveBundle
    $unknownKey['reviewer_notes'] = 'private'
    Assert-BundleRejected 'unknown-key' $unknownKey

    $rawHtml = Copy-FixtureObject $positiveBundle
    $rawHtml.field_note = '<script>alert(1)</script>'
    Assert-BundleRejected 'raw-html' $rawHtml

    $eventHandler = Copy-FixtureObject $positiveBundle
    $eventHandler.field_note = 'onclick=alert(1)'
    Assert-BundleRejected 'event-handler-text' $eventHandler

    $controlCharacter = Copy-FixtureObject $positiveBundle
    $controlCharacter.field_note = "unsafe$([char]1)text"
    Assert-BundleRejected 'control-character' $controlCharacter

    foreach ($unsafeTextFixture in ([ordered]@{
        'safe-text-trailing-lf' = "unsafe`n"
        'safe-text-trailing-cr' = "unsafe`r"
        'safe-text-embedded-newline' = "unsafe`ntext"
        'safe-text-delete-control' = "unsafe$([char]0x7F)text"
    }).GetEnumerator()) {
        Assert-Rejected $unsafeTextFixture.Key $unsafeTextFixture.Value (Get-Definition $Schema 'safeText')
    }
    $vietnameseText = [string]::Concat('Th', [char]0x00F4, 'ng tin ', [char]0x0111, [char]0x00E3, ' ki', [char]0x1EC3, 'm ch', [char]0x1EE9, 'ng')
    Assert-Accepted 'safe-text-vietnamese-unicode' $vietnameseText (Get-Definition $Schema 'safeText')
    Assert-Accepted 'safe-text-condition-assignment' 'condition=clear' (Get-Definition $Schema 'safeText')
    Assert-Accepted 'safe-text-connection-status-assignment' 'connectionStatus=confirmed' (Get-Definition $Schema 'safeText')
    Assert-Rejected 'safe-text-event-attribute-start' 'onclick=alert(1)' (Get-Definition $Schema 'safeText')
    Assert-Rejected 'safe-text-event-attribute-after-space' 'text onclick=alert(1)' (Get-Definition $Schema 'safeText')

    Assert-Accepted 'safe-http-url' 'https://example.org/source?id=1' (Get-Definition $Schema 'httpUrl')
    Assert-Accepted 'safe-http-url-http' 'http://example.org/source' (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-javascript-url' 'javascript:alert(1)' (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-data-url' 'data:text/html,bad' (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-url-trailing-lf' "https://example.org/source`n" (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-url-trailing-cr' "https://example.org/source`r" (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-url-delete-control' "https://example.org/source$([char]0x7F)" (Get-Definition $Schema 'httpUrl')

    $isoDateSchema = Get-Definition $Schema 'isoDate'
    $script:fixtureChecks++
    if ((Get-Property $isoDateSchema 'format') -cne 'date') {
        $script:fixtureErrors += 'E_SCHEMA fixture/iso-date-format-contract: isoDate must declare format date'
    }
    Assert-Accepted 'iso-date-normal' '2026-08-03' $isoDateSchema
    Assert-Accepted 'iso-date-leap-day' '2024-02-29' $isoDateSchema
    Assert-Rejected 'iso-date-impossible-february' '2026-02-31' $isoDateSchema
    Assert-Rejected 'iso-date-impossible-april' '2026-04-31' $isoDateSchema

    $publicEnumSchemaNodes = Get-PublicEnumSchemaNodes $Schema
    foreach ($enumFamily in $publicEnumContracts.Keys) {
        $enumSchemaNode = $publicEnumSchemaNodes[$enumFamily]
        $declaredValues = Get-Property $enumSchemaNode 'enum'
        $script:fixtureChecks++
        if (-not (Test-OrdinalSequence $declaredValues $publicEnumContracts[$enumFamily])) {
            $script:fixtureErrors += "E_SCHEMA fixture/enum-inventory-${enumFamily}: schema enum does not match the approved ordinal contract"
        }
        foreach ($allowedValue in $publicEnumContracts[$enumFamily]) {
            Assert-Accepted "enum-${enumFamily}-${allowedValue}" $allowedValue $enumSchemaNode
        }
        Assert-Rejected "enum-${enumFamily}-unknown" '__unknown__' $enumSchemaNode
    }
    $knownEnumKeys = @{}
    foreach ($enumFamily in $publicEnumContracts.Keys) {
        $knownEnumKeys[[string]::Join([char]0x1F, @($publicEnumContracts[$enumFamily]))] = $true
    }
    foreach ($declaredEnum in @(Get-SchemaEnumInventory $Schema)) {
        $script:fixtureChecks++
        if (-not $knownEnumKeys.ContainsKey($declaredEnum.key)) {
            $script:fixtureErrors += "E_SCHEMA fixture/enum-inventory-untracked: public enum at $($declaredEnum.path) has no approved fixture contract"
        }
    }

    foreach ($enumName in $enumContracts.Keys) {
        foreach ($allowedEnumValue in $enumContracts[$enumName]) {
            Assert-Accepted "$enumName-$allowedEnumValue" $allowedEnumValue (Get-Definition $Schema $enumName)
        }
        $firstEnumValue = [string]$enumContracts[$enumName][0]
        Assert-Rejected "$enumName-invalid-case" $firstEnumValue.ToUpperInvariant() (Get-Definition $Schema $enumName)
        Assert-Rejected "$enumName-invalid-prefix" ("$firstEnumValue-extra") (Get-Definition $Schema $enumName)
    }

    $manifestSchema = Get-Definition $Schema 'manifest'
    $fixtureManifestPath = Join-Path $repoRoot 'ops/comparison-rollout/fixtures/minimal/manifest.json'.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $fixtureManifestDocument = [System.IO.File]::ReadAllText($fixtureManifestPath, [System.Text.Encoding]::UTF8) | ConvertFrom-Json
    $productionPages = @()
    foreach ($targetMapping in $targetMappings) {
        $productionPage = Copy-FixtureObject (Get-Property $fixtureManifestDocument 'pages')[0]
        $productionPage['path'] = $targetMapping.path
        $productionPage['post_id'] = $targetMapping.post_id
        $productionPages += $productionPage
    }
    $manifestFixture = ConvertTo-FixtureObject ([ordered]@{
        manifest_version = 'manifest-v2'
        schema_version = 'v2'
        previous_schema_version = 'v1'
        activation_artifact_version = 'activation-v2'
        activation_stage = 'canary'
        baseline = $baselinePaths
        canary = $canaryInventory
        full = $fullInventory
        six = $sixInventory
        permanent_controls = $permanentControls
        target_mappings = $targetMappings
        canary_activation = $canaryActivation
        pages = $productionPages
    })
    Assert-Accepted 'manifest-contract' $manifestFixture $manifestSchema
    $script:fixtureChecks++
    if (@(Test-ManifestOrdinalContracts $manifestFixture).Count -gt 0) {
        $script:fixtureErrors += 'E_SCHEMA fixture/manifest-ordinal-positive: exact inventory or mapping order was rejected'
    }
    $badOrdinalManifest = Copy-FixtureObject $manifestFixture
    $firstBaselinePath = $badOrdinalManifest['baseline'][0]
    $badOrdinalManifest['baseline'][0] = $badOrdinalManifest['baseline'][1]
    $badOrdinalManifest['baseline'][1] = $firstBaselinePath
    $script:fixtureChecks++
    if (@(Test-ManifestOrdinalContracts $badOrdinalManifest).Count -eq 0) {
        $script:fixtureErrors += 'E_SCHEMA fixture/manifest-ordinal-negative: reordered inventory was accepted'
    }
    $badStage = Copy-FixtureObject $manifestFixture
    $badStage.activation_stage = 'CANARY'
    Assert-Rejected 'activation-stage-enum' $badStage $manifestSchema

    foreach ($activationStage in @('baseline', 'canary', 'full')) {
        $stageFixture = Copy-FixtureObject $manifestFixture
        $stageFixture['activation_stage'] = $activationStage
        Assert-Accepted "activation-stage-$activationStage" $stageFixture $manifestSchema
    }

    $approvalSchema = Get-Definition $Schema 'approvalArtifact'
    $approvalPayload = [ordered]@{
        artifact_version = '1'
        manifest_hash = ('a' * 64)
        identity_id = 'fixture-author'
        wp_user_id = 101
        role = 'author'
        timestamp_utc = '2026-08-03T00:00:00Z'
        change_ids = @('cmp-104-decision')
        change_reason = 'decision_change'
        key_id = 'comparison-approval-2026-01'
    }
    $approvalCanonical = ConvertTo-VgCanonicalJson -Value $approvalPayload
    Assert-ApprovalVectorTrue 'approval-canonical-literal' ($approvalCanonical -ceq '{"artifact_version":"1","change_ids":["cmp-104-decision"],"change_reason":"decision_change","identity_id":"fixture-author","key_id":"comparison-approval-2026-01","manifest_hash":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","role":"author","timestamp_utc":"2026-08-03T00:00:00Z","wp_user_id":101}') 'approval payload canonical ordering changed'
    Assert-ApprovalVectorTrue 'approval-canonical-sha256-literal' ((Get-VgSha256Hex -Value $approvalPayload) -ceq '268cacf50d0eb37b0353422154e3c82291924d04e1c3b0d258a651f8ad81df0f') 'approval payload SHA-256 parity vector changed'
    $approvalFixture = Copy-FixtureObject $approvalPayload
    $approvalFixture['hmac_sha256'] = '802e95adf764dff476f34115485015883ea007ee9406d02897d46da20debf898'
    Assert-Accepted 'approval-flat-artifact-positive' $approvalFixture $approvalSchema
    $unknownApprovalKey = Copy-FixtureObject $approvalFixture
    $unknownApprovalKey['reviewer_notes'] = 'private'
    Assert-Rejected 'approval-flat-artifact-unknown-key' $unknownApprovalKey $approvalSchema
    $missingHmac = Copy-FixtureObject $approvalFixture
    [void]$missingHmac.Remove('hmac_sha256')
    Assert-Rejected 'approval-flat-artifact-missing-hmac' $missingHmac $approvalSchema

    foreach ($terminalOutcome in @('option', 'no_clear_winner', 'combine_or_sequence')) {
        $terminalFixture = Copy-FixtureObject $positiveBundle
        $terminalFixture.primary_decision.outcome = $terminalOutcome
        Assert-BundleAccepted "terminal-outcome-$terminalOutcome" $terminalFixture
    }
    $badTerminal = Copy-FixtureObject $positiveBundle
    $badTerminal.primary_decision.outcome = 'weighted_score'
    Assert-BundleRejected 'terminal-outcome-enum' $badTerminal

    $visibleLimit = Copy-FixtureObject $positiveBundle
    $visibleLimit.render_contract.max_visible_characters = 1801
    Assert-BundleRejected 'visible-decision-frame-limit' $visibleLimit

    $boundaryBundle = New-SizedBundleFixture 65536
    Assert-BundleAccepted 'resolved-bundle-v2-size-boundary' $boundaryBundle
    $script:fixtureChecks++
    if ((Get-SerializedUtf8Size $boundaryBundle) -ne 65536) {
        $script:fixtureErrors += 'E_SIZE fixture/resolved-bundle-v2-size-boundary: bundle is not exactly 65,536 UTF-8 bytes'
    }
    $oversizedBundle = New-SizedBundleFixture 65537
    Assert-BundleRejected 'resolved-bundle-v2-size-oversized' $oversizedBundle 'E_SIZE'
    $script:fixtureChecks++
    if ((Get-SerializedUtf8Size $oversizedBundle) -ne 65537) {
        $script:fixtureErrors += 'E_SIZE fixture/resolved-bundle-v2-size-oversized: bundle is not exactly 65,537 UTF-8 bytes'
    }

    $script:fixtureChecks++
    if (-not (Test-WithinBundleSizeLimit $positiveBundle)) {
        $script:fixtureErrors += 'E_SIZE fixture/resolved-bundle-v2-positive: valid fixture exceeds 65,536 UTF-8 bytes'
    }

    return [ordered]@{ errors = @($script:fixtureErrors); checks = $script:fixtureChecks }
}

function Invoke-ResolverFixtureValidation {
    param(
        [Parameter(Mandatory = $true)]$Schema,
        [Parameter(Mandatory = $true)][datetime]$EvaluationDate
    )

    $script:resolverFixtureErrors = @()
    $script:resolverFixtureChecks = 0

    function Add-ResolverFailure {
        param([string]$Code, [string]$Name, [string]$Explanation)
        $script:resolverFixtureErrors += "$Code fixture/$Name`: $Explanation"
    }

    function Assert-ResolverTrue {
        param([string]$Name, [bool]$Condition, [string]$Explanation)
        $script:resolverFixtureChecks++
        if (-not $Condition) {
            Add-ResolverFailure 'E_FIXTURE' $Name $Explanation
        }
    }

    function Get-PortfolioResult {
        param($ManifestDocument, $SourceDocument, $OrganizationDocument, $IdentityDocument, [string]$Profile = 'Fixture')
        return Test-VgComparisonPortfolio `
            -Manifest $ManifestDocument `
            -Sources $SourceDocument `
            -Organizations $OrganizationDocument `
            -Identities $IdentityDocument `
            -Schema $Schema `
            -Profile $Profile `
            -AsOfDate $EvaluationDate
    }

    function Assert-PortfolioError {
        param([string]$Name, $ManifestDocument, $SourceDocument, $OrganizationDocument, $IdentityDocument, [string]$ExpectedCode)
        $script:resolverFixtureChecks++
        try {
            $mutationResult = Get-PortfolioResult $ManifestDocument $SourceDocument $OrganizationDocument $IdentityDocument
            if (@($mutationResult.Errors | Where-Object { $_.StartsWith("$ExpectedCode ", [System.StringComparison]::Ordinal) }).Count -eq 0) {
                Add-ResolverFailure 'E_FIXTURE' $Name "expected $ExpectedCode but received $([string]::Join(' | ', @($mutationResult.Errors)))"
            }
        } catch {
            Add-ResolverFailure 'E_FIXTURE' $Name "validator threw instead of returning $ExpectedCode"
        }
    }

    function Assert-NoPortfolioError {
        param([string]$Name, $ManifestDocument, $SourceDocument, $OrganizationDocument, $IdentityDocument, [string]$ForbiddenCode)
        $script:resolverFixtureChecks++
        try {
            $mutationResult = Get-PortfolioResult $ManifestDocument $SourceDocument $OrganizationDocument $IdentityDocument
            if (@($mutationResult.Errors | Where-Object { $_.StartsWith("$ForbiddenCode ", [System.StringComparison]::Ordinal) }).Count -gt 0) {
                Add-ResolverFailure 'E_FIXTURE' $Name "did not expect $ForbiddenCode at the exact integer boundary"
            }
        } catch {
            Add-ResolverFailure 'E_FIXTURE' $Name 'validator threw while checking an exact integer boundary'
        }
    }

    function Assert-PortfolioExactError {
        param([string]$Name, $ManifestDocument, $SourceDocument, $OrganizationDocument, $IdentityDocument, [string]$ExpectedError)
        $script:resolverFixtureChecks++
        try {
            $mutationResult = Get-PortfolioResult $ManifestDocument $SourceDocument $OrganizationDocument $IdentityDocument
            if (@($mutationResult.Errors | Where-Object { $_ -ceq $ExpectedError }).Count -ne 1) {
                Add-ResolverFailure 'E_FIXTURE' $Name "expected exact error '$ExpectedError' but received $([string]::Join(' | ', @($mutationResult.Errors)))"
            }
        } catch {
            Add-ResolverFailure 'E_FIXTURE' $Name 'validator threw instead of returning the exact deterministic error'
        }
    }

    $canonicalA = ConvertTo-VgCanonicalJson -Value ([ordered]@{ b = 2; a = 1 })
    $canonicalB = ConvertTo-VgCanonicalJson -Value ([ordered]@{ a = 1; b = 2 })
    Assert-ResolverTrue 'canonical-ordinal-object-order' ($canonicalA -ceq '{"a":1,"b":2}' -and $canonicalA -ceq $canonicalB) 'object keys were not sorted with ordinal comparison'
    Assert-ResolverTrue 'canonical-array-order' ((ConvertTo-VgCanonicalJson -Value @('z', 'a', 2, 1)) -ceq '["z","a",2,1]') 'array order was not preserved'
    Assert-ResolverTrue 'canonical-scalars' ((ConvertTo-VgCanonicalJson -Value ([ordered]@{ n = $null; f = $false; t = $true; d = [decimal]1.25 })) -ceq '{"d":1.25,"f":false,"n":null,"t":true}') 'scalar serialization was not compact and culture-independent'
    $hashA = Get-VgSha256Hex -Value ([ordered]@{ b = 2; a = 1 })
    $hashB = Get-VgSha256Hex -Value ([ordered]@{ a = 1; b = 2 })
    Assert-ResolverTrue 'canonical-hash-order-independent' ($hashA -ceq $hashB -and $hashA -cmatch '^[a-f0-9]{64}$') 'SHA-256 was not lowercase or canonical-order independent'

    $tempFiles = @()
    try {
        $utf8NoBom = New-Object System.Text.UTF8Encoding($false, $true)
        $validPath = [System.IO.Path]::GetTempFileName(); $tempFiles += $validPath
        [System.IO.File]::WriteAllText($validPath, '{"message":"Vietnam"}', $utf8NoBom)
        $validDocument = Read-VgJsonDocument -Path $validPath
        Assert-ResolverTrue 'read-utf8-no-bom' ((Get-Property $validDocument 'message') -ceq 'Vietnam') 'valid UTF-8 without BOM was rejected'

        foreach ($invalidDocument in @(
            [ordered]@{ name = 'read-bom-rejected'; bytes = [byte[]](0xEF, 0xBB, 0xBF, 0x7B, 0x7D); code = 'E_UTF8' }
            [ordered]@{ name = 'read-invalid-utf8-rejected'; bytes = [byte[]](0x7B, 0x22, 0x78, 0x22, 0x3A, 0x22, 0xC3, 0x28, 0x22, 0x7D); code = 'E_UTF8' }
            [ordered]@{ name = 'read-duplicate-key-rejected'; text = '{"a":1,"a":2}'; code = 'E_DUPLICATE_KEY' }
            [ordered]@{ name = 'read-nested-duplicate-key-rejected'; text = '{"a":{"x":1,"x":2}}'; code = 'E_DUPLICATE_KEY' }
            [ordered]@{ name = 'read-control-corruption-rejected'; text = "{`"a`":`"bad$([char]1)value`"}"; code = 'E_JSON' }
        )) {
            $invalidPath = [System.IO.Path]::GetTempFileName(); $tempFiles += $invalidPath
            if (Test-HasProperty $invalidDocument 'bytes') {
                [System.IO.File]::WriteAllBytes($invalidPath, $invalidDocument.bytes)
            } else {
                [System.IO.File]::WriteAllText($invalidPath, $invalidDocument.text, $utf8NoBom)
            }
            $script:resolverFixtureChecks++
            try {
                [void](Read-VgJsonDocument -Path $invalidPath)
                Add-ResolverFailure 'E_FIXTURE' $invalidDocument.name "expected $($invalidDocument.code) rejection"
            } catch {
                if (-not $_.Exception.Message.StartsWith("$($invalidDocument.code) ", [System.StringComparison]::Ordinal)) {
                    Add-ResolverFailure 'E_FIXTURE' $invalidDocument.name "unexpected error '$($_.Exception.Message)'"
                }
            }
        }
    } finally {
        foreach ($tempFile in $tempFiles) {
            if (Test-Path -LiteralPath $tempFile -PathType Leaf) {
                [System.IO.File]::Delete($tempFile)
            }
        }
    }

    $fixtureRoot = Join-Path $repoRoot 'ops/comparison-rollout/fixtures/minimal'.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $manifest = Read-VgJsonDocument -Path (Join-Path $fixtureRoot 'manifest.json')
    $sources = Read-VgJsonDocument -Path (Join-Path $fixtureRoot 'sources.json')
    $organizations = Read-VgJsonDocument -Path (Join-Path $fixtureRoot 'organizations.json')
    $identities = Read-VgJsonDocument -Path (Join-Path $fixtureRoot 'identities.json')

    foreach ($schemaFixture in @(
        [ordered]@{ name = 'fixture-manifest-schema'; document = $manifest; definition = 'fixtureManifest' }
        [ordered]@{ name = 'source-registry-schema'; document = $sources; definition = 'sourceRegistry' }
        [ordered]@{ name = 'organization-registry-schema'; document = $organizations; definition = 'organizationRegistry' }
        [ordered]@{ name = 'identity-registry-schema'; document = $identities; definition = 'identityRegistry' }
    )) {
        $schemaErrors = @(Test-VgSchemaDocument -Document $schemaFixture.document -Schema $Schema -DefinitionName $schemaFixture.definition -DocumentId "fixture/$($schemaFixture.name)")
        Assert-ResolverTrue $schemaFixture.name ($schemaErrors.Count -eq 0) "valid fixture schema was rejected: $([string]::Join(' | ', $schemaErrors))"
    }
    $canonicalDomainValidErrors = @(Test-VgSchemaDocument -Document 'publisher.example.vn' -Schema $Schema -DefinitionName 'canonicalDomain' -DocumentId 'fixture/canonical-domain-valid')
    Assert-ResolverTrue 'canonical-domain-valid' ($canonicalDomainValidErrors.Count -eq 0) 'valid lowercase hostname was rejected'
    $canonicalDomainInvalidErrors = @(Test-VgSchemaDocument -Document 'bad..example.vn' -Schema $Schema -DefinitionName 'canonicalDomain' -DocumentId 'fixture/canonical-domain-invalid')
    Assert-ResolverTrue 'canonical-domain-invalid' (@($canonicalDomainInvalidErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'canonicalDomain accepted an empty hostname label'

    $unknownNested = Copy-FixtureObject $manifest
    $unknownNested.pages[0].axes[0]['unexpected'] = 'private'
    $unknownErrors = @(Test-VgSchemaDocument -Document $unknownNested -Schema $Schema -DefinitionName 'fixtureManifest' -DocumentId 'fixture/unknown-nested')
    Assert-ResolverTrue 'schema-unknown-nested-key' (@($unknownErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'unknown nested key was accepted'
    $missingNested = Copy-FixtureObject $manifest
    [void]$missingNested.pages[0].outcomes[0].Remove('trade_off')
    $missingErrors = @(Test-VgSchemaDocument -Document $missingNested -Schema $Schema -DefinitionName 'fixtureManifest' -DocumentId 'fixture/missing-nested')
    Assert-ResolverTrue 'schema-missing-nested-key' (@($missingErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'missing nested key was accepted'

    $moduleUniqueSchema = '{"type":"array","uniqueItems":true}' | ConvertFrom-Json
    $moduleHugeA = [System.Numerics.BigInteger]::Parse('10000000000000000000000000000000000000000')
    $moduleHugeB = [System.Numerics.BigInteger]::Parse('10000000000000000000000000000000000000001')
    $moduleHugeDistinctErrors = @(Test-VgSchemaDocument -Document (ConvertTo-FixtureObject @($moduleHugeA, $moduleHugeB)) -Schema $moduleUniqueSchema -DocumentId 'fixture/module-big-integer-distinct')
    Assert-ResolverTrue 'module-schema-big-integer-distinct' ($moduleHugeDistinctErrors.Count -eq 0) 'module schema equality collapsed distinct huge integers'
    $moduleHugeDuplicateErrors = @(Test-VgSchemaDocument -Document (ConvertTo-FixtureObject @($moduleHugeA, $moduleHugeA)) -Schema $moduleUniqueSchema -DocumentId 'fixture/module-big-integer-duplicate')
    Assert-ResolverTrue 'module-schema-big-integer-duplicate' (@($moduleHugeDuplicateErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'module schema equality accepted duplicate huge integers'
    $moduleHugeNegative = -$moduleHugeA
    $moduleNumericBoundFixtures = @(
        [ordered]@{ name = 'module-big-integer-positive-exact-bound'; value = $moduleHugeA; minimum = $moduleHugeA; maximum = $moduleHugeA; expected = '' }
        [ordered]@{ name = 'module-big-integer-positive-below-minimum'; value = $moduleHugeA; minimum = ($moduleHugeA + [System.Numerics.BigInteger]::One); maximum = $null; expected = '$ is below minimum' }
        [ordered]@{ name = 'module-big-integer-positive-above-maximum'; value = $moduleHugeA; minimum = $null; maximum = ($moduleHugeA - [System.Numerics.BigInteger]::One); expected = '$ is above maximum' }
        [ordered]@{ name = 'module-big-integer-negative-exact-bound'; value = $moduleHugeNegative; minimum = $moduleHugeNegative; maximum = $moduleHugeNegative; expected = '' }
        [ordered]@{ name = 'module-big-integer-negative-below-minimum'; value = ($moduleHugeNegative - [System.Numerics.BigInteger]::One); minimum = $moduleHugeNegative; maximum = $null; expected = '$ is below minimum' }
        [ordered]@{ name = 'module-big-integer-negative-above-maximum'; value = ($moduleHugeNegative + [System.Numerics.BigInteger]::One); minimum = $null; maximum = $moduleHugeNegative; expected = '$ is above maximum' }
        [ordered]@{ name = 'module-int64-exact-bound'; value = [int64]::MaxValue; minimum = [int64]::MaxValue; maximum = [int64]::MaxValue; expected = '' }
        [ordered]@{ name = 'module-decimal-exact-bound'; value = [decimal]1.25; minimum = [decimal]1.25; maximum = [decimal]1.25; expected = '' }
    )
    foreach ($numericBoundFixture in $moduleNumericBoundFixtures) {
        $script:resolverFixtureChecks++
        $numericBoundSchema = [ordered]@{ type = 'integer' }
        if ($numericBoundFixture.value -is [decimal]) { $numericBoundSchema.type = 'number' }
        if ($null -ne $numericBoundFixture.minimum) { $numericBoundSchema.minimum = $numericBoundFixture.minimum }
        if ($null -ne $numericBoundFixture.maximum) { $numericBoundSchema.maximum = $numericBoundFixture.maximum }
        try {
            $numericBoundErrors = @(Test-VgSchemaDocument -Document $numericBoundFixture.value -Schema $numericBoundSchema -DocumentId "fixture/$($numericBoundFixture.name)")
            if ([string]::IsNullOrEmpty($numericBoundFixture.expected)) {
                if ($numericBoundErrors.Count -ne 0) { Add-ResolverFailure 'E_FIXTURE' $numericBoundFixture.name "exact numeric boundary was rejected: $([string]::Join(' | ', $numericBoundErrors))" }
            } else {
                $expectedBoundError = "E_SCHEMA fixture/$($numericBoundFixture.name): $($numericBoundFixture.expected)"
                if (@($numericBoundErrors | Where-Object { $_ -ceq $expectedBoundError }).Count -ne 1) { Add-ResolverFailure 'E_FIXTURE' $numericBoundFixture.name "expected exact bound error '$expectedBoundError'" }
            }
        } catch {
            Add-ResolverFailure 'E_FIXTURE' $numericBoundFixture.name 'schema numeric bound comparison threw a raw exception'
        }
    }
    $moduleNumberSchema = '{"type":"number"}' | ConvertFrom-Json
    $moduleNonfiniteErrors = @(Test-VgSchemaDocument -Document ([double]::PositiveInfinity) -Schema $moduleNumberSchema -DocumentId 'fixture/module-nonfinite')
    Assert-ResolverTrue 'module-schema-nonfinite' (@($moduleNonfiniteErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'module schema accepted non-finite JSON number'

    $validResult = Get-PortfolioResult $manifest $sources $organizations $identities
    $expectedShape = @('Ok', 'Errors', 'Hashes', 'ResolvedBundles', 'CoverageMatrix', 'ImpactIndex', 'StageInventories')
    Assert-ResolverTrue 'portfolio-valid-fixture' ([bool]$validResult.Ok -and @($validResult.Errors).Count -eq 0) "minimal fixture failed: $([string]::Join(' | ', @($validResult.Errors)))"
    Assert-ResolverTrue 'portfolio-exact-return-shape' (Test-OrdinalSequence @($validResult.PSObject.Properties.Name) $expectedShape) 'Test-VgComparisonPortfolio return shape changed'
    Assert-ResolverTrue 'portfolio-return-types' ($validResult.Ok -is [bool] -and $validResult.Errors -is [array] -and $validResult.CoverageMatrix -is [array] -and $validResult.Hashes -is [System.Collections.IDictionary] -and $validResult.ResolvedBundles -is [System.Collections.IDictionary] -and $validResult.ImpactIndex -is [System.Collections.IDictionary] -and $validResult.StageInventories -is [System.Collections.IDictionary]) 'portfolio result property types changed'
    foreach ($portfolioError in @($validResult.Errors)) {
        Assert-ResolverTrue 'error-format' ($portfolioError -cmatch '^E_[A-Z0-9_]+ [^:]+: .+$') "invalid validator error format '$portfolioError'"
    }

    $fixturePath = 'compare/fixture-alpha-vs-beta'
    $resolvedBundle = $validResult.ResolvedBundles[$fixturePath]
    $sixLensManifest = Copy-FixtureObject $manifest
    foreach ($lensSuffix in @('fourth', 'fifth', 'sixth')) {
        $additionalLens = Copy-FixtureObject $sixLensManifest.pages[0].traveler_lenses[0]
        $additionalLens.lens_id = "lens-$lensSuffix"
        $additionalLens.traveler = "$lensSuffix traveler profile"
        $sixLensManifest.pages[0].traveler_lenses = @($sixLensManifest.pages[0].traveler_lenses) + @($additionalLens)
    }
    $sixLensSchemaErrors = @(Test-VgSchemaDocument -Document $sixLensManifest -Schema $Schema -DefinitionName 'fixtureManifest' -DocumentId 'fixture/six-lenses')
    Assert-ResolverTrue 'traveler-lens-max-five-schema' (@($sixLensSchemaErrors | Where-Object { $_.StartsWith('E_SCHEMA ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'comparisonPage schema accepted six traveler lenses'
    Assert-PortfolioError 'traveler-lens-max-five-resolver' $sixLensManifest $sources $organizations $identities 'E_SCHEMA'

    $bundleRejectingSchema = Copy-FixtureObject $Schema
    $bundleRejectingDefinition = Get-Property (Get-Property $bundleRejectingSchema '$defs') 'resolvedBundleV2'
    $bundleRejectingDefinition.required = @($bundleRejectingDefinition.required) + @('fixture_generated_guard')
    $bundleRejectingResult = Test-VgComparisonPortfolio -Manifest $manifest -Sources $sources -Organizations $organizations -Identities $identities -Schema $bundleRejectingSchema -Profile Fixture -AsOfDate $EvaluationDate
    Assert-ResolverTrue 'generated-bundle-schema-validation' (@($bundleRejectingResult.Errors | Where-Object { $_.StartsWith("E_SCHEMA $fixturePath bundle`:", [System.StringComparison]::Ordinal) }).Count -gt 0) 'resolver accepted a generated bundle that failed resolvedBundleV2'
    $script:resolverFixtureChecks++
    try {
        [void](New-VgComparisonArtifact -Manifest $manifest -Sources $sources -Organizations $organizations -Identities $identities -Schema $bundleRejectingSchema -Profile Fixture -AsOfDate $EvaluationDate)
        Add-ResolverFailure 'E_FIXTURE' 'generated-bundle-artifact-fail-closed' 'artifact construction accepted an invalid generated bundle'
    } catch {
        if (-not $_.Exception.Message.StartsWith("E_SCHEMA $fixturePath bundle`:", [System.StringComparison]::Ordinal)) {
            Add-ResolverFailure 'E_FIXTURE' 'generated-bundle-artifact-fail-closed' "unexpected rejection '$($_.Exception.Message)'"
        }
    }
    Assert-ResolverTrue 'resolved-bundle-schema' (@(Test-VgSchemaDocument -Document $resolvedBundle -Schema $Schema -DefinitionName 'resolvedBundleV2' -DocumentId $fixturePath).Count -eq 0) 'resolved bundle does not satisfy resolvedBundleV2'
    Assert-ResolverTrue 'unused-rule-not-rendered' ((ConvertTo-VgCanonicalJson -Value $resolvedBundle) -cnotmatch 'rule-unused') 'unused rule catalog entry leaked into the bundle'
    foreach ($impactEntry in $validResult.ImpactIndex.Values) {
        Assert-ResolverTrue 'impact-index-ordinal-claims' (Test-OrdinalSequence @($impactEntry.claim_ids) @($impactEntry.claim_ids | Sort-Object -CaseSensitive)) "claim_ids are not ordinal-sorted for $($impactEntry.source_id)"
        Assert-ResolverTrue 'impact-index-ordinal-axes' (Test-OrdinalSequence @($impactEntry.axis_ids) @($impactEntry.axis_ids | Sort-Object -CaseSensitive)) "axis_ids are not ordinal-sorted for $($impactEntry.source_id)"
        Assert-ResolverTrue 'impact-index-ordinal-outcomes' (Test-OrdinalSequence @($impactEntry.outcome_ids) @($impactEntry.outcome_ids | Sort-Object -CaseSensitive)) "outcome_ids are not ordinal-sorted for $($impactEntry.source_id)"
    }

    foreach ($freshnessFixture in @(
        [ordered]@{ name = 'freshness-live-30-current'; source_index = 2; age_days = 30; expected_state = 'current' }
        [ordered]@{ name = 'freshness-live-31-stale'; source_index = 2; age_days = 31; expected_state = 'stale' }
        [ordered]@{ name = 'freshness-current-180-current'; source_index = 0; age_days = 180; expected_state = 'current' }
        [ordered]@{ name = 'freshness-current-181-stale'; source_index = 0; age_days = 181; expected_state = 'stale' }
        [ordered]@{ name = 'freshness-stable-730-current'; source_index = 7; age_days = 730; expected_state = 'current' }
        [ordered]@{ name = 'freshness-stable-731-stale'; source_index = 7; age_days = 731; expected_state = 'stale' }
    )) {
        $boundarySources = Copy-FixtureObject $sources
        $boundarySource = $boundarySources.sources[$freshnessFixture.source_index]
        $boundarySource.checked_on = $EvaluationDate.Date.AddDays(-[int]$freshnessFixture.age_days).ToString('yyyy-MM-dd', [System.Globalization.CultureInfo]::InvariantCulture)
        $boundaryResult = Get-PortfolioResult $manifest $boundarySources $organizations $identities
        $boundaryRow = @($boundaryResult.CoverageMatrix | Where-Object { $_.source_id -ceq $boundarySource.source_id } | Select-Object -First 1)
        Assert-ResolverTrue $freshnessFixture.name ($boundaryRow.Count -eq 1 -and $boundaryRow[0].freshness_state -ceq $freshnessFixture.expected_state) "expected $($freshnessFixture.expected_state) at age $($freshnessFixture.age_days) days"
    }

    $publisherMismatchSources = Copy-FixtureObject $sources
    $publisherMismatchSources.sources[0].publisher_id = 'pub-beta-national'
    Assert-PortfolioError 'publisher-organization-bijection' $manifest $publisherMismatchSources $organizations $identities 'E_PUBLISHER'
    $aliasCanonicalSources = Copy-FixtureObject $sources
    $aliasCanonicalSources.sources[0].canonical_domain = 'national-alpha-alias.example.vn'
    Assert-PortfolioError 'source-canonical-domain-must-match-publisher-canonical-domain' $manifest $aliasCanonicalSources $organizations $identities 'E_PUBLISHER'
    $aliasUrlSources = Copy-FixtureObject $sources
    $aliasUrlSources.sources[0].url = 'https://national-alpha-alias.example.vn/access'
    Assert-NoPortfolioError 'source-url-may-use-registered-publisher-alias' $manifest $aliasUrlSources $organizations $identities 'E_PUBLISHER'
    $duplicateUrlSources = Copy-FixtureObject $sources
    $duplicateUrlSources.sources[1].publisher_id = $duplicateUrlSources.sources[0].publisher_id
    $duplicateUrlSources.sources[1].publisher_name = $duplicateUrlSources.sources[0].publisher_name
    $duplicateUrlSources.sources[1].canonical_domain = $duplicateUrlSources.sources[0].canonical_domain
    $duplicateUrlSources.sources[1].url = $duplicateUrlSources.sources[0].url
    Assert-PortfolioError 'source-url-unique-across-source-ids' $manifest $duplicateUrlSources $organizations $identities 'E_SOURCE'
    $titleMismatchSources = Copy-FixtureObject $sources
    $titleMismatchSources.sources[0].expected_title = 'Different source title'
    Assert-PortfolioError 'source-expected-title-contract' $manifest $titleMismatchSources $organizations $identities 'E_SOURCE'
    $catastrophicTitleSources = Copy-FixtureObject $sources
    $catastrophicTitleSource = $catastrophicTitleSources.sources[2]
    $catastrophicTitleSource.title = ('a' * 26) + '!'
    $catastrophicTitleSource.expected_title = '^(a+)+$'
    $catastrophicStopwatch = [System.Diagnostics.Stopwatch]::StartNew()
    try {
        $catastrophicTitleResult = Get-PortfolioResult $manifest $catastrophicTitleSources $organizations $identities
        $catastrophicStopwatch.Stop()
        $expectedTimeoutError = "E_SOURCE $($catastrophicTitleSource.source_id): expected_title pattern timed out"
        Assert-ResolverTrue 'source-expected-title-pattern-timeout-error' (@($catastrophicTitleResult.Errors | Where-Object { $_ -ceq $expectedTimeoutError }).Count -eq 1) 'catastrophic expected_title pattern did not return the exact timeout error'
        Assert-ResolverTrue 'source-expected-title-pattern-timeout-bound' ($catastrophicStopwatch.Elapsed -lt [timespan]::FromSeconds(30)) "catastrophic expected_title pattern took $([math]::Round($catastrophicStopwatch.Elapsed.TotalMilliseconds)) ms"
    } catch {
        $catastrophicStopwatch.Stop()
        Add-ResolverFailure 'E_FIXTURE' 'source-expected-title-pattern-timeout' 'catastrophic expected_title pattern threw instead of returning E_SOURCE'
    }
    $invalidTitlePatternSources = Copy-FixtureObject $sources
    $invalidTitlePatternSource = $invalidTitlePatternSources.sources[2]
    $invalidTitlePatternSource.expected_title = '['
    $invalidTitlePatternResult = Get-PortfolioResult $manifest $invalidTitlePatternSources $organizations $identities
    Assert-ResolverTrue 'source-expected-title-invalid-pattern-error' (@($invalidTitlePatternResult.Errors | Where-Object { $_ -ceq "E_SOURCE $($invalidTitlePatternSource.source_id): expected_title pattern is invalid" }).Count -eq 1) 'invalid expected_title pattern did not return the exact E_SOURCE error'
    $orderedInvalidSources = Copy-FixtureObject $sources
    $orderedInvalidSources.sources[0].expected_title = 'Mismatch Alpha title'
    $orderedInvalidSources.sources[4].expected_title = 'Mismatch Beta title'
    $orderedInvalidResult = Get-PortfolioResult $manifest $orderedInvalidSources $organizations $identities
    $reversedInvalidSources = Copy-FixtureObject $orderedInvalidSources
    [array]::Reverse($reversedInvalidSources.sources)
    $reversedInvalidResult = Get-PortfolioResult $manifest $reversedInvalidSources $organizations $identities
    $orderedErrorMultiset = [string[]]@($orderedInvalidResult.Errors)
    $reversedErrorMultiset = [string[]]@($reversedInvalidResult.Errors)
    [array]::Sort($orderedErrorMultiset, [System.StringComparer]::Ordinal)
    [array]::Sort($reversedErrorMultiset, [System.StringComparer]::Ordinal)
    Assert-ResolverTrue 'portfolio-error-order-fixtures-equivalent' (Test-OrdinalSequence $orderedErrorMultiset $reversedErrorMultiset) 'invalid source permutations did not produce the same error multiset'
    Assert-ResolverTrue 'portfolio-error-order-input-independent' (Test-OrdinalSequence $orderedInvalidResult.Errors $reversedInvalidResult.Errors) 'equivalent invalid source permutations changed Errors order'
    Assert-ResolverTrue 'portfolio-error-order-ordinal' (Test-OrdinalSequence $orderedInvalidResult.Errors $orderedErrorMultiset) 'Errors are not returned in StringComparer.Ordinal order'
    $duplicateViolationSources = Copy-FixtureObject $sources
    $duplicateViolationSources.sources[1].source_id = $duplicateViolationSources.sources[0].source_id
    $duplicateViolationSources.sources[2].source_id = $duplicateViolationSources.sources[0].source_id
    $duplicateViolationResult = Get-PortfolioResult $manifest $duplicateViolationSources $organizations $identities
    Assert-ResolverTrue 'portfolio-error-order-preserves-duplicates' (@($duplicateViolationResult.Errors | Where-Object { $_ -ceq "E_SOURCE sources: duplicate source_id '$($duplicateViolationSources.sources[0].source_id)'" }).Count -eq 2) 'ordinal error sorting removed duplicate violations'
    $claimGroupMismatchSources = Copy-FixtureObject $sources
    $claimGroupMismatchSources.sources[0].claim_groups = @('experience_fit')
    Assert-PortfolioError 'source-claim-group-contract' $manifest $claimGroupMismatchSources $organizations $identities 'E_SOURCE'

    $aliasCollapsedSources = Copy-FixtureObject $sources
    $aliasCollapsedManifest = Copy-FixtureObject $manifest
    $aliasCollapsedManifest.pages[0].source_assignments[4].evidence_label = 'corroborating'
    $aliasCollapsedManifest.pages[0].source_assignments[6].decisive = $false
    foreach ($sourceItem in $aliasCollapsedSources.sources) {
        if ($sourceItem.source_id -ceq 'source-beta-national') {
            $sourceItem.organization_id = 'org-alpha'; $sourceItem.publisher_id = 'pub-alpha-national'; $sourceItem.publisher_name = 'Alpha National'; $sourceItem.canonical_domain = 'national-alpha.example.vn'
        }
        if ($sourceItem.source_id -ceq 'source-beta-operator') {
            $sourceItem.organization_id = 'org-alpha'; $sourceItem.publisher_id = 'pub-alpha-operator'; $sourceItem.publisher_name = 'Alpha Operator'; $sourceItem.canonical_domain = 'operator-alpha.example.vn'
        }
    }
    Assert-PortfolioError 'same-organization-publishers-do-not-corroborate' $aliasCollapsedManifest $aliasCollapsedSources $organizations $identities 'E_NEGATIVE'

    $duplicateEdgeManifest = Copy-FixtureObject $manifest
    $duplicateMapping = Copy-FixtureObject $duplicateEdgeManifest.pages[0].source_assignments[0].mappings[0]
    $duplicateEdgeManifest.pages[0].source_assignments[0].mappings += $duplicateMapping
    $duplicateResult = Get-PortfolioResult $duplicateEdgeManifest $sources $organizations $identities
    Assert-ResolverTrue 'edge-deduplication' ([bool]$duplicateResult.Ok) "duplicate edge changed validation: $([string]::Join(' | ', @($duplicateResult.Errors)))"

    $mixedScopeManifest = Copy-FixtureObject $manifest
    $mixedScopeManifest.pages[0].source_assignments[3].mappings += [ordered]@{ claim_id = 'claim-experience'; option_id = 'alpha'; axis_id = 'axis-experience'; outcome_id = 'outcome-alpha' }
    Assert-PortfolioError 'all-options-mixed-scope' $mixedScopeManifest $sources $organizations $identities 'E_SCOPE'

    $fortyPercentManifest = Copy-FixtureObject $manifest
    foreach ($assignmentIndex in @(1, 7)) {
        foreach ($mapping in $fortyPercentManifest.pages[0].source_assignments[$assignmentIndex].mappings) { $mapping.option_id = 'all_options' }
    }
    Assert-NoPortfolioError 'two-way-balance-exact-forty-percent' $fortyPercentManifest $sources $organizations $identities 'E_BALANCE'
    $belowFortyManifest = Copy-FixtureObject $fortyPercentManifest
    foreach ($mapping in $belowFortyManifest.pages[0].source_assignments[6].mappings) { $mapping.option_id = 'all_options' }
    Assert-PortfolioError 'two-way-balance-below-forty-percent' $belowFortyManifest $sources $organizations $identities 'E_BALANCE'

    $threeWayManifest = Copy-FixtureObject $manifest
    $threeWayManifest.pages[0].options += [ordered]@{ option_id = 'gamma'; label = 'Gamma'; summary = 'A third comparison option.' }
    foreach ($claim in $threeWayManifest.pages[0].claims) { $claim.option_ids += 'gamma' }
    foreach ($axis in $threeWayManifest.pages[0].axes) {
        $axis.option_ids += 'gamma'
        $axis.assessments += [ordered]@{ option_id = 'gamma'; outcome = 'Gamma provides a third trade-off.' }
    }
    $threeWayOptions = @('alpha', 'alpha', 'alpha', 'beta', 'beta', 'beta', 'gamma', 'gamma')
    for ($assignmentIndex = 0; $assignmentIndex -lt 8; $assignmentIndex++) {
        foreach ($mapping in $threeWayManifest.pages[0].source_assignments[$assignmentIndex].mappings) { $mapping.option_id = $threeWayOptions[$assignmentIndex] }
    }
    Assert-NoPortfolioError 'three-way-balance-exact-twenty-five-percent' $threeWayManifest $sources $organizations $identities 'E_BALANCE'
    $belowTwentyFiveManifest = Copy-FixtureObject $threeWayManifest
    foreach ($mapping in $belowTwentyFiveManifest.pages[0].source_assignments[7].mappings) { $mapping.option_id = 'beta' }
    Assert-PortfolioError 'three-way-balance-below-twenty-five-percent' $belowTwentyFiveManifest $sources $organizations $identities 'E_BALANCE'

    $domainSources = Copy-FixtureObject $sources
    $domainOrganizations = Copy-FixtureObject $organizations
    $domainManifest = Copy-FixtureObject $manifest
    foreach ($newSourceNumber in 9..10) {
        $newSource = Copy-FixtureObject $domainSources.sources[5]
        $newSource.source_id = "source-extra-$newSourceNumber"
        $domainSources.sources += $newSource
        $domainManifest.pages[0].source_assignments += [ordered]@{
            source_id = $newSource.source_id
            evidence_label = 'corroborating'
            establishes = "Extra balance source $newSourceNumber."
            decisive = $false
            mappings = @([ordered]@{ claim_id = 'claim-experience'; option_id = if ($newSourceNumber -eq 9) { 'alpha' } else { 'beta' }; axis_id = 'axis-experience'; outcome_id = if ($newSourceNumber -eq 9) { 'outcome-alpha' } else { 'outcome-beta' } })
        }
    }
    foreach ($sourceIndex in 0..3) {
        $domainSources.sources[$sourceIndex].organization_id = 'org-alpha'
        $domainSources.sources[$sourceIndex].publisher_id = 'pub-alpha-national'
        $domainSources.sources[$sourceIndex].publisher_name = 'Alpha National'
        $domainSources.sources[$sourceIndex].canonical_domain = 'national-alpha.example.vn'
    }
    Assert-NoPortfolioError 'domain-cap-exact-forty-percent' $domainManifest $domainSources $domainOrganizations $identities 'E_DOMAIN'
    $domainSources.sources[4].organization_id = 'org-alpha'
    $domainSources.sources[4].publisher_id = 'pub-alpha-national'
    $domainSources.sources[4].publisher_name = 'Alpha National'
    $domainSources.sources[4].canonical_domain = 'national-alpha.example.vn'
    Assert-PortfolioError 'domain-cap-over-forty-percent' $domainManifest $domainSources $domainOrganizations $identities 'E_DOMAIN'

    $missingAxisSide = Copy-FixtureObject $manifest
    $missingAxisSide.pages[0].axes[0].assessments = @($missingAxisSide.pages[0].axes[0].assessments | Select-Object -First 1)
    Assert-PortfolioError 'decisive-axis-missing-side' $missingAxisSide $sources $organizations $identities 'E_AXIS'

    $orphanManifest = Copy-FixtureObject $manifest
    $orphanManifest.pages[0].source_assignments[0].mappings[0].claim_id = 'claim-orphan'
    Assert-PortfolioError 'provenance-orphan-claim' $orphanManifest $sources $organizations $identities 'E_PROVENANCE'

    $cycleManifest = Copy-FixtureObject $manifest
    $cycleManifest.pages[0].claims[0].claim_id = 'axis-access'
    $cycleManifest.pages[0].axes[0].claim_ids = @('axis-access')
    foreach ($assignment in $cycleManifest.pages[0].source_assignments) {
        foreach ($mapping in $assignment.mappings) { if ($mapping.claim_id -ceq 'claim-access') { $mapping.claim_id = 'axis-access' } }
    }
    foreach ($ruleItem in $cycleManifest.pages[0].rule_catalog) {
        for ($claimIndex = 0; $claimIndex -lt $ruleItem.claim_ids.Count; $claimIndex++) { if ($ruleItem.claim_ids[$claimIndex] -ceq 'claim-access') { $ruleItem.claim_ids[$claimIndex] = 'axis-access' } }
    }
    Assert-PortfolioError 'provenance-cycle' $cycleManifest $sources $organizations $identities 'E_CYCLE'

    $badRuleOrder = Copy-FixtureObject $manifest
    $badRuleOrder.pages[0].traveler_lenses[2].rule_path = @('rule-tie-combine', 'rule-preference-combine')
    Assert-PortfolioError 'rule-path-order' $badRuleOrder $sources $organizations $identities 'E_RULE'
    $badContext = Copy-FixtureObject $manifest
    $badContext.pages[0].traveler_lenses[1].context_tags = @('short-time')
    Assert-PortfolioError 'rule-context-intersection' $badContext $sources $organizations $identities 'E_RULE'
    $badTerminal = Copy-FixtureObject $manifest
    $badTerminal.pages[0].traveler_lenses[1].outcome_id = 'outcome-alpha'
    Assert-PortfolioError 'rule-terminal-outcome' $badTerminal $sources $organizations $identities 'E_RULE'
    $ruleClaimAxisMismatch = Copy-FixtureObject $manifest
    $ruleClaimAxisMismatch.pages[0].rule_catalog[0].axis_ids = @('axis-experience')
    Assert-PortfolioExactError 'rule-claim-axis-relationship' $ruleClaimAxisMismatch $sources $organizations $identities "E_RULE $fixturePath rule=rule-hard-alpha: claim_id 'claim-access' is not declared by axis_id 'axis-experience'"
    $ruleOptionScopeMismatch = Copy-FixtureObject $manifest
    $ruleOptionScopeMismatch.pages[0].claims[2].option_ids = @('alpha')
    Assert-PortfolioExactError 'rule-option-scope-relationship' $ruleOptionScopeMismatch $sources $organizations $identities "E_RULE $fixturePath rule=rule-preference-beta: option_id 'beta' is outside claim_id 'claim-experience' scope"
    $ruleOutcomeScopeMismatch = Copy-FixtureObject $manifest
    $ruleOutcomeScopeMismatch.pages[0].rule_catalog[1].option_ids = @('alpha')
    Assert-PortfolioExactError 'rule-outcome-scope-relationship' $ruleOutcomeScopeMismatch $sources $organizations $identities "E_RULE $fixturePath rule=rule-preference-beta: option outcome 'outcome-beta' must use only winner_option_id 'beta'"
    $ruleEvidenceEdgeMismatch = Copy-FixtureObject $manifest
    $ruleEvidenceEdgeMismatch.pages[0].axes[2].claim_ids += @('claim-access')
    $ruleEvidenceEdgeMismatch.pages[0].rule_catalog[0].axis_ids = @('axis-experience')
    Assert-PortfolioExactError 'rule-evidence-edge-relationship' $ruleEvidenceEdgeMismatch $sources $organizations $identities "E_RULE $fixturePath rule=rule-hard-alpha: no complete option-specific evidence tuple matches the rule scope and outcome_id 'outcome-alpha'"
    $wrongOutcomeTuple = Copy-FixtureObject $manifest
    $wrongOutcomeTuple.pages[0].source_assignments[0].mappings = @($wrongOutcomeTuple.pages[0].source_assignments[0].mappings | Where-Object { [string](Get-Property $_ 'outcome_id') -cne 'outcome-combine' })
    $wrongOutcomeTuple.pages[0].rule_catalog[0].outcome_id = 'outcome-combine'
    $wrongOutcomeTuple.pages[0].traveler_lenses[0].outcome_id = 'outcome-combine'
    $wrongOutcomeTuple.pages[0].traveler_lenses[2].rule_path = @('rule-preference-combine')
    Assert-PortfolioExactError 'rule-evidence-tuple-requires-exact-outcome' $wrongOutcomeTuple $sources $organizations $identities "E_RULE $fixturePath rule=rule-hard-alpha: no complete option-specific evidence tuple matches the rule scope and outcome_id 'outcome-combine'"
    $pairedRulePaths = Copy-FixtureObject $manifest
    $pairedRulePaths.pages[0].rule_catalog[2].claim_ids = @('claim-timing', 'claim-access')
    $pairedRulePaths.pages[0].rule_catalog[2].axis_ids = @('axis-timing', 'axis-access')
    $pairedRuleResult = Get-PortfolioResult $pairedRulePaths $sources $organizations $identities
    Assert-ResolverTrue 'rule-evidence-allows-paired-claim-axis-paths' ([bool]$pairedRuleResult.Ok -and @($pairedRuleResult.Errors).Count -eq 0) "valid paired rule paths failed full portfolio validation: $([string]::Join(' | ', @($pairedRuleResult.Errors)))"
    $missingRuleClaimTuple = Copy-FixtureObject $pairedRulePaths
    $missingRuleClaimTuple.pages[0].claims[2].option_ids = @('alpha', 'beta')
    $missingRuleClaimTuple.pages[0].axes[1].claim_ids += @('claim-experience')
    $missingRuleClaimTuple.pages[0].rule_catalog[2].claim_ids += @('claim-experience')
    Assert-PortfolioExactError 'rule-evidence-requires-every-claim' $missingRuleClaimTuple $sources $organizations $identities "E_RULE $fixturePath rule=rule-preference-combine: no complete evidence tuple covers claim_id 'claim-experience' within the rule scope and outcome_id 'outcome-combine'"
    $missingRuleAxisTuple = Copy-FixtureObject $pairedRulePaths
    $missingRuleAxisTuple.pages[0].axes[2].claim_ids += @('claim-timing')
    $missingRuleAxisTuple.pages[0].rule_catalog[2].axis_ids += @('axis-experience')
    Assert-PortfolioExactError 'rule-evidence-requires-every-axis' $missingRuleAxisTuple $sources $organizations $identities "E_RULE $fixturePath rule=rule-preference-combine: no complete evidence tuple covers axis_id 'axis-experience' within the rule scope and outcome_id 'outcome-combine'"
    $missingRuleOptionTuple = Copy-FixtureObject $manifest
    $missingRuleOptionTuple.pages[0].source_assignments[4].mappings = @($missingRuleOptionTuple.pages[0].source_assignments[4].mappings | Where-Object { [string](Get-Property $_ 'outcome_id') -cne 'outcome-combine' })
    $missingRuleOptionTuple.pages[0].source_assignments[6].mappings[0].outcome_id = 'outcome-beta'
    Assert-PortfolioExactError 'rule-evidence-requires-every-option' $missingRuleOptionTuple $sources $organizations $identities "E_RULE $fixturePath rule=rule-preference-combine: no complete evidence tuple covers option_id 'beta' within the rule scope and outcome_id 'outcome-combine'"
    $backgroundOnlyRuleOption = Copy-FixtureObject $manifest
    $backgroundOnlyRuleOption.pages[0].source_assignments[6].mappings[0].outcome_id = 'outcome-beta'
    $backgroundOnlyRuleOption.pages[0].source_assignments[3].mappings[0].claim_id = 'claim-timing'
    $backgroundOnlyRuleOption.pages[0].source_assignments[3].mappings[0].axis_id = 'axis-timing'
    $backgroundOnlySources = Copy-FixtureObject $sources
    $backgroundOnlySources.sources[3].claim_groups += @('timing_duration')
    Assert-PortfolioExactError 'rule-evidence-rejects-all-options-only-coverage' $backgroundOnlyRuleOption $backgroundOnlySources $organizations $identities "E_RULE $fixturePath rule=rule-preference-combine: no complete evidence tuple covers option_id 'beta' within the rule scope and outcome_id 'outcome-combine'"
    $continuedHardConstraint = Copy-FixtureObject $manifest
    $continuedHardConstraint.pages[0].traveler_lenses[0].context_tags = @('short-time', 'unused-context')
    $continuedHardConstraint.pages[0].traveler_lenses[0].rule_path = @('rule-hard-alpha', 'rule-unused')
    Assert-PortfolioError 'rule-terminal-hard-constraint-stops-replay' $continuedHardConstraint $sources $organizations $identities 'E_RULE'
    $continuedHardToDifferentOutcome = Copy-FixtureObject $manifest
    $continuedHardToDifferentOutcome.pages[0].traveler_lenses[0].context_tags = @('short-time', 'slow-travel')
    $continuedHardToDifferentOutcome.pages[0].traveler_lenses[0].rule_path = @('rule-hard-alpha', 'rule-preference-beta')
    $continuedHardToDifferentOutcome.pages[0].traveler_lenses[0].outcome_id = 'outcome-beta'
    Assert-PortfolioError 'rule-hard-constraint-cannot-continue-to-different-outcome' $continuedHardToDifferentOutcome $sources $organizations $identities 'E_RULE'
    $hardOutcomeMismatch = Copy-FixtureObject $manifest
    $hardOutcomeMismatch.pages[0].traveler_lenses[0].outcome_id = 'outcome-beta'
    Assert-PortfolioError 'rule-hard-constraint-must-match-lens-outcome' $hardOutcomeMismatch $sources $organizations $identities 'E_RULE'
    Assert-NoPortfolioError 'rule-preference-only-path-remains-valid' $manifest $sources $organizations $identities 'E_RULE'
    Assert-NoPortfolioError 'rule-preference-tie-breaker-path-remains-valid' $manifest $sources $organizations $identities 'E_RULE'
    $universalLensOutcome = Copy-FixtureObject $manifest
    foreach ($lensIndex in @(1, 2)) {
        $universalLensOutcome.pages[0].traveler_lenses[$lensIndex].context_tags = @('unused-context')
        $universalLensOutcome.pages[0].traveler_lenses[$lensIndex].rule_path = @('rule-unused')
        $universalLensOutcome.pages[0].traveler_lenses[$lensIndex].outcome_id = 'outcome-alpha'
    }
    Assert-PortfolioError 'traveler-lenses-reject-universal-outcome' $universalLensOutcome $sources $organizations $identities 'E_RULE'
    $missingMaterialLens = Copy-FixtureObject $manifest
    [void]$missingMaterialLens.pages[0].traveler_lenses[1].Remove('trade_off')
    [void]$missingMaterialLens.pages[0].traveler_lenses[1].Remove('reversal_condition')
    Assert-PortfolioError 'rule-material-trade-off-schema' $missingMaterialLens $sources $organizations $identities 'E_SCHEMA'
    Assert-PortfolioError 'rule-material-trade-off-semantic' $missingMaterialLens $sources $organizations $identities 'E_RULE'
    $reversalOnlyLens = Copy-FixtureObject $manifest
    [void]$reversalOnlyLens.pages[0].traveler_lenses[1].Remove('trade_off')
    $reversalOnlyResult = Get-PortfolioResult $reversalOnlyLens $sources $organizations $identities
    Assert-ResolverTrue 'rule-allows-reversal-without-trade-off' ([bool]$reversalOnlyResult.Ok -and @($reversalOnlyResult.Errors).Count -eq 0) "reversal-only lens failed full portfolio validation: $([string]::Join(' | ', @($reversalOnlyResult.Errors)))"
    $resolvedReversalOnlyLens = @($reversalOnlyResult.ResolvedBundles[$fixturePath].traveler_lenses | Where-Object { $_.lens_id -ceq 'lens-slow' })
    Assert-ResolverTrue 'resolved-reversal-only-lens-schema' ($resolvedReversalOnlyLens.Count -eq 1 -and -not (Test-HasProperty $resolvedReversalOnlyLens[0] 'trade_off') -and [string](Get-Property $resolvedReversalOnlyLens[0] 'reversal_condition') -ceq 'Use Alpha when the return window tightens.') 'resolved reversal-only lens did not omit trade_off and preserve reversal_condition'
    $duplicateLensId = Copy-FixtureObject $manifest
    $duplicateLensId.pages[0].traveler_lenses[1].lens_id = $duplicateLensId.pages[0].traveler_lenses[0].lens_id
    Assert-PortfolioError 'traveler-lens-id-unique' $duplicateLensId $sources $organizations $identities 'E_RULE'
    foreach ($scoringField in @('weight', 'priority', 'score')) {
        $scoredManifest = Copy-FixtureObject $manifest
        $scoredManifest.pages[0].rule_catalog[0][$scoringField] = 1
        Assert-PortfolioError "rule-reject-$scoringField" $scoredManifest $sources $organizations $identities 'E_RULE'
    }

    $sameIdentityManifest = Copy-FixtureObject $manifest
    $sameIdentityManifest.pages[0].editorial.reviewed_by_identity_id = 'fixture-author'
    Assert-PortfolioError 'distinct-author-reviewer-identities' $sameIdentityManifest $sources $organizations $identities 'E_IDENTITY'
    $missingNotApplicable = Copy-FixtureObject $manifest
    $missingNotApplicable.pages[0].claims[1].option_ids = @('alpha')
    Assert-PortfolioError 'explicit-not-applicable-reason' $missingNotApplicable $sources $organizations $identities 'E_NOT_APPLICABLE'
    $stalePrimarySources = Copy-FixtureObject $sources
    $stalePrimarySources.sources[6].checked_on = '2020-01-01'
    Assert-PortfolioError 'current-primary-timing' $manifest $stalePrimarySources $organizations $identities 'E_FRESHNESS'
    $staleExperienceSources = Copy-FixtureObject $sources
    $staleExperienceSources.sources[5].checked_on = '2020-01-01'
    Assert-PortfolioError 'experience-fit-requires-current-organizations' $manifest $staleExperienceSources $organizations $identities 'E_EXPERIENCE'
    $nationalExperienceSources = Copy-FixtureObject $sources
    $nationalExperienceSources.sources[2].source_class = 'national_official'
    $nationalExperienceSources.sources[5].source_class = 'national_official'
    Assert-PortfolioError 'experience-fit-requires-local-operational-or-independent-source' $manifest $nationalExperienceSources $organizations $identities 'E_EXPERIENCE'

    $negativePrimaryManifest = Copy-FixtureObject $manifest
    $negativePrimaryManifest.pages[0].source_assignments[1].decisive = $false
    $negativePrimaryManifest.pages[0].source_assignments[6].decisive = $false
    Assert-NoPortfolioError 'negative-outcome-allows-responsible-current-primary' $negativePrimaryManifest $sources $organizations $identities 'E_NEGATIVE'
    $liveCheckPrimaryManifest = Copy-FixtureObject $negativePrimaryManifest
    $liveCheckPrimaryManifest.pages[0].source_assignments[6].decisive = $true
    $liveCheckPrimaryManifest.pages[0].outcomes[1].settled = $true
    $liveCheckPrimaryManifest.pages[0].outcomes[1].live_check_required = $true
    Assert-NoPortfolioError 'settled-live-check-allows-sole-responsible-current-primary' $liveCheckPrimaryManifest $sources $organizations $identities 'E_SETTLED'
    $staleNegativeManifest = Copy-FixtureObject $manifest
    $staleNegativeSources = Copy-FixtureObject $sources
    $staleNegativeManifest.pages[0].source_assignments[6].decisive = $false
    $staleNegativeManifest.pages[0].source_assignments[4].evidence_label = 'corroborating'
    $staleNegativeSources.sources[4].checked_on = '2020-01-01'
    Assert-PortfolioError 'negative-outcome-rejects-stale-corroboration' $staleNegativeManifest $staleNegativeSources $organizations $identities 'E_NEGATIVE'
    $corroboratedNegativeManifest = Copy-FixtureObject $manifest
    $corroboratedNegativeManifest.pages[0].source_assignments[6].decisive = $false
    $corroboratedNegativeManifest.pages[0].source_assignments[4].evidence_label = 'corroborating'
    Assert-NoPortfolioError 'negative-outcome-allows-two-current-corroborating-organizations' $corroboratedNegativeManifest $sources $organizations $identities 'E_NEGATIVE'
    $singleOrganizationSettledManifest = Copy-FixtureObject $manifest
    $singleOrganizationSettledManifest.pages[0].source_assignments[5].decisive = $false
    $singleOrganizationSettledManifest.pages[0].source_assignments[7].decisive = $false
    Assert-PortfolioError 'settled-winner-still-requires-two-organizations' $singleOrganizationSettledManifest $sources $organizations $identities 'E_SETTLED'
    $backgroundPrimaryManifest = Copy-FixtureObject $manifest
    $backgroundPrimarySources = Copy-FixtureObject $sources
    $backgroundPrimaryManifest.pages[0].source_assignments[0].evidence_label = 'corroborating'
    $backgroundPrimaryManifest.pages[0].source_assignments[3].evidence_label = 'primary'
    $backgroundPrimaryManifest.pages[0].source_assignments[3].mappings[0].claim_id = 'claim-access'
    $backgroundPrimaryManifest.pages[0].source_assignments[3].mappings[0].axis_id = 'axis-access'
    $backgroundPrimaryManifest.pages[0].source_assignments[3].mappings[0].outcome_id = 'outcome-alpha'
    $backgroundPrimarySources.sources[3].claim_groups = @('access_transport')
    Assert-PortfolioError 'background-primary-does-not-cover-option' $backgroundPrimaryManifest $backgroundPrimarySources $organizations $identities 'E_FRESHNESS'
    $expiredSettledSources = Copy-FixtureObject $sources
    $expiredSettledSources.sources[7].checked_on = $EvaluationDate.Date.AddDays(-731).ToString('yyyy-MM-dd', [System.Globalization.CultureInfo]::InvariantCulture)
    Assert-PortfolioError 'expired-settled-decisive-source' $manifest $expiredSettledSources $organizations $identities 'E_FRESHNESS'
    $incompleteLiveCheckManifest = Copy-FixtureObject $manifest
    $incompleteLiveCheckManifest.pages[0].source_assignments[7].evidence_label = 'live_check_required'
    Assert-PortfolioError 'expired-settled-needs-live-check-outcome' $incompleteLiveCheckManifest $expiredSettledSources $organizations $identities 'E_FRESHNESS'
    $completeLiveCheckManifest = Copy-FixtureObject $incompleteLiveCheckManifest
    $completeLiveCheckManifest.pages[0].outcomes[0].live_check_required = $true
    $completeLiveCheckResult = Get-PortfolioResult $completeLiveCheckManifest $expiredSettledSources $organizations $identities
    Assert-ResolverTrue 'expired-settled-live-check-exception' (@($completeLiveCheckResult.Errors | Where-Object { $_.StartsWith('E_FRESHNESS ', [System.StringComparison]::Ordinal) }).Count -eq 0) "complete live-check exception failed: $([string]::Join(' | ', @($completeLiveCheckResult.Errors)))"
    $visibleLiveCheckSource = @($completeLiveCheckResult.ResolvedBundles[$fixturePath].sources | Where-Object { $_.source_id -ceq 'source-beta-independent' })
    Assert-ResolverTrue 'live-check-preserves-checked-date-and-url' ($visibleLiveCheckSource.Count -eq 1 -and $visibleLiveCheckSource[0].checked_on -ceq $expiredSettledSources.sources[7].checked_on -and $visibleLiveCheckSource[0].url -ceq $expiredSettledSources.sources[7].url -and $visibleLiveCheckSource[0].evidence_label -ceq 'live_check_required' -and $visibleLiveCheckSource[0].freshness_state -ceq 'live_check_required') 'resolved live-check source did not preserve its checked date, direct URL, and visible label'
    $wrongLiveCheckCover = Copy-FixtureObject $completeLiveCheckManifest
    $wrongLiveCheckCover.pages[0].source_assignments[0].mappings[0].outcome_id = 'outcome-beta'
    $wrongLiveCheckCoverResult = Get-PortfolioResult $wrongLiveCheckCover $expiredSettledSources $organizations $identities
    Assert-ResolverTrue 'expired-settled-live-check-needs-exact-current-cover' (@($wrongLiveCheckCoverResult.Errors | Where-Object { $_.StartsWith("E_FRESHNESS $fixturePath outcome=outcome-alpha source=source-beta-independent`:", [System.StringComparison]::Ordinal) }).Count -gt 0) 'expired live-check source was accepted with current coverage for a different claim mapping'
    $missingLiveCheckCover = Copy-FixtureObject $completeLiveCheckManifest
    foreach ($assignmentIndex in @(0, 2, 5)) {
        foreach ($mapping in $missingLiveCheckCover.pages[0].source_assignments[$assignmentIndex].mappings) {
            if ($mapping.outcome_id -ceq 'outcome-alpha') { $mapping.outcome_id = 'outcome-beta' }
        }
    }
    $missingLiveCheckCoverResult = Get-PortfolioResult $missingLiveCheckCover $expiredSettledSources $organizations $identities
    Assert-ResolverTrue 'expired-settled-live-check-needs-current-cover' (@($missingLiveCheckCoverResult.Errors | Where-Object { $_.StartsWith("E_FRESHNESS $fixturePath outcome=outcome-alpha source=source-beta-independent`:", [System.StringComparison]::Ordinal) }).Count -gt 0) 'expired live-check source was accepted without current same-outcome coverage'
    $englishOnlySources = Copy-FixtureObject $sources
    foreach ($sourceItem in $englishOnlySources.sources) { $sourceItem.language = 'en' }
    Assert-PortfolioError 'locality-language-coverage' $manifest $englishOnlySources $organizations $identities 'E_LOCALITY'
    $missingLocalityManifest = Copy-FixtureObject $manifest
    $missingLocalityManifest.pages[0].localities += 'fixture-secondary'
    Assert-PortfolioError 'every-page-locality-needs-non-background-evidence' $missingLocalityManifest $sources $organizations $identities 'E_LOCALITY'
    $decisiveBackground = Copy-FixtureObject $manifest
    $decisiveBackground.pages[0].source_assignments[3].decisive = $true
    Assert-PortfolioError 'background-source-not-decisive' $decisiveBackground $sources $organizations $identities 'E_BACKGROUND'
    $backgroundIndependence = Copy-FixtureObject $manifest
    $backgroundIndependence.pages[0].source_assignments[0].mappings += [ordered]@{ claim_id = 'claim-access'; option_id = 'beta'; axis_id = 'axis-access'; outcome_id = 'outcome-combine' }
    $backgroundIndependence.pages[0].source_assignments[2].mappings += [ordered]@{ claim_id = 'claim-timing'; option_id = 'beta'; axis_id = 'axis-timing'; outcome_id = 'outcome-combine' }
    $backgroundIndependence.pages[0].source_assignments[4].mappings[1].outcome_id = 'outcome-alpha'
    $backgroundIndependence.pages[0].source_assignments[6].mappings[0].outcome_id = 'outcome-alpha'
    Assert-PortfolioError 'background-source-does-not-settle-outcome' $backgroundIndependence $sources $organizations $identities 'E_SETTLED'
    $duplicateRelatedRoute = Copy-FixtureObject $manifest
    $duplicateRelatedRoute.pages[0].related_routes[1].path = $duplicateRelatedRoute.pages[0].related_routes[0].path
    Assert-PortfolioError 'related-route-path-unique' $duplicateRelatedRoute $sources $organizations $identities 'E_ROUTE'
    $selfRelatedRoute = Copy-FixtureObject $manifest
    $selfRelatedRoute.pages[0].related_routes[0].path = $selfRelatedRoute.pages[0].path
    Assert-PortfolioError 'related-route-rejects-self-link' $selfRelatedRoute $sources $organizations $identities 'E_ROUTE'

    $shuffledSources = Copy-FixtureObject $sources
    [array]::Reverse($shuffledSources.sources)
    $shuffledManifest = Copy-FixtureObject $manifest
    [array]::Reverse($shuffledManifest.pages[0].source_assignments)
    $shuffledArtifact = New-VgComparisonArtifact -Manifest $shuffledManifest -Sources $shuffledSources -Organizations $organizations -Identities $identities -Schema $Schema -Profile Fixture -AsOfDate $EvaluationDate
    $orderedArtifact = New-VgComparisonArtifact -Manifest $manifest -Sources $sources -Organizations $organizations -Identities $identities -Schema $Schema -Profile Fixture -AsOfDate $EvaluationDate
    Assert-ResolverTrue 'artifact-shuffled-input-determinism' (Compare-VgArtifactDeterminism -First $orderedArtifact -Second $shuffledArtifact) 'shuffled registry/assignment input changed the deterministic artifact'
    Assert-ResolverTrue 'impact-index-shuffled-input-determinism' ((Get-VgSha256Hex -Value $orderedArtifact.impact_index) -ceq (Get-VgSha256Hex -Value $shuffledArtifact.impact_index)) 'shuffled input changed ImpactIndex'

    $refreshedSources = Copy-FixtureObject $sources
    $refreshedSources.sources[1].checked_on = '2026-08-03'
    $refreshedResult = Get-PortfolioResult $manifest $refreshedSources $organizations $identities
    Assert-ResolverTrue 'source-refresh-preserves-outcome' ((ConvertTo-VgCanonicalJson -Value $validResult.ResolvedBundles[$fixturePath].primary_decision) -ceq (ConvertTo-VgCanonicalJson -Value $refreshedResult.ResolvedBundles[$fixturePath].primary_decision)) 'source refresh mutated a reviewed outcome'

    $productionResult = Get-PortfolioResult $manifest $sources $organizations $identities 'Production'
    Assert-ResolverTrue 'fixture-fails-production-portfolio-gates' (-not $productionResult.Ok -and @($productionResult.Errors | Where-Object { $_.StartsWith('E_PORTFOLIO ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'fixture profile was accepted as a Production portfolio'
    $expectedProductionProfileError = 'E_PROFILE manifest: Production requires the Production manifest profile; fixtureManifest is not accepted'
    $shuffledProductionResult = Get-PortfolioResult $shuffledManifest $shuffledSources $organizations $identities 'Production'
    Assert-ResolverTrue 'fixture-rejected-by-production-profile' ($productionResult.Errors[0] -ceq $expectedProductionProfileError -and $shuffledProductionResult.Errors[0] -ceq $expectedProductionProfileError -and (Test-OrdinalSequence $productionResult.Errors $shuffledProductionResult.Errors)) 'Production profile rejection was not first and input-independent'
    $defaultProfileResult = Test-VgComparisonPortfolio -Manifest $manifest -Sources $sources -Organizations $organizations -Identities $identities -Schema $Schema -AsOfDate $EvaluationDate
    Assert-ResolverTrue 'default-profile-is-production' (@($defaultProfileResult.Errors | Where-Object { $_.StartsWith('E_PROFILE ', [System.StringComparison]::Ordinal) }).Count -gt 0) 'omitting -Profile did not use the Production discriminator'
    $storedProfileManifest = Copy-FixtureObject $manifest
    $storedProfileManifest.Profile = 'Fixture'
    Assert-PortfolioError 'stored-profile-field-is-forbidden' $storedProfileManifest $sources $organizations $identities 'E_SCHEMA'
    foreach ($geographicGroup in @('northern', 'central', 'south-central-coast', 'southern', 'island', 'nationwide')) {
        Assert-ResolverTrue "production-requires-$geographicGroup-geographic-group" (@($productionResult.Errors | Where-Object { $_.StartsWith("E_LOCALITY portfolio geographic_group=$geographicGroup`:", [System.StringComparison]::Ordinal) }).Count -gt 0) "Production did not enforce the fixed $geographicGroup geographic group"
    }
    $coveredGeographicSources = Copy-FixtureObject $sources
    foreach ($sourceItem in $coveredGeographicSources.sources) { $sourceItem.localities = @('fixture-province') }
    $coveredGeographicSources.sources[0].localities += @('hanoi', 'phu_quoc', 'north')
    $coveredGeographicSources.sources[1].localities += @('hanoi', 'phu_quoc')
    $coveredGeographicSources.sources[2].localities += @('da_nang', 'central')
    $coveredGeographicSources.sources[3].localities += 'da_nang'
    $coveredGeographicSources.sources[4].localities += @('nha_trang', 'south')
    $coveredGeographicSources.sources[5].localities += 'nha_trang'
    $coveredGeographicSources.sources[6].localities += @('da_nang', 'ho_chi_minh_city')
    $coveredGeographicSources.sources[7].localities += 'ho_chi_minh_city'
    $coveredGeographicResult = Get-PortfolioResult $manifest $coveredGeographicSources $organizations $identities 'Production'
    Assert-ResolverTrue 'production-fixed-geographic-groups-covered' (@($coveredGeographicResult.Errors | Where-Object { $_.StartsWith('E_LOCALITY portfolio geographic_group=', [System.StringComparison]::Ordinal) }).Count -eq 0) "complete fixed geographic groups were rejected: $([string]::Join(' | ', @($coveredGeographicResult.Errors)))"
    $script:resolverFixtureChecks++
    try {
        [void](New-VgComparisonArtifact -Manifest $manifest -Sources $sources -Organizations $organizations -Identities $identities -Schema $Schema -Profile Production -AsOfDate $EvaluationDate)
        Add-ResolverFailure 'E_FIXTURE' 'production-artifact-rejects-fixture' 'New-VgComparisonArtifact accepted a fixture manifest as Production'
    } catch {
        if ($_.Exception.Message -cne $expectedProductionProfileError) {
            Add-ResolverFailure 'E_FIXTURE' 'production-artifact-rejects-fixture' "unexpected production rejection '$($_.Exception.Message)'"
        }
    }

    return [ordered]@{ errors = @($script:resolverFixtureErrors); checks = $script:resolverFixtureChecks }
}

function Invoke-ApprovalContractValidation {
    param([Parameter(Mandatory = $true)]$Schema)

    $approvalErrors = @()
    $approvalChecks = 0
    $fixtureIdentityRelativePath = 'ops/comparison-rollout/fixtures/minimal/identities.json'
    $fixtureIdentityPath = Join-Path $repoRoot $fixtureIdentityRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $productionIdentityRelativePath = 'ops/comparison-rollout/identities.json'
    $productionIdentityPath = Join-Path $repoRoot $productionIdentityRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $phpLibraryRelativePath = 'ops/comparison-rollout-lib.php'
    $phpLibraryPath = Join-Path $repoRoot $phpLibraryRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $phpSignerRelativePath = 'ops/comparison-rollout-approve.php'
    $phpSignerPath = Join-Path $repoRoot $phpSignerRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)

    foreach ($requiredApprovalFile in @(
        [ordered]@{ Relative = $fixtureIdentityRelativePath; Path = $fixtureIdentityPath; Code = 'E_FILE' }
        [ordered]@{ Relative = $productionIdentityRelativePath; Path = $productionIdentityPath; Code = 'E_FILE' }
        [ordered]@{ Relative = $phpLibraryRelativePath; Path = $phpLibraryPath; Code = 'E_PHP' }
        [ordered]@{ Relative = $phpSignerRelativePath; Path = $phpSignerPath; Code = 'E_PHP' }
    )) {
        $approvalChecks++
        if (-not (Test-Path -LiteralPath $requiredApprovalFile.Path -PathType Leaf)) {
            $approvalErrors += "$($requiredApprovalFile.Code) $($requiredApprovalFile.Relative): required approval contract file is missing"
        }
    }

    if (Test-Path -LiteralPath $fixtureIdentityPath -PathType Leaf) {
        try {
            $fixtureIdentities = Read-VgJsonDocument -Path $fixtureIdentityPath
            $identitySchemaErrors = @(Test-VgSchemaDocument -Document $fixtureIdentities -Schema $Schema -DefinitionName 'identityRegistry' -DocumentId $fixtureIdentityRelativePath)
            $approvalChecks++
            if ($identitySchemaErrors.Count -gt 0) { $approvalErrors += $identitySchemaErrors }
            $missingWpUser = Copy-FixtureObject $fixtureIdentities
            [void]$missingWpUser.identities[0].Remove('wp_user_id')
            $approvalChecks++
            if (@(Test-VgSchemaDocument -Document $missingWpUser -Schema $Schema -DefinitionName 'identityRegistry' -DocumentId 'fixture/identity-missing-wp-user').Count -eq 0) {
                $approvalErrors += 'E_SCHEMA fixture/identity-missing-wp-user: identityRegistry accepted a missing wp_user_id'
            }
        } catch {
            $approvalErrors += $_.Exception.Message
        }
    }

    if (Test-Path -LiteralPath $productionIdentityPath -PathType Leaf) {
        try {
            $productionIdentities = Read-VgJsonDocument -Path $productionIdentityPath
            $productionIdentityErrors = @(Test-VgSchemaDocument -Document $productionIdentities -Schema $Schema -DefinitionName 'identityRegistry' -DocumentId $productionIdentityRelativePath)
            $approvalChecks++
            if ($productionIdentityErrors.Count -ne 1 -or $productionIdentityErrors[0] -notmatch 'identities has too few items') {
                $approvalErrors += "E_APPROVAL ${productionIdentityRelativePath}: expected only the reviewer-minimum schema rejection until a distinct production reviewer exists"
            }
            $approvalChecks++
            $productionIdentityList = Get-Property $productionIdentities 'identities'
            if (-not (Test-IsArray $productionIdentityList) -or $productionIdentityList.Count -ne 1) {
                $approvalErrors += "E_APPROVAL ${productionIdentityRelativePath}: production registry must contain only the reviewed single author until a reviewer is provisioned"
            } else {
                $productionIdentity = $productionIdentityList[0]
                if ((Get-Property $productionIdentity 'identity_id') -cne 'editorial-author-administrator' -or
                    [int](Get-Property $productionIdentity 'wp_user_id') -ne 1 -or
                    (Get-Property $productionIdentity 'display_name') -cne 'Administrator' -or
                    (Get-Property $productionIdentity 'public_profile_path') -cne 'author/administrator' -or
                    -not (Test-IsArray (Get-Property $productionIdentity 'roles')) -or
                    (Get-Property $productionIdentity 'roles').Count -ne 1 -or
                    (Get-Property $productionIdentity 'roles')[0] -cne 'author') {
                    $approvalErrors += "E_APPROVAL ${productionIdentityRelativePath}: production identity does not match the reviewed WordPress user fact"
                }
            }
        } catch {
            $approvalErrors += $_.Exception.Message
        }
    }

    if (Test-Path -LiteralPath $phpLibraryPath -PathType Leaf) {
        $librarySource = [System.IO.File]::ReadAllText($phpLibraryPath, [System.Text.Encoding]::UTF8)
        foreach ($functionName in $requiredPhpFunctions) {
            $approvalChecks++
            if ($librarySource -cnotmatch "function\s+$([regex]::Escape($functionName))\s*\(") {
                $approvalErrors += "E_PHP ${phpLibraryRelativePath}: missing public function $functionName"
            }
        }
    }

    if (Test-Path -LiteralPath $phpSignerPath -PathType Leaf) {
        $signerSource = [System.IO.File]::ReadAllText($phpSignerPath, [System.Text.Encoding]::UTF8)
        foreach ($needle in @('PHP_SAPI', 'comparison-approval-2026-01', 'VG_COMPARISON_APPROVAL_SECRET_2026_01', "fopen(`$resolvedOutput, 'x')", 'chmod')) {
            $approvalChecks++
            if ($signerSource.IndexOf($needle, [System.StringComparison]::Ordinal) -lt 0) {
                $approvalErrors += "E_PHP ${phpSignerRelativePath}: missing signer safety contract '$needle'"
            }
        }
        $approvalChecks += 2
        $fchmodIndex = $signerSource.IndexOf('fchmod($handle, 0600)', [StringComparison]::Ordinal)
        $fcloseAfterFchmodIndex = $signerSource.IndexOf('fclose($handle)', [Math]::Max(0, $fchmodIndex), [StringComparison]::Ordinal)
        if ($fchmodIndex -lt 0 -or $fcloseAfterFchmodIndex -lt $fchmodIndex) {
            $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: signer must fchmod the open handle before fclose'
        }
        if ($signerSource -match '(?<!f)chmod\s*\(\s*\$[A-Za-z_]') {
            $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: path-based chmod is forbidden'
        }
    }

    if ((Test-Path -LiteralPath $phpLibraryPath -PathType Leaf) -and (Test-Path -LiteralPath $phpSignerPath -PathType Leaf)) {
        $phpBinary = [Environment]::GetEnvironmentVariable('VG_COMPARISON_PHP_BINARY')
        if ([string]::IsNullOrWhiteSpace($phpBinary)) {
            $phpCommand = Get-Command php -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
            if ($null -ne $phpCommand) { $phpBinary = $phpCommand.Source }
        }
        $approvalChecks++
        if ([string]::IsNullOrWhiteSpace($phpBinary) -or -not (Test-Path -LiteralPath $phpBinary -PathType Leaf)) {
            $approvalErrors += 'E_PHP php: PHP binary is unavailable; set VG_COMPARISON_PHP_BINARY'
        } else {
            $previousSecret = [Environment]::GetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01')
            try {
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01', 'task4-test-secret-dedicated-2026-01')
                $selfTestOutput = @(& $phpBinary $phpLibraryPath '--self-test' 2>&1)
                $selfTestExit = $LASTEXITCODE
            } finally {
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01', $previousSecret)
            }
            $approvalChecks++
            if ($selfTestExit -ne 0 -or $selfTestOutput.Count -ne 1 -or [string]$selfTestOutput[0] -cne 'Comparison rollout PHP self-tests passed.') {
                $approvalErrors += 'E_PHP ops/comparison-rollout-lib.php: PHP approval self-test failed'
            }

            $signerTempDirectory = Join-Path ([System.IO.Path]::GetTempPath()) ("vg-comparison-signer-" + [Guid]::NewGuid().ToString('N'))
            [void][System.IO.Directory]::CreateDirectory($signerTempDirectory)
            $isolatedRepoRoot = Join-Path $signerTempDirectory 'repo'
            $isolatedOpsPath = Join-Path $isolatedRepoRoot 'ops'
            $isolatedRegistryDirectory = Join-Path $isolatedOpsPath 'comparison-rollout'
            [void][System.IO.Directory]::CreateDirectory($isolatedRegistryDirectory)
            $isolatedLibraryPath = Join-Path $isolatedOpsPath 'comparison-rollout-lib.php'
            $isolatedSignerPath = Join-Path $isolatedOpsPath 'comparison-rollout-approve.php'
            $isolatedRegistryPath = Join-Path $isolatedRegistryDirectory 'identities.json'
            $alternateRegistryPath = Join-Path $isolatedOpsPath 'alternate-identities.json'
            Copy-Item -LiteralPath $phpLibraryPath -Destination $isolatedLibraryPath
            Copy-Item -LiteralPath $phpSignerPath -Destination $isolatedSignerPath
            Copy-Item -LiteralPath $productionIdentityPath -Destination $isolatedRegistryPath
            Copy-Item -LiteralPath $fixtureIdentityPath -Destination $alternateRegistryPath
            $signerOutputPath = Join-Path $signerTempDirectory 'approval.json'
            $signerArguments = @(
                "--registry=$isolatedRegistryPath", "--registry-hash=$((Get-FileHash -LiteralPath $isolatedRegistryPath -Algorithm SHA256).Hash.ToLowerInvariant())", '--identity-id=editorial-author-administrator', '--role=author',
                "--manifest-hash=$('a' * 64)", '--change-ids=cmp-104-decision', '--change-reason=decision_change', "--output=$signerOutputPath"
            )
            $previousSignerSecret = [Environment]::GetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01')
            function Invoke-SignerProcess {
                param([Parameter(Mandatory = $true)][string[]]$Arguments)
                $startInfo = New-Object System.Diagnostics.ProcessStartInfo
                $startInfo.FileName = $phpBinary
                $startInfo.UseShellExecute = $false
                $startInfo.RedirectStandardOutput = $true
                $startInfo.RedirectStandardError = $true
                $startInfo.Arguments = (($Arguments | ForEach-Object { '"' + ([string]$_).Replace('\\', '\\\\').Replace('"', '\"') + '"' }) -join ' ')
                $process = New-Object System.Diagnostics.Process
                $process.StartInfo = $startInfo
                [void]$process.Start()
                $standardOutput = $process.StandardOutput.ReadToEnd()
                $standardError = $process.StandardError.ReadToEnd()
                $process.WaitForExit()
                return [ordered]@{ ExitCode = $process.ExitCode; Output = $standardOutput + $standardError }
            }
            try {
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01', $null)
                $directSignerResult = Invoke-SignerProcess -Arguments (@($isolatedSignerPath) + $signerArguments)
                $approvalChecks++
                if ($directSignerResult.ExitCode -eq 0 -or $directSignerResult.Output.IndexOf('Run the signer through WP-CLI', [StringComparison]::Ordinal) -lt 0 -or (Test-Path -LiteralPath $signerOutputPath)) {
                    $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: direct PHP CLI signer did not fail closed without output'
                }

                $signerStubTemplate = @'
define('WP_CLI', true);
class WP_User { public int $ID = 1; public int $user_status = 0; public string $display_name = 'Administrator'; public array $roles = ['administrator']; public bool $spam = false; public bool $deleted = false; }
function get_userdata(int $id): object|false { return $id === 1 ? new WP_User() : false; }
%CURRENT_USER_FUNCTION%
$argv = json_decode(base64_decode(getenv('VG_SIGNER_ARGV'), true), true, 32, JSON_THROW_ON_ERROR);
require getenv('VG_SIGNER_PATH');
'@
                $signerArgv = @($isolatedSignerPath) + $signerArguments
                [Environment]::SetEnvironmentVariable('VG_SIGNER_ARGV', [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(($signerArgv | ConvertTo-Json -Compress))))
                [Environment]::SetEnvironmentVariable('VG_SIGNER_PATH', $isolatedSignerPath)
                foreach ($signerCase in @(
                    [ordered]@{ Name = 'missing-current-user-api'; Function = ''; Expected = 'Run the signer through WP-CLI' },
                    [ordered]@{ Name = 'mismatched-current-user'; Function = 'function get_current_user_id(): int { return 2; }'; Expected = 'must exactly match' },
                    [ordered]@{ Name = 'valid-wp-cli-reaches-platform-gate'; Function = 'function get_current_user_id(): int { return 1; }'; Expected = 'POSIX platform with verifiable Unix 0600' }
                )) {
                    $stubCode = $signerStubTemplate.Replace('%CURRENT_USER_FUNCTION%', $signerCase.Function)
                    $stubResult = Invoke-SignerProcess -Arguments @('-r', $stubCode)
                    $approvalChecks++
                    if ($stubResult.ExitCode -eq 0 -or $stubResult.Output.IndexOf($signerCase.Expected, [StringComparison]::Ordinal) -lt 0 -or (Test-Path -LiteralPath $signerOutputPath)) {
                        $approvalErrors += "E_PHP ops/comparison-rollout-approve.php: signer vector $($signerCase.Name) failed or left partial output"
                    }
                }

                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01', 'task4-test-secret-dedicated-2026-01')
                $successStub = $signerStubTemplate.Replace('%CURRENT_USER_FUNCTION%', 'function get_current_user_id(): int { return 1; }')
                $successResult = Invoke-SignerProcess -Arguments @('-r', $successStub)
                $approvalChecks++
                if ([Environment]::OSVersion.Platform -eq [PlatformID]::Win32NT) {
                    if ($successResult.ExitCode -eq 0 -or $successResult.Output.IndexOf('POSIX platform with verifiable Unix 0600', [StringComparison]::Ordinal) -lt 0 -or (Test-Path -LiteralPath $signerOutputPath)) {
                        $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: Windows signer did not refuse unverifiable Unix permissions'
                    }
                } elseif ($successResult.ExitCode -ne 0 -or -not (Test-Path -LiteralPath $signerOutputPath -PathType Leaf)) {
                    $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: isolated POSIX signer success vector did not create an artifact'
                } else {
                    $successBytes = [System.IO.File]::ReadAllBytes($signerOutputPath)
                    $successText = [Text.Encoding]::UTF8.GetString($successBytes).TrimEnd("`r", "`n")
                    try {
                        $successArtifact = $successText | ConvertFrom-Json
                        $successHmac = Get-Property $successArtifact 'hmac_sha256'
                        $hmacProperty = $successArtifact.PSObject.Properties['hmac_sha256']
                        $hmacProperty.Value = $null
                        $payloadText = [regex]::Replace($successText, ',"hmac_sha256":"[a-f0-9]{64}"', '')
                        [Environment]::SetEnvironmentVariable('VG_SIGNER_PAYLOAD', [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes($payloadText)))
                        $expectedHmac = & $phpBinary '-r' "echo hash_hmac('sha256', base64_decode(getenv('VG_SIGNER_PAYLOAD')), getenv('VG_COMPARISON_APPROVAL_SECRET_2026_01'));" 2>$null
                        if ((Get-Property $successArtifact 'identity_id') -cne 'editorial-author-administrator' -or $successHmac -cne $expectedHmac) {
                            $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: isolated signer artifact identity or HMAC was invalid'
                        }
                    } catch {
                        $approvalErrors += "E_PHP ops/comparison-rollout-approve.php: isolated signer artifact verification failed: $($_.Exception.Message)"
                    } finally {
                        [Environment]::SetEnvironmentVariable('VG_SIGNER_PAYLOAD', $null)
                    }
                    $preserveResult = Invoke-SignerProcess -Arguments @('-r', $successStub)
                    $approvalChecks++
                    if ($preserveResult.ExitCode -eq 0 -or -not ([System.Linq.Enumerable]::SequenceEqual([byte[]]$successBytes, [byte[]][System.IO.File]::ReadAllBytes($signerOutputPath)))) {
                        $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: second create overwrote the signed artifact'
                    }
                    Remove-Item -LiteralPath $signerOutputPath -Force
                }

                if ([Environment]::OSVersion.Platform -ne [PlatformID]::Win32NT) {
                    foreach ($failureStage in @('write', 'flush', 'chmod')) {
                        [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_TEST_FAILURE', $failureStage)
                        $failureResult = Invoke-SignerProcess -Arguments @('-r', $successStub)
                        $approvalChecks++
                        if ($failureResult.ExitCode -eq 0 -or (Test-Path -LiteralPath $signerOutputPath)) {
                            $approvalErrors += "E_PHP ops/comparison-rollout-approve.php: induced $failureStage failure left a partial artifact"
                        }
                    }
                }
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_TEST_FAILURE', $null)

                $alternateRegistryArguments = @($signerArguments)
                $alternateRegistryArguments[0] = "--registry=$alternateRegistryPath"
                $alternateRegistryArguments[1] = "--registry-hash=$((Get-FileHash -LiteralPath $alternateRegistryPath -Algorithm SHA256).Hash.ToLowerInvariant())"
                [Environment]::SetEnvironmentVariable('VG_SIGNER_ARGV', [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(((@($isolatedSignerPath) + $alternateRegistryArguments) | ConvertTo-Json -Compress))))
                $alternateResult = Invoke-SignerProcess -Arguments @('-r', $successStub)
                $approvalChecks++
                if ($alternateResult.ExitCode -eq 0 -or (Test-Path -LiteralPath $signerOutputPath)) {
                    $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: alternate in-repo registry was accepted'
                }
                [Environment]::SetEnvironmentVariable('VG_SIGNER_ARGV', [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(((@($isolatedSignerPath) + $signerArguments) | ConvertTo-Json -Compress))))

                [System.IO.File]::WriteAllText($signerOutputPath, 'existing', (New-Object System.Text.UTF8Encoding($false)))
                $existingSignerResult = Invoke-SignerProcess -Arguments @('-r', $signerStubTemplate.Replace('%CURRENT_USER_FUNCTION%', 'function get_current_user_id(): int { return 1; }'))
                $approvalChecks++
                if ($existingSignerResult.ExitCode -eq 0 -or [System.IO.File]::ReadAllText($signerOutputPath) -cne 'existing') {
                    $approvalErrors += 'E_PHP ops/comparison-rollout-approve.php: signer overwrote an existing output artifact'
                }
            } finally {
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_SECRET_2026_01', $previousSignerSecret)
                [Environment]::SetEnvironmentVariable('VG_SIGNER_ARGV', $null)
                [Environment]::SetEnvironmentVariable('VG_SIGNER_PATH', $null)
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_APPROVAL_TEST_FAILURE', $null)
                if (Test-Path -LiteralPath $signerOutputPath -PathType Leaf) { Remove-Item -LiteralPath $signerOutputPath -Force }
                if (Test-Path -LiteralPath $signerTempDirectory -PathType Container) { Remove-Item -LiteralPath $signerTempDirectory -Recurse -Force }
            }

            $phpVectorCode = @'
$library = getenv('VG_COMPARISON_LIBRARY_PATH');
$schemaPath = getenv('VG_COMPARISON_SCHEMA_PATH');
if (!class_exists('WP_User')) {
    class WP_User {
        public int $ID;
        public int $user_status;
        public string $display_name;
        public array $roles;
        public bool $spam;
        public bool $deleted;
        public function __construct(int $id, string $displayName, array $roles, int $status = 0) {
            $this->ID = $id; $this->display_name = $displayName; $this->user_status = $status;
            $this->roles = $roles; $this->spam = false; $this->deleted = false;
        }
    }
}
$GLOBALS['vg_comparison_test_users'] = [];
function get_userdata(int $id): object|false { return $GLOBALS['vg_comparison_test_users'][$id] ?? false; }
require $library;
$secret = 'task4-test-secret-dedicated-2026-01';
putenv('VG_COMPARISON_APPROVAL_SECRET_2026_01=' . $secret);
$manifestHash = str_repeat('a', 64);
$registry = ['identities' => [
    ['identity_id' => 'fixture-author', 'wp_user_id' => 101, 'display_name' => 'Fixture Author', 'public_profile_path' => 'about/fixture-author', 'roles' => ['author']],
    ['identity_id' => 'fixture-reviewer', 'wp_user_id' => 202, 'display_name' => 'Fixture Reviewer', 'public_profile_path' => 'about/fixture-reviewer', 'roles' => ['reviewer']],
]];
$GLOBALS['vg_comparison_test_users'] = [101 => new WP_User(101, 'Fixture Author', ['author']), 202 => new WP_User(202, 'Fixture Reviewer', ['editor'])];
$make = static function (string $identityId, int $wpUserId, string $role, array $changeIds, string $reason) use ($manifestHash, $secret): array {
    $payload = ['artifact_version' => '1', 'manifest_hash' => $manifestHash, 'identity_id' => $identityId, 'wp_user_id' => $wpUserId, 'role' => $role, 'timestamp_utc' => '2026-08-03T00:00:00Z', 'change_ids' => $changeIds, 'change_reason' => $reason, 'key_id' => 'comparison-approval-2026-01'];
    $payload['hmac_sha256'] = hash_hmac('sha256', vg_comparison_canonical_json($payload), $secret);
    return $payload;
};
$author = $make('fixture-author', 101, 'author', ['cmp-104-decision'], 'decision_change');
$reviewer = $make('fixture-reviewer', 202, 'reviewer', ['cmp-104-decision'], 'decision_change');
$authorCorrection = $make('fixture-author', 101, 'author', ['cmp-104-decision'], 'correction');
$reviewerCorrection = $make('fixture-reviewer', 202, 'reviewer', ['cmp-104-decision'], 'correction');
$refresh = $make('fixture-reviewer', 202, 'reviewer', ['cmp-104-source-refresh'], 'source_refresh');
$alteredScope = $author; $alteredScope['change_ids'] = ['cmp-104-source-refresh'];
$alteredHash = $author; $alteredHash['manifest_hash'] = str_repeat('b', 64);
$invalidHmac = $author; $invalidHmac['hmac_sha256'] = str_repeat('0', 64);
$unknownKey = $author; $unknownKey['key_id'] = 'comparison-approval-2026-02';
$unordered = $make('fixture-author', 101, 'author', ['cmp-104-source-refresh', 'cmp-104-decision'], 'decision_change');
$unauthorized = $make('fixture-author', 101, 'reviewer', ['cmp-104-decision'], 'decision_change');
$duplicateRegistry = $registry; $duplicateRegistry['identities'][] = ['identity_id' => 'fixture-author', 'wp_user_id' => 303, 'display_name' => 'Duplicate', 'public_profile_path' => 'about/duplicate', 'roles' => ['author']];
$sameWpRegistry = $registry; $sameWpRegistry['identities'][1]['wp_user_id'] = 101; $sameWpRegistry['identities'][1]['display_name'] = 'Fixture Author'; $sameWpReviewer = $make('fixture-reviewer', 101, 'reviewer', ['cmp-104-decision'], 'decision_change');
$exact64 = str_repeat('a', 64); $over64 = str_repeat('a', 65);
$shape64 = $author; $shape64['identity_id'] = $exact64; $shape64['key_id'] = $exact64; $shape64['change_ids'] = [$exact64];
$shape65Identity = $shape64; $shape65Identity['identity_id'] = $over64;
$shape65Key = $shape64; $shape65Key['key_id'] = $over64;
$shape65Change = $shape64; $shape65Change['change_ids'] = [$over64];
$sixtyFourChanges = []; for ($i = 0; $i < 64; $i++) { $sixtyFourChanges[] = sprintf('cmp-%03d', $i); }
$sixtyFiveChanges = $sixtyFourChanges; $sixtyFiveChanges[] = 'cmp-064';
$shape64Changes = $author; $shape64Changes['change_ids'] = $sixtyFourChanges;
$shape65Changes = $author; $shape65Changes['change_ids'] = $sixtyFiveChanges;
$schema = json_decode(file_get_contents($schemaPath), true, 512, JSON_THROW_ON_ERROR);
$approvalSchema = ['$defs' => $schema['$defs'], '$ref' => '#/$defs/approvalArtifact'];
$results = [];
$results['canonical_numeric'] = vg_comparison_canonical_json(['one' => 1.0, 'negative_zero' => -0.0, 'fraction' => 1.25, 'exponent' => 1.0e20, 'safe_integer' => 9007199254740991.0]) === base64_decode(getenv('VG_COMPARISON_CANONICAL_NUMERIC_VECTOR'), true);
$results['canonical_small_exponents'] = vg_comparison_canonical_json(['e5' => 1.0e-5, 'e6' => 1.0e-6, 'e7' => 1.0e-7, 'negative_e7' => -1.0e-7, 'e8' => 1.0e-8, 'negative_e20' => -1.0e-20, 'fraction_e7' => 1.23456789e-7]) === base64_decode(getenv('VG_COMPARISON_CANONICAL_SMALL_EXPONENT_VECTOR'), true);
$results['canonical_literal'] = vg_comparison_canonical_json(array_diff_key($author, ['hmac_sha256' => true])) === base64_decode(getenv('VG_COMPARISON_CANONICAL_VECTOR'), true);
$results['canonical_hash'] = vg_comparison_sha256(array_diff_key($author, ['hmac_sha256' => true])) === '268cacf50d0eb37b0353422154e3c82291924d04e1c3b0d258a651f8ad81df0f';
$results['schema_valid'] = vg_comparison_validate_schema($author, $approvalSchema) === [];
$results['stable_id_boundaries'] = _vg_comparison_approval_shape_valid($shape64) === true && _vg_comparison_approval_shape_valid($shape65Identity) === false && _vg_comparison_approval_shape_valid($shape65Key) === false && _vg_comparison_approval_shape_valid($shape65Change) === false;
$results['change_count_boundaries'] = _vg_comparison_approval_shape_valid($shape64Changes) === true && _vg_comparison_approval_shape_valid($shape65Changes) === false;
$results['valid_hmac'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === true;
$results['altered_scope'] = vg_comparison_verify_approval($alteredScope, $registry, $manifestHash)['ok'] === false;
$results['altered_hash'] = vg_comparison_verify_approval($alteredHash, $registry, $manifestHash)['ok'] === false;
$results['invalid_hmac'] = vg_comparison_verify_approval($invalidHmac, $registry, $manifestHash)['ok'] === false;
$results['unknown_key'] = vg_comparison_verify_approval($unknownKey, $registry, $manifestHash)['ok'] === false;
$savedSecret = getenv('VG_COMPARISON_APPROVAL_SECRET_2026_01'); putenv('VG_COMPARISON_APPROVAL_SECRET_2026_01');
$results['unset_key'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
putenv('VG_COMPARISON_APPROVAL_SECRET_2026_01=' . $savedSecret);
$results['ordinal_ordering'] = vg_comparison_verify_approval($unordered, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101]->user_status = 1;
$results['inactive_user'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101]->user_status = 0;
$originalAuthorUser = $GLOBALS['vg_comparison_test_users'][101];
$GLOBALS['vg_comparison_test_users'][101] = new WP_User(999, 'Fixture Author', ['author']);
$results['wp_user_id_mismatch'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101] = new WP_User(101, 'Fixture Author', ['author']); $GLOBALS['vg_comparison_test_users'][101]->spam = true;
$results['wp_user_spam'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101] = new WP_User(101, 'Fixture Author', ['author']); $GLOBALS['vg_comparison_test_users'][101]->deleted = true;
$results['wp_user_deleted'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101] = new WP_User(101, 'Fixture Author', []);
$results['wp_user_empty_roles'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101] = new WP_User(101, 'Wrong Display', ['author']);
$results['wp_user_display_mismatch'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101] = (object) ['ID' => 101, 'user_status' => 0, 'display_name' => 'Fixture Author', 'roles' => ['author'], 'spam' => false, 'deleted' => false];
$results['wp_user_invalid_type'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][101] = $originalAuthorUser;
$results['unauthorized_role'] = vg_comparison_verify_approval($unauthorized, $registry, $manifestHash)['ok'] === false;
$results['duplicate_identity'] = vg_comparison_verify_approval($author, $duplicateRegistry, $manifestHash)['ok'] === false;
$results['source_refresh_one_reviewer'] = _vg_comparison_verify_approval_set([$refresh], $registry, $manifestHash, ['change_reason' => 'source_refresh', 'change_ids' => ['cmp-104-source-refresh'], 'outcomes_changed' => false])['ok'] === true;
$GLOBALS['vg_comparison_test_users'][202]->roles = ['author'];
$results['reviewer_wp_role_mismatch'] = vg_comparison_verify_approval($refresh, $registry, $manifestHash)['ok'] === false;
$GLOBALS['vg_comparison_test_users'][202]->roles = ['editor'];
$results['decision_four_eyes'] = _vg_comparison_verify_approval_set([$author, $reviewer], $registry, $manifestHash, ['change_reason' => 'decision_change', 'change_ids' => ['cmp-104-decision']])['ok'] === true;
$results['omitted_expected_scope'] = _vg_comparison_verify_approval_set([$author, $reviewer], $registry, $manifestHash, ['change_reason' => 'decision_change'])['ok'] === false;
$results['malformed_impact_boolean'] = _vg_comparison_verify_approval_set([$authorCorrection], $registry, $manifestHash, ['change_reason' => 'correction', 'change_ids' => ['cmp-104-decision'], 'outcomes_changed' => 'true'])['ok'] === false;
$results['unknown_impact_key'] = _vg_comparison_verify_approval_set([$authorCorrection], $registry, $manifestHash, ['change_reason' => 'correction', 'change_ids' => ['cmp-104-decision'], 'unknown' => false])['ok'] === false;
foreach (['outcomes_changed', 'primary_outcome_changed', 'hard_constraints_changed', 'negative_recommendations_changed', 'archetype_changed', 'decisive_axes_changed'] as $trigger) {
    $impact = ['change_reason' => 'correction', 'change_ids' => ['cmp-104-decision'], $trigger => true];
    $results['four_eyes_' . $trigger] = _vg_comparison_verify_approval_set([$authorCorrection], $registry, $manifestHash, $impact)['ok'] === false
        && _vg_comparison_verify_approval_set([$authorCorrection, $reviewerCorrection], $registry, $manifestHash, $impact)['ok'] === true;
}
$results['altered_expected_scope'] = _vg_comparison_verify_approval_set([$author, $reviewer], $registry, $manifestHash, ['change_reason' => 'decision_change', 'change_ids' => ['cmp-104-source-refresh']])['ok'] === false;
$results['duplicate_artifact'] = _vg_comparison_verify_approval_set([$author, $author], $registry, $manifestHash, ['change_reason' => 'decision_change', 'change_ids' => ['cmp-104-decision']])['ok'] === false;
$results['duplicate_wp_single'] = vg_comparison_verify_approval($author, $sameWpRegistry, $manifestHash)['ok'] === false;
$results['same_wp_user'] = _vg_comparison_verify_approval_set([$author, $sameWpReviewer], $sameWpRegistry, $manifestHash, ['change_reason' => 'decision_change', 'change_ids' => ['cmp-104-decision']])['ok'] === false;
$results['safe_compile_surface'] = vg_comparison_compile_portfolio([], [], [], [], [])['ok'] === false;
$results['safe_probe_surface'] = vg_comparison_probe_source([], [])['ok'] === false;
$results['safe_insert_surface'] = vg_comparison_insert_modules('unchanged', [])['ok'] === false;
define('AUTH_KEY', $secret);
$results['wordpress_salt_secret'] = vg_comparison_verify_approval($author, $registry, $manifestHash)['ok'] === false;
echo json_encode($results, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
'@
            $previousLibraryPath = [Environment]::GetEnvironmentVariable('VG_COMPARISON_LIBRARY_PATH')
            $previousSchemaPath = [Environment]::GetEnvironmentVariable('VG_COMPARISON_SCHEMA_PATH')
            $previousCanonicalVector = [Environment]::GetEnvironmentVariable('VG_COMPARISON_CANONICAL_VECTOR')
            $previousCanonicalNumericVector = [Environment]::GetEnvironmentVariable('VG_COMPARISON_CANONICAL_NUMERIC_VECTOR')
            $previousCanonicalSmallExponentVector = [Environment]::GetEnvironmentVariable('VG_COMPARISON_CANONICAL_SMALL_EXPONENT_VECTOR')
            try {
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_LIBRARY_PATH', $phpLibraryPath)
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_SCHEMA_PATH', $schemaPath)
                $canonicalVector = '{"artifact_version":"1","change_ids":["cmp-104-decision"],"change_reason":"decision_change","identity_id":"fixture-author","key_id":"comparison-approval-2026-01","manifest_hash":"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa","role":"author","timestamp_utc":"2026-08-03T00:00:00Z","wp_user_id":101}'
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_CANONICAL_VECTOR', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($canonicalVector)))
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_CANONICAL_NUMERIC_VECTOR', 'eyJleHBvbmVudCI6MWUyMCwiZnJhY3Rpb24iOjEuMjUsIm5lZ2F0aXZlX3plcm8iOi0wLCJvbmUiOjEsInNhZmVfaW50ZWdlciI6OTAwNzE5OTI1NDc0MDk5MX0=')
                $canonicalSmallExponentVector = ConvertTo-VgCanonicalJson -Value ([ordered]@{ e5 = [double]1.0e-5; e6 = [double]1.0e-6; e7 = [double]1.0e-7; negative_e7 = [double]-1.0e-7; e8 = [double]1.0e-8; negative_e20 = [double]-1.0e-20; fraction_e7 = [double]1.23456789e-7 })
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_CANONICAL_SMALL_EXPONENT_VECTOR', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($canonicalSmallExponentVector)))
                $vectorOutput = @(& $phpBinary '-d' 'display_errors=stderr' '-r' $phpVectorCode 2>&1)
                $vectorExit = $LASTEXITCODE
            } finally {
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_LIBRARY_PATH', $previousLibraryPath)
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_SCHEMA_PATH', $previousSchemaPath)
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_CANONICAL_VECTOR', $previousCanonicalVector)
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_CANONICAL_NUMERIC_VECTOR', $previousCanonicalNumericVector)
                [Environment]::SetEnvironmentVariable('VG_COMPARISON_CANONICAL_SMALL_EXPONENT_VECTOR', $previousCanonicalSmallExponentVector)
            }
            $approvalVectorNames = @(
                'canonical_numeric', 'canonical_small_exponents', 'canonical_literal', 'canonical_hash', 'schema_valid',
                'stable_id_boundaries', 'change_count_boundaries', 'valid_hmac', 'altered_scope', 'altered_hash',
                'invalid_hmac', 'unknown_key', 'unset_key', 'ordinal_ordering', 'inactive_user', 'wp_user_id_mismatch',
                'wp_user_spam', 'wp_user_deleted', 'wp_user_empty_roles', 'wp_user_display_mismatch', 'wp_user_invalid_type',
                'unauthorized_role', 'duplicate_identity', 'source_refresh_one_reviewer', 'reviewer_wp_role_mismatch',
                'decision_four_eyes', 'omitted_expected_scope', 'malformed_impact_boolean', 'unknown_impact_key',
                'four_eyes_outcomes_changed', 'four_eyes_primary_outcome_changed', 'four_eyes_hard_constraints_changed',
                'four_eyes_negative_recommendations_changed', 'four_eyes_archetype_changed', 'four_eyes_decisive_axes_changed',
                'altered_expected_scope', 'duplicate_artifact', 'duplicate_wp_single', 'same_wp_user',
                'safe_compile_surface', 'safe_probe_surface', 'safe_insert_surface', 'wordpress_salt_secret'
            )
            $approvalChecks += 2
            $truthyBooleanProbe = [pscustomobject]@{ string_value = 'True'; numeric_value = 1; boolean_value = $true }
            foreach ($truthyName in @('string_value', 'numeric_value')) {
                $truthyValue = Get-Property $truthyBooleanProbe $truthyName
                if ($truthyValue -is [bool] -and $truthyValue -eq $true) {
                    $approvalErrors += "E_APPROVAL fixture/${truthyName}: non-boolean truthy value was accepted"
                }
            }
            $approvalChecks += $approvalVectorNames.Count
            if ($vectorExit -ne 0 -or $vectorOutput.Count -ne 1) {
                $approvalErrors += 'E_PHP ops/comparison-rollout-lib.php: PHP approval vector execution failed'
            } else {
                try {
                    $vectorResults = [string]$vectorOutput[0] | ConvertFrom-Json
                    foreach ($vectorName in $approvalVectorNames) {
                        $vectorValue = Get-Property $vectorResults $vectorName
                        $vectorPassed = ($vectorValue -is [bool] -and $vectorValue -eq $true)
                        if (-not $vectorPassed) {
                            $approvalErrors += "E_APPROVAL fixture/${vectorName}: PHP approval vector failed"
                        }
                    }
                } catch {
                    $approvalErrors += 'E_PHP ops/comparison-rollout-lib.php: PHP approval vector output was invalid'
                }
            }
        }
    }

    return [ordered]@{ errors = @($approvalErrors); checks = $approvalChecks }
}

function Invoke-BundleRuntimeValidation {
    param([Parameter(Mandatory = $true)]$Schema)

    $runtimeErrors = @()
    $runtimeChecks = 0
    $bootstrapRelativePath = 'wordpress/wp-content/mu-plugins/vietnamguide-z-comparison.php'
    $bundleRelativePath = 'wordpress/wp-content/mu-plugins/vietnamguide-comparison/bundle.php'
    $renderRelativePath = 'wordpress/wp-content/mu-plugins/vietnamguide-comparison/render.php'
    $shortcodesRelativePath = 'wordpress/wp-content/mu-plugins/vietnamguide-comparison/shortcodes.php'
    $bootstrapPath = Join-Path $repoRoot $bootstrapRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $bundlePath = Join-Path $repoRoot $bundleRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $renderPath = Join-Path $repoRoot $renderRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    $shortcodesPath = Join-Path $repoRoot $shortcodesRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    foreach ($runtimeFile in @(
        [ordered]@{ Relative = $bootstrapRelativePath; Path = $bootstrapPath }
        [ordered]@{ Relative = $bundleRelativePath; Path = $bundlePath }
        [ordered]@{ Relative = $renderRelativePath; Path = $renderPath }
        [ordered]@{ Relative = $shortcodesRelativePath; Path = $shortcodesPath }
    )) {
        $runtimeChecks++
        if (-not (Test-Path -LiteralPath $runtimeFile.Path -PathType Leaf)) {
            $runtimeErrors += "E_RUNTIME $($runtimeFile.Relative): required runtime file is missing"
        }
    }
    if ($runtimeErrors.Count -gt 0) {
        return [ordered]@{ errors = @($runtimeErrors); checks = $runtimeChecks }
    }

    $bootstrapSource = [System.IO.File]::ReadAllText($bootstrapPath, [System.Text.Encoding]::UTF8)
    $bundleSource = [System.IO.File]::ReadAllText($bundlePath, [System.Text.Encoding]::UTF8)
    $renderSource = [System.IO.File]::ReadAllText($renderPath, [System.Text.Encoding]::UTF8)
    $shortcodesSource = [System.IO.File]::ReadAllText($shortcodesPath, [System.Text.Encoding]::UTF8)
    $runtimeSource = $bootstrapSource + "`n" + $bundleSource + "`n" + $renderSource + "`n" + $shortcodesSource
    $runtimeChecks++
    if ([regex]::Matches($runtimeSource, "get_post_meta\(\`$post_id,\s*'vg_eeat_comparison_bundle',\s*true\)").Count -ne 1) {
        $runtimeErrors += 'E_RUNTIME comparison runtime: exactly one approved metadata read is required'
    }
    $runtimeChecks++
    if ($runtimeSource -match '(?i)wp_remote_get|curl_[a-z_]*|\bcurl\b|do_shortcode|the_content|template_redirect|\bexit\s*\(|\bdie\s*\(|run_id|reviewer_notes|source_body|raw_html|block_markup|[A-Za-z]:\\\\') {
        $runtimeErrors += 'E_RUNTIME comparison loader: forbidden fetch, evaluation, suppression, run-ID, or local-path surface found'
    }
    $runtimeChecks++
    if ($bootstrapSource -notmatch "add_action\(\s*'init'.*20\s*\)" -or $bootstrapSource -notmatch "is_callable\(\s*'vg_comparison_register_shortcode_replacements'\s*\)") {
        $runtimeErrors += "E_RUNTIME ${bootstrapRelativePath}: shortcode registration must be callable-gated at init priority 20"
    }
    $runtimeChecks++
    if ($bootstrapSource -notmatch "is_file\(\`$render_file\)" -or $bootstrapSource -notmatch "is_file\(\`$shortcodes_file\)" -or $bootstrapSource -notmatch "require_once\s+\`$bundle_file") {
        $runtimeErrors += "E_RUNTIME ${bootstrapRelativePath}: bundle include must be required and Task 6 includes conditional"
    }
    $runtimeChecks++
    if ($renderSource -notmatch '\besc_html\s*\(' -or $renderSource -notmatch '\besc_attr\s*\(' -or $renderSource -notmatch '\besc_url\s*\(') {
        $runtimeErrors += "E_RUNTIME ${renderRelativePath}: final text, attribute, and URL boundaries must use WordPress escaping"
    }
    $runtimeChecks++
    foreach ($requiredRenderer in @('vg_comparison_render_decision_frame', 'vg_comparison_render_source_trail', 'vg_comparison_render_related_routes', 'vg_comparison_render_update_log')) {
        if ($renderSource -notmatch "function\s+$requiredRenderer\s*\(") {
            $runtimeErrors += "E_RUNTIME ${renderRelativePath}: missing $requiredRenderer"
        }
    }
    $runtimeChecks++
    foreach ($requiredShortcode in @('vg_comparison_register_shortcode_replacements', 'vg_comparison_shortcode_source_trail', 'vg_comparison_shortcode_update_log', 'vg_comparison_shortcode_related_routes')) {
        if ($shortcodesSource -notmatch "function\s+$requiredShortcode\s*\(") {
            $runtimeErrors += "E_RUNTIME ${shortcodesRelativePath}: missing $requiredShortcode"
        }
    }

    $phpBinary = [Environment]::GetEnvironmentVariable('VG_COMPARISON_PHP_BINARY')
    if ([string]::IsNullOrWhiteSpace($phpBinary)) {
        $phpCommand = Get-Command php -CommandType Application -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($null -ne $phpCommand) { $phpBinary = $phpCommand.Source }
    }
    $runtimeChecks++
    if ([string]::IsNullOrWhiteSpace($phpBinary) -or -not (Test-Path -LiteralPath $phpBinary -PathType Leaf)) {
        $runtimeErrors += 'E_RUNTIME php: PHP binary is unavailable; set VG_COMPARISON_PHP_BINARY'
        return [ordered]@{ errors = @($runtimeErrors); checks = $runtimeChecks }
    }
    foreach ($runtimeFilePath in @($bootstrapPath, $bundlePath, $renderPath, $shortcodesPath)) {
        $lintOutput = @(& $phpBinary '-l' $runtimeFilePath 2>&1)
        $runtimeChecks++
        if ($LASTEXITCODE -ne 0) {
            $runtimeErrors += "E_RUNTIME ${runtimeFilePath}: PHP lint failed"
        }
    }
    if ($runtimeErrors.Count -gt 0) {
        return [ordered]@{ errors = @($runtimeErrors); checks = $runtimeChecks }
    }

    $requiredKeys = @((Get-Definition $Schema 'resolvedBundleV2').required)
    $positiveBundleJson = New-PositiveBundle | ConvertTo-Json -Depth 100 -Compress
    $lineTerminatorBundle = New-PositiveBundle
    $lineTerminatorBundle['path'] = $canaryActivation[0]
    $lineTerminatorBundle['post_id'] = 5005
    $lineTerminatorBundle['field_note'] = 'Line' + [char]0x2028 + 'paragraph' + [char]0x2029 + 'end'
    $lineTerminatorHashInput = ConvertTo-FixtureObject $lineTerminatorBundle
    [void]$lineTerminatorHashInput.Remove('bundle_hash')
    $lineTerminatorBundle['bundle_hash'] = Get-VgSha256Hex -Value $lineTerminatorHashInput
    $lineTerminatorBundleJson = $lineTerminatorBundle | ConvertTo-Json -Depth 100 -Compress
    $lineTerminatorCanonicalInput = [ordered]@{}
    $lineTerminatorCanonicalInput['key' + [char]0x2028 + 'separator' + [char]0x2029] = 'Line' + [char]0x2028 + 'paragraph' + [char]0x2029 + 'end'
    $lineTerminatorCanonical = ConvertTo-VgCanonicalJson -Value $lineTerminatorCanonicalInput
    $runtimeStageInventoriesJson = [ordered]@{
        baseline = @($baselinePaths)
        canary = @($canaryInventory)
        full = @($fullInventory)
    } | ConvertTo-Json -Depth 5 -Compress
    $runtimeCode = @'
<?php
declare(strict_types=1);
final class WP_Post { public function __construct(public mixed $ID, public string $path) {} }
$GLOBALS['vg_test_posts'] = [];
$GLOBALS['vg_test_meta'] = [];
$GLOBALS['vg_test_reads'] = [];
$GLOBALS['vg_test_active'] = [];
$GLOBALS['vg_test_snapshot'] = [];
$GLOBALS['vg_test_snapshots'] = [];
$GLOBALS['vg_test_org'] = 'organizations-v2';
$GLOBALS['vg_test_actions'] = [];
$GLOBALS['vg_test_shortcodes'] = [];
$GLOBALS['vg_test_shortcode_ops'] = [];
$GLOBALS['vg_test_current_id'] = 0;
$GLOBALS['vg_test_escape_calls'] = [];
$GLOBALS['vg_test_baseline_calls'] = [];
$GLOBALS['vg_test_throw'] = 0;
$GLOBALS['vg_test_get_post_mode'] = 'normal';
$GLOBALS['vg_test_path_mode'] = 'normal';
$GLOBALS['vg_test_gate_mode'] = 'normal';
$GLOBALS['vg_test_escape_throw'] = '';
$GLOBALS['vg_test_home_url_mode'] = 'normal';
function add_action(string $hook, callable|string $callback, int $priority = 10): void { $GLOBALS['vg_test_actions'][] = [$hook, $callback, $priority]; }
function add_shortcode(string $tag, callable|string $callback): void { $GLOBALS['vg_test_shortcodes'][$tag]=$callback;$GLOBALS['vg_test_shortcode_ops'][]=['add',$tag,$callback]; }
function remove_shortcode(string $tag): void { unset($GLOBALS['vg_test_shortcodes'][$tag]);$GLOBALS['vg_test_shortcode_ops'][]=['remove',$tag]; }
function get_the_ID(): int { return $GLOBALS['vg_test_current_id']; }
function get_post(int $id): mixed { if ($id === $GLOBALS['vg_test_throw'] || $GLOBALS['vg_test_get_post_mode']==='throw') { throw new RuntimeException('private'); } if($GLOBALS['vg_test_get_post_mode']==='invalid'){return (object)['ID'=>$id];} return $GLOBALS['vg_test_posts'][$id] ?? false; }
function get_page_uri(WP_Post $post): string { return $post->path; }
function vg_get_guide_path(WP_Post $post): string { if($GLOBALS['vg_test_path_mode']==='throw'){throw new RuntimeException('private');}if($GLOBALS['vg_test_path_mode']==='invalid'){return 'INVALID PATH';}return $post->path; }
function vg_is_comparison_rollout_active_path(string $path): bool { if($GLOBALS['vg_test_gate_mode']==='throw'){throw new RuntimeException('private');}return in_array($path, $GLOBALS['vg_test_active'], true); }
function vg_comparison_activation_snapshot(): array { if($GLOBALS['vg_test_snapshots']!==[]){return array_shift($GLOBALS['vg_test_snapshots']);}return $GLOBALS['vg_test_snapshot']; }
function vg_comparison_organization_registry_version(): string { return $GLOBALS['vg_test_org']; }
function get_post_meta(int $post_id, string $key, bool $single): mixed { $GLOBALS['vg_test_reads'][$post_id] = ($GLOBALS['vg_test_reads'][$post_id] ?? 0) + 1; return $GLOBALS['vg_test_meta'][$post_id] ?? ''; }
function __(string $text, string $domain='default'): string { return $text; }
function esc_html(mixed $text): string { if($GLOBALS['vg_test_escape_throw']==='html'){throw new RuntimeException('private');}$GLOBALS['vg_test_escape_calls'][]=['html',(string)$text];return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false); }
function esc_attr(mixed $text): string { if($GLOBALS['vg_test_escape_throw']==='attr'){throw new RuntimeException('private');}$GLOBALS['vg_test_escape_calls'][]=['attr',(string)$text];return htmlspecialchars((string)$text,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false); }
function esc_url(mixed $url): string { if($GLOBALS['vg_test_escape_throw']==='url'){throw new RuntimeException('private');}$url=trim((string)$url);$GLOBALS['vg_test_escape_calls'][]=['url',$url];$url=preg_replace('/[\x00-\x1F\x7F]/','',$url)??'';$url=str_replace(' ','%20',$url);if(preg_match('#^(?:https?://|/)#D',$url)!==1){return '';}$escaped=htmlspecialchars($url,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8',false);return str_replace(['&amp;','&#039;'],['&#038;','&#039;'],$escaped); }
function esc_html__(string $text, string $domain='default'): string { return esc_html(__($text,$domain)); }
function esc_attr__(string $text, string $domain='default'): string { return esc_attr(__($text,$domain)); }
function home_url(string $path=''): string { if($GLOBALS['vg_test_home_url_mode']==='dangerous'){return 'javascript:alert(1)';}if($GLOBALS['vg_test_home_url_mode']==='quote'){return 'https://vietnamguide.example/route?q=" onclick="bad';}return 'https://vietnamguide.example'.('/'.ltrim($path,'/')); }
function vg_shortcode_source_trail(array $atts=[]): string { $GLOBALS['vg_test_baseline_calls'][]=['source',$atts];return 'BASELINE_SOURCE'; }
function vg_shortcode_update_log(array $atts=[]): string { $GLOBALS['vg_test_baseline_calls'][]=['update',$atts];return 'BASELINE_UPDATE'; }
function vg_shortcode_related_routes(array $atts=[]): string { $GLOBALS['vg_test_baseline_calls'][]=['routes',$atts];return 'BASELINE_ROUTES'; }
$logFile = tempnam(sys_get_temp_dir(), 'vg-log-');
ini_set('log_errors', '1'); ini_set('error_log', $logFile);
require getenv('VG_RUNTIME_BOOTSTRAP');
$results = [];
$requiredKeys = json_decode(base64_decode(getenv('VG_RUNTIME_REQUIRED_KEYS'), true), true, 32, JSON_THROW_ON_ERROR);
$results['constants'] = VG_COMPARISON_BUNDLE_SCHEMA_CURRENT === '2' && VG_COMPARISON_BUNDLE_SCHEMA_PREVIOUS === '1' && VG_COMPARISON_ACTIVATION_ARTIFACT_VERSION === 'activation-v2';
$results['schema_key_parity'] = vg_comparison_bundle_v2_required_keys() === $requiredKeys;
$results['shortcode_registration_priority'] = $GLOBALS['vg_test_actions'] === [['init','vg_comparison_register_shortcode_replacements',20]];
$results['canonical_integer_parity'] = vg_comparison_bundle_canonical_json(['z'=>1,'a'=>['nested'=>'slash/value'],'m'=>0]) === '{"a":{"nested":"slash/value"},"m":0,"z":1}';
$results['canonical_line_terminator_parity'] = vg_comparison_bundle_canonical_json(["key\u{2028}separator\u{2029}"=>"Line\u{2028}paragraph\u{2029}end"]) === base64_decode(getenv('VG_RUNTIME_LINE_TERMINATOR_CANONICAL'), true);
$canonicalFloatRejected=false;try{vg_comparison_bundle_canonical_json(['float'=>1.0]);}catch(InvalidArgumentException){$canonicalFloatRejected=true;}$results['canonical_float_rejected']=$canonicalFloatRejected;
function vg_test_bundle(int $id, string $path): array {
    $bundle=json_decode(base64_decode(getenv('VG_RUNTIME_POSITIVE_BUNDLE'),true),true,128,JSON_THROW_ON_ERROR);$bundle['path']=$path;$bundle['post_id']=$id;
    $hashable=$bundle; unset($hashable['bundle_hash']); $bundle['bundle_hash']=hash('sha256',vg_comparison_bundle_canonical_json($hashable)); return $bundle;
}
function vg_test_json(array $bundle): string { return json_encode($bundle, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
function vg_test_rehash(array $bundle): array { $hashable=$bundle; unset($hashable['bundle_hash']); $bundle['bundle_hash']=hash('sha256',vg_comparison_bundle_canonical_json($hashable)); return $bundle; }
function vg_test_post(int $id, string $path='compare/old-quarter-vs-french-quarter-vs-west-lake'): WP_Post { return new WP_Post($id,$path); }
function vg_test_stage_snapshot(string $stage, ?array $bundle=null): array {
    $inventories=json_decode(base64_decode(getenv('VG_RUNTIME_STAGE_INVENTORIES'),true),true,32,JSON_THROW_ON_ERROR);$paths=$inventories[$stage];
    $entries=[];foreach($paths as $path){$entry=['path'=>$path,'bundle_hash'=>str_repeat('0',64),'schema_version'=>'v2','source_registry_version'=>'sources-v2','activation_artifact_version'=>'activation-v2'];if($bundle!==null&&$bundle['path']===$path){$entry['bundle_hash']=$bundle['bundle_hash'];$entry['schema_version']=$bundle['schema_version'];$entry['source_registry_version']=$bundle['source_registry_version'];$entry['activation_artifact_version']=$bundle['activation_artifact_version'];}$entries[]=$entry;}
    return ['snapshot_version'=>'snapshot-v2','activation_stage'=>$stage,'activated_on'=>'2026-08-03','manifest_version'=>$bundle['manifest_version']??'manifest-v2','activation_artifact_version'=>'activation-v2','paths'=>$paths,'bundle_hashes'=>$entries];
}
$results['snapshot_exact_baseline_valid']=vg_comparison_bundle_snapshot_valid(vg_test_stage_snapshot('baseline'));
$results['snapshot_exact_canary_valid']=vg_comparison_bundle_snapshot_valid(vg_test_stage_snapshot('canary'));
$results['snapshot_exact_full_valid']=vg_comparison_bundle_snapshot_valid(vg_test_stage_snapshot('full'));
$dateContractSnapshot=vg_test_stage_snapshot('canary');$results['snapshot_iso_date_valid']=vg_comparison_bundle_snapshot_valid($dateContractSnapshot);
$otherArtifactContractSnapshot=$dateContractSnapshot;$otherArtifactContractSnapshot['activation_artifact_version']='activation-v3';foreach($otherArtifactContractSnapshot['bundle_hashes'] as &$otherArtifactContractEntry){$otherArtifactContractEntry['activation_artifact_version']='activation-v3';}unset($otherArtifactContractEntry);$results['snapshot_exact_artifact_required']=!vg_comparison_bundle_snapshot_valid($otherArtifactContractSnapshot);
$truncatedSnapshot=vg_test_stage_snapshot('canary');array_pop($truncatedSnapshot['paths']);array_pop($truncatedSnapshot['bundle_hashes']);$results['snapshot_truncated_inventory_rejected']=!vg_comparison_bundle_snapshot_valid($truncatedSnapshot);
$unknownSnapshot=vg_test_stage_snapshot('canary');$unknownSnapshot['paths'][10]='compare/unknown-target';$unknownSnapshot['bundle_hashes'][10]['path']='compare/unknown-target';$results['snapshot_unknown_inventory_rejected']=!vg_comparison_bundle_snapshot_valid($unknownSnapshot);
$reorderedSnapshot=vg_test_stage_snapshot('canary');[$reorderedSnapshot['paths'][8],$reorderedSnapshot['paths'][9]]=[$reorderedSnapshot['paths'][9],$reorderedSnapshot['paths'][8]];[$reorderedSnapshot['bundle_hashes'][8],$reorderedSnapshot['bundle_hashes'][9]]=[$reorderedSnapshot['bundle_hashes'][9],$reorderedSnapshot['bundle_hashes'][8]];$results['snapshot_reordered_inventory_rejected']=!vg_comparison_bundle_snapshot_valid($reorderedSnapshot);
$otherVersionSnapshot=vg_test_stage_snapshot('canary');$otherVersionSnapshot['snapshot_version']='snapshot-v3';$results['snapshot_other_version_rejected']=!vg_comparison_bundle_snapshot_valid($otherVersionSnapshot);
$otherArtifactSnapshot=vg_test_stage_snapshot('canary');$otherArtifactSnapshot['activation_artifact_version']='activation-v3';foreach($otherArtifactSnapshot['bundle_hashes'] as &$otherArtifactEntry){$otherArtifactEntry['activation_artifact_version']='activation-v3';}unset($otherArtifactEntry);$results['snapshot_other_artifact_rejected']=!vg_comparison_bundle_snapshot_valid($otherArtifactSnapshot);
function vg_test_install(WP_Post $post, string $raw, array $expect=[]): void {
    $GLOBALS['vg_test_snapshots']=[];$GLOBALS['vg_test_posts'][$post->ID]=$post; $GLOBALS['vg_test_meta'][$post->ID]=$raw; $GLOBALS['vg_test_active']=[$post->path]; $decoded=json_decode($raw,true); $defaults=is_array($decoded)?$decoded:[];
    $snapshotBundle=['path'=>$post->path,'bundle_hash'=>$defaults['bundle_hash']??str_repeat('0',64),'schema_version'=>$defaults['schema_version']??'v2','source_registry_version'=>$defaults['source_registry_version']??'sources-v2','activation_artifact_version'=>'activation-v2','manifest_version'=>$expect['manifest_version']??($defaults['manifest_version']??'manifest-v2')];
    $GLOBALS['vg_test_snapshot']=vg_test_stage_snapshot('canary',$snapshotBundle);
    foreach($GLOBALS['vg_test_snapshot']['bundle_hashes'] as &$snapshotEntry){if($snapshotEntry['path']===$post->path&&isset($expect['entry'])){$snapshotEntry=array_replace($snapshotEntry,$expect['entry']);}}unset($snapshotEntry);
    if(isset($expect['activation_artifact_version'])){$GLOBALS['vg_test_snapshot']['activation_artifact_version']=$expect['activation_artifact_version'];}
    if(isset($expect['paths'])){$GLOBALS['vg_test_snapshot']['paths']=$expect['paths'];}
    if(isset($expect['snapshot'])){$GLOBALS['vg_test_snapshot']=array_replace($GLOBALS['vg_test_snapshot'],$expect['snapshot']);}
    $GLOBALS['vg_test_org']=$expect['organization_registry_version']??'organizations-v2';
}
function vg_test_reason(array $result,string $reason):bool{return $result===['ok'=>false,'reason'=>$reason];}
$post=vg_test_post(5001); $bundle=vg_test_bundle($post->ID,$post->path); vg_test_install($post,vg_test_json($bundle)); $loaded=vg_comparison_load_bundle($post);
$results['valid_exact']=$loaded===['ok'=>true,'bundle'=>$bundle,'bundle_hash'=>$bundle['bundle_hash'],'cache_key'=>implode(':',[$post->path,$bundle['bundle_hash'],'v2','sources-v2','organizations-v2','activation-v2'])];
$results['single_read']=vg_comparison_load_bundle($post->ID)===$loaded&&$GLOBALS['vg_test_reads'][$post->ID]===1;
function vg_test_visible(string $html): string { return trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8'))??''); }
function vg_test_render_hash(string $html): string { return preg_match('/data-vg-render-hash="([a-f0-9]{64})"/D',$html,$m)===1?$m[1]:''; }
$decision=vg_comparison_render_decision_frame($bundle);$sources=vg_comparison_render_source_trail($bundle);$routes=vg_comparison_render_related_routes($bundle);$updates=vg_comparison_render_update_log($bundle);
$results['renderers_nonempty']=$decision!==''&&$sources!==''&&$routes!==''&&$updates!=='';
$results['decision_structure']=substr_count($decision,'<section class="vg-comparison-decision"')===1&&substr_count($decision,'<h2')===1&&substr_count($decision,'class="vg-comparison-axis"')===3&&substr_count($decision,'class="vg-comparison-lens"')===3&&substr_count($decision,'class="vg-comparison-rule"')===3;
$results['decision_contract']=!str_contains(strtolower($decision),'<table')&&vg_comparison_bundle_string_length(vg_test_visible($decision))<=min($bundle['render_contract']['max_visible_characters'],1800)&&str_contains($decision,'data-vg-bundle-version="v2"')&&str_contains($decision,'data-vg-bundle-hash="'.$bundle['bundle_hash'].'"')&&vg_test_render_hash($decision)!=='';
$results['render_hash_deterministic']=$decision===vg_comparison_render_decision_frame($bundle)&&vg_test_render_hash($decision)===vg_test_render_hash(vg_comparison_render_decision_frame($bundle));
$privateBundle=$bundle;$privateBundle['provenance_hash']=str_repeat('c',64);$privateBundle['editorial']['written_by_identity_id']='private-author';$privateBundle['editorial']['reviewed_by_identity_id']='private-reviewer';
$results['render_hash_ignores_private']=$decision===vg_comparison_render_decision_frame($privateBundle);
$pathBundle=$bundle;$pathBundle['traveler_lenses'][0]['rule_path']=[
    ['rule_id'=>'path-hard','order'=>1,'kind'=>'hard_constraint','condition'=>'Hard path condition','outcome'=>'Hard path outcome','context_tags'=>['short-time'],'option_ids'=>['option-a'],'claim_ids'=>['claim-access'],'axis_ids'=>['axis-1'],'outcome_id'=>'outcome-a'],
    ['rule_id'=>'path-pref','order'=>2,'kind'=>'preference','condition'=>'Preference path condition','outcome'=>'Preference path outcome','context_tags'=>['short-time'],'option_ids'=>['option-a'],'claim_ids'=>['claim-access'],'axis_ids'=>['axis-1'],'outcome_id'=>'outcome-a'],
    ['rule_id'=>'path-tie','order'=>3,'kind'=>'tie_breaker','condition'=>'Tie path condition','outcome'=>'Tie path outcome','context_tags'=>['short-time'],'option_ids'=>['option-a'],'claim_ids'=>['claim-access'],'axis_ids'=>['axis-1'],'outcome_id'=>'outcome-a'],
];
$pathBundle['primary_decision']['rules'][]=['rule_id'=>'unused-rule','order'=>2,'kind'=>'preference','condition'=>'PRIVATE UNUSED RULE','outcome'=>'PRIVATE UNUSED OUTCOME','context_tags'=>['short-time'],'option_ids'=>['option-b'],'claim_ids'=>['claim-access'],'axis_ids'=>['axis-1'],'outcome_id'=>'outcome-a'];
$pathHtml=vg_comparison_render_decision_frame($pathBundle);$hardPos=strpos($pathHtml,'Hard path condition');$prefPos=strpos($pathHtml,'Preference path condition');$tiePos=strpos($pathHtml,'Tie path condition');
$results['reviewed_rule_path_only']=$hardPos!==false&&$prefPos>$hardPos&&$tiePos>$prefPos&&!str_contains($pathHtml,'PRIVATE UNUSED');
$escapedBundle=$bundle;$escapedBundle['render_contract']['decision_heading']='Decision "quoted" & checked';$escapedBundle['axes'][0]['label']='Access & "timing"';$escapedBundle['sources'][0]['url']='https://source-1.example.vn/guidance?x=1&y=2';
$escapedDecision=vg_comparison_render_decision_frame($escapedBundle);$escapedSources=vg_comparison_render_source_trail($escapedBundle);
$results['escaping_boundaries']=str_contains($escapedDecision,'Decision &quot;quoted&quot; &amp; checked')&&str_contains($escapedDecision,'Access &amp; &quot;timing&quot;')&&str_contains($escapedSources,'?x=1&#038;y=2')&&count(array_filter($GLOBALS['vg_test_escape_calls'],fn($call)=>$call[0]==='html'))>0&&count(array_filter($GLOBALS['vg_test_escape_calls'],fn($call)=>$call[0]==='attr'))>0&&count(array_filter($GLOBALS['vg_test_escape_calls'],fn($call)=>$call[0]==='url'))>0;
$entityBundle=$bundle;$entityBundle['render_contract']['decision_heading']='Already &amp; reviewed';$entityHtml=vg_comparison_render_decision_frame($entityBundle);$results['wordpress_entity_preservation']=str_contains($entityHtml,'<h2>Already &amp; reviewed</h2>')&&str_contains($entityHtml,'aria-label="Already &amp; reviewed"')&&!str_contains($entityHtml,'&amp;amp;');
$GLOBALS['vg_test_home_url_mode']='dangerous';$dangerousRouteHtml=vg_comparison_render_related_routes($bundle);$GLOBALS['vg_test_home_url_mode']='quote';$quotedRouteHtml=vg_comparison_render_related_routes($bundle);$GLOBALS['vg_test_home_url_mode']='normal';$results['wordpress_url_safety']=$dangerousRouteHtml===''&&$quotedRouteHtml!==''&&!str_contains($quotedRouteHtml,'" onclick="')&&str_contains($quotedRouteHtml,'&quot;')&&str_contains($quotedRouteHtml,'%20onclick=');
$allOutput=$decision.$sources.$routes.$updates;$forbiddenPublic=['target_blank'=>'target="_blank"','script'=>'<script','style'=>'<style','event'=>'onclick=','shortcode'=>'[vg_','block'=>'<!-- wp','confidence'=>'confidence','weight'=>'weight','score'=>'score','axis_id'=>'axis-1','lens_id'=>'lens-1','author_id'=>'author-one','reviewer_id'=>'reviewer-one','provenance'=>str_repeat('b',64),'local_path'=>'C:\\'];foreach($forbiddenPublic as $name=>$needle){$results['public_absent_'.$name]=!str_contains(strtolower($allOutput),strtolower($needle));}
$sourceIdBundle=$bundle;$sourceIdBundle['sources'][0]['source_id']='private-source-stable-id';$results['public_absent_source_id']=!str_contains(vg_comparison_render_source_trail($sourceIdBundle),'private-source-stable-id');
$results['public_absent_raw_bundle']=!str_contains($allOutput,vg_test_json($bundle));$results['public_absent_source_count']=preg_match('/\b(?:6|six)\s+sources?\b/i',$allOutput)!==1;
$sourceOrderBundle=$bundle;$sourceOrderBundle['sources'][4]['source_class']='conditions_heritage';$orderedSources=vg_comparison_render_source_trail($sourceOrderBundle);$groupLabels=['National official','Local official','Operational','Conditions and heritage','Independent corroboration'];$last=-1;$ordered=true;foreach($groupLabels as $label){$pos=strpos($orderedSources,$label);if($pos===false||$pos<=$last){$ordered=false;break;}$last=$pos;}
$results['source_group_order']=$ordered&&substr_count($orderedSources,'class="vg-comparison-source"')===count($sourceOrderBundle['sources']);
$results['source_public_fields']=str_contains($orderedSources,'Publisher 2')&&str_contains($orderedSources,'Fixture Locality')&&preg_match('/<a[^>]*lang="vi"[^>]*>Checked source 2<\/a>/',$orderedSources)===1&&str_contains($orderedSources,'2026-08-03')&&str_contains($orderedSources,'Access and transport')&&str_contains($orderedSources,'Primary');
$routeLabels=['Deepen place','Build route','Check practical'];$last=-1;$ordered=true;foreach($routeLabels as $label){$pos=strpos($routes,$label);if($pos===false||$pos<=$last){$ordered=false;break;}$last=$pos;}
$results['route_group_order']=$ordered&&substr_count($routes,'class="vg-comparison-route"')===6&&str_contains($routes,'href="https://vietnamguide.example/destinations/hanoi-travel-guide/"');
$results['update_public_fields']=str_contains($updates,'2026-08-03')&&str_contains($updates,'Source refresh')&&str_contains($updates,'Refreshed source checks')&&str_contains($updates,'Decision frame')&&!str_contains($updates,'reviewer-one');
$tooSmall=$bundle;$tooSmall['render_contract']['max_visible_characters']=10;$results['decision_visible_limit']=vg_comparison_render_decision_frame($tooSmall)==='';
$badSources=$bundle;$badSources['sources']='bad';$badRoutes=$bundle;$badRoutes['related_routes']='bad';$badUpdates=$bundle;$badUpdates['update_log']='bad';$body='ARTICLE_BODY_SENTINEL';
$results['module_isolation_sources']=vg_comparison_render_source_trail($badSources)===''&&vg_comparison_render_decision_frame($badSources)!==''&&vg_comparison_render_related_routes($badSources)!==''&&vg_comparison_render_update_log($badSources)!==''&&str_contains($body.vg_comparison_render_decision_frame($badSources),'ARTICLE_BODY_SENTINEL');
$results['module_isolation_routes']=vg_comparison_render_related_routes($badRoutes)===''&&vg_comparison_render_decision_frame($badRoutes)!==''&&vg_comparison_render_source_trail($badRoutes)!==''&&vg_comparison_render_update_log($badRoutes)!=='';
$results['module_isolation_updates']=vg_comparison_render_update_log($badUpdates)===''&&vg_comparison_render_decision_frame($badUpdates)!==''&&vg_comparison_render_source_trail($badUpdates)!==''&&vg_comparison_render_related_routes($badUpdates)!=='';
$unreviewedBundle=$bundle;$unreviewedBundle['editorial']['reviewed_guide']=false;$results['unreviewed_bundle_suppressed']=vg_comparison_render_decision_frame($unreviewedBundle)===''&&vg_comparison_render_source_trail($unreviewedBundle)===''&&vg_comparison_render_related_routes($unreviewedBundle)===''&&vg_comparison_render_update_log($unreviewedBundle)===''&&str_contains($body.vg_comparison_render_decision_frame($unreviewedBundle),'ARTICLE_BODY_SENTINEL');
$duplicateOptionBundle=$bundle;$duplicateOption=$bundle['options'][0];$duplicateOption['label']='Option A alternate';$duplicateOption['summary']='Different metadata with the same semantic option identity.';$duplicateOptionBundle['options'][]=$duplicateOption;$results['duplicate_option_id_rejected']=vg_comparison_render_decision_frame($duplicateOptionBundle)==='';
$optionC=['option_id'=>'option-c','label'=>'Option C','summary'=>'A third reviewed option for assessment coverage tests.'];
$duplicateAssessment=$bundle;$duplicateAssessment['axes'][0]['assessments'][1]['option_id']='option-a';$results['duplicate_assessment_id_rejected']=vg_comparison_render_decision_frame($duplicateAssessment)==='';
$missingAssessment=$bundle;$missingAssessment['options'][]=$optionC;$missingAssessment['axes'][0]['option_ids']=['option-a','option-b','option-c'];$results['missing_assessment_rejected']=vg_comparison_render_decision_frame($missingAssessment)==='';
$foreignAssessment=$bundle;$foreignAssessment['options'][]=$optionC;$foreignAssessment['axes'][0]['assessments'][1]['option_id']='option-c';$results['foreign_assessment_rejected']=vg_comparison_render_decision_frame($foreignAssessment)==='';
$extraAssessment=$bundle;$extraAssessment['options'][]=$optionC;$extraAssessment['axes'][0]['assessments'][]=['option_id'=>'option-c','outcome'=>'Option C adds an undeclared assessment.'];$results['extra_assessment_rejected']=vg_comparison_render_decision_frame($extraAssessment)==='';
$reorderedAssessment=$bundle;$reorderedAssessment['axes'][0]['assessments']=array_reverse($reorderedAssessment['axes'][0]['assessments']);$results['reordered_assessment_rejected']=vg_comparison_render_decision_frame($reorderedAssessment)==='';
$duplicateAxis=$bundle;$axisCopy=$bundle['axes'][0];$axisCopy['label']='Duplicate axis identity';$duplicateAxis['axes'][]=$axisCopy;$results['duplicate_axis_id_rejected']=vg_comparison_render_decision_frame($duplicateAxis)==='';
$duplicateLens=$bundle;$duplicateLens['traveler_lenses'][1]['lens_id']=$duplicateLens['traveler_lenses'][0]['lens_id'];$results['duplicate_lens_id_rejected']=vg_comparison_render_decision_frame($duplicateLens)==='';
$duplicateLensRule=$bundle;$ruleOne=$bundle['traveler_lenses'][0]['rule_path'][0];$ruleOne['condition']='First condition';$ruleOne['outcome']='First outcome';$ruleTwo=$ruleOne;$ruleTwo['order']=2;$ruleTwo['kind']='preference';$ruleTwo['condition']='Second condition';$ruleTwo['outcome']='Second outcome';$duplicateLensRule['traveler_lenses'][0]['rule_path']=[$ruleOne,$ruleTwo];$results['duplicate_lens_rule_id_rejected']=vg_comparison_render_decision_frame($duplicateLensRule)==='';
$duplicateSource=$bundle;$sourceCopy=$bundle['sources'][0];$sourceCopy['publisher_id']='publisher-copy';$sourceCopy['publisher_name']='Different publisher metadata';$sourceCopy['organization_id']='organization-copy';$sourceCopy['canonical_domain']='source-copy.example.vn';$sourceCopy['title']='Different title with duplicate source identity';$sourceCopy['url']='https://source-copy.example.vn/guidance';$duplicateSource['sources'][]=$sourceCopy;$results['duplicate_source_id_rejected']=vg_comparison_render_source_trail($duplicateSource)==='';
$duplicateRoute=$bundle;$routeCopy=$bundle['related_routes'][0];$routeCopy['label']='Different label with duplicate route path';$routeCopy['route_group']='check_practical';$duplicateRoute['related_routes'][]=$routeCopy;$results['duplicate_route_path_rejected']=vg_comparison_render_related_routes($duplicateRoute)==='';
$badDecisionModule=$bundle;$badDecisionModule['primary_decision']='bad';$results['module_isolation_decision']=vg_comparison_render_decision_frame($badDecisionModule)===''&&vg_comparison_render_source_trail($badDecisionModule)!==''&&vg_comparison_render_related_routes($badDecisionModule)!==''&&vg_comparison_render_update_log($badDecisionModule)!==''&&str_contains($body.vg_comparison_render_source_trail($badDecisionModule),'ARTICLE_BODY_SENTINEL');
$rendererExceptions=true;foreach(['vg_comparison_render_decision_frame','vg_comparison_render_source_trail','vg_comparison_render_related_routes','vg_comparison_render_update_log'] as $renderer){$GLOBALS['vg_test_escape_throw']='html';$rendererExceptions=$rendererExceptions&&$renderer($bundle)==='';$GLOBALS['vg_test_escape_throw']='';}$results['renderer_exceptions_local']=$rendererExceptions&&vg_comparison_render_decision_frame($bundle)!==''&&vg_comparison_render_source_trail($bundle)!==''&&vg_comparison_render_related_routes($bundle)!==''&&vg_comparison_render_update_log($bundle)!==''&&str_contains($body.vg_comparison_render_update_log($bundle),'ARTICLE_BODY_SENTINEL');
$scoredDecision=$bundle;$scoredDecision['primary_decision']['confidence']=100;$badMapping=$bundle;$badMapping['sources'][0]['mappings']='bad';$privateUpdate=$bundle;$privateUpdate['editorial']['reviewer_notes']='private';$shortcodeText=$bundle;$shortcodeText['axes'][0]['label']='[vg_source_trail]';$htmlText=$bundle;$htmlText['render_contract']['decision_heading']='<em>unsafe</em>';$badUrl=$bundle;$badUrl['sources'][0]['url']='javascript:alert(1)';
$results['direct_reject_scoring']=vg_comparison_render_decision_frame($scoredDecision)==='';$results['direct_reject_mapping']=vg_comparison_render_source_trail($badMapping)==='';$results['direct_reject_private_update']=vg_comparison_render_update_log($privateUpdate)==='';$results['direct_reject_shortcode_text']=vg_comparison_render_decision_frame($shortcodeText)==='';$results['direct_reject_html_text']=vg_comparison_render_decision_frame($htmlText)==='';$results['direct_reject_url']=vg_comparison_render_source_trail($badUrl)==='';
$malformedClosed=false;set_error_handler(static function(int $severity,string $message,string $file,int $line):never{throw new ErrorException($message,0,$severity,$file,$line);});try{$malformedClosed=vg_comparison_render_decision_frame('bad')===''&&vg_comparison_render_source_trail([])===''&&vg_comparison_render_related_routes(null)===''&&vg_comparison_render_update_log(['update_log'=>[]])==='';}catch(Throwable){}finally{restore_error_handler();}$results['malformed_renderers_fail_closed']=$malformedClosed;
$warningCount=0;$badDecisionShape=$bundle;$badDecisionShape['primary_decision']='bad';set_error_handler(static function()use(&$warningCount):bool{++$warningCount;return true;});try{$badDecisionOutput=vg_comparison_render_decision_frame($badDecisionShape);}finally{restore_error_handler();}$results['malformed_renderer_no_warnings']=$badDecisionOutput===''&&$warningCount===0;
$lensScore=$bundle;$lensScore['traveler_lenses'][0]['score']=10;$axisType=$bundle;$axisType['axes'][0]['option_ids']='bad';$warningCount=0;set_error_handler(static function()use(&$warningCount):bool{++$warningCount;return true;});try{$axisTypeOutput=vg_comparison_render_decision_frame($axisType);}finally{restore_error_handler();}$results['decision_nested_contract_closed']=vg_comparison_render_decision_frame($lensScore)===''&&$axisTypeOutput===''&&$warningCount===0;
$GLOBALS['vg_test_current_id']=$post->ID;$beforeAttrLogs=is_file($logFile)?count(file($logFile,FILE_IGNORE_NEW_LINES)?:[]):0;$attrResults=[vg_comparison_shortcode_source_trail(['secret'=>'do-not-log']),vg_comparison_shortcode_update_log(['secret'=>'do-not-log']),vg_comparison_shortcode_related_routes(['secret'=>'do-not-log'])];$afterAttrLines=is_file($logFile)?(file($logFile,FILE_IGNORE_NEW_LINES)?:[]):[];$attrLogSlice=array_slice($afterAttrLines,$beforeAttrLogs);
$results['active_attributes_rejected']=$attrResults===['','','']&&count($attrLogSlice)===1&&str_contains($attrLogSlice[0],'code=E_SHORTCODE_ATTR')&&!str_contains($attrLogSlice[0],'secret')&&!str_contains($attrLogSlice[0],'do-not-log');
$fallbackAtts=['id'=>'007','title'=>'A&B','nested'=>['bytes'=>"a\0b"]];$fallbackSerialized=serialize($fallbackAtts);function vg_test_all_shortcode_fallbacks(array $atts): bool {$GLOBALS['vg_test_baseline_calls']=[];$outputs=[vg_comparison_shortcode_source_trail($atts),vg_comparison_shortcode_update_log($atts),vg_comparison_shortcode_related_routes($atts)];return $outputs===['BASELINE_SOURCE','BASELINE_UPDATE','BASELINE_ROUTES']&&$GLOBALS['vg_test_baseline_calls']===[['source',$atts],['update',$atts],['routes',$atts]];}
$GLOBALS['vg_test_current_id']=0;$results['fallback_invalid_current_id']=vg_test_all_shortcode_fallbacks($fallbackAtts)&&serialize($fallbackAtts)===$fallbackSerialized;
$GLOBALS['vg_test_current_id']=$post->ID;$GLOBALS['vg_test_get_post_mode']='invalid';$results['fallback_invalid_post']=vg_test_all_shortcode_fallbacks($fallbackAtts);$GLOBALS['vg_test_get_post_mode']='throw';$results['fallback_throwing_post']=vg_test_all_shortcode_fallbacks($fallbackAtts);$GLOBALS['vg_test_get_post_mode']='normal';
$GLOBALS['vg_test_path_mode']='invalid';$results['fallback_invalid_path']=vg_test_all_shortcode_fallbacks($fallbackAtts);$GLOBALS['vg_test_path_mode']='throw';$results['fallback_throwing_path']=vg_test_all_shortcode_fallbacks($fallbackAtts);$GLOBALS['vg_test_path_mode']='normal';
$GLOBALS['vg_test_gate_mode']='throw';$results['fallback_throwing_gate']=vg_test_all_shortcode_fallbacks($fallbackAtts);$GLOBALS['vg_test_gate_mode']='normal';
$activePost=vg_test_post(6001);$activeBundle=vg_test_bundle($activePost->ID,$activePost->path);vg_test_install($activePost,vg_test_json($activeBundle));$GLOBALS['vg_test_current_id']=$activePost->ID;$activeModules=[vg_comparison_shortcode_source_trail(),vg_comparison_shortcode_update_log(),vg_comparison_shortcode_related_routes()];
$results['active_shortcode_modules']=$activeModules[0]!==''&&$activeModules[1]!==''&&$activeModules[2]!==''&&$GLOBALS['vg_test_reads'][$activePost->ID]===1;
$inactivePost=new WP_Post(6002,'compare/runtime-inactive');$GLOBALS['vg_test_posts'][$inactivePost->ID]=$inactivePost;$GLOBALS['vg_test_active']=[];$GLOBALS['vg_test_current_id']=$inactivePost->ID;$GLOBALS['vg_test_baseline_calls']=[];$inactiveResults=[vg_comparison_shortcode_source_trail(['id'=>9]),vg_comparison_shortcode_update_log(['id'=>9]),vg_comparison_shortcode_related_routes(['title'=>'Legacy'])];
$results['inactive_shortcode_fallback']=$inactiveResults===['BASELINE_SOURCE','BASELINE_UPDATE','BASELINE_ROUTES']&&$GLOBALS['vg_test_baseline_calls']===[['source',['id'=>9]],['update',['id'=>9]],['routes',['title'=>'Legacy']]]&&!isset($GLOBALS['vg_test_reads'][$inactivePost->ID]);
$invalidPost=vg_test_post(6003);vg_test_install($invalidPost,'');$GLOBALS['vg_test_current_id']=$invalidPost->ID;$GLOBALS['vg_test_baseline_calls']=[];$invalidResults=[vg_comparison_shortcode_source_trail(),vg_comparison_shortcode_update_log(),vg_comparison_shortcode_related_routes()];
$results['invalid_shortcode_fallback']=$invalidResults===['BASELINE_SOURCE','BASELINE_UPDATE','BASELINE_ROUTES']&&count($GLOBALS['vg_test_baseline_calls'])===3&&$GLOBALS['vg_test_reads'][$invalidPost->ID]===1;
$GLOBALS['vg_test_shortcode_ops']=[];vg_comparison_register_shortcode_replacements();vg_comparison_register_shortcode_replacements();
$results['shortcode_registration_idempotent']=count($GLOBALS['vg_test_shortcode_ops'])===6&&$GLOBALS['vg_test_shortcodes']===['vg_source_trail'=>'vg_comparison_shortcode_source_trail','vg_update_log'=>'vg_comparison_shortcode_update_log','vg_related_routes'=>'vg_comparison_shortcode_related_routes'];
$linePost=vg_test_post(5005);$lineBundle=json_decode(base64_decode(getenv('VG_RUNTIME_LINE_TERMINATOR_BUNDLE'),true),true,128,JSON_THROW_ON_ERROR);vg_test_install($linePost,vg_test_json($lineBundle));$lineLoaded=vg_comparison_load_bundle($linePost);$results['canonical_line_terminator_bundle_hash']=$lineLoaded['ok']===true&&$lineLoaded['bundle_hash']===$lineBundle['bundle_hash'];
$floatCases=['decimal'=>'1.0','exponent'=>'1e3'];$floatId=5050;foreach($floatCases as $name=>$literal){$p=vg_test_post(++$floatId);$b=vg_test_bundle($p->ID,$p->path);$raw=vg_test_json($b);$needle='"max_visible_characters":1800';$replacement='"max_visible_characters":'.$literal;$raw=str_replace($needle,$replacement,$raw,$replaced);vg_test_install($p,$raw);$results['float_'.$name]=$replaced===1&&vg_test_reason(vg_comparison_load_bundle($p),'E_SCHEMA');}
$inactive=new WP_Post(5002,'compare/runtime-inactive'); $GLOBALS['vg_test_posts'][$inactive->ID]=$inactive; $GLOBALS['vg_test_active']=[]; $results['inactive_zero_read']=vg_test_reason(vg_comparison_load_bundle($inactive),'E_INACTIVE')&&!isset($GLOBALS['vg_test_reads'][$inactive->ID]);
$results['missing_post']=vg_test_reason(vg_comparison_load_bundle(5003),'E_POST'); $GLOBALS['vg_test_throw']=5004; $results['throwable']=vg_test_reason(vg_comparison_load_bundle(5004),'E_RUNTIME'); $GLOBALS['vg_test_throw']=0;
$numericStringResult=false;try{$numericStringResult=vg_test_reason(vg_comparison_load_bundle(new WP_Post('5001','compare/old-quarter-vs-french-quarter-vs-west-lake')),'E_POST');}catch(Throwable){}$results['post_numeric_string_id']=$numericStringResult;
$arrayIdResult=false;try{$arrayIdResult=vg_test_reason(vg_comparison_load_bundle(new WP_Post([], 'compare/old-quarter-vs-french-quarter-vs-west-lake')),'E_POST');}catch(Throwable){}$results['post_array_id']=$arrayIdResult;
$results['duplicate_top']=vg_comparison_bundle_duplicate_keys('{"a":1,"a":2}');
$results['duplicate_nested']=vg_comparison_bundle_duplicate_keys('{"a":{"b":1,"b":2}}');
$results['duplicate_escaped']=vg_comparison_bundle_duplicate_keys('{"a\\u0062":1,"ab":2}');
$rawCases=['empty'=>['','E_META'],'oversize'=>[str_repeat('x',65537),'E_SIZE'],'bom'=>["\xEF\xBB\xBF{}",'E_BOM'],'utf8'=>["{\"x\":\"\xC3\x28\"}",'E_UTF8'],'control'=>["{\"x\":\"bad\x00\"}",'E_CONTROL'],'duplicate'=>['{"a":1,"a":2}','E_JSON_DUPLICATE'],'json'=>['{"a":','E_JSON'],'root'=>['[]','E_SCHEMA']]; $id=5100;
foreach($rawCases as $name=>[$raw,$reason]){$p=vg_test_post(++$id);vg_test_install($p,$raw);$one=vg_comparison_load_bundle($p);$results[$name]=vg_test_reason($one,$reason)&&vg_comparison_load_bundle($p)===$one&&$GLOBALS['vg_test_reads'][$p->ID]===1;}
$cases=[];
$p=vg_test_post(5201);$b=vg_test_bundle($p->ID,$p->path);$b['unknown']=true;$b=vg_test_rehash($b);$cases['unknown_key']=[$p,$b,[],'E_SCHEMA'];
$p=vg_test_post(5202);$b=vg_test_bundle($p->ID,'compare/ninh-binh-day-trip-vs-overnight');$cases['path']=[$p,$b,[],'E_PATH'];
$p=vg_test_post(5203);$b=vg_test_bundle(999,$p->path);$cases['post_id']=[$p,$b,[],'E_POST_ID'];
$p=vg_test_post(5204);$b=vg_test_bundle($p->ID,$p->path);$cases['manifest']=[$p,$b,['manifest_version'=>'manifest-other'],'E_MANIFEST_VERSION'];
$p=vg_test_post(5205);$b=vg_test_bundle($p->ID,$p->path);$cases['source']=[$p,$b,['entry'=>['source_registry_version'=>'sources-other']],'E_SOURCE_REGISTRY_VERSION'];
$p=vg_test_post(5206);$b=vg_test_bundle($p->ID,$p->path);$cases['org']=[$p,$b,['organization_registry_version'=>'organizations-other'],'E_ORGANIZATION_REGISTRY_VERSION'];
$p=vg_test_post(5207);$b=vg_test_bundle($p->ID,$p->path);$b['activation_artifact_version']='activation-other';$b=vg_test_rehash($b);$cases['activation']=[$p,$b,[],'E_ACTIVATION_VERSION'];
$p=vg_test_post(5208);$b=vg_test_bundle($p->ID,$p->path);$b['bundle_hash']=str_repeat('a',64);$cases['self_hash']=[$p,$b,['entry'=>['bundle_hash'=>str_repeat('a',64)]],'E_BUNDLE_HASH'];
$p=vg_test_post(5209);$b=vg_test_bundle($p->ID,$p->path);$cases['snapshot_hash']=[$p,$b,['entry'=>['bundle_hash'=>str_repeat('c',64)]],'E_BUNDLE_HASH'];
$p=vg_test_post(5210);$b=vg_test_bundle($p->ID,$p->path);$cases['snapshot_path']=[$p,$b,['paths'=>['compare/other']],'E_ACTIVATION'];
$p=vg_test_post(5211);$b=vg_test_bundle($p->ID,$p->path);$b['schema_version']='v3';$b=vg_test_rehash($b);$cases['version']=[$p,$b,['entry'=>['schema_version'=>'v2','bundle_hash'=>$b['bundle_hash']]],'E_VERSION'];
$p=vg_test_post(5212);$b=vg_test_bundle($p->ID,$p->path);$b['options']='bad';$b=vg_test_rehash($b);$cases['shape']=[$p,$b,[],'E_SCHEMA'];
foreach($cases as $name=>[$p,$b,$expect,$reason]){vg_test_install($p,vg_test_json($b),$expect);$results[$name]=vg_test_reason(vg_comparison_load_bundle($p),$reason);}
$snapshotMutations=[
    'missing_key'=>function($s){unset($s['snapshot_version']);return $s;},
    'extra_key'=>function($s){$s['unknown']=true;return $s;},
    'snapshot_version'=>function($s){$s['snapshot_version']='UPPER';return $s;},
    'snapshot_version_other'=>function($s){$s['snapshot_version']='snapshot-v3';return $s;},
    'stage'=>function($s){$s['activation_stage']='partial';return $s;},
    'date'=>function($s){$s['activated_on']='2026-08-03T00:00:00Z';return $s;},
    'invalid_calendar_date'=>function($s){$s['activated_on']='2026-02-31';return $s;},
    'artifact'=>function($s){$s['activation_artifact_version']='activation-other';return $s;},
    'paths_duplicate'=>function($s){$s['paths']=[$s['paths'][0],$s['paths'][0]];return $s;},
    'paths_order'=>function($s){$s['paths']=[$s['paths'][0],'compare/a-path'];$second=$s['bundle_hashes'][0];$second['path']='compare/a-path';$s['bundle_hashes'][]=$second;return $s;},
    'entry_extra'=>function($s){$s['bundle_hashes'][0]['unknown']=true;return $s;},
    'entry_artifact'=>function($s){$s['bundle_hashes'][0]['activation_artifact_version']='activation-other';return $s;},
    'entry_path_set'=>function($s){$s['bundle_hashes'][0]['path']='compare/other';return $s;},
    'entry_duplicate'=>function($s){$s['paths'][]='compare/z-path';$second=$s['bundle_hashes'][0];$second['path']='compare/z-path';$s['bundle_hashes'][]=$second;$s['bundle_hashes'][]=$second;return $s;},
    'entry_order'=>function($s){$s['paths']=[$s['paths'][0],'compare/z-path'];$second=$s['bundle_hashes'][0];$second['path']='compare/z-path';$s['bundle_hashes']=[$second,$s['bundle_hashes'][0]];return $s;},
    'entry_schema'=>function($s){$s['bundle_hashes'][0]['schema_version']='v3';return $s;},
    'entry_hash'=>function($s){$s['bundle_hashes'][0]['bundle_hash']='abc';return $s;},
];
$snapshotId=5500;foreach($snapshotMutations as $name=>$mutate){$p=vg_test_post(++$snapshotId);$b=vg_test_bundle($p->ID,$p->path);vg_test_install($p,vg_test_json($b));$GLOBALS['vg_test_snapshot']=$mutate($GLOBALS['vg_test_snapshot']);$actual=vg_comparison_load_bundle($p);$results['snapshot_'.$name]=vg_test_reason($actual,'E_ACTIVATION');}
$schemaMutations=[
    'top_missing'=>fn($b)=>array_diff_key($b,['field_note'=>true]),
    'editorial_unknown'=>function($b){$b['editorial']['unknown']=true;return $b;},
    'editorial_bool'=>function($b){$b['editorial']['reviewed_guide']='true';return $b;},
    'editorial_id'=>function($b){$b['editorial']['written_by_identity_id']='UPPER';return $b;},
    'editorial_date'=>function($b){$b['editorial']['last_meaningful_update']='2026-02-31';return $b;},
    'editorial_text'=>function($b){$b['editorial']['update_summary']='<script>alert(1)</script>';return $b;},
    'editorial_reason'=>function($b){$b['editorial']['change_reason']='unknown';return $b;},
    'editorial_labels_duplicate'=>function($b){$b['editorial']['affected_public_labels']=['Sources','Sources'];return $b;},
    'archetype_enum'=>function($b){$b['archetype']='generic';return $b;},
    'localities_empty'=>function($b){$b['localities']=[];return $b;},
    'localities_duplicate'=>function($b){$b['localities']=['hanoi','hanoi'];return $b;},
    'option_count'=>function($b){$b['options']=[$b['options'][0]];return $b;},
    'option_unknown'=>function($b){$b['options'][0]['unknown']=true;return $b;},
    'option_label_length'=>function($b){$b['options'][0]['label']=str_repeat('x',49);return $b;},
    'decision_outcome'=>function($b){$b['primary_decision']['outcome']='unknown';return $b;},
    'decision_missing_winner'=>function($b){unset($b['primary_decision']['winner_option_id']);return $b;},
    'decision_extra_winner'=>function($b){$b['primary_decision']['outcome']='no_clear_winner';return $b;},
    'decision_combine_extra_winner'=>function($b){$b['primary_decision']['outcome']='combine_or_sequence';return $b;},
    'decision_rule_unknown'=>function($b){$b['primary_decision']['rules'][0]['unknown']=true;return $b;},
    'decision_rule_kind'=>function($b){$b['primary_decision']['rules'][0]['kind']='unknown';return $b;},
    'decision_rule_order'=>function($b){$b['primary_decision']['rules'][0]['order']=9;return $b;},
    'decision_rule_duplicate_ids'=>function($b){$b['primary_decision']['rules'][0]['claim_ids']=['claim-access','claim-access'];return $b;},
    'field_note_control'=>function($b){$b['field_note']="bad\x00text";return $b;},
    'moat_too_many'=>function($b){$b['evidence_moat']=['a','b','c','d','e'];return $b;},
    'axes_count'=>function($b){$b['axes']=array_slice($b['axes'],0,2);return $b;},
    'axis_unknown'=>function($b){$b['axes'][0]['unknown']=true;return $b;},
    'axis_decisive_type'=>function($b){$b['axes'][0]['decisive']=1;return $b;},
    'axis_assessment_count'=>function($b){$b['axes'][0]['assessments']=[$b['axes'][0]['assessments'][0]];return $b;},
    'assessment_unknown'=>function($b){$b['axes'][0]['assessments'][0]['unknown']=true;return $b;},
    'lenses_count'=>function($b){$b['traveler_lenses']=array_slice($b['traveler_lenses'],0,2);return $b;},
    'lens_unknown'=>function($b){$b['traveler_lenses'][0]['unknown']=true;return $b;},
    'lens_no_tradeoff'=>function($b){unset($b['traveler_lenses'][0]['trade_off'],$b['traveler_lenses'][0]['reversal_condition']);return $b;},
    'lens_reversal_validity'=>function($b){unset($b['traveler_lenses'][0]['trade_off']);$b['traveler_lenses'][0]['reversal_condition']='<bad>';return $b;},
    'lens_rule_order'=>function($b){$b['traveler_lenses'][0]['rule_path'][0]['order']=6;return $b;},
    'sources_count'=>function($b){$b['sources']=array_slice($b['sources'],0,5);return $b;},
    'source_unknown'=>function($b){$b['sources'][0]['unknown']=true;return $b;},
    'source_class'=>function($b){$b['sources'][0]['source_class']='blog';return $b;},
    'source_domain'=>function($b){$b['sources'][0]['canonical_domain']='bad_domain';return $b;},
    'source_url'=>function($b){$b['sources'][0]['url']='javascript:alert(1)';return $b;},
    'source_evidence'=>function($b){$b['sources'][0]['evidence_label']='strong';return $b;},
    'source_date'=>function($b){$b['sources'][0]['checked_on']='2026-13-01';return $b;},
    'source_freshness_tier'=>function($b){$b['sources'][0]['freshness_tier']='old';return $b;},
    'source_freshness_state'=>function($b){$b['sources'][0]['freshness_state']='old';return $b;},
    'source_language'=>function($b){$b['sources'][0]['language']='eng';return $b;},
    'source_media'=>function($b){$b['sources'][0]['media_type']='xml';return $b;},
    'source_claim_group'=>function($b){$b['sources'][0]['claim_groups']=['unknown'];return $b;},
    'source_claim_group_duplicate'=>function($b){$b['sources'][0]['claim_groups']=['access_transport','access_transport'];return $b;},
    'mapping_unknown'=>function($b){$b['sources'][0]['mappings'][0]['unknown']=true;return $b;},
    'mapping_missing'=>function($b){unset($b['sources'][0]['mappings'][0]['axis_id']);return $b;},
    'mapping_option_id'=>function($b){$b['sources'][0]['mappings'][0]['option_id']='UPPER';return $b;},
    'mapping_outcome_id'=>function($b){$b['sources'][0]['mappings'][0]['outcome_id']='bad value';return $b;},
    'routes_count'=>function($b){$b['related_routes']=array_slice($b['related_routes'],0,5);return $b;},
    'route_unknown'=>function($b){$b['related_routes'][0]['unknown']=true;return $b;},
    'route_group'=>function($b){$b['related_routes'][0]['route_group']='unknown';return $b;},
    'route_group_coverage'=>function($b){foreach($b['related_routes'] as &$route){$route['route_group']='deepen_place';}unset($route);return $b;},
    'update_unknown'=>function($b){$b['update_log'][0]['unknown']=true;return $b;},
    'update_reason'=>function($b){$b['update_log'][0]['change_reason']='unknown';return $b;},
    'module_unknown'=>function($b){$b['module_requirements']['unknown']=true;return $b;},
    'module_cache_order'=>function($b){$b['module_requirements']['cache_key_fields']=array_reverse($b['module_requirements']['cache_key_fields']);return $b;},
    'module_feature'=>function($b){$b['module_requirements']['required_features']=['unknown','decision_frame','evidence_labels'];return $b;},
    'provenance_hash'=>function($b){$b['provenance_hash']='abc';return $b;},
    'render_unknown'=>function($b){$b['render_contract']['unknown']=true;return $b;},
    'render_language'=>function($b){$b['render_contract']['page_language']='english';return $b;},
    'render_limit'=>function($b){$b['render_contract']['max_visible_characters']=1801;return $b;},
];
$mutationId=5600;foreach($schemaMutations as $name=>$mutate){$p=vg_test_post(++$mutationId);$b=$mutate(vg_test_bundle($p->ID,$p->path));$b=vg_test_rehash($b);vg_test_install($p,vg_test_json($b));$results['schema_'.$name]=vg_test_reason(vg_comparison_load_bundle($p),'E_SCHEMA');}
$p=vg_test_post(5301);$v1=['schema_version'=>'v1','bundle_hash'=>str_repeat('0',64),'path'=>$p->path,'post_id'=>$p->ID,'title'=>'Runtime v1','decision'=>'Reviewed decision','sources'=>['a','b','c']];$h=$v1;unset($h['bundle_hash']);$v1['bundle_hash']=hash('sha256',vg_comparison_bundle_canonical_json($h));$before=serialize($v1);
$results['v1_pure']=vg_comparison_migrate_bundle_v1_to_v2($v1)===[]&&serialize($v1)===$before;vg_test_install($p,vg_test_json($v1),['entry'=>['schema_version'=>'v1']]);$results['v1_fallback']=vg_test_reason(vg_comparison_load_bundle($p),'E_MIGRATION');
$authoritative=vg_test_post(5901);$forged=new WP_Post(5901,'compare/forged');$validBundle=vg_test_bundle($authoritative->ID,$authoritative->path);vg_test_install($authoritative,vg_test_json($validBundle));$GLOBALS['vg_test_active']=[];$first=vg_comparison_load_bundle($forged);$GLOBALS['vg_test_active']=[$authoritative->path];$second=vg_comparison_load_bundle($authoritative->ID);$results['cache_reject_not_poisoned']=vg_test_reason($first,'E_INACTIVE')&&$second['ok']===true;
$authoritative2=vg_test_post(5902);$validBundle2=vg_test_bundle($authoritative2->ID,$authoritative2->path);vg_test_install($authoritative2,vg_test_json($validBundle2));$success=vg_comparison_load_bundle($authoritative2);$changed=$GLOBALS['vg_test_snapshot'];$changed['activation_artifact_version']='activation-other';$GLOBALS['vg_test_snapshots']=[$changed];$afterChange=vg_comparison_load_bundle($authoritative2->ID);$results['cache_success_not_stale']=$success['ok']===true&&vg_test_reason($afterChange,'E_ACTIVATION');
for($id=5401;$id<=5412;++$id){$p=vg_test_post($id);vg_test_install($p,'');vg_comparison_load_bundle($p);}vg_comparison_log_rejection('NOT_ALLOWED',9999);$logs=is_file($logFile)?file($logFile,FILE_IGNORE_NEW_LINES):[];$logs=array_values(array_filter($logs?:[],fn($line)=>str_contains($line,'VG_COMPARISON_REJECT')));
$results['logs_bounded']=count($logs)>0&&count($logs)<=8;$joined=implode("\n",$logs);$results['logs_safe']=!str_contains($joined,'private-')&&!str_contains($joined,'NOT_ALLOWED')&&!str_contains($joined,'{')&&preg_match('/[a-f0-9]{64}/',$joined)!==1;@unlink($logFile);
echo json_encode(['results'=>$results],JSON_THROW_ON_ERROR);
'@
    $tempRuntime = [System.IO.Path]::GetTempFileName()
    try {
        [System.IO.File]::WriteAllText($tempRuntime, $runtimeCode, (New-Object System.Text.UTF8Encoding($false)))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_BOOTSTRAP', $bootstrapPath)
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_REQUIRED_KEYS', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes(($requiredKeys | ConvertTo-Json -Compress))))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_POSITIVE_BUNDLE', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($positiveBundleJson)))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_LINE_TERMINATOR_BUNDLE', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($lineTerminatorBundleJson)))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_LINE_TERMINATOR_CANONICAL', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($lineTerminatorCanonical)))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_STAGE_INVENTORIES', [Convert]::ToBase64String([System.Text.Encoding]::UTF8.GetBytes($runtimeStageInventoriesJson)))
        $runtimeOutput = @(& $phpBinary '-d' 'display_errors=stderr' $tempRuntime 2>&1)
        $runtimeExit = $LASTEXITCODE
        $runtimeChecks++
        if ($runtimeExit -ne 0 -or $runtimeOutput.Count -ne 1) {
            $runtimeErrors += 'E_RUNTIME comparison loader: PHP runtime vector execution failed'
        } else {
            try {
                $runtimeResult = ([string]$runtimeOutput[0]) | ConvertFrom-Json
                foreach ($property in @($runtimeResult.results.PSObject.Properties)) {
                    $runtimeChecks++
                    if ($property.Value -ne $true) {
                        $runtimeErrors += "E_RUNTIME fixture/$($property.Name): PHP runtime vector failed"
                    }
                }
            } catch {
                $runtimeErrors += 'E_RUNTIME comparison loader: PHP runtime vector output was invalid'
            }
        }
    } finally {
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_BOOTSTRAP', $null)
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_REQUIRED_KEYS', $null)
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_POSITIVE_BUNDLE', $null)
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_LINE_TERMINATOR_BUNDLE', $null)
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_LINE_TERMINATOR_CANONICAL', $null)
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_STAGE_INVENTORIES', $null)
        Remove-Item -LiteralPath $tempRuntime -Force -ErrorAction SilentlyContinue
    }

    $fallbackCode = @'
<?php
declare(strict_types=1);
final class WP_Post { public function __construct(public int $ID, public string $path) {} }
$case=getenv('VG_FALLBACK_CASE');$post=new WP_Post(7001,'compare/old-quarter-vs-french-quarter-vs-west-lake');$GLOBALS['post']=$post;$GLOBALS['calls']=[];
if(!in_array($case,['missing_get_the_id','missing_baseline'],true)){function get_the_ID():mixed{return getenv('VG_FALLBACK_CASE')==='invalid_id'?0:$GLOBALS['post']->ID;}}
if($case!=='missing_get_post'){function get_post(int $id):mixed{$case=getenv('VG_FALLBACK_CASE');if($case==='throw_post'){throw new RuntimeException('private');}if($case==='invalid_post'){return (object)['ID'=>$id];}return $GLOBALS['post'];}}
if(!in_array($case,['missing_path'],true)){function vg_get_guide_path(WP_Post $post):string{$case=getenv('VG_FALLBACK_CASE');if($case==='throw_path'){throw new RuntimeException('private');}return $case==='invalid_path'?'INVALID PATH':$post->path;}}
if($case!=='missing_gate'){function vg_is_comparison_rollout_active_path(string $path):bool{if(getenv('VG_FALLBACK_CASE')==='throw_gate'){throw new RuntimeException('private');}return true;}}
function vg_comparison_bundle_valid_path(string $path):bool{return preg_match('/^[a-z0-9-]+(?:\/[a-z0-9-]+)+$/D',$path)===1;}
if(!in_array($case,['missing_loader'],true)){function vg_comparison_load_bundle(WP_Post|int $post):mixed{$case=getenv('VG_FALLBACK_CASE');if($case==='throw_loader'){throw new RuntimeException('private');}if($case==='invalid_loader'){return ['ok'=>true,'bundle'=>'bad'];}if($case==='rejected_loader'){return ['ok'=>false,'reason'=>'E_SCHEMA'];}return ['ok'=>true,'bundle'=>['public'=>'bundle']];}}
if($case!=='missing_renderer'){
function vg_comparison_render_source_trail(mixed $bundle):string{if(getenv('VG_FALLBACK_CASE')==='throw_renderer'){throw new RuntimeException('private');}return 'ENHANCED_SOURCE';}
function vg_comparison_render_update_log(mixed $bundle):string{if(getenv('VG_FALLBACK_CASE')==='throw_renderer'){throw new RuntimeException('private');}return 'ENHANCED_UPDATE';}
function vg_comparison_render_related_routes(mixed $bundle):string{if(getenv('VG_FALLBACK_CASE')==='throw_renderer'){throw new RuntimeException('private');}return 'ENHANCED_ROUTES';}
}
if($case!=='missing_baseline'){
function vg_shortcode_source_trail(array $atts=[]):string{$GLOBALS['calls'][]=['source',serialize($atts)];return 'BASELINE_SOURCE';}
function vg_shortcode_update_log(array $atts=[]):string{$GLOBALS['calls'][]=['update',serialize($atts)];return 'BASELINE_UPDATE';}
function vg_shortcode_related_routes(array $atts=[]):string{$GLOBALS['calls'][]=['routes',serialize($atts)];return 'BASELINE_ROUTES';}
}
require getenv('VG_RUNTIME_SHORTCODES');
$loaderCases=['missing_loader','invalid_loader','rejected_loader','throw_loader','throw_renderer','missing_renderer'];$atts=in_array($case,$loaderCases,true)?[]:['id'=>'007','title'=>'A&B','nested'=>['bytes'=>"a\0b"]];$serialized=serialize($atts);$outputs=[vg_comparison_shortcode_source_trail($atts),vg_comparison_shortcode_update_log($atts),vg_comparison_shortcode_related_routes($atts)];
$emptyCases=['throw_renderer','missing_renderer','missing_baseline'];$expectEmpty=in_array($case,$emptyCases,true);$expectedOutputs=$expectEmpty?['','','']:['BASELINE_SOURCE','BASELINE_UPDATE','BASELINE_ROUTES'];$expectedCalls=$expectEmpty?[]:[['source',$serialized],['update',$serialized],['routes',$serialized]];
echo json_encode(['ok'=>$outputs===$expectedOutputs&&$GLOBALS['calls']===$expectedCalls&&serialize($atts)===$serialized],JSON_THROW_ON_ERROR);
'@
    $tempFallback = [System.IO.Path]::GetTempFileName()
    try {
        [System.IO.File]::WriteAllText($tempFallback, $fallbackCode, (New-Object System.Text.UTF8Encoding($false)))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_SHORTCODES', $shortcodesPath)
        foreach ($fallbackCase in @(
            'missing_get_the_id', 'invalid_id', 'missing_get_post', 'invalid_post', 'throw_post',
            'missing_path', 'invalid_path', 'throw_path', 'missing_gate', 'throw_gate',
            'missing_loader', 'invalid_loader', 'rejected_loader', 'throw_loader',
            'throw_renderer', 'missing_renderer', 'missing_baseline'
        )) {
            [Environment]::SetEnvironmentVariable('VG_FALLBACK_CASE', $fallbackCase)
            $fallbackOutput = @(& $phpBinary '-d' 'display_errors=stderr' $tempFallback 2>&1)
            $runtimeChecks++
            if ($LASTEXITCODE -ne 0 -or $fallbackOutput.Count -ne 1) {
                $runtimeErrors += "E_RUNTIME fixture/fallback_${fallbackCase}: isolated shortcode vector execution failed"
                continue
            }
            try {
                $fallbackResult = [string]$fallbackOutput[0] | ConvertFrom-Json
                if ($fallbackResult.ok -ne $true) {
                    $runtimeErrors += "E_RUNTIME fixture/fallback_${fallbackCase}: shortcode fallback failed"
                }
            } catch {
                $runtimeErrors += "E_RUNTIME fixture/fallback_${fallbackCase}: isolated shortcode output was invalid"
            }
        }
    } finally {
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_SHORTCODES', $null)
        [Environment]::SetEnvironmentVariable('VG_FALLBACK_CASE', $null)
        Remove-Item -LiteralPath $tempFallback -Force -ErrorAction SilentlyContinue
    }

    $constantCode = @'
<?php
define('VG_COMPARISON_BUNDLE_SCHEMA_CURRENT', '999');
require getenv('VG_RUNTIME_BOOTSTRAP');
echo function_exists('vg_comparison_load_bundle') ? 'unsafe' : 'blocked';
'@
    $tempConstant = [System.IO.Path]::GetTempFileName()
    try {
        [System.IO.File]::WriteAllText($tempConstant, $constantCode, (New-Object System.Text.UTF8Encoding($false)))
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_BOOTSTRAP', $bootstrapPath)
        $constantOutput = @(& $phpBinary $tempConstant 2>&1)
        $runtimeChecks++
        if ($LASTEXITCODE -ne 0 -or $constantOutput.Count -ne 1 -or [string]$constantOutput[0] -cne 'blocked') {
            $runtimeErrors += "E_RUNTIME ${bootstrapRelativePath}: inconsistent predefined constants did not fail closed"
        }
    } finally {
        [Environment]::SetEnvironmentVariable('VG_RUNTIME_BOOTSTRAP', $null)
        Remove-Item -LiteralPath $tempConstant -Force -ErrorAction SilentlyContinue
    }

    return [ordered]@{ errors = @($runtimeErrors); checks = $runtimeChecks }
}

function Write-VerificationResult {
    param(
        [Parameter(Mandatory = $true)][AllowEmptyCollection()][string[]]$Errors,
        [Parameter(Mandatory = $true)][int]$Checks
    )

    if ($Json) {
        [ordered]@{
            ok = ($Errors.Count -eq 0)
            scope = $Scope
            as_of_date = $AsOfDate.ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
            checks = $Checks
            errors = @($Errors)
        } | ConvertTo-Json -Depth 10 -Compress
    } elseif ($Errors.Count -gt 0) {
        $Errors | ForEach-Object { Write-Output $_ }
    } else {
        Write-Output "OK ${Scope}: $Checks contract checks passed"
    }
}

$repoRoot = Get-RepoRoot
$errors = @()
$checks = 0
$validatorModule = $null
$validatorRelativePath = 'ops/comparison-rollout-validator.psm1'
$validatorPath = Join-Path $repoRoot $validatorRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
if (-not (Test-Path -LiteralPath $validatorPath -PathType Leaf)) {
    $errors += "E_MODULE ${validatorRelativePath}: validator module is missing"
} else {
    try {
        $validatorModule = Import-Module -Name $validatorPath -Force -PassThru -ErrorAction Stop
        $actualExports = @($validatorModule.ExportedFunctions.Keys | Sort-Object -CaseSensitive)
        $expectedExports = @($requiredModuleExports | Sort-Object -CaseSensitive)
        $checks++
        if (-not (Test-OrdinalSequence $actualExports $expectedExports)) {
            $errors += "E_MODULE ${validatorRelativePath}: exported function surface does not match the exact eight-function contract"
        }
    } catch {
        $errors += "E_MODULE ${validatorRelativePath}: module import failed"
    }
}

$requiredForScope = if ($Scope -ceq 'fixtures' -or $Scope -ceq 'approvals' -or $Scope -ceq 'runtime') { @($requiredInputs[0]) } else { @($requiredInputs) }
foreach ($relativePath in $requiredForScope) {
    $nativePath = $relativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
    if (-not (Test-Path -LiteralPath (Join-Path $repoRoot $nativePath) -PathType Leaf)) {
        $errors += "E_FILE ${relativePath}: required production input is missing"
    }
}

$schema = $null
$schemaRelativePath = $requiredInputs[0]
$schemaPath = Join-Path $repoRoot $schemaRelativePath.Replace('/', [System.IO.Path]::DirectorySeparatorChar)
if (Test-Path -LiteralPath $schemaPath -PathType Leaf) {
    try {
        $schemaText = [System.IO.File]::ReadAllText($schemaPath, [System.Text.Encoding]::UTF8)
        $schema = $schemaText | ConvertFrom-Json
        $checks++
        if ((Get-Property $schema '$schema') -cne 'https://json-schema.org/draft/2020-12/schema') {
            $errors += "E_SCHEMA ${schemaRelativePath}: `$schema must declare Draft 2020-12"
        }
        if (-not (Test-HasProperty $schema '$id') -or [string]::IsNullOrWhiteSpace([string](Get-Property $schema '$id'))) {
            $errors += "E_SCHEMA ${schemaRelativePath}: `$id is required"
        }
        $definitions = Get-Property $schema '$defs'
        foreach ($definitionName in $requiredDefinitions) {
            if ($null -eq $definitions -or -not (Test-HasProperty $definitions $definitionName)) {
                $errors += "E_SCHEMA ${schemaRelativePath}: missing `$defs.$definitionName"
            }
        }
        if ($schemaText -match '"(raw_html|script|style|shortcode|block_markup|runtime_fetch|reviewer_notes|run_id|source_body|confidence|weight)"\s*:') {
            $errors += "E_SCHEMA ${schemaRelativePath}: forbidden raw, private, runtime, or scoring field is declared"
        }
    } catch {
        $errors += "E_SCHEMA ${schemaRelativePath}: invalid JSON schema"
    }
}

if ($Scope -ceq 'approvals' -and $null -ne $schema -and $errors.Count -eq 0) {
    $approvalResult = Invoke-ApprovalContractValidation -Schema $schema
    $errors += @($approvalResult.errors)
    $checks += [int]$approvalResult.checks
}

if ($Scope -ceq 'runtime' -and $null -ne $schema -and $errors.Count -eq 0) {
    $runtimeResult = Invoke-BundleRuntimeValidation -Schema $schema
    $errors += @($runtimeResult.errors)
    $checks += [int]$runtimeResult.checks
}

if ($Scope -ceq 'fixtures' -and $null -ne $schema -and $errors.Count -eq 0) {
    $fixtureResult = Invoke-FixtureValidation $schema
    $errors += @($fixtureResult.errors)
    $checks += [int]$fixtureResult.checks

    if ($errors.Count -eq 0 -and $null -ne $validatorModule) {
        try {
            $resolverFixtureResult = Invoke-ResolverFixtureValidation -Schema $schema -EvaluationDate $AsOfDate
            $errors += @($resolverFixtureResult.errors)
            $checks += [int]$resolverFixtureResult.checks
        } catch {
            $errors += "E_MODULE ops/comparison-rollout-validator.psm1: resolver fixture execution failed: $($_.Exception.Message)"
        }
    }
}

Write-VerificationResult -Errors @($errors) -Checks $checks
if ($errors.Count -gt 0) {
    exit 1
}

exit 0
