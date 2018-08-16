<?php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

/**
 * Class ApiClient
 */
Class ApiClient
{

    const API_URL = "API_URL";

    private $client = null;
    private $login;
    private $access_token;
    private $log_error;
	private $debug = false;
	private $m;

    /**
     * ApiClient constructor.
     * @param $login
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function __construct($login, $debug = false)
    {
        $this->login = $login;

        // Init Client
        $this->client = new Client([
            'base_uri' => self::API_URL,
            'cookies' => true,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ]
        ]);

        // create log channels
        $this->prepare_log_channel();

        // Get token
        $this->prepare_access_token();

        // Init mustache templating
        $this->m = new Mustache_Engine;
		
		// Debug mode
        if ($debug) {
            $this->debug = true;
        }

    }

    /**
     * Get Access Token
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function prepare_access_token()
    {
        try {
            $response = $this->client->request('POST', 'Authenticate/Token', ['json' => $this->login]);
            $result = json_decode($response->getBody()->getContents());
            $this->access_token = $result->ResultData->Token;

        } catch (Exception $e) {
            $response = $this->StatusCodeHandling($e);
        }
    }

    /**
     * Init. Log Channel
     * @throws Exception
     */
    private function prepare_log_channel()
    {
        // Log error
        $this->log_error = new Logger('error');
        $this->log_error->pushHandler(new StreamHandler('./logs/error.log', Logger::DEBUG));
    }
	
	/**
     * Function builder for GET content
     *
     * @param $method
     * @param $url_api
     * @param string $template_path
     * @param array $values
     * @param int $rows_per_page
     * @return array|mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function get_content($method, $url_api, $template_path = '', $values = [], $rows_per_page = 50)
    {
        try {
            if (file_exists($template_path)) {
                static $num_page = 1;
                //$result_data = [];
                $template = file_get_contents($template_path);
                $data = $values;
                $data['rows_per_page'] = $rows_per_page;
                $data['num_page'] = $num_page;
                $body = $this->m->render($template, $data);
                if (is_JSON($body)) {
                    $headers = ['x-auth' => $this->access_token, 'Content-Type' => 'application/json'];
                    $headers_response = [];

                    if ($this->debug) {
                        $one = microtime();
                    }
                    $response = $this->client->request($method, $url_api, ["headers" => $headers, "body" => $body]);
                    if ($this->debug) {
                        $two = microtime();
                        echo 'Total Request time (' . $url_api . '): ' . ($two - $one) . '<br>';
                    }

                    $result = json_decode($response->getBody()->getContents());

                    if (!$result->ResultInfos->Success) {
                        $this->log_error->addDebug("API: " . $result->ResultInfos->ErrorNumber . " : " . $result->ResultInfos->ErrorMessage, array(__FILE__ . ' on line ' . __LINE__));
                    }

                    $result_data = $result->ResultData->Rows;

                    if (count($result_data) == $rows_per_page) {
                        $num_page++;
                        $result_data = array_merge($result_data, $this->get_content($method, $url_api, $template_path, $values, $rows_per_page));
                    } else {
                        $num_page = 1;
                    }

                    return $result_data;

                } else {
                    $this->log_error->addDebug("JSON: Not valid JSON" . substr($body, 0, 80) . "...", array(__FILE__ . ' on line ' . __LINE__));
                }
            } else {
                $this->log_error->addDebug("JSON: The file does not exist", array(__FILE__ . ' on line ' . __LINE__));
            }

        } catch (Exception $e) {
            $response = $this->StatusCodeHandling($e);
            return $response;
        }
    }

    /**
     * Function builder for POST content (insert/update)
     *
     * @param $method
     * @param $url_api
     * @param $template (%OPERATION% replaced by 'insert' or 'update')
     * @param $values
     * @param null $fileid
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function post_content($method, $url_api, $template, $values, $fileid = null)
    {

        try {
            if (isset($fileid) && !empty($fileid)) {
                // UPDATE
                $url_api = $url_api . '/' . $fileid;
                $template_to_load = str_replace('%OPERATION%', 'update', $template);
            } else {
                // INSERT
                $template_to_load = str_replace('%OPERATION%', 'insert', $template);
            }

            if (file_exists($template_to_load)) {
                $template = file_get_contents($template_to_load);
                $data = $values;
                $body = $this->m->render($template, $data);
                if (is_JSON($body)) {
                    $headers = ['x-auth' => $this->access_token, 'Content-Type' => 'application/json'];
                    if ($this->debug) {
                        $one = microtime();
                    }
                    $response = $this->client->request($method, $url_api, ["headers" => $headers, "body" => $body]);
                    if ($this->debug) {
                        $two = microtime();
                        echo 'Total Request time (' . $url_api . '): ' . ($two - $one) . '<br>';
                    }

                    $result = json_decode($response->getBody()->getContents());

                    if (!$result->ResultInfos->Success) {
                        $this->log_error->addDebug("API: " . $result->ResultInfos->ErrorNumber . " : " . $result->ResultInfos->ErrorMessage, array(__FILE__ . ' on line ' . __LINE__));
                    } else {
                        return $result->ResultData->FileId;
                    }
                } else {
                    $this->log_error->addDebug("JSON: Not valid JSON" . $body . "...", array(__FILE__ . ' on line ' . __LINE__));
                }
            } else {
                $this->log_error->addDebug("JSON: The file does not exist", array(__FILE__ . ' on line ' . __LINE__));
            }


        } catch (Exception $e) {
            $response = $this->StatusCodeHandling($e);
            return $response;
        }
    }
	
    /**
     * Status code
     * @param $e
     * @return mixed
     */
    private function StatusCodeHandling($e)
    {
        $this->log_error->addDebug("REQUEST: " . Psr7\str($e->getRequest()), array(__FILE__ . ' on line ' . __LINE__));
        //$this->log_error->addDebug("RESPONSE: " . Psr7\str($e->getResponse()), array(__FILE__ . ' on line ' . __LINE__));
        if (!empty($e->getResponse())) {
            return Psr7\str($e->getResponse());
        }
    }
	
	 /**
     * Get example
     *
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get_companies()
    {
        return $this->get_content('POST', 'Search/Companies', './data/json/api_get_companies.mustache');
    }
	
	 /**
     * Post example
     *
     * @param $company
     * @param null $fileid
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post_company($company, $fileid = null)
    {
        return $this->post_content('POST', 'CUD/Company', './data/json/api_%OPERATION%_company.mustache', $company, $fileid);
    }


}