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
        if (Test-DictionaryKey $Object $Name) {
            $value = $Object[$Name]
            if (Test-IsArray $value) {
                return ,$value
            }
            return $value
        }
        return $null
    }

    $property = $Object.PSObject.Properties[$Name]
    if ($null -eq $property) {
        return $null
    }
    if (Test-IsArray $property.Value) {
        return ,$property.Value
    }
    return $property.Value
}

function Test-DictionaryKey {
    param(
        [Parameter(Mandatory = $true)]$Dictionary,
        [Parameter(Mandatory = $true)][string]$Name
    )

    if ($null -ne $Dictionary.PSObject.Methods['ContainsKey']) {
        return $Dictionary.ContainsKey($Name)
    }
    return $Dictionary.Contains($Name)
}

function Test-HasProperty {
    param(
        [Parameter(Mandatory = $true)]$Object,
        [Parameter(Mandatory = $true)][string]$Name
    )

    if ($Object -is [System.Collections.IDictionary]) {
        return (Test-DictionaryKey $Object $Name)
    }
    return ($null -ne $Object.PSObject.Properties[$Name])
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

function Test-JsonValueEqual {
    param($Left, $Right)
    if ($Left -is [string] -and $Right -is [string]) {
        return ($Left -ceq $Right)
    }
    if (($Left -is [ValueType]) -and ($Right -is [ValueType])) {
        return ($Left -eq $Right)
    }
    return ((ConvertTo-Json $Left -Depth 100 -Compress) -ceq (ConvertTo-Json $Right -Depth 100 -Compress))
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
            $seenValues = @{}
            for ($index = 0; $index -lt $arrayValue.Count; $index++) {
                $serializedItem = ConvertTo-Json $arrayValue[$index] -Depth 100 -Compress
                if ($seenValues.ContainsKey($serializedItem)) {
                    $nodeErrors += "$Path[$index] duplicates an earlier item"
                } else {
                    $seenValues[$serializedItem] = $true
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

function ConvertTo-FixtureObject {
    param([Parameter(Mandatory = $true)]$Value)
    Add-Type -AssemblyName System.Web.Extensions
    $serializer = New-Object System.Web.Script.Serialization.JavaScriptSerializer
    $serializer.MaxJsonLength = 10485760
    $serialized = ConvertTo-Json $Value -Depth 100 -Compress
    return $serializer.DeserializeObject($serialized)
}

function Copy-FixtureObject {
    param([Parameter(Mandatory = $true)]$Value)
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
        archetype = 'day_trip_choice'
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

function Invoke-FixtureValidation {
    param([Parameter(Mandatory = $true)]$Schema)

    $script:fixtureErrors = @()
    $script:fixtureChecks = 0
    $bundleSchema = Get-Definition $Schema 'resolvedBundleV2'

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

    $positiveBundle = New-PositiveBundle
    Assert-Accepted 'resolved-bundle-v2-positive' $positiveBundle $bundleSchema

    $missingRequired = Copy-FixtureObject $positiveBundle
    [void]$missingRequired.Remove('editorial')
    Assert-Rejected 'required-key' $missingRequired $bundleSchema

    $wrongScalar = Copy-FixtureObject $positiveBundle
    $wrongScalar.post_id = '279'
    Assert-Rejected 'scalar-type' $wrongScalar $bundleSchema

    $tooFewAxes = Copy-FixtureObject $positiveBundle
    $tooFewAxes.axes = @($tooFewAxes.axes | Select-Object -First 2)
    Assert-Rejected 'axis-count' $tooFewAxes $bundleSchema

    $longAxisLabel = Copy-FixtureObject $positiveBundle
    $longAxisLabel.axes[0].label = ('x' * 49)
    Assert-Rejected 'axis-label-length' $longAxisLabel $bundleSchema

    $longAxisExplanation = Copy-FixtureObject $positiveBundle
    $longAxisExplanation.axes[0].explanation = ('x' * 181)
    Assert-Rejected 'axis-explanation-length' $longAxisExplanation $bundleSchema

    $longDecision = Copy-FixtureObject $positiveBundle
    $longDecision.primary_decision.summary = ('x' * 241)
    Assert-Rejected 'primary-decision-length' $longDecision $bundleSchema

    $longLensOutcome = Copy-FixtureObject $positiveBundle
    $longLensOutcome.traveler_lenses[0].outcome = ('x' * 221)
    Assert-Rejected 'traveler-lens-length' $longLensOutcome $bundleSchema

    $tooFewSources = Copy-FixtureObject $positiveBundle
    $tooFewSources.sources = @($tooFewSources.sources | Select-Object -First 5)
    Assert-Rejected 'source-count' $tooFewSources $bundleSchema

    $tooFewRoutes = Copy-FixtureObject $positiveBundle
    $tooFewRoutes.related_routes = @($tooFewRoutes.related_routes | Select-Object -First 5)
    Assert-Rejected 'route-count' $tooFewRoutes $bundleSchema

    $missingRouteGroup = Copy-FixtureObject $positiveBundle
    foreach ($route in $missingRouteGroup.related_routes) {
        if ($route.route_group -ceq 'check_practical') {
            $route.route_group = 'build_route'
        }
    }
    Assert-Rejected 'route-group-coverage' $missingRouteGroup $bundleSchema

    $unknownKey = Copy-FixtureObject $positiveBundle
    $unknownKey['reviewer_notes'] = 'private'
    Assert-Rejected 'unknown-key' $unknownKey $bundleSchema

    $rawHtml = Copy-FixtureObject $positiveBundle
    $rawHtml.field_note = '<script>alert(1)</script>'
    Assert-Rejected 'raw-html' $rawHtml $bundleSchema

    $eventHandler = Copy-FixtureObject $positiveBundle
    $eventHandler.field_note = 'onclick=alert(1)'
    Assert-Rejected 'event-handler-text' $eventHandler $bundleSchema

    $controlCharacter = Copy-FixtureObject $positiveBundle
    $controlCharacter.field_note = "unsafe$([char]1)text"
    Assert-Rejected 'control-character' $controlCharacter $bundleSchema

    Assert-Accepted 'safe-http-url' 'https://example.org/source?id=1' (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-javascript-url' 'javascript:alert(1)' (Get-Definition $Schema 'httpUrl')
    Assert-Rejected 'unsafe-data-url' 'data:text/html,bad' (Get-Definition $Schema 'httpUrl')

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
        Assert-Accepted "terminal-outcome-$terminalOutcome" $terminalFixture $bundleSchema
    }
    $badTerminal = Copy-FixtureObject $positiveBundle
    $badTerminal.primary_decision.outcome = 'weighted_score'
    Assert-Rejected 'terminal-outcome-enum' $badTerminal $bundleSchema

    $visibleLimit = Copy-FixtureObject $positiveBundle
    $visibleLimit.render_contract.max_visible_characters = 1801
    Assert-Rejected 'visible-decision-frame-limit' $visibleLimit $bundleSchema

    $boundaryString = ('x' * 65534)
    $script:fixtureChecks++
    if ((Get-SerializedUtf8Size $boundaryString) -ne 65536 -or -not (Test-WithinBundleSizeLimit $boundaryString)) {
        $script:fixtureErrors += 'E_SIZE fixture/utf8-boundary: 65,536-byte serialized value was not accepted'
    }
    $oversizedString = ('x' * 65535)
    $script:fixtureChecks++
    if ((Get-SerializedUtf8Size $oversizedString) -ne 65537 -or (Test-WithinBundleSizeLimit $oversizedString)) {
        $script:fixtureErrors += 'E_SIZE fixture/utf8-oversized: value above 65,536 UTF-8 bytes was not rejected'
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
