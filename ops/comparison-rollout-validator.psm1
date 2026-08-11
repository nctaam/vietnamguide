Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'

function Get-VgProperty {
    param($Object, [Parameter(Mandatory = $true)][AllowEmptyString()][string]$Name)
    if ($null -eq $Object) { return $null }
    if ($Object -is [System.Collections.IDictionary]) {
        foreach ($key in $Object.Keys) {
            if ([string]$key -ceq $Name) {
                $value = $Object[$key]
                if ($value -is [System.Collections.IEnumerable] -and -not ($value -is [string])) { return ,$value }
                return $value
            }
        }
        return $null
    }
    foreach ($property in $Object.PSObject.Properties) {
        if ($property.Name -ceq $Name) {
            if ($property.Value -is [System.Collections.IEnumerable] -and -not ($property.Value -is [string])) { return ,$property.Value }
            return $property.Value
        }
    }
    return $null
}

function Test-VgProperty {
    param($Object, [Parameter(Mandatory = $true)][AllowEmptyString()][string]$Name)
    if ($null -eq $Object) { return $false }
    if ($Object -is [System.Collections.IDictionary]) {
        foreach ($key in $Object.Keys) { if ([string]$key -ceq $Name) { return $true } }
        return $false
    }
    foreach ($property in $Object.PSObject.Properties) { if ($property.Name -ceq $Name) { return $true } }
    return $false
}

function Test-VgArray {
    param($Value)
    return (($Value -is [System.Collections.IList]) -and -not ($Value -is [string]))
}

function Test-VgObject {
    param($Value)
    if ($null -eq $Value -or (Test-VgArray $Value) -or $Value -is [string] -or $Value -is [ValueType]) { return $false }
    return (($Value -is [System.Collections.IDictionary]) -or ($Value -is [pscustomobject]))
}

function Get-VgPropertyNames {
    param($Object)
    if ($Object -is [System.Collections.IDictionary]) { return @($Object.Keys | ForEach-Object { [string]$_ }) }
    return @($Object.PSObject.Properties.Name)
}

function New-VgOrdinalDictionary {
    return ,(New-Object System.Collections.Specialized.OrderedDictionary ([System.StringComparer]::Ordinal))
}

function New-VgStringSet {
    return ,(New-Object 'System.Collections.Generic.HashSet[string]' ([System.StringComparer]::Ordinal))
}

function New-VgStringObjectMap {
    return ,(New-Object 'System.Collections.Generic.Dictionary[string,object]' ([System.StringComparer]::Ordinal))
}

function Get-VgSortedStrings {
    param($Values)
    $items = [string[]]@($Values | ForEach-Object { [string]$_ })
    [array]::Sort($items, [System.StringComparer]::Ordinal)
    return $items
}

function Test-VgNumeric {
    param($Value)
    return ($Value -is [sbyte] -or $Value -is [byte] -or
        $Value -is [int16] -or $Value -is [uint16] -or
        $Value -is [int32] -or $Value -is [uint32] -or
        $Value -is [int64] -or $Value -is [uint64] -or
        $Value -is [single] -or $Value -is [double] -or
        $Value -is [decimal] -or $Value -is [System.Numerics.BigInteger])
}

function Get-VgInvariantNumber {
    param([Parameter(Mandatory = $true)]$Value)
    if (-not (Test-VgNumeric $Value)) { throw 'E_CANONICAL value: unsupported numeric value' }
    if (($Value -is [double] -and ([double]::IsNaN($Value) -or [double]::IsInfinity($Value))) -or
        ($Value -is [single] -and ([single]::IsNaN($Value) -or [single]::IsInfinity($Value)))) {
        throw 'E_CANONICAL value: non-finite numbers are forbidden'
    }
    $culture = [System.Globalization.CultureInfo]::InvariantCulture
    if ($Value -is [decimal]) { return $Value.ToString('G29', $culture) }
    if ($Value -is [double] -or $Value -is [single]) { return $Value.ToString('R', $culture).Replace('E+', 'e').Replace('E', 'e') }
    return ([System.IFormattable]$Value).ToString('D', $culture)
}

function ConvertTo-VgJsonStringLiteral {
    param([Parameter(Mandatory = $true)][AllowEmptyString()][string]$Value)
    $builder = New-Object System.Text.StringBuilder
    [void]$builder.Append('"')
    for ($index = 0; $index -lt $Value.Length; $index++) {
        $character = $Value[$index]
        $code = [int]$character
        if ([char]::IsHighSurrogate($character)) {
            if ($index + 1 -ge $Value.Length -or -not [char]::IsLowSurrogate($Value[$index + 1])) {
                throw 'E_CANONICAL value: unpaired high surrogate is forbidden'
            }
            [void]$builder.Append($character)
            $index++
            [void]$builder.Append($Value[$index])
            continue
        }
        if ([char]::IsLowSurrogate($character)) { throw 'E_CANONICAL value: unpaired low surrogate is forbidden' }
        switch ($code) {
            0x22 { [void]$builder.Append('\"'); continue }
            0x5C { [void]$builder.Append('\\'); continue }
            0x08 { [void]$builder.Append('\b'); continue }
            0x09 { [void]$builder.Append('\t'); continue }
            0x0A { [void]$builder.Append('\n'); continue }
            0x0C { [void]$builder.Append('\f'); continue }
            0x0D { [void]$builder.Append('\r'); continue }
        }
        if ($code -lt 0x20) {
            [void]$builder.Append(('\u{0:x4}' -f $code))
        } else {
            [void]$builder.Append($character)
        }
    }
    [void]$builder.Append('"')
    return $builder.ToString()
}

function ConvertTo-VgCanonicalJsonValue {
    param([AllowNull()]$Value)
    if ($null -eq $Value) { return 'null' }
    if ($Value -is [string]) { return (ConvertTo-VgJsonStringLiteral $Value) }
    if ($Value -is [bool]) { if ($Value) { return 'true' }; return 'false' }
    if (Test-VgNumeric $Value) { return (Get-VgInvariantNumber $Value) }
    if (Test-VgArray $Value) {
        $parts = New-Object System.Collections.Generic.List[string]
        foreach ($item in $Value) { $parts.Add((ConvertTo-VgCanonicalJsonValue $item)) }
        return '[' + [string]::Join(',', $parts.ToArray()) + ']'
    }
    if (Test-VgObject $Value) {
        $names = Get-VgSortedStrings (Get-VgPropertyNames $Value)
        $parts = New-Object System.Collections.Generic.List[string]
        foreach ($name in $names) {
            $parts.Add((ConvertTo-VgJsonStringLiteral $name) + ':' + (ConvertTo-VgCanonicalJsonValue (Get-VgProperty $Value $name)))
        }
        return '{' + [string]::Join(',', $parts.ToArray()) + '}'
    }
    throw "E_CANONICAL value: unsupported CLR type $($Value.GetType().FullName)"
}

function ConvertTo-VgCanonicalJson {
    [CmdletBinding()]
    param([Parameter(Mandatory = $true, ValueFromPipeline = $true)][AllowNull()]$Value)
    process { return (ConvertTo-VgCanonicalJsonValue $Value) }
}

function Get-VgSha256Hex {
    [CmdletBinding()]
    param([Parameter(Mandatory = $true, ValueFromPipeline = $true)][AllowNull()]$Value)
    process {
        $canonical = ConvertTo-VgCanonicalJsonValue $Value
        $bytes = (New-Object System.Text.UTF8Encoding($false, $true)).GetBytes($canonical)
        $sha = [System.Security.Cryptography.SHA256]::Create()
        try { $hash = $sha.ComputeHash($bytes) } finally { $sha.Dispose() }
        return ([System.BitConverter]::ToString($hash).Replace('-', '').ToLowerInvariant())
    }
}

function Read-VgJsonStringToken {
    param([Parameter(Mandatory = $true)]$State)
    if ($State.Text[$State.Index] -cne '"') { throw "E_JSON $($State.Path): expected JSON string" }
    $State.Index = $State.Index + 1
    $builder = New-Object System.Text.StringBuilder
    while ($State.Index -lt $State.Text.Length) {
        $character = $State.Text[$State.Index]
        $State.Index = $State.Index + 1
        if ($character -ceq '"') { return $builder.ToString() }
        if ([int]$character -lt 0x20) { throw "E_JSON $($State.Path): raw control character is forbidden" }
        if ($character -cne '\') { [void]$builder.Append($character); continue }
        if ($State.Index -ge $State.Text.Length) { throw "E_JSON $($State.Path): incomplete string escape" }
        $escape = $State.Text[$State.Index]
        $State.Index = $State.Index + 1
        switch ($escape) {
            '"' { [void]$builder.Append('"'); continue }
            '\' { [void]$builder.Append('\'); continue }
            '/' { [void]$builder.Append('/'); continue }
            'b' { [void]$builder.Append([char]0x08); continue }
            'f' { [void]$builder.Append([char]0x0C); continue }
            'n' { [void]$builder.Append([char]0x0A); continue }
            'r' { [void]$builder.Append([char]0x0D); continue }
            't' { [void]$builder.Append([char]0x09); continue }
            'u' {
                if ($State.Index + 4 -gt $State.Text.Length) { throw "E_JSON $($State.Path): incomplete unicode escape" }
                $hex = $State.Text.Substring($State.Index, 4)
                if ($hex -cnotmatch '^[0-9A-Fa-f]{4}$') { throw "E_JSON $($State.Path): invalid unicode escape" }
                $State.Index += 4
                [void]$builder.Append([char][Convert]::ToInt32($hex, 16))
                continue
            }
            default { throw "E_JSON $($State.Path): invalid string escape" }
        }
    }
    throw "E_JSON $($State.Path): unterminated JSON string"
}

function Skip-VgJsonWhitespace {
    param([Parameter(Mandatory = $true)]$State)
    while ($State.Index -lt $State.Text.Length) {
        $character = $State.Text[$State.Index]
        if ($character -ceq ' ' -or $character -ceq "`t" -or $character -ceq "`r" -or $character -ceq "`n") { $State.Index = $State.Index + 1 } else { break }
    }
}

function Read-VgJsonValueToken {
    param([Parameter(Mandatory = $true)]$State)
    Skip-VgJsonWhitespace $State
    if ($State.Index -ge $State.Text.Length) { throw "E_JSON $($State.Path): unexpected end of JSON" }
    $character = $State.Text[$State.Index]
    if ($character -ceq '"') { [void](Read-VgJsonStringToken $State); return }
    if ($character -ceq '{') {
        $State.Index = $State.Index + 1
        Skip-VgJsonWhitespace $State
        $keys = New-VgStringSet
        if ($State.Index -lt $State.Text.Length -and $State.Text[$State.Index] -ceq '}') { $State.Index = $State.Index + 1; return }
        while ($true) {
            Skip-VgJsonWhitespace $State
            if ($State.Index -ge $State.Text.Length -or $State.Text[$State.Index] -cne '"') { throw "E_JSON $($State.Path): object key must be a string" }
            $key = Read-VgJsonStringToken $State
            if (-not $keys.Add($key)) { throw "E_DUPLICATE_KEY $($State.Path): duplicate JSON object key '$key'" }
            Skip-VgJsonWhitespace $State
            if ($State.Index -ge $State.Text.Length -or $State.Text[$State.Index] -cne ':') { throw "E_JSON $($State.Path): expected colon after object key" }
            $State.Index = $State.Index + 1
            Read-VgJsonValueToken $State
            Skip-VgJsonWhitespace $State
            if ($State.Index -ge $State.Text.Length) { throw "E_JSON $($State.Path): unterminated JSON object" }
            if ($State.Text[$State.Index] -ceq '}') { $State.Index = $State.Index + 1; return }
            if ($State.Text[$State.Index] -cne ',') { throw "E_JSON $($State.Path): expected comma in JSON object" }
            $State.Index = $State.Index + 1
        }
    }
    if ($character -ceq '[') {
        $State.Index = $State.Index + 1
        Skip-VgJsonWhitespace $State
        if ($State.Index -lt $State.Text.Length -and $State.Text[$State.Index] -ceq ']') { $State.Index = $State.Index + 1; return }
        while ($true) {
            Read-VgJsonValueToken $State
            Skip-VgJsonWhitespace $State
            if ($State.Index -ge $State.Text.Length) { throw "E_JSON $($State.Path): unterminated JSON array" }
            if ($State.Text[$State.Index] -ceq ']') { $State.Index = $State.Index + 1; return }
            if ($State.Text[$State.Index] -cne ',') { throw "E_JSON $($State.Path): expected comma in JSON array" }
            $State.Index = $State.Index + 1
        }
    }
    $remaining = $State.Text.Substring($State.Index)
    $match = [regex]::Match($remaining, '^(?:true|false|null|-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?)')
    if (-not $match.Success) { throw "E_JSON $($State.Path): invalid JSON token" }
    $State.Index += $match.Length
}

function Read-VgJsonDocument {
    [CmdletBinding()]
    param([Parameter(Mandatory = $true)][string]$Path)
    $fullPath = [System.IO.Path]::GetFullPath($Path)
    if (-not [System.IO.File]::Exists($fullPath)) { throw "E_FILE ${Path}: JSON document is missing" }
    $bytes = [System.IO.File]::ReadAllBytes($fullPath)
    if (($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) -or
        ($bytes.Length -ge 2 -and (($bytes[0] -eq 0xFF -and $bytes[1] -eq 0xFE) -or ($bytes[0] -eq 0xFE -and $bytes[1] -eq 0xFF)))) {
        throw "E_UTF8 ${Path}: byte-order marks are forbidden"
    }
    try { $text = (New-Object System.Text.UTF8Encoding($false, $true)).GetString($bytes) } catch { throw "E_UTF8 ${Path}: invalid UTF-8 byte sequence" }
    $state = [pscustomobject]@{ Text = $text; Index = 0; Path = $Path }
    [void](Read-VgJsonValueToken $state)
    Skip-VgJsonWhitespace $state
    if ($state.Index -ne $state.Text.Length) { throw "E_JSON ${Path}: trailing JSON content is forbidden" }
    try { $document = $text | ConvertFrom-Json -ErrorAction Stop } catch { throw "E_JSON ${Path}: invalid JSON document" }
    return ,$document
}

function Test-VgJsonNumber {
    param($Value)
    if (-not (Test-VgNumeric $Value)) { return $false }
    if ($Value -is [double]) { return (-not [double]::IsNaN($Value) -and -not [double]::IsInfinity($Value)) }
    if ($Value -is [single]) { return (-not [single]::IsNaN($Value) -and -not [single]::IsInfinity($Value)) }
    return $true
}

function Test-VgInteger {
    param($Value)
    if (-not (Test-VgJsonNumber $Value)) { return $false }
    if ($Value -is [single] -or $Value -is [double]) { return ([Math]::Truncate([double]$Value) -eq [double]$Value) }
    if ($Value -is [decimal]) { return ([decimal]::Truncate($Value) -eq $Value) }
    return $true
}

function Test-VgUnicodeString {
    param([string]$Value, [ref]$Length)
    $count = 0
    for ($index = 0; $index -lt $Value.Length; $index++) {
        if ([char]::IsHighSurrogate($Value[$index])) {
            if ($index + 1 -ge $Value.Length -or -not [char]::IsLowSurrogate($Value[$index + 1])) { return $false }
            $index++
        } elseif ([char]::IsLowSurrogate($Value[$index])) { return $false }
        $count++
    }
    $Length.Value = $count
    return $true
}

function ConvertTo-VgNumberRational {
    param($Value, [ref]$Numerator, [ref]$Denominator)
    if (-not (Test-VgJsonNumber $Value)) { return $false }
    $numberText = Get-VgInvariantNumber $Value
    $match = [regex]::Match($numberText, '^(?<sign>[+-]?)(?<integer>[0-9]+)(?:\.(?<fraction>[0-9]+))?(?:[eE](?<exponent>[+-]?[0-9]+))?$')
    if (-not $match.Success) { return $false }
    $fraction = if ($match.Groups['fraction'].Success) { $match.Groups['fraction'].Value } else { '' }
    try {
        $parsedNumerator = [System.Numerics.BigInteger]::Parse($match.Groups['integer'].Value + $fraction, [System.Globalization.NumberStyles]::None, [System.Globalization.CultureInfo]::InvariantCulture)
    } catch { return $false }
    if ($match.Groups['sign'].Value -ceq '-') { $parsedNumerator = -$parsedNumerator }
    $exponent = 0
    if ($match.Groups['exponent'].Success -and -not [int]::TryParse($match.Groups['exponent'].Value, [System.Globalization.NumberStyles]::AllowLeadingSign, [System.Globalization.CultureInfo]::InvariantCulture, [ref]$exponent)) { return $false }
    $scale = [int64]$fraction.Length - [int64]$exponent
    if ([Math]::Abs($scale) -gt 10000) { return $false }
    if ($scale -ge 0) {
        $parsedDenominator = [System.Numerics.BigInteger]::Pow(10, [int]$scale)
    } else {
        $parsedNumerator *= [System.Numerics.BigInteger]::Pow(10, [int](-$scale))
        $parsedDenominator = [System.Numerics.BigInteger]::One
    }
    if ($parsedNumerator.IsZero) { $parsedDenominator = [System.Numerics.BigInteger]::One }
    $Numerator.Value = $parsedNumerator
    $Denominator.Value = $parsedDenominator
    return $true
}

function Test-VgJsonEqual {
    param($Left, $Right)
    if ($null -eq $Left -or $null -eq $Right) { return ($null -eq $Left -and $null -eq $Right) }
    if ((Test-VgNumeric $Left) -or (Test-VgNumeric $Right)) {
        if (-not (Test-VgJsonNumber $Left) -or -not (Test-VgJsonNumber $Right)) { return $false }
        $leftNumerator = [System.Numerics.BigInteger]::Zero; $leftDenominator = [System.Numerics.BigInteger]::One
        $rightNumerator = [System.Numerics.BigInteger]::Zero; $rightDenominator = [System.Numerics.BigInteger]::One
        if (-not (ConvertTo-VgNumberRational $Left ([ref]$leftNumerator) ([ref]$leftDenominator)) -or -not (ConvertTo-VgNumberRational $Right ([ref]$rightNumerator) ([ref]$rightDenominator))) { return $false }
        return ($leftNumerator * $rightDenominator -eq $rightNumerator * $leftDenominator)
    }
    if ($Left -is [string] -or $Right -is [string]) { return ($Left -is [string] -and $Right -is [string] -and $Left -ceq $Right) }
    if ($Left -is [bool] -or $Right -is [bool]) { return ($Left -is [bool] -and $Right -is [bool] -and $Left -eq $Right) }
    if ((Test-VgArray $Left) -or (Test-VgArray $Right)) {
        if (-not (Test-VgArray $Left) -or -not (Test-VgArray $Right) -or $Left.Count -ne $Right.Count) { return $false }
        for ($index = 0; $index -lt $Left.Count; $index++) { if (-not (Test-VgJsonEqual $Left[$index] $Right[$index])) { return $false } }
        return $true
    }
    if ((Test-VgObject $Left) -or (Test-VgObject $Right)) {
        if (-not (Test-VgObject $Left) -or -not (Test-VgObject $Right)) { return $false }
        $leftNames = @(Get-VgPropertyNames $Left); $rightNames = @(Get-VgPropertyNames $Right)
        if ($leftNames.Count -ne $rightNames.Count) { return $false }
        foreach ($name in $leftNames) { if (-not (Test-VgProperty $Right $name) -or -not (Test-VgJsonEqual (Get-VgProperty $Left $name) (Get-VgProperty $Right $name))) { return $false } }
        return $true
    }
    return ($Left.GetType() -eq $Right.GetType() -and $Left -eq $Right)
}

function Resolve-VgSchemaRef {
    param([string]$Reference, $RootSchema)
    if (-not $Reference.StartsWith('#/$defs/', [System.StringComparison]::Ordinal)) { throw 'unsupported reference' }
    $name = $Reference.Substring(8).Replace('~1', '/').Replace('~0', '~')
    $definitions = Get-VgProperty $RootSchema '$defs'
    if (-not (Test-VgProperty $definitions $name)) { throw 'missing reference' }
    return (Get-VgProperty $definitions $name)
}

function Test-VgSchemaNode {
    param($Value, $SchemaNode, $RootSchema, [string]$Pointer)
    $errors = @()
    if ($SchemaNode -is [bool]) { if (-not $SchemaNode) { $errors += "$Pointer is forbidden" }; return @($errors) }
    if (Test-VgProperty $SchemaNode '$ref') {
        try { $errors += @(Test-VgSchemaNode $Value (Resolve-VgSchemaRef (Get-VgProperty $SchemaNode '$ref') $RootSchema) $RootSchema $Pointer) } catch { $errors += "$Pointer has invalid schema reference" }
    }
    if (Test-VgProperty $SchemaNode 'allOf') { foreach ($child in (Get-VgProperty $SchemaNode 'allOf')) { $errors += @(Test-VgSchemaNode $Value $child $RootSchema $Pointer) } }
    if (Test-VgProperty $SchemaNode 'anyOf') {
        $anyOfMatched = $false
        foreach ($child in (Get-VgProperty $SchemaNode 'anyOf')) { if (@(Test-VgSchemaNode $Value $child $RootSchema $Pointer).Count -eq 0) { $anyOfMatched = $true; break } }
        if (-not $anyOfMatched) { $errors += "$Pointer does not satisfy anyOf" }
    }
    if (Test-VgProperty $SchemaNode 'type') {
        $type = [string](Get-VgProperty $SchemaNode 'type')
        $matches = switch ($type) {
            'object' { Test-VgObject $Value; break }
            'array' { Test-VgArray $Value; break }
            'string' { $Value -is [string]; break }
            'integer' { Test-VgInteger $Value; break }
            'number' { Test-VgJsonNumber $Value; break }
            'boolean' { $Value -is [bool]; break }
            'null' { $null -eq $Value; break }
            default { $false }
        }
        if (-not $matches) { $errors += "$Pointer must be $type"; return @($errors) }
    }
    if (Test-VgProperty $SchemaNode 'const') { if (-not (Test-VgJsonEqual $Value (Get-VgProperty $SchemaNode 'const'))) { $errors += "$Pointer does not match const" } }
    if (Test-VgProperty $SchemaNode 'enum') {
        $matched = $false
        foreach ($candidate in (Get-VgProperty $SchemaNode 'enum')) { if (Test-VgJsonEqual $Value $candidate) { $matched = $true; break } }
        if (-not $matched) { $errors += "$Pointer is not in enum" }
    }
    if ($Value -is [string]) {
        $length = 0
        if (-not (Test-VgUnicodeString $Value ([ref]$length))) { $errors += "$Pointer contains an unpaired surrogate"; return @($errors) }
        if (Test-VgProperty $SchemaNode 'minLength') { if ($length -lt [int](Get-VgProperty $SchemaNode 'minLength')) { $errors += "$Pointer is shorter than minLength" } }
        if (Test-VgProperty $SchemaNode 'maxLength') { if ($length -gt [int](Get-VgProperty $SchemaNode 'maxLength')) { $errors += "$Pointer is longer than maxLength" } }
        if (Test-VgProperty $SchemaNode 'pattern') { if (-not [regex]::IsMatch($Value, [string](Get-VgProperty $SchemaNode 'pattern'), [System.Text.RegularExpressions.RegexOptions]::CultureInvariant)) { $errors += "$Pointer does not match pattern" } }
        if ((Get-VgProperty $SchemaNode 'format') -ceq 'date') {
            $parsed = [datetime]::MinValue
            if (-not [datetime]::TryParseExact($Value, 'yyyy-MM-dd', [System.Globalization.CultureInfo]::InvariantCulture, [System.Globalization.DateTimeStyles]::None, [ref]$parsed)) { $errors += "$Pointer is not a valid date" }
        }
    }
    if (Test-VgJsonNumber $Value) {
        if (Test-VgProperty $SchemaNode 'minimum') { if ([decimal]$Value -lt [decimal](Get-VgProperty $SchemaNode 'minimum')) { $errors += "$Pointer is below minimum" } }
        if (Test-VgProperty $SchemaNode 'maximum') { if ([decimal]$Value -gt [decimal](Get-VgProperty $SchemaNode 'maximum')) { $errors += "$Pointer is above maximum" } }
    }
    if (Test-VgObject $Value) {
        $names = @(Get-VgPropertyNames $Value)
        if (Test-VgProperty $SchemaNode 'required') { foreach ($required in (Get-VgProperty $SchemaNode 'required')) { if (-not (Test-VgProperty $Value ([string]$required))) { $errors += "$Pointer.$required is required" } } }
        $properties = Get-VgProperty $SchemaNode 'properties'
        foreach ($name in $names) {
            if ($null -ne $properties -and (Test-VgProperty $properties $name)) { $errors += @(Test-VgSchemaNode (Get-VgProperty $Value $name) (Get-VgProperty $properties $name) $RootSchema "$Pointer.$name") }
            elseif ((Get-VgProperty $SchemaNode 'additionalProperties') -is [bool] -and -not (Get-VgProperty $SchemaNode 'additionalProperties')) { $errors += "$Pointer.$name is not allowed" }
        }
    }
    if (Test-VgArray $Value) {
        if (Test-VgProperty $SchemaNode 'minItems') { if ($Value.Count -lt [int](Get-VgProperty $SchemaNode 'minItems')) { $errors += "$Pointer has too few items" } }
        if (Test-VgProperty $SchemaNode 'maxItems') { if ($Value.Count -gt [int](Get-VgProperty $SchemaNode 'maxItems')) { $errors += "$Pointer has too many items" } }
        if ((Get-VgProperty $SchemaNode 'uniqueItems') -eq $true) {
            for ($left = 0; $left -lt $Value.Count; $left++) { for ($right = $left + 1; $right -lt $Value.Count; $right++) { if (Test-VgJsonEqual $Value[$left] $Value[$right]) { $errors += "$Pointer must contain unique items"; $left = $Value.Count; break } } }
        }
        $prefix = Get-VgProperty $SchemaNode 'prefixItems'
        if ($null -ne $prefix) { for ($index = 0; $index -lt [Math]::Min($Value.Count, $prefix.Count); $index++) { $errors += @(Test-VgSchemaNode $Value[$index] $prefix[$index] $RootSchema "$Pointer[$index]") } }
        $items = Get-VgProperty $SchemaNode 'items'
        if ($null -ne $items) {
            $start = if ($null -ne $prefix) { $prefix.Count } else { 0 }
            for ($index = $start; $index -lt $Value.Count; $index++) { $errors += @(Test-VgSchemaNode $Value[$index] $items $RootSchema "$Pointer[$index]") }
        }
        if (Test-VgProperty $SchemaNode 'contains') {
            $matches = 0
            foreach ($item in $Value) { if (@(Test-VgSchemaNode $item (Get-VgProperty $SchemaNode 'contains') $RootSchema $Pointer).Count -eq 0) { $matches++ } }
            $minimumMatches = if (Test-VgProperty $SchemaNode 'minContains') { [int](Get-VgProperty $SchemaNode 'minContains') } else { 1 }
            if ($matches -lt $minimumMatches) { $errors += "$Pointer does not satisfy contains" }
        }
    }
    return @($errors)
}

function Test-VgSchemaDocument {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)][AllowNull()]$Document,
        [Parameter(Mandatory = $true)]$Schema,
        [string]$DefinitionName = '',
        [string]$DocumentId = 'document'
    )
    $node = $Schema
    if (-not [string]::IsNullOrWhiteSpace($DefinitionName)) {
        $definitions = Get-VgProperty $Schema '$defs'
        if ($null -eq $definitions -or -not (Test-VgProperty $definitions $DefinitionName)) { return [string[]]@("E_SCHEMA ${DocumentId}: missing schema definition '$DefinitionName'") }
        $node = Get-VgProperty $definitions $DefinitionName
    }
    $formatted = @()
    foreach ($errorMessage in @(Test-VgSchemaNode $Document $node $Schema '$')) { $formatted += "E_SCHEMA ${DocumentId}: $errorMessage" }
    return [string[]]@($formatted)
}

function Copy-VgValue {
    param([AllowNull()]$Value)
    if ($null -eq $Value -or $Value -is [string] -or $Value -is [ValueType]) { return $Value }
    if (Test-VgArray $Value) {
        $copy = New-Object 'object[]' $Value.Count
        for ($index = 0; $index -lt $Value.Count; $index++) { $copy[$index] = Copy-VgValue $Value[$index] }
        return ,$copy
    }
    $copy = New-VgOrdinalDictionary
    foreach ($name in Get-VgPropertyNames $Value) { $copy.Add($name, (Copy-VgValue (Get-VgProperty $Value $name))) }
    return ,$copy
}

function Add-VgError {
    param([Parameter(Mandatory = $true)]$Errors, [string]$Code, [string]$Subject, [string]$Explanation)
    [void]$Errors.Add("$Code ${Subject}: $Explanation")
}

function Get-VgDateValue {
    param([string]$Value)
    $parsed = [datetime]::MinValue
    if ([datetime]::TryParseExact($Value, 'yyyy-MM-dd', [System.Globalization.CultureInfo]::InvariantCulture, [System.Globalization.DateTimeStyles]::AssumeUniversal, [ref]$parsed)) { return $parsed }
    return [datetime]::MinValue
}

function Get-VgFreshnessState {
    param($Source, [string]$EvidenceLabel, [datetime]$AsOfDate)
    if ($EvidenceLabel -ceq 'live_check_required') { return 'live_check_required' }
    if (Test-VgSourceExpired $Source $AsOfDate) { return 'stale' }
    return 'current'
}

function Test-VgSourceExpired {
    param($Source, [datetime]$AsOfDate)
    $checked = Get-VgDateValue ([string](Get-VgProperty $Source 'checked_on'))
    if ($checked -eq [datetime]::MinValue) { return $true }
    $age = [Math]::Floor(($AsOfDate.Date - $checked.Date).TotalDays)
    $tier = [string](Get-VgProperty $Source 'freshness_tier')
    $limit = if ($tier -ceq 'live') { 30 } elseif ($tier -ceq 'current') { 180 } else { 730 }
    return ($age -lt 0 -or $age -gt $limit)
}

function Test-VgIntersection {
    param($Left, $Right)
    $set = New-VgStringSet
    foreach ($item in @($Left)) { [void]$set.Add([string]$item) }
    foreach ($item in @($Right)) { if ($set.Contains([string]$item)) { return $true } }
    return $false
}

function Get-VgLookup {
    param($Items, [string]$IdField, $Errors, [string]$Code, [string]$Subject)
    $lookup = New-VgStringObjectMap
    foreach ($item in @($Items)) {
        $id = [string](Get-VgProperty $item $IdField)
        if ([string]::IsNullOrWhiteSpace($id)) { continue }
        if ($lookup.ContainsKey($id)) { Add-VgError $Errors $Code $Subject "duplicate $IdField '$id'" } else { $lookup.Add($id, $item) }
    }
    return $lookup
}

function Test-VgOrdinalSorted {
    param($Values)
    $actual = [string[]]@($Values)
    $expected = Get-VgSortedStrings $Values
    if ($actual.Count -ne $expected.Count) { return $false }
    for ($index = 0; $index -lt $actual.Count; $index++) { if ($actual[$index] -cne $expected[$index]) { return $false } }
    return $true
}

function Add-VgGraphEdge {
    param($Graph, [string]$From, [string]$To)
    if (-not $Graph.ContainsKey($From)) { $Graph.Add($From, (New-Object System.Collections.Generic.List[string])) }
    if (-not $Graph.ContainsKey($To)) { $Graph.Add($To, (New-Object System.Collections.Generic.List[string])) }
    if (-not $Graph[$From].Contains($To)) { $Graph[$From].Add($To) }
}

function Test-VgGraphCycle {
    param($Graph)
    $state = New-Object 'System.Collections.Generic.Dictionary[string,int]' ([System.StringComparer]::Ordinal)
    function Visit-VgGraphNode {
        param([string]$Node)
        if ($state.ContainsKey($Node)) {
            if ($state[$Node] -eq 1) { return $true }
            if ($state[$Node] -eq 2) { return $false }
        }
        $state[$Node] = 1
        foreach ($next in $Graph[$Node]) { if (Visit-VgGraphNode $next) { return $true } }
        $state[$Node] = 2
        return $false
    }
    foreach ($node in Get-VgSortedStrings $Graph.Keys) { if (Visit-VgGraphNode $node) { return $true } }
    return $false
}

function ConvertTo-VgResolvedRule {
    param($Rule, [int]$Order)
    return [ordered]@{
        rule_id = [string](Get-VgProperty $Rule 'rule_id')
        order = $Order
        kind = [string](Get-VgProperty $Rule 'kind')
        condition = [string](Get-VgProperty $Rule 'condition')
        outcome = [string](Get-VgProperty $Rule 'outcome')
        context_tags = [object[]]@((Get-VgProperty $Rule 'context_tags'))
        option_ids = [object[]]@((Get-VgProperty $Rule 'option_ids'))
        claim_ids = [object[]]@((Get-VgProperty $Rule 'claim_ids'))
        axis_ids = [object[]]@((Get-VgProperty $Rule 'axis_ids'))
        outcome_id = [string](Get-VgProperty $Rule 'outcome_id')
    }
}

function Get-VgNormalizedHashInput {
    param($Document, [ValidateSet('Manifest', 'Sources', 'Organizations', 'Identities')][string]$Kind)
    $copy = Copy-VgValue $Document
    if ($Kind -ceq 'Sources') {
        $copy['sources'] = [object[]]@((Get-VgProperty $copy 'sources') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'source_id') }; Ascending = $true })
    } elseif ($Kind -ceq 'Organizations') {
        foreach ($organization in @((Get-VgProperty $copy 'organizations'))) {
            $organization['publishers'] = [object[]]@((Get-VgProperty $organization 'publishers') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'publisher_id') }; Ascending = $true })
        }
        $copy['organizations'] = [object[]]@((Get-VgProperty $copy 'organizations') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'organization_id') }; Ascending = $true })
    } elseif ($Kind -ceq 'Identities') {
        $copy['identities'] = [object[]]@((Get-VgProperty $copy 'identities') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'identity_id') }; Ascending = $true })
    } else {
        foreach ($page in @((Get-VgProperty $copy 'pages'))) {
            foreach ($assignment in @((Get-VgProperty $page 'source_assignments'))) {
                $assignment['mappings'] = [object[]]@((Get-VgProperty $assignment 'mappings') | Sort-Object @{ Expression = { ConvertTo-VgCanonicalJsonValue $_ }; Ascending = $true })
            }
            $page['source_assignments'] = [object[]]@((Get-VgProperty $page 'source_assignments') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'source_id') }; Ascending = $true })
        }
        $copy['pages'] = [object[]]@((Get-VgProperty $copy 'pages') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'path') }; Ascending = $true })
    }
    return ,$copy
}

function Invoke-VgComparisonPortfolio {
    param(
        $Manifest,
        $Sources,
        $Organizations,
        $Identities,
        $Schema,
        [ValidateSet('Fixture', 'Production')][string]$Profile,
        [datetime]$AsOfDate
    )
    $errors = New-Object System.Collections.Generic.List[string]
    $resolvedBundles = [ordered]@{}
    $coverageMatrix = New-Object System.Collections.ArrayList
    $impactWorking = New-VgStringObjectMap
    $stageInventories = [ordered]@{}

    $manifestDefinition = if ($Profile -ceq 'Fixture') { 'fixtureManifest' } else { 'manifest' }
    foreach ($schemaError in @(Test-VgSchemaDocument -Document $Manifest -Schema $Schema -DefinitionName $manifestDefinition -DocumentId 'manifest')) { [void]$errors.Add($schemaError) }
    foreach ($schemaFixture in @(
        [ordered]@{ Document = $Sources; Definition = 'sourceRegistry'; Id = 'sources' }
        [ordered]@{ Document = $Organizations; Definition = 'organizationRegistry'; Id = 'organizations' }
        [ordered]@{ Document = $Identities; Definition = 'identityRegistry'; Id = 'identities' }
    )) {
        foreach ($schemaError in @(Test-VgSchemaDocument -Document $schemaFixture.Document -Schema $Schema -DefinitionName $schemaFixture.Definition -DocumentId $schemaFixture.Id)) { [void]$errors.Add($schemaError) }
    }

    $organizationItems = @((Get-VgProperty $Organizations 'organizations'))
    $sourceItems = @((Get-VgProperty $Sources 'sources'))
    $identityItems = @((Get-VgProperty $Identities 'identities'))
    $pages = @((Get-VgProperty $Manifest 'pages') | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'path') }; Ascending = $true })
    if ($Profile -ceq 'Production' -and (-not (Test-VgProperty $Manifest 'target_mappings') -or -not (Test-VgProperty $Manifest 'baseline') -or -not (Test-VgProperty $Manifest 'full'))) {
        Add-VgError $errors 'E_PROFILE' 'manifest' 'Production requires the Production manifest profile; fixtureManifest is not accepted'
    }
    $organizationById = Get-VgLookup $organizationItems 'organization_id' $errors 'E_PUBLISHER' 'organizations'
    $identityById = Get-VgLookup $identityItems 'identity_id' $errors 'E_IDENTITY' 'identities'
    $publisherById = New-VgStringObjectMap
    $domainToPublisher = New-VgStringObjectMap

    foreach ($organization in $organizationItems) {
        $organizationId = [string](Get-VgProperty $organization 'organization_id')
        foreach ($publisher in @((Get-VgProperty $organization 'publishers'))) {
            $publisherId = [string](Get-VgProperty $publisher 'publisher_id')
            $publisherRecord = [ordered]@{
                publisher = $publisher
                organization_id = $organizationId
                canonical_domain = [string](Get-VgProperty $publisher 'canonical_domain')
            }
            if ($publisherById.ContainsKey($publisherId)) {
                Add-VgError $errors 'E_PUBLISHER' $publisherId 'publisher ID is declared more than once'
            } else {
                $publisherById.Add($publisherId, $publisherRecord)
            }
            foreach ($domain in @([string](Get-VgProperty $publisher 'canonical_domain')) + @((Get-VgProperty $publisher 'aliases'))) {
                $normalizedDomain = ([string]$domain).ToLowerInvariant()
                if ($domainToPublisher.ContainsKey($normalizedDomain) -and [string](Get-VgProperty $domainToPublisher[$normalizedDomain] 'publisher_id') -cne $publisherId) {
                    Add-VgError $errors 'E_PUBLISHER' $normalizedDomain 'domain or alias maps to more than one publisher ID'
                } elseif (-not $domainToPublisher.ContainsKey($normalizedDomain)) {
                    $domainToPublisher.Add($normalizedDomain, [ordered]@{ publisher_id = $publisherId; organization_id = $organizationId; canonical_domain = [string](Get-VgProperty $publisher 'canonical_domain') })
                }
            }
        }
    }

    $sourceById = Get-VgLookup $sourceItems 'source_id' $errors 'E_SOURCE' 'sources'
    $sourceIdByUrl = New-VgStringObjectMap
    foreach ($source in $sourceItems) {
        $sourceId = [string](Get-VgProperty $source 'source_id')
        $publisherId = [string](Get-VgProperty $source 'publisher_id')
        $organizationId = [string](Get-VgProperty $source 'organization_id')
        $domain = ([string](Get-VgProperty $source 'canonical_domain')).ToLowerInvariant()
        if (-not $publisherById.ContainsKey($publisherId)) {
            Add-VgError $errors 'E_PUBLISHER' $sourceId "unknown publisher_id '$publisherId'"
            continue
        }
        $publisherRecord = $publisherById[$publisherId]
        if ([string](Get-VgProperty $publisherRecord 'organization_id') -cne $organizationId) { Add-VgError $errors 'E_PUBLISHER' $sourceId 'source publisher belongs to a different controlling organization' }
        $publisherCanonicalDomain = ([string](Get-VgProperty $publisherRecord 'canonical_domain')).ToLowerInvariant()
        if ($domain -cne $publisherCanonicalDomain) { Add-VgError $errors 'E_PUBLISHER' $sourceId 'source canonical_domain does not match the publisher registry canonical_domain' }
        $expectedName = [string](Get-VgProperty (Get-VgProperty $publisherRecord 'publisher') 'publisher_name')
        if ([string](Get-VgProperty $source 'publisher_name') -cne $expectedName) { Add-VgError $errors 'E_PUBLISHER' $sourceId 'publisher_name does not match the organization registry' }
        if ($organizationById.ContainsKey($organizationId) -and @((Get-VgProperty $organizationById[$organizationId] 'source_classes')) -cnotcontains [string](Get-VgProperty $source 'source_class')) { Add-VgError $errors 'E_SOURCE' $sourceId 'source_class is not authorized by the controlling organization' }
        $sourceTitle = [string](Get-VgProperty $source 'title')
        $expectedTitle = [string](Get-VgProperty $source 'expected_title')
        if ([string](Get-VgProperty $source 'expected_title_mode') -ceq 'exact') {
            if ($sourceTitle -cne $expectedTitle) { Add-VgError $errors 'E_SOURCE' $sourceId 'title does not match expected_title exactly' }
        } else {
            try { if (-not [regex]::IsMatch($sourceTitle, $expectedTitle, [System.Text.RegularExpressions.RegexOptions]::CultureInvariant)) { Add-VgError $errors 'E_SOURCE' $sourceId 'title does not match expected_title pattern' } } catch { Add-VgError $errors 'E_SOURCE' $sourceId 'expected_title pattern is invalid' }
        }
        try {
            $uri = New-Object System.Uri([string](Get-VgProperty $source 'url'))
            $host = $uri.DnsSafeHost.ToLowerInvariant()
            if (-not $domainToPublisher.ContainsKey($host) -or [string](Get-VgProperty $domainToPublisher[$host] 'publisher_id') -cne $publisherId) { Add-VgError $errors 'E_PUBLISHER' $sourceId 'source URL host is not a registered publisher domain or alias' }
            $normalizedUrl = $uri.AbsoluteUri
            if ($sourceIdByUrl.ContainsKey($normalizedUrl) -and [string]$sourceIdByUrl[$normalizedUrl] -cne $sourceId) {
                Add-VgError $errors 'E_SOURCE' $sourceId "source URL duplicates source_id '$([string]$sourceIdByUrl[$normalizedUrl])'"
            } elseif (-not $sourceIdByUrl.ContainsKey($normalizedUrl)) {
                $sourceIdByUrl.Add($normalizedUrl, $sourceId)
            }
        } catch { Add-VgError $errors 'E_SOURCE' $sourceId 'source URL could not be normalized' }
    }

    $authorRoleById = New-VgStringSet
    $reviewerRoleById = New-VgStringSet
    foreach ($identity in $identityItems) {
        $identityId = [string](Get-VgProperty $identity 'identity_id')
        foreach ($role in @((Get-VgProperty $identity 'roles'))) {
            if ([string]$role -ceq 'author') { [void]$authorRoleById.Add($identityId) }
            if ([string]$role -ceq 'reviewer') { [void]$reviewerRoleById.Add($identityId) }
        }
    }

    $portfolioPublisherIds = New-VgStringSet
    $portfolioLocalPublisherIds = New-VgStringSet
    $portfolioAssignmentCount = 0
    $portfolioLocalAssignmentCount = 0
    $portfolioLensIds = New-VgStringSet
    $vietnamesePageCount = 0
    $nationalPublisherPages = New-VgStringObjectMap
    $nationalDomainPages = New-VgStringObjectMap
    $geographicGroupMembers = [ordered]@{
        northern = @('hanoi', 'ninh_binh', 'trang_an', 'tam_coc', 'old_quarter', 'french_quarter', 'west_lake')
        central = @('da_nang', 'hoi_an', 'quang_nam', 'hue')
        'south-central-coast' = @('binh_thuan', 'mui_ne', 'khanh_hoa', 'nha_trang')
        southern = @('ho_chi_minh_city', 'cu_chi', 'mekong_delta')
        island = @('phu_quoc', 'kien_giang')
    }
    $geographicPublishers = New-VgStringObjectMap
    foreach ($groupName in $geographicGroupMembers.Keys) { $geographicPublishers.Add([string]$groupName, (New-VgStringSet)) }
    $nationwideRegions = New-VgStringSet
    $pageByPath = New-VgStringObjectMap

    foreach ($page in $pages) {
        $path = [string](Get-VgProperty $page 'path')
        if ([string]::IsNullOrWhiteSpace($path)) { continue }
        if ($pageByPath.ContainsKey($path)) { Add-VgError $errors 'E_PAGE' $path 'page path is duplicated'; continue }
        $pageByPath.Add($path, $page)
        $options = @((Get-VgProperty $page 'options'))
        $optionById = Get-VgLookup $options 'option_id' $errors 'E_PAGE' $path
        $optionIds = [string[]]@($options | ForEach-Object { [string](Get-VgProperty $_ 'option_id') })
        $claims = @((Get-VgProperty $page 'claims'))
        $axes = @((Get-VgProperty $page 'axes'))
        $outcomes = @((Get-VgProperty $page 'outcomes'))
        $rules = @((Get-VgProperty $page 'rule_catalog'))
        $lenses = @((Get-VgProperty $page 'traveler_lenses'))
        $assignments = @((Get-VgProperty $page 'source_assignments'))
        $claimById = Get-VgLookup $claims 'claim_id' $errors 'E_PROVENANCE' $path
        $axisById = Get-VgLookup $axes 'axis_id' $errors 'E_PROVENANCE' $path
        $outcomeById = Get-VgLookup $outcomes 'outcome_id' $errors 'E_PROVENANCE' $path
        $ruleById = Get-VgLookup $rules 'rule_id' $errors 'E_RULE' $path
        $pageLensIds = New-VgStringSet
        foreach ($lens in $lenses) {
            $lensId = [string](Get-VgProperty $lens 'lens_id')
            if (-not $pageLensIds.Add($lensId)) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'lens_id is duplicated' }
            [void]$portfolioLensIds.Add($lensId)
        }

        $editorial = Get-VgProperty $page 'editorial'
        $authorId = [string](Get-VgProperty $editorial 'written_by_identity_id')
        $reviewerId = [string](Get-VgProperty $editorial 'reviewed_by_identity_id')
        if ($authorId -ceq $reviewerId) { Add-VgError $errors 'E_IDENTITY' $path 'author and reviewer identity IDs must be distinct' }
        if (-not $identityById.ContainsKey($authorId) -or -not $authorRoleById.Contains($authorId)) { Add-VgError $errors 'E_IDENTITY' $path "written_by_identity_id '$authorId' is not an active author" }
        if (-not $identityById.ContainsKey($reviewerId) -or -not $reviewerRoleById.Contains($reviewerId)) { Add-VgError $errors 'E_IDENTITY' $path "reviewed_by_identity_id '$reviewerId' is not an active reviewer" }

        $notApplicable = @((Get-VgProperty $page 'not_applicable'))
        foreach ($requiredGroup in @('access_transport', 'timing_duration', 'experience_fit')) {
            foreach ($optionId in $optionIds) {
                $covered = $false
                foreach ($claim in $claims) {
                    if ([string](Get-VgProperty $claim 'claim_group') -ceq $requiredGroup -and @((Get-VgProperty $claim 'option_ids')) -ccontains $optionId) { $covered = $true; break }
                }
                if (-not $covered) {
                    $hasReason = $false
                    foreach ($record in $notApplicable) {
                        if ([string](Get-VgProperty $record 'option_id') -ceq $optionId -and [string](Get-VgProperty $record 'claim_group') -ceq $requiredGroup -and -not [string]::IsNullOrWhiteSpace([string](Get-VgProperty $record 'reason'))) { $hasReason = $true; break }
                    }
                    if (-not $hasReason) { Add-VgError $errors 'E_NOT_APPLICABLE' "$path option=$optionId claim_group=$requiredGroup" 'missing explicit not_applicable reason' }
                }
            }
        }

        $relatedRoutePaths = New-VgStringSet
        foreach ($relatedRoute in @((Get-VgProperty $page 'related_routes'))) {
            $relatedRoutePath = [string](Get-VgProperty $relatedRoute 'path')
            if ($relatedRoutePath -ceq $path) { Add-VgError $errors 'E_ROUTE' "$path route=$relatedRoutePath" 'related route cannot link to its own page' }
            if (-not $relatedRoutePaths.Add($relatedRoutePath)) { Add-VgError $errors 'E_ROUTE' "$path route=$relatedRoutePath" 'related route path is duplicated' }
        }

        $graph = New-VgStringObjectMap
        $mappedClaims = New-VgStringSet; $mappedAxes = New-VgStringSet; $mappedOutcomes = New-VgStringSet
        $optionEdges = New-VgStringSet
        $pageSourceIds = New-VgStringSet
        $pageSourceClasses = New-VgStringSet
        $pageDomains = New-VgStringObjectMap
        $assignmentBySource = New-VgStringObjectMap
        $supportRows = New-Object System.Collections.ArrayList
        $coverageKeys = New-VgStringSet
        $pageHasVietnameseLocal = $false
        $assignedEvidenceLocalities = New-VgStringSet

        foreach ($assignment in $assignments) {
            $sourceId = [string](Get-VgProperty $assignment 'source_id')
            if ($assignmentBySource.ContainsKey($sourceId)) { Add-VgError $errors 'E_SOURCE' "$path source=$sourceId" 'source assignment is duplicated' } else { $assignmentBySource.Add($sourceId, $assignment) }
            if (-not $sourceById.ContainsKey($sourceId)) { Add-VgError $errors 'E_SOURCE' "$path source=$sourceId" 'source assignment references an unknown source'; continue }
            $source = $sourceById[$sourceId]
            [void]$pageSourceIds.Add($sourceId)
            $sourceClass = [string](Get-VgProperty $source 'source_class')
            [void]$pageSourceClasses.Add($sourceClass)
            $publisherId = [string](Get-VgProperty $source 'publisher_id')
            [void]$portfolioPublisherIds.Add($publisherId)
            $portfolioAssignmentCount++
            if ($sourceClass -ceq 'local_official' -or $sourceClass -ceq 'operational') { [void]$portfolioLocalPublisherIds.Add($publisherId); $portfolioLocalAssignmentCount++ }
            $publisherRecord = if ($publisherById.ContainsKey($publisherId)) { $publisherById[$publisherId] } else { $null }
            $normalizedDomain = if ($null -ne $publisherRecord) { [string](Get-VgProperty $publisherRecord 'canonical_domain') } else { [string](Get-VgProperty $source 'canonical_domain') }
            if (-not $pageDomains.ContainsKey($normalizedDomain)) { $pageDomains.Add($normalizedDomain, (New-VgStringSet)) }
            [void]$pageDomains[$normalizedDomain].Add($sourceId)
            if (($sourceClass -ceq 'local_official' -or $sourceClass -ceq 'operational') -and [string](Get-VgProperty $source 'language') -ceq 'vi' -and (Test-VgIntersection (Get-VgProperty $source 'localities') (Get-VgProperty $page 'localities'))) { $pageHasVietnameseLocal = $true }
            if ($sourceClass -ceq 'national_official') {
                if (-not $nationalPublisherPages.ContainsKey($publisherId)) { $nationalPublisherPages.Add($publisherId, (New-VgStringSet)) }
                [void]$nationalPublisherPages[$publisherId].Add($path)
                if (-not $nationalDomainPages.ContainsKey($normalizedDomain)) { $nationalDomainPages.Add($normalizedDomain, (New-VgStringSet)) }
                [void]$nationalDomainPages[$normalizedDomain].Add($path)
            }
            $mappings = @((Get-VgProperty $assignment 'mappings'))
            $hasAllOptions = @($mappings | Where-Object { [string](Get-VgProperty $_ 'option_id') -ceq 'all_options' }).Count -gt 0
            $hasSpecific = @($mappings | Where-Object { [string](Get-VgProperty $_ 'option_id') -cne 'all_options' }).Count -gt 0
            if ($hasAllOptions -and $hasSpecific) { Add-VgError $errors 'E_SCOPE' "$path source=$sourceId" 'all_options cannot be mixed with option-specific mappings in one source assignment' }
            if ($hasAllOptions -and [bool](Get-VgProperty $assignment 'decisive')) { Add-VgError $errors 'E_BACKGROUND' "$path source=$sourceId" 'background all_options evidence cannot be decisive' }
            if (-not $hasAllOptions) {
                $sourceLocalities = @((Get-VgProperty $source 'localities'))
                foreach ($locality in @((Get-VgProperty $page 'localities'))) { if ($sourceLocalities -ccontains [string]$locality) { [void]$assignedEvidenceLocalities.Add([string]$locality) } }
                foreach ($groupName in $geographicGroupMembers.Keys) { if (Test-VgIntersection $sourceLocalities $geographicGroupMembers[$groupName]) { [void]$geographicPublishers[[string]$groupName].Add($publisherId) } }
                foreach ($region in @('north', 'central', 'south')) { if ($sourceLocalities -ccontains $region) { [void]$nationwideRegions.Add($region) } }
            }
            $freshnessState = Get-VgFreshnessState $source ([string](Get-VgProperty $assignment 'evidence_label')) $AsOfDate
            $sourceExpired = Test-VgSourceExpired $source $AsOfDate
            foreach ($mapping in $mappings) {
                $claimId = [string](Get-VgProperty $mapping 'claim_id')
                $optionId = [string](Get-VgProperty $mapping 'option_id')
                $axisId = [string](Get-VgProperty $mapping 'axis_id')
                $outcomeId = [string](Get-VgProperty $mapping 'outcome_id')
                $validClaim = $claimById.ContainsKey($claimId); $validAxis = $axisById.ContainsKey($axisId); $validOutcome = $outcomeById.ContainsKey($outcomeId)
                if (-not $validClaim) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "orphan claim_id '$claimId'" }
                if (-not $validAxis) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "orphan axis_id '$axisId'" }
                if (-not $validOutcome) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "orphan outcome_id '$outcomeId'" }
                if ($optionId -cne 'all_options' -and -not $optionById.ContainsKey($optionId)) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "unknown option_id '$optionId'" }
                if ($validClaim -and $optionId -cne 'all_options' -and @((Get-VgProperty $claimById[$claimId] 'option_ids')) -cnotcontains $optionId) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "claim_id '$claimId' does not cover option_id '$optionId'" }
                if ($validAxis -and @((Get-VgProperty $axisById[$axisId] 'claim_ids')) -cnotcontains $claimId) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "axis_id '$axisId' does not reference claim_id '$claimId'" }
                if ($validAxis -and $optionId -cne 'all_options' -and @((Get-VgProperty $axisById[$axisId] 'option_ids')) -cnotcontains $optionId) { Add-VgError $errors 'E_PROVENANCE' "$path source=$sourceId" "axis_id '$axisId' does not cover option_id '$optionId'" }
                if ($validClaim) { [void]$mappedClaims.Add($claimId) }
                if ($validAxis) { [void]$mappedAxes.Add($axisId) }
                if ($validOutcome) { [void]$mappedOutcomes.Add($outcomeId) }
                Add-VgGraphEdge $graph $sourceId $claimId; Add-VgGraphEdge $graph $claimId $axisId; Add-VgGraphEdge $graph $axisId $outcomeId
                if (-not $impactWorking.ContainsKey($sourceId)) {
                    $impactRecord = [ordered]@{}
                    $impactRecord['claims'] = New-VgStringSet
                    $impactRecord['axes'] = New-VgStringSet
                    $impactRecord['outcomes'] = New-VgStringSet
                    $impactWorking.Add($sourceId, $impactRecord)
                }
                [void](Get-VgProperty $impactWorking[$sourceId] 'claims').Add($claimId); [void](Get-VgProperty $impactWorking[$sourceId] 'axes').Add($axisId); [void](Get-VgProperty $impactWorking[$sourceId] 'outcomes').Add($outcomeId)
                if ($optionId -cne 'all_options') { [void]$optionEdges.Add("$path$([char]0x1F)$optionId$([char]0x1F)$sourceId") }
                $claimGroup = if ($validClaim) { [string](Get-VgProperty $claimById[$claimId] 'claim_group') } else { 'unknown' }
                if ($validClaim -and @((Get-VgProperty $source 'claim_groups')) -cnotcontains $claimGroup) { Add-VgError $errors 'E_SOURCE' "$path source=$sourceId" "source registry does not authorize claim_group '$claimGroup'" }
                $supportOptions = if ($optionId -ceq 'all_options') { $optionIds } else { @($optionId) }
                foreach ($supportOption in $supportOptions) {
                    [void]$supportRows.Add([ordered]@{ option_id = $supportOption; claim_id = $claimId; claim_group = $claimGroup; axis_id = $axisId; outcome_id = $outcomeId; source_id = $sourceId; organization_id = [string](Get-VgProperty $source 'organization_id'); source_class = $sourceClass; evidence_label = [string](Get-VgProperty $assignment 'evidence_label'); freshness_state = $freshnessState; expired = $sourceExpired; decisive = [bool](Get-VgProperty $assignment 'decisive'); background = $hasAllOptions })
                }
                $coverageKey = "$path$([char]0x1F)$optionId$([char]0x1F)$claimGroup$([char]0x1F)$axisId$([char]0x1F)$sourceId"
                if ($coverageKeys.Add($coverageKey)) {
                    [void]$coverageMatrix.Add([ordered]@{
                        path = $path; option_id = $optionId; claim_group = $claimGroup; axis_id = $axisId; source_id = $sourceId
                        organization_id = [string](Get-VgProperty $source 'organization_id'); freshness_tier = [string](Get-VgProperty $source 'freshness_tier')
                        freshness_state = $freshnessState; evidence_label = [string](Get-VgProperty $assignment 'evidence_label'); decisive = [bool](Get-VgProperty $assignment 'decisive')
                    })
                }
            }
        }

        foreach ($locality in @((Get-VgProperty $page 'localities'))) { if (-not $assignedEvidenceLocalities.Contains([string]$locality)) { Add-VgError $errors 'E_LOCALITY' "$path locality=$locality" 'declared locality needs non-background assigned evidence' } }
        if ($pageHasVietnameseLocal) { $vietnamesePageCount++ } else { Add-VgError $errors 'E_LOCALITY' $path 'page needs a Vietnamese-language local or operational assignment for a compared locality' }
        if ($pageSourceIds.Count -lt 6 -or $pageSourceIds.Count -gt 10) { Add-VgError $errors 'E_SOURCE' $path "page has $($pageSourceIds.Count) unique assigned sources; expected 6-10" }
        if ($pageSourceClasses.Count -lt 4 -or -not $pageSourceClasses.Contains('national_official') -or -not $pageSourceClasses.Contains('local_official') -or -not $pageSourceClasses.Contains('operational')) { Add-VgError $errors 'E_SOURCE_CLASS' $path 'page needs at least four source classes including national, local, and operational' }
        foreach ($domain in $pageDomains.Keys) { if ($pageDomains[$domain].Count * 100 -gt $pageSourceIds.Count * 40) { Add-VgError $errors 'E_DOMAIN' "$path domain=$domain" "$($pageDomains[$domain].Count) of $($pageSourceIds.Count) unique page sources exceeds the 40 percent domain cap" } }
        $totalOptionEdges = $optionEdges.Count
        $minimumPercent = if ($optionIds.Count -eq 2) { 40 } else { 25 }
        foreach ($optionId in $optionIds) {
            $optionEdgeCount = @($optionEdges | Where-Object { $_.Split([char]0x1F)[1] -ceq $optionId }).Count
            if ($optionEdgeCount * 100 -lt $totalOptionEdges * $minimumPercent) { Add-VgError $errors 'E_BALANCE' "$path option=$optionId" "$optionEdgeCount of $totalOptionEdges unique option-source edges is below $minimumPercent percent" }
        }
        if ($pageSourceIds.Count -gt 0) {
            $pageLocalCount = @($pageSourceIds | Where-Object { $sourceClassValue = [string](Get-VgProperty $sourceById[$_] 'source_class'); $sourceClassValue -ceq 'local_official' -or $sourceClassValue -ceq 'operational' }).Count
            if ($pageLocalCount * 100 -lt $pageSourceIds.Count * 35) { Add-VgError $errors 'E_PORTFOLIO' $path "$pageLocalCount of $($pageSourceIds.Count) assignments is below the 35 percent local/operational minimum" }
        }
        foreach ($optionId in $optionIds) {
            foreach ($group in @('access_transport', 'timing_duration')) {
                $currentPrimary = @($supportRows | Where-Object { $_.option_id -ceq $optionId -and $_.claim_group -ceq $group -and $_.evidence_label -ceq 'primary' -and $_.freshness_state -ceq 'current' -and -not $_.background }).Count
                if ($currentPrimary -eq 0) { Add-VgError $errors 'E_FRESHNESS' "$path option=$optionId claim_group=$group" 'missing current primary evidence' }
            }
            $experienceOrganizations = New-VgStringSet
            $currentExperienceRows = @($supportRows | Where-Object { $_.option_id -ceq $optionId -and $_.claim_group -ceq 'experience_fit' -and $_.freshness_state -ceq 'current' -and -not $_.background })
            foreach ($row in $currentExperienceRows) { [void]$experienceOrganizations.Add([string]$row.organization_id) }
            $hasIndependentExperienceClass = @($currentExperienceRows | Where-Object { $_.source_class -ceq 'local_official' -or $_.source_class -ceq 'operational' -or $_.source_class -ceq 'independent_corroboration' }).Count -gt 0
            if ($experienceOrganizations.Count -lt 2 -or -not $hasIndependentExperienceClass) { Add-VgError $errors 'E_EXPERIENCE' "$path option=$optionId" 'experience-fit coverage needs current evidence from two controlling organizations including a local, operational, or independent source' }
        }

        foreach ($axis in $axes) {
            $axisId = [string](Get-VgProperty $axis 'axis_id')
            foreach ($claimId in @((Get-VgProperty $axis 'claim_ids'))) { if (-not $claimById.ContainsKey([string]$claimId)) { Add-VgError $errors 'E_PROVENANCE' "$path axis=$axisId" "orphan claim_id '$claimId'" } else { Add-VgGraphEdge $graph ([string]$claimId) $axisId } }
            if ([bool](Get-VgProperty $axis 'decisive')) {
                foreach ($optionId in $optionIds) {
                    $assessmentCount = @((Get-VgProperty $axis 'assessments') | Where-Object { [string](Get-VgProperty $_ 'option_id') -ceq $optionId }).Count
                    $axisSupport = @($supportRows | Where-Object { $_.axis_id -ceq $axisId -and $_.option_id -ceq $optionId -and $_.decisive }).Count
                    if ($assessmentCount -ne 1 -or @((Get-VgProperty $axis 'option_ids')) -cnotcontains $optionId -or $axisSupport -eq 0) { Add-VgError $errors 'E_AXIS' "$path axis=$axisId option=$optionId" 'decisive axis is not symmetric across option sides' }
                }
            }
        }
        foreach ($claim in $claims) {
            $claimId = [string](Get-VgProperty $claim 'claim_id')
            if (-not $mappedClaims.Contains($claimId)) { Add-VgError $errors 'E_PROVENANCE' "$path claim=$claimId" 'claim is orphaned from source evidence' }
            if (@($axes | Where-Object { @((Get-VgProperty $_ 'claim_ids')) -ccontains $claimId }).Count -eq 0) { Add-VgError $errors 'E_PROVENANCE' "$path claim=$claimId" 'claim is orphaned from axes' }
        }
        foreach ($axis in $axes) { $axisId = [string](Get-VgProperty $axis 'axis_id'); if (-not $mappedAxes.Contains($axisId)) { Add-VgError $errors 'E_PROVENANCE' "$path axis=$axisId" 'axis is orphaned from source evidence' } }
        foreach ($outcome in $outcomes) {
            $outcomeId = [string](Get-VgProperty $outcome 'outcome_id')
            $outcomeOrganizations = New-VgStringSet
            $currentOutcomeRows = @($supportRows | Where-Object { $_.outcome_id -ceq $outcomeId -and $_.decisive -and $_.freshness_state -ceq 'current' -and -not $_.background })
            foreach ($row in $currentOutcomeRows) { [void]$outcomeOrganizations.Add([string]$row.organization_id) }
            $responsiblePrimaryOrganizations = New-VgStringSet
            foreach ($row in @($currentOutcomeRows | Where-Object { $_.evidence_label -ceq 'primary' })) { [void]$responsiblePrimaryOrganizations.Add([string]$row.organization_id) }
            if (-not $mappedOutcomes.Contains($outcomeId)) { Add-VgError $errors 'E_PROVENANCE' "$path outcome=$outcomeId" 'outcome is orphaned from source evidence' }
            if ([bool](Get-VgProperty $outcome 'negative')) {
                $hasResponsiblePrimary = $responsiblePrimaryOrganizations.Count -gt 0
                $corroboratingOrganizations = New-VgStringSet
                foreach ($row in @($currentOutcomeRows | Where-Object { $_.evidence_label -ceq 'corroborating' })) { [void]$corroboratingOrganizations.Add([string]$row.organization_id) }
                if (-not $hasResponsiblePrimary -and $corroboratingOrganizations.Count -lt 2) { Add-VgError $errors 'E_NEGATIVE' "$path outcome=$outcomeId" 'negative outcome needs one responsible current primary source or current corroboration from two controlling organizations' }
            }
            $allowsSoleResponsibleLiveCheck = ([bool](Get-VgProperty $outcome 'live_check_required') -and $outcomeOrganizations.Count -eq 1 -and $responsiblePrimaryOrganizations.Count -eq 1)
            if ([bool](Get-VgProperty $outcome 'settled') -and $outcomeOrganizations.Count -lt 2 -and -not $allowsSoleResponsibleLiveCheck) { Add-VgError $errors 'E_SETTLED' "$path outcome=$outcomeId" 'settled outcome needs independent controlling organizations unless a live check has one sole responsible current primary organization' }
            if ([bool](Get-VgProperty $outcome 'settled') -and [bool](Get-VgProperty $outcome 'decisive')) {
                foreach ($expiredRow in @($supportRows | Where-Object { $_.outcome_id -ceq $outcomeId -and $_.decisive -and -not $_.background -and $_.expired })) {
                    $hasLiveCheckLabel = ([string]$expiredRow.evidence_label -ceq 'live_check_required' -and [bool](Get-VgProperty $outcome 'live_check_required'))
                    $hasCurrentCover = @($supportRows | Where-Object { $_.option_id -ceq $expiredRow.option_id -and $_.claim_id -ceq $expiredRow.claim_id -and $_.axis_id -ceq $expiredRow.axis_id -and $_.outcome_id -ceq $expiredRow.outcome_id -and $_.source_id -cne $expiredRow.source_id -and -not $_.background -and $_.freshness_state -ceq 'current' }).Count -gt 0
                    if (-not ($hasLiveCheckLabel -and $hasCurrentCover)) { Add-VgError $errors 'E_FRESHNESS' "$path outcome=$outcomeId source=$($expiredRow.source_id)" 'expired source supports a settled decisive recommendation without a complete live-check exception' }
                }
            }
            if ([string](Get-VgProperty $outcome 'outcome_type') -ceq 'option') {
                $winner = [string](Get-VgProperty $outcome 'winner_option_id')
                if ([string]::IsNullOrWhiteSpace($winner) -or -not $optionById.ContainsKey($winner)) { Add-VgError $errors 'E_OUTCOME' "$path outcome=$outcomeId" 'option outcome needs a valid winner_option_id' }
            }
        }

        foreach ($rule in $rules) {
            $ruleId = [string](Get-VgProperty $rule 'rule_id')
            foreach ($forbidden in @('weight', 'priority', 'score')) { if (Test-VgProperty $rule $forbidden) { Add-VgError $errors 'E_RULE' "$path rule=$ruleId" "numeric scoring field '$forbidden' is forbidden" } }
            foreach ($claimId in @((Get-VgProperty $rule 'claim_ids'))) { if (-not $claimById.ContainsKey([string]$claimId)) { Add-VgError $errors 'E_RULE' "$path rule=$ruleId" "unknown claim_id '$claimId'" } }
            foreach ($axisId in @((Get-VgProperty $rule 'axis_ids'))) { if (-not $axisById.ContainsKey([string]$axisId)) { Add-VgError $errors 'E_RULE' "$path rule=$ruleId" "unknown axis_id '$axisId'" } }
            $ruleOutcomeId = [string](Get-VgProperty $rule 'outcome_id')
            if (-not $outcomeById.ContainsKey($ruleOutcomeId)) { Add-VgError $errors 'E_RULE' "$path rule=$ruleId" "unknown outcome_id '$ruleOutcomeId'" }
        }
        $primaryOutcomeId = [string](Get-VgProperty $page 'primary_outcome_id')
        if (-not $outcomeById.ContainsKey($primaryOutcomeId)) { Add-VgError $errors 'E_OUTCOME' $path "primary_outcome_id '$primaryOutcomeId' is unknown" }
        $lensOutcomeIds = New-VgStringSet
        foreach ($lens in $lenses) {
            $lensId = [string](Get-VgProperty $lens 'lens_id')
            $pathIds = @((Get-VgProperty $lens 'rule_path'))
            $resolvedPath = @()
            foreach ($ruleId in $pathIds) { if ($ruleById.ContainsKey([string]$ruleId)) { $resolvedPath += $ruleById[[string]$ruleId] } else { Add-VgError $errors 'E_RULE' "$path lens=$lensId" "unknown rule_id '$ruleId'" } }
            $lensOutcomeId = [string](Get-VgProperty $lens 'outcome_id')
            [void]$lensOutcomeIds.Add($lensOutcomeId)
            $hardCount = @($resolvedPath | Where-Object { [string](Get-VgProperty $_ 'kind') -ceq 'hard_constraint' }).Count
            $preferenceCount = @($resolvedPath | Where-Object { [string](Get-VgProperty $_ 'kind') -ceq 'preference' }).Count
            $tieCount = @($resolvedPath | Where-Object { [string](Get-VgProperty $_ 'kind') -ceq 'tie_breaker' }).Count
            if ($hardCount -gt 1 -or ($hardCount -eq 1 -and [string](Get-VgProperty $resolvedPath[0] 'kind') -cne 'hard_constraint')) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'hard constraint must appear at most once and first' }
            $hardTerminates = ($resolvedPath.Count -eq 1 -and $hardCount -eq 1 -and [string](Get-VgProperty $resolvedPath[0] 'outcome_id') -ceq $lensOutcomeId)
            if ($hardCount -eq 1 -and -not $hardTerminates) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'a hard constraint must be the sole used rule and match the lens terminal outcome' }
            if (-not $hardTerminates -and $preferenceCount -ne 1) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'rule path needs exactly one preference unless the first hard constraint terminates' }
            if ($tieCount -gt 1) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'rule path permits at most one tie-breaker' }
            if ($tieCount -eq 1 -and [string](Get-VgProperty $resolvedPath[$resolvedPath.Count - 1] 'kind') -cne 'tie_breaker') { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'tie-breaker must be later and terminal' }
            foreach ($resolvedRule in $resolvedPath) { if (-not (Test-VgIntersection (Get-VgProperty $resolvedRule 'context_tags') (Get-VgProperty $lens 'context_tags'))) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" "rule '$([string](Get-VgProperty $resolvedRule 'rule_id'))' has no context-tag intersection" } }
            if (-not $outcomeById.ContainsKey($lensOutcomeId)) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" "unknown terminal outcome '$lensOutcomeId'" }
            if ($resolvedPath.Count -gt 0 -and [string](Get-VgProperty $resolvedPath[$resolvedPath.Count - 1] 'outcome_id') -cne $lensOutcomeId) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'last used rule does not reach the lens terminal outcome' }
            if ([string]::IsNullOrWhiteSpace([string](Get-VgProperty $lens 'trade_off')) -and [string]::IsNullOrWhiteSpace([string](Get-VgProperty $lens 'reversal_condition'))) { Add-VgError $errors 'E_RULE' "$path lens=$lensId" 'lens needs a material trade-off or reversal condition' }
        }
        if ($lensOutcomeIds.Count -lt 2) { Add-VgError $errors 'E_RULE' $path 'traveler lenses must resolve to at least two distinct outcomes' }
        if (Test-VgGraphCycle $graph) { Add-VgError $errors 'E_CYCLE' $path 'provenance graph contains a cycle' }

        $sortedAssignments = @($assignments | Sort-Object @{ Expression = { [string](Get-VgProperty $_ 'source_id') }; Ascending = $true })
        $bundleSources = @()
        foreach ($assignment in $sortedAssignments) {
            $sourceId = [string](Get-VgProperty $assignment 'source_id')
            if (-not $sourceById.ContainsKey($sourceId)) { continue }
            $source = $sourceById[$sourceId]
            $publisherId = [string](Get-VgProperty $source 'publisher_id')
            $publisherRecord = if ($publisherById.ContainsKey($publisherId)) { $publisherById[$publisherId] } else { $null }
            $normalizedMappings = @((Get-VgProperty $assignment 'mappings') | Sort-Object @{ Expression = { ConvertTo-VgCanonicalJsonValue $_ }; Ascending = $true })
            $bundleSources += [ordered]@{
                source_id = $sourceId; publisher_id = $publisherId; publisher_name = [string](Get-VgProperty $source 'publisher_name')
                organization_id = [string](Get-VgProperty $source 'organization_id'); canonical_domain = if ($null -ne $publisherRecord) { [string](Get-VgProperty $publisherRecord 'canonical_domain') } else { [string](Get-VgProperty $source 'canonical_domain') }
                source_class = [string](Get-VgProperty $source 'source_class'); title = [string](Get-VgProperty $source 'title'); url = [string](Get-VgProperty $source 'url')
                checked_on = [string](Get-VgProperty $source 'checked_on'); freshness_tier = [string](Get-VgProperty $source 'freshness_tier'); freshness_state = Get-VgFreshnessState $source ([string](Get-VgProperty $assignment 'evidence_label')) $AsOfDate
                localities = [object[]]@((Get-VgProperty $source 'localities')); language = [string](Get-VgProperty $source 'language'); media_type = [string](Get-VgProperty $source 'expected_media_type')
                evidence_label = [string](Get-VgProperty $assignment 'evidence_label'); claim_groups = [object[]]@((Get-VgProperty $source 'claim_groups')); mappings = [object[]]@($normalizedMappings | ForEach-Object { Copy-VgValue $_ })
            }
        }
        $bundleAxes = @()
        foreach ($axis in $axes) {
            $axisId = [string](Get-VgProperty $axis 'axis_id')
            $axisSourceIds = Get-VgSortedStrings @($supportRows | Where-Object { $_.axis_id -ceq $axisId } | ForEach-Object { $_.source_id } | Select-Object -Unique)
            $bundleAxes += [ordered]@{
                axis_id = $axisId; label = [string](Get-VgProperty $axis 'label'); explanation = [string](Get-VgProperty $axis 'explanation')
                claim_ids = [object[]]@((Get-VgProperty $axis 'claim_ids')); option_ids = [object[]]@((Get-VgProperty $axis 'option_ids')); decisive = [bool](Get-VgProperty $axis 'decisive')
                assessments = [object[]]@((Get-VgProperty $axis 'assessments') | ForEach-Object { Copy-VgValue $_ }); source_ids = [object[]]@($axisSourceIds)
            }
        }
        $bundleLenses = @()
        foreach ($lens in $lenses) {
            $usedRules = @(); $ruleOrder = 0
            foreach ($ruleId in @((Get-VgProperty $lens 'rule_path'))) { if ($ruleById.ContainsKey([string]$ruleId)) { $ruleOrder++; $usedRules += ConvertTo-VgResolvedRule $ruleById[[string]$ruleId] $ruleOrder } }
            $lensOutcomeId = [string](Get-VgProperty $lens 'outcome_id')
            $lensOutcome = if ($outcomeById.ContainsKey($lensOutcomeId)) { $outcomeById[$lensOutcomeId] } else { $null }
            $bundleLens = [ordered]@{
                lens_id = [string](Get-VgProperty $lens 'lens_id'); traveler = [string](Get-VgProperty $lens 'traveler'); context_tags = [object[]]@((Get-VgProperty $lens 'context_tags'))
                outcome_id = $lensOutcomeId; outcome = if ($null -ne $lensOutcome) { [string](Get-VgProperty $lensOutcome 'summary') } else { '' }; rule_path = [object[]]@($usedRules)
            }
            if (Test-VgProperty $lens 'trade_off') { $bundleLens['trade_off'] = [string](Get-VgProperty $lens 'trade_off') }
            if (Test-VgProperty $lens 'reversal_condition') { $bundleLens['reversal_condition'] = [string](Get-VgProperty $lens 'reversal_condition') }
            $bundleLenses += $bundleLens
        }
        $primaryOutcome = if ($outcomeById.ContainsKey($primaryOutcomeId)) { $outcomeById[$primaryOutcomeId] } else { $null }
        $primaryRuleIds = @()
        foreach ($lens in $lenses) { if ([string](Get-VgProperty $lens 'outcome_id') -ceq $primaryOutcomeId) { $primaryRuleIds = @((Get-VgProperty $lens 'rule_path')); break } }
        if ($primaryRuleIds.Count -eq 0) { $primaryRuleIds = @($rules | Where-Object { [string](Get-VgProperty $_ 'outcome_id') -ceq $primaryOutcomeId } | ForEach-Object { [string](Get-VgProperty $_ 'rule_id') } | Select-Object -First 8) }
        $primaryRules = @(); $primaryOrder = 0
        foreach ($ruleId in $primaryRuleIds) { if ($ruleById.ContainsKey([string]$ruleId)) { $primaryOrder++; $primaryRules += ConvertTo-VgResolvedRule $ruleById[[string]$ruleId] $primaryOrder } }
        $primaryDecision = [ordered]@{
            outcome_id = $primaryOutcomeId; outcome = if ($null -ne $primaryOutcome) { [string](Get-VgProperty $primaryOutcome 'outcome_type') } else { 'no_clear_winner' }
            summary = if ($null -ne $primaryOutcome) { [string](Get-VgProperty $primaryOutcome 'summary') } else { 'No reviewed outcome is available.' }; rules = [object[]]@($primaryRules)
        }
        if ($null -ne $primaryOutcome -and (Test-VgProperty $primaryOutcome 'winner_option_id')) { $primaryDecision['winner_option_id'] = [string](Get-VgProperty $primaryOutcome 'winner_option_id') }
        $pageImpact = [ordered]@{}
        foreach ($sourceId in Get-VgSortedStrings $assignmentBySource.Keys) {
            if ($impactWorking.ContainsKey($sourceId)) {
                $workingEntry = $impactWorking[$sourceId]
                $pageImpact[$sourceId] = [ordered]@{ source_id = $sourceId; claim_ids = [object[]]@(Get-VgSortedStrings (Get-VgProperty $workingEntry 'claims')); axis_ids = [object[]]@(Get-VgSortedStrings (Get-VgProperty $workingEntry 'axes')); outcome_ids = [object[]]@(Get-VgSortedStrings (Get-VgProperty $workingEntry 'outcomes')) }
            }
        }
        $bundle = [ordered]@{
            schema_version = 'v2'; bundle_hash = ('0' * 64); manifest_version = [string](Get-VgProperty $Manifest 'manifest_version')
            source_registry_version = [string](Get-VgProperty $Sources 'registry_version'); organization_registry_version = [string](Get-VgProperty $Organizations 'registry_version')
            activation_artifact_version = [string](Get-VgProperty $Manifest 'activation_artifact_version'); path = $path; post_id = Get-VgProperty $page 'post_id'
            editorial = Copy-VgValue (Get-VgProperty $page 'editorial'); archetype = [string](Get-VgProperty $page 'archetype'); localities = [object[]]@((Get-VgProperty $page 'localities'))
            options = [object[]]@($options | ForEach-Object { Copy-VgValue $_ }); primary_decision = $primaryDecision; field_note = [string](Get-VgProperty $page 'field_note')
            evidence_moat = [object[]]@((Get-VgProperty $page 'evidence_moat')); axes = [object[]]@($bundleAxes); traveler_lenses = [object[]]@($bundleLenses); sources = [object[]]@($bundleSources)
            related_routes = [object[]]@((Get-VgProperty $page 'related_routes') | ForEach-Object { Copy-VgValue $_ })
            update_log = [object[]]@([ordered]@{ date = [string](Get-VgProperty $editorial 'last_meaningful_update'); summary = [string](Get-VgProperty $editorial 'update_summary'); change_reason = [string](Get-VgProperty $editorial 'change_reason'); affected_public_labels = [object[]]@((Get-VgProperty $editorial 'affected_public_labels')) })
            module_requirements = [ordered]@{ validator_version = 'validator-v2'; renderer_version = 'renderer-v2'; cache_key_fields = [object[]]@('path', 'bundle_hash', 'schema_version', 'source_registry_version', 'activation_artifact_version'); required_features = [object[]]@('decision_frame', 'evidence_labels', 'source_checked_dates', 'related_routes', 'update_log', 'editorial_byline') }
            provenance_hash = Get-VgSha256Hex -Value $pageImpact
            render_contract = [ordered]@{ decision_heading = 'Decision guide'; source_heading = 'Sources checked'; route_heading = 'Continue planning'; update_heading = 'What changed'; page_language = 'vi'; max_visible_characters = 1800 }
        }
        $hashInput = Copy-VgValue $bundle; [void]$hashInput.Remove('bundle_hash'); $bundle['bundle_hash'] = Get-VgSha256Hex -Value $hashInput
        foreach ($schemaError in @(Test-VgSchemaDocument -Document $bundle -Schema $Schema -DefinitionName 'resolvedBundleV2' -DocumentId "$path bundle")) { [void]$errors.Add($schemaError) }
        $resolvedBundles[$path] = $bundle
    }

    if ($Profile -ceq 'Fixture') {
        $stageInventories['fixture'] = [object[]]@(Get-VgSortedStrings $pageByPath.Keys)
    } else {
        foreach ($inventoryName in @('baseline', 'canary', 'full', 'six', 'permanent_controls')) { $stageInventories[$inventoryName] = [object[]]@((Get-VgProperty $Manifest $inventoryName)) }
        if ($pages.Count -ne 9) { Add-VgError $errors 'E_PORTFOLIO' 'portfolio' "Production requires 9 comparison pages; found $($pages.Count)" }
        $targetPostByPath = New-VgStringObjectMap
        foreach ($targetMapping in @((Get-VgProperty $Manifest 'target_mappings'))) {
            $targetPath = [string](Get-VgProperty $targetMapping 'path')
            if (-not [string]::IsNullOrWhiteSpace($targetPath) -and -not $targetPostByPath.ContainsKey($targetPath)) { $targetPostByPath.Add($targetPath, (Get-VgProperty $targetMapping 'post_id')) }
        }
        foreach ($page in $pages) {
            $pagePath = [string](Get-VgProperty $page 'path')
            if (-not $targetPostByPath.ContainsKey($pagePath) -or [int](Get-VgProperty $page 'post_id') -ne [int]$targetPostByPath[$pagePath]) { Add-VgError $errors 'E_PROFILE' $pagePath 'Production page path/post_id does not match target_mappings' }
        }
        foreach ($targetPath in $targetPostByPath.Keys) { if (-not $pageByPath.ContainsKey($targetPath)) { Add-VgError $errors 'E_PROFILE' $targetPath 'target_mappings entry has no Production comparisonPage' } }
        if ($portfolioPublisherIds.Count -lt 18) { Add-VgError $errors 'E_PORTFOLIO' 'portfolio' "Production requires at least 18 publisher IDs; found $($portfolioPublisherIds.Count)" }
        if ($portfolioLocalPublisherIds.Count -lt 12) { Add-VgError $errors 'E_PORTFOLIO' 'portfolio' "Production requires at least 12 local/operational publisher IDs; found $($portfolioLocalPublisherIds.Count)" }
        if ($portfolioAssignmentCount -eq 0 -or $portfolioLocalAssignmentCount * 100 -lt $portfolioAssignmentCount * 35) { Add-VgError $errors 'E_PORTFOLIO' 'portfolio' "$portfolioLocalAssignmentCount of $portfolioAssignmentCount assignments is below the 35 percent local/operational minimum" }
        if ($vietnamesePageCount -lt 7) { Add-VgError $errors 'E_PORTFOLIO' 'portfolio' "Production requires Vietnamese local/operational evidence on 7 pages; found $vietnamesePageCount" }
        if ($portfolioLensIds.Count -lt 7) { Add-VgError $errors 'E_PORTFOLIO' 'portfolio' "Production requires at least 7 traveler lenses; found $($portfolioLensIds.Count)" }
        foreach ($groupName in $geographicGroupMembers.Keys) { if ($geographicPublishers[[string]$groupName].Count -lt 2) { Add-VgError $errors 'E_LOCALITY' "portfolio geographic_group=$groupName" 'required geographic group has fewer than two publishers' } }
        if ($nationwideRegions.Count -lt 3) { Add-VgError $errors 'E_LOCALITY' 'portfolio geographic_group=nationwide' 'nationwide coverage requires assigned evidence for north, central, and south' }
        foreach ($publisherId in $nationalPublisherPages.Keys) { if ($nationalPublisherPages[$publisherId].Count -gt 6) { Add-VgError $errors 'E_PORTFOLIO' "publisher=$publisherId" 'national publisher is reused on more than six pages' } }
        foreach ($domain in $nationalDomainPages.Keys) { if ($nationalDomainPages[$domain].Count -gt 6) { Add-VgError $errors 'E_PORTFOLIO' "domain=$domain" 'national domain is reused on more than six pages' } }
    }

    $sortedCoverage = @($coverageMatrix | Sort-Object @{ Expression = { [string]::Join([char]0x1F, @($_.path, $_.option_id, $_.claim_group, $_.axis_id, $_.source_id)) }; Ascending = $true })
    $impactIndex = [ordered]@{}
    foreach ($sourceId in Get-VgSortedStrings $impactWorking.Keys) {
        $working = $impactWorking[$sourceId]
        $impactIndex[$sourceId] = [ordered]@{ source_id = $sourceId; claim_ids = [object[]]@(Get-VgSortedStrings (Get-VgProperty $working 'claims')); axis_ids = [object[]]@(Get-VgSortedStrings (Get-VgProperty $working 'axes')); outcome_ids = [object[]]@(Get-VgSortedStrings (Get-VgProperty $working 'outcomes')) }
    }
    $hashes = [ordered]@{
        Manifest = Get-VgSha256Hex -Value (Get-VgNormalizedHashInput $Manifest 'Manifest')
        Sources = Get-VgSha256Hex -Value (Get-VgNormalizedHashInput $Sources 'Sources')
        Organizations = Get-VgSha256Hex -Value (Get-VgNormalizedHashInput $Organizations 'Organizations')
        Identities = Get-VgSha256Hex -Value (Get-VgNormalizedHashInput $Identities 'Identities')
        Portfolio = Get-VgSha256Hex -Value ([ordered]@{ bundles = $resolvedBundles; coverage = $sortedCoverage; impact = $impactIndex; inventories = $stageInventories })
    }
    return [pscustomobject]@{
        Ok = [bool]($errors.Count -eq 0)
        Errors = [string[]]@($errors.ToArray())
        Hashes = $hashes
        ResolvedBundles = $resolvedBundles
        CoverageMatrix = [object[]]@($sortedCoverage)
        ImpactIndex = $impactIndex
        StageInventories = $stageInventories
    }
}

function Resolve-VgComparisonPortfolio {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]$Manifest,
        [Parameter(Mandatory = $true)]$Sources,
        [Parameter(Mandatory = $true)]$Organizations,
        [Parameter(Mandatory = $true)]$Identities,
        [Parameter(Mandatory = $true)]$Schema,
        [ValidateSet('Fixture', 'Production')][string]$Profile = 'Production',
        [datetime]$AsOfDate = [datetime]::UtcNow
    )
    return (Invoke-VgComparisonPortfolio $Manifest $Sources $Organizations $Identities $Schema $Profile $AsOfDate)
}

function Test-VgComparisonPortfolio {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]$Manifest,
        [Parameter(Mandatory = $true)]$Sources,
        [Parameter(Mandatory = $true)]$Organizations,
        [Parameter(Mandatory = $true)]$Identities,
        [Parameter(Mandatory = $true)]$Schema,
        [ValidateSet('Fixture', 'Production')][string]$Profile = 'Production',
        [datetime]$AsOfDate = [datetime]::UtcNow
    )
    return (Invoke-VgComparisonPortfolio $Manifest $Sources $Organizations $Identities $Schema $Profile $AsOfDate)
}

function New-VgComparisonArtifact {
    [CmdletBinding()]
    param(
        [Parameter(Mandatory = $true)]$Manifest,
        [Parameter(Mandatory = $true)]$Sources,
        [Parameter(Mandatory = $true)]$Organizations,
        [Parameter(Mandatory = $true)]$Identities,
        [Parameter(Mandatory = $true)]$Schema,
        [ValidateSet('Fixture', 'Production')][string]$Profile = 'Production',
        [datetime]$AsOfDate = [datetime]::UtcNow
    )
    $result = Invoke-VgComparisonPortfolio $Manifest $Sources $Organizations $Identities $Schema $Profile $AsOfDate
    if (-not $result.Ok) { throw [string]$result.Errors[0] }
    $artifact = [ordered]@{
        profile = $Profile
        as_of_date = $AsOfDate.ToUniversalTime().ToString('yyyy-MM-ddTHH:mm:ssZ')
        hashes = Copy-VgValue $result.Hashes
        resolved_bundles = Copy-VgValue $result.ResolvedBundles
        coverage_matrix = Copy-VgValue $result.CoverageMatrix
        impact_index = Copy-VgValue $result.ImpactIndex
        stage_inventories = Copy-VgValue $result.StageInventories
        artifact_hash = ('0' * 64)
    }
    $hashInput = Copy-VgValue $artifact; [void]$hashInput.Remove('artifact_hash'); $artifact['artifact_hash'] = Get-VgSha256Hex -Value $hashInput
    return ,$artifact
}

function Compare-VgArtifactDeterminism {
    [CmdletBinding()]
    param([Parameter(Mandatory = $true)]$First, [Parameter(Mandatory = $true)]$Second)
    return ((ConvertTo-VgCanonicalJsonValue $First) -ceq (ConvertTo-VgCanonicalJsonValue $Second))
}

Export-ModuleMember -Function @(
    'Read-VgJsonDocument',
    'ConvertTo-VgCanonicalJson',
    'Get-VgSha256Hex',
    'Test-VgSchemaDocument',
    'Resolve-VgComparisonPortfolio',
    'Test-VgComparisonPortfolio',
    'New-VgComparisonArtifact',
    'Compare-VgArtifactDeterminism'
)
