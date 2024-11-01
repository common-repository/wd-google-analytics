<?php

class GAWD_google {

  private $access_token = "";
  private $refresh_token = "";
  private $user_data = array();
  private $google_client = null;
  private $analytics_member = null;
  private $exception_handler;
  private $profile_info = null;

  private $gawd_last_viewed_profile_data = array();

  public function __construct($access_token = "", $refresh_token = "", $gawd_ajax_instance = ""){
    $this->gawd_last_viewed_profile_data = get_option("gawd_last_viewed_profile");

    $this->access_token = $access_token;
    $this->refresh_token = $refresh_token;

    $this->user_data = get_option('gawd_user_data');
    $this->exception_handler = GAWD_exception_handler::get_instance();
    $this->exception_handler->set_gawd_ajax_instance($gawd_ajax_instance);

    try {
      $this->set_google_client();
      $this->analytics_member = new Google_Service_Analytics($this->google_client);
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', '__construct');
    }

  }

  /**
   * @return null|array profiles array or null
   * */
  public function get_profiles(){
    $profiles_light = array();
    try {

      if($this->analytics_member && $this->analytics_member->management_webproperties) {

        $web_properties = $this->analytics_member->management_webproperties->listManagementWebproperties('~all')->getItems();
        $profiles = $this->analytics_member->management_profiles->listManagementProfiles('~all', '~all')->getItems();
        $profiles_count = count($profiles);
        $web_properties_count = count($web_properties);

        for($i = 0; $i < $web_properties_count; $i++) {
          for($j = 0; $j < $profiles_count; $j++) {
            if($web_properties[$i]['id'] == $profiles[$j]['webPropertyId']) {

              $profiles_light[$web_properties[$i]['name']][] = array(
                'id' => $profiles[$j]['id'],
                'name' => $profiles[$j]['name'],
                'webPropertyId' => $profiles[$j]['webPropertyId'],
                'websiteUrl' => $profiles[$j]['websiteUrl'],
                'accountId' => $profiles[$j]['accountId']
              );

            }
          }
        }
      } else {
        GAWD_logs::add('get_profiles_warning', 'Something went wrong');
        return null;
      }

      return $profiles_light;
    } catch(Google_Service_Exception $e) {
      $this->exception_handler->add($e, 'Google_Service_Exception', 'get_profiles');
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_profiles');
    }

    return null;
  }

  /**
   * @return null|array profiles array or null
   * */
  public function get_management_accounts(){
    try {
      $accounts = $this->analytics_member->management_accounts->listManagementAccounts()->getItems();
      return (array)$accounts;
    } catch(Google_Service_Exception $e) {
      $this->exception_handler->add($e, 'Google_Service_Exception', 'get_management_accounts');
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_management_accounts');
    }
    return null;
  }

  /**
   * @return null|array profiles array or null
   * */
  public function get_properties(){

    try {
      $web_properties = $this->analytics_member->management_webproperties->listManagementWebproperties('~all')->getItems();
    } catch(Google_Service_Exception $e) {
      $this->exception_handler->add($e, 'Google_Service_Exception', 'get_properties');
      return null;
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_properties');
      return null;
    }

    return $web_properties;
  }

  /**
   * @return null|array null or added profile
   * */
  public function add_property($accountId, $name){

    $analytics = $this->analytics_member;
    $websiteUrl = get_site_url();
    try {
      $property = new Google_Service_Analytics_Webproperty();
      $property->setName($name);
      $property->setWebsiteUrl($websiteUrl);
      $analytics->management_webproperties->insert($accountId, $property);
    } catch(apiServiceException $e) {
      $this->exception_handler->add($e, 'apiServiceException', 'add_property');
      return null;
    } catch(apiException $e) {
      $this->exception_handler->add($e, 'apiException', 'add_property');
      return null;
    } catch(Google_Service_Exception $e) {
      $this->exception_handler->add($e, 'Google_Service_Exception', 'add_property');
      return null;
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'add_property');
      return null;
    }

    $web_properties = $this->analytics_member->management_webproperties->listManagementWebproperties($accountId)->getItems();
    foreach($web_properties as $web_property) {
      if($web_property['name'] == $name) {

        $profile = new Google_Service_Analytics_Profile();
        $profile->setName('All Web Site Data');

        try {
          $analytics->management_profiles->insert($accountId, $web_property['id'], $profile);
        } catch(apiServiceException $e) {
          $this->exception_handler->add($e, 'apiServiceException', 'add_property_2');
          return null;
        } catch(apiException $e) {
          $this->exception_handler->add($e, 'apiException', 'add_property_2');
          return null;
        } catch(Exception $e) {
          $this->exception_handler->add($e, 'Exception', 'add_property_2');
          return null;
        }

        $current_profiles = $this->analytics_member->management_profiles->listManagementProfiles($accountId, $web_property['id'])->getItems();
        try {
          $property = new Google_Service_Analytics_Webproperty();
          $property->setName($name);
          $property->setWebsiteUrl($websiteUrl);
          $property->setDefaultProfileId($current_profiles[0]['id']);
          $analytics->management_webproperties->update($accountId, $web_property['id'], $property);
        } catch(apiServiceException $e) {
          $this->exception_handler->add($e, 'apiServiceException', 'add_property_3');
          return null;
        } catch(apiException $e) {
          $this->exception_handler->add($e, 'apiException', 'add_property_3');
          return null;
        } catch(Google_Service_Exception $e) {
          $this->exception_handler->add($e, 'Google_Service_Exception', 'add_property_3');
          return null;
        } catch(Exception $e) {
          $this->exception_handler->add($e, 'Exception', 'add_property_3');
          return null;
        }
      }
    }

    return $current_profiles;
  }

  /**
   * @return boolean
   * */
  public function delete_management_filter($remove_filter){
    $accountId = $this->get_profile_accountId();

    try {
      $this->analytics_member->delete($accountId, $remove_filter);
    } catch(apiServiceException $e) {
      $this->exception_handler->add($e, 'apiServiceException', 'delete_management_filter');
      return false;
    } catch(apiException $e) {
      $this->exception_handler->add($e, 'apiException', 'delete_management_filter');
      return false;
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'delete_management_filter');
      return false;
    }
    return true;
  }

  /**
   * @return boolean
   * */
  public function add_management_filter($name, $type, $value){

    $accountId = $this->get_profile_accountId();
    $profileId = $this->get_profile_id();
    $webPropertyId = $this->get_profile_webPropertyId();

    $analytics = $this->analytics_member;
    $condition = ($type == 'GEO_IP_ADDRESS') ? 'EQUAL' : 'MATCHES';

    try {
      // Construct the filter expression object.
      $details = new Google_Service_Analytics_FilterExpression();
      $details->setField($type);
      $details->setMatchType($type);
      $details->setExpressionValue($value);
      $details->setCaseSensitive(false);
      // Construct the filter and set the details.
      $filter = new Google_Service_Analytics_Filter();
      $filter->setName($name);
      $filter->setType("EXCLUDE");
      $filter->setExcludeDetails($details);

      $insertedFilter = $analytics->management_filters->insert($accountId, $filter);
      $analyticsFilterRef = new Google_Service_Analytics_FilterRef();
      $analyticsFilterRef->setId($insertedFilter->id);
      $filterData = new Google_Service_Analytics_ProfileFilterLink();
      $filterData->setFilterRef($analyticsFilterRef);
      // Add view to inserted filter
      $res = $analytics->management_profileFilterLinks->insert($accountId, $webPropertyId, $profileId, $filterData);

    } catch(apiServiceException $e) {
      $this->exception_handler->add($e, 'apiServiceException', 'add_management_filter');
      return false;
    } catch(apiException $e) {
      $this->exception_handler->add($e, 'apiException', 'add_management_filter');
      return false;
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'add_management_filter');
      return false;
    }
    return true;
  }

  /**
   * @return boolean
   * */
  public function add_custom_dimension($name, $id){

    $custom_dimension = new Google_Service_Analytics_CustomDimension();
    $custom_dimension->setId($id);
    $custom_dimension->setActive(true);
    $custom_dimension->setScope('Hit');
    $custom_dimension->setName($name);

    $accountId = $this->get_default_account_id();
    $webPropertyId = $this->get_default_web_property_id();
    $analytics = $this->analytics_member;

    try {
      $analytics->management_customDimensions->insert($accountId, $webPropertyId, $custom_dimension);
    } catch(apiServiceException $e) {
      $this->exception_handler->add($e, 'apiServiceException', 'add_custom_dimension');
      return false;
    } catch(apiException $e) {
      $this->exception_handler->add($e, 'apiException', 'add_custom_dimension');
      return false;
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'add_custom_dimension');
      return false;
    }
    return true;
  }

  public function get_custom_dimensions($web_property_id, $account_id){

    try {
      $all_dimensions = $this->analytics_member->management_customDimensions->listManagementCustomDimensions($account_id, $web_property_id)->getItems();
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_custom_dimensions');
      return null;
    }

    if(0 == sizeof($all_dimensions)) {
      return array();
    }
    $dimensions_light = array();
    foreach($all_dimensions as $dimension) {
      $dimensions_light[] = array(
        'name' => $dimension['name'],
        'id' => $dimension['id']
      );
    }

    return $dimensions_light;
  }

  public function get_real_time_data(){

    $analytics = $this->analytics_member;
    $profileId = $this->get_profile_id(false);
    $metrics = 'rt:activeUsers';
    $dimensions = 'rt:pagePath,rt:source,rt:keyword,rt:trafficType,rt:country,rt:pageTitle,rt:deviceCategory';
    $managequota = 'u' . get_current_user_id() . 's' . get_current_blog_id();

    try {
      $data = $analytics->data_realtime->get('ga:' . $profileId, $metrics, array('dimensions' => $dimensions, 'quotaUser' => $managequota . 'p' . $profileId));
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_real_time_data');
      return null;
    }

    if($data->getRows() != '') {
      $i = 0;
      $gawd_data = $data;
      foreach($data->getRows() as $row) {
        $gawd_data[$i] = $row;
        $i++;
      }
      return $gawd_data;
    } else {
      return 0;
    }
  }

  /*PRIVATE METHODS*/
  private function set_google_client(){

    $this->google_client = new Google_Client();
    $this->google_client->setAccessToken($this->access_token);

    if($this->google_client->isAccessTokenExpired()) {
      $credentials = GAWD_helper::get_project_credentials();
      $this->google_client->setClientId($credentials['project_id']);
      $this->google_client->setClientSecret($credentials['project_secret']);
      $this->google_client->setRedirectUri(GAWD::get_instance()->redirect_uri);
      $this->google_client->refreshToken($this->refresh_token);
    }

  }

  /*STATIC METHODS*/
  public static function get_authentication_url(){
    $client = new Google_Client();
    $credentials = GAWD_helper::get_project_credentials();

    $client->setClientId($credentials['project_id']);
    $client->setClientSecret($credentials['project_secret']);
    $client->setRedirectUri(GAWD::get_instance()->redirect_uri);
    $client->addScope(array(Google_Service_Analytics::ANALYTICS_EDIT, Google_Service_Analytics::ANALYTICS_READONLY));
    $client->setApprovalPrompt('force');
    $client->setAccessType('offline');
    return $client->createAuthUrl();
  }

  /**
   * @param $token string
   * @return array with access token and refresh token or empty array on fail
   * */
  public static function authenticate($token){
    $exception_handler = GAWD_exception_handler::get_instance();
    $credentials = GAWD_helper::get_project_credentials();

    $client = new Google_Client();

    $client->setClientId($credentials['project_id']);
    $client->setClientSecret($credentials['project_secret']);
    $client->setRedirectUri(GAWD::get_instance()->redirect_uri);

    try {
      $client->fetchAccessTokenWithAuthCode($token);

      if($client->isAccessTokenExpired()) {
        GAWD_logs::add("authenticate", 'Access Token is Expired.');
        return array();
      }

      $tokens = array(
        'access_token' => $client->getAccessToken(),
        'refresh_token' => $client->getRefreshToken()
      );

      return $tokens;

    } catch(Google_Service_Exception $e) {
      $exception_handler->add($e, 'Google_Service_Exception', 'authenticate');
    } catch(Exception $e) {
      $exception_handler->add($e, 'Exception', 'authenticate');
    }

    return array();
  }

  public function add_management_goal($gawd_goal_profile, $goal_id, $gawd_goal_type, $gawd_goal_name, $gawd_goal_comparison = "GREATER_THAN", $gawd_goal_value, $url_case_sensitve = 'false'){

    $goal = new Google_Service_Analytics_Goal();
    $goal->setId($goal_id); //ID
    $goal->setActive(True); //ACTIVE/INACTIVE
    $goal->setType($gawd_goal_type); //URL_DESTINATION, VISIT_TIME_ON_SITE, VISIT_NUM_PAGES, AND EVENT
    $goal->setName($gawd_goal_name); //NAME
    // Construct the time on site details.
    if($gawd_goal_type == 'VISIT_TIME_ON_SITE') {
      $details = new Google_Service_Analytics_GoalVisitTimeOnSiteDetails();
      $details->setComparisonType($gawd_goal_comparison); //VISIT_TIME_ON_SITE -------- LESS_THAN/ GREATER_THAN------
      $details->setComparisonValue($gawd_goal_value);
      $goal->setVisitTimeOnSiteDetails($details);
    } elseif($gawd_goal_type == 'URL_DESTINATION') {
      if($url_case_sensitve != '') {
        $url_case_sensitve = true;
      }
      $details = new Google_Service_Analytics_GoalUrlDestinationDetails();
      $details->setCaseSensitive($url_case_sensitve);
      $details->setFirstStepRequired('false');
      $details->setMatchType($gawd_goal_comparison);
      $details->setUrl($gawd_goal_value);
      $goal->setUrlDestinationDetails($details);
    } elseif($gawd_goal_type == 'VISIT_NUM_PAGES') {
      $details = new Google_Service_Analytics_GoalVisitNumPagesDetails();
      $details->setComparisonType($gawd_goal_comparison); //VISIT_TIME_ON_SITE -------- LESS_THAN/ GREATER_THAN------
      $details->setComparisonValue($gawd_goal_value);
      $goal->setVisitNumPagesDetails($details);
    } elseif($gawd_goal_type == 'EVENT') {
      /*     $details = new Google_Service_Analytics_GoalEventDetails();
        $details = new Google_Service_Analytics_GoalEventDetailsEventConditions();
        $detailssetComparisonType
        //$details->setEventConditions($gawd_goal_comparison);//VISIT_TIME_ON_SITE -------- LESS_THAN/ GREATER_THAN------
        //$details->setUseEventValue($gawd_goal_value); */
      // $goal->setEventDetails($details);
    }

    //Set the time on site details.


    $accountId = $this->get_default_account_id();
    $webPropertyId = $this->get_default_web_property_id();
    $profileId = $gawd_goal_profile;
    $analytics = $this->analytics_member;
    try {
      $analytics->management_goals->insert($accountId, $webPropertyId, $profileId, $goal);
    } catch(apiServiceException $e) {
      $this->exception_handler->add($e, 'apiServiceException', 'add_management_goal');
      return false;
    } catch(apiException $e) {
      $this->exception_handler->add($e, 'apiException', 'add_management_goal');
      return false;
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'apiException', 'add_management_goal');
      return false;
    }
    return true;
  }

  public function get_management_goals($profile=null){

    if($profile === null){
      $last_viewed_profile = GAWD_helper::get_last_viewed_profile();
      $profileId = $last_viewed_profile["profile_id"];
      $accountId = $last_viewed_profile['account_id'];
      $webPropertyId = $last_viewed_profile["web_property_id"];
    }else{
      $profileId = $profile["id"];
      $accountId = $profile['accountId'];
      $webPropertyId = $profile["webPropertyId"];
    }

    $goals = array();
    try {
      $goals = $this->analytics_member->management_goals->listManagementGoals($accountId, $webPropertyId, $profileId)->getItems();
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_management_goals');
      return null;
    }
    $goals_light = array();
    if(0 == sizeof($goals)) {
      return $goals_light;
    } else {
      foreach($goals as $goal) {
        $goals_light[] = array(
          'name' => $goal['name'],
          'id' => $goal['id']
        );
      }
      return $goals_light;
    }
  }

  public function get_goals_list($webPropertyId, $accountId) {
    try {
      return  $this->analytics_member->management_goals->listManagementGoals($accountId, $webPropertyId, '~all')->getItems();
    } catch (Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_goals_list');
      return null;
    }
  }


  public function get_goal_data($dimension, $start_date, $end_date, $timezone, $same_dimension){
    $goals = $this->get_management_goals();
    if(!empty($goals)) {
      $analytics = $this->analytics_member;
      $profileId = $this->get_profile_id(false);
      $metric = array();
      $all_metric = '';
      $counter = 1;
      $metrics = array();
      foreach($goals as $goal) {
        $all_metric .= 'ga:goal' . $goal['id'] . 'Completions,';
        if($counter <= 10) {
          $metrics[0][] = 'ga:goal' . $goal['id'] . 'Completions';
        } else {
          $metrics[1][] = 'ga:goal' . $goal['id'] . 'Completions';
        }
        $counter++;
      }
      $rows = array();
      foreach($metrics as $metric) {
        $metric = implode(',', $metric);
        $results = $analytics->data_ga->get(
          'ga:' . $profileId, $start_date, $end_date, $metric, array(
            'dimensions' => 'ga:' . $dimension,
            'sort' => 'ga:' . $dimension,
          )
        );
        $temp_rows = $results->getRows();
        if(empty($temp_rows)) {
          continue;
        }
        foreach($temp_rows as $key => $value) {
          if(!isset($rows[$key])) {
            $rows[$key] = $value;
          } else {
            unset($value[0]);
            $rows[$key] = array_merge($rows[$key], $value);
          }
        }
      }
      $all_metric = explode(',', $all_metric);
      if($rows) {
        $j = 0;
        $data_sum = array();
        foreach($rows as $row) {
          if($dimension == 'date') {
            $row[0] = date('Y-m-d', strtotime($row[0]));
          }
          $data[$j] = array(
            preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension)))) => $row[0]
          );
          $data[$j]['No'] = floatval($j + 1);
          for($i = 0; $i < count($goals); $i++) {
            $data[$j][preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $goals[$i]['name']))))] = floatval($row[$i + 1]);
            if(isset($data_sum[preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $goals[$i]['name']))))])) {
              $data_sum[preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $goals[$i]['name']))))] += floatval($row[$i + 1]);
            } else {
              if(substr($all_metric[$i], 3) != 'percentNewSessions' && substr($all_metric[$i], 3) != 'bounceRate') {
                $data_sum[preg_replace('!\s+!', ' ', trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $goals[$i]['name']))))] = floatval($row[$i + 1]);
              }
            }
          }
          $j++;
        }
        $expiration = strtotime(date("Y-m-d 23:59:59")) - strtotime(gmdate("Y-m-d H:i:s") . '+' . $timezone . ' hours');
        if(isset($same_dimension) && $same_dimension != null) {
          $dimension = $same_dimension;
        }
        $result = $data;
        if($data_sum != '') {
          $result = array('data_sum' => $data_sum, 'chart_data' => $data);
        }
        return $result;
      } else {
        return $goals;
      }
    } else {
      return array('error_message' => 'No goals exist');
    }
  }


  public function get_data($metric, $dimension, $start_date, $end_date, $filter_type, $timezone, $same_dimension = null){
    $dimension = lcfirst($dimension);
    $metric = lcfirst($metric);
    $profileId = $this->get_profile_id(false);
    $analytics = $this->analytics_member;
    if(strpos($metric, 'ga:') === false) {
      $metric = 'ga:' . $metric;
    }
    $selected_metric = $metric;
    if(strpos($selected_metric, 'ga:') > -1) {
      $selected_metric = substr($selected_metric, 3);
    }
    if($dimension == 'interestInMarketCategory' || $dimension == 'interestAffinityCategory' || $dimension == 'interestOtherCategory' || $dimension == 'country' || $dimension == 'language' || $dimension == 'userType' || $dimension == 'sessionDurationBucket' || $dimension == 'userAgeBracket' || $dimension == 'userGender' || $dimension == 'mobileDeviceInfo' || $dimension == 'deviceCategory' || $dimension == 'operatingSystem' || $dimension == 'browser' || $dimension == 'date' || $dimension == "source") {
      $metrics = 'ga:users,ga:sessions,ga:percentNewSessions,ga:bounceRate,ga:pageviews,ga:avgSessionDuration,ga:pageviewsPerSession';

      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }

    } elseif($dimension == 'siteSpeed') {
      $dimension = 'date';
      $metrics = 'ga:avgPageLoadTime,ga:avgRedirectionTime,ga:avgServerResponseTime,ga:avgPageDownloadTime';

      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }

    } elseif($dimension == 'adsense') {
      $dimension = 'date';
      $metrics = 'ga:adsenseRevenue,ga:adsenseAdsClicks';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($dimension == 'eventLabel' || $dimension == 'eventAction' || $dimension == 'eventCategory') {
      $metrics = 'ga:eventsPerSessionWithEvent,ga:sessionsWithEvent,ga:avgEventValue,ga:eventValue,ga:uniqueEvents,ga:totalEvents';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($dimension == 'productCategory' || $dimension == 'productName' || $dimension == 'productSku') {
      $metrics = 'ga:itemQuantity,ga:uniquePurchases,ga:itemRevenue,ga:itemsPerPurchase';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($dimension == 'sales_performance') {
      $dimension = 'date';
      $metrics = 'ga:transactionRevenue,ga:transactionsPerSession';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($dimension == 'transactionId') {
      $metrics = 'ga:transactionRevenue,ga:transactionTax,ga:transactionShipping,ga:itemQuantity';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($dimension == 'daysToTransaction') {
      $metrics = 'ga:transactions';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($dimension == 'adGroup') {
      $metrics = 'ga:adClicks,ga:adCost';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    }

    $dimension = $dimension == 'date' ? $filter_type != '' ? $filter_type : 'date' : $dimension;
    if($same_dimension == 'sales_performance' && ($dimension == 'week' || $dimension == 'month' || $dimension == 'hour')) {
      $metrics = 'ga:transactionRevenue, ga:transactionsPerSession';
      if(strpos($metrics, $metric) !== false) {
        $metric = $metrics;
      }
    } elseif($same_dimension == 'adsense' && ($dimension == 'week' || $dimension == 'month' || $dimension == 'hour')) {
      $metrics = 'ga:adsenseRevenue,ga:adsenseAdsClicks';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    } elseif($same_dimension == 'siteSpeed' && ($dimension == 'week' || $dimension == 'month' || $dimension == 'hour')) {
      $metrics = 'ga:avgPageLoadTime,ga:avgRedirectionTime,ga:avgServerResponseTime,ga:avgPageDownloadTime';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    }

    if($same_dimension == 'week' || $same_dimension == 'month' || $same_dimension == 'hour') {
      $metrics = 'ga:users,ga:sessions,ga:percentNewSessions,ga:bounceRate,ga:pageviews,ga:avgSessionDuration';
      if($this->check_metric_compare($metrics, $metric)){
        $metric = $metrics;
      }
    }
    // Get the results from the Core Reporting API and print the results.
    // Calls the Core Reporting API and queries for the number of sessions
    // for the last seven days.
    if($dimension == 'hour') {

      $gawd_dimension = array(
        'dimensions' => 'ga:date, ga:hour',
        'sort' => 'ga:date',
      );
    } else {
      if($dimension != 'sessionDurationBucket') {
        $gawd_dimension = array(
          'dimensions' => 'ga:' . $dimension,
          'sort' => '-ga:' . $selected_metric,
        );
      } else {
        $gawd_dimension = array(
          'dimensions' => 'ga:' . $dimension,
          'sort' => 'ga:' . $dimension,
        );
      }
    }
    try {
      $results = $analytics->data_ga->get(
        'ga:' . $profileId, $start_date, $end_date, $metric, $gawd_dimension
      );
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_data');
      $error = array('error_message' => 'Error');
      if(strpos($e->getMessage(), 'Selected dimensions and metrics cannot be queried together')) {
        $error['error_message'] = 'Selected dimensions and metrics cannot be queried together';
      } else if(strpos($e->getMessage(), 'User does not have sufficient permissions for this profile')) {
        $error['error_message'] = 'User does not have sufficient permissions for this profile';
      }
      return $error;
    }
    $metric = explode(',', $metric);

    $rows = $results->getRows();
    $rows = $this->sort_ga_data($rows, $dimension);
    if($rows) {
      $j = 0;
      $data_sum = array();
      foreach($results->getTotalsForAllResults() as $key => $value) {
        $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($key, 3))))] = $value;
      }
      if($dimension == 'week' || $dimension == 'month' || $dimension == 'hour') {
        $date = $start_date;
        if($dimension == 'week') {
          $_end_date = date("l", strtotime($date)) == 'Saturday' ? date("M d,Y", strtotime($date)) : date("M d,Y", strtotime('next Saturday ' . $date));
        } elseif($dimension == 'month') {
          $_end_date = date("M t,Y", strtotime($date));
          if(strtotime($_end_date) > strtotime(date('Y-m-d'))) {
            $_end_date = date("M d,Y", strtotime('-1 day ' . date('Y-m-d')));
          }
        }
        if(strtotime($end_date) > strtotime(date('Y-m-d'))) {
          $end_date = date("M d,Y");
        }

        foreach($rows as $row) {

          $date_period = '';
          if($dimension == 'hour') {
            $dimension_value = date("Y-m-d", strtotime($row[0])) . ' ' . $row[1] . ':00';
          } else {
            if(isset($_end_date) && (strtotime($_end_date) <= strtotime(date('Y-m-d')))) {
              $dimension_value = date("Y-m-d", strtotime($date));// . '-' . $_end_date;
              $date_period = $dimension_value . ' - ' . date('Y-m-d', strtotime($_end_date));
            } else {
              if(isset($_end_date) && (strtotime($date) != strtotime($end_date))) {
                $dimension_value = date("Y-m-d", strtotime($date));// . '-' . $_end_date;
                $date_period = $dimension_value . ' - ' . date('Y-m-d', strtotime($_end_date));
              } else {
                break;
              }
            }
          }

          $data[] = array(trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension))) => $dimension_value);
          $data[$j]['No'] = floatval($j + 1);
          $data[$j]['date_period'] = $date_period;
          for($i = 0; $i < count($metric); $i++) {
            $val = $i + 1;
            if($dimension == 'hour') {
              $val = $i + 2;
            }
            $metric_val = floatval($row[$val]);
            $data[$j][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = $metric_val;
          }

          $j++;

          if(isset($break) && $break) {
            break;
          }

          if($dimension == 'week' && isset($_end_date)) {
            $date = date("M d,Y", strtotime('next Sunday ' . $_end_date));
            $_end_date = date("M d,Y", strtotime('next Saturday ' . $date));
          } elseif($dimension == 'month' && isset($_end_date)) {
            $date = date("M d,Y", strtotime('+1 day ' . $_end_date));
            $_end_date = date("M t,Y", strtotime($date));
          }
          if(isset($_end_date) && (strtotime($_end_date) > strtotime($end_date))) {
            $_end_date = date("M d,Y", strtotime($end_date));
            $break = true;
          }
        }
      } else {
        /*day*/
        foreach($rows as $row) {
          if(strtolower($dimension) == 'date') {
            $row[0] = date('Y-m-d', strtotime($row[0]));
          }
          elseif(strtolower($dimension) == 'sessiondurationbucket') {

            if($row[0] >= 0 && $row[0] <= 10) {
              $row[0] = '0-10';
            } elseif($row[0] >= 11 && $row[0] <= 30) {
              $row[0] = '11-30';
            } elseif($row[0] >= 31 && $row[0] <= 40) {
              $row[0] = '31-40';
            } elseif($row[0] >= 41 && $row[0] <= 60) {
              $row[0] = '41-60';
            } elseif($row[0] >= 61 && $row[0] <= 180) {
              $row[0] = '61-180';
            } elseif($row[0] >= 181 && $row[0] <= 600) {
              $row[0] = '181-600';
            } elseif($row[0] >= 601 && $row[0] <= 1800) {
              $row[0] = '601-1800';
            } elseif($row[0] >= 1801) {
              $row[0] = '1801';
            }
          } elseif(strpos($dimension, 'dimension') > -1) {
            $gawd_web_property_id = $this->gawd_last_viewed_profile_data["web_property_id"];
            $gawd_account_id = $this->gawd_last_viewed_profile_data["account_id"];
            $dimension_data = $this->get_custom_dimensions($gawd_web_property_id,$gawd_account_id);
            foreach($dimension_data as $key => $value) {
              if($dimension == substr($value['id'], 3)) {
                $dimension = $value['name'];
              }
            }
          }
          $data[$j]['No'] = floatval($j + 1);
          $dimension_data = ctype_digit($row[0]) ? intval($row[0]) : $row[0];
          $dimension_data = strpos($dimension_data, 'T') ? substr($dimension_data, 0, strpos($dimension_data, 'T')) : $dimension_data;
          $data[$j][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension)))] = $dimension_data;

          for($i = 0; $i < count($metric); $i++) {
            $metric_val = floatval($row[$i + 1]);
            if(substr($metric[$i], 3) == 'avgSessionDuration') {
              $metric_val = ceil($row[$i + 1]);
            }
            $data[$j][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = $metric_val;
          }
          $j++;
        }
      }
      if(isset($same_dimension) && $same_dimension != null) {
        $dimension = $filter_type == 'date' || $filter_type == '' || $filter_type == 'Date' ? $same_dimension : $same_dimension . '_' . $filter_type;
      }
      if($dimension == "daysToTransaction") {
        foreach($data as $key => $row) {
          $daysToTransaction[$key] = $row['Days To Transaction'];
        }
        array_multisort($daysToTransaction, SORT_ASC, $data);
        foreach($data as $j => $val) {
          $val["No"] = ($j + 1);
          $data[$j] = $val;
        }
      } elseif($dimension == "sessionDurationBucket") {
        $_data = array();
        foreach($data as $val) {
          if(isset($_data[$val["Session Duration Bucket"]])) {
            $_data[$val["Session Duration Bucket"]]["Users"] += floatval($val["Users"]);
            $_data[$val["Session Duration Bucket"]]["Sessions"] += floatval($val["Sessions"]);
            $_data[$val["Session Duration Bucket"]]["Percent New Sessions"] += floatval($val["Percent New Sessions"]);
            $_data[$val["Session Duration Bucket"]]["Bounce Rate"] += floatval($val["Bounce Rate"]);
            $_data[$val["Session Duration Bucket"]]["Pageviews"] += floatval($val["Pageviews"]);
            $_data[$val["Session Duration Bucket"]]["Avg Session Duration"] += $val["Avg Session Duration"];
          } else {
            // $val["No"] = $j;
            // $j++;
            $_data[$val["Session Duration Bucket"]] = $val;
            $_data[$val["Session Duration Bucket"]]["order"] = intval($val["Session Duration Bucket"]);
          }


        }
        $data = array_values($_data);
        foreach($data as $key => $row) {
          $yyy[$key] = $row['order'];
        }
        array_multisort($yyy, SORT_ASC, $data);
        foreach($data as $j => $val) {
          $val["No"] = ($j + 1);
          $data[$j] = $val;
        }
      } else {
        if(strpos($dimension, 'dimension') === false) {
          $dimension = $dimension == 'siteSpeed' || $dimension == 'sales_performance' ? 'Date' : $dimension;
          foreach($data as $key => $row) {
            if(isset($row[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension)))])) {
              $new_data[$key] = $row[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension)))];
            } else {
              $new_data[$key] = null;
            }
          }
          array_multisort($new_data, SORT_ASC, $data);
          foreach($data as $j => $val) {
            $val["No"] = ($j + 1);
            $data[$j] = $val;
          }
        }
      }
      $result = $data;
      if($data_sum != '') {
        $result = array('data_sum' => $data_sum, 'chart_data' => $data);
      }
      return $result;
    } else {
      if(strpos($dimension, 'dimension') > -1) {
        $gawd_web_property_id = $this->gawd_last_viewed_profile_data["web_property_id"];
        $gawd_account_id = $this->gawd_last_viewed_profile_data["account_id"];

        $dimension_data = $this->get_custom_dimensions($gawd_web_property_id, $gawd_account_id);
        foreach($dimension_data as $key => $value) {
          if($dimension == substr($value['id'], 3)) {
            $dimension = $value['name'];
          }
        }
      }

      $key = trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension)));
      $key = str_replace('  ',' ',$key);
      $empty[0] = array(
        $key => 0
      );
      $empty[0]['No'] = 1;
      for($i = 0; $i < count($metric); $i++) {
        $empty[0][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
      }

      return array('chart_data' => $empty);
    }
  }


  public function get_page_data($dimension, $start_date, $end_date, $timezone){
    $analytics = $this->analytics_member;
    $profileId = $this->get_profile_id(false);
    $metric = $dimension == 'pagePath' || $dimension == 'PagePath' ? 'ga:pageviews,ga:uniquePageviews,ga:avgTimeOnPage,ga:entrances,ga:bounceRate,ga:exitRate,ga:pageValue,ga:avgPageLoadTime' : 'ga:sessions,ga:percentNewSessions,ga:newUsers,ga:bounceRate,ga:pageviewsPerSession,ga:avgSessionDuration,ga:transactions,ga:transactionRevenue,ga:transactionsPerSession';
    $sorts = explode(',', $metric);
    $sort = '-' . $sorts[0];

    try {
      $results = $analytics->data_ga->get(
        'ga:' . $profileId, $start_date, $end_date, $metric, array(
          'dimensions' => 'ga:' . $dimension,
          'sort' => $sort,
        )
      );
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_page_data');
      $error = array('error_message' => 'Error');
      if(strpos($e->getMessage(), 'User does not have sufficient permissions for this profile')) {
        $error['error_message'] = 'User does not have sufficient permissions for this profile';
      }
      return $error;
    }
    $rows = $results->getRows();
    $metric = explode(',', $metric);
    if($rows) {
      $data_sum = array();
      foreach($results->getTotalsForAllResults() as $key => $value) {
        $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($key, 3))))] = $value;
      }
      foreach($rows as $key => $row) {
        $hours = strlen(floor($row[3] / 3600)) < 2 ? '0' . floor($row[3] / 3600) : floor($row[3] / 3600);
        $mins = strlen(floor($row[3] / 60 % 60)) < 2 ? '0' . floor($row[3] / 60 % 60) : floor($row[3] / 60 % 60);
        $secs = strlen(floor($row[3] % 60)) < 2 ? '0' . floor($row[3] % 60) : floor($row[3] % 60);
        $time_on_page = $hours . ':' . $mins . ':' . $secs;
        if($dimension == 'pagePath' || $dimension == 'PagePath') {
          $data[] = array(
            'No' => floatval($key + 1),
            'Page Path' => $row[0],
            'Pageviews' => intval($row[1]),
            'Unique Pageviews' => intval($row[2]),
            'Avg Time On Page' => $time_on_page,
            'Entrances' => intval($row[4]),
            'Bounce Rate' => floatval($row[5]),
            'Exit Rate' => ($row[6]),
            'Page Value' => intval($row[7]),
            'Avg Page Load Time' => intval($row[8])
          );
        } else {
          $data[] = array(
            'No' => floatval($key + 1),
            'Landing Page' => $row[0],
            'Sessions' => intval($row[1]),
            'Percent New Sessions' => ($row[2]),
            'New Users' => ($row[3]),
            'Bounce Rate' => ($row[4]),
            'Pageviews Per Session' => floatval($row[5]),
            'Avg Session Duration' => ($row[6]),
            'Transactions' => intval($row[7]),
            'Transaction Revenue' => intval($row[8]),
            'Transactions Per Session' => intval($row[9])
          );
        }
      }

    } else {
      $empty[0] = array(
        trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension))) => 0
      );
      $empty[0]['No'] = 1;
      for($i = 0; $i < count($metric); $i++) {
        $empty[0][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
        $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
      }

      return array('data_sum' => $data_sum, 'chart_data' => $empty);
    }
    $result = array();
    if($data_sum != '') {
      $result = array('data_sum' => $data_sum, 'chart_data' => $data);
    }
    return $result;
  }


  public function get_country_data($metric, $dimension, $start_date, $end_date, $country_filter, $geo_type, $timezone){
    $profileId = $this->get_profile_id(false);
    $analytics = $this->analytics_member;
    $metric = 'ga:users,ga:sessions,ga:percentNewSessions,ga:bounceRate,ga:pageviews,ga:avgSessionDuration';

    try {
      $results = $analytics->data_ga->get(
        'ga:' . $profileId, $start_date, $end_date, $metric, array(
          'dimensions' => 'ga:' . $dimension,
          'sort' => 'ga:' . $dimension,
          'filters' => 'ga:' . $geo_type . '==' . $country_filter
        )
      );
    } catch(Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_country_data');
      $error = array('error_message' => 'Error');
      if(strpos($e->getMessage(), 'User does not have sufficient permissions for this profile')) {
        $error['error_message'] = 'User does not have sufficient permissions for this profile';
      }
      return $error;
    }
    $rows = $results->getRows();
    $metric = explode(',', $metric);
    if($rows) {
      $data_sum = array();
      foreach($results->getTotalsForAllResults() as $key => $value) {
        $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($key, 3))))] = $value;
      }
      $j = 0;
      foreach($rows as $row) {
        $data[$j] = array(
          ucfirst($dimension) => $row[0]
        );
        $data[$j]['No'] = floatval($j + 1);
        for($i = 0; $i < count($metric); $i++) {
          $data[$j][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = floatval($row[$i + 1]);
        }
        $j++;
      }
    } else {
      $empty[0] = array(
        trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension))) => 0
      );
      $empty[0]['No'] = 1;
      for($i = 0; $i < count($metric); $i++) {
        $empty[0][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
      }

      return $empty;
    }
    $result = $data;
    if($data_sum != '') {
      $result = array('data_sum' => $data_sum, 'chart_data' => $data);
    }
    return $result;
  }


  public function get_post_page_data( $front, $metric, $dimension, $start_date, $end_date, $filter, $timezone, $chart=null) {
    $profileId = $this->get_profile_id();
    $analytics = $this->analytics_member;
    $metric = 'ga:users,ga:sessions,ga:percentNewSessions,ga:bounceRate,ga:pageviews,ga:avgSessionDuration,ga:pageviewsPerSession';
    if($chart == 'pie'){
      $diff = date_diff(date_create($start_date),date_create($end_date));
      if(intval($diff->format("%a")) > 7){
        $dimension = 'week';
      }
      if(intval($diff->format("%a")) > 60){
        $dimension = 'month';
      }
    }
    // Get the results from the Core Reporting API and print the results.
    // Calls the Core Reporting API and queries for the number of sessions
    // for the last seven days.


    $filter_type = 'pagePath';
    try {
      $results = $analytics->data_ga->get(
        'ga:' . $profileId, $start_date, $end_date, $metric, array(
          'dimensions' => 'ga:' . $dimension,
          'sort' => 'ga:' . $dimension,
          'filters' => 'ga:' . $filter_type . '%3D~' . $filter
        )
      );
    } catch (Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_post_page_data');
      $error = array('error_message' => 'Error');
      if(strpos($e->getMessage(), 'User does not have sufficient permissions for this profile')) {
        $error['error_message'] = 'User does not have sufficient permissions for this profile';
      }
      return $error;
    }

    $rows = $results->getRows();
    $metric = explode(',', $metric);
    if ($rows) {
      $j = 0;
      $data_sum = array();
      if ($dimension == 'week' || $dimension == 'month') {
        $date = $start_date;
        if ($dimension == 'week') {
          $_end_date =  date("l", strtotime($date)) == 'Saturday' ? date("M d,Y", strtotime($date)) : date("M d,Y", strtotime('next Saturday ' . $date));
        }
        elseif ($dimension == 'month') {
          $_end_date = date("M t,Y", strtotime($date));
          if(strtotime($_end_date) > strtotime(date('Y-m-d'))){
            $_end_date = date("M d,Y",strtotime('-1 day ' . date('Y-m-d')));
          }
        }
        if (strtotime($end_date) > strtotime(date('Y-m-d'))) {
          $end_date = date("M d,Y");
        }
        foreach ($rows as $row) {
          if ($dimension == 'hour') {
            $dimension_value = date("Y-m-d", strtotime($row[0])) . ' ' . $row[1] . ':00';
          }
          else {
            if (strtotime($_end_date) <= strtotime(date('Y-m-d'))) {
              $dimension_value = date("Y-m-d", strtotime($date));// . '-' . $_end_date;
            } else {
              if (strtotime($date) != strtotime($end_date) ) {
                $dimension_value = date("Y-m-d", strtotime($date));// . '-' . $_end_date;
              } else {
                break;
              }
            }
          }
          $data[] = array(trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension))) => $dimension_value);
          $data[$j]['No'] = floatval($j + 1);
          for ($i = 0; $i < count($metric); $i++) {
            $val = $i + 1;
            if ($dimension == 'hour') {
              $val = $i + 2;
            }
            $metric_val = floatval($row[$val]);
            $data[$j][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = $metric_val;
          }
          $j++;
          if(isset($break) && $break){
            break;
          }

          if ($dimension == 'week') {
            $date = date("M d,Y", strtotime('next Sunday ' . $_end_date));
            $_end_date = date("M d,Y", strtotime('next Saturday ' . $date));
          } elseif ($dimension == 'month') {
            $date = date("M d,Y", strtotime('+1 day ' . $_end_date));
            $_end_date = date("M t,Y", strtotime($date));
          }
          if (isset($_end_date) && (strtotime($_end_date) > strtotime($end_date))) {
            $_end_date = date("M d,Y", strtotime($end_date));
            $break = true;
          }
        }
      }
      else{
        foreach ($rows as $row) {
          if ($dimension == 'date') {
            $row[0] = date('Y-m-d', strtotime($row[0]));
          }
          $data[$j] = array(
            $dimension => $row[0]
          );
          for ($i = 0; $i < count($metric); $i++) {
            $data[$j][substr($metric[$i], 3)] = floatval($row[$i + 1]);
            if (isset($data_sum[substr($metric[$i], 3)])) {
              $data_sum[substr($metric[$i], 3)] += floatval($row[$i + 1]);
            } else {
              if (substr($metric[$i], 3) != 'percentNewSessions' && substr($metric[$i], 3) != 'bounceRate') {
                $data_sum[substr($metric[$i], 3)] = floatval($row[$i + 1]);
              }
            }
          }
          $j++;
        }
      }
      $result = array('data_sum' => $data_sum, 'chart_data' => $data);
      return $result;
    } else {
      $empty[0][$dimension] = date("Y-m-d");
      for ($i = 0; $i < count($metric); $i++) {
        $empty[0][trim(str_replace(" ", "", preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
      }
      return $empty;
    }
  }

  public function get_data_compact($metric, $dimension, $start_date, $end_date, $timezone) {
    $get_last_viewed_profile = GAWD_helper::get_last_viewed_profile();

    $profileId = $get_last_viewed_profile["profile_id"];
    $metric_sort = $metric;
    $analytics = $this->analytics_member;
    $result = array();
    $data = array();
    // Get the results from the Core Reporting API and print the results.
    // Calls the Core Reporting API and queries for the number of sessions
    // for the last seven days.
    if ($dimension == 'date') {
      $metric = 'ga:users,ga:sessions,ga:percentNewSessions,ga:bounceRate,ga:pageviews,ga:avgSessionDuration,ga:pageviewsPerSession';
    }
    try{
      $results = $analytics->data_ga->get(
        'ga:' . $profileId, $start_date, $end_date, $metric, array(
          'dimensions' => 'ga:' . $dimension,
          'sort' => 'ga:' . $dimension,
        )
      );
    }
    catch (Exception $e) {
      $this->exception_handler->add($e, 'Exception', 'get_data_compact');
      $error = array('error_message' => 'Error');
      if(strpos($e->getMessage(), 'User does not have sufficient permissions for this profile')) {
        $error['error_message'] = 'User does not have sufficient permissions for this profile';
      }
      return $error;
    }
    $rows = $results->getRows();
    $metric = explode(',', $metric);
    if ($rows) {
      $j = 0;
      $data_sum = array();
      foreach($results->getTotalsForAllResults() as $key => $value){
        $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($key,3))))] = $value;
      }
      foreach ($rows as $row) {
        if ($dimension == 'date') {
          $row[0] = date('Y-m-d', strtotime($row[0]));
        }
        $data[$j] = array(
          trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension))) => $row[0]
        );
        for ($i = 0; $i < count($metric); $i++) {
          $metric_val = floatval($row[$i + 1]);

          $data[$j][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = $metric_val;
        }
        $j++;
      }
      if($dimension == "country"){
        foreach ($data as $key => $row) {
          $country[$key]  = $row[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric_sort, 3))))];
        }
        array_multisort($country, SORT_DESC, $data);
        foreach($data as $j=>$val){
          $val["No"] = ($j+1);
          $data[$j] = $val;
        }
      }
    }
    else {
      $data_sum = array();
      $empty[0] = array(
        trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension))) => 0
      );
      $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', $dimension)))] = 0;
      $empty[0]['No'] = 1;
      for ($i = 0; $i < count($metric); $i++) {
        $empty[0][trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
        $data_sum[trim(ucfirst(preg_replace('/([A-Z])/', ' $1', substr($metric[$i], 3))))] = 0;
      }
      $result = array('data_sum' => $data_sum, 'chart_data' => $empty);
      return $result;
    }
    if ($data_sum != '') {
      $result = array('data_sum' => $data_sum, 'chart_data' => $data);
    }
    return $result;
  }

  public function refresh_user_data($user_data = null){
    $this->user_data = ($user_data !== null) ? $user_data : get_option('gawd_user_data');
  }

  private function check_metric_compare($metrics1, $metrics2){
    $metrics_array = explode(',', $metrics2);

    foreach($metrics_array as $m) {
      if(strpos($metrics1, $m) === false) {
        return false;
      }
    }
    return true;
  }

  private function sort_ga_data($rows, $duration){

    if($rows === null){
      return $rows;
    }

    if($duration === 'week'){
      usort($rows, array($this, 'sort_week'));
    }else if($duration == 'month'){
      usort($rows, array($this, 'sort_month'));
    }else if($duration === 'hour'){
      usort($rows, array($this, 'sort_hour'));
    }else{
      usort($rows, array($this, 'sort_day'));
    }

    return $rows;
  }

  public function sort_week($a, $b){
    //max interval is 90 days
    // last weeks of the previous yuear
    if(($a[0] <= 15) && ($b[0] >= 37)) {
      return 1;
    }

    if(($b[0] <= 15) && ($a[0] >= 37)) {
      return -1;
    }

    if($a[0] == $b[0]) {
      return 0;
    }

    return ($a[0] < $b[0]) ? -1 : 1;
  }

  public function sort_month($a, $b){
    //max interval is 90 days
    // last weeks of the previous yuear
    if(($a[0] >= 10) && ($b[0] <= 3)) {
      return -1;
    }

    if(($b[0] >= 10) && ($a[0] <= 3)) {
      return 1;
    }

    if($a[0] == $b[0]) {
      return 0;
    }

    return ($a[0] < $b[0]) ? -1 : 1;
  }

  public function sort_hour($a, $b){

    $a_value = $a[0].$a[1].$a[2];
    $b_value = $b[0].$b[1].$b[2];

    if($a_value == $b_value) {
      return 0;
    }

    return ($a_value < $b_value) ? -1 : 1;
  }

  public function sort_day($a, $b){
    if($a[0] == $b[0]) {
      return 0;
    }

    return ($a[0] < $b[0]) ? -1 : 1;
  }


  public function get_default_account_id(){
    return $this->user_data['account_id'];
  }

  public function get_default_web_property_id(){
    return $this->user_data['property_id'];
  }

  public function get_account_id(){
    return $this->user_data['account_id'];
  }

  public function get_profile_id($get_default = true){
    $gawd_last_viewed_profile = GAWD_helper::get_last_viewed_profile();

    if($get_default === false) {
      if(isset($this->profile_info['view_id'])) {
        return $this->profile_info['view_id'];
      } else if(isset($gawd_last_viewed_profile['profile_id'])) {
        return $gawd_last_viewed_profile['profile_id'];
      }

    }

    return (isset($this->user_data['profile_id']) ? $this->user_data['profile_id'] : null);
  }


  public function get_profile_accountId(){
    return isset($this->user_data['account_id']) ? $this->user_data['account_id'] : '';
  }

  public function get_profile_webPropertyId(){
    return (isset($this->user_data['property_id']) ? $this->user_data['property_id'] : null);
  }

  public function set_profile_info($view_id){
    $this->profile_info = array(
      'view_id' => $view_id
    );
  }

}