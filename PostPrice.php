<?php

/**
 * Iran Postage Price Calculator
 * Simple wrapper for parcelprice.post.ir
 * Based on MadelineProto
 * https://github.com/nimah79/Iran-Postage-Price-Calculator
 * By NimaH79
 * http://nimah79.ir.
 */
class PostPrice
{
    private static $cookie_file = __DIR__.'/post_cookies.txt';
    private static $url = 'http://parcelprice.post.ir/default.aspx';
    private static $form_values = [
        '__EVENTTARGET',
        '__EVENTARGUMENT',
        '__LASTFOCUS',
        '__VIEWSTATE',
        '__EVENTVALIDATION',
    ];

    private static $default_values = [
        'service_type' => [
            'vizhe'     => 'rdoSPS',
            'pishtaz'   => 'rdoEMS',
            'sefareshi' => 'rdoEXP',
        ],
        'product_type' => [
            'pakat'  => 'rdoLetter',
            'baste'  => 'rdoPackage',
            'amanat' => 'rdoConsign',
            'kise_m' => 'rdoMPackage',
            'matbou' => 'rdoNewsLetter',
        ],
        'weight'                => 'txtWeight',
        'special_delivery_time' => [
            'today_22'    => 'rdoSPS22',
            'tomorrow_10' => 'rdoSPS10',
            'tomorrow_12' => 'rdoSPS12',
        ],
        'destination_type' => [
            'shahri'      => 'rdoCity',
            'beyn_shahri' => 'rdoBetweenCity',
        ],
        'send_method'          => 'cboSendMethod',
        'origin_state'         => 'cboFromState',
        'origin_city'          => 'cboFromCity',
        'destination_country'  => 'cboToCountry',
        'destination_state'    => 'cboToState',
        'destination_city'     => 'cboToCity',
        'sender_postal_code'   => 'chkSenderPCode',
        'receiver_postal_code' => 'chkReceiverPCode',
        'insurance_type'       => 'cboInsurType',
        'insurance_amount'     => 'txtInsurAmount',
        'extra'                => [
            'agahi'              => 'chkTwoReceipt',
            'shekastani'         => 'chkIsBreakable',
            'express'            => 'chkExpress',
            'agahi_electronic'   => 'chkElecTwoReceipt',
            'mayeat'             => 'chkIsLiquid',
            'amanat_anbouh'      => 'chkBulkConsign',
            'cot'                => 'chkIsCOT',
            'shenase_electronic' => 'chkElectronicDue',
            'sms'                => 'chkSMS',
        ],
        'post_devilery_time' => 'DropDown_extra',
    ];

    private static $form_keys = [
        'service_type' => 'g1',
        'product_type' => 'g2',
    ];

    private static $obligated_form_keys = [
        'service_type',
        'product_type',
        'weight',
        'insurance_type',
        'post_devilery_time',
    ];

    public static function calculatePrice($input)
    {
        foreach (self::$obligated_form_keys as $key) {
            if (!isset($input[$key])) {
                return false;
            }
        }
        self::deleteCookieFile();
        $ch = curl_init(self::$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEFILE, self::$cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEJAR, self::$cookie_file);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        curl_setopt($ch, CURLOPT_POST, true);
        $postfields = [];
        foreach (['service_type', 'product_type'] as $key) {
            $postfields[self::$form_keys[$key]] = str_replace(array_keys(self::$default_values[$key]), array_values(self::$default_values[$key]), $input[$key]);
            if ($key == 'service_type' && $input['service_type'] != 'vizhe') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
                $response = curl_exec($ch);
            }
        }
        $postfields[self::$default_values['weight']] = (string) $input['weight'];
        if (isset($input['special_delivery_time']) && $input['service_type'] == 'vizhe') {
            $postfields['g4'] = str_replace(array_keys(self::$default_values['special_delivery_time']), array_values(self::$default_values['special_delivery_time']), $input['special_delivery_time']);
        }
        $postfields['btnnext'] = 'مرحله بعد';
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
        $response = curl_exec($ch);
        $postfields = [];
        foreach (['sender_postal_code', 'receiver_postal_code'] as $key) {
            if (isset($input[$key])) {
                $postfields[self::$default_values[$key]] = 1;
            }
        }
        if (isset($input['destination_state'], $input['destination_city'])) {
            $postfields['g3'] = 'rdoBetweenCity';
            curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
            $response = curl_exec($ch);
            foreach (['origin_state', 'origin_city', 'destination_state', 'destination_city'] as $key) {
                if (!isset($input[$key])) {
                    curl_close($ch);

                    return false;
                }
                $postfields[self::$default_values[$key]] = (string) $input[$key];
                if ($key == 'destination_city') {
                    $postfields['btnnext'] = 'مرحله بعد';
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
                $response = curl_exec($ch);
            }
        } elseif (isset($input['send_method'])) {
            $postfields['g3'] = 'rdoForeign';
            curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
            $response = curl_exec($ch);
            if (!isset($input['destination_country'])) {
                curl_close($ch);

                return false;
            }
            $postfields['btnnext'] = 'مرحله بعد';
            curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
            $response = curl_exec($ch);
        } else {
            $postfields['g3'] = 'rdoCity';
            foreach (['origin_state', 'origin_city'] as $key) {
                if (!isset($input[$key])) {
                    curl_close($ch);

                    return false;
                }
                $postfields[self::$default_values[$key]] = (string) $input[$key];
                if ($key == 'origin_city') {
                    $postfields['btnnext'] = 'مرحله بعد';
                }
                curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
                $response = curl_exec($ch);
            }
        }
        $postfields = [];
        foreach (['insurance_type', 'post_devilery_time'] as $key) {
            $postfields[self::$default_values[$key]] = (string) $input[$key];
            if ($key == 'insurance_type' && $input['insurance_type'] != 1) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
                $response = curl_exec($ch);
            }
        }
        if ($input['insurance_type'] == 1 && isset($input['insurance_amount'])) {
            $postfields[self::$default_values['insurance_amount']] = (string) $input['insurance_amount'];
        }
        foreach (self::$default_values['extra'] as $key => $value) {
            if (isset($input[$key])) {
                $postfields[self::$default_values['extra'][$key]] = 'on';
            }
        }
        $postfields['btnnext'] = 'مرحله بعد';
        curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge($postfields, self::getFormValues($response)));
        $response = curl_exec($ch);
        curl_close($ch);
        self::deleteCookieFile();
        $result = [];
        if (!preg_match('/size="4">(.*?)</', $response, $total)) {
            return false;
        }
        $total = str_replace(',', '', $total[1]) / 10;
        $result['total'] = $total;
        preg_match_all('/<td.*?>\s+<span id=".*?>(<font.*?>)?(.*?):(<\/font>)?<\/span>\s+<\/td>\s+<td style.*?>\s+<input name=".*?" type="text" value="(.*?)" readonly="readonly" id=".*?" \/>\s+ریال\s+</', $response, $prices);
        for ($i = 0; $i < count($prices[4]); $i++) {
            $result[$prices[2][$i]] = str_replace(',', '', $prices[4][$i]) / 10;
        }

        return $result;
    }

    private static function getFormValues($page)
    {
        $result = [];
        foreach (self::$form_values as $key) {
            if (preg_match('/name="'.$key.'" id="'.$key.'" value="(.*?)"/', $page, $value)) {
                $result[$key] = $value[1];
            }
        }

        return $result;
    }

    private static function deleteCookieFile()
    {
        if (is_file(self::$cookie_file)) {
            unlink(self::$cookie_file);
        }
    }
}
