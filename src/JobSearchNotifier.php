<?php
declare(strict_types=1); // must be first line


namespace kdaviesnz\jobsearchnotifier;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Runner\Exception;

class JobSearchNotifier implements IJobSearchNotifier
{

    private $keyphrases = array();
    private $upwork_accessToken = "";
    private $upwork_accessSecret = "";
    private $upwork = array();
    private $consumerKey = "";
    private $consumerSecret = "";

    /**
     * JobSearchNotifier constructor.
     * @param array $keyphrases
     * @param string $upwork_ouathVerifier
     * @param string $upwork_accessToken
     * @param string $upwork_accessSecret
     */
    public function __construct(array $keyphrases, string $upwork_accessToken, string $upwork_accessSecret, string $consumerKey, string $consumerSecret)
    {
        $this->keyphrases = $keyphrases;
        $this->upwork_accessToken = $upwork_accessToken;
        $this->upwork_accessSecret = $upwork_accessSecret;

        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;

        $this->setUpwork();

    }

    public function getJobs()
    {
        $upworkJobs = $this->getUpworkJobs();
        $freelancerJobs = $this->getFreelancerJobs();
        $jobs = array_merge($upworkJobs, $freelancerJobs);
        return $jobs;
    }

    private function setUpwork() {

        @session_start();

        $_SESSION['access_token'] = $this->upwork_accessToken;
        $_SESSION['access_secret']= $this->upwork_accessSecret;

        $this->upwork["config"] = new \Upwork\API\Config(
            array(
                'consumerKey'       => $this->consumerKey,
                'consumerSecret'    => $this->consumerSecret,
                'accessToken'       => $_SESSION['access_token'],       // got access token
                'accessSecret'      => $_SESSION['access_secret'],      // got access secret
//        'verifySsl'         => false,                           // whether to verify SSL
                'debug'             => false,                            // enables debug mode
                "mode" => "nonweb",
                'authType'          => 'OAuthPHPLib' // your own authentication type, see AuthTypes directory
            )
        );

        $this->upwork["client"] = new \Upwork\API\Client($this->upwork["config"]);
    }

    public function getUpworkJobs()
    {

        session_start();

        $client = $this->upwork["client"];
        $config = $this->upwork["config"];

        if (!empty($_SESSION['access_token']) && !empty($_SESSION['access_secret'])) {
            $client->getServer()
                ->getInstance()
                ->addServerToken(
                    $config::get('consumerKey'),
                    'access',
                    $_SESSION['access_token'],
                    $_SESSION['access_secret'],
                    0
                );
        } else {
            // $accessTokenInfo has the following structure
            // array('access_token' => ..., 'access_secret' => ...);
            // keeps the access token in a secure place
            // gets info of authenticated user
            $accessTokenInfo = $client->auth();
        }

        $auth = new \Upwork\API\Routers\Auth($client);
        // $info = $auth->getUserInfo();

        $jobs = new \Upwork\API\Routers\Jobs\Search($client);

        $params = array("q" => implode(" ", $this->keyphrases));
        // var_dump($jobs->find($params));
        $temp = $jobs->find($params);
        $projects = $temp->jobs;

        $projectsParsed = array();

        foreach ($projects as $project) {
            $projectsParsed[] = array(
                "description" => $project->snippet,
                "urgent" => false,
                "id" => $project->id,
                "title" => $project->title,
                "url" => $project->url,
                "date" => $project->date_created,
                "min" => 0,
                "max" => $project->budget,
                "currency" => "USD"
            );
        }

        return $projectsParsed;

    }

    public function getFreelancerJobs(): array
    {
        $client = new Client();
        $jobs = array();
        foreach ($this->keyphrases as $keyphrase) {
            $fetchedJobs = $this->fetchFreelancerProjects($keyphrase, $client);
            if (!isset($response["Error"])) {
                $jobs = array_merge($jobs, $fetchedJobs);
            }
        }
        return $jobs;
    }

    public function fetchFreelancerProjects(string $keyphrase, Client $client): array
    {
        $responseObj = null;

        try {
            $response = $client->request(
                'GET',
                'https://www.freelancer.com/api/projects/0.1/projects/active/?query=' . $keyphrase
            );

            $responseObj = json_decode((String)$response->getBody());

            if ("success" != $responseObj->status) {
                throw new Exception("Status returned error");
            }

        } catch (RequestException $e) {
            return array("Error"=>$e->getCode());
        }

        $projects = $responseObj->result->projects;

        $projectsParsed = array();

        foreach ($projects as $project) {
            $projectsParsed[] = array(
                "description" => $project->preview_description,
                "urgent" => $project->urgent,
                "id" => $project->id,
                "title" => $project->title,
                "url" => "https://www.freelancer.com/projects/" . $project->seo_url,
                "date" => date("Y-m-d H:i:s", $project->time_submitted),
                "min" => $project->budget->minimum,
                "max" => $project->budget->maximum,
                "currency" => $project->currency->code
            );
        }

        return $projectsParsed;

    }

    public function sendMail(string $to)
    {
        $jobs = $this->getJobs();

        $html = "<html><head></head><body>";

        foreach ($jobs as $job) {
            $html .= str_replace(
                array("%URL%", "%TITLE%", "%DESCRIPTON%","%URGENT%","%DATE%", "%MIN%", "%MAX%", "%CURRENCY%"),
                array(
                    $job["url"],
                    $job['title'],
                    $job["description"],
                    ($job["urgent"]?"Urgent ":""),
                    $job["date"], $job["min"],
                    $job["max"],
                    $job["currency"]),
                "<article><h3><a href='%URL%'>%TITLE%</a></h3><p>%DESCRIPTON%</p><p>%URGENT% %DATE% %MIN% to %MAX% %CURRENCY%</p></article>");
        }

        $html .= "</body></html>";

        $subject = 'New jobs';
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";

        $send = mail($to, $subject, $html, $headers);

        var_dump($html);

    }

}