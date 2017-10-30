<?php
declare(strict_types=1); // must be first line


namespace kdaviesnz\jobsearchnotifier;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use PHPUnit\Runner\Exception;

class JobSearchNotifier implements IJobSearchNotifier
{

    private $keyphrases; // array

    /**
     * JobSearchNotifier constructor.
     */
    public function __construct(array $keyphrases)
    {
        $this->keyphrases = $keyphrases;
    }

    public function getJobs(): array
    {
        $client = new Client();
        $jobs = array();
        foreach ($this->keyphrases as $keyphrase) {
            $fetchedJobs = $this->fetch($keyphrase, $client);
            if (!isset($response["Error"])) {
                $jobs = array_merge($jobs, $fetchedJobs);
            }
        }
        return $jobs;
    }

    public function fetch(string $keyphrase, Client $client): array
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

    }

}