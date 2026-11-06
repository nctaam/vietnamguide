<?php
define('ABSPATH',1);
$GLOBALS['audit_filters']=[]; $GLOBALS['audit_options']=[];
function add_action(...$args) {}
function add_shortcode(...$args) {}
function add_filter($tag,$callback,$priority=10,$n=1) {$GLOBALS['audit_filters'][$tag][]=$callback;}
function is_search(){return false;} function is_404(){return false;} function is_singular(){return true;}
function get_option($key,$default=false){return $GLOBALS['audit_options'][$key]??$default;}
function update_option($key,$val,$autoload=false){$GLOBALS['audit_options'][$key]=$val;return true;}
require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-routing.php';
require 'wordpress/wp-content/themes/vietnamguide-premium/inc/guide-seo.php';
$robotResult=[];
foreach($GLOBALS['audit_filters']['rank_math/frontend/robots'] as $cb){$robotResult=$cb(['index'=>'noindex','follow'=>'nofollow']);}
$original='<script type="application/ld+json">'.json_encode(['description'=>'&lt;/script&gt;&lt;script&gt;window.__auditExecuted=true&lt;/script&gt;']).'</script>';
$normalized=vg_normalize_serialized_json_ld_html($original);
for($i=0;$i<501;$i++){vg_process_newsletter_subscription_payload('audit'.$i.'@example.invalid');}
$subscribers=get_option('vg_newsletter_subscribers');
echo json_encode(['robots_after_noindex'=>$robotResult,'jsonld_before'=>$original,'jsonld_after'=>$normalized,'subscriber_count'=>count($subscribers),'first_subscriber_retained'=>isset($subscribers[hash('sha256','audit0@example.invalid')]),'last_subscriber_retained'=>isset($subscribers[hash('sha256','audit500@example.invalid')])]);
