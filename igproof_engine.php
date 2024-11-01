<?php


function igproof_getTemplate()
{

    // Getting the setting options of IGProof plugin
    $setting_options = get_option('igProof_api_settings', igproof_get_default_settings());

    // Checking if the content showing is enabled or disabled
    $is_disable_verifiedBy = ($setting_options['disable_verified_by'] == 'yes') ? '' : ($setting_options['display_order_time'] == 'yes'?'| <a href="'.esc_url('https://www.ilmigo.com/wooproof/').'" rel="nofollow" target="_blank" class="ig-proof-verified-by"><span>Verified by Ilmigo</span></a>':'<a href="'.esc_url('https://www.ilmigo.com/wooproof/').'" rel="nofollow" target="_blank" class="ig-proof-verified-by"><span>Verified by Ilmigo</span></a>');
    $is_display_orderTime = ($setting_options['display_order_time'] == 'no') ? '' : '  <span class="ig-proof-time">[when]</span>';


    $template = '<li>
    <div class="ig-proof-wrapper">
      <div class="ig-proof">
        <div class="ig-proof-img" style="background-image: url([background_image])">
        </div>
        <div class="ig-proof-content">
          <div class="ig-proof-who">[who]</div>
          <div class="ig-proof-default">[what]</div>
          
          <div class="ig-proof-final">
          
          ' . $is_display_orderTime . '
          
          ' . $is_disable_verifiedBy . '
          
          </div>

        </div>
      </div>
      <div class="ig-proof-close">&times</div>
     
      <a href="[product_url]" class="ig-proof-anchor" onclick=wooproof_product_counter("[product]")></a>
    </div>
  </li>';

    return $template;
}



function igproof_get_order_items()
{
    $setting_options = get_option('igProof_api_settings', igproof_get_default_settings());
    $numberPosts = empty($setting_options['total_orders_display']) ? 10 : $setting_options['total_orders_display'];
    $min_product_price = floatval(empty($setting_options['min_product_price']) ? 15 : $setting_options['min_product_price']);

    $all_orders = wc_get_orders(array(
        'numberposts' => 5,
        'status' => array('wc-completed', 'wc-processing'),
        'orderby' => 'id',
        'order' => 'DESC'
    ));
    
    if (!$all_orders) return;

    $count = 1;
    $proofItems = "";
    $product = NULL;
    
    foreach ($all_orders as $order) {
        if ($count <= $numberPosts) {
            if ($order->get_subtotal()) {
                $order_items = $order->get_items();
                foreach ($order_items as $item_id => $order_item){
                    $product = $order_item->get_product();
                    if(floatval($product->get_price()) >= $min_product_price){
                        
                        
                        
                        $template = igproof_getTemplate();
                        $order = wc_get_order($order);
                        $user_fname = $order->get_billing_first_name();

                        $who = ucfirst($user_fname);
                        if ($order->get_billing_city() != "" && $order->get_billing_state() != "") {
                            $who .= " from " . ucfirst($order->get_billing_city()) . ", {$order->get_billing_state()}";
                        } elseif ($order->get_billing_city() == "" && $order->get_billing_state() != "") {
                            $who .= " from {$order->get_billing_state()}, " . igproof_get_country_name_by_code($order->get_billing_country());
                        } else {
                            $who .= " from " . igproof_get_country_name_by_code($order->get_billing_country());
                        }

                        $template = str_replace("[who]", $who, $template);

                        //Following variable will templace [what] in template
                        $what = "Purchased <span>{$product->get_name()}</span>";

                        $template = str_replace("[what]", $what, $template);

                        //Following variable will templace [background_image] in template
                        //$product_id = $product->get_type() == 'variation' ? $product->get_parent_id() : $product->get_id() ;
                        $image_id = $product->get_image_id();
                        $image = wp_get_attachment_image_src($image_id, 'thumbnail');
                        if($image){
                            $template = str_replace("[background_image]", $image[0], $template);
                        }else{
                            $template = str_replace("[background_image]", plugin_dir_url( __FILE__ ).'assets/images/product-placeholder-gray.png', $template);
                        }

                        //Following variable will templace [when] in template
                        $when = igproof_time_elapsed_string($order->get_date_created());
                        $template = str_replace("[when]", $when, $template);


                        //Following will templace [product_url] in template
                        $template = str_replace("[product_url]", get_permalink($product->get_id()), $template);
                        $template = str_replace("[product]", ($product->get_slug()), $template);

                        $proofItems .= $template;
                        
                        $count++;
                        break;
                    }
                }
            }
        }
    }

    return $proofItems;
}


function igproof_time_elapsed_string($datetime, $full = false)
{
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;

    $string = array(
        'y' => 'year',
        'm' => 'month',
        'w' => 'week',
        'd' => 'day',
        'h' => 'hour',
        'i' => 'minute',
        's' => 'second',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
}


function igproof_get_country_name_by_code($country_code)
{
    $countries = array(
        'AX' => 'Åland Islands',
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua and Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'PW' => 'Belau',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BQ' => 'Bonaire, Saint Eustatius and Saba',
        'BA' => 'Bosnia and Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'VG' => 'British Virgin Islands',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Colombia',
        'KM' => 'Comoros',
        'CG' => 'Congo (Brazzaville)',
        'CD' => 'Congo (Kinshasa)',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'HR' => 'Croatia',
        'CU' => 'Cuba',
        'CW' => 'CuraÇao',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'FK' => 'Falkland Islands',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'GF' => 'French Guiana',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GT' => 'Guatemala',
        'GG' => 'Guernsey',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard Island and McDonald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IM' => 'Isle of Man',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'CI' => 'Ivory Coast',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JE' => 'Jersey',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macao S.A.R., China',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PS' => 'Palestinian Territory',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'QA' => 'Qatar',
        'IE' => 'Republic of Ireland',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'ST' => 'São Tomé and Príncipe',
        'BL' => 'Saint Barthélemy',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts and Nevis',
        'LC' => 'Saint Lucia',
        'SX' => 'Saint Martin (Dutch part)',
        'MF' => 'Saint Martin (French part)',
        'PM' => 'Saint Pierre and Miquelon',
        'VC' => 'Saint Vincent and the Grenadines',
        'SM' => 'San Marino',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'RS' => 'Serbia',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia/Sandwich Islands',
        'KR' => 'South Korea',
        'SS' => 'South Sudan',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard and Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TL' => 'Timor-Leste',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad and Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks and Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'GB' => 'United Kingdom (UK)',
        'US' => 'United States (US)',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'WF' => 'Wallis and Futuna',
        'EH' => 'Western Sahara',
        'WS' => 'Western Samoa',
        'YE' => 'Yemen',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe',
    );
    return (isset($countries[$country_code]) ? $countries[$country_code] : false);
}