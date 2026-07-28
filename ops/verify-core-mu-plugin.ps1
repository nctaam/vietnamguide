$ErrorActionPreference = 'Stop'

$RepoRoot = Split-Path -Parent $PSScriptRoot
$PluginPath = 'wordpress/wp-content/mu-plugins/vietnamguide-core.php'
$ThemeDirectoryPath = 'wordpress/wp-content/themes/vietnamguide-premium'

function Get-PhpFunctionBody {
    param(
        [string]$Content,
        [string]$Name
    )

    $Pattern = '(?ms)^function\s+' + [regex]::Escape($Name) + '\b.*?^}'
    $Match = [regex]::Match($Content, $Pattern)
    if (-not $Match.Success) {
        return ''
    }

    return $Match.Value
}

function Get-CorePluginCompatibilityFingerprint {
    param(
        [string]$Content
    )

    $NormalizedContent = $Content.Replace("`r`n", "`n").Replace("`r", "`n")
    $NormalizedContent = [regex]::Replace(
        $NormalizedContent,
        '(?m)^\s*\*\s*Version:\s*.*$',
        ' * Version: __VG_VERSION__'
    )

    $AuthorizedRegions = @(
        @{ Name = 'vg_register_pattern_category'; Marker = '__VG_REGISTER_PATTERN_CATEGORY__' }
        @{ Name = 'vg_add_affiliate_link_attributes'; Marker = '__VG_ADD_AFFILIATE_LINK_ATTRIBUTES__' }
    )

    foreach ($Region in $AuthorizedRegions) {
        $FunctionBody = Get-PhpFunctionBody $NormalizedContent $Region.Name
        if ($FunctionBody -ne '') {
            $NormalizedContent = $NormalizedContent.Replace($FunctionBody, $Region.Marker)
        }
    }

    $Sha256 = [System.Security.Cryptography.SHA256]::Create()
    try {
        $Bytes = [System.Text.Encoding]::UTF8.GetBytes($NormalizedContent)
        return ([System.BitConverter]::ToString($Sha256.ComputeHash($Bytes))).Replace('-', '').ToLowerInvariant()
    } finally {
        $Sha256.Dispose()
    }
}

function Add-ContractFailure {
    param(
        [System.Collections.Generic.List[string]]$Failures,
        [string]$CaseName,
        [string]$Message
    )

    [void]$Failures.Add("${CaseName}: $Message")
}

function Require-ContractMatch {
    param(
        [System.Collections.Generic.List[string]]$Failures,
        [string]$CaseName,
        [string]$Label,
        [string]$Content,
        [string]$Pattern
    )

    if (-not [regex]::IsMatch($Content, $Pattern)) {
        Add-ContractFailure $Failures $CaseName "Missing contract: $Label"
    }
}

function Require-ContractCount {
    param(
        [System.Collections.Generic.List[string]]$Failures,
        [string]$CaseName,
        [string]$Label,
        [string]$Content,
        [string]$Pattern,
        [int]$ExpectedCount
    )

    $ActualCount = ([regex]::Matches($Content, $Pattern)).Count
    if ($ActualCount -ne $ExpectedCount) {
        Add-ContractFailure $Failures $CaseName "$Label expected $ExpectedCount occurrence(s), found $ActualCount"
    }
}

function Test-CorePluginContract {
    [OutputType([string[]])]
    param(
        [string]$PluginContent,
        [string[]]$ThemePhpContents,
        [string]$CaseName
    )

    $Failures = [System.Collections.Generic.List[string]]::new()

    # Plugin identity and the actual top-level direct-access guard.
    Require-ContractMatch $Failures $CaseName 'Plugin Name metadata' $PluginContent '(?m)^\s*\*\s*Plugin Name:\s*VietnamGuide Core\s*$'
    Require-ContractMatch $Failures $CaseName 'Version 0.1.6 metadata' $PluginContent '(?m)^\s*\*\s*Version:\s*0\.1\.6\s*$'
    Require-ContractMatch $Failures $CaseName 'top-level ABSPATH guard' $PluginContent '(?ms)\A<\?php\s*/\*\*.*?^\s*\*/\s*if\s*\(\s*!\s*defined\s*\(\s*[''"]ABSPATH[''"]\s*\)\s*\)\s*\{\s*exit\s*;\s*\}'

    $ExpectedCompatibilityFingerprint = 'b4bd55544a1c7a86eda065ce92dcc02aa9dab53027cc5be2aff494e501069aa6'
    $ActualCompatibilityFingerprint = Get-CorePluginCompatibilityFingerprint $PluginContent
    if ($ActualCompatibilityFingerprint -cne $ExpectedCompatibilityFingerprint) {
        Add-ContractFailure $Failures $CaseName "Immutable plugin fingerprint expected $ExpectedCompatibilityFingerprint, found $ActualCompatibilityFingerprint"
    }

    # Constants must retain their deployed key-to-meta mappings.
    $EeatMatch = [regex]::Match($PluginContent, '(?ms)^const\s+VG_EEAT_META_KEYS\s*=\s*\[(?<body>.*?)^\];')
    if (-not $EeatMatch.Success) {
        Add-ContractFailure $Failures $CaseName 'Missing VG_EEAT_META_KEYS constant body'
    } else {
        $EeatEntries = [ordered]@{
            'primary_decision' = 'vg_eeat_primary_decision'
            'reviewed_guide' = 'vg_eeat_reviewed_guide'
            'written_by' = 'vg_eeat_written_by'
            'reviewed_by' = 'vg_eeat_reviewed_by'
            'last_meaningful_update' = 'vg_eeat_last_meaningful_update'
            'update_summary' = 'vg_eeat_update_summary'
            'sources_checked' = 'vg_eeat_sources_checked'
            'field_note' = 'vg_eeat_field_note'
            'affiliate_status' = 'vg_eeat_affiliate_status'
            'evidence_moat' = 'vg_eeat_evidence_moat'
            'related_routes' = 'vg_eeat_related_routes'
            'hero_image_credit' = 'vg_eeat_hero_image_credit'
        }

        foreach ($Entry in $EeatEntries.GetEnumerator()) {
            $EntryPattern = '[''"]{0}[''"]\s*=>\s*[''"]{1}[''"]' -f [regex]::Escape($Entry.Key), [regex]::Escape($Entry.Value)
            Require-ContractMatch $Failures $CaseName "VG_EEAT_META_KEYS $($Entry.Key) mapping" $EeatMatch.Groups['body'].Value $EntryPattern
        }
    }

    $AdminFirstMatch = [regex]::Match($PluginContent, '(?ms)^const\s+VG_ADMIN_FIRST_META_KEYS\s*=\s*\[(?<body>.*?)^\];')
    if (-not $AdminFirstMatch.Success) {
        Add-ContractFailure $Failures $CaseName 'Missing VG_ADMIN_FIRST_META_KEYS constant body'
    } else {
        $AdminFirstEntries = [ordered]@{
            'content_owner' = 'vg_content_owner'
            'automation_lock' = 'vg_automation_lock'
            'last_manual_review' = 'vg_last_manual_review'
            'workflow_notes' = 'vg_admin_first_notes'
            'automation_hash' = '_vg_last_automation_content_hash'
            'meta_hash_prefix' = '_vg_last_automation_meta_hash_'
        }

        foreach ($Entry in $AdminFirstEntries.GetEnumerator()) {
            $EntryPattern = '[''"]{0}[''"]\s*=>\s*[''"]{1}[''"]' -f [regex]::Escape($Entry.Key), [regex]::Escape($Entry.Value)
            Require-ContractMatch $Failures $CaseName "VG_ADMIN_FIRST_META_KEYS $($Entry.Key) mapping" $AdminFirstMatch.Groups['body'].Value $EntryPattern
        }
    }

    # Each shortcode slug is paired to its deployed callback and a real rendering body.
    $Shortcodes = @(
        @{ Slug = 'vg_editorial_proof'; Callback = 'vg_shortcode_editorial_proof_panel' }
        @{ Slug = 'vg_source_trail'; Callback = 'vg_shortcode_source_trail' }
        @{ Slug = 'vg_update_log'; Callback = 'vg_shortcode_update_log' }
        @{ Slug = 'vg_related_routes'; Callback = 'vg_shortcode_related_routes' }
    )

    foreach ($Shortcode in $Shortcodes) {
        $CallbackBody = Get-PhpFunctionBody $PluginContent $Shortcode.Callback
        if ($CallbackBody -eq '') {
            Add-ContractFailure $Failures $CaseName "Missing shortcode callback body: $($Shortcode.Callback)"
        } else {
            Require-ContractMatch $Failures $CaseName "$($Shortcode.Callback) non-empty return path" $CallbackBody '(?ms)\breturn\s+(?![''"]{2}\s*;)(?!null\s*;).+?;'
        }

        $SlugPattern = 'add_shortcode\s*\(\s*[''"]{0}[''"]' -f [regex]::Escape($Shortcode.Slug)
        $PairPattern = 'add_shortcode\s*\(\s*[''"]{0}[''"]\s*,\s*[''"]{1}[''"]\s*\)\s*;' -f [regex]::Escape($Shortcode.Slug), [regex]::Escape($Shortcode.Callback)
        Require-ContractCount $Failures $CaseName "$($Shortcode.Slug) shortcode registration" $PluginContent $SlugPattern 1
        Require-ContractCount $Failures $CaseName "$($Shortcode.Slug) shortcode callback pair" $PluginContent $PairPattern 1
    }

    # Both ACF callbacks must exist and retain their exact acf/init registrations.
    $AcfCallbacks = @('vg_register_eeat_acf_fields', 'vg_register_admin_first_acf_fields')
    Require-ContractCount $Failures $CaseName 'acf/init registrations' $PluginContent 'add_action\s*\(\s*[''"]acf/init[''"]' 2
    foreach ($Callback in $AcfCallbacks) {
        $CallbackBody = Get-PhpFunctionBody $PluginContent $Callback
        if ($CallbackBody -eq '') {
            Add-ContractFailure $Failures $CaseName "Missing ACF callback body: $Callback"
        } else {
            Require-ContractMatch $Failures $CaseName "$Callback field-group registration" $CallbackBody 'acf_add_local_field_group\s*\('
        }

        $HookPattern = 'add_action\s*\(\s*[''"]acf/init[''"]\s*,\s*[''"]{0}[''"]\s*\)\s*;' -f [regex]::Escape($Callback)
        Require-ContractCount $Failures $CaseName "$Callback acf/init hook pair" $PluginContent $HookPattern 1
    }

    # Admin-first callbacks and hook arity/priority are compatibility-sensitive.
    $AdminHooks = @(
        @{ Api = 'add_filter'; Hook = 'wp_insert_post_data'; Callback = 'vg_admin_first_protect_post_writes'; Priority = 1; Args = 4 }
        @{ Api = 'add_action'; Hook = 'wp_after_insert_post'; Callback = 'vg_admin_first_record_post_automation_hash'; Priority = 20; Args = 4 }
        @{ Api = 'add_filter'; Hook = 'update_post_metadata'; Callback = 'vg_admin_first_protect_post_meta'; Priority = 1; Args = 5 }
        @{ Api = 'add_action'; Hook = 'updated_post_meta'; Callback = 'vg_admin_first_record_protected_meta_automation_hash'; Priority = 20; Args = 4 }
        @{ Api = 'add_action'; Hook = 'added_post_meta'; Callback = 'vg_admin_first_record_protected_meta_automation_hash'; Priority = 20; Args = 4 }
    )

    foreach ($AdminHook in $AdminHooks) {
        $CallbackBody = Get-PhpFunctionBody $PluginContent $AdminHook.Callback
        if ($CallbackBody -eq '') {
            Add-ContractFailure $Failures $CaseName "Missing admin-first callback body: $($AdminHook.Callback)"
        }

        $HookOnlyPattern = '{0}\s*\(\s*[''"]{1}[''"]' -f $AdminHook.Api, [regex]::Escape($AdminHook.Hook)
        $PairPattern = '{0}\s*\(\s*[''"]{1}[''"]\s*,\s*[''"]{2}[''"]\s*,\s*{3}\s*,\s*{4}\s*\)\s*;' -f $AdminHook.Api, [regex]::Escape($AdminHook.Hook), [regex]::Escape($AdminHook.Callback), $AdminHook.Priority, $AdminHook.Args
        Require-ContractCount $Failures $CaseName "$($AdminHook.Hook) hook registration" $PluginContent $HookOnlyPattern 1
        Require-ContractCount $Failures $CaseName "$($AdminHook.Hook) callback/priority/args" $PluginContent $PairPattern 1
    }

    # XML-RPC, author enumeration, and anonymous REST user protections.
    $XmlrpcBody = Get-PhpFunctionBody $PluginContent 'vg_block_xmlrpc_request'
    if ($XmlrpcBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_block_xmlrpc_request body'
    } else {
        Require-ContractMatch $Failures $CaseName 'XMLRPC_REQUEST guard' $XmlrpcBody 'defined\s*\(\s*[''"]XMLRPC_REQUEST[''"]\s*\)\s*&&\s*XMLRPC_REQUEST'
        Require-ContractMatch $Failures $CaseName 'XML-RPC 403 response' $XmlrpcBody 'status_header\s*\(\s*403\s*\)\s*;'
        Require-ContractMatch $Failures $CaseName 'XML-RPC exit' $XmlrpcBody '\bexit\s*;'
    }
    Require-ContractCount $Failures $CaseName 'vg_block_xmlrpc_request init hook' $PluginContent 'add_action\s*\(\s*[''"]init[''"]\s*,\s*[''"]vg_block_xmlrpc_request[''"]\s*,\s*0\s*\)\s*;' 1
    Require-ContractCount $Failures $CaseName 'xmlrpc_enabled hook count' $PluginContent 'add_filter\s*\(\s*[''"]xmlrpc_enabled[''"]' 1
    Require-ContractCount $Failures $CaseName 'xmlrpc_enabled false callback' $PluginContent 'add_filter\s*\(\s*[''"]xmlrpc_enabled[''"]\s*,\s*[''"]__return_false[''"]\s*\)\s*;' 1

    $AuthorBody = Get-PhpFunctionBody $PluginContent 'vg_block_author_enumeration'
    if ($AuthorBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_block_author_enumeration body'
    } else {
        Require-ContractMatch $Failures $CaseName 'author archive detection' $AuthorBody 'is_author\s*\(\s*\)'
        Require-ContractMatch $Failures $CaseName 'author enumeration 404' $AuthorBody 'status_header\s*\(\s*404\s*\)\s*;'
        Require-ContractMatch $Failures $CaseName 'author enumeration exit' $AuthorBody '\bexit\s*;'
    }
    Require-ContractCount $Failures $CaseName 'author enumeration hook' $PluginContent 'add_action\s*\(\s*[''"]template_redirect[''"]\s*,\s*[''"]vg_block_author_enumeration[''"]\s*,\s*0\s*\)\s*;' 1

    $RestBody = Get-PhpFunctionBody $PluginContent 'vg_disable_public_user_rest_endpoints'
    if ($RestBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_disable_public_user_rest_endpoints body'
    } else {
        $RestProtectionContract = '(?ms)\Afunction\s+vg_disable_public_user_rest_endpoints\b.*?\{\s*if\s*\(\s*is_user_logged_in\s*\(\s*\)\s*\)\s*\{\s*return\s+\$endpoints\s*;\s*\}\s*unset\s*\(\s*\$endpoints\[[''"]/wp/v2/users[''"]\]\s*,\s*\$endpoints\[[''"]/wp/v2/users/\(\?P<id>\[\\d\]\+\)[''"]\]\s*\)\s*;'
        Require-ContractMatch $Failures $CaseName 'ordered anonymous REST protection' $RestBody $RestProtectionContract
    }
    Require-ContractCount $Failures $CaseName 'REST user endpoint filter' $PluginContent 'add_filter\s*\(\s*[''"]rest_endpoints[''"]\s*,\s*[''"]vg_disable_public_user_rest_endpoints[''"]\s*\)\s*;' 1

    $RobotsBody = Get-PhpFunctionBody $PluginContent 'vg_add_sitemap_to_robots'
    if ($RobotsBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_add_sitemap_to_robots body'
    } else {
        Require-ContractMatch $Failures $CaseName 'robots sitemap label' $RobotsBody 'Sitemap:'
        Require-ContractMatch $Failures $CaseName 'robots sitemap URL' $RobotsBody 'home_url\s*\(\s*[''"]/sitemap_index\.xml[''"]\s*\)'
        Require-ContractMatch $Failures $CaseName 'robots sitemap append return' $RobotsBody 'return\s+rtrim\s*\(\s*\$output\s*\)\s*\.\s*["'']\\nSitemap:\s["'']\s*\.\s*home_url'
    }
    Require-ContractCount $Failures $CaseName 'robots_txt hook count' $PluginContent 'add_filter\s*\(\s*[''"]robots_txt[''"]' 1
    Require-ContractCount $Failures $CaseName 'robots_txt callback/priority/args' $PluginContent 'add_filter\s*\(\s*[''"]robots_txt[''"]\s*,\s*[''"]vg_add_sitemap_to_robots[''"]\s*,\s*20\s*,\s*2\s*\)\s*;' 1

    # Pattern registration guards and registration must be connected and ordered in one body.
    $PatternBody = Get-PhpFunctionBody $PluginContent 'vg_register_pattern_category'
    if ($PatternBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_register_pattern_category body'
    } else {
        $OrderedPatternContract = '(?ms)\Afunction\s+vg_register_pattern_category\b.*?\{\s*if\s*\(\s*!\s*function_exists\s*\(\s*[''"]register_block_pattern_category[''"]\s*\)\s*\)\s*\{\s*return\s*;\s*\}\s*if\s*\(\s*class_exists\s*\(\s*[''"]WP_Block_Pattern_Categories_Registry[''"]\s*\)\s*\)\s*\{\s*\$registry\s*=\s*WP_Block_Pattern_Categories_Registry::get_instance\s*\(\s*\)\s*;\s*if\s*\(\s*\$registry->is_registered\s*\(\s*[''"]vietnamguide[''"]\s*\)\s*\)\s*\{\s*return\s*;\s*\}\s*\}\s*register_block_pattern_category\s*\(\s*[''"]vietnamguide[''"]'
        Require-ContractMatch $Failures $CaseName 'ordered pattern-category guards and registration' $PatternBody $OrderedPatternContract
        Require-ContractCount $Failures $CaseName 'pattern-category registration call in callback' $PatternBody 'register_block_pattern_category\s*\(' 1
    }
    Require-ContractCount $Failures $CaseName 'global pattern-category registration call' $PluginContent '\bregister_block_pattern_category\s*\(' 1
    Require-ContractCount $Failures $CaseName 'pattern-category init hook' $PluginContent 'add_action\s*\(\s*[''"]init[''"]\s*,\s*[''"]vg_register_pattern_category[''"]\s*\)\s*;' 1

    for ($Index = 0; $Index -lt $ThemePhpContents.Count; $Index++) {
        Require-ContractCount $Failures $CaseName "theme PHP file $Index pattern registration literal" $ThemePhpContents[$Index] 'register_block_pattern_category' 0
        Require-ContractCount $Failures $CaseName "theme PHP file $Index pattern registry literal" $ThemePhpContents[$Index] 'WP_Block_Pattern_Categories_Registry' 0
    }

    # Affiliate mutation is one scoped tag-processor path and returns its updated HTML.
    $AffiliateBody = Get-PhpFunctionBody $PluginContent 'vg_add_affiliate_link_attributes'
    if ($AffiliateBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_add_affiliate_link_attributes body'
    } else {
        $AffiliateFunctionContract = '(?ms)\Afunction\s+vg_add_affiliate_link_attributes\s*\(\s*string\s+\$content\s*\)\s*:\s*string\s*\{\s*if\s*\(\s*is_admin\s*\(\s*\)\s*\|\|\s*!\s*is_singular\s*\(\s*\)\s*\)\s*\{\s*return\s+\$content\s*;\s*\}\s*if\s*\(\s*!\s*class_exists\s*\(\s*[''"]WP_HTML_Tag_Processor[''"]\s*\)\s*\)\s*\{\s*return\s+\$content\s*;\s*\}\s*\$processor\s*=\s*new\s+WP_HTML_Tag_Processor\s*\(\s*\$content\s*\)\s*;\s*while\s*\(\s*\$processor->next_tag\s*\(\s*\[\s*[''"]tag_name[''"]\s*=>\s*[''"]A[''"]\s*,\s*[''"]class_name[''"]\s*=>\s*[''"]vg-affiliate-link[''"]\s*,?\s*\]\s*\)\s*\)\s*\{\s*\$rel_value\s*=\s*\$processor->get_attribute\s*\(\s*[''"]rel[''"]\s*\)\s*;\s*\$processor->set_attribute\s*\(\s*[''"]rel[''"]\s*,\s*vg_merge_affiliate_rel_tokens\s*\(\s*is_string\s*\(\s*\$rel_value\s*\)\s*\?\s*\$rel_value\s*:\s*[''"]{2}\s*\)\s*\)\s*;\s*\}\s*return\s+\$processor->get_updated_html\s*\(\s*\)\s*;\s*\}\z'
        Require-ContractMatch $Failures $CaseName 'complete affiliate function body' $AffiliateBody $AffiliateFunctionContract
        Require-ContractCount $Failures $CaseName 'affiliate tag processor construction' $AffiliateBody 'new\s+WP_HTML_Tag_Processor\s*\(' 1
        Require-ContractCount $Failures $CaseName 'affiliate next_tag path' $AffiliateBody '->next_tag\s*\(' 1
        Require-ContractCount $Failures $CaseName 'affiliate rel mutation path' $AffiliateBody '->set_attribute\s*\(' 1
        Require-ContractCount $Failures $CaseName 'affiliate updated HTML return' $AffiliateBody 'return\s+\$processor->get_updated_html\s*\(\s*\)\s*;' 1
        Require-ContractCount $Failures $CaseName 'affiliate regex mutation' $AffiliateBody '\bpreg_replace(?:_callback)?\s*\(' 0
        Require-ContractCount $Failures $CaseName 'affiliate while loop' $AffiliateBody '\bwhile\s*\(' 1
    }
    Require-ContractCount $Failures $CaseName 'the_content hook count' $PluginContent 'add_filter\s*\(\s*[''"]the_content[''"]' 1
    Require-ContractCount $Failures $CaseName 'affiliate the_content callback/priority' $PluginContent 'add_filter\s*\(\s*[''"]the_content[''"]\s*,\s*[''"]vg_add_affiliate_link_attributes[''"]\s*,\s*20\s*\)\s*;' 1

    $MergeBody = Get-PhpFunctionBody $PluginContent 'vg_merge_affiliate_rel_tokens'
    if ($MergeBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_merge_affiliate_rel_tokens body'
    } else {
        Require-ContractMatch $Failures $CaseName 'affiliate rel token parsing' $MergeBody 'preg_split\s*\(\s*[''"]/\\s\+/[''"]\s*,\s*trim\s*\(\s*\$rel_value\s*\)\s*\)'
        Require-ContractMatch $Failures $CaseName 'affiliate rel case-insensitive deduplication' $MergeBody 'strtolower\s*\(\s*\$token\s*\)'
        Require-ContractMatch $Failures $CaseName 'affiliate existing token preservation' $MergeBody '\$merged_tokens\[\]\s*=\s*\$token\s*;'
        $RequiredTokenContract = '(?ms)foreach\s*\(\s*\[[''"]sponsored[''"]\s*,\s*[''"]nofollow[''"]\]\s+as\s+\$required_token\s*\)\s*\{\s*if\s*\(\s*isset\s*\(\s*\$seen_tokens\[\$required_token\]\s*\)\s*\)\s*\{\s*continue\s*;\s*\}\s*\$merged_tokens\[\]\s*=\s*\$required_token\s*;\s*\$seen_tokens\[\$required_token\]\s*=\s*true\s*;\s*\}'
        Require-ContractMatch $Failures $CaseName 'ordered affiliate required-token addition' $MergeBody $RequiredTokenContract
        Require-ContractMatch $Failures $CaseName 'affiliate merged token return' $MergeBody 'return\s+implode\s*\(\s*[''"] [''"]\s*,\s*\$merged_tokens\s*\)\s*;'
    }

    # Image slugs occur once globally and only with the exact deployed dimensions.
    $ImageBody = Get-PhpFunctionBody $PluginContent 'vg_register_image_sizes'
    if ($ImageBody -eq '') {
        Add-ContractFailure $Failures $CaseName 'Missing vg_register_image_sizes body'
    } else {
        Require-ContractCount $Failures $CaseName 'image registrations in callback' $ImageBody 'add_image_size\s*\(' 3
    }

    $ImageSizes = @(
        @{ Slug = 'vg-hero'; Width = 1920; Height = 1080 }
        @{ Slug = 'vg-editorial-wide'; Width = 1440; Height = 900 }
        @{ Slug = 'vg-card'; Width = 720; Height = 540 }
    )
    foreach ($ImageSize in $ImageSizes) {
        Require-ContractCount $Failures $CaseName "$($ImageSize.Slug) slug count" $PluginContent ([regex]::Escape($ImageSize.Slug)) 1
        if ($ImageBody -ne '') {
            $ImagePattern = 'add_image_size\s*\(\s*[''"]{0}[''"]\s*,\s*{1}\s*,\s*{2}\s*,\s*true\s*\)\s*;' -f [regex]::Escape($ImageSize.Slug), $ImageSize.Width, $ImageSize.Height
            Require-ContractCount $Failures $CaseName "$($ImageSize.Slug) exact registration" $ImageBody $ImagePattern 1
        }
    }
    Require-ContractCount $Failures $CaseName 'image-size setup hook' $PluginContent 'add_action\s*\(\s*[''"]after_setup_theme[''"]\s*,\s*[''"]vg_register_image_sizes[''"]\s*\)\s*;' 1

    return $Failures.ToArray()
}

function Test-PluginMutationRejected {
    param(
        [System.Collections.Generic.List[string]]$VerifierFailures,
        [string]$Name,
        [string]$OriginalPluginContent,
        [string]$MutatedPluginContent,
        [string[]]$ThemePhpContents
    )

    if ($MutatedPluginContent -ceq $OriginalPluginContent) {
        [void]$VerifierFailures.Add("Mutation setup failed: $Name")
        return
    }

    $MutationFailures = @(Test-CorePluginContract $MutatedPluginContent $ThemePhpContents "mutation $Name")
    if ($MutationFailures.Count -eq 0) {
        [void]$VerifierFailures.Add("Mutation escaped contract: $Name")
        return
    }

    Write-Output "Mutation rejected: $Name ($($MutationFailures.Count) contract failure(s))"
}

function Test-ThemeMutationRejected {
    param(
        [System.Collections.Generic.List[string]]$VerifierFailures,
        [string]$Name,
        [string]$PluginContent,
        [string[]]$OriginalThemePhpContents,
        [string[]]$MutatedThemePhpContents
    )

    if (($MutatedThemePhpContents -join "`0") -ceq ($OriginalThemePhpContents -join "`0")) {
        [void]$VerifierFailures.Add("Mutation setup failed: $Name")
        return
    }

    $MutationFailures = @(Test-CorePluginContract $PluginContent $MutatedThemePhpContents "mutation $Name")
    if ($MutationFailures.Count -eq 0) {
        [void]$VerifierFailures.Add("Mutation escaped contract: $Name")
        return
    }

    Write-Output "Mutation rejected: $Name ($($MutationFailures.Count) contract failure(s))"
}

$PluginFullPath = Join-Path $RepoRoot $PluginPath
if (-not (Test-Path -LiteralPath $PluginFullPath -PathType Leaf)) {
    Write-Output "FAIL: Missing file: $PluginPath"
    exit 1
}

$ThemeDirectory = Join-Path $RepoRoot $ThemeDirectoryPath
if (-not (Test-Path -LiteralPath $ThemeDirectory -PathType Container)) {
    Write-Output "FAIL: Missing directory: $ThemeDirectoryPath"
    exit 1
}

$PluginContent = Get-Content -Raw -LiteralPath $PluginFullPath
$ThemePhpContents = @(
    Get-ChildItem -LiteralPath $ThemeDirectory -Recurse -File -Filter '*.php' |
        Sort-Object FullName |
        ForEach-Object { Get-Content -Raw -LiteralPath $_.FullName }
)

$Failures = [System.Collections.Generic.List[string]]::new()
foreach ($Failure in @(Test-CorePluginContract $PluginContent $ThemePhpContents 'repository source')) {
    [void]$Failures.Add($Failure)
}

if ($Failures.Count -eq 0) {
    Test-PluginMutationRejected $Failures 'inverted ABSPATH guard' $PluginContent (
        $PluginContent.Replace("if (! defined('ABSPATH')) {", "if (defined('ABSPATH')) {")
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'affiliate returns old content' $PluginContent (
        $PluginContent.Replace('return $processor->get_updated_html();', 'return $content;')
    ) $ThemePhpContents

    $BroadAffiliateReplacement = @'
    while ($processor->next_tag('A')) {
        $processor->set_attribute('rel', 'nofollow');
    }

    return $processor->get_updated_html();
'@
    Test-PluginMutationRejected $Failures 'second broad affiliate path' $PluginContent (
        $PluginContent.Replace('    return $processor->get_updated_html();', $BroadAffiliateReplacement)
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'duplicate wrong vg-hero image size' $PluginContent (
        $PluginContent.Replace(
            "    add_image_size('vg-hero', 1920, 1080, true);",
            "    add_image_size('vg-hero', 1920, 1080, true);`n    add_image_size('vg-hero', 100, 100, false);"
        )
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'wrong XML-RPC callback' $PluginContent (
        $PluginContent.Replace(
            "add_filter('xmlrpc_enabled', '__return_false');",
            "add_filter('xmlrpc_enabled', '__return_true');"
        )
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'inverted REST login condition' $PluginContent (
        $PluginContent.Replace(
            '    if (is_user_logged_in()) {',
            '    if (! is_user_logged_in()) {'
        )
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'inverted required-token condition' $PluginContent (
        $PluginContent.Replace(
            '        if (isset($seen_tokens[$required_token])) {',
            '        if (! isset($seen_tokens[$required_token])) {'
        )
    ) $ThemePhpContents

    $TopLevelPatternRegistration = @'
add_action('init', 'vg_register_pattern_category');
register_block_pattern_category(
    'vietnamguide',
    ['label' => __('VietnamGuide', 'vietnamguide-core')]
);
'@
    Test-PluginMutationRejected $Failures 'second top-level pattern registration' $PluginContent (
        $PluginContent.Replace(
            "add_action('init', 'vg_register_pattern_category');",
            $TopLevelPatternRegistration
        )
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'affiliate loop changed from while to if' $PluginContent (
        $PluginContent.Replace(
            '    while ($processor->next_tag([',
            '    if ($processor->next_tag(['
        )
    ) $ThemePhpContents

    $UpdateLogBody = Get-PhpFunctionBody $PluginContent 'vg_shortcode_update_log'
    $JunkUpdateLogBody = @'
function vg_shortcode_update_log(array $atts = []): string
{
    return 'junk';
}
'@
    Test-PluginMutationRejected $Failures 'update-log callback replaced with junk' $PluginContent (
        $PluginContent.Replace($UpdateLogBody, $JunkUpdateLogBody)
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'inverted XML-RPC request condition' $PluginContent (
        $PluginContent.Replace(
            "    if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {",
            "    if (! defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {"
        )
    ) $ThemePhpContents

    Test-PluginMutationRejected $Failures 'extra EEAT constant mapping' $PluginContent (
        $PluginContent.Replace(
            "    'hero_image_credit'      => 'vg_eeat_hero_image_credit',",
            "    'hero_image_credit'      => 'vg_eeat_hero_image_credit',`n    'extra_contract_key'       => 'vg_eeat_extra_contract_key',"
        )
    ) $ThemePhpContents

    $AffiliateLoop = @'
    while ($processor->next_tag([
        'tag_name' => 'A',
        'class_name' => 'vg-affiliate-link',
    ])) {
        $rel_value = $processor->get_attribute('rel');
        $processor->set_attribute(
            'rel',
            vg_merge_affiliate_rel_tokens(is_string($rel_value) ? $rel_value : '')
        );
    }
'@
    $EmptyAffiliateLoop = @'
    while ($processor->next_tag([
        'tag_name' => 'A',
        'class_name' => 'vg-affiliate-link',
    ])) {
    }

    $rel_value = $processor->get_attribute('rel');
    $processor->set_attribute(
        'rel',
        vg_merge_affiliate_rel_tokens(is_string($rel_value) ? $rel_value : '')
    );
'@
    Test-PluginMutationRejected $Failures 'affiliate mutations moved outside loop' $PluginContent (
        $PluginContent.Replace($AffiliateLoop, $EmptyAffiliateLoop)
    ) $ThemePhpContents

    $IndirectThemeRegistrationContents = @($ThemePhpContents)
    $IndirectThemeRegistrationContents[0] = $IndirectThemeRegistrationContents[0] + @'

call_user_func(
    'register_block_pattern_category',
    'vietnamguide',
    ['label' => 'VietnamGuide']
);
'@
    Test-ThemeMutationRejected $Failures 'indirect theme pattern registration' $PluginContent $ThemePhpContents $IndirectThemeRegistrationContents

    Test-PluginMutationRejected $Failures 'unconditional affiliate early return' $PluginContent (
        $PluginContent.Replace(
            '    $processor = new WP_HTML_Tag_Processor($content);',
            "    return `$content;`n`n    `$processor = new WP_HTML_Tag_Processor(`$content);"
        )
    ) $ThemePhpContents

    $RegistryThemeRegistrationContents = @($ThemePhpContents)
    $RegistryThemeRegistrationContents[0] = $RegistryThemeRegistrationContents[0] + @'

WP_Block_Pattern_Categories_Registry::get_instance()->register(
    'vietnamguide',
    ['label' => 'VietnamGuide']
);
'@
    Test-ThemeMutationRejected $Failures 'theme registry pattern registration' $PluginContent $ThemePhpContents $RegistryThemeRegistrationContents
}

if ($Failures.Count -gt 0) {
    foreach ($Failure in $Failures) {
        Write-Output "FAIL: $Failure"
    }

    exit 1
}

Write-Output 'VietnamGuide core mu-plugin checks and mutation tests passed.'
