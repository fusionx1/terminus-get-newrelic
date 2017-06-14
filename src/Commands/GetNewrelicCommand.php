<?php
/**
 * This command will fetch an new relic overview of the project in a specific environment
 *
 */
namespace Pantheon\TerminusGetNewrelic\Commands;

use Pantheon\Terminus\Commands\Site\SiteCommand;
use Consolidation\OutputFormatters\StructuredData\PropertyList;
use Pantheon\Terminus\Commands\TerminusCommand;
use Pantheon\Terminus\Exceptions\TerminusException;
use Pantheon\Terminus\Site\SiteAwareInterface;
use Pantheon\Terminus\Site\SiteAwareTrait;
use Symfony\Component\Filesystem\Filesystem;
use League\CLImate\CLImate;


class GetNewrelicCommand extends TerminusCommand implements SiteAwareInterface
{
    use SiteAwareTrait;

    /**
     * Object constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    private $new_relic_monitoring = [
          'overview',
          'plan',
    ];


    /**
     * Pull sites new-relic data within org
     *
     * @command newrelic-data:org
     *
     * @option overview
     *
     */
     public function org($org_id, $plan = null, $options = ['overview' => false, 'all' => false ]) {
         $climate = new CLImate;

         if(!empty($org_id)) {
             $pro = array();
             $basic = array();
             $free = array();
             $business = array();
             $elite = array();
             $preloader = 'Loading.';
             $counter = 0;
             $this->sites()->fetch(
             [
                'org_id' => isset($org_id) ? $org_id : null,
             ]
             );

             $sites = $this->sites->serialize();
             if (empty($sites)) {
                $this->log()->notice('You have no sites.');
             }


             $count = count($sites);
             $offset = $count/100;
             $progress = $climate->progress()->total($count);

             foreach ($sites as $site) {
                  $service_level = $site['service_level'];
                  $progress->current($counter);
                  $counter += $offset;
                  if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                      foreach ($environments as $environment) {
                          if ( $environment['id'] == 'dev' AND !$options['overview'] ) { // #1 start
                              $site_env = $site['name'] . '.' . $environment['id'];
                              list(, $env) = $this->getSiteEnv($site_env);
                              $env_id = $env->getName();
                              $newrelic = $env->getBindings()->getByType('newrelic');
                              $nr_data = array_pop($newrelic);
                              $nr_status = empty($nr_data) ? "Disabled" : "Enabled";
                              $dash_path = 'https://dashboard.pantheon.io/sites/';

                                if(empty($nr_data) OR $options['all']) {

                                  $appserver = $env->getBindings()->getByType('appserver');
                                  $appserver_data = array_pop($appserver);
                                  $dash_link = empty($appserver_data) ? "--" : $dash_path . $appserver_data->get('site');

                                  $data = array( "Site" => $site['name'],
                                      "Service level" => $site['service_level'],
                                      "Framework"  => $site['framework'],
                                      "Site created" => $site['created'],
                                      "PHP version" => $site['php_version'],
                                      "Newrelic" => $nr_status,
                                      "Dashboard URL" => $dash_link);

                                    switch ($service_level) {
                                        case "free":
                                            $free[] = $data;
                                        break;

                                        case "basic":
                                            $basic[] = $data;
                                        break;

                                        case "pro":
                                            $pro[] = $data;
                                        break;

                                        case "business":
                                            $business[] = $data;
                                        break;

                                        case "elite":
                                            $elite[] = $data;
                                        break;
                                    }
                              }
                          }
                                                                                         // #1 end
                          if ( $environment['id'] == 'live' AND $options['overview']) { // #2 start
                              $site_env = $site['name'] . '.' . $environment['id'];
                              list(, $env) = $this->getSiteEnv($site_env);
                              $env_id = $env->getName();
                              $newrelic = $env->getBindings()->getByType('newrelic');
                              $nr_data = array_pop($newrelic);
                              if(!empty($nr_data) OR $options['all']) {
                                  $api_key = $nr_data->get('api_key');
                                  $pop = $this->fetch_newrelic_data($api_key, $env_id);
                                  if($pop) {
                                     $items[] = $pop;

                                  }
                              }

                          }                                                                // #2 end
                      }
                  }
              }
            //$progress->current($counter);
            if (empty($items) AND !isset($items)) {
                if (!empty($free))
                    $climate->table($free);
                if (!empty($basic))
                    $climate->table($basic);
                if (!empty($pro))
                    $climate->table($pro);
                if (!empty($business))
                    $climate->table($business);
                if (!empty($elite))
                    $climate->table($elite);
             } else {
                $items = $this->multi_sort($items);
                $climate->table($items);
             }
      }
  }


    /**
     * Pull new-relic data per site
     *
     * @command newrelic-data:site
     */
     public function site($site_env_id, $plan = null,
         $options = ['all' => false, 'overview' => false]) {

         $climate = new CLImate;
         $progress = $climate->progress()->total(100);
         $progress->advance();

         // Get env_id and site_id.
         list($site, $env) = $this->getSiteEnv($site_env_id);
         $env_id = $env->getName();

         $siteInfo = $site->serialize();
         $site_id = $siteInfo['id'];
         $newrelic = $env->getBindings()->getByType('newrelic');
         $progress->current(50);

         $nr_data = array_pop($newrelic);

         if(!empty($nr_data)) {
             $api_key = $nr_data->get('api_key');
             $pop = $this->fetch_newrelic_data($api_key, $env_id);
             if(isset($pop)) {
                $items[] = $pop;
                $progress->current(100);
                $climate->table($items);
             }
         }

     }

     /**
      * Color Status based on New-relic
      */
     public function HealthStatus($color) {
         switch ($color) {
             case "green":
                 return "Healthy Condition";
                 break;
             case "red":
                 return "<blink><red>Critical Condition</red></blink>";
                 break;
             case "yellow":
                 return "<yellow>Warning Condition</yellow>";
                 break;
             case "gray":
                 return "Not Reporting";
                 break;
             default:
                 return "Unknown";
                 break;
         }
     }

     /**
      * Object constructor
      */

    public function CallAPI($method, $url, $api_key, $data = false) {

        $header[] = 'X-Api-Key:' . $api_key;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        $data = curl_exec($ch);
        curl_close($ch);;

        return $data;
    }

    public function multi_sort($items) {
        foreach ($items as $key => $row) {
           if(isset($row['Response Time'])) {
               $resp[$key]  = $row['Response Time'];
           }
        }
        // Sort the items with responsetime descending, throughput ascending
        // Add $items as the last parameter, to sort by the common key
        array_multisort($resp, SORT_DESC, $items);
        return $items;
    }

    public function check_array_keys($obj, $status, $reporting) {
        $arr_components = array("response_time" => "Response Time",
                                "throughput" => "Throughput",
                                "error_rate" => "Error Rate",
                                "apdex_target" => "Apdex Target",
                                "host_count" => "Number of Hosts",
                                "instance_count" => "Number of Instance");

        $items = array( "Name" => $obj['name'],
                         "Response Time" => "--",
                         "Throughput" => "--",
                         "Error Rate" => "--",
                         "Apdex Target" => "--",
                         "Number of Hosts" => "--",
                         "Number of Instance" => "--",
                         "Health Status" => $status);

        if((!empty($reporting) OR $reporting != 'Not Reporting') AND isset($obj['application_summary'])) {
            $sum_obj = $obj['application_summary'];
            foreach ($arr_components as $key => $val) {
              if (array_key_exists($key, $sum_obj)) {
                   $items[$val] = $sum_obj[$key];
              }
            }
        }

        return $items;
    }

    public function fetch_newrelic_data($api_key, $env_id) {
      $url =  'https://api.newrelic.com/v2/applications.json';

      $result = $this->CallAPI('GET', $url, $api_key, $data = false);
      $obj_result = json_decode($result, true);

      if(isset($obj_result['applications'])) {
          foreach($obj_result['applications'] as $key => $val) {

              $url =  "https://api.newrelic.com/v2/applications/" . $val['id'] . ".json";

              $myresult = $this->CallAPI('GET', $url, $api_key, $data = false);
              $item_obj = json_decode($myresult, true);

              if(strstr($item_obj['application']['name'], $env_id)) {
                   $obj = $item_obj['application'];
                   $status = $this->HealthStatus($obj['health_status']);
                   $reporting = $this->HealthStatus($obj['reporting']);
                   return $this->check_array_keys($obj, $status, $reporting);
              }

          }

      }
      return false;
    }
}
