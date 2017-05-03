<?php
/**
 * This command will fetch an new relic overview of the project in a specific environment
 *
 */
namespace Pantheon\TerminusGetNewrelic\Commands;


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
     * @command getNewrelic
     */
     public function getNewrelic($site_env_id, $dest = null,
         $options = ['all' => false, 'overview' => false, 'transactions' => false, 'database' => false, 'hooks' => false, 'themes_plugins' => false, 'modules' => false, 'external_services' => false,]) {
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


         $climate = new CLImate;

         $items = [];
         foreach($obj_result['applications'] as $key => $val) {

             $url =  "https://api.newrelic.com/v2/applications/" . $val['id'] . ".json";

             $myresult = $this->CallAPI('GET', $url, $api_key, $data = false);

             $item_obj = json_decode($myresult, true);

             if(strstr($item_obj['application']['name'], $env_id)) {
                  $obj = $item_obj['application'];
                  $status = $this->HealthStatus($obj['health_status']);
                  $items[] = array( "Name" => $obj['name'],
                                    "App Apdex" => $obj['settings']['app_apdex_threshold'],
                                    "Browser Apdex" => $obj['settings']['end_user_apdex_threshold'],
                                    "Health Status" => $status,
                             );

              }

         }
         $climate->table($items);
     }

     function HealthStatus($color)
     {

       switch ($color) {
         case "red":
             return "\033[31;5m" . $color . "\033[0m\t";
             break;
         case "yellow":
             return "\033[31m" . $color . "\033[0m\t";
             break;
         default:
             return "\033[32m" . $color. "\033[0m\t";
       }

     }

    function CallAPI($method, $url, $api_key, $data = false)
    {

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

}
