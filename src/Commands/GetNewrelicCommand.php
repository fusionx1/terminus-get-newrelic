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
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
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
          'transactions',
          'hooks',
          'plugins_themes',
          'modules',
          'databases',
          'external_services',
      ];



      /**
       * Pull new relic data
       *
       * @command newrelic:org
       */
       public function org($org_id, $dest = null,
         $options = ['overview' => false ]) {

         $climate = new CLImate;
         if(!empty($org_id)) {
           $pro = array();
           $free = array();
           $business = array();
           $elite = array();

           $this->sites()->fetch(
           [
               'org_id' => isset($org_id) ? $org_id : null,
           ]
           );

           $sites = $this->sites->serialize();
           if (empty($sites)) {
             $this->log()->notice('You have no sites.');
           }

            $status = [];

            foreach ($sites as $site) {
              if ($environments = $this->getSite($site['name'])->getEnvironments()->serialize()) {
                foreach ($environments as $environment) {
                  if ( $environment['id'] == 'dev' AND !isset($options['overview'])) { // #1 start

                      $site_env = $site['name'] . '.' . $environment['id'];
                      list(, $env) = $this->getSiteEnv($site_env);
                      $env_id = $env->getName();
                      $pop = $env->getBindings()->getByType('newrelic');
                      $pip = array_pop($pop);
                      $service_lvl = $site['service_level'];



                      if(empty($pip)) {
                        switch ($service_lvl) {
                          case "free":
                            $free[] = array( "Site" => $site['name'],
                                            "Service level" => $site['service_level']
                                           );
                          break;

                          case "pro":
                            $pro[] = array( "Site" => $site['name'],
                                            "Service level" => $site['service_level']
                                          );
                          break;

                          case "business":
                            $business[] = array( "Site" => $site['name'],
                                          "Service level" => $site['service_level']
                                               );
                          break;

                          case "elite":
                            $elite[] = array( "Site" => $site['name'],
                                          "Service level" => $site['service_level']
                                           );
                          break;
                      }
                    }
                  }                                                                // #1 end
                  if ( $environment['id'] == 'live' AND $options['overview']) { // #2 start

                      $site_env = $site['name'] . '.' . $environment['id'];
                      list(, $env) = $this->getSiteEnv($site_env);
                      $env_id = $env->getName();
                      $pop = $env->getBindings()->getByType('newrelic');
                      $pip = array_pop($pop);
                      if(!empty($pip)) {
                        $api_key = $pip->get('api_key');
                        $service_lvl = $site['service_level'];
                        $items[] = $this->fetch_newrelic_data($api_key, $env_id);
                      }


                  }                                                                // #2 end
                }
              }
            }
           //return new RowsOfFields($status);

          if($options['overview']) {
             $climate->table($items);
          } else {
             $climate->table($free);
             $climate->table($pro);
             $climate->table($business);
             $climate->table($elite);
             return false;
         }
      }
    }


    /**
     * Pull new relic data 10:14
     *
     * @command newrelic:site
     */
     public function site($site_env_id, $dest = null,
         $options = ['all' => false, 'overview' => false, 'transactions' => false, 'database' => false, 'hooks' => false, 'themes_plugins' => false, 'modules' => false, 'external_services' => false, 'org_sites' => null ]) {

         $climate = new CLImate;

         // Get env_id and site_id.
         list($site, $env) = $this->getSiteEnv($site_env_id);
         $env_id = $env->getName();

         $siteInfo = $site->serialize();
         $site_id = $siteInfo['id'];

         // Set destination to cwd if not specified.
         if (!$dest) {
             $dest = $siteInfo['name'] . '/' . $env_id;
         }

         $pop = $env->getBindings()->getByType('newrelic');
         $pip = array_pop($pop);
         $api_key = $pip->get('api_key');


         $url =  'https://api.newrelic.com/v2/applications.json';

         $result = $this->CallAPI('GET', $url, $api_key, $data = false);
         $obj_result = json_decode($result, true);


         $items = [];
         foreach($obj_result['applications'] as $key => $val) {

             $url =  "https://api.newrelic.com/v2/applications/" . $val['id'] . ".json";

             $myresult = $this->CallAPI('GET', $url, $api_key, $data = false);

             $item_obj = json_decode($myresult, true);
             if(strstr($item_obj['application']['name'], $env_id)) {
                  $obj = $item_obj['application'];
                  $status = $this->HealthStatus($obj['health_status']);
                  $reporting = $this->HealthStatus($obj['reporting']);

                  if($reporting != ""){
                    $items[] = array( "Name" => $obj['name'],
                                      "App Apdex" => $obj['settings']['app_apdex_threshold'],
                                      "Browser Apdex" => $obj['settings']['end_user_apdex_threshold'],
                                      "Health Status" => $status,
                               );
                  } else {
                    $sum_obj = $obj['application_summary'];
                    if(empty($sum_obj) {
                        $items[] = array( "Name" => $obj['name'],
                                          "Response time" => $sum_obj['response_time'],
                                          "Throughput" => $sum_obj['throughput'],
                                          "Error Rate" => $sum_obj['error_rate'],
                                          "Apdex" => $sum_obj['apdex_target'] . '/' . $sum_obj['apdex_score'],
                                          "# of Hosts" => $sum_obj['host_count'],
                                          "# of Instance" => $sum_obj['instance_count'],
                                          "Health" => $status);
                    }

              }

         }
         $climate->table($items);
     }

     function HealthStatus($color) {
       switch ($color) {
         case "green":
             return "Healthy Condition";
             break;
         case "red":
             return "Critical Condition";
             break;
         case "yellow":
             return "Warning Condition";
             break;
         default:
             return "Not Reporting";
       }
     }

    function CallAPI($method, $url, $api_key, $data = false) {

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

    function fetch_newrelic_data($api_key, $env_id) {
      $url =  'https://api.newrelic.com/v2/applications.json';

      $result = $this->CallAPI('GET', $url, $api_key, $data = false);
      $obj_result = json_decode($result, true);


      $items = [];
      foreach($obj_result['applications'] as $key => $val) {

          $url =  "https://api.newrelic.com/v2/applications/" . $val['id'] . ".json";

          $myresult = $this->CallAPI('GET', $url, $api_key, $data = false);

          $item_obj = json_decode($myresult, true);
          if(strstr($item_obj['application']['name'], $env_id)) {
               $obj = $item_obj['application'];
               $status = $this->HealthStatus($obj['health_status']);
               $reporting = $this->HealthStatus($obj['reporting']);

               if(!empty($obj['application_summary']) {
                 $items[] = array( "Name" => $obj['name'],
                                   "Response time" => $sum_obj['response_time'],
                                   "Throughput" => $sum_obj['throughput'],
                                   "Error Rate" => $sum_obj['error_rate'],
                                   "Apdex" => $sum_obj['apdex_target'] . '/' . $sum_obj['apdex_score'],
                                   "# of Hosts" => $sum_obj['host_count'],
                                   "# of Instance" => $sum_obj['instance_count'],
                                   "Health" => $status);
               }
           }

      }

      return $items;
    }
}
