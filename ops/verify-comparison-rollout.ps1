param(
    [string]$RepoRootOverride = '',
    [switch]$Json,
    [switch]$EmitArtifact,
    [ValidateSet('fixtures', 'canary', 'six', 'portfolio')]
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

function Test-IsJsonNumber {
    param($Value)
    return ($Value -is [sbyte] -or $Value -is [byte] -or
        $Value -is [int16] -or $Value -is [uint16] -or
        $Value -is [int32] -or $Value -is [uint32] -or
        $Value -is [int64] -or $Value -is [uint64] -or
        $Value -is [single] -or $Value -is [double] -or
        $Value -is [decimal] -or $Value -is [System.Numerics.BigInteger])
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
    if ((Test-IsJsonNumber $Left) -or (Test-IsJsonNumber $Right)) {
        if (-not (Test-IsJsonNumber $Left) -or -not (Test-IsJsonNumber $Right)) {
            return $false
        }
        try {
            return ([decimal]$Left -eq [decimal]$Right)
        } catch {
            return ([double]$Left -eq [double]$Right)
        }
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

    if (Test-HasProperty $SchemaNode 'type') {
        $expectedType = [string](Get-Property $SchemaNode 'type')
        $typeMatches = switch ($expectedType) {
            'object' { Test-IsObject $Value; break }
            'array' { Test-IsArray $Value; break }
            'string' { $Value -is [string]; break }
            'integer' { $Value -is [sbyte] -or $Value -is [byte] -or $Value -is [int16] -or $Value -is [uint16] -or $Value -is [int32] -or $Value -is [uint32] -or $Value -is [int64] -or $Value -is [uint64]; break }
            'number' { $Value -is [ValueType] -and -not ($Value -is [bool]); break }
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
        if ((Test-HasProperty $SchemaNode 'minLength') -and $Value.Length -lt [int](Get-Property $SchemaNode 'minLength')) {
            $nodeErrors += "$Path is shorter than minLength"
        }
        if ((Test-HasProperty $SchemaNode 'maxLength') -and $Value.Length -gt [int](Get-Property $SchemaNode 'maxLength')) {
            $nodeErrors += "$Path is longer than maxLength"
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

    if ($Value -is [ValueType] -and -not ($Value -is [bool])) {
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
    $rule = [ordered]@{ order = 1; kind = 'hard_constraint'; condition = 'Choose the route that fits the available transfer window.'; outcome = 'Option A when the day is tightly constrained.' }
    $assessmentA = [ordered]@{ option_id = 'option-a'; outcome = 'Faster access with less transfer overhead.' }
    $assessmentB = [ordered]@{ option_id = 'option-b'; outcome = 'More context when an overnight stop is possible.' }
    $axes = @()
    foreach ($axisNumber in 1..3) {
        $axes += [ordered]@{
            axis_id = "axis-$axisNumber"
            label = "Decision axis $axisNumber"
            explanation = 'Compare the practical trade-off using checked evidence.'
            assessments = @($assessmentA, $assessmentB)
            source_ids = @("source-$axisNumber")
        }
    }
    $lenses = @()
    foreach ($lensNumber in 1..3) {
        $lenses += [ordered]@{
            lens_id = "lens-$lensNumber"
            traveler = "Traveler profile $lensNumber"
            outcome = 'Use the ordered rule path and choose the option that fits the stated constraint.'
            rule_path = @($rule)
        }
    }
    $sources = @()
    $claimGroups = @('access_transport', 'timing_duration', 'cost_booking', 'season_current_conditions', 'experience_fit', 'constraints_safety')
    foreach ($sourceNumber in 1..6) {
        $sources += [ordered]@{
            source_id = "source-$sourceNumber"
            evidence_label = if ($sourceNumber -eq 1) { 'primary' } elseif ($sourceNumber -eq 6) { 'live_check_required' } else { 'corroborating' }
            checked_on = '2026-08-03'
            claim_groups = @($claimGroups[$sourceNumber - 1])
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
            order = $ruleNumber
            kind = if ($ruleNumber -eq 8) { 'tie_breaker' } else { 'preference' }
            condition = "Qualitative condition $ruleNumber for this decision path."
            outcome = "Qualitative outcome $ruleNumber without scoring or hidden weights."
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
                order = $ruleNumber
                kind = if ($ruleNumber -eq 1) { 'hard_constraint' } elseif ($ruleNumber -eq 5) { 'tie_breaker' } else { 'preference' }
                condition = "Lens $lensNumber condition $ruleNumber."
                outcome = "Lens $lensNumber qualitative outcome $ruleNumber."
            }
        }
        $lenses += [ordered]@{
            lens_id = "lens-$lensNumber"
            traveler = "Traveler profile $lensNumber"
            outcome = "Outcome for traveler profile $lensNumber."
            rule_path = $lensRules
        }
    }
    $bundle['traveler_lenses'] = ConvertTo-FixtureObject $lenses

    $sources = @($bundle['sources'])
    foreach ($sourceNumber in 7..10) {
        $sources += [ordered]@{
            source_id = "source-$sourceNumber"
            evidence_label = 'corroborating'
            checked_on = '2026-08-03'
            claim_groups = @('experience_fit')
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
    $approvalItemProperties = Get-Property (Get-Property (Get-Property $approvalProperties 'approvals') 'items') 'properties'
    $sourceProbeProperties = Get-Property (Get-Definition $Schema 'sourceProbe') 'properties'
    $impactReportProperties = Get-Property (Get-Definition $Schema 'impactReport') 'properties'
    $backupProperties = Get-Property (Get-Definition $Schema 'backup') 'properties'

    return [ordered]@{
        sourceClass = Get-Definition $Schema 'sourceClass'
        claimGroup = Get-Definition $Schema 'claimGroup'
        evidenceLabel = Get-Definition $Schema 'evidenceLabel'
        freshnessTier = Get-Definition $Schema 'freshnessTier'
        ruleKind = Get-Definition $Schema 'ruleKind'
        routeGroup = Get-Definition $Schema 'routeGroup'
        changeReason = Get-Definition $Schema 'changeReason'
        activationStage = Get-Property $manifestProperties 'activation_stage'
        approvalRole = Get-Property $approvalItemProperties 'role'
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

    $positiveBundle = New-PositiveBundle
    Assert-BundleAccepted 'resolved-bundle-v2-positive' $positiveBundle

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
    $approvalFixture = ConvertTo-FixtureObject ([ordered]@{
        artifact_version = 'approval-v2'
        path = 'compare/da-nang-vs-hoi-an'
        bundle_hash = ('c' * 64)
        schema_version = 'v2'
        approvals = @(
            [ordered]@{ role = 'author'; identity_id = 'author-one'; approved_on = '2026-08-03' }
            [ordered]@{ role = 'reviewer'; identity_id = 'reviewer-one'; approved_on = '2026-08-03' }
        )
        approved_on = '2026-08-03'
    })
    Assert-Accepted 'approval-role-positive' $approvalFixture $approvalSchema
    $badRole = Copy-FixtureObject $approvalFixture
    $badRole.approvals[1].role = 'editor'
    Assert-Rejected 'approval-role-enum' $badRole $approvalSchema

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

$requiredForScope = if ($Scope -ceq 'fixtures') { @($requiredInputs[0]) } else { @($requiredInputs) }
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

if ($Scope -ceq 'fixtures' -and $null -ne $schema -and $errors.Count -eq 0) {
    $fixtureResult = Invoke-FixtureValidation $schema
    $errors += @($fixtureResult.errors)
    $checks += [int]$fixtureResult.checks
}

Write-VerificationResult -Errors @($errors) -Checks $checks
if ($errors.Count -gt 0) {
    exit 1
}

exit 0
